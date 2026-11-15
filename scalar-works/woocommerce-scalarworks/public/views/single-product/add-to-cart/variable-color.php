<?php
/**
 * Variable Color.
 * 
 * @package WooCommerce/Scalarworks
 * @version 1.0.0
 */ ?>
<tr class="<?php echo $disabled; ?>">
    <td class="scalarworks-label">
        <div>
            <label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo '<strong>' . wc_attribute_label( $attribute_label ) . '</strong>' . $description; // WPCS: XSS ok. ?></label>
            <div id="scalarworks-custom_<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>" class="scalarworks-color__outer">
                <div class="scalarworks-color__inner"><?php 

                    // Loop.
                    foreach( $options as $option ) {

                        // Get color.
                        $color = get_term_meta( $option->term_id, 'item_color', true );

                        // Check.
                        if( empty( $color ) ) continue; 
                    
                        // Output. ?>
                        <div class="scalarworks-single scalarworks-color-single" data-id="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>" data-value="<?php echo $option->slug; ?>" data-name="<?php echo esc_attr( $option->name ); ?>">
                            <span class="scalarworks-color-bubble" style="background-color:<?php echo esc_attr( $color ); ?>;border-color:<?php echo esc_attr( $color ); ?>"><?php echo esc_attr( $option->name ); ?></span>
                        </div><?php

                    }

                    // FAQs.
                    include WOOSCALAR_PATH . 'public/views/single-product/add-to-cart/variable-faq.php'; ?>

                </div>
            </div>
        </div>
    </td>
    <td class="scalarworks-value" style="display:none;"><?php

        // List pluck slugs.
        $slugs = wp_list_pluck( $options, 'slug' );

        // Dropdown.
        wc_dropdown_variation_attribute_options( array(
            'options'   => $slugs,
            'attribute' => $attribute_name,
            'product'   => $product,
        ) );

        // Reset link.
        echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : ''; ?>
    
    </td>
</tr>