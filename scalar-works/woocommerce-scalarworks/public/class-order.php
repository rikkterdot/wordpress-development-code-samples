<?php
/**
 * Order.
 *
 * Methods dealing with WooCommerce orders.
 *
 * @package WooCommerce Scalarworks
 * @since   1.0.0
 * @version 1.0.0
 */
namespace WooCommerceScalarworks\Public;
class order {

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Actions.
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'line_items' ], 10, 4 );
        add_action( 'woocommerce_order_status_processing', [ $this, 'payment_complete' ] );

    }

    /**
     * Line Items.
     * 
     * @param   object  $item
     * @param   string  $cart_item_key
     * @param   array   $values
     * @param   object  $order
     * 
     * @since   1.0.0
     */
    public function line_items( $item, $cart_item_key, $values, $order ) {

        // Check if set.
        if( ! isset( $values['attached_optic'] ) ) return;

        // Set price.
        $price = floatval( $values['data']->get_price() ) + floatval( $values['attached_optic']['price'] );

        // Set the subtotal and total.
        $item->set_subtotal( $price );
        $item->set_total( $price );
        
        // Loop.
        foreach( $values['attached_optic'] as $key => $data ) {

            // Check key.
            if( ! in_array( $key, [ 'id', 'name', 'sku', 'price' ] ) ) continue;

            // Set key.
            $key = ( in_array( $key, [ 'id', 'sku' ] ) ) ? strtoupper( $key ) : ucwords( $key );

            // Add meta data.
            $item->add_meta_data( 'Package ' . $key, $data, true );

        }

    }

    /**
     * Payment Complete.
     * 
     * @param   int     $order_id
     * 
     * @since   1.0.0
     */
    public function payment_complete( $order_id ) {

        // Get order.
        $order = wc_get_order( $order_id );

        // Check for an order.
        if( ! $order ) return;

        // Check for package.
        $packages = $this->has_package( $order );

        // Check for package.
        if( ! $packages ) return;

        // Loop through packages.
        foreach( $packages['packages'] as $package_id => $quantity ) {

            // Skip if exists in items.
            if( isset( $packages['items'][$package_id] ) ) continue;

            // Set package.
            $package = wc_get_product( $package_id );

            // Check for package.
            if( ! $package ) continue;

            // Add package to order.
            $order->add_product( $package, $quantity );
            $order->save();

            // Set stock.
            $package->set_stock( $package->get_stock_quantity() - $quantity );
            $package->save();

        }

        // Loop through line items and set package ID line item to $0.
        foreach( $order->get_items() as $item ) {

            // Check if item product ID is the same as the package ID.
            if( $item->get_product_id() !== $package_id ) continue;

            // Set price.
            $item->set_subtotal( 0 );
            $item->set_total( 0 );
            $item->save();

        }

        // Save order.
        $order->save();

    }

    /**
     * Has package.
     * 
     * @param   object  $order
     * 
     * @since   1.0.0
     */
    public function has_package( $order ) {

        // Check for order.
        if( ! $order ) return false;

        // Set packages.
        $packages = [];

        // Loop through items and check for package.
        foreach( $order->get_items() as $item ) {

            // Add to packages.
            $packages['items'][$item->get_product_id()] = $item->get_quantity();

            // Check for package.
            if( ! $item->get_meta( 'Package ID' ) ) continue;

            // Add to packages.
            $packages['packages'][$item->get_meta( 'Package ID' )] = $item->get_quantity();

        }

        // Return.
        return ( ! empty( $packages ) ) ? $packages : false;

    }

}