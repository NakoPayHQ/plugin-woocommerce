<?php
/**
 * Lifecycle hooks + admin AJAX (Test Connection).
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

class NakoPay_Setup
{
    public static function on_activate(): void
    {
        if (!get_option('nakopay_webhook_secret_seed')) {
            update_option('nakopay_webhook_secret_seed', wp_generate_password(48, false, false));
        }
    }

    public static function on_deactivate(): void
    {
        // Intentionally non-destructive. Use uninstall.php for full cleanup.
    }

    public static function ajax_test_connection(): void
    {
        check_ajax_referer('nakopay_test_connection', '_wpnonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('Unauthorized', 'nakopay-woocommerce')], 403);
        }

        $gateways = WC()->payment_gateways()->payment_gateways();
        $gw = $gateways['nakopay'] ?? null;
        if (!$gw) {
            wp_send_json_error(['message' => 'Gateway not found.'], 500);
        }

        $client = new NakoPay_Client($gw->settings);
        $resp   = $client->ping();

        if (!empty($resp['_ok'])) {
            wp_send_json_success([
                'message' => __('Connection OK.', 'nakopay-woocommerce'),
                'base'    => $client->getBaseUrl(),
            ]);
        }
        wp_send_json_error([
            'message' => $resp['_error'] ?? sprintf('HTTP %d', $resp['_status'] ?? 0),
            'base'    => $client->getBaseUrl(),
        ]);
    }
}

add_action('wp_ajax_nakopay_test_connection', ['NakoPay_Setup', 'ajax_test_connection']);
