# Changelog - NakoPay for WooCommerce

## 0.2.0
- Default API base URL is now `https://api.nakopay.com/v1/` (branded host).
  The previous Supabase functions URL stays declared as `BASE_FALLBACK` and
  is still selectable via the "API Base URL override" advanced setting or
  the `NAKOPAY_API_BASE` PHP constant - existing installs that pinned a
  custom base keep working with zero changes.
- Fix: the wallet deeplink / QR was always empty because the receipt page
  read `payment_uri` from `invoices-create`, but the API returns the BIP21
  string in `bip21`. Now reads `bip21` first and falls back to `payment_uri`
  for forward compatibility.

## 0.1.1
- Fix release workflow: stage plugin into a properly-named `nakopay-woocommerce/`
  folder before zipping so the GitHub release zip extracts to the canonical
  WordPress plugin directory layout.

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
