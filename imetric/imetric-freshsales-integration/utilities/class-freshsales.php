<?php
namespace ImetricFreshsalesIntegration\Utility;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path( __FILE__ ) . 'class-api.php';

class Freshsales extends API {

    public function __construct() {
        $api_key = get_option( 'freshsales_api_key' );
        $domain  = get_option( 'freshsales_domain' );

        if ( ! $api_key || ! $domain ) {
            throw new \Exception('Missing Freshsales configuration.');
        }

        parent::__construct(
            "https://{$domain}/api",
            [ 'Authorization' => 'Token token=' . $api_key ]
        );

        //$this->get_account_by_email('info@crosbysmiles.com');
        //$this->get_or_create_account_id_from_order();
    }

    /**
     * Tests the connection to the Freshsales API by attempting to retrieve contact filters.
     *
     * @return array The response from the Freshsales API containing contact filters.
     */
    public function test_connection(): array {
        return $this->get('contacts/filters');
    }

    /**
     * Retrieves or creates a contact in Freshsales based on the provided order.
     *
     * This method first attempts to find an existing contact in Freshsales using the email
     * address extracted from the order. If a contact is found, its Freshsales ID is returned
     * and optionally stored in the user's meta data. If no contact is found, a new contact
     * is created in Freshsales using the order data, and the Freshsales ID of the newly
     * created contact is returned.
     *
     * @param WC_Order $order The WooCommerce order object containing customer details.
     * 
     * @return int|null The Freshsales contact ID if found or created successfully, or null on failure.
     */
    public function get_or_create_contact($order): ?int {
        $user_id        = $order->get_user_id();
        $is_distributor = false;

        if ($user_id) {
            $user = get_user_by('id', $user_id);
            $is_distributor = $user && in_array('wholesale_customer', (array) $user->roles, true);
        }

        $data       = $this->get_contact_data_from_order($order);
        extract($data); // sets $email, $first_name, $last_name, etc.

        // Lookup in Freshsales
        $contact    = $this->find_contact_by_email($email);
    
        // If contact exists in Freshsales, return the ID
        // and add Freshsales ID to user meta if user exists
        if ( $contact ) {
            $this->custom_log('[Freshsales Sync] Contact found in Freshsales: ' . print_r($contact, true));
            update_user_meta( $user_id, '_freshsales_contact_id', $contact['id'] );
    
            return $contact['id'];
        }
        
        $this->custom_log('[Freshsales Sync] No contact ID found in Freshsales. Creating new contact.');
    
        $contact_data = [
            'contact' => [
                'first_name'     => $first_name,
                'last_name'      => $last_name,
                'display_name'   => $full_name,
                'email'          => $email,
                'mobile_number'  => $phone,
                'city'           => $city,
                'state'          => $state,
                'zipcode'        => $zipcode,
                'country'        => $country,
                'address'        => $address,
                'custom_field'   => [],
            ]
        ];
    
        $res = $this->post('contacts', $contact_data);
        
        // Check if the contact was created successfully
        // and add Freshsales ID to user meta if user exists
        if ( $res['success'] ) {
            $contact_id = $res['data']['contact']['id'];
            if ( $user_id ) {
                $this->custom_log('[Freshsales Sync] Contact added: ' . print_r($contact_data, true));
                update_user_meta( $user_id, '_freshsales_contact_id', $contact_id );
            }
            return $contact_id;
        }
    
        return null;
    }

    /**
     * Retrieves contact data from a WooCommerce order.
     *
     * This method extracts contact information from a WooCommerce order object. By default, 
     * it uses the billing details. However, if the user associated with the order has the 
     * "wholesale_customer" role, it switches to using the shipping details instead.
     *
     * @param \WC_Order $order The WooCommerce order object.
     *
     * @return array An associative array containing the following contact details:
     *               - 'email' (string): The contact's email address.
     *               - 'first_name' (string): The contact's first name.
     *               - 'last_name' (string): The contact's last name.
     *               - 'phone' (string|null): The contact's phone number.
     *               - 'address1' (string): The first line of the contact's address.
     *               - 'address2' (string|null): The second line of the contact's address.
     *               - 'city' (string): The contact's city.
     *               - 'state' (string): The contact's state.
     *               - 'zipcode' (string): The contact's postal code.
     *               - 'country' (string): The contact's country.
     *               - 'full_name' (string|null): The full name of the contact (first and last name combined).
     *               - 'address' (string|null): The full formatted address as a single string.
     */
    public function get_contact_data_from_order(\WC_Order $order): array {
        $user_id = $order->get_user_id();
    
        // Default to billing
        $email      = $order->get_billing_email();
        $first_name = $order->get_billing_first_name();
        $last_name  = $order->get_billing_last_name();
        $phone      = $order->get_billing_phone();
        $address1   = $order->get_billing_address_1();
        $address2   = $order->get_billing_address_2();
        $city       = $order->get_billing_city();
        $state      = $order->get_billing_state();
        $zipcode    = $order->get_billing_postcode();
        $country    = $order->get_billing_country();
    
        // Billing
        $billing_address1   = $order->get_billing_address_1();
        $billing_address2   = $order->get_billing_address_2();
        $billing_city       = $order->get_billing_city();
        $billing_state      = $order->get_billing_state();
        $billing_zipcode    = $order->get_billing_postcode();
        $billing_country    = $order->get_billing_country();
        $billing_address    = implode(', ', array_filter([$billing_address1, $billing_address2]));

        // Shipping
        $shipping_address1   = $order->get_shipping_address_1();
        $shipping_address2   = $order->get_shipping_address_2();
        $shipping_city       = $order->get_shipping_city();
        $shipping_state      = $order->get_shipping_state();
        $shipping_zipcode    = $order->get_shipping_postcode();
        $shipping_country    = $order->get_shipping_country();
        $shipping_address    = implode(', ', array_filter([$shipping_address1, $shipping_address2]));
    
        // If user is Distributor, switch to shipping Info
        if ( $user_id ) {
            $user = get_user_by('id', $user_id);
            if ( $user && in_array('wholesale_customer', (array) $user->roles, true) ) {
                $email      = $order->get_meta( '_shipping_email' ) ?: $email;
                $first_name = $order->get_shipping_first_name();
                $last_name  = $order->get_shipping_last_name();
                $full_name  = trim($first_name . ' ' . $last_name);
                $phone      = $order->get_meta( key: '_shipping_phone' );
                $address1   = $order->get_shipping_address_1();
                $address2   = $order->get_shipping_address_2();
                $city       = $order->get_shipping_city();
                $state      = $order->get_shipping_state();
                $zipcode    = $order->get_shipping_postcode();
                $country    = $order->get_shipping_country();
    
                $this->custom_log('[Freshsales Sync] User is Distributor, using shipping info for contact. Email: ' . $email);

            }
        }
        $address    = implode(', ', array_filter([$address1, $address2, $city, $state, $zipcode, $country]));

        return [
            'email'                 => $email,
            'first_name'            => $first_name,
            'last_name'             => $last_name,
            'full_name'             => $full_name,
            'phone'                 => $phone,
            'address1'              => $address1,
            'address2'              => $address2,
            'address'               => $address,
            'city'                  => $city,
            'state'                 => $state,
            'zipcode'               => $zipcode,
            'country'               => $country,
            'billing_address1'      => $billing_address1,
            'billing_address2'      => $billing_address2,
            'billing_address'       => $billing_address,
            'billing_city'          => $billing_city,
            'billing_state'         => $billing_state,
            'billing_zipcode'       => $billing_zipcode,
            'billing_country'       => $billing_country,
            'shipping_address1'     => $shipping_address1,
            'shipping_address2'     => $shipping_address2,
            'shipping_address'      => $shipping_address,
            'shipping_city'         => $shipping_city,
            'shipping_state'        => $shipping_state,
            'shipping_zipcode'      => $shipping_zipcode,
            'shipping_country'      => $shipping_country,
        ];
    }
    
    /**
     * Finds a contact in Freshsales by their email address.
     *
     * This method searches for a contact in the Freshsales system using the provided
     * email address. If a matching contact is found, the full contact array is returned.
     * Otherwise, it returns null.
     *
     * @param string $email The email address of the contact to search for.
     * 
     * @return array|null Returns the full contact array if a match is found, or null if no match is found.
     */
    public function find_contact_by_email(string $email): ?array {
        $this->custom_log('[Freshsales Sync] find_contact_by_email: Searching email: ' . $email);

        $payload = [
            'filter_rule' => [
                [
                    'attribute' => 'contact_email.email',
                    'operator'  => 'is_in',
                    'value'     => $email,
                ]
            ]
        ];
    
        $response = $this->post('filtered_search/contact', $payload);
    
        if ($response['success'] && !empty($response['data']['contacts'])) {
            return $response['data']['contacts'][0]; // return full contact array
        }
    
        return null;
    }

    /**
     * Retrieves the ID of a deal stage by its name from Freshsales.
     *
     * This method sends a request to fetch all deal stages and searches for a stage
     * with a name that matches the provided name (case-insensitive). If a match is found,
     * the corresponding stage ID is returned. If no match is found, `null` is returned.
     *
     * @param string $name The name of the deal stage to search for.
     * @return int|null The ID of the deal stage if found, or null if not found.
     */
    public function get_stage_id_by_name($name): ?int {
        $res = $this->get('selector/deal_stages');

        if ($res['success']) {
            foreach ($res['data']['deal_stages'] as $stage) {
                if (strcasecmp($stage['name'], $name) === 0) {
                    $this->custom_log('[Freshsales Sync] Stage found: ' . $name . ' - ID: ' . $stage['id']);
                    return $stage['id'];
                }
            }
        }

        $this->custom_log('[Freshsales Sync] Stage not found: ' . $name);
        return null;
    }

    /**
     * Creates a deal in Freshsales for a given order and contact ID.
     *
     * @param WC_Order $order The WooCommerce order object.
     * @param int $contact_id The Freshsales contact ID associated with the deal.
     * 
     * @return array The response from the Freshsales API after attempting to create the deal.
     *
     * The function performs the following steps:
     * - Retrieves the stage ID for the deal based on the stage name 'Purchased Awaiting Shipping'.
     * - Fetches a custom order number or falls back to the order ID if not available.
     * - Checks if the user associated with the order is a distributor (wholesale customer) and assigns
     *   the distributor name to a custom field.
     * - Constructs the deal data including the deal name, amount, stage ID, contact ID, and custom fields.
     * - Sends a POST request to the Freshsales API to create the deal.
     * - If the deal is successfully created, stores the deal ID in the order meta.
     * - Iterates through the order items to add associated products to the deal in Freshsales.
     * - Logs relevant information and errors during the process.
     */
    public function create_deal($order, $contact_id): array {
        $this->custom_log('[Freshsales Sync] Create Deal Called');


        $products_array = [];
        $order_id       = $order->get_id();
        $order_meta     = get_post_meta( $order_id );
        $user_id        = $order->get_user_id();

        // Get the stage ID by name
        $stage_id = $this->get_stage_id_by_name('Purchased Awaiting Shipping');

        // Get custom order number
        $order_number = get_post_meta($order_id, '_custom_order_number', true);
        if ( ! $order_number ) {
            $order_number = $order_id;
        }

        // Custom Fields
        // MUA Gets added later when adding products to the deal (so we can avoid having to loop through order items more than 1)
        $custom_fields  = [];
        $user           = get_user_by('id', $user_id);

        // Customer PO Number
        $custom_fields['cf_customer_po_number'] = isset( $order_meta['additional_po_number'][0] ) ? $order_meta['additional_po_number'][0] : '';

        // Shipping Info
        $custom_fields['cf_shipping_address']   =   ( isset( $order_meta['_shipping_address_1'][0] ) ? $order_meta['_shipping_address_1'][0] : '' ) .
                                                    ( isset( $order_meta['_shipping_address_2'][0] ) ? ' ' . $order_meta['_shipping_address_2'][0] : '' );
        $custom_fields['cf_shipping_city']      = isset( $order_meta['_shipping_city'][0] ) ? $order_meta['_shipping_city'][0] : '';
        $custom_fields['cf_shipping_state']     = isset( $order_meta['_shipping_state'][0] ) ? $order_meta['_shipping_state'][0] : '';
        $custom_fields['cf_shipping_zipcode']   = isset( $order_meta['_shipping_postcode'][0] ) ? $order_meta['_shipping_postcode'][0] : '';
        $custom_fields['cf_shipping_country']   = isset( $order_meta['_shipping_country'][0] ) ? $order_meta['_shipping_country'][0] : '';

        // Billing Info
        $custom_fields['cf_invoice_address']    =   ( isset( $order_meta['_billing_address_1'][0] ) ? $order_meta['_billing_address_1'][0] : '' ) .
                                                    ( isset( $order_meta['_billing_address_2'][0] ) ? ' ' . $order_meta['_billing_address_2'][0] : '' );
        $custom_fields['cf_invoice_city']       = isset( $order_meta['_billing_city'][0] ) ? $order_meta['_billing_city'][0] : '';
        $custom_fields['cf_invoice_state']      = isset( $order_meta['_billing_state'][0] ) ? $order_meta['_billing_state'][0] : '';
        $custom_fields['cf_invoice_zipcode']    = isset( $order_meta['_billing_postcode'][0] ) ? $order_meta['_billing_postcode'][0] : '';
        $custom_fields['cf_invoice_country']    = isset( $order_meta['_billing_country'][0] ) ? $order_meta['_billing_country'][0] : '';

        // Check if user is Distributor (wholesale_customer in the system)
        if ( $user && in_array('wholesale_customer', (array) $user->roles, true) ) {

            // Get Distributor Name
            $distributor_name = get_user_meta( $user->ID, 'freshsales_distributor_name', true );
            
            // Fallbacks
            if (empty($distributor_name)) {
                $first = get_user_meta($user_id, 'first_name', true);
                $last  = get_user_meta($user_id, 'last_name', true);

                if ($first && $last) {
                    $distributor_name = "{$first} {$last}";
                } elseif ($last) {
                    $distributor_name = $last;
                } else {
                    $distributor_name = $user->user_email;
                }
            }


            $custom_fields['cf_distributor'] = $distributor_name;
            $this->custom_log('[Freshsales Sync] Assigned distributor name to cf_distributor: ' . $distributor_name);
        }

        $sales_account_id = $this->get_or_create_account_id_from_order($order);
        if ($sales_account_id) {
            $this->custom_log("[Freshsales Sync] Assigned sales_account_id to deal: $sales_account_id");
        }

        // Set the Deal Data
        $deal_data = [
            'deal' => [
                'name'                  => 'Order #' . $order_number,
                'amount'                => floatval($order->get_total()),
                'sales_account_id'      => $sales_account_id,
                'owner_id'              => '22000193430', // ID of "Website Orders" user in Freshsales
                'deal_stage_id'         => $stage_id,
                'contacts_added_list'   => [$contact_id],
                'custom_field'          => $custom_fields
            ]
        ];
    
        $this->custom_log('[Freshsales Sync] Deal Data: ' . print_r($deal_data, true));
        $response = $this->post('deals', $deal_data);

        // If successful, store deal ID into order meta
        if ( $response['success'] && !empty($response['data']['deal']['id']) ) {
            $deal_id = $response['data']['deal']['id'];
            update_post_meta( $order_id, '_freshsales_deal_id', $deal_id );
            $this->custom_log('[Freshsales Sync] Deal ID Saved to Order: ' . $deal_id);

            $mua_values     = [];
            $serial_numbers = [];

            // Add product(s) to the deal
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( ! $product ) {
                    continue;
                }
            
                $sku = $product->get_sku();
                if ( ! $sku ) {
                    continue;
                }
            
                $freshsales_product_id = $this->get_product_id_by_sku($sku);
            
                if ( $freshsales_product_id ) {
                    $products_array[] = [
                        'id'            => $freshsales_product_id,
                        'quantity'      => $item->get_quantity(),
                        'discount'      => 0,
                        'unit_price'    => floatval($item->get_total()) / $item->get_quantity(),
                    ];
                }

                // Get the MUA Value
                $item_id            = $item->get_id();
                $order_item_meta    = wc_get_order_item_meta($item_id, '_tmcartepo_data', true);

                // Get MUA from variation attribute (stored as 'pa_mua' in item meta)
                $value_mua = $item->get_meta('pa_mua');
                if ( $value_mua ) {
                    $value_mua = trim($value_mua);
                    $mua_values[] = $value_mua;
                    $this->custom_log('[Freshsales Sync] MUA Value found: ' . $value_mua);
                }

                // Get ICAM Serial# from product add-on meta
                $icam_sn = $item->get_meta('ICAM Serial#');
                if ( $icam_sn ) {
                    $icam_sn = trim($icam_sn);
                    $serial_numbers[] = $icam_sn;
                    $this->custom_log('[Freshsales Sync] Serial Number found: ' . $icam_sn);
                }

            }

            if ( ! empty($products_array) ) {
                $this->add_products_to_deal($deal_id, $products_array);
            }

            // Update deal with MUAs if found
            if ( ! empty($mua_values) ) {
                $update_data = [
                    'deal' => [
                        'custom_field' => [
                            'cf_mua_value' => implode(', ', $mua_values)
                        ]
                    ]
                ];
                $update_response = $this->put('deals/' . $deal_id, $update_data);
                $this->custom_log('[Freshsales Sync] Updated deal with MUA value: ' . implode(', ', $mua_values) . ' Response: ' . print_r($update_response, true));
            } else {
                $this->custom_log('[Freshsales Sync] No MUA value found for order #' . $order_id);
            }

            // Update deal with Serial Numbers if found
            if ( ! empty($serial_numbers) ) {
                $update_data = [
                    'deal' => [
                        'custom_field' => [
                            'cf_serial_number' => implode(', ', $serial_numbers)
                        ]
                    ]
                ];
                $update_response = $this->put('deals/' . $deal_id, $update_data);
                $this->custom_log('[Freshsales Sync] Updated deal with Serial Numbers: ' . implode(', ', $serial_numbers) . ' Response: ' . print_r($update_response, true));
            } else {
                $this->custom_log('[Freshsales Sync] No Serial Numbers found for order #' . $order_id);
            }

        } else {
            $this->custom_log('[Freshsales Sync] Error creating deal: ' . print_r($response, true));
        }

        return $response;
    }

    /**
     * Retrieves the product ID based on the provided SKU.
     *
     * This method searches through all available products to find a match
     * for the given SKU. If a matching SKU is found, the corresponding
     * product ID is returned. If no match is found, or if no products are
     * available, the method returns null.
     *
     * @param string $sku The SKU to search for.
     * 
     * @return int|null The product ID if a matching SKU is found, or null if not found.
     */
    public function get_product_id_by_sku(string $sku): ?int {
        // 1. Fetch all products
        $products = $this->get_all_products();
    
        if (! $products) {
            $this->custom_log('[Freshsales Product Lookup] No products found.');
            return null;
        }
    
        // 2. Search for matching SKU inside sku_number
        foreach ($products as $product) {
            if (isset($product['sku_number']) && strtolower(trim($product['sku_number'])) === strtolower(trim($sku))) {
                return (int) $product['id'];
            }
        }
    
        // 3. Not found
        $this->custom_log('[Freshsales Product Lookup] SKU not found: ' . $sku);
        return null;
    }

    /**
     * Retrieves a list of all products from the Freshsales API.
     *
     * @param int $page The page number to retrieve. Defaults to 1.
     * @param int $per_page The number of products per page. Defaults to 1000. 
     *                      The maximum value allowed is 1000.
     *
     * @return array|null Returns an array of products if the request is successful 
     *                    and products are available, or null otherwise.
     */
    public function get_all_products(int $page = 1, int $per_page = 1000): ?array {
        $params = [
            'page'      => $page,
            'per_page'  => min($per_page, 1000)
        ];
    
        $response = $this->get('/cpq/products', $params);
    
    
        if ($response['success'] && !empty($response['data']['products'])) {
            return $response['data']['products'];
        }
    
        return null;
    }
    
    /**
     * Logs a custom message to a specified log file within the WordPress uploads directory.
     *
     * This method ensures that the log directory exists before writing the log message.
     * The log message is timestamped and appended to the specified log file.
     *
     * @param string $message  The message to log.
     * @param string $filename Optional. The name of the log file. Defaults to 'freshsales-sync.log'.
     *
     * @return void
     */
    public function custom_log(string $message, string $filename = 'freshsales-sync.log') {
        $upload_dir = wp_upload_dir();
        $log_dir    = trailingslashit($upload_dir['basedir']) . 'freshsales-logs/';
    
        // Make sure log directory exists
        if ( ! file_exists($log_dir) ) {
            wp_mkdir_p($log_dir);
        }
    
        // Full path to the log file
        $log_file = $log_dir . $filename;
    
        // Format message
        $timestamp          = date('Y-m-d H:i:s');
        $formatted_message  = "[{$timestamp}] {$message}\n";
    
        // Append to log file
        file_put_contents($log_file, $formatted_message, FILE_APPEND);
    }

    /**
     * Adds products to a specific deal in Freshsales.
     *
     * @param int   $deal_id  The ID of the deal to which products will be added.
     * @param array $products An array of products to associate with the deal.
     *                        Each product should be formatted as required by the Freshsales API.
     *
     * @return array The response from the Freshsales API after adding the products.
     */
    public function add_products_to_deal(int $deal_id, array $products): array {
        $payload = [
            'deal' => [
                'products' => $products
            ]
        ];
    
        $url        = "/deals/{$deal_id}?include=products";
        $response   = $this->put($url, $payload);
    
        $this->custom_log('[Freshsales Sync] Add Products to Deal Payload: ' . print_r($payload, true));
    
        return $response;
    }
    
    /**
     * Updates the stage of a deal in Freshsales.
     *
     * @param int $deal_id The ID of the deal to be updated.
     * @param int $stage_id The ID of the new deal stage.
     * 
     * @return array The response from the Freshsales API.
     */
    public function update_deal_stage(int $deal_id, int $stage_id): array {
        return $this->put("deals/{$deal_id}", [
            'deal' => [
                'deal_stage_id' => $stage_id
            ]
        ]);
    }

    /**
     * Retrieves the account name associated with a given email address from Freshsales.
     *
     * This method performs a search query to Freshsales using the provided email address
     * and returns the name of the associated account, either as a contact's primary sales account
     * or as a sales account directly. If no account is found or an error occurs, it returns null.
     *
     * @param string $email The email address to search for in Freshsales.
     * 
     * @return string|null The name of the associated account, or null if no account is found or an error occurs.
     */
    public function get_account_by_email(string $email): ?string {
        $this->custom_log('[Freshsales Sync] Getting accounts by email ' . $email);
    
        $payload = [
            [
                'q'        => $email,
                'include'  => 'sales_account,contact',
                'per_page' => 100,
            ]
        ];
    
        $response = $this->GET('/search/', $payload);
    
        if (!empty($response['success']) && !empty($response['data'])) {
    
            foreach ($response['data'] as $entry) {
                $this->custom_log('[Freshsales Sync] Looping through entry');
                if ($entry['type'] === 'sales_account') {
                    $this->custom_log('[Freshsales Sync] Using sales_account: ' . $entry['id']);
                    return $entry['id'];
                }
    
                if ($entry['type'] === 'contact' && isset($entry['primary_sales_account_name'])) {
                    $account_name = $entry['primary_sales_account_name'];
                    return $this->get_account_by_name($account_name);
                }
            }
    
        }
    
        $this->custom_log('[Freshsales Sync] No account match found for: ' . $email);
        return null;
    }

    public function get_account_by_name(string $name): ?string {
        $this->custom_log('[Freshsales Sync] Getting Account by name ' . $name);
    
        $payload = [
            [
                'q'        => $name,
                'include'  => 'sales_account',
                'per_page' => 100,
            ]
        ];
    
        $response = $this->GET('/search/', $payload);
    
        if (!empty($response['success']) && !empty($response['data'])) {
            foreach ($response['data'] as $entry) {
                $this->custom_log('[Freshsales Sync] Looping through entry');
                if ($entry['type'] === 'sales_account') {
                    $this->custom_log('[Freshsales Sync] Using sales_account: ' . $entry['id']);
                    return $entry['id'];
                }
            }
        }
    
        $this->custom_log('[Freshsales Sync] No account match found for: ' . $email);
        return null;
    }
    
    

    public function create_account(string $email): ?string {
        $this->custom_log('[Freshsales Sync] Creating new account for: ' . $email);
    
        $account_name = ucfirst(explode('@', $email)[0]);
    
        $payload = [
            'sales_account' => [
                'name' => $account_name,
                'custom_field' => [
                    'cf_email' => $email
                ]
            ]
        ];
    
        $response = $this->POST('/api/sales_accounts', $payload);
    
        if (!empty($response['sales_account']['name'])) {
            $this->custom_log('[Freshsales Sync] Created account: ' . $response['sales_account']['name']);
            return $response['sales_account']['name'];
        }
    
        $this->custom_log('[Freshsales Sync] Failed to create account for: ' . $email);
        return null;
    }
    
    public function get_or_create_account_id_from_order(\WC_Order $order): ?int {
        $user_id = $order->get_user_id();
        $is_distributor = false;
    
        if ($user_id) {
            $user = get_user_by('id', $user_id);
            $is_distributor = $user && in_array('wholesale_customer', (array) $user->roles, true);
        }
    
        // Use shipping info if distributor, else default to billing
        $email  = $is_distributor
            ? $order->get_meta('_shipping_email') ?: $order->get_billing_email()
            : $order->get_billing_email();

        $first  = $is_distributor ? $order->get_shipping_first_name() : ($order->get_shipping_first_name() ?: $order->get_billing_first_name());
        $last   = $is_distributor ? $order->get_shipping_last_name()  : ($order->get_shipping_last_name()  ?: $order->get_billing_last_name());
        $phone  = $is_distributor ? $order->get_meta('_shipping_phone') : ($order->get_billing_phone() ?: '');

        $full_name = trim("$first $last");
    
        // Try to get existing account
        $account_id = $this->get_account_by_email($email);
        if ($account_id) {
            $this->custom_log("[Freshsales Sync] Found account ID: $account_id for email: $email");
            return (int) $account_id;
        }
    
        // Create new account if not found
        $payload = [
            'sales_account' => [
                'name' => $full_name ?: ucfirst(explode('@', $email)[0]),
                'phone' => $phone,
                'custom_field' => [
                    'cf_email' => $email
                ]
            ]
        ];
    
        $response = $this->post('sales_accounts', $payload);
    
        if (!empty($response['data']['sales_account']['id'])) {
            $this->custom_log('[Freshsales Sync] Created new sales account: ' . print_r($response['data']['sales_account']['id'], true));
            return (int) $response['data']['sales_account']['id'];
        }
    
        $this->custom_log('[Freshsales Sync] Failed to create account for: ' . $email . ' - Response: ' . print_r($response, true));
        return null;
    }

}
