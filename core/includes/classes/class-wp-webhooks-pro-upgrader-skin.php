<?php

class WP_Webhooks_Upgrader_Skin extends WP_Upgrader_Skin {

  public $plugin = '';
  public $plugin_active = false;
  public $plugin_network_active = false;
  public $messages = array();

  /**
   *
   * @param array $args
   */
  public function __construct( $args = array() ) {

    $defaults = array( 'url' => '', 'plugin' => '', 'nonce' => '', 'title' => __('Update Plugin') );
    $args = wp_parse_args($args, $defaults);

    $this->plugin = $args['plugin'];

    $this->plugin_active = is_plugin_active( $this->plugin );
    $this->plugin_network_active = is_plugin_active_for_network( $this->plugin );

    parent::__construct($args);
  }

  /**
   * @access public
   */
  public function after() { }
  public function header() { }
  public function footer() { }

  public function feedback( $feedback, ...$args ) {
    if ( isset( $this->upgrader->strings[$feedback] ) )
      $feedback = $this->upgrader->strings[$feedback];

    if ( strpos($feedback, '%') !== false ) {
      if ( $args ) {
        $args = array_map( 'strip_tags', $args );
        $args = array_map( 'esc_html', $args );
        $feedback = vsprintf($feedback, $args);
      }
    }
    if ( empty($feedback) )
      return;

    $this->messages[] = $feedback;
  }
}