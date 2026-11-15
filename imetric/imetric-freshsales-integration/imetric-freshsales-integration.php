<?php
/*
Plugin Name: Imetric Freshsales Integration
Plugin URI: https://builtmighty.com
Description: Integration for Imetric's Website and Freshsales
Version: 1.0.1
Author: Built Mighty
Author URI: https://builtmighty.com
Copyright: Built Mighty
Text Domain: imetric-freshsales-integration
Requires Plugins: 
Copyright © 2025 Built Mighty. All Rights Reserved.

*/

/**
 * Namespace.
 *
 * @since   1.0.0
 */
namespace ImetricFreshsalesIntegration;

/**
 * Disallow direct access.
 * 
 * @since   1.0.0
 */
if( ! defined( 'WPINC' ) ) { die; }

/**
 * Constants.
 *
 * @since   1.0.0
 */
define( 'IMFRESHI_VERSION', '1.0.1' );
define( 'IMFRESHI_NAME', 'imetric-freshsales-integration' );
define( 'IMFRESHI_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'IMFRESHI_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * On activation.
 *
 * @since   1.0.0
 */
register_activation_hook( __FILE__, '\ImetricFreshsalesIntegration\activation' );
function activation() {

    // Flush rewrite rules.
    flush_rewrite_rules();

}

/**
 * On deactivation.
 *
 * @since   1.0.0
 */
register_deactivation_hook( __FILE__, '\ImetricFreshsalesIntegration\deactivation' );
function deactivation() {

    // Flush rewrite rules.
    flush_rewrite_rules();

}

/**
 * Load.
 *
 * @since   1.0.1
 */
add_action( 'plugins_loaded', '\ImetricFreshsalesIntegration\load' );
function load() {

    /**
     * Settings.
     * 
     * @since   1.0.0
     */
    require_once IMFRESHI_PATH . 'includes/class-global-settings.php';

    /**
     * Utilities.
     * 
     * @since   1.0.0
     */
    require_once IMFRESHI_PATH . 'utilities/class-api.php';
    require_once IMFRESHI_PATH . 'utilities/class-controller.php';
    require_once IMFRESHI_PATH . 'utilities/class-cpt.php';
    if( class_exists( 'WC_Email' ) ) require_once IMFRESHI_PATH . 'utilities/class-email.php';

    /**
     * Require classes.
     *
     * @since   1.0.0
     */
    require_once IMFRESHI_PATH . 'init.php';
    require_once IMFRESHI_PATH . 'public/class-public.php';
    require_once IMFRESHI_PATH . 'private/class-private.php';

    /**
     * Initiate.
     *
     * @since   1.0.0
     */
    \ImetricFreshsalesIntegration\Plugin::get_instance();

}
