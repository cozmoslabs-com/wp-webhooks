<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP_Webhooks_Integrations_ninjaforms Class
 *
 * This class integrates all Ninja Forms related features and endpoints
 *
 * @since 4.2.1
 */
class WP_Webhooks_Integrations_ninjaforms {

    // PHP 8.2 compatibility requires the declaration of all properties
    public $details;
    public $helpers;
    public $auth;
    public $actions;
    public $triggers;

    public function is_active(){
        return class_exists( 'Ninja_Forms' );
    }

    public function get_details(){
        $integration_url = plugin_dir_url( __FILE__ );

        return array(
            'name' => 'Ninja Forms',
            'icon' => $integration_url . '/assets/img/icon-ninjaforms.png',
        );
    }

}
