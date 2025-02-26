<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP_Webhooks_Integrations_learndash Class
 *
 * This class integrates all LearnDash related features and endpoints
 *
 * @since 4.3.1
 */
class WP_Webhooks_Integrations_learndash {

    // PHP 8.2 compatibility requires the declaration of all properties
    public $details;
    public $helpers;
    public $auth;
    public $actions;
    public $triggers;

    public function is_active(){
        return defined( 'LEARNDASH_VERSION' );
    }

    public function get_details(){
        $integration_url = plugin_dir_url( __FILE__ );

        return array(
            'name' => 'LearnDash',
            'icon' => $integration_url . '/assets/img/icon-learndash.png',
        );
    }

}
