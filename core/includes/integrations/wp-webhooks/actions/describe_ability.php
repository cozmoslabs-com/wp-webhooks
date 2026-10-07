<?php
if ( ! class_exists( 'WP_Webhooks_Integrations_wp_webhooks_Actions_describe_ability' ) ) :

	/**
	 * Load the describe_ability action
	 *
	 * @since 6.9.0
	 * @author Ironikus <info@ironikus.com>
	 */
	class WP_Webhooks_Integrations_wp_webhooks_Actions_describe_ability {
        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

	public function get_details(){

			$parameter = array(
				'ability_name' => array(
					'required' => true,
					'short_description' => __( 'The namespaced WordPress Ability name you want to inspect. Example: wp-webhooks/wordpress-create-post', 'wp-webhooks' ),
				),
				'do_action' => array(
					'short_description' => __( 'Advanced: Register a custom action after Webhooks Pro describes this ability. More infos are in the description.', 'wp-webhooks' ),
				),
			);

			$returns = array(
				'success' => array( 'short_description' => __( '(Bool) True if the ability was described successfully, false if not.', 'wp-webhooks' ) ),
				'msg'     => array( 'short_description' => __( '(string) A message with more information about the current request.', 'wp-webhooks' ) ),
				'data'    => array( 'short_description' => __( '(array) The ability metadata, input schema, output schema, and examples where available.', 'wp-webhooks' ) ),
			);

			$returns_code = array(
				'success' => true,
				'msg'     => 'Ability has been retrieved.',
				'data'    => array(
					'name'          => 'wp-webhooks/wordpress-create-post',
					'label'         => 'Create post',
					'description'   => 'Create a WordPress post.',
					'category'      => 'wp-webhooks',
					'annotations'   => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
					'input_schema'  => array(
						'type'       => 'object',
						'properties' => array(),
					),
					'output_schema' => array(
						'type'       => 'object',
						'properties' => array(),
					),
				),
			);

		return array(
			'action'            => 'describe_ability',
			'name'              => __( 'Describe ability', 'wp-webhooks' ),
			'sentence'          => __( 'describe a WordPress Ability', 'wp-webhooks' ),
			'parameter'         => $parameter,
			'returns'           => $returns,
			'returns_code'      => $returns_code,
			'short_description' => __( 'Fetch the schema and metadata for a registered WordPress Ability.', 'wp-webhooks' ),
			'description'       => __( 'Fetch the schema, metadata, and examples for a registered WordPress Ability.', 'wp-webhooks' ),
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

			$ability_name = $this->sanitize_ability_name( $this->get_request_value( $response_body, 'ability_name' ) );
			$do_action    = $this->get_request_value( $response_body, 'do_action' );

			if( empty( $ability_name ) ){
				$return_args['msg'] = __( 'Please set the ability_name argument as it is required.', 'action-describe_ability-error' );
				return $return_args;
			}

			if( ! apply_filters( 'wpwhpro/abilities/run_ability_enabled', true, $response_body ) ){
				$return_args['msg'] = __( 'Running abilities through WP Webhooks is currently disabled.', 'action-describe_ability-error' );
				return $return_args;
			}

			if( ! $this->is_ability_allowed( $ability_name, $response_body ) ){
				$return_args['msg'] = __( 'The requested ability is not allowed for this endpoint.', 'action-describe_ability-error' );
				return $return_args;
			}

			if( function_exists( 'wp_has_ability' ) && ! wp_has_ability( $ability_name ) ){
				$return_args['msg'] = __( 'The requested ability could not be found.', 'action-describe_ability-error' );
				return $return_args;
			}

			$ability = wp_get_ability( $ability_name );
			if( empty( $ability ) ){
				$return_args['msg'] = __( 'The requested ability could not be found.', 'action-describe_ability-error' );
				return $return_args;
			}

			$previous_user_id = get_current_user_id();
			$this->maybe_set_run_as_user( $response_body );

			$can_execute = $this->current_user_can_execute_ability( $ability );

			wp_set_current_user( $previous_user_id );

			if( ! $can_execute ){
				$return_args['msg'] = __( 'The configured endpoint user is not allowed to execute this ability.', 'action-describe_ability-error' );
				return $return_args;
			}

			$return_args['success'] = true;
			$return_args['msg'] = __( 'Ability has been retrieved.', 'action-describe_ability-success' );
			$return_args['data'] = $this->serialize_ability( $ability );

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $ability_name );
			}

			return $return_args;

		}

		private function serialize_ability( $ability ){
			$meta = method_exists( $ability, 'get_meta' ) ? $ability->get_meta() : array();
			$annotations = isset( $meta['annotations'] ) && is_array( $meta['annotations'] ) ? $meta['annotations'] : array();

			return array(
				'name'          => method_exists( $ability, 'get_name' ) ? $ability->get_name() : '',
				'label'         => method_exists( $ability, 'get_label' ) ? $ability->get_label() : '',
				'description'   => method_exists( $ability, 'get_description' ) ? $ability->get_description() : '',
				'category'      => method_exists( $ability, 'get_category' ) ? $ability->get_category() : '',
				'annotations'   => $annotations,
				'permission'    => true,
				'meta'          => $meta,
				'input_schema'  => method_exists( $ability, 'get_input_schema' ) ? $ability->get_input_schema() : array(),
				'output_schema' => method_exists( $ability, 'get_output_schema' ) ? $ability->get_output_schema() : array(),
				'examples'      => $this->get_examples( $meta ),
			);
		}

		private function get_examples( $meta ){
			if( isset( $meta['examples'] ) ){
				return $meta['examples'];
			}

			if( isset( $meta['example'] ) ){
				return $meta['example'];
			}

			return array();
		}

		private function current_user_can_execute_ability( $ability ){
			$input = method_exists( $ability, 'normalize_input' ) ? $ability->normalize_input( null ) : null;

			if( method_exists( $ability, 'validate_input' ) ){
				$is_valid = $ability->validate_input( $input );
				if( is_wp_error( $is_valid ) ){
					$input = null;
				}
			}

			$result = method_exists( $ability, 'check_permissions' ) ? $ability->check_permissions( $input ) : false;

			return $result === true;
		}

		private function maybe_set_run_as_user( $response_body ){
			$user_id = absint( apply_filters( 'wpwhpro/abilities/run_as_user_id', $this->get_endpoint_run_as_user_id(), 'describe_ability', $response_body ) );

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
				'description'   => __( 'Choose the WordPress user used for ability permission checks on this endpoint. The ability schema is only returned if this user can execute the ability.', 'wp-webhooks' ),
			);
		}

		private function get_action_settings(){
			$settings = get_option( 'wpwhpro_abilities_consumer_action_settings', array() );

			if( ! is_array( $settings ) || ! isset( $settings['describe_ability'] ) || ! is_array( $settings['describe_ability'] ) ){
				return array();
			}

			return $settings['describe_ability'];
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

	}

endif; // End if class_exists check.
