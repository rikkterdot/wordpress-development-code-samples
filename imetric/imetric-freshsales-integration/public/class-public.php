<?php
/**
 * Public.
 *
 * The core.
 *
 * @package Imetric Freshsales Integration
 * @since   1.0.0
 * @version 1.0.0
 */
namespace ImetricFreshsalesIntegration\Public;
use ImetricFreshsalesIntegration\Utility\Freshsales;

class core {

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Enqueue.
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
        add_action( 'woocommerce_order_status_processing', [ $this, 'handle_order' ] );
        add_action( 'woocommerce_order_status_completed', [ $this, 'handle_order_completed' ] );

        require_once IMFRESHI_PATH . 'utilities/class-freshsales.php';
        $this->freshsales = new Freshsales();

    }

    /**
     * Enqueue.
     * 
     * @since   1.0.0
     */
    public function enqueue() {

        // Get asset version.
        $version = file_exists( IMFRESHI_PATH . 'public/mix-manifest.json' ) ? json_decode( file_get_contents( IMFRESHI_PATH . 'public/mix-manifest.json' ), true ) : [];

        // Set version.
        $css    = $version['/css/style.css'] ?? time();
        $js     = $version['/js/main.js'] ?? time();

        // CSS.
        wp_enqueue_style( 'imetric-freshsales-integration-css', IMFRESHI_URI . 'public/css/style.css', [], $css );

        // JS.
        wp_enqueue_script( 'imetric-freshsales-integration-js', IMFRESHI_URI . 'public/js/main.js', [ 'jquery' ], $js, true );

    }

    public function handle_order( $order_id ) {

        //Exit if there's no order ID
        if ( ! $order_id ) return;

        // Get the order
        $order = wc_get_order( $order_id );

        // Check if the order is valid
        if ( ! $order instanceof \WC_Order ) return;

        $this->freshsales->custom_log('[Freshsales Sync] Order placed! Order ID: ' . $order->get_id());

        try {
            $contact_id = $this->freshsales->get_or_create_contact( $order );
            if ( $contact_id ) {
                $this->freshsales->create_deal( $order, $contact_id );
                update_post_meta( $order_id, '_freshsales_synced', true );
            }
        } catch ( \Throwable $e ) {
            $this->freshsales->custom_log( '[Freshsales Sync] Exception: ' . $e->getMessage() );
        }
    }

    /**
     * Handles the WooCommerce order completion event.
     *
     * This method is triggered when an order is marked as completed in WooCommerce.
     * It performs the following actions:
     * - Logs the order completion event.
     * - Retrieves the Freshsales deal ID associated with the order.
     * - Fetches the stage ID for the "Shipped Awaiting Installation" stage in Freshsales.
     * - Updates the deal stage in Freshsales to "Shipped Awaiting Installation".
     * - Logs the response from the Freshsales API.
     *
     * @param int $order_id The ID of the completed WooCommerce order.
     *
     * @return void
     */
    public function handle_order_completed( $order_id ) {
        if ( ! $order_id ) return;
    
        $order = wc_get_order( $order_id );
        if ( ! $order instanceof \WC_Order ) return;
    
        $this->freshsales->custom_log('[Freshsales Sync] Order marked completed: ' . $order_id);
    
        // Get stored Freshsales deal ID
        $deal_id = get_post_meta( $order_id, '_freshsales_deal_id', true );
        if ( ! $deal_id ) {
            $this->freshsales->custom_log('[Freshsales Sync] No deal ID found for order ' . $order_id);
            return;
        }
    
        // Get the stage ID for "Shipped Awaiting Installation"
        $stage_id = $this->freshsales->get_stage_id_by_name('Shipped Awaiting Installation');
        if ( ! $stage_id ) {
            $this->freshsales->custom_log('[Freshsales Sync] Could not find stage: Shipped Awaiting Installation');
            return;
        }
    
        // Send update
        $response = $this->freshsales->update_deal_stage($deal_id, $stage_id);
    
        $this->freshsales->custom_log('[Freshsales Sync] Deal stage update response: ' . print_r($response, true));
    }
    
    

    
    
}
