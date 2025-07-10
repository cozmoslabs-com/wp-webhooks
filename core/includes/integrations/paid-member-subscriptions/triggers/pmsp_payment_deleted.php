<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_payment_deleted' ) ) :

    /**
     * Load the pmsp_payment_deleted trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_payment_deleted {

        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'pms_payment_delete',
                    'callback' => array( $this, 'pms_payment_delete_callback' ),
                    'priority' => 20,
                    'arguments' => 1,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $parameter = array(
                'payment_id' => array( 'short_description' => __( '(Integer) Payment ID.', 'wp-webhooks' ) ),
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Payment Deleted',
                'webhook_slug' => 'pmsp_payment_deleted',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'pms_payment_delete',
                    ),
                ),
                'tipps' => array(
                )
            ) );

            $settings = array(
                'load_default_settings' => true,
                'data' => array()
            );

            return array(
                'trigger'		   => 'pmsp_payment_deleted',
                'name'			  => __( 'Payment Deleted', 'wp-webhooks' ),
                'sentence'			  => __( 'a payment has been deleted', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires as soon as a payment has been deleted within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_payment_delete_callback( $id ){

            if ( empty( $id ) )
                return;

            $payment_id = intval( $id );

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_payment_deleted' );

            $payload = array(
                'payment_id' => $payment_id,
            );

            $response_data_array = array();

            foreach( $webhooks as $webhook ){

                $webhook_url_name = ( is_array( $webhook ) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;

                if( $webhook_url_name !== null ){
                    $response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload );
                } else {
                    $response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload );
                }

            }

            do_action( 'wpwhpro/webhooks/trigger_pmsp_payment_deleted', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = array (
                'payment_id' => 23,
            );

            return $data;
        }

    }

endif; // End if class_exists check.