<?php
/**
 * Initialize.
 * 
 * Load all of our classes using a Singleton pattern.
 * 
 * @package WooCommerce Scalarworks
 * @since   1.0.0
 */
namespace WooCommerceScalarworks;
class Plugin {

    /**
     * Set instance(s).
     * 
     * @since   1.0.0
     */
    private static $instance = null;
    private $instances = [];

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    private function __construct() {

        // Initiate classes.
        $this->init_classes();

    }

    /**
     * Get instance.
     * 
     * @since   1.0.0
     */
    public static function get_instance() {

        // Set instance.
        if( self::$instance === null ) {

            // Set.
            self::$instance = new self();
            
        }

        // Return.
        return self::$instance;

    }

    /**
     * Initiate classes.
     * 
     * @since   1.0.0
     */
    private function init_classes() {

        // Load classes.
        $this->load_class( \WooCommerceScalarworks\Public\core::class );
        $this->load_class( \WooCommerceScalarworks\Public\attributes::class );
        $this->load_class( \WooCommerceScalarworks\Public\faqs_cpt::class );
        $this->load_class( \WooCommerceScalarworks\Public\products::class );
        $this->load_class( \WooCommerceScalarworks\Public\order::class );
        $this->load_class( \WooCommerceScalarworks\Private\core::class );

    }

    /**
     * Load class.
     * 
     * @since   1.0.0
     * 
     * @param   string  $class
     */
    private function load_class( $class ) {

        // Check if class exists.
        if( ! isset( $this->instances[$class] ) ) {

            // Set instance.
            $this->instances[$class] = new $class();

        }
        
    }
    
}