<?php
/**
 * NakoPay HTTP client + signature helpers for the WooCommerce plugin.
 *
 * Dual base URL strategy (per project memory: plugin-base-urls):
 *   PRIMARY   - https://api.nakopay.com/v1/                              (active, branded)
 *   FALLBACK  - https://daslrxpkbkqrbnjwouiq.supabase.co/functions/v1/   (origin
 *               Supabase functions URL; declared for transparency and as an
 *               operator escape hatch)
 *
 * Resolution order for the active base:
 *   1) Gateway setting "api_base_url" (advanced field, blank = default)
 *   2) PHP constant NAKOPAY_API_BASE (host-defined, e.g. wp-config.php)
 *   3) self::BASE_PRIMARY
 *
 * Endpoint paths are centralised in resolveEndpoint() so the difference
 * between Supabase functions URLs (`invoices-create`) and the future v1
 * surface (`invoices/create`) lives in ONE place.
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

class NakoPay_Client
{
    const VERSION       = '0.1.0';
    const BASE_PRIMARY  = 'https://api.nakopay.com/v1/';
    const BASE_FALLBACK = 'https://daslrxpkbkqrbnjwouiq.supabase.co/functions/v1/';
    const SIG_TOLERANCE = 300;

    /** @var array gateway settings snapshot */
    private array $settings;

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    /* ------------------------------------------------------------- urls */

    public function getBaseUrl(): string
    {
        $override = trim((string) ($this->settings['api_base_url'] ?? ''));
        if ($override !== '') {
            return rtrim($override, '/') . '/';
        }
        if (defined('NAKOPAY_API_BASE') && is_string(NAKOPAY_API_BASE) && NAKOPAY_API_BASE !== '') {
            return rtrim(NAKOPAY_API_BASE, '/') . '/';
        }
        return self::BASE_PRIMARY;
    }

    /**
     * Map a logical endpoint name to a path appropriate for the active base.
     * Keep this map in sync with the WHMCS plugin so both speak the same names.
     */
    /**
     * v1 paths are pass-through kebab-case on every supported base URL
     * (Supabase functions base today, api.nakopay.com in the future).
     */
    public function resolveEndpoint(string $name): string
    {
        return $name;
    }

    /* --------------------------------------------------------- credentials */

    public function getApiKey(): string
    {
        return trim((string) ($this->settings['api_key'] ?? ''));
    }

    public function getWebhookSecret(): string
    {
        return trim((string) ($this->settings['webhook_secret'] ?? ''));
    }

    public function isTestMode(): bool
    {
        return ($this->settings['testmode'] ?? 'no') === 'yes'
            || str_starts_with($this->getApiKey(), 'sk_test_');
    }

    public function getWebhookUrl(): string
    {
        return WC()->api_request_url('wc_gateway_nakopay');
    }

    /* ---------------------------------------------------------------- HTTP */

    private function request(string $method, string $endpoint, ?array $body = null): array
    {
        $apiKey = $this->getApiKey();
        if ($apiKey === '') {
            return ['_ok' => false, '_status' => 0, '_error' => 'NakoPay API key is not configured.'];
        }

        $url  = $this->getBaseUrl() . ltrim($this->resolveEndpoint($endpoint), '/');
        $args = [
            'method'  => strtoupper($method),
            'timeout' => 20,
            'headers' => [
                'Authorization'    => 'Bearer ' . $apiKey,
                'Accept'           => 'application/json',
                'User-Agent'       => 'NakoPay-WooCommerce/' . self::VERSION,
                'X-NakoPay-Version' => '2025-04-20',
            ],
        ];
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['headers']['Idempotency-Key'] = 'idem_' . bin2hex(random_bytes(16));
            $args['body'] = wp_json_encode($body);
        }

        $resp = wp_remote_request($url, $args);
        if (is_wp_error($resp)) {
            return ['_ok' => false, '_status' => 0, '_error' => $resp->get_error_message()];
        }
        $status = (int) wp_remote_retrieve_response_code($resp);
        $raw    = (string) wp_remote_retrieve_body($resp);
        $json   = json_decode($raw, true);
        if (!is_array($json)) {
            return ['_ok' => false, '_status' => $status, '_error' => 'invalid json', '_raw' => $raw];
        }
        $json['_ok']     = $status >= 200 && $status < 300;
        $json['_status'] = $status;
        return $json;
    }

    public function ping(): array
    {
        return $this->request('GET', 'ping');
    }

    public function createInvoice(array $args): array
    {
        return $this->request('POST', 'invoices-create', [
            'amount'         => (string) $args['amount'],
            'currency'       => strtoupper((string) ($args['currency'] ?? 'USD')),
            'coin'           => strtoupper((string) ($args['coin'] ?? 'BTC')),
            'description'    => (string) ($args['description'] ?? 'WooCommerce order'),
            'customer_email' => (string) ($args['customer_email'] ?? ''),
            'metadata'       => array_filter([
                'wc_order_id' => $args['wc_order_id'] ?? null,
                'source'      => 'woocommerce',
            ], fn($v) => $v !== null && $v !== ''),
        ]);
    }

    public function getInvoice(string $id): array
    {
        return $this->request('GET', 'invoices-get?id=' . rawurlencode($id));
    }

    /* ------------------------------------------------------- webhook sig */

    public function verifyWebhook(string $rawBody, string $sigHeader): bool
    {
        $secret = $this->getWebhookSecret();
        if ($secret === '' || $sigHeader === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $sigHeader) as $kv) {
            $kv = trim($kv);
            if ($kv === '' || strpos($kv, '=') === false) continue;
            [$k, $v] = explode('=', $kv, 2);
            $parts[trim($k)] = trim($v);
        }
        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $t = (int) $parts['t'];
        if (abs(time() - $t) > self::SIG_TOLERANCE) {
            return false;
        }

        $expected = hash_hmac('sha256', $t . '.' . $rawBody, $secret);
        return hash_equals($expected, $parts['v1']);
    }
}
