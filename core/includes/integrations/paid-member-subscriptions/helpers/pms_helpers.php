<?php

if ( ! class_exists( 'WP_Webhooks_Integrations_paid_member_subscriptions_Helpers_pms_helpers' ) ) :

	/**
	 * Load the Paid Member Subscriptions helpers
	 *
	 */
	class WP_Webhooks_Integrations_paid_member_subscriptions_Helpers_pms_helpers {

        /**
         * Get the settings for Member Subscription related triggers
         * - should always return an array
         *
         * @return array
         */
        public function get_subscription_settings() {
            $settings = array();
            $features = $this->get_subscription_features();

            if ( !empty( $features ) ) {
                $settings['wpwhpro_pms_subscription_feature'] = array(
                    'id'		  => 'wpwhpro_pms_subscription_feature',
                    'type'		=> 'select',
                    'multiple'	=> false,
                    'choices'	  => array( '0' => 'Choose...' ) + $features,
                    'label'	   => __( 'Subscription feature', 'wp-webhooks' ),
                    'placeholder' => '',
                    'required'	=> false,
                    'description' => __( 'Fire this trigger only when the selected feature is enabled on the Member Subscription. If no feature is selected, this condition will be ignored and the trigger will fire normally.', 'wp-webhooks' ),
                );
            }

            return $settings;
        }


        /**
         * Get the Subscription Plan features
         * - check for Add-on activation before adding the option to the list
         *
         * @return array
         */
        private function get_subscription_features() {

            if ( defined( 'PMS_IN_GM_PLUGIN_DIR_PATH' ) )
                $subscription_types['group'] = __( 'Group Subscription', 'wp-webhooks' );

            if ( defined( 'PMS_IN_PWYW_PLUGIN_DIR_PATH' ) )
                $subscription_types['pwyw'] = __( 'Pay What You Want', 'wp-webhooks' );

            if ( defined( 'PMS_IN_MSFP_PLUGIN_DIR_PATH' ) )
                $subscription_types['fixed_period'] = __( 'Fixed Period', 'wp-webhooks' );

            $subscription_types['payment_cycles'] = __( 'Payment Cycles', 'wp-webhooks' );

            return $subscription_types;
        }


        /**
         * Verify if the Subscription Plan feature is enabled
         *
         * @param $feature - the selected feature in webhook trigger settings
         * @param $subscription_plan_id - the ID of the Subscription Plan
         *
         * @return bool
         */
        public function validate_subscription_feature( $feature, $subscription_plan_id ) {
            $subscription_plan = pms_get_subscription_plan( $subscription_plan_id );

            switch ( $feature ) {
                case 'group':
                    $is_valid = $subscription_plan->type == 'group';
                    break;

                case 'pwyw':
                    $is_valid = function_exists( 'pms_in_pwyw_pricing_enabled' ) && pms_in_pwyw_pricing_enabled( $subscription_plan_id );
                    break;

                case 'fixed_period':
                    $is_valid = $subscription_plan->is_fixed_period_membership();
                    break;

                case 'payment_cycles':
                    $is_valid = $subscription_plan->has_installments();
                    break;

                default:
                    $is_valid = true;
                    break;
            }

            return $is_valid;
        }


	}

endif; // End if class_exists check.