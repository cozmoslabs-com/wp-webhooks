<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_profile_builder_Actions_pbp_confirm_user_email' ) ) :

    /**
     * Load the pbp_confirm_user_email action
     */
    class WP_Webhooks_Integrations_profile_builder_Actions_pbp_confirm_user_email {
        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_details(){

            $parameter = array(
                'user_email' => array(
                    'required' => true,
                    'label' => __( 'User Email', 'wp-webhooks' ),
                    'short_description' => __( 'User email address.', 'wp-webhooks' ),
                ),
            );

            $returns = array(
                'success'		=> array( 'short_description' => __( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'wp-webhooks' ) ),
                'msg'		=> array( 'short_description' => __( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'wp-webhooks' ) ),
                'data'		=> array( 'short_description' => __( '(Array) Further data about the request.', 'wp-webhooks' ) ),
            );

            $returns_code = array (
                'success' => true,
                'msg' => 'ok',
                'data' => array (
                    'signup_id' => 75,
                    'domain' => '',
                    'path' => '',
                    'title' => '',
                    'user_login' => 'test',
                    'user_email' => 'test@yahoo.com',
                    'registered' => '2025-12-16 15:16:45',
                    'activated' => '0000-00-00 00:00:00',
                    'active' => '0',
                    'activation_key' => 'ca02de552ce5918f',
                    'meta' => 'a:11:{s:10:"user_login";s:2:"da";s:10:"first_name";s:0:"";s:9:"last_name";s:0:"";s:9:"user_pass";s:63:"$wp$2y$10$9oa9Nn0iTnBi7oIPl5fT.uO.PW8jyFDbIHoDVWn3u/GvOVHHCGJyO";s:8:"nickname";s:0:"";s:11:"description";s:0:"";s:8:"user_url";s:0:"";s:10:"user_email";s:10:"da@yah.com";s:4:"role";s:10:"subscriber";s:9:"form_name";s:11:"unspecified";s:28:"wppb_login_after_register_da";b:0;}',
                ),
            );

            return array(
                'action'			=> 'pbp_confirm_user_email', //required
                'name'			   	=> __( 'Confirm user email', 'wp-webhooks' ),
                'sentence'			=> __( 'confirm user email', 'wp-webhooks' ),
                'parameter'		 	=> $parameter,
                'returns'		   	=> $returns,
                'returns_code'	  	=> $returns_code,
                'short_description' => __( 'Confirm user email with Profile Builder', 'wp-webhooks' ),
                'description'	   	=> '',
                'integration'	   	=> 'profile-builder',
                'premium'	   		=> false
            );

        }

        public function execute( $return_data, $response_body ){

            $return_args = array(
                'success' => false,
                'msg' => 'There was an error while trying to activate the user',
                'data' => array(),
            );

            $wppb_general_settings = get_option( 'wppb_general_settings' );

            if( !isset( $wppb_general_settings['emailConfirmation'] ) || $wppb_general_settings['emailConfirmation'] != 'yes' ){
                $return_args = array(
                    'success' => false,
                    'msg' => 'Email Confirmation option is not enabled in Profile Builder settings!',
                    'data' => array(),
                );

                return $return_args;
            }

            $user_email = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'user_email' );

            if ( !empty( $user_email ) ) {

                global $wpdb;

                $user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->base_prefix . "signups WHERE user_email = %s", $user_email), ARRAY_A);

                if( isset( $user_data['activation_key'] ) && !empty( $user_data['activation_key'] ) && function_exists( 'wppb_manual_activate_signup' ) ){

                    $activation_message = wppb_manual_activate_signup( $user_data['activation_key'] );

                    $success = $activation_message === 'ok' ? true : false;

                    $return_args = array (
                        'success' => $success,
                        'msg' => $activation_message,
                        'data' => $user_data
                    );

                }

            }

            return $return_args;

        }

    }

endif; // End if class_exists check.