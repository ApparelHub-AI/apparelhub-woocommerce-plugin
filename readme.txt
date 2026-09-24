=== ApparelHub Shipping for WooCommerce ===
Contributors: apparelhub
Tags: woocommerce, shipping, print on demand, printful, printify
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 11.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Live shipping rates at checkout for print-on-demand products, with the right pricing rule per provider and a flat fallback so checkout never breaks.

== Description ==

If you sell print-on-demand products from more than one fulfillment provider in a single WooCommerce store, shipping gets awkward. Each provider prices it differently, and WooCommerce has one shipping table.

This plugin gives each provider the right treatment at checkout:

* **Printful items** are priced with live, automatically calculated shipping.
* **Printify items** use a flat rate you set once in your ApparelHub dashboard.

You manage the per-provider rules in ApparelHub, so you never edit code or maintain rate tables inside WordPress. Change a rate in one place and every connected store picks it up.

**Checkout keeps working even when the rate service does not.** If ApparelHub is briefly unreachable, the plugin falls back to a flat rate you configure per shipping zone, so a customer is never blocked from completing an order. Orders that used the fallback are flagged in ApparelHub so you can review them.

= What you need =

This plugin is a connector for the ApparelHub platform. It requires an ApparelHub account with a connected WooCommerce store, and it will not return rates without one. The per-provider shipping rules all live in your ApparelHub dashboard.

= What it does not do =

This plugin is shipping only. Tax is handled by WooCommerce automated tax, configured to your own registrations. The plugin does not create products, sync inventory, or process payments.

= Compatibility =

* High Performance Order Storage (HPOS) compatible.
* Cart and Checkout Blocks compatible.
* Works alongside other shipping methods in the same zone.

== Installation ==

1. Upload and activate the plugin (Plugins > Add New > Upload Plugin), or install it from the WordPress plugin directory.
2. Go to WooCommerce > Settings > ApparelHub, paste your shipping token, and click Test connection.
3. Go to WooCommerce > Settings > Shipping, open the zone you want to use, and add the ApparelHub Shipping method.
4. Set the flat fallback amounts on that zone. These are only used if the rate service cannot be reached.

Your shipping token is in your ApparelHub dashboard under your store's shipping settings. The bundled docs/INSTALL.md has a full walkthrough.

== External Services ==

This plugin connects to the ApparelHub API to calculate shipping rates. It will not function without this connection, because the per-provider shipping rules live in your ApparelHub account rather than in WordPress.

**Service:** ApparelHub, operated by ApparelHub.AI, LLC.
**Endpoint:** https://api.apparelhub.ai

The plugin contacts this service in exactly two situations.

**1. Connection test.** When an administrator clicks "Test connection" on the WooCommerce > Settings > ApparelHub screen, the plugin sends a request to `/integrations/woocommerce/ping`. The only data sent is your store's ApparelHub shipping token, so the service can confirm the token is valid.

**2. Shipping rate quote.** When a customer reaches checkout and the ApparelHub Shipping method is active for their zone, the plugin sends a request to `/integrations/woocommerce/shipping-rates`. The data sent is:

* The shipping destination: country, state or region, postcode, and city.
* The cart contents: the SKU and quantity of each physical item. Virtual and downloadable products are excluded.
* The store's currency code.
* Your store's ApparelHub shipping token.

**Data that is never sent:** the customer's name, street address, email address, phone number, and any payment information. The plugin sends only the partial destination listed above, which is the minimum needed to price a shipment.

Rate quotes are cached in your own WordPress database for a configurable number of minutes (10 by default, and you can set it to 0 to disable caching). Caching reduces how often destination data leaves your site during a checkout session.

Your use of the ApparelHub service is governed by its terms and privacy policy:

* Terms of Service: https://apparelhub.ai/terms
* Privacy Policy: https://apparelhub.ai/privacy

== Frequently Asked Questions ==

= Do I need an ApparelHub account? =

Yes. The plugin is a connector to the ApparelHub platform, and the per-provider shipping rules it applies are configured there. Without a connected account and a shipping token, the plugin cannot return rates.

= Does this handle tax? =

No. Tax is handled by WooCommerce automated tax, configured to your own registrations. This plugin is shipping only.

= What happens if ApparelHub cannot be reached at checkout? =

The plugin uses the flat fallback rate you configured on that shipping zone, so the customer can still complete their order. Orders that used the fallback are flagged in ApparelHub for review. You can also tune how long the plugin waits before giving up, in the zone's method settings.

= Where do I set the Printful and Printify rates? =

In your ApparelHub dashboard, under your store's shipping settings. The plugin reads those rules at checkout, so you never maintain a rate table in WordPress.

= What customer data is sent to ApparelHub? =

Only the country, state, postcode and city of the shipping destination, plus the SKU and quantity of each item and your store currency. No name, street address, email, phone or payment data is ever sent. See the External Services section above for the full detail.

= Can I use this alongside other shipping methods? =

Yes. Add it to a zone like any other WooCommerce shipping method. If you offer other options in the same zone, the customer sees them all.

= Is it compatible with HPOS and the checkout blocks? =

Yes. The plugin declares compatibility with High Performance Order Storage and with the Cart and Checkout Blocks.

= Does it work if my store has products from only one provider? =

Yes. The per-provider logic applies to whichever provider your products come from.

== Changelog ==

= 1.0.2 =
* Document the ApparelHub API connection in full, including exactly what data is sent and when.
* Refresh tested-up-to versions for current WordPress and WooCommerce.
* Expand the installation steps and FAQ.

= 1.0.1 =
* Point the shipping endpoints at the public per-store-token path so they work for merchants on any plan (the previous path required an API-enabled tier).

= 1.0.0 =
* Initial release: live Printful rates and flat Printify rate at WooCommerce checkout, with a flat fallback.

== Upgrade Notice ==

= 1.0.2 =
Documentation and compatibility update. No functional changes and no action needed.

= 1.0.1 =
Fixes rate requests for merchants who are not on an API-enabled plan. Recommended for all users.
