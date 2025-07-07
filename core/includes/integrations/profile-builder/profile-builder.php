<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP_Webhooks_Integrations_profile_builder Class
 *
 * This class integrates all Profile Builder related features and endpoints
 *
 * @since 6.1.5
 */
class WP_Webhooks_Integrations_profile_builder {

    // PHP 8.2 compatibility requires the declaration of all properties
    public $details;
    public $helpers;
    public $auth;
    public $actions;
    public $triggers;

    public function is_active(){
        return defined( 'PROFILE_BUILDER_VERSION' );
    }

    public function get_details(){
        $integration_url = plugin_dir_url( __FILE__ );

        return array(
            'name' => 'Profile Builder',
            'icon' => $integration_url . 'assets/img/icon-profile-builder.svg',
        );
    }

}
