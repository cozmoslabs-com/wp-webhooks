<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_woocommerce_Triggers_wc_order_created' ) ) :

    /**
    * Load the wc_order_created trigger
    *
    * @since 4.3.2
    * @author Ironikus <info@ironikus.com>
    */
    class WP_Webhooks_Integrations_woocommerce_Triggers_wc_order_created {

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'woocommerce_new_order',
                    'callback' => array( $this, 'wc_order_created_callback' ),
                    'priority' => 20,
                    'arguments' => 1,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $translation_ident = "trigger-wc_order_created-description";
//            $validated_api_versions = array();
//
//            if( class_exists( 'WooCommerce' ) ){
//                $wc_helpers = WPWHPRO()->integrations->get_helper( 'woocommerce', 'wc_helpers' );
//
//                $validated_api_versions = $wc_helpers->get_wc_api_versions();
//            }

            $validated_statuses = array();
            if( function_exists( 'wc_get_order_statuses' ) ){
                $validated_statuses = wc_get_order_statuses();
            }

            $parameter = array(
                'custom' => array( 'short_description' => WPWHPRO()->helpers->translate( 'A custom data construct from your chosen Woocommerce API.', $translation_ident ) ),
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Order created',
                'webhook_slug' => 'wc_order_created',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'woocommerce_new_order',
                    ),
                ),
                'tipps' => array(
                    WPWHPRO()->helpers->translate( 'Please make sure to set the user id setting within the webhook URL. This setting allows our webhook to request the original payload from the REST API, just as Woocommerce does.', $translation_ident ),
                    WPWHPRO()->helpers->translate( 'You can fire this trigger as well on a specific Woocommerce API version. To do that, select a version within the webhook URL settings.', $translation_ident ),
                    WPWHPRO()->helpers->translate( 'You can also set a custom secret key just as for the default Woocommerce webhooks. IF you do not set one, there will be one automatically generated.', $translation_ident ),
                )
            ) );

            $settings = array(
                'load_default_settings' => true,
                'data' => array(
//                    'wpwhpro_woocommerce_set_user' => array(
//                        'id'		  => 'wpwhpro_woocommerce_set_user',
//                        'type'		=> 'text',
//                        'label'	   => WPWHPRO()->helpers->translate( 'Set user id', $translation_ident ),
//                        'placeholder' => '',
//                        'required'	=> false,
//                        'description' => WPWHPRO()->helpers->translate( 'Set the id of a user that has permission to view the Woocommerce REST API. If you do not set a valid user id, the response will not be verified.', $translation_ident )
//                    ),
//                    'wpwhpro_woocommerce_set_api_version' => array(
//                        'id'		  => 'wpwhpro_woocommerce_set_api_version',
//                        'type'		=> 'select',
//                        'multiple'	=> false,
//                        'choices'	  => $validated_api_versions,
//                        'label'	   => WPWHPRO()->helpers->translate( 'Set API version', $translation_ident ),
//                        'placeholder' => '',
//                        'required'	=> false,
//                        'default_value'	=> 'wp_api_v2',
//                        'description' => WPWHPRO()->helpers->translate( 'Select the Woocommerce API version you want to use for this request. By default, we use wp_api_v2', $translation_ident )
//                    ),
                    'wpwhpro_woocommerce_set_secret' => array(
                        'id'		  => 'wpwhpro_woocommerce_set_secret',
                        'type'		=> 'text',
                        'label'	   => WPWHPRO()->helpers->translate( 'Set secret', $translation_ident ),
                        'placeholder' => '',
                        'required'	=> false,
                        'description' => WPWHPRO()->helpers->translate( 'Set a custom secret that gets validated by Woocommerce, just as you know it from the default Woocommerce webhooks.', $translation_ident )
                    ),
                    'wpwhpro_woocommerce_trigger_on_statuses' => array(
                        'id'		  => 'wpwhpro_woocommerce_trigger_on_statuses',
                        'type'		=> 'select',
                        'multiple'	=> true,
                        'choices'	  => $validated_statuses,
                        'label'	   => WPWHPRO()->helpers->translate( 'Trigger on selected statuses', $translation_ident ),
                        'placeholder' => '',
                        'required'	=> false,
                        'description' => WPWHPRO()->helpers->translate( 'Select only the statuses you want to fire the trigger on. You can also choose multiple ones. If none is selected, all are triggered.', $translation_ident )
                    ),
                )
            );

            return array(
                'trigger'		   => 'wc_order_created',
                'name'			  => WPWHPRO()->helpers->translate( 'Order created', $translation_ident ),
                'sentence'			  => WPWHPRO()->helpers->translate( 'an order was created', $translation_ident ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => WPWHPRO()->helpers->translate( 'This webhook fires as soon as an order was created within Woocommerce.', $translation_ident ),
                'description'	   => $description,
                'integration'	   => 'woocommerce',
                'premium'		   => false,
            );

        }

        /**
         * Triggers once an order was created
         *
         * @param mixed $arg
         */
    //	public function wc_order_created_callback( $arg ){
    //
    //		$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'wc_order_created' );
    //		$payload = array();
    //		$payload_track = array();
    //
    //		$topic = 'order.created';
    //		$api_version = 'wp_api_v2';
    //
    //		if( ! class_exists( 'WC_Webhook' ) ){
    //			return;
    //		}
    //
    //		$wc_helpers = WPWHPRO()->integrations->get_helper( 'woocommerce', 'wc_helpers' );
    //		$post_id = ( is_numeric( $arg ) ) ? intval( $arg ) : 0;
    //
    //		$wc_webhook = new WC_Webhook();
    //		$wc_webhook->set_name( 'wpwh-' . $topic );
    //		$wc_webhook->set_status( 'active' );
    //		$wc_webhook->set_topic( $topic );
    //		$wc_webhook->set_user_id( 0 );
    //		$wc_webhook->set_pending_delivery( false );
    //		#$wc_webhook->set_delivery_url(  );
    //
    //		$response_data_array = array();
    //
    //		foreach( $webhooks as $webhook ){
    //
    //			$webhook_url_name = ( is_array($webhook) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;
    //			$is_valid = true;
    //
    //			if( isset( $webhook['settings'] ) ){
    //
    //				if( $is_valid && isset( $webhook['settings']['wpwhpro_woocommerce_set_api_version'] ) && ! empty( $webhook['settings']['wpwhpro_woocommerce_set_api_version'] ) ){
    //					$api_version = $webhook['settings']['wpwhpro_woocommerce_set_api_version'];
    //				}
    //
    //				if( $is_valid && isset( $webhook['settings']['wpwhpro_woocommerce_set_secret'] ) && ! empty( $webhook['settings']['wpwhpro_woocommerce_set_secret'] ) ){
    //					$wc_webhook->set_secret( $webhook['settings']['wpwhpro_woocommerce_set_secret'] );
    //				}
    //
    //				if( $is_valid
    //					&& isset( $webhook['settings']['wpwhpro_woocommerce_set_user'] )
    //					&& ! empty( $webhook['settings']['wpwhpro_woocommerce_set_user'] )
    //					&& is_numeric( $webhook['settings']['wpwhpro_woocommerce_set_user'] )
    //				){
    //					$wc_webhook->set_user_id( intval( $webhook['settings']['wpwhpro_woocommerce_set_user'] ) );
    //				}
    //
    //				//Make sure we automatically prevent the webhook from firing twice due to the Woocommerce hook notation
    //				$webhook['settings']['wpwhpro_trigger_single_instance_execution'] = 1;
    //			} else {
    //				$webhook['settings'] = array(
    //					'wpwhpro_trigger_single_instance_execution' => 1,
    //				);
    //			}
    //
    //			if( $is_valid ){
    //
    //				$wc_webhook->set_api_version( $api_version );
    //				$payload = $wc_webhook->build_payload( $arg );
    //
    //				//Revalidate the given Woocommerce status
    //				if( is_array( $payload ) && isset( $payload['status'] ) && isset( $webhook['settings'] ) ){
    //
    //					$status_ident = 'wc-';
    //					if( substr( $payload['status'], 0, strlen( $status_ident ) ) !== $status_ident ){
    //						$status = $status_ident . $payload['status'];
    //					} else {
    //						$status = $payload['status'];
    //					}
    //
    //					if( isset( $webhook['settings']['wpwhpro_woocommerce_trigger_on_statuses'] ) && ! empty( $webhook['settings']['wpwhpro_woocommerce_trigger_on_statuses'] ) ){
    //						if( ! in_array( $status, $webhook['settings']['wpwhpro_woocommerce_trigger_on_statuses'] ) ){
    //							continue;
    //						}
    //					}
    //				}
    //
    //				//Append additional data
    //				if( ! empty( $post_id ) && is_array( $payload ) ){
    //					$payload['wpwh_meta_data'] = get_post_meta( $post_id );
    //					$payload['wpwh_tax_data'] = $wc_helpers->get_validated_taxonomies( $post_id );
    //				}
    //
    //				//setup headers
    //				$headers	                                      = array();
    //				$headers['Content-Type']      		 = 'application/json';
    //				$headers['X-WC-Webhook-Source']      = home_url( '/' ); // Since 2.6.0.
    //				$headers['X-WC-Webhook-Topic']       = $wc_webhook->get_topic();
    //				$headers['X-WC-Webhook-Resource']    = $wc_webhook->get_resource();
    //				$headers['X-WC-Webhook-Event']       = $wc_webhook->get_event();
    //				$headers['X-WC-Webhook-Signature']   = $wc_webhook ->generate_signature( trim( wp_json_encode( $payload ) ) );
    //				$headers['X-WC-Webhook-ID']          = 0;
    //				$headers['X-WC-Webhook-Delivery-ID'] = 0;
    //
    //				if( $webhook_url_name !== null ){
    //					$response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload, array( 'headers' => $headers ) );
    //					$payload_track[] = $payload;
    //				} else {
    //					$response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload, array( 'headers' => $headers ) );
    //				}
    //			}
    //
    //		}
    //
    //		do_action( 'wpwhpro/webhooks/trigger_wc_order_created', $payload, $response_data_array, $payload_track );
    //	}


        /**
         * Triggers once an order was created
         *
         * @param $arg - order ID
         * @return void
         */
        public function wc_order_created_callback( $arg ) {
            $order_id = is_numeric( $arg ) ? intval( $arg ) : 0;
            $order = wc_get_order( $order_id );

            // Return if we don't have an Order
            if ( !$order )
                return;

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'wc_order_created' );
            $response_data_array = array();
            $payload = array();
            $payload_track = array();

            foreach ( $webhooks as $webhook ) {
                $webhook_url_name = isset( $webhook['webhook_url_name'] ) ? $webhook['webhook_url_name'] : null;

                // Make sure we automatically prevent the webhook from firing twice due to the Woocommerce hook notation
                $webhook['settings']['wpwhpro_trigger_single_instance_execution'] = 1;

                // Revalidate the given Woocommerce status
                $trigger_statuses = isset( $webhook['settings']['wpwhpro_woocommerce_trigger_on_statuses'] ) ? $webhook['settings']['wpwhpro_woocommerce_trigger_on_statuses'] : array();
                $wc_order_status = $order->get_status();
                $order_status = strpos( $wc_order_status, 'wc-' ) === 0 ? $wc_order_status : 'wc-' . $wc_order_status;

                if ( !empty( $trigger_statuses ) && !in_array( $order_status, $trigger_statuses, true ) )
                    continue;

                // Prepare Order data for response
                $payload = $this->build_payload( $order_id, $order );

                // Create signature
                $secret = isset( $webhook['settings']['wpwhpro_woocommerce_set_secret'] ) ? $webhook['settings']['wpwhpro_woocommerce_set_secret'] : '';
                $signature = base64_encode( hash_hmac( 'sha256', wp_json_encode( $payload ), $secret, true ) );

                // Setup headers
                $headers = array(
                    'Content-Type'               => 'application/json',
                    'X-WC-Webhook-Source'        => home_url( '/' ),
                    'X-WC-Webhook-Topic'         => 'order.created',
                    'X-WC-Webhook-Resource'      => 'order',
                    'X-WC-Webhook-Event'         => 'created',
                    'X-WC-Webhook-Signature'     => $signature,
                    'X-WC-Webhook-ID'            => 0,
                    'X-WC-Webhook-Delivery-ID'   => 0,
                );

                // Post data to webhook
                if ( $webhook_url_name !== null ) {

                    $response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload, array( 'headers' => $headers ) );
                    $payload_track[] = $payload;

                } else {

                    $response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload, array( 'headers' => $headers ) );

                }
            }

            do_action( 'wpwhpro/webhooks/trigger_wc_order_created', $payload, $response_data_array, $payload_track );
        }


        /**
         * Prepare Order data for response
         *
         * @param $order_id
         * @param $order
         * @return array|WP_Error
         */
        private function build_payload( $order_id, $order ) {

            if ( ! $order instanceof WC_Order ) {
                return new WP_Error( 'invalid_order', 'Invalid order object' );
            }

            $data = array(
                'id'                   => $order->get_id(),
                'parent_id'            => $order->get_parent_id(),
                'status'               => $order->get_status(),
                'order_key'            => $order->get_order_key(),
                'number'               => $order->get_order_number(),
                'currency'             => $order->get_currency(),
                'version'              => WC()->version,
                'prices_include_tax'   => wc_prices_include_tax(),
                'date_created'         => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : null,
                'date_modified'        => $order->get_date_modified() ? $order->get_date_modified()->date( 'c' ) : null,
                'customer_id'          => $order->get_customer_id(),
                'discount_total'       => $order->get_discount_total(),
                'discount_tax'         => $order->get_discount_tax(),
                'shipping_total'       => $order->get_shipping_total(),
                'shipping_tax'         => $order->get_shipping_tax(),
                'cart_tax'             => $order->get_cart_tax(),
                'total'                => $order->get_total(),
                'total_tax'            => $order->get_total_tax(),
                'billing'              => $order->get_address( 'billing' ),
                'shipping'             => $order->get_address( 'shipping' ),
                'payment_method'       => $order->get_payment_method(),
                'payment_method_title' => $order->get_payment_method_title(),
                'transaction_id'       => $order->get_transaction_id(),
                'customer_ip_address'  => $order->get_customer_ip_address(),
                'customer_user_agent'  => $order->get_customer_user_agent(),
                'created_via'          => $order->get_created_via(),
                'customer_note'        => $order->get_customer_note(),
                'date_completed'       => $order->get_date_completed() ? $order->get_date_completed()->date( 'c' ) : null,
                'date_paid'            => $order->get_date_paid() ? $order->get_date_paid()->date( 'c' ) : null,
                'cart_hash'            => $order->get_cart_hash(),
                'line_items'           => array(),
                'tax_lines'            => array(),
                'shipping_lines'       => array(),
                'fee_lines'            => array(),
                'coupon_lines'         => array(),
                'refunds'              => array(),
                '_links'               => array(
                    'self' => array(
                        array( 'href' => rest_url( '/wc/v3/orders/' . $order_id ) ),
                    ),
                    'collection' => array(
                        array( 'href' => rest_url( '/wc/v3/orders' ) ),
                    ),
                    'customer' => array(
                        array( 'href' => rest_url( '/wc/v3/customers/' . $order->get_customer_id() ) ),
                    ),
                ),
            );

            // Add line items
            // - at this point WooCommerce functions wc_format_decimal() and wc_get_price_decimals() used bellow should be accessible
            foreach ( $order->get_items() as $item_id => $item ) {
                $data['line_items'][] = array(
                    'id'           => $item_id,
                    'name'         => $item->get_name(),
                    'sku'          => $item->get_product() ? $item->get_product()->get_sku() : '',
                    'product_id'   => $item->get_product_id(),
                    'variation_id' => $item->get_variation_id(),
                    'quantity'     => $item->get_quantity(),
                    'tax_class'    => $item->get_tax_class(),
                    'price'        => wc_format_decimal( $item->get_total() / $item->get_quantity(), wc_get_price_decimals() ),
                    'subtotal'     => wc_format_decimal( $item->get_subtotal(), wc_get_price_decimals() ),
                    'subtotal_tax' => wc_format_decimal( $item->get_subtotal_tax(),  wc_get_price_decimals() ),
                    'total'        => wc_format_decimal( $item->get_total(),  wc_get_price_decimals() ),
                    'total_tax'    => wc_format_decimal( $item->get_total_tax(), wc_get_price_decimals() ),
                    'taxes'        => $item->get_taxes(),
                    'meta'         => $item->get_meta_data(),
                );
            }

            // Shipping
            foreach ( $order->get_items( 'shipping' ) as $shipping_id => $shipping ) {
                $data['shipping_lines'][] = $shipping->get_data();
            }

            // Fee
            foreach ( $order->get_items( 'fee' ) as $fee_id => $fee ) {
                $data['fee_lines'][] = $fee->get_data();
            }

            // Coupons
            foreach ( $order->get_items( 'coupon' ) as $coupon_id => $coupon ) {
                $data['coupon_lines'][] = $coupon->get_data();
            }

            // Taxes
            foreach ( $order->get_items( 'tax' ) as $tax_id => $tax ) {
                $data['tax_lines'][] = $tax->get_data();
            }

            // Refunds
            foreach ( $order->get_refunds() as $refund ) {
                $data['refunds'][] = $refund->get_data();
            }

            // Load the Woocommerce helpers
            $wc_helpers = WPWHPRO()->integrations->get_helper( 'woocommerce', 'wc_helpers' );

            // Append additional data
            $data['wpwh_meta_data'] = get_post_meta( $order_id );
            $data['wpwh_tax_data']  = $wc_helpers->get_validated_taxonomies( $order_id );

            return $data;
        }


        /**
         * Demo response
         *
         * @param $options
         * @return int[]
         */
        public function get_demo( $options = array() ) {

            $data = array (
                'id' => 8095,
                'parent_id' => 0,
                'status' => 'processing',
                'order_key' => 'wc_order_G6tAiKndLB8up',
                'number' => '8095',
                'currency' => 'EUR',
                'version' => '6.0.0',
                'prices_include_tax' => false,
                'date_created' => '2021-12-28T05:26:04',
                'date_modified' => '2021-12-28T05:26:04',
                'customer_id' => 153,
                'discount_total' => '0.00',
                'discount_tax' => '0.00',
                'shipping_total' => '0.00',
                'shipping_tax' => '0.00',
                'cart_tax' => '0.00',
                'total' => '0.00',
                'total_tax' => '0.00',
                'billing' =>
                array (
                  'first_name' => 'Demo',
                  'last_name' => 'User',
                  'company' => 'Demo Corp',
                  'address_1' => 'Demo St. 55',
                  'address_2' => '',
                  'city' => 'Demo City',
                  'state' => '',
                  'postcode' => '12345',
                  'country' => 'DE',
                  'email' => 'demouser@yourdomain.test',
                  'phone' => '123456789',
                ),
                'shipping' =>
                array (
                  'first_name' => '',
                  'last_name' => '',
                  'company' => '',
                  'address_1' => '',
                  'address_2' => '',
                  'city' => '',
                  'state' => '',
                  'postcode' => '',
                  'country' => '',
                  'phone' => '',
                ),
                'payment_method' => '',
                'payment_method_title' => '',
                'transaction_id' => '',
                'customer_ip_address' => '127.0.0.1',
                'customer_user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.93 Safari/537.36',
                'created_via' => 'checkout',
                'customer_note' => 'This ism a demo order',
                'date_completed' => NULL,
                'date_paid' => '2021-12-28T05:26:04',
                'cart_hash' => '06595c37c969ebe1f8a6304602c2f2e4',
                'line_items' =>
                array (
                  0 =>
                  array (
                    'id' => 43,
                    'name' => 'Bookable Product',
                    'sku' => '',
                    'product_id' => 604,
                    'variation_id' => 0,
                    'quantity' => 1,
                    'tax_class' => '',
                    'price' => '0.00',
                    'subtotal' => '0.00',
                    'subtotal_tax' => '0.00',
                    'total' => '0.00',
                    'total_tax' => '0.00',
                    'taxes' =>
                    array (
                    ),
                    'meta' =>
                    array (
                    ),
                  ),
                ),
                'tax_lines' =>
                array (
                ),
                'shipping_lines' =>
                array (
                ),
                'fee_lines' =>
                array (
                ),
                'coupon_lines' =>
                array (
                ),
                'refunds' =>
                array (
                ),
                '_links' =>
                array (
                  'self' =>
                  array (
                    0 =>
                    array (
                      'href' => 'https://yourdomain.test/wp-json/wc/v1/orders/8095',
                    ),
                  ),
                  'collection' =>
                  array (
                    0 =>
                    array (
                      'href' => 'https://yourdomain.test/wp-json/wc/v1/orders',
                    ),
                  ),
                  'customer' =>
                  array (
                    0 =>
                    array (
                      'href' => 'https://yourdomain.test/wp-json/wc/v1/customers/153',
                    ),
                  ),
                ),
                'wpwh_meta_data' =>
                array (
                    '_order_key' =>
                    array (
                    0 => 'wc_order_G6tAiKndLB8up',
                    ),
                    '_customer_user' =>
                    array (
                    0 => '153',
                    ),
                    '_customer_ip_address' =>
                    array (
                    0 => '127.0.0.1',
                    ),
                    '_customer_user_agent' =>
                    array (
                    0 => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.93 Safari/537.36',
                    ),
                    '_created_via' =>
                    array (
                    0 => 'checkout',
                    ),
                    '_cart_hash' =>
                    array (
                    0 => '06595c37c969ebe1f8a6304602c2f2e4',
                    ),
                    '_billing_first_name' =>
                    array (
                    0 => 'Demo',
                    ),
                    '_billing_last_name' =>
                    array (
                    0 => 'User',
                    ),
                    '_billing_company' =>
                    array (
                    0 => 'Demo Corp',
                    ),
                    '_billing_address_1' =>
                    array (
                    0 => 'Demo St. 55',
                    ),
                    '_billing_city' =>
                    array (
                    0 => 'Demo City',
                    ),
                    '_billing_postcode' =>
                    array (
                    0 => '12345',
                    ),
                    '_billing_country' =>
                    array (
                    0 => 'DE',
                    ),
                    '_billing_email' =>
                    array (
                    0 => 'demouser@yourdomain.test',
                    ),
                    '_billing_phone' =>
                    array (
                    0 => '123456789',
                    ),
                    '_order_currency' =>
                    array (
                    0 => 'EUR',
                    ),
                    '_cart_discount' =>
                    array (
                    0 => '0',
                    ),
                    '_cart_discount_tax' =>
                    array (
                    0 => '0',
                    ),
                    '_order_shipping' =>
                    array (
                    0 => '0',
                    ),
                    '_order_shipping_tax' =>
                    array (
                    0 => '0',
                    ),
                    '_order_tax' =>
                    array (
                    0 => '0',
                    ),
                    '_order_total' =>
                    array (
                    0 => '0.00',
                    ),
                    '_order_version' =>
                    array (
                    0 => '6.0.0',
                    ),
                    '_prices_include_tax' =>
                    array (
                    0 => 'no',
                    ),
                    '_billing_address_index' =>
                    array (
                    0 => 'Demo User Demo Corp Demo St. 55  Demo City  12345 DE demouser@yourdomain.test 123456789',
                    ),
                    '_shipping_address_index' =>
                    array (
                    0 => '         ',
                    ),
                    'is_vat_exempt' =>
                    array (
                    0 => 'no',
                    ),
                    '_date_paid' =>
                    array (
                    0 => '1640669164',
                    ),
                    '_paid_date' =>
                    array (
                    0 => '2021-12-28 05:26:04',
                    ),
                    '_download_permissions_granted' =>
                    array (
                    0 => 'yes',
                    ),
                    '_recorded_sales' =>
                    array (
                    0 => 'yes',
                    ),
                    '_recorded_coupon_usage_counts' =>
                    array (
                    0 => 'yes',
                    ),
                    '_order_stock_reduced' =>
                    array (
                    0 => 'yes',
                    ),
                    '_new_order_email_sent' =>
                    array (
                    0 => 'true',
                    ),
                    'wpwhpro_create_post_temp_status_jobs' =>
                    array (
                    0 => 'wc-processing',
                    ),
                    '_edit_lock' =>
                    array (
                    0 => '1641462604:1',
                    ),
                    '_edit_last' =>
                    array (
                    0 => '1',
                    ),
                ),
                'wpwh_tax_data' =>
                array (
                ),
            );

            return $data;
        }

    }

endif; // End if class_exists check.