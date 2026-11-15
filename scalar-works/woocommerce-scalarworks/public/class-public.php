<?php
/**
 * Public.
 *
 * The core.
 *
 * @package WooCommerce Scalarworks
 * @since   1.0.0
 * @version 1.0.0
 */
namespace WooCommerceScalarworks\Public;
class core {

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Check if WooCommerce is active.
        if( ! class_exists( 'WooCommerce' ) ) return;

        // Actions.
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ], 99 );

        // Filters.
        add_filter( 'woocommerce_locate_template', [ $this, 'template' ], 10, 3 );
        add_filter( 'woocommerce_cart_item_thumbnail', [ $this, 'custom_checkout_image' ], 10, 3 );


    }
 
    /**
     * Enqueue.
     * 
     * @since   1.0.0
     */
    public function enqueue() {

        // Global.
        global $post;

        // Get asset version.
        $version = file_exists( WOOSCALAR_PATH . 'public/mix-manifest.json' ) ? json_decode( file_get_contents( WOOSCALAR_PATH . 'public/mix-manifest.json' ), true ) : [];

        // Set version.
        $css    = str_replace( '/css/style.css?id=', '', $version['/css/style.css'] ) ?? time();
        $js     = str_replace( '/js/main.js?id=', '', $version['/js/main.js'] ) ?? time();

        // CSS.
        wp_enqueue_style( 'woocommerce-scalarworks-font', 'https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap', [], $css );
        wp_enqueue_style( 'woocommerce-scalarworks-css', WOOSCALAR_URI . 'public/css/style.css', [], $css );

        // JS.
        wp_enqueue_script( 'woocommerce-scalarworks-js', WOOSCALAR_URI . 'public/js/main.js', [ 'jquery' ], $js, true );

        // Localize.
        wp_localize_script( 'woocommerce-scalarworks-js', 'woocommerce_scalarworks', [
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'currency'   => get_woocommerce_currency_symbol(),
            'outofstock' => $this->stock_notifications( $post->ID ),
            'instock'    => '<p class="stock in-stock"><span class="c-in-stock">IN STOCK</span></p>',
        ] );

    }

    /**
     * Locate template.
     * 
     * @since   1.0.0
     */
    public function template( $template, $name, $path ) {

        // Global.
        global $woocommerce;

        // Set template.
        $_template = $template;

        // Check path.
        if( ! $path ) $path = $woocommerce->template_url;

        // Set plugin path.
        $plugin_path  = WOOSCALAR_PATH  . 'public/views/';

        // Set plugin template.
        $template = locate_template( [
            $plugin_path . $name,
            $name
        ] );

        // Check for theme template.
        if( ! $template && file_exists( $plugin_path . $name ) )
            $template = $plugin_path . $name;

        // Check template.
        if( ! $template )
            $template = $_template;

        // Return.
        return $template;

    }

    /**
     * Stock notifications.
     * 
     * @since   1.0.0
     */
    public function stock_notifications( $id ) {

        // Check.
        if( get_post_type( $id ) != 'product' ) return;
        if( is_shop() || is_home() || is_front_page() || is_category() || is_product_category() ) return;
        if( ! is_product() && ! is_page() ) return;

        // Get product.
        $product = wc_get_product( $id );

        // Set variables.
        $unsubscribe = '';
        $is_category = false;
        $hide        = is_array( get_option( 'bis_hide_products' ) ) ? get_option( 'bis_hide_products' ) : explode( ',', get_option( 'bis_hide_products' ) );
        $user        = get_current_user_id();
        $user_email  = get_userdata( $user ) ? get_userdata( $user )->user_email : '';

        // Check if hidden product.
        if( in_array( $id, (array)$hide ) ) return;

        // Loop through product terms.
        foreach( wp_get_post_terms( $id, 'product_cat' ) as $term ) {

            // Check if term is in hidden products.
            if( ! in_array( $term->term_id, (array) get_option( 'bis_hide_products_cat' ) ) ) continue;
            $is_category = true;

        }

        // Check if hidden category.
        if( $is_category ) return;

        // Unsubscribe.
        if( isset( $_GET['bis_product_id'] ) && isset( $_GET['bis_variation_id'] ) && isset( $_GET['bis_mailid'] ) ) {

            // Set variables.
            $unsubscribe_product_level_id = is_numeric( $_GET['bis_product_id'] ) && $_GET['bis_product_id'] ? $_GET['bis_product_id'] : 0;
            $unsubscribe_variation_id     = is_numeric( $_GET['bis_variation_id'] ) && $_GET['bis_variation_id'] ? $_GET['bis_variation_id'] : 0;
            $product_to_unsubscribe       = $unsubscribe_variation_id ? $unsubscribe_variation_id : $unsubscribe_product_level_id;

            // Unsubscribe.
            if( bis_delete_r_uncheck_subscribers( $product_to_unsubscribe, $_GET['bis_mailid'], 'delete' ) ) {
                $unsubscribe_text = __( 'You have been successfully Unsubscribed', 'backinstocknotifier' );
                $product_type     = \BIS_Admin_Functions::backinstock_get_product_type( $product );
                if( 'variation' === $product_type && fp_get_product_id( $product ) != $unsubscribe_variation_id )
                    $unsubscribe_text = '';
            }

        }

        // Set variables.
        $new_line = get_option( 'backinstock_notify_button_line' ) === 'yes' ? '<br/>' : '';
        $product_type = \BIS_Admin_Functions::backinstock_get_product_type( $product );
        $notifying_product = $product;

        // Set label.
        $notify_label = "<form class = 'outofstocknotifyme' id = 'bis_outofstock' action = '' method = 'post' style='display: block;'>"
                . "<div class = 'outofstock_message'></div>"
                . "<p class='custom_message' id='bis_form_title'>" . get_option( 'instock_get_notified_form_caption' ) . "</p>";

        // Check for error message position.
        if( get_option( 'bis_error_msg_position' ) === 'above' )
            $notify_label .= "<div class='outofstock_error_msg'></div>";
        if( get_option( 'bis_success_msg_position' ) === 'above' )
            $notify_label .= "<div class='outofstock_success_msg'></div>";
        if( get_option( 'backinstock_iagree_enabled' ) === 'yes' ) {
            $privacy_policy_url = ( function_exists( 'get_privacy_policy_url' ) ) ? get_privacy_policy_url() : '';
            $privacy_policy = '<a href="' . $privacy_policy_url . '">' . __( "Privacy Policy", "backinstocknotifier" ) . '</a>';
            $iagree_label = str_replace( '[privacy_policy]', $privacy_policy, get_option( 'backinstock_iagree_label' ) );
            $notify_label .= "<p><input type='checkbox' id='backinstock_iagree'/>" . " " . $iagree_label . "</p>";
        }
        $productid = $id;
        $variation_id = fp_get_variation_id( $notifying_product );
        $notify_label .= "<p class = 'notifymeptag'>"
                . "<input type = 'text' id = 'backinstock_textbox' placeholder = '" . get_option( 'instock_get_notified_placeholder_text' ) . "' value = {$user_email}>"
                . $new_line .
                "<input type = 'submit' data-product_id = {$productid} data-variation_id = {$variation_id} class = 'notifyme' id = 'backinstock_button' name = 'subscribeme' value = '" . get_option( 'instock_get_notified_button_label' ) . "'/>"
                . "</p>"
                . "<p><input type = 'hidden' name = 'out_stock_variable_id' value = ''></p>"
                . "</form>";
        if( get_option( 'bis_error_msg_position' ) === 'below' )
            $notify_label .= "<div class='outofstock_error_msg'></div>";
        if( in_array( get_option( 'bis_success_msg_position' ), array( 'below', 'formtop' ) ) )
            $notify_label .= "<div class='outofstock_success_msg'></div>";
        return '<p class="stock out-of-stock"><span class="c-out-of-stock">OUT OF STOCK</span></p>' . $unsubscribe_text . $notify_label;

    }


    /**
     * Custom checkout image.
     * 
     * @since   1.0.0
     */
    public function custom_checkout_image( $image, $cart_item, $cart_item_key ) {
        $product_id   = $cart_item['product_id'];
        $variation_id = $cart_item['variation_id'];
        $image_html   = '';
    
        if ( $variation_id && have_rows( 'scalarworks_attached_products', $product_id ) ) {
            while ( have_rows( 'scalarworks_attached_products', $product_id ) ) {
                the_row();
    
                if ( have_rows( 'variation_images' ) ) {
                    while ( have_rows( 'variation_images' ) ) {
                        the_row();
                        $variation_match = get_sub_field( 'scalarworks_variations' );
    
                        if ( isset( $variation_match[0] ) && intval( $variation_match[0] ) === intval( $variation_id ) ) {
                            $gallery = get_sub_field( 'scalarworks_image' );
                            if ( ! empty( $gallery ) && is_array( $gallery ) ) {
                                $image_html = '<img src="' . esc_url( $gallery[0] ) . '" />';
                            }
                            break 2;
                        }
                    }
                }
            }
        }
    
        return $image_html ?: $image;
    }
    
    
}