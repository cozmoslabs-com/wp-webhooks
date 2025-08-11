<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_payment_updated' ) ) :

    /**
     * Load the pmsp_payment_updated trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_payment_updated {

        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'pms_payment_update',
                    'callback' => array( $this, 'pms_payment_update_callback' ),
                    'priority' => 20,
                    'arguments' => 3,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $parameter = array(
                'payment_id' => array( 'short_description' => __( '(Integer) Payment ID.', 'wp-webhooks' ) ),
                'new_data' => array( 'short_description' => __( '(Array) Payment updated data.', 'wp-webhooks' ) ),
                'old_data' => array( 'short_description' => __( '(Array) Payment data before the update.', 'wp-webhooks' ) ),
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Payment Updated',
                'webhook_slug' => 'pmsp_payment_updated',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'pms_payment_update',
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
                'trigger'		   => 'pmsp_payment_updated',
                'name'			  => __( 'Payment Updated', 'wp-webhooks' ),
                'sentence'			  => __( 'a payment has been updated', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires as soon as a payment has been updated within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_payment_update_callback( $id, $new_data, $old_data ){

            if ( empty( $id ) || empty( $new_data ) || empty( $old_data ) )
                return;

            $payment_id = intval( $id );

            // remove unnecessary data
            unset( $old_data['logs'] );

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_payment_updated' );

            $payload = array(
                'payment_id' => $payment_id,
                'new_data' => $new_data,
                'old_data' => $old_data,
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

            do_action( 'wpwhpro/webhooks/trigger_pmsp_payment_updated', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = array (
                'payment_id' => 23,
                'new_data' =>
                array (
                    'date' => '2025-07-10 12:22:25',
                ),
                'old_data' =>
                array (
                    'id' => '3',
                    'user_id' => '21',
                    'subscription_id' => '505',
                    'member_subscription_id' => '4',
                    'status' => 'completed',
                    'date' => '2025-07-10 08:15:25',
                    'amount' => '100',
                    'currency' => 'USD',
                    'type' => 'subscription_initial_payment',
                    'payment_gateway' => 'stripe_connect',
                    'transaction_id' => 'pi_3RjFdaGJgxjnPzma1VbOUY5F',
                    'profile_id' => '',
                    'ip_address' => '172.18.0.1',
                    'discount_code' => ''
                ),
            );

            return $data;
        }

    }

endif; // End if class_exists check.