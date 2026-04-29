<?php
/**
 * Customer-facing crypto checkout page.
 * Available variables:
 *   $order        WC_Order
 *   $row          array (DB row from nakopay_orders)
 *   $amount_fiat  float
 *   $currency     string
 */
if (!defined('ABSPATH')) exit;
?>
<div class="nakopay-checkout" data-invoice="<?php echo esc_attr($row['nakopay_invoice_id']); ?>">
  <div class="nakopay-card">
    <header class="nakopay-header">
      <h2><?php esc_html_e('Pay with crypto', 'nakopay-woocommerce'); ?></h2>
      <span class="nakopay-order-id">
        <?php echo esc_html(sprintf(__('Order #%s', 'nakopay-woocommerce'), $order->get_order_number())); ?>
      </span>
    </header>

    <div class="nakopay-amount">
      <div class="nakopay-amount-fiat">
        <?php echo esc_html(number_format((float) $amount_fiat, 2)); ?> <?php echo esc_html($currency); ?>
      </div>
      <?php if (!empty($row['amount_crypto'])): ?>
        <div class="nakopay-amount-crypto">
          <?php echo esc_html(rtrim(rtrim(number_format((float) $row['amount_crypto'], 8, '.', ''), '0'), '.')); ?>
          <?php echo esc_html($row['crypto_code']); ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="nakopay-qr">
      <canvas id="nakopay-qr-canvas" width="220" height="220"></canvas>
    </div>

    <div class="nakopay-row">
      <label><?php esc_html_e('Address', 'nakopay-woocommerce'); ?></label>
      <div class="nakopay-input-row">
        <input id="nakopay-address" type="text" readonly value="<?php echo esc_attr($row['address']); ?>">
        <button type="button" class="nakopay-copy-btn" data-target="nakopay-address">
          <?php esc_html_e('Copy', 'nakopay-woocommerce'); ?>
        </button>
      </div>
    </div>

    <?php if (!empty($row['payment_uri'])): ?>
    <div class="nakopay-row">
      <a id="nakopay-wallet-link" class="nakopay-wallet-btn" href="<?php echo esc_attr($row['payment_uri']); ?>">
        <?php esc_html_e('Open in wallet', 'nakopay-woocommerce'); ?>
      </a>
    </div>
    <?php endif; ?>

    <p id="nakopay-status" class="nakopay-status" data-status="<?php echo esc_attr($row['status']); ?>">
      <?php esc_html_e('Waiting for payment…', 'nakopay-woocommerce'); ?>
    </p>
  </div>
</div>
