<?php
/**
 * Attributes.
 *
 * Dealing with WooCommerce Attributes.
 *
 * @package WooCommerce Scalarworks
 * @since   1.0.0
 * @version 1.0.0
 */
namespace WooCommerceScalarworks\Public;
class attributes {

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Enqueue.
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );

        // Check if term.
        if( isset( $_GET['taxonomy'] ) && 0 == strpos( $_GET['taxonomy'], 'pa_' ) || isset( $_GET['page'] ) && $_GET['page'] == 'product_attributes' ) {

            // Actions.
            add_action( $_GET['taxonomy'] . '_add_form_fields', [ $this, 'term_fields' ] );
            add_action( $_GET['taxonomy'] . '_edit_form_fields', [ $this, 'term_fields' ] );
            add_action( $_GET['taxonomy'] . '_pre_add_form', [ $this, 'render_attribute_type_editor' ] );
            add_action( 'load-edit-tags.php', [ $this, 'save_attribute_type_change' ] );

            // Filters.
            add_filter( 'product_attributes_type_selector', [ $this, 'attribute_types' ] );
            
        }

        // Actions.
        add_action( 'edited_term', [ $this, 'term_save' ], 10, 1 );
        add_action( 'created_term', [ $this, 'term_save' ], 10, 1 );
        add_action( 'woocommerce_after_edit_attribute_fields', [ $this, 'attribute_fields' ] );
        add_action( 'woocommerce_after_add_attribute_fields', [ $this, 'attribute_fields' ] );
        add_action( 'woocommerce_attribute_added', [ $this, 'attribute_save' ] );
        add_action( 'woocommerce_attribute_updated', [ $this, 'attribute_save' ] );

    }

    /**
     * Enqueue.
     * 
     * @since   1.0.0
     */
    public function enqueue( $hook_suffix ) {

        // Check if term.
        if( 'term.php' !== $hook_suffix ) return;

        // CSS.
        wp_enqueue_style( 'wp-color-picker' );

        // JS.
        wp_enqueue_script( 'wp-color-picker' );

    }

    /**
     * Fields.
     * 
     * @param  WP_Term $term
     * @param  string  $taxonomy
     * @since   1.0.0
     */
    public function term_fields( $taxonomy ) {

        // Check if taxonomy is string or object.
        if( is_object( $taxonomy ) ) {

            // Save to term.
            $term = $taxonomy;

            // Get taxonomy.
            $taxonomy = $taxonomy->taxonomy;

        }

        // Check if this is a WooCommerce attribute taxonomy.
        if( 0 !== strpos( $taxonomy, 'pa_' ) ) return;

        // Get slug.
        $slug = substr( $taxonomy, 3 );

        // Check for color type.
        if( ! empty( wc_get_attribute_taxonomies() ) ) {
            foreach( wc_get_attribute_taxonomies() as $attribute ) {
                if( ! isset( $attribute->attribute_name ) || $attribute->attribute_name !== $slug ) continue;
                if( 'color' !== $attribute->attribute_type ) return;
            }
        }

        // Retrieve any saved color for this term.
        $color = get_term_meta( $term->term_id, 'item_color', true ); ?>
        <tr class="form-field term-custom-color-wrap">
            <th scope="row">
                <label for="item_color"><?php esc_html_e( 'Color', 'textdomain' ); ?></label>
            </th>
            <td>
                <input type="text" name="item_color" id="item_color" value="<?php echo esc_attr( $color ); ?>" class="scalarworks-item-color" data-default-color="#ffffff" />
                <p class="description"><?php esc_html_e( 'Select a color for this attribute.', 'textdomain' ); ?></p>
            </td>
        </tr>
        <script>
            jQuery(document).ready(function($){
                // Initialize the color picker.
                $('#item_color').wpColorPicker();
            });
        </script><?php

    }

    /**
     * Save.
     * 
     * @param  int $term_id
     * @since   1.0.0
     */
    public function term_save( $term_id ) {

        // Check if set.
        if( ! isset( $_POST['item_color'] ) ) return;

        // Save.
        update_term_meta( $term_id, 'item_color', sanitize_text_field( wp_unslash( $_POST['item_color'] ) ) );

    }

    /**
     * Attribute Fields.
     * 
     * @since   1.0.0
     */
    public function attribute_fields() {

        // Get attribute ID.
        $attribute_id = absint( $_GET['edit'] );
        
        // Field. ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="attribute_description">Description</label>
            </th>
            <td>
                <textarea name="attribute_description" id="attribute_description"><?php echo get_term_meta( $attribute_id, 'attribute_description', true ); ?></textarea>
                <p class="description"><?php esc_html_e('Enter a description for this attribute.', 'woocommerce'); ?></p>
            </td>
        </tr><?php

    }

    /**
     * Attribute Save.
     * 
     * @param  int $attribute_id
     * @since   1.0.0
     */
    public function attribute_save( $attribute_id ) {

        // Check if set.
        if( ! isset( $_POST['attribute_description'] ) ) return;

        // Save to term meta. 
        update_term_meta( $attribute_id, 'attribute_description', sanitize_text_field( wp_unslash( $_POST['attribute_description'] ) ) );

    }

    /** 
     * Attribute types.
     * 
     * @param  array $types
     * @since   1.0.0
     */
    public function attribute_types( $types ) {

        // Add to types.
        $types['color'] = __( 'Color', 'textdomain' );
        $types['tile']  = __( 'Tile', 'textdomain' );

        // Return.
        return $types;

    }

    /**
     * Get attribute type.
     * 
     * @param  string $attribute_name
     * @return array
     * @since   1.0.0
     */
    public static function get_attribute_type( $product ) {

        // Set data.
        $taxonomies = [];

        // Loop through attributes.
        foreach( $product->get_attributes() as $attribute ) {

            // Get name.
            $name = $attribute->get_name();

            // Add to taxonomies.
            $taxonomies[$name] = false;

            // Check if taxonomy.
            if( $attribute->is_taxonomy() ) {

                // Set. 
                $taxonomies[$name] = true;

                // Loop through options.
                foreach( $attribute->get_options() as $option ) {

                    // Get term.
                    $term = get_term( (int)$option, $name );

                    // Check if term exists.
                    if( $term ) {

                        // Set to true.
                        $taxonomies[$name] = true;

                    } else {

                        // Set to false.
                        $taxonomies[$name] = false;
                        break;

                    }

                }

            } else {

                // Set to false.
                $taxonomies[$name] = false;

            }

        }

        // Return.
        return (array)$taxonomies;

    }

    /**
     * Renders the attribute type editor on the taxonomy edit page for WooCommerce product attributes.
     *
     * This method checks if the current page is the taxonomy edit page (`edit-tags.php`) and if the taxonomy
     * being edited is a WooCommerce product attribute (prefixed with `pa_`). If these conditions are met, it
     * generates a form allowing the user to change the attribute type for the selected taxonomy.
     *
     * The form is dynamically injected into the page using JavaScript and includes a nonce for security,
     * the attribute ID, and a dropdown to select the new attribute type.
     *
     * @global string $pagenow The current admin page being viewed.
     *
     * @return void Outputs the JavaScript and HTML for the attribute type editor if conditions are met.
     */
    public function render_attribute_type_editor() {
        global $pagenow;
    
        if ( $pagenow !== 'edit-tags.php' || ! isset( $_GET['taxonomy'] ) || 0 !== strpos( $_GET['taxonomy'], 'pa_' ) ) {
            return;
        }
    
        $taxonomy = sanitize_text_field( $_GET['taxonomy'] );
        $attribute_name = substr( $taxonomy, 3 );
    
        $attribute_id = null;
        $current_type = '';
        foreach ( wc_get_attribute_taxonomies() as $attribute ) {
            if ( $attribute->attribute_name === $attribute_name ) {
                $attribute_id = $attribute->attribute_id;
                $current_type = $attribute->attribute_type;
                break;
            }
        }
    
        if ( ! $attribute_id ) return;
    
        $types = wc_get_attribute_types();
    
        ?>
        <script>
            jQuery(document).ready(function($) {
                const typeEditorHTML = `
                    <form method="post" style="margin: 10px 0; padding: 10px; background: #fff; border: 1px solid #ccd0d4;">
                        <strong>Change Attribute Type:</strong>
                        <input type="hidden" name="edit_attribute_type_nonce" value="<?php echo esc_attr( wp_create_nonce( 'edit_attribute_type_' . $attribute_id ) ); ?>" />
                        <input type="hidden" name="attribute_id" value="<?php echo esc_attr( $attribute_id ); ?>" />
                        <select name="new_attribute_type">
                            <?php foreach ( $types as $key => $label ): ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $current_type ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button button-primary">Update</button>
                    </form>
                `;
    
                $('#col-container #col-left .col-wrap').prepend(typeEditorHTML);
            });
        </script>
        <?php
    }

    /**
     * Handles the saving of changes to the attribute type for WooCommerce attributes.
     *
     * This method verifies the nonce, validates the input, updates the attribute type in the database,
     * clears the WooCommerce attribute taxonomies cache, and redirects to remove POST data.
     *
     * @return void
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @uses wp_verify_nonce() To verify the nonce for security.
     * @uses absint() To sanitize the attribute ID.
     * @uses sanitize_text_field() To sanitize the new attribute type.
     * @uses wc_get_attribute_types() To retrieve valid WooCommerce attribute types.
     * @uses $wpdb->update() To update the attribute type in the database.
     * @uses delete_transient() To clear the WooCommerce attribute taxonomies cache.
     * @uses wp_safe_redirect() To safely redirect the user.
     * @uses remove_query_arg() To remove the 'message' query argument from the URL.
     */
    public function save_attribute_type_change() {
        if (
            isset( $_POST['edit_attribute_type_nonce'], $_POST['attribute_id'], $_POST['new_attribute_type'] ) &&
            wp_verify_nonce( $_POST['edit_attribute_type_nonce'], 'edit_attribute_type_' . absint( $_POST['attribute_id'] ) )
        ) {
            global $wpdb;
    
            $attribute_id = absint( $_POST['attribute_id'] );
            $new_type     = sanitize_text_field( $_POST['new_attribute_type'] );
    
            $valid_types = array_keys( wc_get_attribute_types() );
            if ( in_array( $new_type, $valid_types, true ) ) {
                $wpdb->update(
                    $wpdb->prefix . 'woocommerce_attribute_taxonomies',
                    [ 'attribute_type' => $new_type ],
                    [ 'attribute_id' => $attribute_id ]
                );
    
                // Flush cache and refresh
                delete_transient( 'wc_attribute_taxonomies' );
            }
    
            // Redirect to remove POST
            wp_safe_redirect( remove_query_arg( 'message' ) );
            exit;
        }
    }
    
}
