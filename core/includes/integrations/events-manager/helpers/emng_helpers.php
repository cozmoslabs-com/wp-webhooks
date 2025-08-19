<?php

if ( ! class_exists( 'WP_Webhooks_Integrations_events_manager_Helpers_emng_helpers' ) ) :

	/**
	 * Load the Events Manager helpers
	 *
	 * @since 4.3.6
	 * @author Ironikus <info@ironikus.com>
	 */
	class WP_Webhooks_Integrations_events_manager_Helpers_emng_helpers {

        public function get_events(){
            $validated_forms = array();
           
            if( defined( 'EM_POST_TYPE_EVENT' ) ){
                $forms = get_posts( array( 
					'post_type' => EM_POST_TYPE_EVENT,
					'posts_per_page' => -1,
					'numberposts' => -1,
				) );
				
				if( ! empty( $forms ) ){
					foreach( $forms as $form ){
						$validated_forms[ $form->ID ] = $form->post_title;
					}
				}
				
            }

            return $validated_forms;

        }
        
        public function transform_nested_object_to_array( $data, $seen = [] ){

            $keys_to_skip = array( 'fields', 'required_fields' );

            // Handle objects
            if (is_object($data)) {
                $hash = spl_object_hash($data);

                // Break circular references
                if (isset($seen[$hash])) {
                    return '';
                }
                $seen[$hash] = true;

                $data = get_object_vars($data);
            }

            // Handle arrays
            if (is_array($data)) {
                $result = [];
                foreach ($data as $key => $value) {
                    if( !in_array( $key, $keys_to_skip ) ){
                        $result[$key] = $this->transform_nested_object_to_array( $value, $seen );
                    }
                }
                return $result;
            }

            // Handle scalars
            return $data;


        }

	}

endif; // End if class_exists check.