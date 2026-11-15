<?php
/**
 * FAQ hero.
 * 
 * @package WooCommerce/Scalarworks
 * @version 1.0.0
 */ 
$id = uniqid();

// Set background.
$background = '';
if( ! empty( $content['background'] ) ) {

    // Set background.
    $background = ( is_numeric( $content['background'] ) ? wp_get_attachment_image_url( $content['background'], 'full' ) : $content['background'] );

} else {

    // Set default background.
    $background = WOOSCALAR_URI . 'assets/default-background.jpg';

}

// Set icon.
$icon = '';
if( ! empty( $content['icon'] ) ) {

    // Set icon.
    $icon = ( is_numeric( $content['icon'] ) ? wp_get_attachment_image_url( $content['icon'], 'full' ) : $content['icon'] );

} else {

    // Set default icon.
    $icon = WOOSCALAR_URI . 'assets/play.png';

}

// Output. ?>
<div class="scalarworks-faq-modal__video" style="background:url(<?php echo $background; ?>) no-repeat center center / cover;">
    <div class="scalarworks-faq-modal__video-overlay"></div><?php

    // Link.
    if( ! empty( $content['link'] ) ) {

        // Output. ?>
        <a href="<?php echo $content['link']['url']; ?>" target="<?php echo $content['link']['target']; ?>" class="scalarworks-faq-modal__video-link" rel="noopener noreferrer"><?php

    } 
    
    // Inner. ?>
    <div class="scalarworks-faq-modal__video-inner"><?php

        // Title.
        if( ! empty( $content['heading'] ) ) {

            // Output. ?>
            <h2 class="scalarworks-faq-modal__video-title"><?php echo $content['heading']; ?></h2><?php

        }

        // Subtitle.
        if( ! empty( $content['subheading'] ) ) {

            // Output. ?>
            <div class="scalarworks-faq-modal__video-subtitle"><?php echo $content['subheading']; ?></div><?php

        } 
        
        // Icon.
        if( ! empty( $content['link'] ) ) {

            // Output. ?>
            <div class="scalarworks-faq-modal__video-play">
                <span><img src="<?php echo $icon; ?>" /></span>
            </div><?php
            
        } ?>

    </div><?php

    // Close link.
    if( ! empty( $content['link'] ) ) {

        // Output. ?>
        </a><?php

    } ?>

</div>