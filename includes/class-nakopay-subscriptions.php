<?php
/**
 * NakoPay WooCommerce - Subscription webhook handler.
 *
 * Handles subscription lifecycle webhooks from NakoPay:
 *   - subscription.created
 *   - subscription.renewed
 *   - subscription.past_due
 *   - subscription.updated
 *   - subscription.canceled
 *
 * If WooCommerce Subscriptions is active, this maps NakoPay subscription
 * events to WC_Subscription status changes. Otherwise it logs the events
 * as order notes for manual handling.
 */

if (!defined('ABSPATH') && !defined('NAKOPAY_CLI')) {
    exit;
}

class NakoPay_Subscriptions
{
    /**
     * Handle a subscription webhook event.
     *
     * @param string $event_type  e.g. 'subscription.renewed'
     * @param array  $data        The event data payload
     * @return bool
     */
    public static function handle_event(string $event_type, array $data): bool
    {
        $subscription_id = $data['id'] ?? '';
        if (empty($subscription_id)) {
            return false;
        }

        // Find WC orders linked to this NakoPay subscription
        $orders = wc_get_orders([
            'meta_key'   => '_nakopay_subscription_id',
            'meta_value' => $subscription_id,
            'limit'      => 1,
            'orderby'    => 'date',
            'order'      => 'DESC',
        ]);

        switch ($event_type) {
            case 'subscription.created':
                // Log creation; initial invoice handles order creation
                if (!empty($orders)) {
                    $orders[0]->add_order_note(
                        sprintf(
                            /* translators: %s: NakoPay subscription ID */
                            __('NakoPay subscription created: %s', 'nakopay-woocommerce'),
                            $subscription_id
                        )
                    );
                }
                break;

            case 'subscription.renewed':
                $invoice_id = $data['invoice_id'] ?? '';
                if (!empty($orders)) {
                    $orders[0]->add_order_note(
                        sprintf(
                            /* translators: %1$s: subscription ID, %2$s: invoice ID */
                            __('NakoPay subscription %1$s renewed. New invoice: %2$s', 'nakopay-woocommerce'),
                            $subscription_id,
                            $invoice_id
                        )
                    );
                }
                // If WC Subscriptions is active, keep it in active state
                self::update_wc_subscription_status($subscription_id, 'active');
                break;

            case 'subscription.past_due':
                if (!empty($orders)) {
                    $orders[0]->add_order_note(
                        sprintf(
                            /* translators: %s: subscription ID */
                            __('NakoPay subscription %s is past due - payment overdue', 'nakopay-woocommerce'),
                            $subscription_id
                        )
                    );
                }
                self::update_wc_subscription_status($subscription_id, 'on-hold');
                break;

            case 'subscription.canceled':
                $reason = $data['reason'] ?? 'unknown';
                if (!empty($orders)) {
                    $orders[0]->add_order_note(
                        sprintf(
                            /* translators: %1$s: subscription ID, %2$s: reason */
                            __('NakoPay subscription %1$s canceled. Reason: %2$s', 'nakopay-woocommerce'),
                            $subscription_id,
                            $reason
                        )
                    );
                }
                self::update_wc_subscription_status($subscription_id, 'cancelled');
                break;

            case 'subscription.updated':
                if (!empty($orders)) {
                    $orders[0]->add_order_note(
                        sprintf(
                            /* translators: %s: subscription ID */
                            __('NakoPay subscription %s updated', 'nakopay-woocommerce'),
                            $subscription_id
                        )
                    );
                }
                break;

            default:
                return false;
        }

        return true;
    }

    /**
     * Update WooCommerce Subscriptions status if the plugin is active.
     *
     * @param string $nakopay_sub_id  NakoPay subscription ID
     * @param string $wc_status       WC subscription status (active, on-hold, cancelled)
     */
    private static function update_wc_subscription_status(string $nakopay_sub_id, string $wc_status): void
    {
        if (!class_exists('WC_Subscriptions')) {
            return;
        }

        // Look up WC Subscription by NakoPay subscription ID meta
        $wc_subs = wcs_get_subscriptions([
            'meta_key'   => '_nakopay_subscription_id',
            'meta_value' => $nakopay_sub_id,
            'limit'      => 1,
        ]);

        foreach ($wc_subs as $wc_sub) {
            if ($wc_sub->get_status() !== $wc_status) {
                $wc_sub->update_status(
                    $wc_status,
                    sprintf(
                        /* translators: %s: NakoPay subscription ID */
                        __('Status updated via NakoPay webhook (sub: %s)', 'nakopay-woocommerce'),
                        $nakopay_sub_id
                    )
                );
            }
        }
    }
}
