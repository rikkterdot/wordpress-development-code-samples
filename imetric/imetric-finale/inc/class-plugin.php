<?php
/**
 * Plugin.
 * 
 * @since   1.0.0
 */

// Namespace.
namespace BuiltMighty\IMETF;
class imetfPlugin {

    /**
     * Loader.
     * 
     * @since   1.0.0
     * @access  protected
     * @var     imetfLoader       $loader     Registers all hooks.
     */
    protected $loader;

    /**
     * Identifier.
     * 
     * @since   1.0.0
     * @access  protected
     * @var     string          $identifier     The plugin identifier.
     */
    protected $plugin_name;

    /**
     * Version.
     * 
     * @since   1.0.0
     * @access  protected
     * @var     string          $version        The plugin version.
     */
    protected $version;

    /**
     * Plugin instance.
     *
     * @see   get_instance()
     * @var   object
     */
    protected static $instance = null;

    /**
     * Plugin Prvate Class instance.
     *
     * @since   1.0.0
     * @var  imetfPrivate
     */
    protected $private = null;

    /**
     * Plugin Public Class instance.
     *
     * @since   1.0.0
     * @var  imetfPublic
     */
    protected $public = null;


    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Set version.
        $this->version = IMETF_VERSION;

        // Set name.
        $this->plugin_name = IMETF_NAME;

        // Load dependencies.
        $this->load_dependencies();

        // Load private hooks.
        $this->private_hooks();

        // Load public hooks.
        $this->public_hooks();

    }

    /**
     * Load dependencies.
     * 
     * @since   1.0.0
     * @access  private
     */
    private function load_dependencies() {

        // Loader.
        require_once IMETF_PATH . 'inc/class-loader.php';

        // Logging.
        require_once IMETF_PATH . 'utilities/class-log.php';

        // Utilities.
        require_once IMETF_PATH . 'utilities/class-google-sheets.php';
        
        // Private.
        require_once IMETF_PATH . 'private/class-private.php';

        // Public.
        require_once IMETF_PATH . 'public/class-public.php';
        
        // My Account
        require_once IMETF_PATH . 'public/class-imetf-my-account.php';


        // Initiate loader.
        $this->loader = new \BuiltMighty\IMETF\imetfLoader();

    }

    /**
     * Register admin hooks.
     */
    private function private_hooks() {

        // Set new admin.
        $this->private = new \BuiltMighty\IMETF\imetfPrivate( $this->get_name(), $this->get_version() );
        

        // Add actions.
        $this->loader->add_action('imetric_cron_job', $this->private, 'process_google_sheets_data');

        // Hide raw meta keys
        $this->loader->add_filter('woocommerce_hidden_order_itemmeta',$this->private, 'hide_serial_number_meta');

        // Display serial number in a user-friendly way
        $this->loader->add_action('woocommerce_after_order_itemmeta', $this->private, 'display_serial_number_in_admin_order', 10, 3);

        // Customer: Display serial number for customers
        $this->loader->add_action('woocommerce_order_item_meta_end', $this->private, 'display_serial_number_in_customer_order', 10, 3);
        



        // Add menu to dashboard.
        $this->loader->add_action('admin_menu', $this->private, 'imetf_add_admin_menu');
        $this->loader->add_action( 'admin_init', $this->private, 'register_settings' );


    }

    /**
     * Register public hooks.
     */
    private function public_hooks() {

        // Set new public.
        $public = new \BuiltMighty\IMETF\imetfPublic( $this->get_name(), $this->get_version() );

        // My Account hooks.
        $my_account = new \BuiltMighty\IMETF\IMETF_MyAccount();

        // Add filters.
        add_filter('upload_mimes', [$this, 'allow_json_uploads']);

        add_filter('cron_schedules', function ($schedules) {
            if (!isset($schedules['ten_minutes'])) {
                $schedules['ten_minutes'] = [
                    'interval' => 10 * 60, // 10 minutes in seconds.
                    'display'  => __('Every 10 Minutes', 'imetr_finale'),
                ];
            }
            return $schedules;
        });
        


    }

    /**
     * Allow .json files to be uploaded.
     *
     * @param array $mimes The array of allowed MIME types.
     * @return array The modified array of MIME types.
     */
    public function allow_json_uploads($mimes) {
        $mimes['json'] = 'application/json'; // Allow .json files
        return $mimes;
    }

    /**
     * Run.
     * 
     * @since   1.0.0
     */
    public function run() {

        // Run the loader.
        $this->loader->run();

    }

    /**
     * Get plugin name.
     * 
     * @since   1.0.0
     * @return  string      The plugin name.
     */
    public function get_name() {

        // Return plugin name.
        return $this->plugin_name;

    }

    /**
     * Get plugin version.
     * 
     * @since   1.0.0
     * @return  string      The plugin version.
     */
    public function get_version() {

        // Return plugin version.
        return $this->version;

    }

}
