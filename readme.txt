=== NakoPay for WooCommerce ===
Contributors: nakopay
Tags: bitcoin, woocommerce, payments, crypto, lightning
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 0.1.0
License: MIT

Accept Bitcoin in WooCommerce. Wallet-to-wallet, non-custodial, 1% flat fee.

== Description ==

NakoPay adds a Bitcoin (and Lightning, plus 6 other coins) payment method to
your WooCommerce checkout. No custody - funds settle directly to your wallet.

If you only need pay buttons, donation forms, or payment links on a normal WordPress site, install "NakoPay for WordPress" instead.

Features:

* One-time checkout, refunds, subscriptions
* Multi-currency display, tax pass-through
* Test mode (sk_test_*) for sandbox checkouts
* Signed webhooks (HMAC-SHA256, 5-minute replay window)

== Installation ==

Easiest path - upload via WordPress admin:

1. Download `nakopay-woocommerce.zip` from
   https://github.com/NakoPayHQ/plugin-woocommerce/releases/latest/download/nakopay-woocommerce.zip (do NOT unzip).
2. In WordPress admin, go to Plugins -> Add New -> Upload Plugin.
3. Choose the zip, click Install Now, then Activate Plugin.

Or via WP-CLI:

`wp plugin install nakopay-woocommerce --activate`

Or manually via SFTP:

1. Unzip the file on your computer.
2. Upload the `nakopay-woocommerce/` folder to `wp-content/plugins/`.
3. Activate from the Plugins page.

After activating:

1. Get an API key at https://nakopay.com/dashboard/api-keys
2. WooCommerce -> Settings -> Payments -> NakoPay -> Manage
3. Paste the API key, copy the Webhook URL into your NakoPay dashboard
   (Settings -> Webhooks), then paste the signing secret back here.
4. Save.

== Frequently Asked Questions ==

= Do I need a NakoPay account? =

Yes - sign up free at https://nakopay.com. You get test keys instantly and live
keys after a quick verification.

= Does NakoPay hold my funds? =

No. NakoPay is non-custodial. Customer payments settle directly to the wallet
address you configure. NakoPay never touches your money.

= How do I test without spending real Bitcoin? =

Use a `sk_test_*` API key. The plugin runs the full flow against the NakoPay
sandbox and accepts Bitcoin testnet payments - grab funds from any testnet
faucet.

= What if I need help? =

Open an issue at https://github.com/NakoPayHQ/plugin-woocommerce/issues or email
support@nakopay.com.

== Changelog ==

= 0.1.0 =
* Initial release.
