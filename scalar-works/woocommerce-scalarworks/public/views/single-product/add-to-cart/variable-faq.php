<?php
/**
 * FAQs.
 * 
 * @package WooCommerce/Scalarworks
 * @version 1.0.0
 */

// Set FAQ ID.
$faq_id = false;

// Check if overriden.
if( get_field( 'scalarworks_faq_override' ) && ! empty( get_field( 'scalarworks_attributes' ) ) ) {

    // Loop.
    foreach( get_field( 'scalarworks_attributes' ) as $faq_attribute ) {

        // Compare.
        if( $faq_attribute['scalarworks_link_attributes'] !== str_replace( 'pa_', '', $attribute_name ) ) continue;

        // Set FAQ ID.
        $faq_id = $faq_attribute['scalarworks_faq'];
        break;

    }

}

// Check if we have a FAQ ID.
if( ! $faq_id ) {

    // Set args.
    $args = [
        'post_type'      => 'faq',
        'posts_per_page' => 1, // Get all matching posts
        'meta_query'     => [
            [
                'key'     => 'scalarworks_link_attributes',
                'value'   => str_replace( 'pa_', '', $attribute_name ), // Replace with the string you're searching for
                'compare' => 'LIKE',
            ],
        ],
    ];

    // Get FAQs.
    $faqs = new WP_Query($args);		
    if( $faqs->have_posts() ) {
        while( $faqs->have_posts() ) {

            // Get the post.
            $faqs->the_post();

            // Set override.
            $faq_id = get_the_ID();

        }
    }
    wp_reset_postdata();

}

// Check for a FAQ ID.
if( ! $faq_id ) return; 

// Get options.
$options = get_field( 'scalarworks_options', $faq_id ); ?>

<div id="faq-<?php echo $faq_id; ?>" data-id="<?php echo $faq_id; ?>" class="scalarworks-faq">
    <div class="scalarworks-faq__inner"><?php

        // Check for a featured image.
        if( has_post_thumbnail( $faq_id ) ) { 
            
            // Get featured image URL.
            $image = get_the_post_thumbnail_url( $faq_id, 'full' ); ?>

            <div class="scalarworks-faq__image" style="background-image: url('<?php echo esc_url( $image ); ?>');"></div>
            <?php

        } ?>

        <div class="scalarworks-faq__content">
            <h3 class="scalarworks-faq__title"><?php echo get_the_title( $faq_id ); ?></h3><?php

            // Check for content.
            if( ! empty( get_post_meta( $faq_id, 'scalarworks_subtitle', true ) ) ) { ?>

                <div class="scalarworks-faq__text"><?php echo get_post_meta( $faq_id, 'scalarworks_subtitle', true ); ?></div><?php

            } ?>

        </div>
        <div class="scalarworks-faq__button">
            <span><?php include WOOSCALAR_PATH . 'assets/plus_close.svg'; ?></span>
        </div>
    </div>
</div>
<div id="faq-modal-<?php echo $faq_id; ?>" class="scalarworks-faq-modal">
    <div class="scalarworks-faq-modal__inner">
        <div class="scalarworks-faq-modal__close">
            <span data-id="<?php echo $faq_id; ?>"><?php include WOOSCALAR_PATH . 'assets/plus_close.svg'; ?></span>
        </div><?php

        // Check for content.
        if( ! empty( get_field( 'scalarworks_content_flexible', $faq_id ) ) ) {

            // Loop.
            foreach( get_field( 'scalarworks_content_flexible', $faq_id ) as $content ) {

                // Get template.
                $template = WOOSCALAR_PATH . 'public/views/modal/' . $content['acf_fc_layout'] . '.php';
                if( file_exists( $template ) ) {

                    // Output.
                    include $template;

                }

            }

        } ?>

    </div>
</div>