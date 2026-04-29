# NakoPay for WooCommerce

Accept Bitcoin and other crypto in WooCommerce with a one-flat-fee, non-custodial
checkout. Wallet-to-wallet - NakoPay never holds your funds.

Use this plugin for **WooCommerce stores** with cart and checkout flows. If you only need pay buttons, donation forms, or payment links on a normal WordPress site, install [NakoPay for WordPress](https://github.com/NakoPayHQ/plugin-wordpress) instead.

[![Status](https://img.shields.io/badge/status-stable-blue)](https://nakopay.com/integrations)
[![License](https://img.shields.io/badge/license-MIT-green)](../LICENSE)

## Requirements

- WordPress 6.0+
- WooCommerce 8.0+
- PHP 8.0+
- A NakoPay account (free) and at least one API key from <https://nakopay.com/dashboard/api-keys>

## Download

Pick whichever source is easiest for you. Use the GitHub Releases zip until the WordPress.org listing is live.

| # | Source | When to use |
|---|--------|-------------|
| 1 | **GitHub Releases zip** - <https://github.com/NakoPayHQ/plugin-woocommerce/releases/latest/download/nakopay-woocommerce.zip> | Available today. Download the `nakopay-woocommerce.zip` asset. |
| 2 | **WordPress.org Plugin Directory** - search "NakoPay" in your WP admin (`Plugins -> Add New`) | Use after the listing is approved. |
| 3 | **Build from source** | For developers. See "Build from source" at the bottom. |

## Install

You only need to do **one** of the methods below. Method A is what 90% of users do.

### Method A - Upload via WordPress admin (recommended)

1. Download `nakopay-woocommerce.zip` from option 2 above (do **not** unzip it).
2. Log in to your WordPress admin.
3. Go to **Plugins -> Add New -> Upload Plugin** (button at the top).
4. Click **Choose File**, pick the zip, then click **Install Now**.
5. When the upload finishes, click **Activate Plugin**.

### Method B - WP-CLI (one command)

Available once the plugin is approved on wp.org:

```bash
wp plugin install nakopay-woocommerce --activate
```

Until then, install the zip with:

```bash
wp plugin install /path/to/nakopay-woocommerce.zip --activate
```

### Method C - SFTP / cPanel File Manager (manual)

For locked-down hosts that disable the admin uploader.

1. Unzip `nakopay-woocommerce.zip` on your computer. You'll get a folder called `nakopay-woocommerce/`.
2. Connect to your site via SFTP (FileZilla, Cyberduck, etc.) or open cPanel -> File Manager.
3. Upload the **whole folder** to `wp-content/plugins/` so the final path is:
   ```
   wp-content/plugins/nakopay-woocommerce/nakopay-woocommerce.php
   ```
4. In WordPress admin, go to **Plugins**, find **NakoPay for WooCommerce**, click **Activate**.

## Configure

1. Get an API key (or two): <https://nakopay.com/dashboard/api-keys>. Create both a **test** key (`sk_test_...`) and a **live** key (`sk_live_...`).
2. In WordPress admin, go to **WooCommerce -> Settings -> Payments**.
3. Find **NakoPay** in the list and click **Manage**.
4. Tick **Enable NakoPay**.
5. Paste your **API key** into the API key field.
6. Copy the **Webhook URL** the plugin shows you (looks like `https://your-store.com/?wc-api=nakopay_webhook`).
7. In your NakoPay dashboard, go to **Settings -> Webhooks -> Add endpoint**, paste that URL, subscribe to `invoice.paid`, `invoice.completed`, `invoice.expired`, `invoice.cancelled`. Save.
8. NakoPay shows a **Signing secret** once - copy it back into the WooCommerce plugin's **Webhook secret** field.
9. Click **Save changes**.

## Verify

- Open your storefront, add anything to the cart, go to checkout.
- **NakoPay** should appear as a payment method.
- Place a test order with `sk_test_...` keys - you'll see a QR + address. Pay with testnet and the order should auto-flip to "Processing" within ~10 seconds.

## Test mode

Use `sk_test_*` keys to run the full checkout against the NakoPay sandbox. No real funds move. Flip to `sk_live_*` when you're ready for production.

## Uninstall

1. **Plugins -> Installed Plugins -> NakoPay for WooCommerce -> Deactivate**.
2. Click **Delete** to remove plugin files.
3. The plugin's settings rows in `wp_options` are cleaned up automatically by `uninstall.php`.

## Supported features

- [x] One-time checkout
- [x] Refunds
- [x] Subscriptions
- [x] Multi-currency display
- [x] Tax pass-through
- [x] Test mode

## Build from source

```bash
git clone https://github.com/NakoPayHQ/plugin-woocommerce.git
cd plugin-woocommerce
zip -r nakopay-woocommerce.zip . -x "*.git*" "tests/*" "*.DS_Store"
```

Then install the resulting zip via Method A or C above.

## Local development

See [`../CONTRIBUTING.md`](../CONTRIBUTING.md). Run `bash ../scripts/check-no-internal-urls.sh .` before opening a PR.

## Release

Tag-driven from the monorepo:

```
plugins/scripts/release.sh woocommerce 0.1.0
```

The matching workflow at `.github/workflows/release-woocommerce.yml` handles the upload to the marketplace. Full runbook in [`../PUBLISHING.md`](../PUBLISHING.md).

## Support

- Issues: <https://github.com/NakoPayHQ/plugin-woocommerce/issues>
- Email: support@nakopay.com

## License

MIT - see [`../LICENSE`](../LICENSE).
