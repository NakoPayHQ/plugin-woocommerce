<?php
/**
 * Cart/Checkout Blocks integration for the NakoPay gateway.
 * Bare-bones: registers the method so it shows up in the Blocks checkout.
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class NakoPay_Blocks_Support extends AbstractPaymentMethodType
{
    protected $name = 'nakopay';

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_nakopay_settings', []);
    }

    public function is_active(): bool
    {
        return ($this->settings['enabled'] ?? 'no') === 'yes';
    }

    public function get_payment_method_script_handles(): array
    {
        wp_register_script(
            'nakopay-blocks',
            NAKOPAY_WC_URL . 'assets/js/block.js',
            ['wc-blocks-registry', 'wp-element', 'wp-html-entities', 'wp-i18n'],
            NAKOPAY_WC_VERSION,
            true
        );
        return ['nakopay-blocks'];
    }

    public function get_payment_method_data(): array
    {
        return [
            'title'       => $this->settings['title'] ?? __('NakoPay (Crypto)', 'nakopay-woocommerce'),
            'description' => $this->settings['description'] ?? '',
            'supports'    => ['products'],
        ];
    }
}
