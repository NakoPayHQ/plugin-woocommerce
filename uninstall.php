<?php
/**
 * Removes plugin data on uninstall (NOT deactivation).
 */
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nakopay_orders");

delete_option('woocommerce_nakopay_settings');
delete_option('nakopay_db_version');
delete_option('nakopay_webhook_secret_seed');
