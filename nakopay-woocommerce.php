<?php
/**
 * Plugin Name: NakoPay for WooCommerce
 * Plugin URI:  https://nakopay.com/integrations
 * Description: Accept BTC and crypto in WooCommerce. One flat fee, non-custodial, wallet-to-wallet.
 * Version: 0.2.0
 * Author:      NakoPay
 * Author URI:  https://nakopay.com
 * License:     MIT
 * Text Domain: nakopay-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to:      6.5
 * Requires PHP:      8.0
 * WC requires at least: 7.0
 * WC tested up to:      9.4
 * Requires Plugins:  woocommerce
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

define('NAKOPAY_WC_VERSION', '0.2.0');
define('NAKOPAY_WC_FILE', __FILE__);
define('NAKOPAY_WC_DIR', plugin_dir_path(__FILE__));
define('NAKOPAY_WC_URL', plugin_dir_url(__FILE__));

require_once NAKOPAY_WC_DIR . 'includes/bootstrap.php';
