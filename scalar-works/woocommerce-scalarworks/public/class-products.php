<?php
/**
 * Products.
 *
 * Methods dealing with products.
 *
 * @package WooCommerce Scalarworks
 * @since   1.0.0
 * @version 1.0.0
 */
namespace WooCommerceScalarworks\Public;
class products {

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Actions.
        add_action( 'template_redirect', [ $this, 'redirect_optics' ] );
        add_action( 'pre_get_posts', [ $this, 'exclude_optics' ] );
        add_action( 'parse_query', [ $this, 'optics_404' ] );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'calculate_totals' ], 10, 1 );
        add_action( 'woocommerce_after_calculate_totals', [ $this, 'calculate_totals' ], 10, 1 );

        // Filters.
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'cart_item_data' ], 10, 3 );
        add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'cart_item_session' ], 10, 2 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'show_cart_item_data' ], 10, 2 );
        add_filter( 'woocommerce_cart_subtotal', [ $this, 'cart_subtotal' ], 10, 3 );
        add_filter( 'woocommerce_calculated_total', [ $this, 'cart_total' ], 10, 2 );
        add_filter( 'woocommerce_cart_item_price', [ $this, 'cart_item_price' ], 10, 3 );
        add_filter( 'woocommerce_cart_item_subtotal', [ $this, 'cart_item_price_subtotal' ], 10, 3 );
        add_filter( 'woocommerce_get_price_excluding_tax', [ $this, 'adjust_get_price' ], 20, 3 );
        add_filter( 'woocommerce_get_price_including_tax', [ $this, 'adjust_get_price' ], 20, 3 );

    }

    /**
     * Redirect Optics.
     * 
     * @since   1.0.0
     */
    public function redirect_optics() {

        // Check if is product.
        if( ! is_singular( 'product' ) ) return;

        // Global.
        global $post;

        // Check if is optic.
        if( get_post_meta( $post->ID, '_is_optic', true ) !== 'yes' ) return;

        // Redirect.
        wp_redirect( home_url( '/' ) );
        exit;

    }

    /**
     * Exclude Optics.
     * 
     * @since   1.0.0
     */
    public function exclude_optics( $query ) {

        // Check.
        if( is_admin() && ! $query->is_main_query() || ! is_product_category() || ! is_product_tag() || ! is_search() ) return;

        // Set query.
        $meta_query = [
            [
                'key'     => '_is_optic',
                'value'   => 'yes',
                'compare' => '!=', // Exclude "Optic" products
            ],
        ];
        $query->set( 'meta_query', $meta_query );

    }

    /**
     * Optics 404.
     * 
     * @since   1.0.0
     */
    public function optics_404( $query ) {
        
        // Check.
        if ( is_admin() || ! $query->is_main_query() || ! is_singular( 'product' ) ) {
            return;
        }
    
        // Global.
        global $post;
    
        // Correct condition: only 404 if _is_optic === 'yes'
        $is_optic = get_post_meta( $post->ID, '_is_optic', true );
        if ( $is_optic === 'yes' ) {
            $query->set_404();
            status_header( 404 );
        }
    }
    

    /**
     * Calculate totals.
     * 
     * @since   1.0.0
     */
    public function calculate_totals( $cart ) {

        // Check.
        if( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        // Only run once.
        if( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

        // Loop through cart.
        foreach( $cart->get_cart() as $cart_item_key => $cart_item ) {

            // Check if set.
            if( ! isset( $cart_item['attached_optic'] ) ) continue;

            // Get product.
            $product = $cart_item['data'];

            // Check if set.
            if( ! isset( $cart_item['attached_optic']['price'] ) ) continue;

            // Set price.
            $price = floatval( $product->get_price() ) + floatval( $cart_item['attached_optic']['price'] );

            // Set price.
            //$product->set_price( $price );

            // Also update the cart item totals.
            $cart->cart_contents[$cart_item_key]['data']->set_price( $price );
            $cart->cart_contents[$cart_item_key]['line_total']    = $price * $cart_item['quantity'];
            $cart->cart_contents[$cart_item_key]['line_subtotal'] = $price * $cart_item['quantity'];

        }

    }

    /**
     * Add cart item data.
     * 
     * @param  array   $cart_item_data
     * @param  integer $product_id
     * @param  integer $variation_id
     * 
     * @since   1.0.0
     */
    public function cart_item_data( $cart_item_data, $product_id, $variation_id ) {
        
        // Check for attached optic data.
        if( ! isset( $_POST['attached_optic_data'] ) ) return $cart_item_data;

        // Check if set.
        if( empty( $_POST['attached_optic_data'] ) || $_POST['attached_optic_data'] == 'standalone' ) return $cart_item_data;

        // Add.
        $cart_item_data['attached_optic'] = json_decode( stripslashes( $_POST['attached_optic_data'] ), true );

        // Return.
        return $cart_item_data;

    }

    /**
     * Cart item session.
     * 
     * @param  array   $cart_item_data
     * @param  array   $values
     * 
     * @since   1.0.0
     */
    public function cart_item_session( $cart_item_data, $values ) {

        // Check if set.
        if( ! isset( $values['attached_optic'] ) ) return $cart_item_data;

        // Add.
        $cart_item_data['attached_optic'] = $values['attached_optic'];

        // Return.
        return $cart_item_data;

    }

    /**
     * Show cart item data.
     * 
     * @param  array   $item_data
     * @param  object  $cart_item_data
     * 
     * @since   1.0.0
     */
    public function show_cart_item_data( $item_data, $cart_item_data ) {

        // Check if set.
        if( ! isset( $cart_item_data['attached_optic'] ) ) return $item_data;

        // Loop.
        foreach( $cart_item_data['attached_optic'] as $key => $data ) {

            // Check key.
            if( ! in_array( $key, [ 'name' ] ) ) continue;

            // Add.
            $item_data[] = [
                'key'   => 'Package',
                'value' => $data . ' (' . $cart_item_data['attached_optic']['sku'] . ')',
            ];

        }

        // Return.
        return $item_data;

    }

    /**
     * Cart subtotal.
     * 
     * @param   float   $subtotal
     * @param   boolean $compound
     * @param   object  $cart
     * 
     * @since   1.0.0
     */
    public function cart_subtotal( $subtotal, $compound, $cart ) {

        // Set subtotal.
        $subtotal = 0;

        // Loop through cart.
        foreach( $cart->get_cart() as $cart_item_key => $cart_item ) {

            // Get product.
            $product = $cart_item['data'];

            // Set price.
            $price = ( ! isset( $cart_item['attached_optic']['price'] ) ) ? $product->get_price() : $product->get_price() + $cart_item['attached_optic']['price'];

            // Add to subtotal.
            $subtotal += $price * $cart_item['quantity'];

        }
        
        // Return.
        return wc_price( $subtotal );

    }

    /**
     * Cart total.
     * 
     * @param   float   $total
     * @param   object  $cart
     * 
     * @since   1.0.0
     */
    public function cart_total( $total, $cart ) {

        // Set total.
        $total = 0;

        // Loop through cart.
        foreach( $cart->get_cart() as $cart_item_key => $cart_item ) {

            // Get product.
            $product = $cart_item['data'];

            // Set price.
            $price = ( ! isset( $cart_item['attached_optic']['price'] ) ) ? $product->get_price() : $product->get_price() + $cart_item['attached_optic']['price'];

            // Add to total.
            $total += $price * $cart_item['quantity'];

        }
        
        // Return.
        return $total;

    }

    /**
     * Set cart item price.
     * 
     * @param   string  $price
     * @param   array   $cart_item
     * @param   string  $cart_item_key
     * 
     * @since   1.0.0
     */
    public function cart_item_price( $product_price, $cart_item, $cart_item_key ) {

        // Check.
        if( ! isset( $cart_item['attached_optic']['price'] ) ) return $product_price;

        // Get the product object
        $product = $cart_item['data'];
        
        // Add to price.
        $updated_price = $product->get_price() + $cart_item['attached_optic']['price'];
        
        // Format the updated price.
        $formatted_price = wc_price( $updated_price );
        
        // Return the updated price.
        return $formatted_price;

    }

    /**
     * Set cart item subtotal price.
     * 
     * @param   string  $price
     * @param   array   $cart_item
     * @param   string  $cart_item_key
     * 
     * @since   1.0.0
     */
    public function cart_item_price_subtotal( $product_price, $cart_item, $cart_item_key ) {

        // Check.
        if( ! isset( $cart_item['attached_optic']['price'] ) ) return $product_price;

        // Get the product object
        $product = $cart_item['data'];
        
        // Add to price.
        $updated_price = ( $product->get_price() + $cart_item['attached_optic']['price'] ) * $cart_item['quantity'];
        
        // Format the updated price.
        $formatted_price = wc_price( $updated_price );
        
        // Return the updated price.
        return $formatted_price;

    }

    /**
     * Adjust get price.
     * 
     * @param   float   $price
     * @param   integer $qty
     * @param   object  $product
     * 
     * @since   1.0.0
     */
    public function adjust_get_price( $price, $qty, $product ) {

        // Only adjust on cart/checkout.
        if( ! is_cart() && ! is_checkout() ) return $price;

        // Loop through cart items.
        foreach( WC()->cart->get_cart() as $item ) {

            // Check if the product matches a cart item.
            if( $product->get_id() !== $item['product_id'] && $product->get_id() !== $item['variation_id'] ) continue;

            // Only adjust if attached_optic exists.
            if( ! isset( $item['attached_optic'], $item['attached_optic']['price'] ) ) continue;

            // Set price.
            $price = floatval( $item['data']->get_regular_price() ) + floatval( $item['attached_optic']['price'] );

            // Return.
            return $price * $qty;

        }

        // Return.
        return $price;

    }

    /**
     * Get optic data.
     * 
     * @since   1.0.0
     */
    public static function get_optic_data( $optics ) {

        // Set data.
        $data = [];

        // Loop.
        foreach( $optics as $optic ) {

            // Check if set.
            if( empty( $optic['scalarworks_attached'] ) ) continue;

            // Get optic.
            $product = wc_get_product( $optic['scalarworks_attached'] );

            // Check.
            if( ! $product ) continue;

            // Check for price.
            $price = ( $optic['custom_price'] && ! empty( $optic['set_price'] ) ) ? $optic['set_price'] : $product->get_price();

            // Get variations assigned to the optic.
            $variations = [];
            if ( ! empty( $optic['variation_images'] ) && is_array( $optic['variation_images'] ) ) {
                foreach ( $optic['variation_images'] as $variation ) {
                     $variations = array_merge( $variations, $variation['scalarworks_variations'] );
                }
                $variations = array_unique( $variations );
            }

            // Set.
            $data[$product->get_id()] = [
                'id'            => $product->get_id(),
                'name'          => $product->get_name(),
                'sku'           => $product->get_sku(),
                'price'         => $price,
                'images'        => self::get_image_data( $optic ),
                'variations'    => $variations,
            ];

        }

        // Apply filter.
        $data = apply_filters( 'woocommerce_scalarworks_optic_data', $data, $product );

        // Return.
        return $data;

    }

    /**
     * Get optic image data.
     * 
     * @since   1.0.0
     */
    public static function get_image_data( $optic ) {

        // Check.
        if( empty( $optic['variation_images'] ) ) return [];

        // Set images.
        $images = [];

        // Loop.
        foreach( $optic['variation_images'] as $image ) {

            // Loop through variations.
            foreach( $image['scalarworks_variations'] as $variation ) {

                // Check if set.
                if( empty( $image['scalarworks_image'] ) ) continue;

                // Add.
                $images[$variation] = $image['scalarworks_image'];

            } 

        }

        // Return.
        return $images;

    }
    
}