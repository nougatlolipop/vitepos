<?php
/**
 * Its api for product
 *
 * @since: 12/07/2021
 * @author: Sarwar Hasan
 * @version 1.0.0
 * @package VitePos\Api\V1
 */

namespace VitePos\Api\V1;

use Appsbd\V1\libs\API_Data_Response;
use Appsbd\V1\libs\AppInput;
use VitePos\Libs\API_Base;
use VitePos\Libs\POS_Product;
use VitePos\Modules\POS_Settings;

/**
 * Class pos_product_api
 *
 * @package VitePos\Api\V1
 */
class Pos_Product_Api extends API_Base {

	/**
	 * The set api base is generated by appsbd
	 *
	 * @return mixed|string
	 */
	public function set_api_base() {
		return 'product';
	}

	/**
	 * The routes is generated by appsbd
	 *
	 * @return mixed|void
	 */
	public function routes() {
		$this->register_rest_route( 'POST', 'list', array( $this, 'product_list' ) );
		$this->register_rest_route( 'POST', 'scan-product', array( $this, 'scan_product' ) );
		$this->register_rest_route( 'POST', 'list-variation', array( $this, 'product_with_variation_list' ) );
		$this->register_rest_route( 'GET', 'categories', array( $this, 'categories' ) );
		$this->register_rest_route( 'GET', 'all-categories', array( $this, 'all_categories' ) );
		$this->register_rest_route( 'GET', 'attributes', array( $this, 'attributes' ) );
		$this->register_rest_route( 'GET', 'getStock/(?P<id>\d+)', array( $this, 'getStock' ) );
		$this->register_rest_route( 'GET', 'details/(?P<id>\d+)', array( $this, 'product_details' ) );
		$this->register_rest_route( 'POST', 'create', array( $this, 'create_product' ) );
		$this->register_rest_route( 'POST', 'update', array( $this, 'update_product' ) );
		$this->register_rest_route( 'POST', 'delete-product', array( $this, 'delete_product' ) );
		$this->register_rest_route( 'POST', 'make-favorite', array( $this, 'make_favorite' ) );
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
			case 'update':
				return current_user_can( 'product-edit' );
			case 'create':
				return current_user_can( 'product-add' );
			case 'delete-product':
				return current_user_can( 'product-delete' );
			default:
				break;
		}

		return parent::set_route_permission( $route );
	}

	/**
	 * The query search filter is generated by appsbd
	 *
	 * @param \VitePos\Libs\any $where Its string.
	 * @param \VitePos\Libs\any $wp_query Its string.
	 *
	 * @return mixed|string|\VitePos\Libs\any
	 */
	public function query_search_filter( $where, $wp_query ) {
		$api_src = $wp_query->get( 'api_src' );
		if ( ! empty( $api_src ) ) {
			foreach ( $api_src as $src_item ) {
				if ( ! empty( $src_item ) ) {
					$where .= $src_item;
				}
			}
		}

		return $where;
	}
	/**
	 * The product list is generated by appsbd
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function scan_product() {
		self::set_vite_pos_request();
		$barcode   = $this->get_payload( 'barcode', '' );
		$cart_item = vitepos_get_product_by_barcode( $barcode );
		$this->response->set_response( ! empty( $cart_item ), '', $cart_item );
		return $this->response;
	}
	/**
	 * The product list is generated by appsbd
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function product_list() {
		self::set_vite_pos_request();
		$page                 = $this->get_payload( 'page', 1 );
		$limit                = $this->get_payload( 'limit', 20 );
		$src_props            = $this->get_payload( 'src_by', array() );
		$sort_by_props        = $this->get_payload( 'sort_by', array() );

		$response_product     = POS_Product::get_product_from_woo_products( $page, $limit, $src_props, $sort_by_props );
		$response_data        = new API_Data_Response();
		$response_data->page  = $page;
		$response_data->limit = $limit;

		if ( $response_data->set_total_records( $response_product->records ) ) {
			$response_data->rowdata = $response_product->products;
		}
		$this->response->set_response( true, '', $response_data );

		return $this->response;
	}

	/**
	 * The product list is generated by appsbd
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function product_with_variation_list() {
		self::set_vite_pos_request();
		$page                 = $this->get_payload( 'page', 1 );
		$limit                = $this->get_payload( 'limit', 20 );
		$src_props            = $this->get_payload( 'src_by', array() );
		$sort_by_props        = $this->get_payload( 'sort_by', array() );
		$response_product     = POS_Product::get_product_from_woo_products_with_variations(
			$page,
			$limit,
			$src_props,
			$sort_by_props
		);
		$response_data        = new API_Data_Response();
		$response_data->page  = $page;
		$response_data->limit = $limit;

		if ( $response_data->set_total_records( $response_product->records ) ) {
			$response_data->rowdata = $response_product->products;
		}
		$this->response->set_response( true, '', $response_data );

		return $this->response;
	}

	/**
	 * The categories is generated by appsbd
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function categories() {
		$response_product = POS_Product::get_categories();
		$this->response->set_response( true, '', $response_product );

		return $this->response;
	}
	/**
	 * The categories is generated by appsbd
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function all_categories() {
		$response_product = POS_Product::get_categories(true);
		$this->response->set_response( true, '', $response_product );

		return $this->response;
	}

	/**
	 * The attributes is generated by appsbd
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function attributes() {
		$response_product = array();
		$attrs_product    = wc_get_attribute_taxonomies();
		foreach ( $attrs_product as $attr ) {
			$attr_item          = new \stdClass();
			$attr_item->id      = $attr->attribute_id;
			$attr_item->name    = $attr->attribute_label;
			$attr_item->slug    = wc_attribute_taxonomy_name( $attr->attribute_name );
			$attr_item->visible = ! empty( $attr->attribute_public );
			$attr_item->options = array();
			$terms              = get_terms(
				array(
					'taxonomy'   => $attr_item->slug,
					'hide_empty' => false,
				)
			);
			foreach ( $terms as $term ) {
				$term_item            = new \stdClass();
				$term_item->id        = $term->term_id;
				$term_item->name      = $term->name;
				$term_item->slug      = $term->slug;
				$attr_item->options[] = $term_item;
			}
			$response_product[] = $attr_item;
		}
		$this->response->set_response( true, '', $response_product );

		return $this->response;
	}

	/**
	 * The getProductStockById is generated by appsbd
	 *
	 * @param any $id Its integer.
	 *
	 * @return \stdClass|null
	 */
	private function get_product_stock_by_id( $id ) {
		$product = wc_get_product( $id );
		if ( ! empty( $product ) ) {
			$product_obj                   = new \stdClass();
			$product_obj->id               = $product->get_id();
			$product_obj->name             = $product->get_name();
			$product_obj->stock_quantity   = $product->get_stock_quantity();
			$product_obj->low_stock_amount = $product->get_low_stock_amount();
			$product_obj->manage_stock     = $product->get_manage_stock();

			return $product_obj;
		}

		return null;
	}

	/**
	 * The setAttributes is generated by appsbd
	 *
	 * @param any $args Its string.
	 * @param any $type Its string.
	 *
	 * @return array
	 */
	public function get_attributes( $args, $type ) {
		$pos        = 0;
		$attributes = array();
		foreach ( $args as $attr ) {
			$attribute_object = new \WC_Product_Attribute();
			$opt_arr          = array();
			$attr['id']       = ! empty( $attr['id'] ) ? absint( $attr['id'] ) : $attr['id'];
			if ( ! empty( $attr['id'] ) ) {
				$attribute_object->set_id( wc_attribute_taxonomy_id_by_name( $attr['slug'] ) );
				$attribute_object->set_name( $attr['slug'] );
			} else {
				$attribute_object->set_name( $attr['name'] );

			}
			foreach ( $attr['options'] as $option ) {
				$opt_arr[] = $option['name'];
			}
			if ( 'variable' == $type ) {
				$attribute_object->set_variation( true );
			}
			$attribute_object->set_position( $pos++ );
			$attribute_object->set_options( $opt_arr );
			$attribute_object->set_visible( $attr['visible'] );
			array_push( $attributes, $attribute_object );
		}
		return $attributes;
	}

	/**
	 * The getStock is generated by appsbd
	 *
	 * @param any $data Its string.
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function get_stock( $data ) {
		if ( ! empty( $data['id'] ) ) {
			$id          = intval( $data['id'] );
			$product_obj = $this->get_product_stock_by_id( $id );
			$this->set_response( true, 'data found', $product_obj );

			return $this->response;
		}
		$this->set_response( false, 'data not found or invalid param' );

		return $this->response;
	}

	/**
	 * The delete product is generated by appsbd
	 *
	 *  @return \Appsbd\V1\libs\API_Response
	 */
	public function delete_product() {
		if ( ! empty( $this->payload ) ) {
			$id      = intval( $this->payload['id'] );
			$product = wc_get_product( $id );
			if ( ! empty( $product ) ) {
				if ( $product->is_type( 'variable' ) ) {
					foreach ( $product->get_children() as $child_id ) {
						$this->delete_variationProduct( $child_id );
					}
				}
				if ( $product->delete() ) {
					$this->add_info( 'Successfully deleted' );
					$this->response->set_response( true, '' );
					return $this->response;
				} else {
					$this->add_error( 'Delete failed' );
					$this->response->set_response( false, '' );
					return $this->response;
				}
			} else {
				$this->add_error( 'Delete failed' );
				$this->response->set_response( false, '' );
				return $this->response;
			}
		} else {
			$this->add_error( 'Invalid request' );
			$this->response->set_response( false, '' );
			return $this->response;
		}
	}
	/**
	 * The delete product is generated by appsbd
	 *
	 *  @return \Appsbd\V1\libs\API_Response
	 */
	public function make_favorite() {
		if ( ! empty( $this->payload ) ) {
			$id      = intval( $this->payload['id'] );
			$product = wc_get_product( $id );
			if ( ! empty( $product ) ) {
				if ( $product->meta_exists( '_vt_is_favorite' ) ) {
					$product->update_meta_data( '_vt_is_favorite',$this->get_payload('status','N') );
				} else {
					$product->add_meta_data( '_vt_is_favorite',$this->get_payload('status','N') );
				}
				if ($product->save())
				{
					$this->add_info( 'Successfully updated' );
					$this->response->set_response( true );
					return $this->response;
				}else{
					$this->add_error( 'Delete failed' );
					$this->response->set_response( false, '' );
					return $this->response;
				}

			} else {
				$this->add_error( 'Delete failed' );
				$this->response->set_response( false, '' );
				return $this->response;
			}
		} else {
			$this->add_error( 'Invalid request' );
			$this->response->set_response( false, '' );
			return $this->response;
		}
	}

	/**
	 * The delete variationProduct is generated by appsbd
	 */
	public function delete_variationProduct( $child_id ) {
		$child = wc_get_product( $child_id );
		if ( $child ) {
			$child->delete();
			return $child;
		} else {
			$this->add_error( 'invalid requiest' );
		}

	}

	/**
	 * The call product action is generated by appsbd
	 *
	 * @param mixed $product_id Its product id.
	 */
	public function call_product_action( $product_id ) {
		/**
		 * Its for product feature update
		 *
		 * @since 1.0
		 */
		do_action( 'apbd-vtpos/action/save-product-feature-image', $product_id );
		$rm_gallery = $this->get_payload( 'rm_gallery', array() );
		/**
		 * Its for product feature update
		 *
		 * @since 1.0
		 */
		do_action( 'apbd-vtpos/action/save-product-gallery-image', $product_id, $rm_gallery );
	}

	/**
	 * The call product variation action is generated by appsbd
	 *
	 * @param mixed $variation_id Its variation id.
	 * @param mixed $variation_index Its variation index.
	 * @param mixed $product_id Its product id.
	 */
	public function call_product_variation_action( $variation_id, $variation_index, $product_id ) {
		/**
		 * Its for product feature update
		 *
		 * @since 1.0
		 */
		do_action( 'apbd-vtpos/action/save-product-variation-image', $variation_id, $variation_index, $product_id );

	}
	/**
	 * The create product is generated by appsbd
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function create_product() {
		if ( ! empty( $this->payload ) ) {
			if ( 'variable' == $this->payload['type'] ) {
				$product = new \WC_Product_Variable();
				$product->set_name( $this->get_payload( 'name', '' ) );
				$product->set_category_ids( $this->get_payload( 'categories', array() ) );
				$product->set_category_ids( ['62'] );
				$product->set_upsell_ids( $this->get_payload( 'up_sale', array() ) );
				$product->set_cross_sell_ids( $this->get_payload( 'cross_sale', array() ) );
				$product->set_description( $this->get_payload( 'description', '' ) );
				if ( '' != $this->payload['slug'] ) {
					$product->set_slug( $this->payload['slug'] );
				}
				$product->set_slug( str_replace( ' ', '_', strtolower( $this->payload['name'] ) ) );

				if ( '' == $this->payload['sku'] ) {
					$product->set_sku( str_replace( ' ', '-', strtolower( $this->payload['name'] ) ) );
				}
				$product->set_sku( $this->payload['sku'] );
				$product->set_attributes( $this->get_attributes( $this->payload['attributes'], $this->payload['type'] ) );
				$product->set_tax_status( $this->get_payload( 'tax_status', '' ) );
				$product->set_tax_class( $this->get_payload( 'tax_class', 'standard' ) );
				$product->set_weight( $this->get_payload( 'weight', '0.00' ) );
				$product->set_height( $this->get_payload( 'height', '0.00' ) );
				$product->set_length( $this->get_payload( 'length', '0.00' ) );
				$product->set_width( $this->get_payload( 'width', '0.00' ) );
				$product->add_meta_data( '_vt_barcode', str_replace( ' ', '-', strtolower( $this->payload['barcode'] ) ) );
				$product->add_meta_data( '_vt_is_favorite',$this->get_payload('is_favorite','N') );
				$product_id = $product->save();
				if ( ! empty( $product_id ) ) {
					$this->call_product_action( $product_id );
					foreach ( $this->get_payload( 'variations', array() ) as $v_ind => $vari ) {
						$variation = $this->add_variation_product( $product_id, $vari, $product->get_name() );
						if ( ! empty( $variation ) ) {
							$this->call_product_variation_action( $variation->get_id(), $v_ind, $product_id );
						}
					}
					$product     = wc_get_product( $product_id );
					$pos_product = POS_Product::get_product_data( $product );
					$this->add_info( 'Product created successfully' );
					$this->set_response( true, '', $pos_product );
					return $this->response->get_response();
				} else {
					$this->add_error( 'Product creation failed' );
					$this->set_response( false );

					return $this->response->get_response();
				}
			} else {
				$new_product = new \WC_Product_Simple();
				$new_product->set_name( $this->get_payload( 'name', '' ) );
				if ( floatval( $this->payload['sale_price'] ) > 0 ) {
					$new_product->set_price( floatval( $this->get_payload( 'sale_price', 0.00 ) ) );
					$new_product->set_sale_price( floatval( $this->get_payload( 'sale_price', 0.00 ) ) );
				} else {
					$new_product->set_price( floatval( $this->get_payload( 'regular_price', 0.00 ) ) );
				}
				$new_product->set_regular_price( floatval( $this->get_payload( 'regular_price', 0.00 ) ) );
				$new_product->set_category_ids( $this->get_payload( 'categories', array() ) );
				$new_product->set_upsell_ids( $this->get_payload( 'up_sale', array() ) );
				$new_product->set_cross_sell_ids( $this->get_payload( 'cross_sale', array() ) );
				$new_product->set_description( $this->get_payload( 'description', '' ) );
				$tax_status=$this->get_payload( 'tax_status', '' );
				if(!empty($tax_status)) {
					$new_product->set_tax_status( $tax_status );
				}
				$tax_class=$this->get_payload( 'tax_class', '' );
				if(!empty($tax_class)) {
					$new_product->set_tax_class( $tax_class );
				}
				$new_product->set_weight( $this->get_payload( 'weight', '0.00' ) );
				$new_product->set_height( $this->get_payload( 'height', '0.00' ) );
				$new_product->set_length( $this->get_payload( 'length', '0.00' ) );
				$new_product->set_width( $this->get_payload( 'width', '0.00' ) );
				if ( ! empty( $this->payload['attributes'] ) ) {
					$new_product->set_attributes(
						$this->get_attributes(
							$this->get_payload( 'attributes', array() ),
							$this->get_payload( 'type', '' )
						)
					);
				}
				if ( '' != $this->payload['slug'] ) {
					$new_product->set_slug( strtolower( $this->get_payload( 'slug' ) ) );
				} else {
					$new_product->set_slug( sanitize_title( strtolower( $this->get_payload( 'name' ) ) ) );
				}
				$sku = ( '' == $this->payload['sku'] ) ? str_replace(
					' ',
					'-',
					strtolower( $this->payload['name'] )
				) : str_replace(
					' ',
					'-',
					strtolower( $this->payload['sku'] )
				);
				if ( wc_get_product_id_by_sku( $sku ) ) {
					if ( '' == $this->payload['sku'] ) {
						$this->add_error( 'Please Provide a SKU or Change The name of Product' );
						$this->add_error( 'SKU already Exist' );

						return $this->response->get_response();
					}
					$this->add_error( 'SKU already Exist' );

					return $this->response->get_response();
				} else {
					$new_product->set_sku( $sku );
				}

				if ( 1 == $this->payload['manage_stock'] ) {
					$quantity         = floatval( $this->payload['stock_quantity'] );
					$low_stock_amount = floatval( $this->payload['low_stock_amount'] );
					$new_product->set_manage_stock( true );
					$new_product->set_stock_quantity( $quantity );
					$new_product->set_low_stock_amount( $low_stock_amount );
				}
				$new_product->set_stock_status( $this->get_payload( 'stock_status', 'instock' ) );
				$new_product->add_meta_data( '_vt_purchase_cost', $this->get_payload( 'purchase_cost', 0 ) );
				$new_product->add_meta_data( '_vt_is_favorite',$this->get_payload('is_favorite','N') );
				$new_product->add_meta_data(
					'_vt_barcode',
					str_replace( ' ', '-', strtolower( $this->payload['barcode'] ) )
				);
				$product_id = $new_product->save();
				if ( null != $product_id ) {
					$this->call_product_action( $product_id );
					$product     = wc_get_product( $product_id );
					$pos_product = POS_Product::get_product_data( $product );
					$this->add_info( 'Product added successfully' );
					$this->set_response( true, '', $pos_product );
					return $this->response->get_response();
				} else {
					$this->add_error( 'data missing' );
					$this->set_response( false, '', null );

					return $this->response->get_response();
				}
			}
		}

		return $this->response;
	}

	/**
	 * The update product is generated by appsbd.
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 * @throws \WC_Data_Exception Throws error.
	 */
	public function update_product() {
		if ( 'simple' == $this->payload['type'] ) {
			$new_product = wc_get_product( $this->payload['id'] );
			$new_product->set_name( $this->get_payload( 'name', '' ) );
			if ( floatval( $this->payload['sale_price'] ) > 0 ) {
				$new_product->set_price( floatval( $this->get_payload( 'sale_price', 0.00 ) ) );
				$new_product->set_sale_price( floatval( $this->get_payload( 'sale_price', 0.00 ) ) );
			} else {
				$new_product->set_price( floatval( $this->get_payload( 'regular_price', 0.00 ) ) );
			}
			$new_product->set_regular_price( floatval( $this->get_payload( 'regular_price', 0.00 ) ) );
			$new_product->set_category_ids( $this->get_payload( 'categories', array() ) );
			$new_product->set_upsell_ids( $this->get_payload( 'up_sale', array() ) );
			$new_product->set_cross_sell_ids( $this->get_payload( 'cross_sale', array() ) );
			$new_product->set_description( $this->get_payload( 'description', '' ) );
			$new_product->set_tax_status( $this->get_payload( 'tax_status', '' ) );
			$new_product->set_tax_class( $this->get_payload( 'tax_class', '' ) );
			$new_product->set_weight( $this->get_payload( 'weight', '0.00' ) );
			$new_product->set_height( $this->get_payload( 'height', '0.00' ) );
			$new_product->set_length( $this->get_payload( 'length', '0.00' ) );
			$new_product->set_width( $this->get_payload( 'width', '0.00' ) );
			if ( ! empty( $this->payload['attributes'] ) ) {
				$new_product->set_attributes(
					$this->get_attributes(
						$this->get_payload( 'attributes', array() ),
						$this->get_payload( 'type', '' )
					)
				);
			}
			if ( '' != $this->payload['slug'] ) {
				$new_product->set_slug( strtolower( $this->get_payload( 'slug' ) ) );
			} else {
				$new_product->set_slug( sanitize_title( strtolower( $this->get_payload( 'name' ) ) ) );
			}
			$sku = ( '' == $this->payload['sku'] ) ? str_replace(
				' ',
				'-',
				strtolower( $this->payload['name'] )
			) : str_replace( ' ', '-', strtolower( $this->payload['sku'] ) );
			if ( wc_get_product_id_by_sku( $sku ) && wc_get_product_id_by_sku( $sku ) != $this->payload['id'] ) {
				if ( '' == $this->payload['sku'] ) {
					$this->add_error( 'Please provide a SKU or change the name of product' );
					$this->add_error( 'SKU already Exist' );

					return $this->response->get_response();
				}
				$this->add_error( 'SKU already exist' );

				return $this->response->get_response();
			} else {
				$new_product->set_sku( $sku );
			}
			if ( 1 == $this->payload['manage_stock'] ) {
				$quantity         = floatval( $this->payload['stock_quantity'] );
				$low_stock_amount = floatval( $this->payload['low_stock_amount'] );
				$new_product->set_manage_stock( true );
				$new_product->set_stock_quantity( $quantity );
				$new_product->set_low_stock_amount( $low_stock_amount );
			}
			$new_product->set_stock_status( $this->get_payload( 'stock_status', 'instock' ) );
			if ( $new_product->meta_exists( '_vt_purchase_cost' ) ) {
				$new_product->update_meta_data( '_vt_purchase_cost', $this->get_payload( 'purchase_cost', 50 ) );
			} else {
				$new_product->add_meta_data( '_vt_purchase_cost', $this->get_payload( 'purchase_cost', 50 ) );
			}
			if ( $new_product->meta_exists( '_vt_barcode' ) ) {
				$new_product->update_meta_data( '_vt_barcode', $this->get_payload( 'barcode', '' ) );
			} else {
				$new_product->add_meta_data( '_vt_barcode', $this->get_payload( 'barcode', '' ) );
			}
			if ( $new_product->meta_exists( '_vt_is_favorite' ) ) {
				$new_product->update_meta_data( '_vt_is_favorite',$this->get_payload('is_favorite','N') );
			} else {
				$new_product->add_meta_data( '_vt_is_favorite',$this->get_payload('is_favorite','N') );
			}
			$product_id = $new_product->save();
			$this->call_product_action( $product_id );
			if ( null != $product_id ) {
				POS_Settings::get_module_instance()->wc_force_product_sync_id( $product_id );
				$this->add_info( 'Successfully updated' );
				$this->response->set_response( true );
			} else {
				$this->add_error( 'data missing' );
				$this->response->set_response( false );
			}

			$product = wc_get_product( $this->payload['id'] );
			if ( ! empty( $product ) ) {
				$pos_product = POS_Product::get_product_data( $product );
				$this->response->set_data( $pos_product );
			}
			return $this->response->get_response();
		} else {
			$product = wc_get_product( $this->payload['id'] );
			$product->set_name( $this->get_payload( 'name', '' ) );
			$product->set_category_ids( $this->get_payload( 'categories', array() ) );
			$product->set_upsell_ids( $this->get_payload( 'up_sale', array() ) );
			$product->set_cross_sell_ids( $this->get_payload( 'cross_sale', array() ) );
			$product->set_description( $this->get_payload( 'description', '' ) );
			if ( '' != $this->payload['slug'] ) {
				$product->set_slug( $this->payload['slug'] );
			}
			$product->set_slug( str_replace( ' ', '_', strtolower( $this->payload['name'] ) ) );
			$sku = ( '' == $this->payload['sku'] ) ? str_replace( ' ', '-', strtolower( $this->payload['name'] ) ) : str_replace( ' ', '-', strtolower( $this->payload['sku'] ) );
			if ( wc_get_product_id_by_sku( $sku ) && wc_get_product_id_by_sku( $sku ) != $this->payload['id'] ) {
				if ( '' == $this->payload['sku'] ) {
					$this->add_error( 'Please provide a SKU or change the name of product' );
					$this->add_error( 'SKU already exist' );
					return $this->response->get_response();
				}
				$this->add_error( 'SKU already exist' );
				return $this->response->get_response();
			} else {
				$product->set_sku( $sku );
			}
			if ( $product->meta_exists( '_vt_barcode' ) ) {
				$product->update_meta_data(
					'_vt_barcode',
					str_replace( ' ', '-', strtolower( $this->payload['barcode'] ) )
				);
			} else {
				$product->add_meta_data( '_vt_barcode', str_replace( ' ', '-', strtolower( $this->payload['barcode'] ) ) );
			}
			if ( $product->meta_exists( '_vt_is_favorite' ) ) {
				$product->update_meta_data( '_vt_is_favorite',$this->get_payload('is_favorite','N') );
			} else {
				$product->add_meta_data( '_vt_is_favorite',$this->get_payload('is_favorite','N') );
			}
			$product->set_attributes( $this->get_attributes( $this->payload['attributes'], $this->payload['type'] ) );
			$product->set_tax_status( $this->get_payload( 'tax_status', '' ) );
			$product->set_tax_class( $this->get_payload( 'tax_class', 'standard' ) );
			$product->set_weight( $this->get_payload( 'weight', '0.00' ) );
			$product->set_height( $this->get_payload( 'height', '0.00' ) );
			$product->set_length( $this->get_payload( 'length', '0.00' ) );
			$product->set_width( $this->get_payload( 'width', '0.00' ) );
			$product_id = $product->save();
			$this->call_product_action( $product_id );
			if ( ! empty( $product_id ) ) {
				$children_ids = $product->get_children();
				$id_vari      = array_column( $this->get_payload( 'variations', array() ), 'id' );
				$diff         = array_diff( $children_ids, $id_vari );
				if ( count( $diff ) > 0 ) {
					foreach ( $diff as $child ) {
						$this->delete_variationProduct( $child );
					}
				}
				foreach ( $this->get_payload( 'variations', array() ) as $v_ind => $vari ) {
					$varation = $this->add_variation_product( $product_id, $vari, $product->get_name() );
					if ( ! empty( $varation ) ) {
						$this->call_product_variation_action( $varation->get_id(), $v_ind, $product_id );
					}
				}
				$this->add_info( 'Successfully updated' );
				POS_Settings::get_module_instance()->wc_force_product_sync_id( $product_id );
				$this->response->set_response( true );
			} else {
				$this->add_error( 'Data missing' );
				$this->response->set_response( false );
			}
			$product = wc_get_product( $this->payload['id'] );
			if ( ! empty( $product ) ) {
				$pos_product = POS_Product::get_product_data( $product );
				$this->response->set_data( $pos_product );
			}
			return $this->response->get_response();
		}
	}

	/**
	 * The add variation product is generated by appsbd
	 *
	 * @param any $parent_id Parent_id is for product id.
	 * @param any $vari     Variation product property.
	 * @param any $name     Name of the product.
	 *
	 * @return \Appsbd\V1\libs\API_Response|false|\WC_Product|\WC_Product_Variation
	 * @throws \WC_Data_Exception Throws error.
	 */
	public function add_variation_product( $parent_id, $vari, $name ) {
		if ( $vari['id'] ) {
			$variation = wc_get_product( $vari['id'] );
		} else {
			$variation = new \WC_Product_Variation();
		}
		$variation->set_manage_stock( $vari['manage_stock'] );
		$variation->set_stock_quantity( $vari['stock_quantity'] );
		$variation->set_low_stock_amount( $vari['low_stock_amount'] );
		$variation->set_parent_id( $parent_id );
		$variation->set_status( 'publish' );
		if ( floatval( $vari['sale_price'] ) > 0 ) {
			$variation->set_sale_price( floatval( $vari['sale_price'] ) );
		} else {
			$variation->set_sale_price( floatval( $vari['regular_price'] ) );
		}
		$variation->set_regular_price( $vari['regular_price'] );
		$variation->set_stock_status( 'instock' );
		$attributes = array();
		foreach ( $vari['attributes'] as $attr ) {
			if ( ! empty( $attr['slug'] ) ) {
								$attributes[ $attr['slug'] ] = $attr['option'];
				unset( $attr['slug'] );
			}
		}
		$variation->set_attributes( $attributes );
		$slg     = $name . '-' . implode( '-', $attributes );
		$sku     = ( '' == $vari['sku'] ) ? str_replace( ' ', '-', strtolower( $slg ) ) : str_replace(
			' ',
			'-',
			strtolower( $vari['sku'] )
		);
		$vari_id = wc_get_product_id_by_sku( $sku );
		if ( ! empty( $vari['id'] ) && ! empty( $vari['sku'] ) && ! empty( $vari_id ) ) {
			if ( $vari_id != $vari['id'] ) {
				$this->add_error( 'Variation SKU already exist' );
				return $this->response->get_response();
			} else {
				$variation->set_sku( $sku );
			}
		} else {
			if ( ! empty( $vari_id ) ) {
				if ( '' == $vari['sku'] ) {
					$this->add_error( 'Please provide valid variation SKU or change name of product' );
					$this->add_error( 'Variation SKU already exist' );
					return $this->response->get_response();
				}
				$this->add_error( 'Variation SKU already exist' );

				return $this->response->get_response();
			} else {
				$variation->set_sku( $sku );
			}
		}
		if ( $variation->meta_exists( '_vt_purchase_cost' ) ) {
			$variation->update_meta_data( '_vt_purchase_cost', $vari['purchase_cost'] );
		} else {
			$variation->add_meta_data( '_vt_purchase_cost', $vari['purchase_cost'] );
		}
		if ( $variation->meta_exists( '_vt_barcode' ) ) {
			$variation->update_meta_data( '_vt_barcode', str_replace( ' ', '-', strtolower( $vari['barcode'] ) ) );
		} else {
			$variation->add_meta_data( '_vt_barcode', str_replace( ' ', '-', strtolower( $vari['barcode'] ) ) );
		}
		if ( $variation->meta_exists( 'is_parent_dimension' ) ) {
			$variation->update_meta_data( 'is_parent_dimension', $vari['is_parent_dimension'] );
		} else {
			$variation->add_meta_data( 'is_parent_dimension', $vari['is_parent_dimension'] );
		}
		if ( $vari['is_parent_dimension'] ) {
			$variation->set_tax_status( $this->get_payload( 'tax_status', '' ) );
			$variation->set_tax_class( $this->get_payload( 'tax_class', 'standard' ) );
			$variation->set_weight( $this->get_payload( 'weight', '0.00' ) );
			$variation->set_height( $this->get_payload( 'height', '0.00' ) );
			$variation->set_length( $this->get_payload( 'length', '0.00' ) );
			$variation->set_width( $this->get_payload( 'width', '0.00' ) );
		} else {
			$variation->set_tax_status( $vari['tax_status'] );
			$variation->set_tax_class( $vari['tax_class'] );
			$variation->set_weight( $vari['weight'] );
			$variation->set_height( $vari['height'] );
			$variation->set_length( $vari['length'] );
			$variation->set_width( $vari['width'] );
		}
		$variation->save();

		return $variation;
	}

	/**
	 * The getProductById is generated by appsbd
	 *
	 * @param any $id Its integer.
	 *
	 * @return POS_Product
	 */
	public function get_product_by_id( $id ) {
		$product     = wc_get_product( $id );
		$pos_product = POS_Product::get_product_data( $product, true );
		return $pos_product;
	}

	/**
	 * The product details is generated by appsbd
	 *
	 * @param any $data Its string.
	 *
	 * @return \Appsbd\V1\libs\API_Response
	 */
	public function product_details( $data ) {
		if ( ! empty( $data['id'] ) ) {
			$id          = intval( $data['id'] );
			$product_obj = $this->get_product_by_id( $id );
			$this->set_response( true, 'data found', $product_obj );

			return $this->response;
		}
		$this->set_response( false, 'Data not found or invalid param' );

		return $this->response;
	}

	/**
	 * The test is generated by appsbd
	 *
	 * @param any $parent Its string.
	 * @param any $variation Its string.
	 *
	 * @return array
	 */
	public function test( $parent, $variation ) {
		$product           = new WC_Product( $parent );
		$parent_attributes = $product->get_attributes();
		$attributes        = array();
		foreach ( $variation as $attribute ) {
			$attribute_id   = 0;
			$attribute_name = '';

						if ( ! empty( $attribute['id'] ) ) {
				$attribute_id   = absint( $attribute['id'] );
				$attribute_name = wc_attribute_taxonomy_name_by_id( $attribute_id );
			} elseif ( ! empty( $attribute['name'] ) ) {
				$attribute_name = sanitize_title( $attribute['name'] );
			}

			if ( ! $attribute_id && ! $attribute_name ) {
				continue;
			}

			if ( ! isset( $parent_attributes[ $attribute_name ] ) || ! $parent_attributes[ $attribute_name ]->get_variation() ) {
				continue;
			}

			$attribute_key   = sanitize_title( $attribute['name'] );
			$attribute_value = isset( $attribute['option'] ) ? wc_clean( stripslashes( $attribute['option'] ) ) : '';

			$attributes[ $attribute_key ] = $attribute_value;
		}

		return $attributes;
	}
}



