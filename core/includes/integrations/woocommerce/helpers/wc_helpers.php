<?php

if ( ! class_exists( 'WP_Webhooks_Integrations_woocommerce_Helpers_wc_helpers' ) ) :

	/**
	 * Load the Woocommerce helpers
	 *
	 * @since 4.3.2
	 * @author Ironikus <info@ironikus.com>
	 */
	class WP_Webhooks_Integrations_woocommerce_Helpers_wc_helpers {

		/**
		 * Get all Woocommerce webhook API versions
		 *
		 * @return array A list of the available types 
		 */
		public function get_wc_api_versions(){

			$versions = array();

			if( function_exists( 'wc_get_webhook_rest_api_versions' ) ){
				$versions = wc_get_webhook_rest_api_versions();
			} else {
				$versions = array(
					'wp_api_v1',
					'wp_api_v2',
					'wp_api_v3',
				);
			}

			$validated_versions = array();
			foreach( $versions as $version ){
				$validated_versions[ $version ] = esc_html( sprintf( WPWHPRO()->helpers->translate( 'WP REST API v%d', 'trigger-wc_helpers-get_types' ), str_replace( 'wp_api_v', '', $version ) ) );
			}

			return apply_filters( 'wpwhpro/webhooks/wc_helpers/get_wc_api_versions', $validated_versions );
		}

		/**
		 * Get an array of assigned taxonomies for a given post
		 *
		 * @param int $post_id
		 * @since 4.3.3
		 * @return array
		 */
		public function get_validated_taxonomies( $post_id ){
			
			$tax_output = array();

			if( ! empty( $post_id ) ){
				$tax_output = array();
                $taxonomies = get_taxonomies( array(),'names' );
                if( ! empty( $taxonomies ) ){
                    $tax_terms = wp_get_post_terms( $post_id, $taxonomies );
                    foreach( $tax_terms as $sk => $sv ){

                        if( ! isset( $sv->taxonomy ) || ! isset( $sv->slug ) ){
                            continue;
                        }

                        if( ! isset( $tax_output[ $sv->taxonomy ] ) ){
                            $tax_output[ $sv->taxonomy ] = array();
                        }

                        if( ! isset( $tax_output[ $sv->taxonomy ][ $sv->slug ] ) ){
                            $tax_output[ $sv->taxonomy ][ $sv->slug ] = array();
                        }

                        $tax_output[ $sv->taxonomy ][ $sv->slug ] = $sv;

                    }
                }
			}

			return $tax_output;
		}

        /**
         * Request and build payload data
         *
         * @param $resource_id
         * @param $webhook_settings
         * @param $topic
         * @return array|mixed
         */
        public function build_payload( $resource_id, $webhook_settings, $topic ) {

            if ( empty( $resource_id ) )
                return array(
                    'message' => esc_html__( 'Resource ID is either missing or invalid.', 'wp-webhooks' ),
                    'data' => array(
                        'status' => 404,
                    ),
                );

            // get the authentication data
            $authentication_data = isset( $webhook_settings['wpwhpro_trigger_authentication'] ) ? $this->get_authentication_template_data( $webhook_settings['wpwhpro_trigger_authentication'] ) : false;

            // make request if authentication data is valid
            if ( $authentication_data && $authentication_data['auth_type'] == 'api_key' ) {

                // get the resource type and event
                $topic_data = !empty( $topic ) ? explode( '.', $topic ) : array();
                $resource_type = isset( $topic_data[0] ) ? $topic_data[0] : '';
//                $event = isset( $topic_data[1] ) ? $topic_data[1] : '';

                // get Woo Api Consumer Key and Secret
                $consumer_key = isset( $authentication_data['data']['wpwhpro_auth_api_key_key'] ) ? $authentication_data['data']['wpwhpro_auth_api_key_key'] : '';
                $consumer_secret = isset( $authentication_data['data']['wpwhpro_auth_api_key_value'] ) ? $authentication_data['data']['wpwhpro_auth_api_key_value'] : '';

                $response = wp_remote_get( rest_url( 'wc/v3/'. $resource_type .'s/' . $resource_id ), [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode( $consumer_key . ':' . $consumer_secret ),
                    ]
                ] );

                $payload = json_decode( wp_remote_retrieve_body( $response ), true );

                // remove unnecessary data
                unset( $payload['meta_data'] );
                unset( $payload['_links']['self'][0]['targetHints'] );
                unset( $payload['_links']['email_templates'] );

            }
            else {
                $payload = array(
                    'message' => esc_html__( 'Authentication template is either missing or invalid.', 'wp-webhooks' ),
                    'data' => array(
                        'status' => 404,
                    ),
                );
            }

            // Append additional data
            $payload['wpwh_meta_data'] = get_post_meta( $resource_id );
            $payload['wpwh_tax_data']  = $this->get_validated_taxonomies( $resource_id );

            return $payload;
        }

        /**
         * Get the Authentication Template data
         *
         * @param $template_id
         * @return array|false
         */
        public function get_authentication_template_data( $template_id ) {
            
            if ( empty( $template_id ) )
                return false;

            $template = WPWHPRO()->auth->get_auth_templates( $template_id );

            if( ! empty( $template ) && ! empty( $template->template ) && ! empty( $template->auth_type ) ){

                $sub_template_data = base64_decode( $template->template );

                if( ! empty( $sub_template_data ) && WPWHPRO()->helpers->is_json( $sub_template_data ) ){

                    $template_data = json_decode( $sub_template_data, true );

                    if( ! empty( $template_data ) ){

                        $authentication_data = array(
                            'auth_type' => $template->auth_type,
                            'data' => $template_data
                        );

                    }

                }

            }

            return $authentication_data;
            
        }

	}

endif; // End if class_exists check.