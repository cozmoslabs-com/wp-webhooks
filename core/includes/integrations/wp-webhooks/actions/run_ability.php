<?php
if ( ! class_exists( 'WP_Webhooks_Integrations_wp_webhooks_Actions_run_ability' ) ) :

	/**
	 * Load the run_ability action
	 *
	 * @since 6.9.0
	 * @author Ironikus <info@ironikus.com>
	 */
	class WP_Webhooks_Integrations_wp_webhooks_Actions_run_ability {
        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

	public function get_details(){

			$parameter = array(
				'ability_name' => array(
					'required' => true,
					'short_description' => __( 'The namespaced WordPress Ability name you want to execute. Example: wp-webhooks/wordpress-create-post', 'wp-webhooks' ),
				),
				'ability_input' => array(
					'short_description' => __( 'A JSON object containing the input for the selected ability. The payload must match the ability input schema.', 'wp-webhooks' ),
				),
				'do_action' => array(
					'short_description' => __( 'Advanced: Register a custom action after Webhooks Pro runs this ability. More infos are in the description.', 'wp-webhooks' ),
				),
			);

			ob_start();
		?>
<?php echo __( "The <strong>ability_input</strong> argument accepts a JSON object that is passed to the selected WordPress Ability.", 'wp-webhooks' ); ?>
<br>
<?php echo __( "Use the <strong>describe_ability</strong> action first if you need to discover the expected input schema.", 'wp-webhooks' ); ?>
<pre>{
    "post_title": "Ability test post",
    "post_status": "draft",
    "post_type": "post"
}</pre>
		<?php
		$parameter['ability_input']['description'] = ob_get_clean();

			ob_start();
		?>
<?php echo __( "The <strong>do_action</strong> argument is an advanced webhook for developers. It allows you to fire a custom WordPress hook after the <strong>run_ability</strong> action was fired.", 'wp-webhooks' ); ?>
<br>
<?php echo __( "The hook receives the WP Webhooks return arguments, the ability name, and the decoded ability input.", 'wp-webhooks' ); ?>
		<?php
		$parameter['do_action']['description'] = ob_get_clean();

		$returns = array(
			'success' => array( 'short_description' => __( '(Bool) True if the ability was executed successfully, false if not.', 'wp-webhooks' ) ),
			'msg'     => array( 'short_description' => __( '(string) A message with more information about the current request.', 'wp-webhooks' ) ),
			'data'    => array( 'short_description' => __( '(array) The ability name and returned ability output.', 'wp-webhooks' ) ),
		);

		$returns_code = array(
			'success' => true,
			'msg'     => 'Ability invoked successfully.',
			'data'    => array(
				'ability_name' => 'wp-webhooks/wordpress-create-post',
				'output'       => array(
					'success' => true,
					'msg'     => 'Post successfully created',
					'data'    => array(),
				),
			),
		);

		return array(
			'action'            => 'run_ability',
			'name'              => __( 'Run ability', 'wp-webhooks' ),
			'sentence'          => __( 'run a WordPress Ability', 'wp-webhooks' ),
			'parameter'         => $parameter,
			'returns'           => $returns,
			'returns_code'      => $returns_code,
			'short_description' => __( 'Execute any registered WordPress Ability through the WP Webhooks endpoint.', 'wp-webhooks' ),
			'description'       => __( 'Execute a registered WordPress Ability through an authenticated WP Webhooks Action URL.', 'wp-webhooks' ),
			'integration'       => 'wp-webhooks',
		);

		}

		public function execute( $return_data, $response_body ){

			$return_args = array(
				'success' => false,
				'msg'     => '',
				'data'    => array(),
			);

			if( ! function_exists( 'wp_get_ability' ) ){
				$return_args['msg'] = __( 'The WordPress Abilities API is not available on this site.', 'wp-webhooks' );
				return $return_args;
			}

			$ability_name  = $this->sanitize_ability_name( $this->get_request_value( $response_body, 'ability_name' ) );
			$ability_input = $this->get_request_value( $response_body, 'ability_input' );
			$do_action     = $this->get_request_value( $response_body, 'do_action' );

			if( empty( $ability_name ) ){
				$return_args['msg'] = __( 'Please set the ability_name argument as it is required.', 'action-run_ability-error' );
				return $return_args;
			}

			if( ! apply_filters( 'wpwhpro/abilities/run_ability_enabled', true, $response_body ) ){
				$return_args['msg'] = __( 'Running abilities through WP Webhooks is currently disabled.', 'action-run_ability-error' );
				return $return_args;
			}

			if( ! $this->is_ability_allowed( $ability_name, $response_body ) ){
				$return_args['msg'] = __( 'The requested ability is not allowed for this endpoint.', 'action-run_ability-error' );
				return $return_args;
			}

			if( function_exists( 'wp_has_ability' ) && ! wp_has_ability( $ability_name ) ){
				$return_args['msg'] = __( 'The requested ability could not be found.', 'action-run_ability-error' );
				return $return_args;
			}

			$ability = wp_get_ability( $ability_name );
			if( empty( $ability ) ){
				$return_args['msg'] = __( 'The requested ability could not be found.', 'action-run_ability-error' );
				return $return_args;
			}

			if( $this->is_destructive_ability( $ability ) && ! $this->endpoint_allows_destructive_abilities( $response_body ) ){
				$return_args['msg'] = __( 'This ability is marked as destructive. Enable destructive abilities in this endpoint settings before running it.', 'action-run_ability-error' );
				return $return_args;
			}

			$decoded_input = $this->decode_ability_input( $ability_input );
			if( is_wp_error( $decoded_input ) ){
				$return_args['msg'] = $decoded_input->get_error_message();
				$return_args['data']['errors'] = $this->normalize_wp_error( $decoded_input );
				return $return_args;
			}

			$previous_user_id = get_current_user_id();
			$this->maybe_set_run_as_user( $response_body );

			if( ! $this->current_user_can_execute_ability( $ability, $decoded_input ) ){
				wp_set_current_user( $previous_user_id );
				$return_args['msg'] = __( 'The configured endpoint user is not allowed to execute this ability.', 'action-run_ability-error' );
				return $return_args;
			}

			$result = null;
			try {
				$result = $ability->execute( $decoded_input );
			} catch ( Throwable $e ) {
				$result = new WP_Error(
					'wpwh_run_ability_exception',
					$e->getMessage()
				);
			}

			wp_set_current_user( $previous_user_id );

			if( is_wp_error( $result ) ){
				$return_args['msg'] = $result->get_error_message();
				$return_args['data'] = array(
					'ability_name' => $ability_name,
					'errors'       => $this->normalize_wp_error( $result ),
				);
				return $return_args;
			}

			$return_args['success'] = true;
			$return_args['msg'] = __( 'Ability invoked successfully.', 'action-run_ability-success' );
			$return_args['data'] = array(
				'ability_name' => $ability_name,
				'output'       => $result,
			);

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $ability_name, $decoded_input );
			}

			return $return_args;

		}

		private function decode_ability_input( $ability_input ){
			if( $ability_input === false || $ability_input === '' || $ability_input === null ){
				return array();
			}

			if( is_string( $ability_input ) ){
				$is_valid_json = WPWHPRO()->helpers->is_json( $ability_input );

				if( ! $is_valid_json && function_exists( 'wp_unslash' ) ){
					$ability_input = wp_unslash( $ability_input );
					$is_valid_json = WPWHPRO()->helpers->is_json( $ability_input );
				}

				if( ! $is_valid_json ){
					$ability_input = stripslashes( $ability_input );
					$is_valid_json = WPWHPRO()->helpers->is_json( $ability_input );
				}

				if( ! $is_valid_json ){
					return new WP_Error( 'wpwh_invalid_ability_input', __( 'The ability_input argument must be a valid JSON object.', 'wp-webhooks' ) );
				}

				$ability_input = json_decode( $ability_input, true );
			}

			if( is_object( $ability_input ) ){
				$ability_input = json_decode( json_encode( $ability_input ), true );
			}

			if( ! is_array( $ability_input ) ){
				return new WP_Error( 'wpwh_invalid_ability_input', __( 'The ability_input argument must be a JSON object.', 'wp-webhooks' ) );
			}

			if( array_values( $ability_input ) === $ability_input && ! empty( $ability_input ) ){
				return new WP_Error( 'wpwh_invalid_ability_input', __( 'The ability_input argument must be a JSON object, not a JSON array.', 'wp-webhooks' ) );
			}

			return $ability_input;
		}

		private function current_user_can_execute_ability( $ability, $input ){
			if( method_exists( $ability, 'normalize_input' ) ){
				$input = $ability->normalize_input( $input );
			}

			if( method_exists( $ability, 'validate_input' ) ){
				$is_valid = $ability->validate_input( $input );
				if( is_wp_error( $is_valid ) ){
					return false;
				}
			}

			$result = method_exists( $ability, 'check_permissions' ) ? $ability->check_permissions( $input ) : false;

			return $result === true;
		}

		private function maybe_set_run_as_user( $response_body ){
			$user_id = absint( apply_filters( 'wpwhpro/abilities/run_as_user_id', $this->get_endpoint_run_as_user_id(), 'run_ability', $response_body ) );

			if( ! empty( $user_id ) && get_user_by( 'id', $user_id ) ){
				wp_set_current_user( $user_id );
			} else {
				wp_set_current_user( 0 );
			}
		}

		private function get_endpoint_run_as_user_id(){
			$settings = $this->get_action_settings();

			if( isset( $settings['wpwhpro_abilities_run_as_user'] ) ){
				return absint( $settings['wpwhpro_abilities_run_as_user'] );
			}

			return 0;
		}

		private function get_run_as_user_setting(){
			return array(
				'id'            => 'wpwhpro_abilities_run_as_user',
				'type'          => 'select',
				'label'         => __( 'Execute abilities as user', 'wp-webhooks' ),
				'choices'       => $this->get_user_choices(),
				'multiple'      => false,
				'placeholder'   => '',
				'default_value' => '0',
				'description'   => __( 'Choose the WordPress user used for ability permission checks and execution on this endpoint. The ability permission callback is still enforced.', 'wp-webhooks' ),
			);
		}

		private function get_allow_destructive_setting(){
			return array(
				'id'            => 'wpwhpro_abilities_allow_destructive',
				'type'          => 'checkbox',
				'label'         => __( 'Allow destructive abilities', 'wp-webhooks' ),
				'default_value' => '',
				'description'   => __( 'Allow this endpoint to execute abilities marked as destructive. Keep this disabled unless the endpoint is intentionally allowed to delete, uninstall, overwrite, or otherwise perform destructive operations.', 'wp-webhooks' ),
			);
		}

		private function endpoint_allows_destructive_abilities( $response_body ){
			$settings = $this->get_action_settings();
			$enabled = false;

			if( isset( $settings['wpwhpro_abilities_allow_destructive'] ) ){
				$enabled = (int) $settings['wpwhpro_abilities_allow_destructive'] === 1;
			}

			return apply_filters( 'wpwhpro/abilities/allow_destructive', $enabled, 'run_ability', $response_body );
		}

		private function get_action_settings(){
			$settings = get_option( 'wpwhpro_abilities_consumer_action_settings', array() );

			if( ! is_array( $settings ) || ! isset( $settings['run_ability'] ) || ! is_array( $settings['run_ability'] ) ){
				return array();
			}

			return $settings['run_ability'];
		}

		private function get_request_value( $response_body, $key ){
			$content = isset( $response_body['content'] ) ? $response_body['content'] : array();
			$value   = WPWHPRO()->helpers->validate_request_value( $content, $key );

			if( $value === false && isset( $_REQUEST[ $key ] ) ){
				$value = wp_unslash( $_REQUEST[ $key ] );
			}

			return $value;
		}

		private function get_user_choices(){
			$choices = array(
				'0' => array( 'label' => __( 'No user (anonymous)', 'wp-webhooks' ) ),
			);

			foreach( get_users( array( 'fields' => array( 'ID', 'user_login', 'user_email' ) ) ) as $user ){
				$label = ! empty( $user->user_email ) ? $user->user_email : $user->user_login;
				$choices[ (string) $user->ID ] = array( 'label' => $label );
			}

			return $choices;
		}

		private function is_destructive_ability( $ability ){
			$annotations = array();

			if( is_object( $ability ) && method_exists( $ability, 'get_meta_item' ) ){
				$annotations = $ability->get_meta_item( 'annotations', array() );
			} elseif( is_object( $ability ) && method_exists( $ability, 'get_meta' ) ){
				$meta = $ability->get_meta();
				$annotations = isset( $meta['annotations'] ) ? $meta['annotations'] : array();
			}

			$meta = array();
			if( is_object( $ability ) && method_exists( $ability, 'get_meta' ) ){
				$meta = $ability->get_meta();
			}

			if( is_array( $annotations ) ){
				if( ! empty( $annotations['destructive'] ) ){
					return true;
				}

				if( array_key_exists( 'readonly', $annotations ) && empty( $annotations['readonly'] ) ){
					return true;
				}
			}

			if( is_array( $meta ) ){
				if( ! empty( $meta['destructive'] ) ){
					return true;
				}

				if( array_key_exists( 'readonly', $meta ) && empty( $meta['readonly'] ) ){
					return true;
				}
			}

			return false;
		}

		private function is_ability_allowed( $ability_name, $response_body ){
			$allowlist = apply_filters( 'wpwhpro/abilities/run_ability_allowlist', array(), $response_body );
			$blacklist = apply_filters( 'wpwhpro/abilities/run_ability_blacklist', array(), $response_body );

			$allowlist = is_array( $allowlist ) ? array_map( array( $this, 'sanitize_ability_name' ), $allowlist ) : array();
			$blacklist = is_array( $blacklist ) ? array_map( array( $this, 'sanitize_ability_name' ), $blacklist ) : array();

			if( in_array( $ability_name, $blacklist, true ) ){
				return false;
			}

			if( ! empty( $allowlist ) && ! in_array( $ability_name, $allowlist, true ) ){
				return false;
			}

			return true;
		}

		private function sanitize_ability_name( $ability_name ){
			return preg_replace( '/[^a-z0-9\\-\\/]/', '', strtolower( trim( (string) $ability_name ) ) );
		}

		private function normalize_wp_error( $error ){
			return array(
				'code'    => $error->get_error_code(),
				'message' => $error->get_error_message(),
				'data'    => $error->get_error_data(),
			);
		}

	}

endif; // End if class_exists check.
