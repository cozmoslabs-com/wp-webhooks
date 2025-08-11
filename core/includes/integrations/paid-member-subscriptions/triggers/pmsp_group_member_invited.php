<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_group_member_invited' ) && defined( 'PMS_IN_GM_PLUGIN_DIR_PATH' ) ) :

    /**
     * Load the pmsp_group_member_invited trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_group_member_invited {

        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'pms_gm_send_invitation_email',
                    'callback' => array( $this, 'pms_gm_send_invitation_email_callback' ),
                    'priority' => 20,
                    'arguments' => 3,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $parameter = array(
                'member_email' => array( 'short_description' => __( '(String) User email address.', 'wp-webhooks' ) ),
                'owner_subscription_data' => array( 'short_description' => __( '(Array) Group owner subscription data.', 'wp-webhooks' ) ),
                'invite_key' => array( 'short_description' => __( '(String) Activation key.', 'wp-webhooks' ) )
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'Group Member Invited',
                'webhook_slug' => 'pmsp_group_member_invited',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'pms_gm_send_invitation_email',
                    ),
                ),
                'tipps' => array(
                    sprintf( __( 'This trigger relates to the %1$sGroup Memberships%2$s add-on from Paid Member Subscriptions.', 'wp-webhooks' ), '<a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/add-ons/group-memberships/?utm_source=wpbackend&utm_medium=clientsite&utm_content=wp-webhooks&utm_campaign=PMS" target="_blank">', '</a>' ),
                    __( 'It will trigger once a user is invited to join a group subscription, before they accept the invitation or register on the website.', 'wp-webhooks' ),
                )
            ) );

            $settings = array(
                'load_default_settings' => true,
                'data' => array()
            );

            return array(
                'trigger'		   => 'pmsp_group_member_invited',
                'name'			  => __( 'Group Member Invited', 'wp-webhooks' ),
                'sentence'			  => __( 'a group member has been invited', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires as soon as a group member has been invited within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_gm_send_invitation_email_callback( $user_email, $owner_subscription, $invite_key ){

            if ( empty( $user_email ) || empty( $owner_subscription ) || empty( $invite_key ) )
                return;

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_group_member_invited' );

            $payload = array(
                'member_email' => $user_email,
                'owner_subscription_data' => is_object( $owner_subscription ) ? get_object_vars( $owner_subscription ) : $owner_subscription,
                'invite_key' => $invite_key,
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

            do_action( 'wpwhpro/webhooks/trigger_pmsp_group_member_invited', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = array (
                'member_email' => 'john_doe@gmail.com',
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
                'invite_key' => 'dad1b6f1a6cffd7f5945e5b90d1fc8684acdc11dfe1b891f43213bd1be129454'
            );

            return $data;
        }

    }

endif; // End if class_exists check.