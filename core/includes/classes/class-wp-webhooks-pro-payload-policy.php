<?php

/**
 * WP_Webhooks_Pro_Payload_Policy Class
 *
 * Removes selected user fields from an outgoing trigger payload
 * - a safety/privacy control so credentials and sensitive user data can be kept from leaving the site
 * - matches by field key name anywhere in the payload, so it works for any trigger regardless of structure
 *
 */

/**
 * The payload policy class of the plugin
 *
 * @package WPWHPRO
 * @author Ironikus <info@ironikus.com>
 */
class WP_Webhooks_Pro_Payload_Policy {

	/**
	 * WP_Webhooks_Pro_Payload_Policy constructor
	 */
	public function __construct(){
		$this->add_hooks();
	}

	/**
	 * The main function for adding our WordPress related hooks
	 *
	 * @return void
	 */
	public function add_hooks(){

		// run before the authentication handler (priority 100) injects auth data into the payload
		add_filter( 'wpwhpro/admin/webhooks/webhook_data', array( $this, 'apply_to_trigger_payload' ), 10, 3 );

	}

	/**
	 * ################################
	 * ###
	 * ##### CORE LOGIC
	 * ###
	 * ################################
	 */

	/**
	 * Apply the saved field policy to an outgoing trigger payload
	 *
	 * - a missing/"send all" policy is a no-op, so legacy webhooks are never altered
	 * - removes any key whose name matches a selected field or pattern, at any depth
	 *
	 * @param mixed $data - the outgoing payload
	 * @param mixed $response - the response bucket passed along the filter (unused)
	 * @param array $webhook - the webhook configuration, including its settings
	 * @return mixed - the filtered payload
	 */
	public function apply_to_trigger_payload( $data, $response = array(), $webhook = array() ){

		// nothing to walk - leave non-array or empty payloads untouched
		if( ! is_array( $data ) || empty( $data ) ){
			return $data;
		}

		// no per-webhook settings means no policy to apply (legacy webhooks land here)
		if( ! is_array( $webhook ) || empty( $webhook['settings'] ) || ! is_array( $webhook['settings'] ) ){
			return $data;
		}

		$settings = $webhook['settings'];
		$mode = isset( $settings['wpwhpro_trigger_payload_policy_mode'] ) ? $settings['wpwhpro_trigger_payload_policy_mode'] : '';

		// some storage paths may wrap the value in an array
		if( is_array( $mode ) ){
			$mode = reset( $mode );
		}

		$mode = is_string( $mode ) ? trim( $mode ) : '';

		// send all is the default and a no-op, so only "remove selected" does any work
		if( $mode !== 'remove_selected' ){
			return $data;
		}

		// resolve the configured field names/patterns, then bail if the policy is effectively empty
		$keys = $this->get_policy_keys( $settings );

		if( empty( $keys['exact'] ) && empty( $keys['wildcards'] ) ){
			return $data;
		}

		return $this->remove_keys( $data, $keys['exact'], $keys['wildcards'] );
	}

	/**
	 * Collect the field keys a policy removes
	 *
	 * - unions the explicitly selected fields with the manual/wildcard keys
	 * - splits them into an exact-match lookup and a list of wildcard patterns
	 *
	 * @param array $settings - the webhook settings
	 * @return array - array( 'exact' => array( key => true ), 'wildcards' => array( pattern ) )
	 */
	private function get_policy_keys( $settings ){

		// gather every configured key from both inputs before splitting them by type
		$collected_keys = array();

		// the "User data to remove" multiselect - normally an array of selected field names
		if( isset( $settings['wpwhpro_trigger_payload_policy_fields'] ) ){
			$fields = $settings['wpwhpro_trigger_payload_policy_fields'];

			if( is_array( $fields ) ){

				foreach( $fields as $field ){
					$field = trim( (string) $field );

					if( $field !== '' ){
						$collected_keys[] = $field;
					}
				}

			} elseif( is_string( $fields ) && trim( $fields ) !== '' ){

				// a single stored value can arrive as a plain string
				$collected_keys[] = trim( $fields );
			}
		}

		// the "Custom keys & patterns" textarea - free text split on commas and new lines
		if( isset( $settings['wpwhpro_trigger_payload_policy_patterns'] ) && is_string( $settings['wpwhpro_trigger_payload_policy_patterns'] ) ){
			$parts = preg_split( '/[\r\n,]+/', $settings['wpwhpro_trigger_payload_policy_patterns'] );

			foreach( $parts as $part ){
				$part = trim( $part );

				if( $part !== '' ){
					$collected_keys[] = $part;
				}
			}
		}

		// split into two buckets: exact names use a fast isset() lookup, wildcards need regex
		$exact = array();
		$wildcards = array();

		foreach( array_unique( $collected_keys ) as $entry ){

			// a "*" anywhere marks a wildcard pattern, everything else is an exact key name
			if( strpos( $entry, '*' ) !== false ){
				$wildcards[] = $entry;
			} else {
				// keyed by name so remove_keys() can test membership with isset()
				$exact[ $entry ] = true;
			}

		}

		return array( 'exact' => $exact, 'wildcards' => $wildcards );
	}

	/**
	 * Remove every key whose name matches, anywhere in the payload
	 *
	 * - traverses arrays and objects consistently
	 * - a matched key is dropped with its whole subtree; unmatched branches are recursed into
	 *
	 * @param mixed $data - the current payload node
	 * @param array $exact - an exact-match lookup ( key => true )
	 * @param array $wildcards - a list of wildcard patterns
	 * @return mixed - the node with matched keys removed
	 */
	private function remove_keys( $data, $exact, $wildcards ){

		// arrays and objects are handled the same way, only the access syntax differs
		if( is_array( $data ) ){

			// iterate over a snapshot of the keys so unsetting inside the loop is safe
			foreach( array_keys( $data ) as $key ){

				// a matched key is dropped with its whole subtree, so we do not recurse into it
				if( isset( $exact[ $key ] ) || ( ! empty( $wildcards ) && $this->key_matches_wildcard( $key, $wildcards ) ) ){
					unset( $data[ $key ] );
					continue;
				}

				// otherwise keep walking nested arrays/objects to catch matches at any depth
				if( is_array( $data[ $key ] ) || is_object( $data[ $key ] ) ){
					$data[ $key ] = $this->remove_keys( $data[ $key ], $exact, $wildcards );
				}

			}

		} elseif( is_object( $data ) ){

			// same logic as the array branch, using object property access
			foreach( array_keys( get_object_vars( $data ) ) as $key ){

				if( isset( $exact[ $key ] ) || ( ! empty( $wildcards ) && $this->key_matches_wildcard( $key, $wildcards ) ) ){
					unset( $data->{$key} );
					continue;
				}

				if( is_array( $data->{$key} ) || is_object( $data->{$key} ) ){
					$data->{$key} = $this->remove_keys( $data->{$key}, $exact, $wildcards );
				}

			}

		}

		return $data;
	}

	/**
	 * Check whether a key matches any of the wildcard patterns
	 *
	 * - "*" matches any run of characters
	 *
	 * @param string $key - the concrete key from the payload
	 * @param array $wildcards - the wildcard patterns
	 * @return bool - whether the key matches a pattern
	 */
	private function key_matches_wildcard( $key, $wildcards ){

		foreach( $wildcards as $pattern ){

			// escape the pattern for regex, then turn the escaped "*" back into ".*" to match any run of characters
			$regex = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';

			if( preg_match( $regex, (string) $key ) ){
				return true;
			}
		}

		return false;
	}

	/**
	 * ################################
	 * ###
	 * ##### NEW WEBHOOK SEEDING
	 * ###
	 * ################################
	 */

	/**
	 * The recommended safety-net patterns a freshly created webhook removes by default
	 *
	 * - wildcard families and non-stored keys that cannot be concrete options in the field list
	 * - kept in the patterns box so 2FA key families and future/rotating keys stay covered, and so they round-trip a re-save
	 *
	 * @return array - the pattern strings
	 */
	public function get_recommended_patterns(){

		// wildcard families + a non-stored key - these cannot be concrete options, so they live in the patterns box
		return apply_filters( 'wpwhpro/admin/payload_policy/recommended_patterns', array(
			'user_pass_raw',
			'_two_factor_*',
			'wp_2fa_*',
		) );
	}

	/**
	 * Build the policy settings a newly created webhook is seeded with
	 *
	 * - pre-selects every sensitive-flagged key that currently exists on the site, so the removal is visible in the field list
	 * - adds the wildcard/non-stored safety-net patterns for key families and keys not present in the list
	 * - only new webhooks are seeded; existing records and the save handler are never backfilled
	 *
	 * @return array - the settings to store on the new webhook
	 */
	public function get_seed_settings(){

		// pre-select every sensitive-flagged key that actually exists on this site
		// - drawn from the same source as the field list, so each seeded field is a real, selectable option
		$fields = array();

		foreach( $this->get_field_keys() as $key ){
			$key = (string) $key;

			if( $key !== '' && $this->is_sensitive_key( $key ) ){
				$fields[] = $key;
			}
		}

		// let integrations/custom code adjust the pre-selected set
		$fields = apply_filters( 'wpwhpro/admin/payload_policy/seed_fields', array_values( array_unique( $fields ) ) );

		if( ! is_array( $fields ) ){
			$fields = array();
		}

		// the textarea stores one entry per line, so join the safety-net patterns with new lines
		$patterns = $this->get_recommended_patterns();

		return array(
			'wpwhpro_trigger_payload_policy_mode'     => 'remove_selected',
			'wpwhpro_trigger_payload_policy_fields'   => $fields,
			'wpwhpro_trigger_payload_policy_patterns' => implode( "\n", $patterns ),
		);
	}

	/**
	 * ################################
	 * ###
	 * ##### FIELD LIST
	 * ###
	 * ################################
	 */

	/**
	 * Build the flat list of selectable field keys read live from the site
	 *
	 * - user table columns plus every distinct user meta key, de-duped
	 * - the single source both the field list and the seeding logic draw from, so a seeded key is always a real option
	 *
	 * @param string $trigger_slug - the trigger the list is built for (passed to the filter)
	 * @return array - the flat list of field keys
	 */
	private function get_field_keys( $trigger_slug = '' ){

		// the full universe of user keys: table columns + every distinct user meta key, de-duped
		$columns = $this->get_user_columns();
		$meta_keys = $this->get_user_meta_keys();

		$keys = array_values( array_unique( array_merge( $columns, $meta_keys ) ) );

		// let integrations and custom code add or remove keys from the list
		$keys = apply_filters( 'wpwhpro/admin/payload_policy/field_keys', $keys, $trigger_slug );

		if( ! is_array( $keys ) ){
			$keys = array();
		}

		return $keys;
	}

	/**
	 * Build the selectable field choices, ready for the settings renderer
	 *
	 * - one universal list read live from the site, identical for every trigger
	 * - grouped into user table columns and user meta keys, with a marker on sensitive names
	 *
	 * @param string $trigger_slug - the trigger the choices are built for (passed to the filter)
	 * @return array - grouped choices: group_key => array( 'group_label', 'options' => array( key => array( 'label', 'title' ) ) )
	 */
	public function get_field_choices( $trigger_slug = '' ){

		$keys = $this->get_field_keys( $trigger_slug );

		// flip the meta keys into a lookup so each key can be sorted into the right optgroup in O(1)
		$meta_lookup = array_flip( $this->get_user_meta_keys() );

		$labels = array(
			'fields' => WPWHPRO()->helpers->translate( 'User fields', 'wpwhpro-fields-trigger-required-settings' ),
			'meta'   => WPWHPRO()->helpers->translate( 'User meta', 'wpwhpro-fields-trigger-required-settings' ),
		);

		$groups = array();

		foreach( $keys as $key ){
			$key = (string) $key;

			if( $key === '' ){
				continue;
			}

			// meta keys go under "User meta", everything else (columns/custom) under "User fields"
			$group_key = isset( $meta_lookup[ $key ] ) ? 'meta' : 'fields';

			// prefix a warning marker on sensitive-looking names to steer the admin's selection
			$label = $this->is_sensitive_key( $key ) ? '⚠ ' . $key : $key;

			// create the optgroup lazily the first time a key lands in it
			if( ! isset( $groups[ $group_key ] ) ){
				$groups[ $group_key ] = array(
					'group_label' => $labels[ $group_key ],
					'options'     => array(),
				);
			}

			// title keeps the bare key name so the ⚠ marker never leaks into the stored/submitted value
			$groups[ $group_key ]['options'][ $key ] = array( 'label' => $label, 'title' => $key );
		}

		return $groups;
	}

	/**
	 * Return the WordPress user table columns
	 *
	 * - queried live so multisite (spam/deleted) and any custom columns are covered
	 * - cached per request; falls back to the standard core columns if the lookup fails
	 *
	 * @return array - the wp_users column names
	 */
	private function get_user_columns(){
		global $wpdb;

		// cache for the whole request - the schema does not change between calls in one load
		static $columns = null;

		if( $columns === null ){

			// read the live schema so custom/multisite columns are covered, not a hard-coded set
			$columns = $wpdb->get_col( "DESCRIBE {$wpdb->users}" );

			// fall back to the standard core columns if the lookup fails
			if( ! is_array( $columns ) || empty( $columns ) ){
				$columns = array(
					'ID',
					'user_login',
					'user_pass',
					'user_nicename',
					'user_email',
					'user_url',
					'user_registered',
					'user_activation_key',
					'user_status',
					'display_name',
				);
			}
		}

		return $columns;
	}

	/**
	 * Return the distinct user meta keys present on the current site
	 *
	 * - cached per request so repeated calls in one render hit the database only once, while staying fresh across page loads
	 *
	 * @return array - the list of user meta keys
	 */
	private function get_user_meta_keys(){
		global $wpdb;

		// cache for the whole request so a page that renders many triggers queries this only once
		static $meta_keys = null;

		if( $meta_keys === null ){

			// distinct keys only - one row per key name, whatever plugins have stored
			$meta_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} ORDER BY meta_key ASC" );

			if( ! is_array( $meta_keys ) ){
				$meta_keys = array();
			}

		}

		return $meta_keys;
	}

	/**
	 * Determine whether a field key looks sensitive based on its name
	 *
	 * - a heuristic used only to flag options in the UI; it removes nothing on its own
	 *
	 * @param string $key - the field key to check
	 * @return bool - whether the key matches a sensitive name pattern
	 */
	public function is_sensitive_key( $key ){

		// substrings that mark a key as sensitive - matched case-insensitively, anywhere in the name
		$patterns = apply_filters( 'wpwhpro/admin/payload_policy/sensitive_patterns', array(
			'password',
			'pass',
			'secret',
			'token',
			'session',
			'otp',
			'totp',
			'2fa',
			'two_factor',
			'recovery',
			'api_key',
			'activation_key',
		) );

		$haystack = strtolower( (string) $key );

		// flag on the first substring hit (e.g. "session" matches "session_tokens")
		foreach( $patterns as $pattern ){
			if( strpos( $haystack, strtolower( $pattern ) ) !== false ){
				return true;
			}
		}

		return false;
	}

}
