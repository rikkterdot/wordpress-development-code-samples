<?php
/**
 * Standalone WooCommerce -> CRM integration example.
 *
 * Original, generalized example of the same integration patterns used in
 * WooCommerce/Freshsales projects. No client code or credentials are included.
 */

defined( 'ABSPATH' ) || exit;

final class Example_WooCommerce_CRM_Sync {
    private const OPTION_DOMAIN = 'example_crm_domain';
    private const OPTION_TOKEN  = 'example_crm_api_token';

    public static function register(): void {
        add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'sync_order' ), 20, 1 );
    }

    /**
     * Sync a completed order. Repeated hook executions are safe because the
     * remote contact ID is persisted on the order.
     */
    public static function sync_order( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        $email = sanitize_email( $order->get_billing_email() );
        if ( ! is_email( $email ) ) {
            return;
        }

        $contact_id = absint( $order->get_meta( '_example_crm_contact_id', true ) );
        $data       = self::contact_payload( $order );

        if ( $contact_id ) {
            $response = self::request( 'PUT', 'contacts/' . $contact_id, $data );
        } else {
            $response = self::request( 'POST', 'contacts', $data );
        }

        if ( is_wp_error( $response ) ) {
            // Do not write personal/customer data or API tokens to logs.
            wc_get_logger()->warning(
                sprintf( 'CRM sync unsuccessful for order %d: %s', $order_id, $response->get_error_code() ),
                array( 'source' => 'example-crm-sync' )
            );
            return;
        }

        $saved_id = absint( $response['contact']['id'] ?? 0 );
        if ( $saved_id ) {
            $order->update_meta_data( '_example_crm_contact_id', $saved_id );
            $order->save();
        }
    }

    private static function contact_payload( WC_Order $order ): array {
        $user = $order->get_user();
        $wholesale = $user instanceof WP_User
            && in_array( 'wholesale_customer', (array) $user->roles, true );

        $prefix = $wholesale ? 'shipping' : 'billing';
        $get = function ( string $field ) use ( $order, $prefix ): string {
            $method = "get_{$prefix}_{$field}";
            return is_callable( array( $order, $method ) )
                ? sanitize_text_field( (string) $order->{$method}() )
                : '';
        };

        return array(
            'contact' => array(
                'first_name' => $get( 'first_name' ),
                'last_name'  => $get( 'last_name' ),
                'email'      => sanitize_email( $order->get_billing_email() ),
                'address'    => $get( 'address_1' ),
                'city'       => $get( 'city' ),
                'state'      => $get( 'state' ),
                'zipcode'    => $get( 'postcode' ),
                'country'    => $get( 'country' ),
            ),
        );
    }

    /**
     * Minimal JSON API wrapper with defensive response handling.
     *
     * In a production integration, add API-specific contact lookup and retry
     * behavior before choosing POST versus PUT.
     *
     * @return array|WP_Error
     */
    private static function request( string $method, string $endpoint, array $body ) {
        $domain = (string) get_option( self::OPTION_DOMAIN, '' );
        $token  = (string) get_option( self::OPTION_TOKEN, '' );
        if ( ! $domain || ! $token ) {
            return new WP_Error( 'crm_not_configured', 'CRM configuration is missing.' );
        }

        $host = wp_parse_url( 'https://' . $domain, PHP_URL_HOST );
        if ( ! $host || ! preg_match( '/^[a-z0-9.-]+$/i', $host ) ) {
            return new WP_Error( 'crm_invalid_domain', 'Invalid CRM host.' );
        }

        $url = 'https://' . $host . '/api/' . ltrim( $endpoint, '/' );
        $response = wp_safe_remote_request(
            $url,
            array(
                'method'  => $method,
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Token token=' . $token,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ),
                'body' => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );
        $json   = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status < 200 || $status >= 300 || ! is_array( $json ) ) {
            return new WP_Error( 'crm_api_response', 'CRM API returned an unsuccessful response.' );
        }

        return $json;
    }
}

add_action( 'plugins_loaded', array( 'Example_WooCommerce_CRM_Sync', 'register' ) );
