# Changelog - NakoPay for WooCommerce

## 0.1.0 - initial release
- WC payment gateway (BTC) with non-custodial wallet-to-wallet flow.
- HMAC-SHA256 webhook receiver at `/?wc-api=wc_gateway_nakopay`.
- Polling fallback on the receipt page (5s).
- HPOS + WooCommerce Cart/Checkout Blocks compatible.
- Dual base URL strategy: ships with the canonical Supabase functions URL
  (`daslrxpkbkqrbnjwouiq.supabase.co`) and a reserved `api.nakopay.com/v1/`
  fallback constant. Active base resolved from setting → PHP constant
  `NAKOPAY_API_BASE` → primary.
- Custom table `{prefix}nakopay_orders` (schema-versioned).
- Test Connection button on the settings screen.
