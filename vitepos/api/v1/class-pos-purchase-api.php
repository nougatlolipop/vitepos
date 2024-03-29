<?php
/**
 * Its api for purchase
 *
 * @since: 12/07/2021
 * @author: Sarwar Hasan
 * @version 1.0.0
 * @package VitePos\Api\V1
 */

namespace VitePos\Api\V1;

use Appsbd\V1\libs\API_Data_Response;
use VitePos\Libs\API_Base;
use Vitepos\Models\Database\Mapbd_pos_purchase;
use Vitepos\Models\Database\Mapbd_Pos_Purchase_Item;
use Vitepos\Models\Database\Mapbd_Pos_Warehouse;
use VitePos\Modules\POS_Settings;

/**
 * Class pos_purchase_api
 *
 * @package VitePos\Api\V1
 */
class Pos_Purchase_Api extends API_Base {

	/**
	 * The set api base is generated by appsbd
	 *
	 * @return mixed|string
	 */
	public function set_api_base() {
		return 'purchase';
	}

	/**
	 * The routes is generated by appsbd
	 *
	 * @return mixed|void
	 */
	public function routes() {
		$this->register_rest_route( 'POST', 'list', array( $this, 'purchase_list' ) );
		$this->register_rest_route( 'POST', 'create', array( $this, 'create_purchase' ) );
		$this->register_rest_route( 'GET', 'details/(?P<id>\d+)', array( $this, 'purchase_details' ) );
	}

	/**
	 * The set route permission is generated by appsbd
	 *
	 * @param \VitePos\Libs\any $route Its string.
	 *
	 * @return bool
	 */
	public function set_route_permission( $route ) {
		switch ( $route ) {
			case 'list':
				return current_user_can( 'purchase-menu' ) || current_user_can( 'stock-menu' ) || current_user_can( 'can-see-any-outlet-purchases' );
			case 'create':
				return current_user_can( 'purchase-add' ) || current_user_can( 'stock-add' );
			case 'details':
				return current_user_can( 'purchase-details' );
			default:
				break;
		}

		return parent::set_route_permission( $route );
	}
	/**
	 * The purchase list is generated by appsbd
	 *
	 * @return API_Data_Response
	 */
	public function purchase_list() {
		$mainobj              = new Mapbd_pos_purchase();
		$response_data        = new API_Data_Response();
		$response_data->limit = $this->get_payload( 'limit', 20 );
		$response_data->page  = $this->get_payload( 'page', 1 );
		$src_props            = $this->get_payload( 'src_by', array() );
		$mainobj->set_search_by_param( $src_props );
		if ( ! POS_Settings::is_admin_user() && ! current_user_can( 'can-see-any-outlet-purchases' ) ) {
			$outlets = get_user_meta( $this->get_current_user_id(), 'outlet_id', true );
			if ( is_array( $outlets ) ) {
				$outlet_in = "'" . implode( "','", $outlets ) . "'";
				$mainobj->warehouse_id( "IN ($outlet_in)", true );
			} else {
				$response_data->set_total_records( 0 );
				return $response_data;
			}
		}
		$order_by = 'purchase_date';
		$order    = 'DESC';
		if ( ! empty( $this->payload['sort_by'][0] ) ) {
			$order_by = $this->payload['sort_by'][0]['prop'];
			$order    = $this->payload['sort_by'][0]['ord'];
		}

		if ( $response_data->set_total_records( $mainobj->count_all() ) ) {
			$outlets                = Mapbd_Pos_Warehouse::find_all_by_key_value( 'status', 'A', 'id', 'name' );
			$response_data->rowdata = $mainobj->select_all_grid_data( '', $order_by, $order, $response_data->limit, $response_data->limit_start() );
			foreach ( $response_data->rowdata as &$data ) {
				$data->purchase_date   = appsbd_get_wp_datetime_with_format( $data->purchase_date );
				$data->discount        = doubleval( $data->discount );
				$data->discount_total  = doubleval( $data->discount_total );
				$data->tax_total       = doubleval( $data->tax_total );
				$data->grand_total     = doubleval( $data->grand_total );
				$data->warehouse_title = appsbd_get_text_by_key( $data->warehouse_id, $outlets );
			}
		}
		return $response_data;
	}

	/**
	 * The update Wc Stock is generated by appsbd
	 *
	 * @param any $purchase Its string.
	 */
	public function update_wc_stock( $purchase ) {
		$product = wc_get_product( $purchase->product_id );
		if ( $product->get_manage_stock() ) {
			$stock = floatval( $purchase->in_stock ) + floatval( $purchase->stock_quantity );
			$product->set_stock_quantity( $stock );
			if ( ! $product->save() ) {
				$this->add_error( 'Stock not updated' );
			}
		} else {
			$this->add_error( 'Manage stock is not enabled' );
		}
	}

	/**
	 * The create purchase is generated by appsbd
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function create_purchase() {
		if ( empty( $this->payload['id'] ) ) {
			$purchase_obj = new Mapbd_pos_purchase();
			$purchase_obj->set_from_array( $this->payload );
			$purchase_obj->payment_status( 'P' );
			$purchase_obj->status( 'A' );
			$purchase_obj->purchase_date = gmdate( 'Y-m-d H:i:s' );
			$purchase_obj->added_by( $this->get_current_user_id() );
			if ( $purchase_obj->is_valid_form( true ) ) {
				if ( ! empty( $this->payload['purchase_items'] ) ) {
					$is_item_ok     = true;
					$purchase_items = array();
					foreach ( $this->payload['purchase_items'] as $purchase_item ) {
						$p_items = new Mapbd_Pos_Purchase_Item();
						$p_items->set_from_array( $purchase_item );
						if ( ! $p_items->is_valid_form( true ) ) {
							$is_item_ok = false;
						} else {
							$purchase_items[] = $p_items;
						}
					}
					if ( $is_item_ok ) {
						$is_item_save_ok = true;
						if ( $purchase_obj->save() ) {
							foreach ( $purchase_items as $purchase_item_obj ) {
								$purchase_item_obj->purchase_id( $purchase_obj->id );
								if ( $purchase_item_obj->Save() ) {
									$this->update_wc_stock( $purchase_item_obj );
								} else {
									$is_item_save_ok = false;
								}
							}
							if ( $is_item_save_ok ) {
								$this->response->set_response( true, 'Successfully purchased' );
								return $this->response;
							}
						}
					}
				} else {
					$this->add_error( 'Purchased failed' );
				}
			}
			$this->response->set_response( false, '' );
			return $this->response;
		} else {
			return $this->update_purchase();
		}

	}

	/**
	 * The UpdatePurchase is generated by appsbd
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function update_purchase() {
		$old_object = Mapbd_pos_purchase::find_by( 'id', $this->payload['id'] );
		if ( $old_object ) {
			$purchase_obj = new Mapbd_pos_purchase();

			oldDataArrayMerge( $old_object->get_properties_array(), $this->payload );
			$purchase_obj->set_from_array( $this->payload );
			$purchase_obj->unset_all_excepts( 'vendor_id,warehouse_id,grand_total,order_tax,tax_type,discount,discount_type,shipping_cost,purchase_note' );
			if ( $purchase_obj->is_valid_form( false ) ) {
				$is_item_ok = true;
				if ( ! empty( $this->payload['purchase_items'] ) ) {
					$purchase_items = array();
					foreach ( $this->payload['purchase_items'] as $purchase_item ) {
						$p_items = new Mapbd_pos_purchase_item();
						$p_items->set_from_array( $purchase_item );
						if ( ! $p_items->is_valid_form( false ) ) {
							$is_item_ok = false;
						} else {
							$purchase_items[] = $p_items;
						}
					}
				} else {
					$this->add_error( 'No item found' );
				}
				if ( $is_item_ok ) {
					$is_item_update_ok = false;
					$purchase_obj->set_where_update( 'id', $this->payload['id'] );
					if ( $purchase_obj->update() ) {
						$is_item_update_ok = true;
					}
					foreach ( $purchase_items as $purchase_item_obj ) {
						$purchase_item_obj->purchase_id( $purchase_obj->id );
						$purchase_item_obj->SetWhereUpdate( 'id', $purchase_item_obj->id );
						$old_item = Mapbd_pos_purchase_item::FindBy( 'id', $purchase_item_obj->id, array( 'purchase_id' => $purchase_obj->id ) );

						if ( $purchase_item_obj->Update() ) {

							if ( $old_item ) {
								$dif = $purchase_item_obj->stock_quantity - $old_item->stock_quantity;
								if ( $dif > 0 ) {
									$total_qty = wc_update_product_stock( absint( $purchase_item_obj->product_id ), absint( $dif ), 'increase' );
								} elseif ( $dif < 0 ) {
									$total_qty = wc_update_product_stock( absint( $purchase_item_obj->product_id ), absint( $dif ), 'decrease' );
								}
							}
						}
						$is_item_update_ok = true;
					}
					if ( $is_item_update_ok ) {
						$this->response->set_response( true, 'Successfully updated' );
						return $this->response;
					}
				}
			}
		} else {
			$this->add_error( 'No data found with request param' );
		}
		$this->response->set_response( false, appsbd_get_msg_api() );
		return $this->response;
	}

	/**
	 * The purchase details is generated by appsbd
	 *
	 * @param any $data Its string.
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function purchase_details( $data ) {
		if ( ! empty( $data['id'] ) ) {
			$id           = intval( $data['id'] );
			$purchase_obj = new Mapbd_pos_purchase();
			$purchase_obj->id( $id );
			if ( ! POS_Settings::is_admin_user() && ! current_user_can( 'can-see-any-outlet-purchases' ) ) {
				$outlets = get_user_meta( $this->get_current_user_id(), 'outlet_id', true );
				if ( is_array( $outlets ) ) {
					$outlet_in = "'" . implode( "','", $outlets ) . "'";
					$purchase_obj->warehouse_id( "IN ($outlet_in)", true );
				} else {
					$this->add_error( "You don't have permission to view details of this outlet" );
					$this->set_response( false );
					return $this->response->get_response();
				}
			}
			if ( $purchase_obj->Select() ) {
				$purchase_obj->warehouse_title = POS_Settings::get_module_instance()->__( 'Unknown' );
				$outletobj                     = Mapbd_Pos_Warehouse::find_by( 'id', $purchase_obj->warehouse_id );
				if ( ! empty( $outletobj ) ) {
					$purchase_obj->warehouse_title = $outletobj->name;
				}
				$user                   = get_user_by( 'ID', $purchase_obj->added_by );
				$purchase_obj->added_by = $user->first_name ? $user->first_name . ' ' . $user->last_name : $user->user_nicename;
				$p_item                 = new Mapbd_Pos_Purchase_Item();
				$p_item->purchase_id( $purchase_obj->id );
				$purchase_obj->purchase_items = $p_item->select_all_grid_data();
				foreach ( $purchase_obj->purchase_items as $item ) {
					$product = wc_get_product( $item->product_id );
					if ( $product ) {
						$item->in_stock = $product->get_stock_quantity();
					} else {
						$item->in_stock = 0;
					}
				}
				$this->set_response( true, 'Data found', $purchase_obj );
				return $this->response->get_response();
			}
		}
		$this->set_response( false, 'data not found or invalid param' );
		return $this->response->get_response();
	}

}
