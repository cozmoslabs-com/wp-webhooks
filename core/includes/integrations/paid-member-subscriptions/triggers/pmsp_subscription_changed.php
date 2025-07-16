<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_subscription_changed' ) ) :

    /**
     * Load the pmsp_subscription_changed trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_subscription_changed {

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
                    'hook' => 'pms_member_subscription_update',
                    'callback' => array( $this, 'pms_member_subscription_changed_callback' ),
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
                'new_subscription_data' => array( 'short_description' => __( '(Array) New member subscription data.', 'wp-webhooks' ) ),
                'old_subscription_data' => array( 'short_description' => __( '(Array) Old member subscription data.', 'wp-webhooks' ) ),
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Subscription Changed',
                'webhook_slug' => 'pmsp_subscription_changed',
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
                'data' => $this->helpers->get_subscription_settings()
            );

            return array(
                'trigger'		   => 'pmsp_subscription_changed',
                'name'			  => __( 'Subscription Changed', 'wp-webhooks' ),
                'sentence'			  => __( 'a member subscription has been changed', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires as soon as a member subscription has been changed within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_member_subscription_changed_callback( $id, $new_data, $old_data  ){

            if ( empty( $id ) || empty( $new_data ) || empty( $old_data ) )
                return;

            if ( !isset( $new_data['subscription_plan_id'] ) || $new_data['subscription_plan_id'] == $old_data['subscription_plan_id'] )
                return;

            $subscription_id = intval( $id );
            $subscription_plan_id = isset( $new_data['subscription_plan_id'] ) ? $new_data['subscription_plan_id'] : ( isset( $old_data['subscription_plan_id'] ) ? $old_data['subscription_plan_id'] : 0 );

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_subscription_changed' );

            $payload = array(
                'member_id' => $old_data['user_id'],
                'subscription_id' => $subscription_id,
                'new_subscription_data' => $new_data,
                'old_subscription_data' => $old_data,
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

            do_action( 'wpwhpro/webhooks/trigger_pmsp_subscription_changed', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = [
                'member_id' => 21,
                'subscription_id' => 10,
                'new_subscription_data' =>
                [
                    'user_id' => '21',
                    'subscription_plan_id' => '90',
                    'expiration_date' => '2025-08-14 22:51:16',
                    'status' => 'active',
                    'payment_gateway' => 'stripe_connect',
                    'billing_amount' => '30',
                    'billing_cycles' => '',
                    'trial_end' => '',
                    'payment_profile_id' => '',
                    'billing_duration' => '',
                    'billing_duration_unit' => '',
                    'billing_next_payment' => '',
                    'billing_last_payment' => '2025-07-15 22:51:18'
                ],
                'old_subscription_data' =>
                [
                    'id' => '10',
                    'user_id' => '21',
                    'subscription_plan_id' => '504',
                    'start_date' => '2025-07-10 12:22:25',
                    'expiration_date' => '2025-09-03 21:43:39',
                    'status' => 'active',
                    'payment_profile_id' => '',
                    'payment_gateway' => 'stripe_connect',
                    'billing_amount' => '50',
                    'billing_duration' => '0',
                    'billing_duration_unit' => '',
                    'billing_cycles' => '0',
                    'billing_next_payment' => '',
                    'billing_last_payment' => '2025-07-13 21:43:40',
                    'trial_end' => ''
                ],
            ];

            return $data;
        }

    }

endif; // End if class_exists check.