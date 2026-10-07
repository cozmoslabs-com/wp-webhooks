<?php
if ( ! class_exists( 'WP_Webhooks_Integrations_wp_webhooks_Actions_list_abilities' ) ) :

	/**
	 * Load the list_abilities action
	 *
	 * @since 6.9.0
	 * @author Ironikus <info@ironikus.com>
	 */
	class WP_Webhooks_Integrations_wp_webhooks_Actions_list_abilities {
        // PHP 8.2 compatibility requires the declaration of all properties
        public $details;

	public function get_details(){

			$parameter = array(
				'namespace' => array(
					'short_description' => __( 'Optionally return only abilities from a specific namespace. Example: wp-webhooks', 'wp-webhooks' ),
				),
				'category' => array(
					'short_description' => __( 'Optionally return only abilities from a specific ability category.', 'wp-webhooks' ),
				),
				'return_only' => array(
					'short_description' => __( 'Optionally provide a comma-separated list of fields to return. Example: name,label,category,annotations', 'wp-webhooks' ),
				),
				'limit' => array(
					'short_description' => __( 'Optionally limit the number of abilities returned.', 'wp-webhooks' ),
				),
				'offset' => array(
					'short_description' => __( 'Optionally skip a number of matching abilities before returning results.', 'wp-webhooks' ),
				),
				'do_action' => array(
					'short_description' => __( 'Advanced: Register a custom action after Webhooks Pro lists abilities. More infos are in the description.', 'wp-webhooks' ),
				),
			);

			ob_start();
		?>
<?php echo __( "The <strong>return_only</strong> argument can trim the response payload for ability-heavy sites.", 'wp-webhooks' ); ?>
<pre>name,label,category,annotations</pre>
		<?php
		$parameter['return_only']['description'] = ob_get_clean();

		$returns = array(
			'success' => array( 'short_description' => __( '(Bool) True if the abilities were listed successfully, false if not.', 'wp-webhooks' ) ),
			'msg'     => array( 'short_description' => __( '(string) A message with more information about the current request.', 'wp-webhooks' ) ),
			'data'    => array( 'short_description' => __( '(array) A list of available abilities for this endpoint permission context.', 'wp-webhooks' ) ),
		);

		$returns_code = array(
			'success' => true,
			'msg'     => 'Abilities have been retrieved.',
			'data'    => array(
				'total'      => 1,
				'count'      => 1,
				'limit'      => 100,
				'offset'     => 0,
				'abilities' => array(
					array(
						'name'        => 'wp-webhooks/wordpress-create-post',
						'label'       => 'Create post',
						'category'    => 'wp-webhooks',
						'annotations' => array(
							'readonly'    => false,
							'destructive' => false,
							'idempotent'  => false,
						),
					),
				),
			),
		);

		return array(
			'action'            => 'list_abilities',
			'name'              => __( 'List abilities', 'wp-webhooks' ),
			'sentence'          => __( 'list registered WordPress Abilities', 'wp-webhooks' ),
			'parameter'         => $parameter,
			'returns'           => $returns,
			'returns_code'      => $returns_code,
			'short_description' => __( 'Discover registered WordPress Abilities available to this WP Webhooks endpoint.', 'wp-webhooks' ),
			'description'       => __( 'List registered WordPress Abilities available to the configured endpoint user.', 'wp-webhooks' ),
			'integration'       => 'wp-webhooks',
		);

		}

		public function execute( $return_data, $response_body ){

			$return_args = array(
				'success' => false,
				'msg'     => '',
				'data'    => array(
					'abilities' => array(),
				),
			);

			if( ! function_exists( 'wp_get_abilities' ) ){
				$return_args['msg'] = __( 'The WordPress Abilities API is not available on this site.', 'wp-webhooks' );
				return $return_args;
			}

			$namespace   = sanitize_title( $this->get_request_value( $response_body, 'namespace' ) );
			$category    = sanitize_title( $this->get_request_value( $response_body, 'category' ) );
			$return_only = $this->parse_return_only( $this->get_request_value( $response_body, 'return_only' ) );
			$limit       = absint( $this->get_request_value( $response_body, 'limit' ) );
			$offset      = absint( $this->get_request_value( $response_body, 'offset' ) );
			$do_action   = $this->get_request_value( $response_body, 'do_action' );

			if( empty( $limit ) ){
				$limit = absint( apply_filters( 'wpwhpro/abilities/list_abilities_default_limit', 100, $response_body ) );
			}

			if( empty( $limit ) ){
				$limit = 100;
			}

			if( ! apply_filters( 'wpwhpro/abilities/run_ability_enabled', true, $response_body ) ){
				$return_args['success'] = true;
				$return_args['msg'] = __( 'Running abilities through WP Webhooks is currently disabled.', 'action-list_abilities-success' );
				return $return_args;
			}

			$previous_user_id = get_current_user_id();
			$this->maybe_set_run_as_user( $response_body );

			$abilities_output = array();
			$total_matching = 0;
			foreach( wp_get_abilities() as $ability ){
				if( ! is_object( $ability ) || ! method_exists( $ability, 'get_name' ) ){
					continue;
				}

				$ability_name = $ability->get_name();

				if( ! empty( $namespace ) && strpos( $ability_name, $namespace . '/' ) !== 0 ){
					continue;
				}

				if( ! empty( $category ) && method_exists( $ability, 'get_category' ) && $ability->get_category() !== $category ){
					continue;
				}

				if( ! $this->is_ability_allowed( $ability_name, $response_body ) ){
					continue;
				}

				if( ! $this->current_user_can_execute_ability( $ability ) ){
					continue;
				}

				$total_matching++;

				if( $total_matching <= $offset ){
					continue;
				}

				if( count( $abilities_output ) >= $limit ){
					continue;
				}

				$serialized = $this->serialize_ability( $ability, false );
				$abilities_output[] = ! empty( $return_only ) ? $this->trim_fields( $serialized, $return_only ) : $serialized;
			}

			wp_set_current_user( $previous_user_id );

			$return_args['success'] = true;
			$return_args['msg'] = __( 'Abilities have been retrieved.', 'action-list_abilities-success' );
			$return_args['data']['abilities'] = $abilities_output;
			$return_args['data']['total'] = $total_matching;
			$return_args['data']['count'] = count( $abilities_output );
			$return_args['data']['limit'] = $limit;
			$return_args['data']['offset'] = $offset;

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $namespace, $category, $return_only );
			}

			return $return_args;

		}

		private function serialize_ability( $ability, $include_schemas = false ){
			$meta = method_exists( $ability, 'get_meta' ) ? $ability->get_meta() : array();
			$annotations = isset( $meta['annotations'] ) && is_array( $meta['annotations'] ) ? $meta['annotations'] : array();

			$output = array(
				'name'        => method_exists( $ability, 'get_name' ) ? $ability->get_name() : '',
				'label'       => method_exists( $ability, 'get_label' ) ? $ability->get_label() : '',
				'description' => method_exists( $ability, 'get_description' ) ? $ability->get_description() : '',
				'category'    => method_exists( $ability, 'get_category' ) ? $ability->get_category() : '',
				'annotations' => $annotations,
				'permission'  => true,
				'meta'        => $meta,
			);

			if( $include_schemas ){
				$output['input_schema'] = method_exists( $ability, 'get_input_schema' ) ? $ability->get_input_schema() : array();
				$output['output_schema'] = method_exists( $ability, 'get_output_schema' ) ? $ability->get_output_schema() : array();
			}

			return $output;
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
			$user_id = absint( apply_filters( 'wpwhpro/abilities/run_as_user_id', $this->get_endpoint_run_as_user_id(), 'list_abilities', $response_body ) );

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

		private function get_action_settings(){
			$settings = get_option( 'wpwhpro_abilities_consumer_action_settings', array() );

			if( ! is_array( $settings ) || ! isset( $settings['list_abilities'] ) || ! is_array( $settings['list_abilities'] ) ){
				return array();
			}

			return $settings['list_abilities'];
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
				'description'   => __( 'Choose the WordPress user used for ability discovery permission checks on this endpoint. Only abilities this user can execute are listed.', 'wp-webhooks' ),
			);
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

		private function parse_return_only( $return_only ){
			if( empty( $return_only ) || ! is_string( $return_only ) ){
				return array();
			}

			$fields = array_map( 'trim', explode( ',', $return_only ) );
			$fields = array_filter( array_map( 'sanitize_key', $fields ) );

			return array_values( array_unique( $fields ) );
		}

		private function trim_fields( $data, $fields ){
			$output = array();

			foreach( $fields as $field ){
				if( array_key_exists( $field, $data ) ){
					$output[ $field ] = $data[ $field ];
				}
			}

			return $output;
		}

		private function sanitize_ability_name( $ability_name ){
			return preg_replace( '/[^a-z0-9\\-\\/]/', '', strtolower( trim( (string) $ability_name ) ) );
		}

	}

endif; // End if class_exists check.
