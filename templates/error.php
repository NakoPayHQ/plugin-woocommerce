<?php
/** @var string $message */
if (!defined('ABSPATH')) exit;
?>
<div class="nakopay-error">
  <h2><?php esc_html_e('Payment unavailable', 'nakopay-woocommerce'); ?></h2>
  <p><?php echo esc_html($message); ?></p>
</div>
