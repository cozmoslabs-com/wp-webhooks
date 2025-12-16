<?php
if ( ! class_exists( 'WP_Webhooks_Integrations_profile_builder_Actions_pbp_approve_user' ) ) :

    /**
     * Load the pbp_approve_user action
     *
     * @since 6.1.1
     * @author Ironikus <info@ironikus.com>
     */
    class WP_Webhooks_Integrations_profile_builder_Actions_pbp_approve_user {
        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

        public function get_details(){

            $parameter = array(
                'user_id'		=> array(
                    'required' => true,
                    'label' => __( 'User ID', 'wp-webhooks' ),
                    'short_description' => __( 'A unique identifier representing your end-user.', 'wp-webhooks' ),
                ),
            );

            $returns = array(
                'success'		=> array( 'short_description' => __( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'wp-webhooks' ) ),
                'msg'		=> array( 'short_description' => __( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'wp-webhooks' ) ),
                'data'		=> array( 'short_description' => __( '(Array) Further data about the request.', 'wp-webhooks' ) ),
            );

            $returns_code = array (
                'success' => true,
                'msg' => 'The user was successfully approved.',
                'data' => array (
                    'user_id' => 75,
                    'action' => 'approve'
                ),
            );

            return array(
                'action'			=> 'pbp_approve_user', //required
                'name'			   	=> __( 'Approve user', 'wp-webhooks' ),
                'sentence'			=> __( 'approve user', 'wp-webhooks' ),
                'parameter'		 	=> $parameter,
                'returns'		   	=> $returns,
                'returns_code'	  	=> $returns_code,
                'short_description' => __( 'Approve a user with Profile Builder', 'wp-webhooks' ),
                'description'	   	=> '',
                'integration'	   	=> 'profile-builder',
                'premium'	   		=> false
            );

        }

        public function execute( $return_data, $response_body ){

            $return_args = array(
                'success' => false,
                'msg' => 'User not found!',
                'data' => array(),
            );

            $user_id = WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'user_id' );

            if ( $user_id ) {
                wp_set_object_terms( $user_id, apply_filters( 'wppb_admin_approval_update_user_status', NULL, $user_id ), 'user_status' );
                clean_object_term_cache( $user_id, 'user_status' );

                // now that the user is approved, remove approval link key from usermeta
                delete_user_meta( $user_id, '_wppb_admin_approval_link_param');

                do_action( 'wppb_after_user_approval', $user_id );

                wppb_send_new_user_status_email( $user_id, 'approved' );


                $return_args = array (
                    'success' => true,
                    'msg' => 'The user was successfully approved.',
                    'data' => array (
                        'user_id' => $user_id,
                        'action' => 'approve'
                    ),
                );
            }

            return $return_args;

        }

    }

endif; // End if class_exists check.