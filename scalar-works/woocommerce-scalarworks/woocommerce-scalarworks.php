<?php
/*
Plugin Name: WooCommerce Scalarworks
Plugin URI: https://builtmighty.com
Description: A custom WooCommerce plugin for Scalarworks.
Version: 1.0.0
Author: Built Mighty
Author URI: https://builtmighty.com
Copyright: Built Mighty
Text Domain: woocommerce-scalarworks
Requires Plugins: 
Copyright © 2025 Built Mighty. All Rights Reserved.
*/

/**
 * Namespace.
 *
 * @since   1.0.0
 */
namespace WooCommerceScalarworks;

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
define( 'WOOSCALAR_VERSION', '1.0.0' );
define( 'WOOSCALAR_NAME', 'woocommerce-scalarworks' );
define( 'WOOSCALAR_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WOOSCALAR_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * On activation.
 *
 * @since   1.0.0
 */
register_activation_hook( __FILE__, '\WooCommerceScalarworks\activation' );
function activation() {

    // Flush rewrite rules.
    flush_rewrite_rules();

}

/**
 * On deactivation.
 *
 * @since   1.0.0
 */
register_deactivation_hook( __FILE__, '\WooCommerceScalarworks\deactivation' );
function deactivation() {

    // Flush rewrite rules.
    flush_rewrite_rules();

}

/**
 * Load.
 *
 * @since   1.0.0
 */
add_action( 'plugins_loaded', '\WooCommerceScalarworks\load' );
function load() {

    /**
     * Utilities.
     * 
     * @since   1.0.0
     */
    require_once WOOSCALAR_PATH . 'utilities/class-api.php';
    require_once WOOSCALAR_PATH . 'utilities/class-controller.php';
    require_once WOOSCALAR_PATH . 'utilities/class-cpt.php';
    if( class_exists( 'WC_Email' ) ) require_once WOOSCALAR_PATH . 'utilities/class-email.php';

    /**
     * Require classes.
     *
     * @since   1.0.0
     */
    require_once WOOSCALAR_PATH . 'init.php';
    require_once WOOSCALAR_PATH . 'public/class-public.php';
    require_once WOOSCALAR_PATH . 'public/class-attributes.php';
    require_once WOOSCALAR_PATH . 'public/class-faqs.php';
    require_once WOOSCALAR_PATH . 'public/class-products.php';
    require_once WOOSCALAR_PATH . 'public/class-order.php';
    require_once WOOSCALAR_PATH . 'private/class-private.php';

    /**
     * Initiate.
     *
     * @since   1.0.0
     */
    \WooCommerceScalarworks\Plugin::get_instance();

}