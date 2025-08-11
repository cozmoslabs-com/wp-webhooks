<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_subscription_deleted_after' ) ) :

    /**
     * Load the pmsp_subscription_deleted_after trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_subscription_deleted_after {

        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;
        public $helpers;

        public function __construct() {

            // Load the PMS helpers
            $this->helpers = WPWHPRO()->integrations->get_helper( 'paid-member-subscriptions', 'pms_helpers' );

        }

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'pms_member_subscription_delete',
                    'callback' => array( $this, 'pms_member_subscription_delete_callback' ),
                    'priority' => 20,
                    'arguments' => 2,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $parameter = array(
                'member_id' => array( 'short_description' => __( '(Integer) ID of the user who owns the subscription.', 'wp-webhooks' ) ),
                'subscription_id' => array( 'short_description' => __( '(Integer) Member subscription ID.', 'wp-webhooks' ) ),
                'subscription_data' => array( 'short_description' => __( '(Array) Member subscription data.', 'wp-webhooks' ) ),
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Subscription Deleted After',
                'webhook_slug' => 'pmsp_subscription_deleted_after',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'pms_member_subscription_delete',
                    ),
                ),
                'tipps' => array(
                )
            ) );

            $settings = array(
                'load_default_settings' => true,
                'data' => $this->helpers->get_subscription_settings()
            );

            return array(
                'trigger'		   => 'pmsp_subscription_deleted_after',
                'name'			  => __( 'Subscription Deleted After', 'wp-webhooks' ),
                'sentence'			  => __( 'a member subscription has been deleted', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires after metadata is removed, when a member subscription is being deleted within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_member_subscription_delete_callback( $id, $data ){

            if ( empty( $id ) || empty( $data ) )
                return;

            $subscription_id = intval( $id );
            $subscription_plan_id = isset( $data['subscription_plan_id'] ) ? $data['subscription_plan_id'] : 0;

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_subscription_deleted_after' );

            $payload = array(
                'member_id' => $data['user_id'],
                'subscription_id' => $subscription_id,
                'subscription_data' => $data,
            );

            $response_data_array = array();

            foreach( $webhooks as $webhook ){

                $selected_feature = isset( $webhook['settings']['wpwhpro_pms_subscription_feature'] ) ? $webhook['settings']['wpwhpro_pms_subscription_feature'] : 0;

                if ( !empty( $selected_feature ) && !$this->helpers->validate_subscription_feature( $selected_feature, $subscription_plan_id ) )
                    continue;

                $webhook_url_name = ( is_array( $webhook ) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;

                if( $webhook_url_name !== null ){
                    $response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload );
                } else {
                    $response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload );
                }

            }

            do_action( 'wpwhpro/webhooks/trigger_pmsp_subscription_deleted_after', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = array (
                'member_id' => 21,
                'subscription_id' => 2,
                'subscription_data' =>
                array (
                    'id' => '2',
                    'user_id' => '21',
                    'subscription_plan_id' => '90',
                    'start_date' => '2025-07-08 12:27:22',
                    'expiration_date' => '2025-08-07 23:59:59',
                    'status' => 'active',
                    'payment_profile_id' => '',
                    'payment_gateway' => 'stripe_connect',
                    'billing_amount' => '30',
                    'billing_duration' => '0',
                    'billing_duration_unit' => '',
                    'billing_cycles' => '0',
                    'billing_next_payment' => '',
                    'billing_last_payment' => '2025-07-08 12:27:24',
                    'trial_end' => ''
                ),
            );

            return $data;
        }

    }

endif; // End if class_exists check.