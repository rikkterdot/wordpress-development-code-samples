<?php
/**
 * Variable Select.
 * 
 * @package WooCommerce/Scalarworks
 * @version 1.0.0
 */ 

// Set label and description.
$label = ( ! empty( get_field( 'attached_title' ) ) ) ? get_field( 'attached_title' ) : 'Package';
$description = ( ! empty( get_field( 'attached_description' ) ) ) ? get_field( 'attached_description' ) : 'Just the mount, or with an optic.'; ?>
<tr class="scalar-disabled">
    <td class="scalarworks-label">
        <div>
            <label for="selected-optic"><?php echo '<strong>' . $label . '</strong> &mdash; ' . $description; // WPCS: XSS ok. ?></label>
        </div>
    </td>
    <td class="scalarworks-value">
        <div><?php

            // Set key.
            $key = 'attached_optic';

            // Get optics.
            $optics = \WooCommerceScalarworks\Public\products::get_optic_data( get_field( 'scalarworks_attached_products', get_the_ID() ) );

            // Output. ?>
            <div id="<?php echo $key; ?>" class="scalarworks-package__inner" data-optics='<?php echo json_encode( $optics ); ?>' name="attribute_<?php echo $key; ?>" data-attribute_name="attribute_<?php echo $key; ?>">
                <div class="scalarworks-package-single active" data-value="standalone" data-stock="instock">
                    <span class="scalarworks-package-name"><?php echo esc_html( 'Standalone Mount' ); ?></span>
                </div><?php

                // Loop.
                foreach( $optics as $optic ) {

                    // Get optic product.
                    $optic_product = wc_get_product( $optic['id'] );

                    // Check for product.
                    if( ! $optic_product ) continue;

                    // Set stock status.
                    $stock_status = $optic_product->get_stock_status();

                    // Set display price.
                    $display_price = ( $optic['price'] == round( $optic['price'] ) ) ? number_format( $optic['price'], 0 ) : number_format( $optic['price'], 2 );

                    // Output. ?>
                    <div class="scalarworks-package-single" data-value="<?php echo esc_attr( $optic['id'] ); ?>" data-stock="<?php echo esc_attr( $stock_status ); ?>" data-price="<?php echo esc_attr( $optic['price'] ); ?>" data-attribute_name="attribute_<?php echo $key; ?>" data-vparent="<?php echo esc_html( implode( ', ', $optic['variations'] ) ); ?>" style="display:none;">
                        <span class="scalarworks-package-name"><?php echo esc_html( $optic['name'] ); ?></span>
                        <span class="scalarworks-package-price">+ <?php echo get_woocommerce_currency_symbol() . $display_price; ?></span>
                    </div><?php

                } ?>

            </div>
            <input id="attached_optic_data" type="hidden" name="attached_optic_data" value='' />
        </div><?php

        // Set.
        $attribute_name = 'attached';

        // FAQs.
        include WOOSCALAR_PATH . 'public/views/single-product/add-to-cart/variable-faq.php'; ?>

    </td>
</tr>