<?php
/**
 * Settings form schema for the NakoPay WooCommerce gateway.
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

class NakoPay_Form_Fields
{
    public static function fields(string $webhook_url): array
    {
        return [
            'enabled' => [
                'title'   => __('Enable / Disable', 'nakopay-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable NakoPay (Crypto)', 'nakopay-woocommerce'),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'nakopay-woocommerce'),
                'type'        => 'text',
                'description' => __('Shown to the customer at checkout.', 'nakopay-woocommerce'),
                'default'     => __('Bitcoin / Crypto (NakoPay)', 'nakopay-woocommerce'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'   => __('Description', 'nakopay-woocommerce'),
                'type'    => 'textarea',
                'default' => __('Pay with Bitcoin or another supported crypto. Funds go directly to the merchant wallet - non-custodial.', 'nakopay-woocommerce'),
            ],

            'credentials_section' => [
                'title' => __('Credentials', 'nakopay-woocommerce'),
                'type'  => 'title',
            ],
            'api_key' => [
                'title'       => __('API Key', 'nakopay-woocommerce'),
                'type'        => 'password',
                'description' => __('Use sk_live_* in production, sk_test_* for sandbox.', 'nakopay-woocommerce'),
                'default'     => '',
            ],
            'webhook_secret' => [
                'title'       => __('Webhook Secret', 'nakopay-woocommerce'),
                'type'        => 'password',
                'description' => __('Shown once when you create the webhook in your NakoPay dashboard.', 'nakopay-woocommerce'),
                'default'     => '',
            ],
            'webhook_url' => [
                'title'       => __('Webhook URL', 'nakopay-woocommerce'),
                'type'        => 'text',
                'description' => __('Copy this into your NakoPay dashboard → Webhooks.', 'nakopay-woocommerce'),
                'default'     => $webhook_url,
                'custom_attributes' => ['readonly' => 'readonly'],
            ],
            'testmode' => [
                'title'   => __('Test mode', 'nakopay-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Force test mode (no real funds move)', 'nakopay-woocommerce'),
                'default' => 'no',
            ],

            'advanced_section' => [
                'title'       => __('Advanced', 'nakopay-woocommerce'),
                'type'        => 'title',
                'description' => __('Leave blank unless instructed by NakoPay support.', 'nakopay-woocommerce'),
            ],
            'api_base_url' => [
                'title'       => __('API Base URL override', 'nakopay-woocommerce'),
                'type'        => 'text',
                'description' => __('Blank = default. Set to a self-hosted base when you migrate.', 'nakopay-woocommerce'),
                'default'     => '',
                'placeholder' => NakoPay_Client::BASE_PRIMARY,
            ],
            'order_button_text' => [
                'title'   => __('Place-order button text', 'nakopay-woocommerce'),
                'type'    => 'text',
                'default' => __('Pay with crypto', 'nakopay-woocommerce'),
            ],
        ];
    }
}
