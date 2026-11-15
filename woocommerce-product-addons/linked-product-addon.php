<?php
/**
 * WooCommerce linked add-on example.
 *
 * Original, standalone code demonstrating server-side validation, WooCommerce
 * cart hooks, and order-item metadata. Not copied from client source code.
 */

defined( 'ABSPATH' ) || exit;

final class Example_WooCommerce_Product_Addon {
    private const ALLOWED_META_KEY = '_example_allowed_addon_ids';
    private const CART_LINK_KEY    = '_example_parent_cart_key';

    public static function register(): void {
        add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_choice' ) );
        add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'add_linked_item' ), 20, 6 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_order_metadata' ), 10, 4 );
    }

    /**
     * Administrators can store an array of permitted product IDs in the
     * parent's _example_allowed_addon_ids product meta.
     */
    private static function get_allowed_products( int $product_id ): array {
        $ids = get_post_meta( $product_id, self::ALLOWED_META_KEY, true );
        return is_array( $ids ) ? array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ) : array();
    }

    public static function render_choice(): void {
        global $product;

        if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
            return;
        }

        $allowed_ids = self::get_allowed_products( $product->get_id() );
        if ( empty( $allowed_ids ) ) {
            return;
        }

        wp_nonce_field( 'example_addon_' . $product->get_id(), 'example_addon_nonce' );

        echo '<fieldset class="example-product-addon">';
        echo '<legend>' . esc_html__( 'Optional add-on', 'example' ) . '</legend>';
        echo '<label><input type="radio" name="example_addon_id" value="0" checked> ';
        echo esc_html__( 'No add-on', 'example' ) . '</label>';

        foreach ( $allowed_ids as $id ) {
            $addon = wc_get_product( $id );
            if ( ! $addon || ! $addon->is_type( 'simple' ) || ! $addon->is_purchasable() || ! $addon->is_in_stock() ) {
                continue;
            }

            printf(
                '<label><input type="radio" name="example_addon_id" value="%d"> %s — %s</label>',
                absint( $id ),
                esc_html( $addon->get_name() ),
                wp_kses_post( wc_price( (float) $addon->get_price() ) )
            );
        }

        echo '</fieldset>';
    }

    /**
     * Add the accessory as a separate WooCommerce line item.
     * Its own product price, stock handling, taxes, and discounts are applied
     * by WooCommerce, rather than recalculating cart totals manually.
     */
    public static function add_linked_item(
        string $cart_item_key,
        int $product_id,
        int $quantity,
        int $variation_id,
        array $variation,
        array $cart_item_data
    ): void {
        if ( isset( $cart_item_data[ self::CART_LINK_KEY ] ) ) {
            return; // Ignore the second add-to-cart action for the add-on.
        }

        if ( ! isset( $_POST['example_addon_id'], $_POST['example_addon_nonce'] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['example_addon_nonce'] ) );
        if ( ! wp_verify_nonce( $nonce, 'example_addon_' . $product_id ) ) {
            return;
        }

        $addon_id = absint( wp_unslash( $_POST['example_addon_id'] ) );
        if ( ! $addon_id || ! in_array( $addon_id, self::get_allowed_products( $product_id ), true ) ) {
            return;
        }

        $addon = wc_get_product( $addon_id );
        if ( ! $addon || ! $addon->is_type( 'simple' ) || ! $addon->is_purchasable() || ! $addon->is_in_stock() ) {
            return;
        }

        WC()->cart->add_to_cart(
            $addon_id,
            max( 1, $quantity ),
            0,
            array(),
            array( self::CART_LINK_KEY => $cart_item_key )
        );
    }

    /**
     * Preserve the relationship for fulfillment after checkout.
     */
    public static function add_order_metadata(
        WC_Order_Item_Product $item,
        string $cart_item_key,
        array $values,
        WC_Order $order
    ): void {
        if ( empty( $values[ self::CART_LINK_KEY ] ) ) {
            return;
        }

        $cart = WC()->cart ? WC()->cart->get_cart() : array();
        $parent = $cart[ $values[ self::CART_LINK_KEY ] ] ?? null;

        if ( $parent ) {
            $parent_product = wc_get_product( absint( $parent['product_id'] ) );
            if ( $parent_product ) {
                $item->add_meta_data(
                    __( 'Selected with', 'example' ),
                    $parent_product->get_name(),
                    true
                );
            }
        }
    }
}

add_action( 'plugins_loaded', array( 'Example_WooCommerce_Product_Addon', 'register' ) );
