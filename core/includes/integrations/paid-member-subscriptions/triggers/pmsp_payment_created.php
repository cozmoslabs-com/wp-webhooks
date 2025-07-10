<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_payment_created' ) ) :

    /**
     * Load the pmsp_payment_created trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_payment_created {

        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'pms_payment_insert',
                    'callback' => array( $this, 'pms_payment_insert_callback' ),
                    'priority' => 20,
                    'arguments' => 2,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $parameter = array(
                'payment_id' => array( 'short_description' => __( '(Integer) Payment ID.', 'wp-webhooks' ) ),
                'payment_data' => array( 'short_description' => __( '(Array) Payment data.', 'wp-webhooks' ) ),
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Payment Created',
                'webhook_slug' => 'pmsp_payment_created',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'pms_payment_insert',
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
                'trigger'		   => 'pmsp_payment_created',
                'name'			  => __( 'Payment Created', 'wp-webhooks' ),
                'sentence'			  => __( 'a payment has been created', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires as soon as a payment has been created within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_payment_insert_callback( $id, $data ){

            if ( empty( $id ) || empty( $data ) )
                return;

            $payment_id = intval( $id );

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_payment_created' );

            $payload = array(
                'payment_id' => $payment_id,
                'payment_data' => $data,
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

            do_action( 'wpwhpro/webhooks/trigger_pmsp_payment_created', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = array (
                'payment_id' => 23,
                'payment_data' =>
                array (
                    'date' => '2025-07-10 12:22:25',
                    'amount' => 30,
                    'status' => 'pending',
                    'ip_address' => '172.18.0.1',
                    'user_id' => 21,
                    'subscription_plan_id' => 90,
                    'payment_gateway' => 'stripe_connect',
                    'currency' => 'USD',
                    'type' => 'subscription_initial_payment'
                ),
            );

            return $data;
        }

    }

endif; // End if class_exists check.