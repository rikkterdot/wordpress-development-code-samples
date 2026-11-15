<?php
/*
Plugin Name: Imetric Finale Connection
Plugin URI: https://builtmighty.com
Description: A plugin to connect Imetric with Finale.
Version: 1.0.0
Author: Built Mighty
Author URI: https://builtmighty.com
Copyright: Built Mighty
Text Domain: imetr_finale
Copyright © 2023 Built Mighty. All Rights Reserved.
*/

/**
 * Disallow direct access.
 */
if( !defined( 'WPINC' ) ) { die; }

/**
 * Constants.
 */
define( 'IMETF_VERSION', '1.0.0' );
define( 'IMETF_NAME', 'imetr_finale' );
define( 'IMETF_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'IMETF_URI', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'IMETF_DOMAIN', 'imetr_finale' );

/**
 * Autoload.
 */
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

/** 
 * On activation.
 */
register_activation_hook( __FILE__, 'imetf_activation' );
function imetf_activation() {


    //Add CRON job
    if (!wp_next_scheduled('imetric_cron_job')) {
        wp_schedule_event(time(), 'ten_minutes', 'imetric_cron_job');
    }

    // Flush rewrite rules.
    flush_rewrite_rules();

}

/**
 * On deactivation.
 */
register_deactivation_hook( __FILE__, 'imetf_deactivation' );
function imetf_deactivation() {

    //Remove CRON Job
    wp_clear_scheduled_hook('imetric_cron_job');

    // Flush rewrite rules.
    flush_rewrite_rules();

}

/**
 * Load plugin.
 * 
 * @since   1.0.0
 */
require IMETF_PATH . 'inc/class-plugin.php';

/**
 * Run plugin.
 * 
 * @since   1.0.0
 */
function run_imetf_plugin() {

    // Get plugin.
    $plugin = new \BuiltMighty\IMETF\imetfPlugin();

    // Run.
    $plugin->run();

}
run_imetf_plugin();
