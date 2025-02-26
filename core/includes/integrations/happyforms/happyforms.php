<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP_Webhooks_Integrations_happyforms Class
 *
 * This class integrates all HappyForms related features and endpoints
 *
 * @since 4.2.2
 */
class WP_Webhooks_Integrations_happyforms {

    // PHP 8.2 compatibility requires the declaration of all properties
    public $details;
    public $helpers;
    public $auth;
    public $actions;
    public $triggers;

    public function is_active(){
        return function_exists( 'HappyForms' );
    }

    public function get_details(){
        $integration_url = plugin_dir_url( __FILE__ );

        return array(
            'name' => 'HappyForms',
            'icon' => $integration_url . '/assets/img/icon-happyforms.png',
        );
    }

}
