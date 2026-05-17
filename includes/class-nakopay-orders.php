<?php
/**
 * Custom DB table wrapper. One row per invoice we open with NakoPay.
 *
 * Schema-versioned via the 'nakopay_db_version' option so future column
 * additions can run safely on plugin update.
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

class NakoPay_Orders
{
    const DB_VERSION = '1';
    const OPTION_KEY = 'nakopay_db_version';

    public static function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'nakopay_orders';
    }

    public static function install(): void
    {
        global $wpdb;
        $table   = self::table();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wc_order_id BIGINT UNSIGNED NOT NULL,
            nakopay_invoice_id VARCHAR(64) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            amount_fiat DECIMAL(18,2) NOT NULL DEFAULT 0,
            currency VARCHAR(8) NOT NULL DEFAULT 'USD',
            amount_crypto DECIMAL(24,8) NULL,
            crypto_code VARCHAR(8) NULL,
            address VARCHAR(128) NULL,
            payment_uri TEXT NULL,
            tx_hash VARCHAR(128) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_invoice (nakopay_invoice_id),
            KEY idx_wc_order (wc_order_id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        update_option(self::OPTION_KEY, self::DB_VERSION);
    }

    public static function maybe_upgrade(): void
    {
        if (get_option(self::OPTION_KEY) !== self::DB_VERSION) {
            self::install();
        }
    }

    public static function save(array $row): void
    {
        global $wpdb;
        $table = self::table();
        $row['updated_at'] = current_time('mysql');

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE nakopay_invoice_id = %s",
            $row['nakopay_invoice_id']
        ));
        if ($existing) {
            $wpdb->update($table, $row, ['id' => (int) $existing]);
        } else {
            $row['created_at'] = $row['created_at'] ?? current_time('mysql');
            $wpdb->insert($table, $row);
        }
    }

    public static function find_open_for_order(int $wc_order_id): ?array
    {
        global $wpdb;
        $table = self::table();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE wc_order_id = %d
               AND status NOT IN ('paid','completed','expired','cancelled')
             ORDER BY id DESC LIMIT 1",
            $wc_order_id
        ), ARRAY_A);
        return $row ?: null;
    }

    public static function find_by_invoice(string $invoice_id): ?array
    {
        global $wpdb;
        $table = self::table();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE nakopay_invoice_id = %s LIMIT 1",
            $invoice_id
        ), ARRAY_A);
        return $row ?: null;
    }

    public static function update_status(string $invoice_id, string $status, ?string $tx = null): void
    {
        global $wpdb;
        $data = ['status' => $status, 'updated_at' => current_time('mysql')];
        if ($tx !== null && $tx !== '') {
            $data['tx_hash'] = $tx;
        }
        $wpdb->update(self::table(), $data, ['nakopay_invoice_id' => $invoice_id]);
    }
}
