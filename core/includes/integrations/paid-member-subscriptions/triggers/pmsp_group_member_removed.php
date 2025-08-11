<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_group_member_removed' ) && defined( 'PMS_IN_GM_PLUGIN_DIR_PATH' ) ) :

    /**
     * Load the pmsp_group_member_removed trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_group_member_removed {

        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'pms_gm_member_removed',
                    'callback' => array( $this, 'pms_gm_member_removed_callback' ),
                    'priority' => 20,
                    'arguments' => 2,
                    'delayed' => true,
                ),
                array(
                    'type' => 'action',
                    'hook' => 'pms_member_subscription_delete',
                    'callback' => array( $this, 'pms_gm_member_removed_callback' ),
                    'priority' => 20,
                    'arguments' => 2,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $parameter = array(
                'member_subscription_id' => array( 'short_description' => __( '(Integer) User ID.', 'wp-webhooks' ) ),
                'owner_subscription_data' => array( 'short_description' => __( '(Array) Group owner subscription data.', 'wp-webhooks' ) )
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Group Member Removed',
                'webhook_slug' => 'pmsp_group_member_removed',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'pms_gm_member_removed',
                    ),
                ),
                'tipps' => array(
                    sprintf( __( 'This trigger relates to the %1$sGroup Memberships%2$s add-on from Paid Member Subscriptions.', 'wp-webhooks' ), '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/group-memberships/?utm_source=wpbackend&utm_medium=clientsite&utm_content=wp-webhooks&utm_campaign=PMS" target="_blank">', '</a>' ),
                    __( 'It will trigger once a a registered user is removed from a group subscription by the group owner or admin.', 'wp-webhooks' ),
                )
            ) );

            $settings = array(
                'load_default_settings' => true,
                'data' => array()
            );

            return array(
                'trigger'		   => 'pmsp_group_member_removed',
                'name'			  => __( 'Group Member Removed', 'wp-webhooks' ),
                'sentence'			  => __( 'a group member has been removed', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires as soon as a group member has been removed within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_gm_member_removed_callback( $id, $owner_subscription ){

            if ( empty( $id ) || empty( $owner_subscription ) )
                return;

            $user_id = intval( $id );

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_group_member_removed' );

            $payload = array(
                'member_subscription_id' => $user_id,
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

            do_action( 'wpwhpro/webhooks/trigger_pmsp_group_member_removed', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = array (
                'member_subscription_id' => 22,
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