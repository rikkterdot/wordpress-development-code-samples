<?php
/**
 * FAQ content.
 * 
 * @package WooCommerce/Scalarworks
 * @version 1.0.0
 */ 
$id = md5( $content['text'] ); ?>
<div id="modal-content-<?php echo $id; ?>" class="scalarworks-faq-modal__content">
    <?php echo wpautop( $content['text'] ); ?>
</div><?php

// Set padding.
$padding = [];
$groups = [ 
    'desktop'   => [
        'top'       => '60',
        'right'     => '60',
        'bottom'    => '60',
        'left'      => '60',
    ], 
    'mobile'   => [
        'top'       => '20',
        'right'     => '20',
        'bottom'    => '20',
        'left'      => '20',
    ],
];

// Loop.
foreach( $groups as $group_id => $group ) {

    // Loop through each group.
    foreach( $group as $key => $value ) {

        // Set padding.
        $padding[$group_id][$key] = ( ! empty( $content[$group_id . '_padding']['padding_' . $key] ) ) ? $content[$group_id . '_padding']['padding_' . $key] : $value;

    }
    
}

// CSS. ?>
<style>
    #modal-content-<?php echo $id; ?> {
        padding: <?php echo $padding['desktop']['top'] . 'px ' . $padding['desktop']['right'] . 'px ' . $padding['desktop']['bottom'] . 'px ' . $padding['desktop']['left'] . 'px'; ?>;
    }
    @media (max-width: 768px) {
        #modal-content-<?php echo $id; ?> {
            padding: <?php echo $padding['mobile']['top'] . 'px ' . $padding['mobile']['right'] . 'px ' . $padding['mobile']['bottom'] . 'px ' . $padding['mobile']['left'] . 'px'; ?>;
        }
    }
</style>