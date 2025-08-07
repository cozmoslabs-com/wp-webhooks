<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_group_member_activated' ) && defined( 'PMS_IN_GM_PLUGIN_DIR_PATH' ) ) :

    /**
     * Load the pmsp_group_member_activated trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_group_member_activated {

        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'pms_gm_invited_member_activated',
                    'callback' => array( $this, 'pms_gm_invited_member_activated_callback' ),
                    'priority' => 20,
                    'arguments' => 3,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $parameter = array(
                'member_id' => array( 'short_description' => __( '(Integer) Member Subscription ID.', 'wp-webhooks' ) ),
                'member_subscription_data' => array( 'short_description' => __( '(Object) Group member subscription data.', 'wp-webhooks' ) ),
                'owner_subscription_data' => array( 'short_description' => __( '(Object) Group owner subscription data.', 'wp-webhooks' ) )
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Group Member Activated',
                'webhook_slug' => 'pmsp_group_member_activated',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'pms_gm_invited_member_activated',
                    ),
                ),
                'tipps' => array(
                    sprintf( __( 'This trigger relates to the %1$sGroup Memberships%2$s add-on from Paid Member Subscriptions.', 'wp-webhooks' ), '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/group-memberships/?utm_source=wpbackend&utm_medium=clientsite&utm_content=wp-webhooks&utm_campaign=PMS" target="_blank">', '</a>' ),
                    __( 'It will trigger once a user invited to a group subscription accepts the invitation and registers on the website.', 'wp-webhooks' ),
                )
            ) );

            $settings = array(
                'load_default_settings' => true,
                'data' => array()
            );

            return array(
                'trigger'		   => 'pmsp_group_member_activated',
                'name'			  => __( 'Group Member Activated', 'wp-webhooks' ),
                'sentence'			  => __( 'a group member has been activated', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires as soon as a group member has been activated within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_gm_invited_member_activated_callback( $id, $user_subscription, $owner_subscription ){

            if ( empty( $id ) || empty( $user_subscription ) || empty( $owner_subscription ) )
                return;

            $user_id = intval( $id );
            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_group_member_activated' );
            $user_subscription_data = is_object( $user_subscription ) ? get_object_vars( $user_subscription ) : $user_subscription;

            // remove unnecessary data
            foreach ( $user_subscription_data as $key => $value ) {
                if ( strpos( $key, 'payment_' ) === 0 || strpos( $key, 'billing_' ) === 0 || $key == 'trial_end' )
                    unset( $user_subscription_data[$key] );
            }

            $payload = array(
                'member_id' => $user_id,
                'member_subscription_data' => $user_subscription_data,
                'owner_subscription_data' => is_object( $owner_subscription ) ? get_object_vars( $owner_subscription ) : $owner_subscription,
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

            do_action( 'wpwhpro/webhooks/trigger_pmsp_group_member_activated', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = array (
                'member_id' => 22,
                'member_subscription_data' =>
                array (
                    'id' => 20,
                    'user_id' => '22',
                    'subscription_plan_id' => '506',
                    'start_date' => '2025-07-14 13:13:13',
                    'expiration_date' => '2025-08-13 13:13:13',
                    'status' => 'active'
                ),
                'owner_subscription_data' =>
                array (
                    'id' => '11',
                    'user_id' => '21',
                    'subscription_plan_id' => '506',
                    'start_date' => '2025-07-14 13:13:13',
                    'expiration_date' => '2025-08-13 13:13:13',
                    'status' => 'active',
                    'payment_profile_id' => '',
                    'payment_gateway' => 'stripe_connect',
                    'billing_amount' => '30',
                    'billing_duration' => '0',
                    'billing_duration_unit' => '',
                    'billing_cycles' => '0',
                    'billing_next_payment' => '',
                    'billing_last_payment' => '2025-07-14 13:13:15',
                    'trial_end' => ''
                ),
            );

            return $data;
        }

    }

endif; // End if class_exists check.