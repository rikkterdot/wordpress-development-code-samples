<?php

namespace BuiltMighty\IMETF;

class IMETF_MyAccount {

    /**
     * Initialize hooks.
     */
    public function __construct() {
        // Add menu item and endpoint.
        add_filter('woocommerce_account_menu_items', array( $this, 'add_account_menu_items' ), 20, 1 );
        add_action('init', [$this, 'register_account_endpoint']);
        add_action('woocommerce_account_my-products_endpoint', [$this, 'display_purchased_products']);
    }

    /**
     * Add "Purchased Products" menu item.
     *
     * @param array $items Existing menu items.
     * @return array Modified menu items.
     */
    public function add_account_menu_items($items) {
        $logout = $items['customer-logout'];
        
        unset($items['customer-logout']); // Temporarily remove logout.
        unset($items['downloads']);
        $items['my-products'] = __('My Products', 'imetr_finale');
        $items['customer-logout'] = $logout; // Re-add logout at the end.
        
        return $items;
    }

    /**
     * Register endpoint for "Purchased Products".
     */
    public function register_account_endpoint() {
        add_rewrite_endpoint('my-products', EP_ROOT | EP_PAGES);
    }

    /**
     * Display "Purchased Products" content on the endpoint.
     */
    public function display_purchased_products() {
        $customer_id = get_current_user_id();
        $orders = wc_get_orders([
            'customer' => $customer_id,
            'limit'    => -1,
        ]);
    
        if (!$orders) {
            echo '<p>' . __('No purchased products found.', 'imetr_finale') . '</p>';
            return;
        }
    
        echo '<table class="shop_table shop_table_responsive my_account_orders">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Product Name', 'imetr_finale') . '</th>';
        echo '<th>' . __('Order #', 'imetr_finale') . '</th>';
        echo '<th>' . __('Serial Number', 'imetr_finale') . '</th>';
        echo '<th>' . __('Warranty Start Date', 'imetr_finale') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
    
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_name           = $item->get_name();
                $order_number           = $order->get_order_number();
                $item_id                = $item->get_id();
                $order_link             = esc_url($order->get_view_order_url());
                $serial_number          = wc_get_order_item_meta($item_id, '_serial_number', true);
                //$serial_number          = is_array($serial_number) ? implode(', ', $serial_number) : $serial_number; 
                $warranty_start_date    = wc_get_order_item_meta($item_id, '_warranty_start_date', true);
                $quantity               = $item->get_quantity();
    
                // Fetch ICAM Serial# from order item meta
                $order_item_meta    = wc_get_order_item_meta($item_id, '_tmcartepo_data', true);
                $icam_serial        = '';
    
                if (is_array($order_item_meta)) {
                    foreach ($order_item_meta as $meta) {
                        if (isset($meta['name']) && $meta['name'] === 'ICAM Serial#') {
                            $icam_serial = $meta['value']; // Retrieve the value for ICAM Serial#
                            break;
                        }
                    }
                }
                
                // Display each unit as a separate row
                $serial_numbers = is_array($serial_number) ? $serial_number : [$serial_number];
                for ($i = 0; $i < $quantity; $i++) {
                    $this_serial = $serial_numbers[$i] ?? __('N/A', 'imetr_finale');

                    echo '<tr>';
                    echo '<td>' . esc_html($product_name) . '<br/><span style="font-size:10px;">ICAM Serial Number: ' . esc_html($icam_serial) . '</span></td>';
                    echo '<td><a href="' . $order_link . '">#' . esc_html($order_number) . '</a></td>';
                    echo '<td>' . esc_html($this_serial ?: __('N/A', 'imetr_finale')) . '</td>';
                    echo '<td>' . esc_html($warranty_start_date ?: __('N/A', 'imetr_finale')) . '</td>';
                    echo '</tr>';
                }
            }
        }
    
        echo '</tbody>';
        echo '</table>';
    }
    
}
