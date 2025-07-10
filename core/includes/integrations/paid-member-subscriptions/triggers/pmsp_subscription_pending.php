<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_subscription_pending' ) ) :

    /**
     * Load the pmsp_subscription_pending trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_subscription_pending {

        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'pms_member_subscription_update',
                    'callback' => array( $this, 'pms_member_subscription_pending_callback' ),
                    'priority' => 20,
                    'arguments' => 3,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $parameter = array(
                'member_id' => array( 'short_description' => __( '(Integer) ID of the user who owns the subscription.', 'wp-webhooks' ) ),
                'subscription_id' => array( 'short_description' => __( '(Integer) Member subscription ID.', 'wp-webhooks' ) ),
                'subscription_status' => array( 'short_description' => __( '(String) Member subscription status.', 'wp-webhooks' ) ),
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Subscription Pending',
                'webhook_slug' => 'pmsp_subscription_pending',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'pms_member_subscription_update',
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
                'trigger'		   => 'pmsp_subscription_pending',
                'name'			  => __( 'Subscription Pending', 'wp-webhooks' ),
                'sentence'			  => __( 'a member subscription is pending', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires as soon as a member subscription is pending within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_member_subscription_pending_callback( $id, $new_data, $old_data ){

            if ( empty( $id ) || empty( $new_data ) || empty( $old_data ) )
                return;

            if ( $new_data['status'] == $old_data['status'] || $new_data['status'] != 'pending' )
                return;

            $subscription_id = intval( $id );

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_subscription_pending' );

            $payload = array(
                'member_id' => $old_data['user_id'],
                'subscription_id' => $subscription_id,
                'subscription_status' => 'pending',
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

            do_action( 'wpwhpro/webhooks/trigger_pmsp_subscription_pending', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = array (
                'member_id' => 21,
                'subscription_id' => 1,
                'subscription_status' => 'pending',
            );

            return $data;
        }

    }

endif; // End if class_exists check.