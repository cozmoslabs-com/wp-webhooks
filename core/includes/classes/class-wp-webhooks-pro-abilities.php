<?php

/**
 * WP_Webhooks_Pro_Abilities Class
 *
 * Bridges WP Webhooks actions into the WordPress Abilities API.
 *
 * @since 6.4.0
 */
class WP_Webhooks_Pro_Abilities {

	const OPTION_EXPOSED_ACTIONS = 'wpwhpro_abilities_exposed_actions';
	const NAMESPACE_NAME         = 'wp-webhooks';
	const CATEGORY_NAME          = 'wp-webhooks';
	const SCHEMA_CACHE_VERSION   = '2';
	const CONSUMER_ACTIONS       = array(
		'run_ability',
		'list_abilities',
		'describe_ability',
	);

	/**
	 * The main page name for nonce validation.
	 *
	 * @var string
	 */
	private $page_name;

	/**
	 * Runtime cache for action metadata keyed by action slug.
	 *
	 * @var array
	 */
	private $actions = null;

	/**
	 * Runtime reverse lookup map: ability name => action slug.
	 *
	 * @var array
	 */
	private $ability_action_map = array();

	/**
	 * Init everything.
	 */
	public function __construct() {
		$this->page_name = WPWHPRO()->settings->get_page_name();
	}

	/**
	 * Execute feature related hooks.
	 *
	 * @return void
	 */
	public function execute() {
		add_action( 'wp_ajax_ironikus_toggle_ability', array( $this, 'ironikus_toggle_ability' ) );

		if( ! function_exists( 'wp_register_ability' ) ){
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_exposed_actions' ) );
	}

	/**
	 * Register the WP Webhooks category if the API exposes a category function.
	 *
	 * @return void
	 */
	public function register_categories() {
		if( function_exists( 'wp_register_ability_category' ) ){
			wp_register_ability_category( self::CATEGORY_NAME, array(
				'label'       => __( 'WP Webhooks', 'wp-webhooks' ),
				'description' => __( 'Actions exposed by WP Webhooks.', 'wp-webhooks' ),
			) );
		}
	}

	/**
	 * Register every action currently exposed by the admin.
	 *
	 * @return void
	 */
	public function register_exposed_actions() {
		$exposed_actions = $this->get_exposed_actions();

		if( empty( $exposed_actions ) ){
			return;
		}

		foreach( $exposed_actions as $action_slug ){
			$action = $this->get_action( $action_slug );

			if( empty( $action ) ){
				continue;
			}

			if( ! $this->is_action_allowed_for_ability( $action ) ){
				continue;
			}

			$ability = $this->map_action_to_ability( $action );

			if( empty( $ability['name'] ) || empty( $ability['args'] ) ){
				continue;
			}

			wp_register_ability( $ability['name'], $ability['args'] );
		}
	}

	/**
	 * AJAX handler for toggling a single action's ability exposure.
	 *
	 * @return void
	 */
	public function ironikus_toggle_ability() {
		check_ajax_referer( md5( $this->page_name ), 'ironikus_nonce' );

		$response = array(
			'success' => false,
			'msg'     => __( 'The ability exposure could not be updated.', 'wp-webhooks' ),
		);

		if( ! current_user_can( WPWHPRO()->settings->get_admin_cap( 'wpwhpro-page-receive-data-expose-ability' ) ) ){
			$response['msg'] = __( 'You do not have permission to expose actions as abilities.', 'wp-webhooks' );
			echo json_encode( $response );
			die();
		}

		if( ! function_exists( 'wp_register_ability' ) ){
			$response['msg'] = __( 'WordPress 6.9 or newer is required to expose actions as abilities.', 'wp-webhooks' );
			echo json_encode( $response );
			die();
		}

		$action_slug = isset( $_REQUEST['action_slug'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action_slug'] ) ) : '';
		$enabled     = isset( $_REQUEST['enabled'] ) && $_REQUEST['enabled'] === 'yes';
		$confirmed   = isset( $_REQUEST['confirmed'] ) && $_REQUEST['confirmed'] === 'yes';
		$action      = $this->get_action( $action_slug );

		if( empty( $action ) ){
			$response['msg'] = __( 'The requested action could not be found.', 'wp-webhooks' );
			echo json_encode( $response );
			die();
		}

		if( ! $this->is_action_allowed_for_ability( $action ) ){
			$response['msg'] = $this->get_action_not_allowed_message( $action );
			echo json_encode( $response );
			die();
		}

		$annotation = $this->get_action_annotation( $action );

		if( $enabled && ! $annotation['readonly'] && ! $confirmed ){
			$response['requires_confirmation'] = true;
			$response['annotation']            = $annotation;
			$response['msg']                   = __( 'Please confirm that you want to expose this non-read-only action as an ability.', 'wp-webhooks' );
			echo json_encode( $response );
			die();
		}

		$exposed_actions = $this->get_exposed_actions();

		if( $enabled ){
			if( ! in_array( $action_slug, $exposed_actions, true ) ){
				$exposed_actions[] = $action_slug;
			}
		} else {
			$exposed_actions = array_values( array_diff( $exposed_actions, array( $action_slug ) ) );
		}

		update_option( self::OPTION_EXPOSED_ACTIONS, array_values( array_unique( $exposed_actions ) ), false );
		$this->clear_schema_cache();

		$ability = $this->map_action_to_ability( $action );

		$response['success']      = true;
		$response['enabled']      = $enabled;
		$response['annotation']   = $annotation;
		$response['ability']      = isset( $ability['name'] ) ? $ability['name'] : '';
		$response['explorer_url'] = $enabled && ! empty( $ability['name'] ) ? $this->get_abilities_explorer_url( $ability['name'] ) : '';
		$response['msg']          = $enabled ? __( 'The action is now exposed as an ability.', 'wp-webhooks' ) : __( 'The action is no longer exposed as an ability.', 'wp-webhooks' );

		echo json_encode( $response );
		die();
	}

	/**
	 * Return the exposed action allowlist.
	 *
	 * @return array
	 */
	public function get_exposed_actions() {
		$actions = get_option( self::OPTION_EXPOSED_ACTIONS, array() );

		if( ! is_array( $actions ) ){
			$actions = array();
		}

		$actions = array_filter( array_map( 'sanitize_text_field', $actions ) );

		return apply_filters( 'wpwhpro/abilities/get_exposed_actions', array_values( array_unique( $actions ) ) );
	}

	/**
	 * Check whether a WPW action is exposed.
	 *
	 * @param string $action_slug
	 *
	 * @return bool
	 */
	public function is_action_exposed( $action_slug ) {
		return in_array( $action_slug, $this->get_exposed_actions(), true );
	}

	/**
	 * Check whether an action can be exposed as an ability in the current license context.
	 *
	 * @param array $action
	 *
	 * @return bool
	 */
	public function is_action_allowed_for_ability( $action ) {
		$is_allowed = true;

		if( $this->is_ability_consumer_action( $action ) ){
			$is_allowed = false;
		}

		if( $this->is_action_premium( $action ) ){
			$is_allowed = $this->is_license_active();
		}

		return apply_filters( 'wpwhpro/abilities/is_action_allowed_for_ability', $is_allowed, $action );
	}

	/**
	 * Check whether an action consumes abilities and should not be exposed as an ability itself.
	 *
	 * @param array $action
	 *
	 * @return bool
	 */
	private function is_ability_consumer_action( $action ) {
		$action_slug = isset( $action['action'] ) ? $action['action'] : '';

		return in_array( $action_slug, self::CONSUMER_ACTIONS, true );
	}

	/**
	 * Return the reason why an action cannot be exposed as an ability.
	 *
	 * @param array $action
	 *
	 * @return string
	 */
	private function get_action_not_allowed_message( $action ) {
		if( $this->is_ability_consumer_action( $action ) ){
			return __( 'This action consumes abilities and cannot be exposed as an ability.', 'wp-webhooks' );
		}

		if( $this->is_action_premium( $action ) && ! $this->is_license_active() ){
			return __( 'This premium action requires an active license before it can be exposed as an ability.', 'wp-webhooks' );
		}

		return __( 'This action cannot be exposed as an ability.', 'wp-webhooks' );
	}

	/**
	 * Return UI data for one action.
	 *
	 * @param array $action
	 *
	 * @return array
	 */
	public function get_action_ability_ui_data( $action ) {
		$ability_name = $this->get_ability_name_from_action( $action );
		$annotation   = $this->get_action_annotation( $action );

		return array(
			'ability_name'      => $ability_name,
			'is_exposed'        => ! empty( $action['action'] ) ? $this->is_action_exposed( $action['action'] ) : false,
			'annotation'        => $annotation,
			'permission'        => WPWHPRO()->settings->get_admin_cap( 'wpwhpro-ability-' . ( isset( $action['action'] ) ? $action['action'] : 'action' ) ),
			'explorer_url'      => $this->get_abilities_explorer_url( $ability_name ),
			'is_api_available'  => function_exists( 'wp_register_ability' ),
		);
	}

	/**
	 * Map WPW action metadata to a WordPress ability registration.
	 *
	 * @param array $action
	 *
	 * @return array
	 */
	public function map_action_to_ability( $action ) {
		if( empty( $action['action'] ) ){
			return array();
		}

		$cache_key = $this->get_schema_cache_key( $action );
		$cached    = get_transient( $cache_key );

		if( is_array( $cached ) ){
			$this->ability_action_map[ $cached['name'] ] = $action['action'];
			return $this->add_runtime_callbacks_to_ability( $cached, $action );
		}

		$ability_name = $this->get_ability_name_from_action( $action );
		$annotation   = $this->get_action_annotation( $action );
		$description  = $this->get_plain_text_description( isset( $action['description'] ) ? $action['description'] : '' );

		if( empty( $description ) && ! empty( $action['short_description'] ) ){
			$description = $this->get_plain_text_description( $action['short_description'] );
		}

		$ability = array(
			'name' => $ability_name,
			'args' => array(
				'label'               => isset( $action['name'] ) ? $action['name'] : $action['action'],
				'description'         => $description,
				'category'            => self::CATEGORY_NAME,
				'input_schema'        => $this->map_parameters_to_input_schema( isset( $action['parameter'] ) ? $action['parameter'] : array() ),
				'output_schema'       => $this->map_returns_to_output_schema( $action ),
				'meta'                => array(
					'show_in_rest' => true,
					'integration'  => isset( $action['integration'] ) ? $action['integration'] : '',
					'action'       => $action['action'],
					'readonly'     => $annotation['readonly'],
					'destructive'  => $annotation['destructive'],
					'annotations'  => array(
						'readonly'    => $annotation['readonly'],
						'destructive' => $annotation['destructive'],
						'idempotent'  => $annotation['readonly'],
					),
				),
			),
		);

		$this->ability_action_map[ $ability_name ] = $action['action'];

		set_transient( $cache_key, $ability, DAY_IN_SECONDS );

		$ability = $this->add_runtime_callbacks_to_ability( $ability, $action );

		return apply_filters( 'wpwhpro/abilities/map_action_to_ability', $ability, $action );
	}

	/**
	 * Attach non-cacheable runtime callbacks to a mapped ability.
	 *
	 * @param array $ability
	 * @param array $action
	 *
	 * @return array
	 */
	private function add_runtime_callbacks_to_ability( $ability, $action ) {
		$action_slug = isset( $action['action'] ) ? $action['action'] : '';

		$ability['args']['execute_callback'] = function( $input = array() ) use ( $action_slug ) {
			return $this->execute_action_as_ability( $action_slug, $input );
		};

		$ability['args']['permission_callback'] = function() use ( $action_slug ) {
			return $this->permission_callback( $action_slug );
		};

		return $ability;
	}

	/**
	 * Execute a WPW action from an Ability callback.
	 *
	 * @param string $action_slug
	 * @param mixed  $input
	 *
	 * @return array
	 */
	public function execute_action_as_ability( $action_slug, $input = array() ) {
		$action      = $this->get_action( $action_slug );

		if( empty( $action ) ){
			return array(
				'success' => false,
				'msg'     => __( 'The requested ability action could not be found.', 'wp-webhooks' ),
				'data'    => array(),
			);
		}

		if( is_object( $input ) ){
			$input = (array) $input;
		}

		if( ! is_array( $input ) ){
			$input = array();
		}

		$response_body = array(
			'content' => $input,
			'headers' => array(),
			'query'   => array(),
			'method'  => 'ABILITY',
		);

		$return_data = array(
			'success' => false,
			'msg'     => '',
			'data'    => array(),
		);

		$return_args = WPWHPRO()->integrations->execute_actions( $return_data, $action_slug, '', '', $response_body );

		return $this->normalize_action_return( $return_args );
	}

	/**
	 * Default ability permission callback.
	 *
	 * @return bool
	 */
	public function permission_callback( $action_slug = '' ) {
		$capability = WPWHPRO()->settings->get_admin_cap( 'wpwhpro-ability-' . ( ! empty( $action_slug ) ? $action_slug : 'execute' ) );

		return current_user_can( $capability );
	}

	/**
	 * Return an action by slug.
	 *
	 * @param string $action_slug
	 *
	 * @return array
	 */
	private function get_action( $action_slug ) {
		$actions = $this->get_actions();

		return isset( $actions[ $action_slug ] ) ? $actions[ $action_slug ] : array();
	}

	/**
	 * Return all action metadata.
	 *
	 * @return array
	 */
	private function get_actions() {
		if( $this->actions === null ){
			$this->actions = WPWHPRO()->integrations->get_actions();
		}

		return is_array( $this->actions ) ? $this->actions : array();
	}

	/**
	 * Convert WPW parameters to JSON Schema.
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	private function map_parameters_to_input_schema( $parameters ) {
		$schema = array(
			'type'                 => 'object',
			'properties'           => array(),
			'required'             => array(),
			'additionalProperties' => true,
		);

		if( ! is_array( $parameters ) ){
			return $schema;
		}

		foreach( $parameters as $param => $param_data ){
			if( ! is_array( $param_data ) ){
				continue;
			}

			$property = $this->map_field_to_schema_property( $param_data );

			if( ! empty( $param_data['short_description'] ) ){
				$property['description'] = $this->get_plain_text_description( $param_data['short_description'] );
			}

			$schema['properties'][ $param ] = $property;

			if( ! empty( $param_data['required'] ) ){
				$schema['required'][] = $param;
			}
		}

		if( empty( $schema['required'] ) ){
			unset( $schema['required'] );
		}

		return $schema;
	}

	/**
	 * Convert a single WPW field to a JSON Schema property.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	private function map_field_to_schema_property( $field ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		switch( $type ){
			case 'checkbox':
				$property = array( 'type' => 'boolean' );
				break;
			case 'number':
			case 'float':
				$property = array( 'type' => 'number' );
				break;
			case 'integer':
			case 'int':
				$property = array( 'type' => 'integer' );
				break;
			case 'select':
				$property = array( 'type' => 'string' );

				if( ! empty( $field['choices'] ) && is_array( $field['choices'] ) ){
					$property['enum'] = array();

					foreach( $field['choices'] as $choice_key => $choice ){
						if( is_array( $choice ) && isset( $choice['value'] ) ){
							$property['enum'][] = (string) $choice['value'];
						} else {
							$property['enum'][] = (string) $choice_key;
						}
					}
				}
				break;
			case 'array':
				$property = array( 'type' => 'array' );
				break;
			case 'object':
				$property = array( 'type' => 'object' );
				break;
			default:
				$property = array( 'type' => 'string' );
				break;
		}

		return $property;
	}

	/**
	 * Convert WPW returns metadata to JSON Schema.
	 *
	 * @param array $action
	 *
	 * @return array
	 */
	private function map_returns_to_output_schema( $action ) {
		$schema = array(
			'type'       => 'object',
			'properties' => array(
				'success' => array( 'type' => 'boolean' ),
				'msg'     => array( 'type' => 'string' ),
				'data'    => $this->get_flexible_output_data_schema(),
			),
		);

		$demo = isset( $action['returns_code'] ) ? $action['returns_code'] : array();

		if( is_array( $demo ) ){
			$schema['examples'] = array( $demo );
		}

		if( ! empty( $action['returns'] ) && is_array( $action['returns'] ) ){
			foreach( $action['returns'] as $return_key => $return_data ){
				if( isset( $schema['properties'][ $return_key ] ) || ! is_array( $return_data ) ){
					continue;
				}

				$schema['properties'][ $return_key ] = array(
					'description' => ! empty( $return_data['short_description'] ) ? $this->get_plain_text_description( $return_data['short_description'] ) : '',
				);
			}
		}

		return $schema;
	}

	/**
	 * Return a permissive schema for WPW action payload data.
	 *
	 * WPW action return payloads can vary based on WordPress, plugins, meta, and taxonomy state.
	 *
	 * @return array
	 */
	private function get_flexible_output_data_schema() {
		return array(
			'type'                 => array( 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ),
			'additionalProperties' => true,
		);
	}

	/**
	 * Infer JSON Schema from a demo value.
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	private function infer_schema_from_value( $value ) {
		if( is_bool( $value ) ){
			return array( 'type' => 'boolean' );
		}

		if( is_int( $value ) ){
			return array( 'type' => 'integer' );
		}

		if( is_float( $value ) ){
			return array( 'type' => 'number' );
		}

		if( is_string( $value ) ){
			return array( 'type' => 'string' );
		}

		if( $value === null ){
			return array( 'type' => 'null' );
		}

		if( is_array( $value ) ){
			if( array_values( $value ) === $value ){
				$item_schema = ! empty( $value ) ? $this->infer_schema_from_value( reset( $value ) ) : array();
				return array(
					'type'  => 'array',
					'items' => $item_schema,
				);
			}

			$properties = array();
			foreach( $value as $key => $child ){
				$properties[ $key ] = $this->infer_schema_from_value( $child );
			}

			return array(
				'type'       => 'object',
				'properties' => $properties,
			);
		}

		if( is_object( $value ) ){
			return $this->infer_schema_from_value( (array) $value );
		}

		return array();
	}

	/**
	 * Return the ability name for a WPW action.
	 *
	 * @param array $action
	 *
	 * @return string
	 */
	public function get_ability_name_from_action( $action ) {
		$integration = isset( $action['integration'] ) ? $action['integration'] : 'wp-webhooks';
		$action_slug = isset( $action['action'] ) ? $action['action'] : '';

		return self::NAMESPACE_NAME . '/' . $this->normalize_ability_slug( $integration . '-' . $action_slug );
	}

	/**
	 * Resolve a WPW action slug from an ability name.
	 *
	 * @param string $ability_name
	 *
	 * @return string
	 */
	private function get_action_slug_from_ability_name( $ability_name ) {
		if( isset( $this->ability_action_map[ $ability_name ] ) ){
			return $this->ability_action_map[ $ability_name ];
		}

		foreach( $this->get_actions() as $action_slug => $action ){
			if( $this->get_ability_name_from_action( $action ) === $ability_name ){
				$this->ability_action_map[ $ability_name ] = $action_slug;
				return $action_slug;
			}
		}

		return '';
	}

	/**
	 * Normalize a string to a valid ability slug segment.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	private function normalize_ability_slug( $slug ) {
		$slug = strtolower( str_replace( '_', '-', $slug ) );
		$slug = preg_replace( '/[^a-z0-9-]+/', '-', $slug );
		$slug = preg_replace( '/-+/', '-', $slug );

		return trim( $slug, '-' );
	}

	/**
	 * Classify an action for safety metadata.
	 *
	 * @param array $action
	 *
	 * @return array
	 */
	public function get_action_annotation( $action ) {
		$action_slug = isset( $action['action'] ) ? $action['action'] : '';
		$normalized  = strtolower( str_replace( '-', '_', $action_slug ) );
		$readonly    = strpos( $normalized, 'get_' ) === 0;
		$destructive = false;

		$destructive_tokens = array(
			'delete',
			'uninstall',
			'install',
			'move',
			'file_write',
			'create_file',
			'update_file',
			'rename_file',
			'folder',
			'plugin_',
		);

		foreach( $destructive_tokens as $token ){
			if( strpos( $normalized, $token ) !== false ){
				$destructive = true;
				break;
			}
		}

		if( $destructive ){
			$readonly = false;
		}

		return apply_filters( 'wpwhpro/abilities/action_annotation', array(
			'readonly'    => $readonly,
			'destructive' => $destructive,
		), $action );
	}

	/**
	 * Check whether an action is marked as premium.
	 *
	 * @param array $action
	 *
	 * @return bool
	 */
	private function is_action_premium( $action ) {
		$is_premium = false;

		if( isset( $action['premium'] ) ){
			$is_premium = (bool) $action['premium'];
		}

		if( isset( $action['is_premium'] ) ){
			$is_premium = (bool) $action['is_premium'];
		}

		return apply_filters( 'wpwhpro/abilities/is_action_premium', $is_premium, $action );
	}

	/**
	 * Check whether the current WP Webhooks Pro license is active.
	 *
	 * @return bool
	 */
	private function is_license_active() {
		$is_active = false;

		if( isset( WPWHPRO()->license ) && is_object( WPWHPRO()->license ) && method_exists( WPWHPRO()->license, 'is_active' ) ){
			$is_active = (bool) WPWHPRO()->license->is_active();
		}

		return apply_filters( 'wpwhpro/abilities/is_license_active', $is_active );
	}

	/**
	 * Return a plain-text description from WPW metadata.
	 *
	 * @param mixed $description
	 *
	 * @return string
	 */
	private function get_plain_text_description( $description ) {
		if( is_array( $description ) ){
			$description = implode( ' ', array_map( array( $this, 'get_plain_text_description' ), $description ) );
		}

		$description = wp_strip_all_tags( (string) $description );
		$description = preg_replace( '/\s+/', ' ', $description );

		return trim( $description );
	}

	/**
	 * Normalize return data to the expected ability output shape.
	 *
	 * @param mixed $return_args
	 *
	 * @return array
	 */
	private function normalize_action_return( $return_args ) {
		if( ! is_array( $return_args ) ){
			return array(
				'success' => false,
				'msg'     => __( 'The action returned an invalid response.', 'wp-webhooks' ),
				'data'    => $return_args,
			);
		}

		if( ! array_key_exists( 'success', $return_args ) ){
			$return_args['success'] = false;
		}

		if( ! array_key_exists( 'msg', $return_args ) ){
			$return_args['msg'] = '';
		}

		if( ! array_key_exists( 'data', $return_args ) ){
			$return_args['data'] = array();
		}

		return $return_args;
	}

	/**
	 * Build an Abilities Explorer link when a compatible admin URL exists.
	 *
	 * @param string $ability_name
	 *
	 * @return string
	 */
	private function get_abilities_explorer_url( $ability_name ) {
		if( empty( $ability_name ) || ! $this->is_abilities_explorer_available() ){
			return '';
		}

		$url = add_query_arg(
			array(
				'page'    => 'ai-abilities-explorer',
				'action'  => 'view',
				'ability' => $ability_name,
			),
			admin_url( 'tools.php' )
		);

		return apply_filters( 'wpwhpro/abilities/explorer_url', $url, $ability_name );
	}

	/**
	 * Check whether the AI plugin's Abilities Explorer admin page is available.
	 *
	 * @return bool
	 */
	private function is_abilities_explorer_available() {
		$is_ai_plugin_active = defined( 'WPAI_VERSION' ) || class_exists( 'WordPress\AI\Main', false );

		if( ! $is_ai_plugin_active ){
			return false;
		}

		if( ! current_user_can( 'manage_options' ) ){
			return false;
		}

		$is_enabled = (bool) get_option( 'wpai_features_enabled', false ) && (bool) get_option( 'wpai_feature_abilities-explorer_enabled', false );

		return (bool) apply_filters( 'wpwhpro/abilities/is_explorer_available', $is_enabled );
	}

	/**
	 * Return a stable transient key for mapped schemas.
	 *
	 * @param array $action
	 *
	 * @return string
	 */
	private function get_schema_cache_key( $action ) {
		$version = defined( 'WPWH_VERSION' ) ? WPWH_VERSION : WPWHPRO_VERSION;
		$hash    = md5( wp_json_encode( array(
			'version'     => $version,
			'cache'       => self::SCHEMA_CACHE_VERSION,
			'integrations'=> $this->get_active_integrations_hash(),
			'integration' => isset( $action['integration'] ) ? $action['integration'] : '',
			'action'      => isset( $action['action'] ) ? $action['action'] : '',
			'parameters'  => isset( $action['parameter'] ) ? $action['parameter'] : array(),
			'returns'     => isset( $action['returns'] ) ? $action['returns'] : array(),
			'returns_code'=> isset( $action['returns_code'] ) ? $action['returns_code'] : array(),
		) ) );

		return 'wpwhpro_ability_schema_' . $hash;
	}

	/**
	 * Return a stable hash for currently loaded integrations.
	 *
	 * @return string
	 */
	private function get_active_integrations_hash() {
		$integrations = WPWHPRO()->integrations->get_integrations();

		if( ! is_array( $integrations ) ){
			return '';
		}

		return md5( wp_json_encode( array_keys( $integrations ) ) );
	}

	/**
	 * Clear cached converted schemas.
	 *
	 * @return void
	 */
	private function clear_schema_cache() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpwhpro_ability_schema_%' OR option_name LIKE '_transient_timeout_wpwhpro_ability_schema_%'" );
	}

}
