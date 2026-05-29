# ApparelHub Shipping for WooCommerce

Live shipping rates at checkout for stores that sell [ApparelHub](https://apparelhub.ai) products through WooCommerce.

A single WooCommerce store can carry products from more than one fulfillment provider. This plugin gives each provider the right shipping treatment at checkout:

- **Printful items** are priced with **live, auto calculated** shipping.
- **Printify items** use a **flat rate** you set in your ApparelHub dashboard.

You set the per provider rules in ApparelHub, so you never edit code or rate tables here. If the rate service is briefly unreachable, the plugin uses a configurable flat fallback so checkout always works.

> Tax is handled by WooCommerce's own automated tax, configured to your registrations. This plugin is shipping only.

## How it works

1. A customer enters their address at checkout.
2. The plugin sends the cart items and destination to ApparelHub using a per store token.
3. ApparelHub prices the Printful items live and the Printify items at your flat rate, then returns a single shipping amount.
4. The customer sees that amount and pays it. If ApparelHub cannot be reached in time, the plugin shows your fallback flat rate instead.

## Requirements

- WordPress 6.0 or newer
- WooCommerce 7.0 or newer
- PHP 7.4 or newer
- An ApparelHub account with a connected WooCommerce store and a shipping token

## Install

1. Download the latest `apparelhub-shipping.zip` from the [Releases](https://github.com/ApparelHub-AI/apparelhub-woocommerce-plugin/releases) page.
2. In WordPress: **Plugins > Add New > Upload Plugin**, choose the zip, install, and activate.
3. Make sure WooCommerce is active and your automated tax is already set up.

## Connect

1. Go to **WooCommerce > Settings > ApparelHub**.
2. Leave **ApparelHub API URL** at the default unless support tells you otherwise.
3. Paste your **Shipping token** (from your ApparelHub dashboard, under your store's shipping settings).
4. Click **Test connection**. You should see "Connected to ApparelHub."

## Enable at checkout

1. Go to **WooCommerce > Settings > Shipping**.
2. Open the shipping zone where you want live rates (for example, your country or a region).
3. Add the **ApparelHub Shipping** method to the zone.
4. Set your fallback amounts (used only if ApparelHub is briefly unreachable) and save.

See [docs/INSTALL.md](docs/INSTALL.md) for a step by step guide with screenshots and troubleshooting.

## Settings reference

Per zone (WooCommerce > Settings > Shipping > your zone > ApparelHub Shipping):

| Setting | Purpose |
| --- | --- |
| Label shown to customers | The shipping label at checkout (default "Shipping"). |
| Fallback: first item | Flat amount for the first item if ApparelHub is briefly unreachable. |
| Fallback: each additional item | Added per additional item in the fallback case. |
| Cache minutes | How long to cache a quote for the same cart and address. 0 disables. |
| Request timeout | Seconds to wait for ApparelHub before using the fallback. |
| Debug logging | Logs rate requests and fallbacks to WooCommerce > Status > Logs. |

## Development

This is an open source plugin (GPL-2.0-or-later). Contributions welcome.

- Coding standards: WordPress Coding Standards (see `phpcs.xml.dist`).
- Run checks locally: `composer install` then `composer run lint`.
- Releases: tag `vX.Y.Z`; CI builds the installable zip and attaches it to the release.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
