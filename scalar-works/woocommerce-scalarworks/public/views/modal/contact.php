<?php
/**
 * FAQ contact.
 * 
 * @package WooCommerce/Scalarworks
 * @version 1.0.0
 */ 
$id = uniqid(); 

// Set icon.
$icon = '';
if( ! empty( $content['icon'] ) ) {

    // Set icon.
    $icon = ( is_numeric( $content['icon'] ) ? wp_get_attachment_image_url( $content['icon'], 'full' ) : $content['icon'] );

} else {

    // Set default icon.
    $icon = WOOSCALAR_URI . 'assets/share.png';

} ?>
<div class="scalarworks-faq-modal__questions">
    <div class="scalarworks-faq-modal__questions-inner"><?php

        // Title.
        if( ! empty( $content['heading'] ) ) {

            // Output. ?>
            <h3 class="scalarworks-faq-modal__questions-title"><?php echo $content['heading']; ?></h3><?php

        }

        // Link
        if( ! empty( $content['contact_link'] ) ) {

            // Output. ?>
            <div class="scalarworks-faq-modal__questions-link">
                <a href="<?php echo $content['contact_link']['url']; ?>" target="<?php echo $content['contact_link']['target']; ?>" rel="noopener noreferrer"><?php echo $content['contact_link']['title']; ?> <img src="<?php echo $icon; ?>" /></a>
            </div><?php

        } ?>

    </div>
</div>