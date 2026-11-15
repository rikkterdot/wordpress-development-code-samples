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
            <div id="scalarworks-custom_<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>" class="scalarworks-tile__outer">
                <div class="scalarworks-tile__inner"><?php 

                    // Loop.
                    foreach( $options as $option ) {

                        // Output. ?>
                        <div class="scalarworks-single scalarworks-tile-single" data-id="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>" data-value="<?php echo $option->slug; ?>">
                            <span class="scalarworks-tile-name"><?php echo esc_html( $option->name ); ?></span><?php

                            // Check if set.
                            if( ! empty( $option->description ) ) {

                                // Output. ?>
                                <span class="scalarworks-tile-description"><?php echo esc_html( $option->description ); ?></span><?php

                            } ?>

                        </div><?php

                    } ?>

                </div><?php

                // FAQs.
                include WOOSCALAR_PATH . 'public/views/single-product/add-to-cart/variable-faq.php'; ?>

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