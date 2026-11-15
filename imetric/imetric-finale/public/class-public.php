<?php
/**
 * Public.
 * 
 * @since   1.0.0
 */

// Namespace.
namespace BuiltMighty\IMETF;
class imetfPublic {

    /**
     * Plugin name.
     */
    private $plugin_name;

    /**
     * Plugin version.
     */
    private $plugin_version;

    /**
     * Construct.
     * 
     * @since   1.0.0
     * @param   string      $plugin_name        The name of the plugin.
     * @param   string      $plugin_version     The version of the plugin.
     */
    public function __construct( $plugin_name, $version ) {

        // Set plugin name.
        $this->plugin_name = $plugin_name;

        // Set plugin version.
        $this->plugin_version = $version;

    }

    /**
     * Enqueue styles.
     * 
     * @since   1.0.0
     */
    public function enqueue_styles() {

        // Styles.
        wp_enqueue_style( $this->plugin_name, IMETF_URI . 'public/assets/css/styles.css', [], $this->plugin_version, 'all' );

    }

    /**
     * Enqueue scripts.
     * 
     * @since   1.0.0
     */
    public function enqueue_scripts() {

        // Scripts.
        wp_enqueue_script( $this->plugin_name, IMETF_URI . 'public/assets/js/scripts.js', [], $this->plugin_version, false );

    }

}
