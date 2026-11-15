<?php
/**
 * Private.
 *
 * The core.
 *
 * @package Imetric Freshsales Integration
 * @since   1.0.0
 * @version 1.0.0
 */
namespace ImetricFreshsalesIntegration\Private;
class core {

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Load settings.
        add_action( 'admin_init', [ $this, 'settings' ] );

        // Add meta box for Freshsales Deal ID
        add_action( 'add_meta_boxes', [ $this, 'add_freshsales_deal_meta_box' ] );

        // Add actions for user profile fields
        add_action( 'show_user_profile', [ $this, 'render_distributor_user_field' ] );
        add_action( 'edit_user_profile', [ $this, 'render_distributor_user_field' ] );
        add_action( 'personal_options_update', [ $this, 'save_distributor_user_field' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save_distributor_user_field' ] );
        

    }

    /**
     * Settings.
     * 
     * @since   1.0.0
     */
    public function settings() {

        // Check if class exists.
        if( ! class_exists( '\BuiltMighty\GlobalSettings\settings' ) ) return;

        // Get settings.
        $settings = \BuiltMighty\GlobalSettings\settings::get_instance();

        // Register a section visible to non-builtmighty.com users
        $settings->add_settings_section(
            'freshsales_integration',
            'Freshsales Integration',
            function () {
                echo '<p>Enter your Freshsales API configuration here.</p>';
            },
            true
        );

        // Add API key field
        $settings->add_settings_field(
            'freshsales_api_key',
            '',
            function () {
                $value = esc_attr( get_option( 'freshsales_api_key' ) ); ?>
                <div class="builtmighty-field builtmighty-text-field">
                    <span class="builtmighty-field-label"><?php echo esc_html( 'Freshsales API Key' ); ?> <span id="freshsales-test-result" style="font-weight: bold;"></span></span>
                    <div class="builtmighty-field_inner">
                        <input type="text" name="<?php echo esc_attr( 'freshsales_api_key' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
                    </div>
                </div><?php
            },
            'freshsales_integration',
            true
        );

        // Add domain field
        $settings->text_field(
            'freshsales_domain',
            'Freshsales Domain (e.g. company.freshsales.io)',
            'freshsales_integration',
            true
        );

        // Add Distributor Names repeater field
        $settings->add_settings_field(
            'freshsales_distributor_names',
            '',
            function () {
                $stored = get_option( 'freshsales_distributor_names', [] );
                if ( ! is_array( $stored ) ) $stored = [];
                ?>
                <div class="builtmighty-field builtmighty-repeater-field">
                    <span class="builtmighty-field-label"><?php echo esc_html( 'Distributor Names' ); ?></span>
                    <div id="distributor-names-list">
                        <?php foreach ( $stored as $name ): ?>
                            <div class="distributor-entry" style="margin-bottom: 6px; display: flex; gap: 8px; align-items: center;">
                                <input type="text" name="freshsales_distributor_names[]" value="<?php echo esc_attr( $name ); ?>" class="regular-text" style="flex: 1;" />
                                <button type="button" class="button button-small remove-distributor" style="margin:0;">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:10px; display: flex; gap: 8px;">
                        <input type="text" id="new-distributor-name" class="regular-text" placeholder="Enter name..." style="flex: 1;" />
                        <button type="button" class="button" id="add-distributor-name">Add</button>
                    </div>
                </div>
                <?php
            },
            'freshsales_integration',
            true
        );
        
        add_action( 'admin_enqueue_scripts', function( $hook ) {
            if ( $hook === 'toplevel_page_builtmighty' ) { // assumes global settings under Settings > General
                wp_enqueue_script( 'freshsales-scripts', plugin_dir_url( __FILE__ ) . '../includes/builtmighty-settings-c.js', [ 'jquery' ], null, true );
                wp_localize_script( 'freshsales-scripts', 'freshsalesTest', [
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'test_freshsales' ),
                ]);
            }
        });

        add_action( 'wp_ajax_test_freshsales', function() {
            check_ajax_referer( 'test_freshsales', 'nonce' );

            require_once plugin_dir_path(__FILE__) . '/../utilities/class-freshsales.php';
        
            try {
                $api        = new \ImetricFreshsalesIntegration\Utility\Freshsales();
                $response   = $api->test_connection();

                if ( $response['success'] ) {
                    wp_send_json_success( '✅ Connection successful' );
                } else {
                    wp_send_json_error( '❌ ' . $response['error'] );
                }
            } catch ( \Throwable $e ) {
                wp_send_json_error( '❌ ' . $e->getMessage() );
            }
        });
    }

    public function add_freshsales_deal_meta_box() {
        add_meta_box(
            'freshsales_deal_meta_box',
            'Freshsales',
            [ $this, 'render_freshsales_deal_meta_box' ],
            'shop_order',
            'side',
            'default'
        );
    }
    
    public function render_freshsales_deal_meta_box( $post ) {
        $deal_id = get_post_meta( $post->ID, '_freshsales_deal_id', true );
    
        if ( $deal_id ) {
            $domain = rtrim( get_option( 'freshsales_domain' ), '/' );
            $url    = esc_url( 'https://' . $domain . '/deals/' . $deal_id );
            echo '<p><strong>Freshsales Deal ID:</strong> ' . esc_html( $deal_id ) . '</p>';
            echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '" target="_blank">View in Freshsales</a></p>';
        } else {
            echo '<p>No Freshsales Deal associated with this order.</p>';
        }
    }

    public function render_distributor_user_field( $user ) {
        $roles = (array) $user->roles;
        if ( ! array_intersect( $roles, [ 'wholesale_customer', 'wholesale_customer_25' ] ) ) {
            return;
        }

        $distributors = get_option( 'freshsales_distributor_names', [] );
        if ( ! is_array( $distributors ) ) $distributors = [];
    
        $selected = get_user_meta( $user->ID, 'freshsales_distributor_name', true );
        ?>
        <h2>Freshsales Distributor</h2>
        <table class="form-table">
            <tr>
                <th><label for="freshsales_distributor_name">Distributor</label></th>
                <td>
                    <select name="freshsales_distributor_name" id="freshsales_distributor_name">
                        <option value="">— Select —</option>
                        <?php foreach ( $distributors as $name ): ?>
                            <option value="<?php echo esc_attr( $name ); ?>" <?php selected( $selected, $name ); ?>>
                                <?php echo esc_html( $name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Select the distributor associated with this user.</p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function save_distributor_user_field( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        update_user_meta( $user_id, 'freshsales_distributor_name', sanitize_text_field( $_POST['freshsales_distributor_name'] ?? '' ) );
    }

}
