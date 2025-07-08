<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP_Webhooks_Integrations_paid_member_subscriptions Class
 *
 * This class integrates all Paid Member Subscriptions related features and endpoints
 *
 */
class WP_Webhooks_Integrations_paid_member_subscriptions {

    // PHP 8.2 compatibility requires the declaration of all properties
    public $details;
    public $helpers;
    public $auth;
    public $actions;
    public $triggers;

    public function is_active(){
        return defined( 'PMS_VERSION' );
    }

    public function get_details(){
        $integration_url = plugin_dir_url( __FILE__ );

        return array(
            'name' => 'Paid Member Subscriptions',
            'icon' => $integration_url . 'assets/img/icon-paid-member-subscriptions.svg',
        );
    }

}
