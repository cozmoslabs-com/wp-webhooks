<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP_Webhooks_Integrations_amelia Class
 *
 * This class integrates all Amelia related features and endpoints
 *
 * @since 4.3.2
 */
class WP_Webhooks_Integrations_amelia {

    // PHP 8.2 compatibility requires the declaration of all properties
    public $details;
    public $helpers;
    public $auth;
    public $actions;
    public $triggers;

    public function is_active(){
        return defined( 'AMELIA_VERSION' );
    }

    public function get_details(){
        $integration_url = plugin_dir_url( __FILE__ );

        return array(
            'name' => 'Amelia',
            'icon' => $integration_url . '/assets/img/icon-amelia.png',
        );
    }

}
