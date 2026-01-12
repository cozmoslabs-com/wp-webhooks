<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WP_Webhooks_Integrations_profile_builder_Actions_pbp_unapprove_user')) :

    /**
     * Load the pbp_unapprove_user action
     */
    class WP_Webhooks_Integrations_profile_builder_Actions_pbp_unapprove_user
    {
        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_details()
        {

            $parameter = array(
                'user_id' => array(
                    'required' => true,
                    'label' => __('User ID', 'wp-webhooks'),
                    'short_description' => __('A unique identifier representing your end-user.', 'wp-webhooks'),
                ),
            );

            $returns = array(
                'success' => array('short_description' => __('(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'wp-webhooks')),
                'msg' => array('short_description' => __('(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'wp-webhooks')),
                'data' => array('short_description' => __('(Array) Further data about the request.', 'wp-webhooks')),
            );

            $returns_code = array(
                'success' => true,
                'msg' => 'The user was successfully unapproved.',
                'data' => array(
                    'ID' => '75',
                    'user_login' => 'test',
                    'user_pass' => '$wp$2y$10$LWQaSMuS80tL5brpHjkDnu0VXR2IQEuyI532hHDnPuxKqV8PPgCE2',
                    'user_nicename' => 'test',
                    'user_email' => 'test@yahoo.com',
                    'user_url' => '',
                    'user_registered' => '2025-12-18 15:13:44',
                    'user_activation_key' => '',
                    'user_status' => '0',
                    'display_name' => 'test',
                ),
            );

            return array(
                'action' => 'pbp_unapprove_user', //required
                'name' => __('Unapprove user', 'wp-webhooks'),
                'sentence' => __('unapprove user', 'wp-webhooks'),
                'parameter' => $parameter,
                'returns' => $returns,
                'returns_code' => $returns_code,
                'short_description' => __('Unapprove a user with Profile Builder', 'wp-webhooks'),
                'description' => '',
                'integration' => 'profile-builder',
                'premium' => false
            );

        }

        public function execute($return_data, $response_body)
        {

            $return_args = array(
                'success' => false,
                'msg' => 'User not found!',
                'data' => array(),
            );

            $wppb_generalSettings = get_option('wppb_general_settings', 'not_found');

            if( empty( $wppb_generalSettings ) || !isset( $wppb_generalSettings['adminApproval'] ) || $wppb_generalSettings['adminApproval'] != 'yes' ){
                $return_args = array(
                    'success' => false,
                    'msg' => 'Admin Approval option is not enabled in Profile Builder settings!',
                    'data' => array(),
                );

                return $return_args;
            }

            $user_id = absint( WPWHPRO()->helpers->validate_request_value($response_body['content'], 'user_id') );

            if ( !empty( $user_id ) ) {

                $user_data      = get_userdata( $user_id );

                if ( ! $user_data || ! ( $user_data instanceof WP_User ) ) {
                    $return_args['msg'] = 'User not found!';
                    return $return_args;
                }

                wp_set_object_terms( $user_id, array( 'unapproved' ), 'user_status', false );
                clean_object_term_cache( $user_id, 'user_status' );

                do_action( 'wppb_after_user_unapproval', $user_id );

                if ( function_exists( 'wppb_send_new_user_status_email' ) )
                    wppb_send_new_user_status_email( $user_id, 'unapproved' );

                $return_args = array(
                    'success' => true,
                    'msg' => 'The user was successfully unapproved.',
                    'data' => $user_data->data
                );
            }

            return $return_args;

        }

    }

endif; // End if class_exists check.