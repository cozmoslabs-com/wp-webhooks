<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_user_created_before' ) ) :

    /**
     * Load the pmsp_user_created_before trigger
     *
     */
    class WP_Webhooks_Integrations_paid_member_subscriptions_Triggers_pmsp_user_created_before {

        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_callbacks(){

            return array(
                array(
                    'type' => 'action',
                    'hook' => 'pms_register_form_before_create_user',
                    'callback' => array( $this, 'pms_register_form_before_create_user_callback' ),
                    'priority' => 20,
                    'arguments' => 1,
                    'delayed' => true,
                ),
            );
        }

        public function get_details(){

            $parameter = array(
                'user_data' => array( 'short_description' => __( '(Array) User data.', 'wp-webhooks' ) ),
            );

            $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
                'webhook_name' => 'User Created Before',
                'webhook_slug' => 'pmsp_user_created_before',
                'post_delay' => true,
                'trigger_hooks' => array(
                    array(
                        'hook' => 'pms_register_form_before_create_user',
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
                'trigger'		   => 'pmsp_user_created_before',
                'name'			  => __( 'User Created Before', 'wp-webhooks' ),
                'sentence'			  => __( 'a user has been created', 'wp-webhooks' ),
                'parameter'		 => $parameter,
                'settings'		  => $settings,
                'returns_code'	  => $this->get_demo( array() ),
                'short_description' => __( 'This webhook fires before a user ID is assigned and metadata is inserted into the database, when a user is being registered within Paid Member Subscriptions.', 'wp-webhooks' ),
                'description'	   => $description,
                'integration'	   => 'paid-member-subscriptions',
                'premium'		   => false,
            );

        }

        public function pms_register_form_before_create_user_callback( $data ){

            if ( empty( $data ) )
                return;

            $webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pmsp_user_created_before' );

            // remove entry because there is no user_id assigned yet
            if ( empty( $data['user_id'] ) )
                unset( $data['user_id'] );

            $payload = array(
                'user_data' => $data,
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

            do_action( 'wpwhpro/webhooks/trigger_pmsp_user_created_before', $payload, $response_data_array );
        }

        public function get_demo( $options = array() ) {

            $data = array (
                'user_data' =>
                    array (
                        'user_login' => 'john',
                        'user_email' => 'john@test.ts',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'user_pass' => 'password',
                        'role' => 'subscriber',
                        'subscription_data' =>
                            array (
                                '504'
                            ),
                    ),
            );

            return $data;
        }

    }

endif; // End if class_exists check.