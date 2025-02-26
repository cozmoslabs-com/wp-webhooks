<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP_Webhooks_Integrations_affiliatewp Class
 *
 * This class integrates all AffiliateWP related features and endpoints
 *
 * @since 4.2.0
 */
class WP_Webhooks_Integrations_affiliatewp {

    // PHP 8.2 compatibility requires the declaration of all properties
    public $details;
    public $helpers;
    public $auth;
    public $actions;
    public $triggers;

    public function is_active(){
        return class_exists( 'Affiliate_WP' );
    }

    public function get_details(){
        $integration_url = plugin_dir_url( __FILE__ );

        return array(
            'name' => 'AffiliateWP',
            'icon' => $integration_url . '/assets/img/icon-affiliatewp.svg',
        );
    }

}
