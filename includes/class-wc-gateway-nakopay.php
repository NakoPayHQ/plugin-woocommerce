<?php
/**
 * The actual WooCommerce payment gateway.
 *
 * Flow:
 *   1) process_payment()      → mark order on-hold, redirect to receipt page.
 *   2) receipt_page()         → call invoices-create, render checkout template.
 *   3) JS polls poll_status() → JSON; on 'paid', JS redirects to thank-you.
 *   4) handle_webhook()       → HMAC-verified, source of truth for completion.
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

require_once NAKOPAY_WC_DIR . 'includes/form-fields.php';

class WC_Gateway_NakoPay extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id                 = 'nakopay';
        $this->method_title       = __('NakoPay (Crypto)', 'nakopay-woocommerce');
        $this->method_description = __('Accept Bitcoin and other crypto. Wallet-to-wallet, non-custodial.', 'nakopay-woocommerce');
        $this->has_fields         = false;
        $this->icon               = NAKOPAY_WC_URL . 'assets/img/logo.png';
        $this->supports           = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title             = $this->get_option('title');
        $this->description       = $this->get_option('description');
        $this->order_button_text = $this->get_option('order_button_text', __('Pay with crypto', 'nakopay-woocommerce'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        add_action('woocommerce_api_wc_gateway_nakopay', [$this, 'handle_requests']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /* -------------------------------------------------------------- admin */

    public function init_form_fields(): void
    {
        $this->form_fields = NakoPay_Form_Fields::fields($this->get_webhook_url());
    }

    public function enqueue_admin_assets($hook): void
    {
        if (!is_string($hook) || strpos($hook, 'wc-settings') === false) return;
        wp_register_script('nakopay-admin', false, [], NAKOPAY_WC_VERSION, true);
        wp_enqueue_script('nakopay-admin');
        wp_add_inline_script('nakopay-admin', $this->test_connection_inline_js());
    }

    private function test_connection_inline_js(): string
    {
        $nonce = wp_create_nonce('nakopay_test_connection');
        $ajax  = admin_url('admin-ajax.php');
        return <<<JS
(function(){
  function inject(){
    var row = document.querySelector('#woocommerce_nakopay_api_key');
    if (!row || document.getElementById('nakopay-test-conn-btn')) return;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'nakopay-test-conn-btn';
    btn.className = 'button';
    btn.style.marginLeft = '8px';
    btn.textContent = 'Test connection';
    var out = document.createElement('span');
    out.id = 'nakopay-test-conn-out';
    out.style.marginLeft = '8px';
    row.parentNode.appendChild(btn);
    row.parentNode.appendChild(out);
    btn.addEventListener('click', function(){
      out.textContent = 'Testing…';
      var fd = new FormData();
      fd.append('action', 'nakopay_test_connection');
      fd.append('_wpnonce', '$nonce');
      fetch('$ajax', { method:'POST', credentials:'same-origin', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(j){
          out.textContent = (j && j.success ? '✓ ' : '✗ ') + ((j && j.data && j.data.message) || 'Unknown');
        })
        .catch(function(e){ out.textContent = '✗ ' + e.message; });
    });
  }
  if (document.readyState !== 'loading') inject(); else document.addEventListener('DOMContentLoaded', inject);
})();
JS;
    }

    /* ------------------------------------------------------ payment flow */

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return ['result' => 'failure'];
        }
        $order->update_status('on-hold', __('Awaiting NakoPay crypto payment.', 'nakopay-woocommerce'));
        WC()->cart->empty_cart();
        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }

    public function receipt_page($order_id): void
    {
        NakoPay_Orders::maybe_upgrade();
        $order = wc_get_order($order_id);
        if (!$order) {
            echo esc_html__('Order not found.', 'nakopay-woocommerce');
            return;
        }

        $row = NakoPay_Orders::find_open_for_order((int) $order_id);
        if (!$row) {
            $client = new NakoPay_Client($this->settings);
            $resp = $client->createInvoice([
                'amount'         => (string) $order->get_total(),
                'currency'       => $order->get_currency(),
                'coin'           => 'BTC',
                'description'    => sprintf(__('WooCommerce order #%s', 'nakopay-woocommerce'), $order->get_order_number()),
                'customer_email' => $order->get_billing_email(),
                'wc_order_id'    => $order_id,
            ]);
            if (empty($resp['_ok']) || empty($resp['id'])) {
                $msg = $resp['_error'] ?? __('Could not open NakoPay invoice. Please try again or pick another payment method.', 'nakopay-woocommerce');
                echo NakoPay_Templates::render('error', ['message' => $msg]);
                return;
            }
            NakoPay_Orders::save([
                'wc_order_id'        => $order_id,
                'nakopay_invoice_id' => (string) $resp['id'],
                'status'             => (string) ($resp['status'] ?? 'pending'),
                'amount_fiat'        => (float) $order->get_total(),
                'currency'           => $order->get_currency(),
                'amount_crypto'      => isset($resp['amount_crypto']) ? (float) $resp['amount_crypto'] : null,
                'crypto_code'        => isset($resp['coin']) ? strtoupper((string) $resp['coin']) : 'BTC',
                'address'            => (string) ($resp['address'] ?? ''),
                'payment_uri'        => (string) ($resp['payment_uri'] ?? ''),
            ]);
            $row = NakoPay_Orders::find_open_for_order((int) $order_id);
        }

        wp_enqueue_style('nakopay-order', NAKOPAY_WC_URL . 'assets/css/order.css', [], NAKOPAY_WC_VERSION);
        wp_enqueue_script('nakopay-qrious', NAKOPAY_WC_URL . 'assets/js/vendors/qrious.min.js', [], NAKOPAY_WC_VERSION, true);
        wp_enqueue_script('nakopay-checkout', NAKOPAY_WC_URL . 'assets/js/checkout.js', ['nakopay-qrious'], NAKOPAY_WC_VERSION, true);

        $poll_url   = add_query_arg([
            'wc-api'  => 'wc_gateway_nakopay',
            'action'  => 'poll',
            'invoice' => $row['nakopay_invoice_id'],
        ], home_url('/'));
        $finish_url = $this->get_return_url($order);

        wp_add_inline_script('nakopay-checkout',
            'window.NAKOPAY = ' . wp_json_encode([
                'pollUrl'    => $poll_url,
                'finishUrl'  => $finish_url,
                'address'    => $row['address'],
                'amount'     => $row['amount_crypto'],
                'bip21'      => $row['payment_uri'],
                'coin'       => $row['crypto_code'],
            ]) . ';',
            'before'
        );

        echo NakoPay_Templates::render('checkout', [
            'order'       => $order,
            'row'         => $row,
            'amount_fiat' => $order->get_total(),
            'currency'    => $order->get_currency(),
        ]);
    }

    /* ---------------------------------------------- wc-api dispatch table */

    public function handle_requests(): void
    {
        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';
        if ($action === 'poll') {
            $this->handle_poll();
            return;
        }
        $this->handle_webhook();
    }

    private function handle_poll(): void
    {
        nocache_headers();
        header('Content-Type: application/json');
        $invoice_id = isset($_GET['invoice']) ? sanitize_text_field((string) $_GET['invoice']) : '';
        $row = $invoice_id !== '' ? NakoPay_Orders::find_by_invoice($invoice_id) : null;
        if (!$row) {
            echo wp_json_encode(['status' => 'unknown']);
            exit;
        }

        // Refresh from API on every poll - webhook is canonical, but polling
        // provides a fallback for hosts that block inbound webhooks.
        $client = new NakoPay_Client($this->settings);
        $resp   = $client->getInvoice($invoice_id);
        if (!empty($resp['_ok']) && !empty($resp['status'])) {
            $status = (string) $resp['status'];
            if ($status !== $row['status']) {
                NakoPay_Orders::update_status($invoice_id, $status, (string) ($resp['tx_hash'] ?? ''));
                if (in_array($status, ['paid', 'completed'], true)) {
                    $order = wc_get_order((int) $row['wc_order_id']);
                    if ($order && !$order->is_paid()) {
                        $order->payment_complete($invoice_id);
                        $order->add_order_note(sprintf('NakoPay payment confirmed (%s).', $invoice_id));
                    }
                }
                $row['status'] = $status;
            }
        }

        $payload = ['status' => $row['status']];
        if (in_array($row['status'], ['paid', 'completed'], true)) {
            $order = wc_get_order((int) $row['wc_order_id']);
            if ($order) $payload['redirect'] = $this->get_return_url($order);
        }
        echo wp_json_encode($payload);
        exit;
    }

    private function handle_webhook(): void
    {
        $raw    = (string) file_get_contents('php://input');
        $sig    = isset($_SERVER['HTTP_X_NAKOPAY_SIGNATURE']) ? (string) $_SERVER['HTTP_X_NAKOPAY_SIGNATURE'] : '';
        $client = new NakoPay_Client($this->settings);

        if (!$client->verifyWebhook($raw, $sig)) {
            status_header(401);
            echo 'invalid signature';
            exit;
        }

        $event = json_decode($raw, true);
        if (!is_array($event) || empty($event['data']['id'])) {
            status_header(400);
            echo 'invalid payload';
            exit;
        }

        $invoice_id = (string) $event['data']['id'];
        $status     = (string) ($event['data']['status'] ?? 'pending');
        $tx         = (string) ($event['data']['tx_hash'] ?? '');

        $row = NakoPay_Orders::find_by_invoice($invoice_id);
        if (!$row) {
            status_header(404);
            echo 'unknown invoice';
            exit;
        }

        NakoPay_Orders::update_status($invoice_id, $status, $tx);
        if (in_array($status, ['paid', 'completed'], true)) {
            $order = wc_get_order((int) $row['wc_order_id']);
            if ($order && !$order->is_paid()) {
                $order->payment_complete($invoice_id);
                $order->add_order_note(sprintf('NakoPay webhook: payment confirmed (%s).', $invoice_id));
            }
        }
        status_header(200);
        echo 'ok';
        exit;
    }

    private function get_webhook_url(): string
    {
        return WC()->api_request_url('wc_gateway_nakopay');
    }
}
