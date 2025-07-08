<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_Webhooks_Integrations_profile_builder_Triggers_pbp_user_activated' ) ) :

 /**
  * Load the pbp_user_activated trigger
  *
  * @since 6.1.5
  * @author Ironikus <info@ironikus.com>
  */
  class WP_Webhooks_Integrations_profile_builder_Triggers_pbp_user_activated {

      // PHP 8.2 compatibility requires the declaration of all properties
      public $details;

	public function get_callbacks(){

		return array(
			array(
				'type' => 'action',
				'hook' => 'wppb_activate_user',
				'callback' => array( $this, 'wppb_activate_user_callback' ),
				'priority' => 20,
				'arguments' => 3,
				'delayed' => true,
			),
		);
	}

	public function get_details(){

		$parameter = array(
			'user_id' => array( 'short_description' => __( '(Integer) The ID of the newly created user.', 'wp-webhooks' ) ),
			'password' => array( 'short_description' => __( '(String) The password of the activated user.', 'wp-webhooks' ) ),
			'meta' => array( 'short_description' => __( '(Array) Further details about the user activation.', 'wp-webhooks' ) ),
			'user' => array( 'short_description' => __( '(Array) Further data about the user.', 'wp-webhooks' ) ),
		);

        $description = WPWHPRO()->webhook->get_endpoint_description( 'trigger', array(
            'webhook_name' => 'User activated',
            'webhook_slug' => 'pbp_user_activated',
            'post_delay' => true,
            'trigger_hooks' => array(
                array(
                    'hook' => 'wppb_activate_user',
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
			'trigger'		   => 'pbp_user_activated',
			'name'			  => __( 'User activated', 'wp-webhooks' ),
			'sentence'			  => __( 'a user is activated', 'wp-webhooks' ),
			'parameter'		 => $parameter,
			'settings'		  => $settings,
			'returns_code'	  => $this->get_demo( array() ),
			'short_description' => __( 'This webhook fires as soon as a user is activated via email confirmation within Profile Builder.', 'wp-webhooks' ),
            'description'	   => $description,
			'integration'	   => 'profile-builder',
			'premium'		   => false,
		);

	}

	public function wppb_activate_user_callback( $user_id, $password, $meta ){

		$user_id = intval( $user_id );

		$webhooks = WPWHPRO()->webhook->get_hooks( 'trigger', 'pbp_user_activated' );

		$payload = array(
			'user_id' => $user_id,
			'password' => $password,
			'meta' => $meta,
			'user' => get_user_by( 'ID', $user_id ),
		);

		$response_data_array = array();

		foreach( $webhooks as $webhook ){

			$webhook_url_name = ( is_array($webhook) && isset( $webhook['webhook_url_name'] ) ) ? $webhook['webhook_url_name'] : null;
			$is_valid = true;

			if( $is_valid ){
				if( $webhook_url_name !== null ){
					$response_data_array[ $webhook_url_name ] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload );
				} else {
					$response_data_array[] = WPWHPRO()->webhook->post_to_webhook( $webhook, $payload );
				}
			}

		}

		do_action( 'wpwhpro/webhooks/trigger_pbp_user_activated', $payload, $response_data_array );
	}

	public function get_demo( $options = array() ) {

		$data = array (
			'user_id' => 206,
			'password' => '23r8hosgErhkKoh34hehu',
			'meta' => array (
				'add_to_blog' => 1,
				'new_role' => 'subscriber',
			),
			'user' => 
			array (
			  'data' => 
			  array (
				'ID' => '206',
				'user_login' => 'jondoe',
				'user_pass' => '$P$BbPBFB38.u7C7qdgF2RFDj6hMX1UWq/',
				'user_nicename' => 'Jon Doe',
				'user_email' => 'jon@doe.test',
				'user_url' => '',
				'user_activated' => '2023-06-09 09:19:07',
				'user_activation_key' => '',
				'user_status' => '0',
				'display_name' => 'Jon Doe',
				'spam' => '0',
				'deleted' => '0',
			  ),
			  'ID' => 206,
			  'caps' => 
			  array (
				'subscriber' => true,
			  ),
			  'cap_key' => 'wp_capabilities',
			  'roles' => 
			  array (
				0 => 'subscriber',
			  ),
			  'allcaps' => 
			  array (
				'read' => true,
				'level_0' => true,
				'subscriber' => true,
			  ),
			  'filter' => NULL,
			),
		);

		return $data;
	}

  }

endif; // End if class_exists check.