<?php
/**
 * Private.
 * 
 * @since   1.0.0
 */

// Namespace.
namespace BuiltMighty\IMETF;

class imetfPrivate {

    /**
     * Plugin name.
     */
    private $plugin_name;

    /**
     * Plugin version.
     */
    private $plugin_version;

    /**
     * Construct.
     * 
     * @since   1.0.0
     * @param   string      $plugin_name        The name of the plugin.
     * @param   string      $plugin_version     The version of the plugin.
     */
    public function __construct( $plugin_name, $version ) {

        // Set plugin name.
        $this->plugin_name = $plugin_name;

        // Set plugin version.
        $this->plugin_version = $version;

    }

    /**
     * Add admin menu for the plugin.
     * 
     * @since   1.0.0
     */
    public function imetf_add_admin_menu() {
        add_menu_page(
            __('Imetric Finale Connect', 'imetr_finale'),
            __('Imetric Finale Connect', 'imetr_finale'),
            'manage_options',
            'imetf_main_menu',
            [$this, 'imetf_settings_page'],
            'dashicons-admin-tools'
        );

        add_submenu_page(
            'imetf_main_menu',
            __('Imetric Finale Connect Settings', 'imetr_finale'),
            __('Settings', 'imetr_finale'),
            'manage_options',
            'imetf_main_menu',
            [$this, 'imetf_settings_page']
        );
    }

    /**
     * Settings page callback.
     * 
     * @since   1.0.0
     */
    public function imetf_settings_page() {
        if (file_exists(IMETF_PATH . 'private/views/settings.php')) {
            include IMETF_PATH . 'private/views/settings.php';
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Settings file not found.', 'imetr_finale') . '</p></div>';
        }
        
    }

    /**
     * Register our settings.
     * 
     * @since   1.0.0
     */
    public function register_settings() {
        error_log('register settings called '); 

        register_setting( 'imetr_finale_settings', 'imetric_finale_spreadsheet_id' );
        register_setting( 'imetr_finale_settings', 'imetric_finale_spreadsheet_name');
        register_setting( 'imetr_finale_settings', 'imetric_finale_json_key_url' );
    
        register_setting('imetr_finale_settings', 'imetric_finale_json_key', [
            'type' => 'string',
            'sanitize_callback' => [$this, 'handle_file_json_key_upload'],
            'default' => ''
        ]);
        
    }

    /**
     * Handle file uploads for the JSON key.
     *
     * @param string $option The current value of the option being saved.
     * @return string The URL of the uploaded file or the current value.
     */
    public function handle_file_json_key_upload($option) {
        // Check if a file is being uploaded.
        if (!empty($_FILES['imetric_finale_json_key']['name'])) {
            error_log('Upload called. File details: ' . print_r($_FILES['imetric_finale_json_key'], true));
    
            // Handle the file upload.
            $uploaded = wp_handle_upload($_FILES['imetric_finale_json_key'], ['test_form' => false]);
    
            if (isset($uploaded['file'])) {
                // Use the local file path instead of the URL.
                $localFilePath = $uploaded['file'];
                $localFileUrl = $uploaded['url'];

                error_log('Upload successful. File URL: ' . $localFileUrl . ', File path: ' . $localFilePath);

                // Update the options only if a new file is successfully uploaded.
                update_option('imetric_finale_json_key_url', $localFileUrl); 
                update_option('imetric_finale_json_key', $localFilePath); 

                return $localFilePath;
            }

            // Log error if upload fails.
            if (isset($uploaded['error'])) {
                error_log('Upload failed: ' . $uploaded['error']);
            }

            return $option;
        }
    
        // If no file is uploaded, retain the current setting.
        error_log('No file uploaded, returning existing value.');
    }
    
    public function allow_json_uploads($mimes) {
        $mimes['json'] = 'application/json'; // Allow .json files
        return $mimes;
    }


    /**
     * Test Google Sheets connection.
     *
     * @return array|\WP_Error The data from the sheet or a WP_Error on failure.
     */
    public function test_google_sheets_connection() {
        error_log('test_google_sheets_connection called');
        
        // Get the local file path from the option
        $jsonKeyPath    = get_option('imetric_finale_json_key');
        $sheetName      = get_option('imetric_finale_spreadsheet_name');
        $spreadsheetId  = get_option('imetric_finale_spreadsheet_id');
        $range          = $sheetName;
    
        // Check if JSON Key file exists
        if (!$jsonKeyPath || !file_exists($jsonKeyPath)) {
            return new \WP_Error('missing_key', __('JSON Key file is missing or invalid: ' . $jsonKeyPath, 'imetr_finale'));
        }
    
        // Check if Spreadsheet ID exists
        if (!$spreadsheetId) {
            return new \WP_Error('missing_id', __('Spreadsheet ID is missing: ' . $spreadsheetId, 'imetr_finale'));
        }
    
        try {
            $googleSheets = new \BuiltMighty\IMETF\ImetfGoogleSheets($jsonKeyPath);
            $data = $googleSheets->readSheet($spreadsheetId, $range);

            if (empty($data)) {
                error_log('No data returned from Google Sheets.');
                return;
            }

            error_log('Data fetched from Google Sheets: ' . print_r($data, true));
            return $data;
        } catch (\Exception $e) {
            return new \WP_Error('connection_error', $e->getMessage());
        }
    }

    
    
    /**
     * Process Google Sheets data for the cron job.
     *
     * @return void
     */
    public function process_google_sheets_data() {
        error_log('Processing Google Sheets data via cron job.');
    
        // Fetch required options
        $jsonKeyPath    = get_option('imetric_finale_json_key');
        $spreadsheetId  = get_option('imetric_finale_spreadsheet_id');
        $sheetName      = get_option('imetric_finale_spreadsheet_name');
        $range          = $sheetName;
    

        // Check if JSON Key file exists
        if (!$jsonKeyPath || !file_exists($jsonKeyPath)) {
            error_log('JSON key file is missing or invalid.');
            return;
        }
    
        // Check if Spreadsheet ID exists
        if (!$spreadsheetId) {
            error_log('Spreadsheet ID is missing.');
            return;
        }
    
        try {
            $googleSheets = new \BuiltMighty\IMETF\ImetfGoogleSheets($jsonKeyPath);
            $data = $googleSheets->readSheet($spreadsheetId, $range);
    
            if (empty($data)) {
                error_log('No data returned from Google Sheets.');
                return;
            }
    
            $lastOrderNumber = null;

            // Process the rows (skipping the header row)
            foreach (array_slice($data, 1) as $row) {
                error_log('Processing row: ' . print_r($row, true));

                $orderNumber            = sanitize_text_field($row[1]); // Order Number
                $sku                    = sanitize_text_field($row[5]); // Product ID
                $serialNumber           = sanitize_text_field($row[8]); // Lot ID
                $warrantyStartDate      = sanitize_text_field($row[13]); // Warranty Start Date
                $record_last_updated    = sanitize_text_field($row[14]); // Record Last Updated
                
                if ($orderNumber && $sku && $serialNumber && $warrantyStartDate && $record_last_updated) {
                    $orderId    = $this->get_order_id_by_order_number($orderNumber);
                    $order      = wc_get_order($orderId);

    
                    if ($order) {
                        $lineItemsUpdated   = false;

    
                        // Loop through line items to find a matching SKU
                        foreach ($order->get_items() as $itemId => $item) {
                            $itemSku = $item->get_product()->get_sku();
    
                            if ($itemSku === $sku) {
                                // Check if the serial number and warranty start date is already set
                                $existingSerials            = wc_get_order_item_meta($itemId, '_serial_number', true);
                                $existingWarrantyStartDate  = wc_get_order_item_meta($itemId, '_warranty_start_date', true);
                                $existingRecordLastUpdated  = wc_get_order_item_meta($itemId, '_record_last_updated', true);

                                // Convert existing and new record update times to timestamps for comparison
                                $existingTimestamp  = strtotime($existingRecordLastUpdated);
                                $newTimestamp       = strtotime($record_last_updated);

                                // Skip update if the existing record is newer or equal
                                if ($existingTimestamp && $newTimestamp && $existingTimestamp >= $newTimestamp && $lastOrderNumber != $orderNumber) {
                                    error_log("Order ID {$orderId}, Item SKU {$sku}: Existing record ({$existingRecordLastUpdated}) is newer or equal to new record ({$record_last_updated}). Skipping update.");
                                    continue;
                                }


                                if( $orderNumber === $lastOrderNumber ){
                                    // Ensure serial numbers are stored as an array
                                    if (!is_array($existingSerials)) {
                                        $serialNumbers = !empty($existingSerials) ? [$existingSerials] : [];
                                    }
                                    $serialNumbers[] = $serialNumber;

                                } else {
                                    $serialNumbers = $serialNumber;
                                }


                                // Update metadata with new values
                                wc_update_order_item_meta($itemId, '_serial_number', $serialNumbers);
                                wc_update_order_item_meta($itemId, '_warranty_start_date', $warrantyStartDate);
                                wc_update_order_item_meta($itemId, '_record_last_updated', $record_last_updated);

                                error_log("Updated Order ID {$orderId}, Item SKU {$sku}: Serial Number {$serialNumber}, Warranty Start Date {$warrantyStartDate}, Record Last Updated {$record_last_updated}.");
                                $lineItemsUpdated = true;
                                $lastOrderNumber = $orderNumber;

                                break; // Stop looking after the match
                            }
                        }
    
                        if (!$lineItemsUpdated) {
                            error_log("No matching SKU {$sku} found in Order ID {$orderId}.");
                        }
                    } else {
                        error_log("Order with ID {$orderId} not found.");
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Error processing Google Sheets data: ' . $e->getMessage());
        }
    }

    /**
     * Hide the raw serial number meta key.
     *
     * @param array $hidden_meta The array of hidden meta keys.
     * @return array The updated array of hidden meta keys.
     */
    public function hide_serial_number_meta($hidden_meta) {
        $hidden_meta[] = '_serial_number';          // Add _serial_number to hidden meta keys.
        $hidden_meta[] = '_warranty_start_date';    // Add _warranty_start_date to hidden meta keys.
        $hidden_meta[] = '_record_last_updated';    // Add _record_last_updated to hidden meta keys.
        return $hidden_meta;
    }

    /**
     * Display data in the admin order view.
     *
     * @param int $itemId The order item ID.
     * @param WC_Order_Item $item The order item object.
     * @param WC_Product $product The product object.
     */
    public function display_serial_number_in_admin_order($itemId, $item, $product) {
        // Fetch the data from item metadata
        $serialNumber       = wc_get_order_item_meta($itemId, '_serial_number', true);
        $serialNumber       = is_array($serialNumber) ? implode(', ', $serialNumber) : $serialNumber; 
        $warrantyStartDate  = wc_get_order_item_meta($itemId, '_warranty_start_date', true);
        $recordLastUpdated  = wc_get_order_item_meta($itemId, '_record_last_updated', true);

        if ($serialNumber && $warrantyStartDate) {
            echo '<p><strong>' . __('Serial Number:', 'imetr_finale') . '</strong> ' . esc_html($serialNumber) . '</p>';
            echo '<p><strong>' . __('Warranty Start Date:', 'imetr_finale') . '</strong> ' . esc_html($warrantyStartDate) . '</p>';
            echo '<p><strong>' . __('Record Last Updated:', 'imetr_finale') . '</strong> ' . esc_html($recordLastUpdated) . '</p>';
        }
    }


     /* Display data on the customer's order details page.
     *
     * @param WC_Order_Item $item The order item object.
     * @param WC_Order $order The order object.
     * @param bool $plain_text Whether to display in plain text.
     */
    public function display_serial_number_in_customer_order($item_id, $item, $order) {
        // Fetch the data from item metadata
        $serialNumber       = wc_get_order_item_meta($item_id, '_serial_number', true);
        $serialNumber       = is_array($serialNumber) ? implode(', ', $serialNumber) : $serialNumber; 
        $warrantyStartDate  = wc_get_order_item_meta($item_id, '_warranty_start_date', true);
        $recordLastUpdated  = wc_get_order_item_meta($item_id, '_record_last_updated', true);
        
        if ($serialNumber && $warrantyStartDate) {
            echo '<ul class="wc-item-meta">';
            echo '<li><strong>' . __('Serial Number:', 'imetr_finale') . '</strong> ' . esc_html($serialNumber) . '</li>';
            echo '<li><strong>' . __('Warranty Start Date:', 'imetr_finale') . '</strong> ' . esc_html($warrantyStartDate) . '</li>';
            echo '<li><strong>' . __('Record Last Updated:', 'imetr_finale') . '</strong> ' . esc_html($recordLastUpdated) . '</li>';
            echo '</ul>';
        }
    }

    /**
     * Retrieve the order ID based on the custom order number.
     *
     * This function searches the WordPress database for a post ID that matches the given
     * order number. It looks for posts of type 'shop_order' with statuses 'wc-completed',
     * 'wc-processing', or 'wc-on-hold'. The search is performed on both the post ID and
     * the post title.
     *
     * @param string $order_number The custom order number to search for.
     * @return int The order ID if found, otherwise 0.
     */
    public function get_order_id_by_order_number($order_number) {
        global $wpdb;

        // Query to find the order ID using the custom order number
        $query = $wpdb->prepare(
            "SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_custom_order_number' 
            AND meta_value = %s 
            LIMIT 1",
            $order_number
        );

        $order_id = $wpdb->get_var($query);

        return $order_id ? (int) $order_id : 0;

    }
    

}
