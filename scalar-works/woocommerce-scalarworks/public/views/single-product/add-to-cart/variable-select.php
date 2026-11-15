<?php
/**
 * Variable Select.
 * 
 * @package WooCommerce/Scalarworks
 * @version 1.0.0
 */ ?>
<tr class="<?php echo $disabled; ?>">
    <td class="scalarworks-label">
        <div>
            <label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo '<strong>' . wc_attribute_label( $attribute_label ) . '</strong>' . $description; // WPCS: XSS ok. ?></label>
        </div>
    </td>
    <td class="scalarworks-value">
        <div><?php

            // List pluck slugs.
            $slugs = wp_list_pluck( $options, 'slug' );

            // Dropdown.
            wc_dropdown_variation_attribute_options( array(
                'options'   => $slugs,
                'attribute' => $attribute_name,
                'product'   => $product,
            ) );

            // FAQs.
            include WOOSCALAR_PATH . 'public/views/single-product/add-to-cart/variable-faq.php';

            // Reset link.
            echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : ''; ?>
        
        </div>
    </td>
</tr>