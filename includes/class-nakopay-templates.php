<?php
/**
 * Tiny template loader with theme override support.
 * Themes can override by dropping a file into:
 *   wp-content/themes/<theme>/nakopay-woocommerce/<name>.php
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

class NakoPay_Templates
{
    public static function render(string $name, array $vars = []): string
    {
        $name = preg_replace('/[^a-z0-9_\-]/i', '', $name);

        $theme = locate_template(['nakopay-woocommerce/' . $name . '.php']);
        $path  = $theme !== '' ? $theme : NAKOPAY_WC_DIR . 'templates/' . $name . '.php';

        if (!file_exists($path)) {
            return '';
        }

        extract($vars, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
