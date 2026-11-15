<?php
/**
 * Private.
 *
 * The core.
 *
 * @package WooCommerce Scalarworks
 * @since   1.0.0
 * @version 1.0.0
 */
namespace WooCommerceScalarworks\Private;
class core {

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Actions.
        add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_product_option' ] );
        add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_option'] );

        // Filters.
        add_filter( 'acf/settings/save_json', [ $this, 'save_json' ] );
        add_filter( 'acf/settings/load_json', [ $this, 'load_json' ] );
        add_filter( 'acf/load_field/name=scalarworks_link_attributes', [ $this, 'link_attributes' ] );
        add_filter( 'acf/fields/post_object/query/name=scalarworks_attached', [ $this, 'attached_optics' ], 10, 3 );        
        add_filter( 'acf/load_field/name=scalarworks_variations', [ $this, 'attached_variations' ] );

    }

    /**
     * Add Product Option.
     * 
     * @since   1.0.0
     */
    public function add_product_option() {

        // Global.
        global $post;

        // Output. ?>
        <div class="options_group"><?php

            // Checkbox.
            woocommerce_wp_checkbox( [
                'id'          => '_is_optic',
                'label'       => __('Optic', 'woocommerce'),
                'description' => __('Check this if the product is an optic.', 'woocommerce'),
                'value'       => get_post_meta( $post->ID, '_is_optic', true )
            ] ); ?>

        </div><?php

    }

    /**
     * Save Product Option.
     * 
     * @since   1.0.0
     */
    public function save_product_option( $post_id ) {

        // Update post meta.
        update_post_meta( $post_id, '_is_optic', isset( $_POST['_is_optic'] ) ? 'yes' : 'no' );

    }
    
    /**
     * Save ACF as JSON.
     * 
     * @since   1.0.0
     */
    public function save_json( $path ) {
        return WOOSCALAR_PATH . 'private/acf-json';
    }

    /**
     * Load ACF JSON.
     * 
     * @since   1.0.0
     */
    public function load_json( $paths ) {

        // Remove the original path (optional).
        unset($paths[0]);
        
        // Append the new path and return it.
        $paths[] = WOOSCALAR_PATH . 'private/acf-json';

        // Return.
        return $paths;

    }

    /**
     * Link Attributes.
     * 
     * @since   1.0.0
     */
    public function link_attributes( $field ) {

        // Empty choices.
        $field['choices'] = [];

        // Get attributes.
        $attributes = wc_get_attribute_taxonomies();

        // Loop.
        foreach( $attributes as $attribute ) {

            // Set.
            $field['choices'][ $attribute->attribute_name ] = $attribute->attribute_label;

        }

        // Add.
        $field['choices']['attached'] = 'Attached Optic';

        // Return.
        return $field;

    }

    /**
     * Attached Optics.
     * 
     * @param  array   $args
     * @param  array   $field
     * @param  int     $post_id
     * 
     * @since   1.0.0
     */
    public function attached_optics( $args, $field, $post_id ) {

        // Check for meta query.
        if( ! isset( $args['meta_query'] ) ) $args['meta_query'] = [];
    
        // Filter only products where _is_optic is set to 'yes'.
        $args['meta_query'][] = [
            'key'     => '_is_optic',
            'value'   => 'yes',
            'compare' => '='
        ];
    
        // Return.
        return $args;

    }

    /**
     * Attached Variations.
     * 
     * @param  array   $field
     * 
     * @since   1.0.0
     */
    public function attached_variations( $field ) {

        // Global.
        global $post;

        // Check if we're editing a product.
        if( ! isset( $post->ID ) || get_post_type( $post->ID ) !== 'product' ) return $field;

        // Get product.
        $product = wc_get_product( $post->ID );

        // Check if this is a variable product.
        if( ! $product || ! $product->is_type( 'variable' ) ) return $field;

        // Set choices.
        $field['choices'] = [];

        // Get variations.
        $variations = $product->get_available_variations();

        // If empty, add a default option and return.
        if( empty( $variations ) ) {
            $field['choices'][''] = 'No variations available. Save and reload.';
            return $field;
        }

        // Loop through variations.
        foreach( $variations as $variation ) {

            // Get variation ID.
            $variation_id   = $variation['variation_id'];
            $variation_obj  = wc_get_product( $variation_id );

            // Get attributes to display as the label.
            $attributes = [];

            // Loop through attributes.
            foreach( $variation['attributes'] as $key => $value ) {

                // Get taxonomy.
                $taxonomy = str_replace( 'attribute_', '', $key );

                // Get term name.
                $term_name = $value;

                // Check if taxonomy exists.
                if( taxonomy_exists( $taxonomy ) ) {

                    // Get term.
                    $term = get_term_by( 'slug', $value, $taxonomy );

                    // Check.
                    if( $term ) $term_name = $term->name;

                }

                // Set.
                $attributes[] = $term_name;

            }

            // Create label.
            $label = 'ID: ' . $variation_id . ' - ' . implode( ', ', $attributes );

            // Add to choices.
            $field['choices'][ $variation_id ] = $label;

        }

        // Add to choices.
        $field['choices']['attached'] = 'Attached Optic';

        // Return.        
        return $field;

    }

}