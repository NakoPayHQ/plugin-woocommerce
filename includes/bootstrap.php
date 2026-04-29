<?php
/**
 * NakoPay WooCommerce - bootstrap.
 *
 * Wires the plugin into WordPress + WooCommerce. All real logic lives in the
 * class files this loads. Keep this file thin and readable.
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

/* ------------------------------------------------------------------ load */

require_once NAKOPAY_WC_DIR . 'includes/class-nakopay-client.php';
require_once NAKOPAY_WC_DIR . 'includes/class-nakopay-orders.php';
require_once NAKOPAY_WC_DIR . 'includes/class-nakopay-templates.php';
require_once NAKOPAY_WC_DIR . 'includes/class-nakopay-setup.php';

/* -------------------------------------------------------- HPOS + Blocks */

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables', NAKOPAY_WC_FILE, true
        );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks', NAKOPAY_WC_FILE, true
        );
    }
});

add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }
    require_once NAKOPAY_WC_DIR . 'includes/class-nakopay-blocks-support.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function ($registry) {
            $registry->register(new NakoPay_Blocks_Support());
        }
    );
});

/* -------------------------------------------------- gateway registration */

add_action('plugins_loaded', function () {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    require_once NAKOPAY_WC_DIR . 'includes/class-wc-gateway-nakopay.php';

    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = 'WC_Gateway_NakoPay';
        return $methods;
    });

    load_plugin_textdomain(
        'nakopay-woocommerce',
        false,
        dirname(plugin_basename(NAKOPAY_WC_FILE)) . '/languages'
    );
});

/* ----------------------------------------------------------- activation */

register_activation_hook(NAKOPAY_WC_FILE, function () {
    NakoPay_Orders::install();
    NakoPay_Setup::on_activate();
});

register_deactivation_hook(NAKOPAY_WC_FILE, function () {
    NakoPay_Setup::on_deactivate();
});

/* ----------------------------------------------------------- admin hint */

add_filter('plugin_action_links_' . plugin_basename(NAKOPAY_WC_FILE), function ($links) {
    $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=nakopay');
    array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'nakopay-woocommerce') . '</a>');
    return $links;
});
