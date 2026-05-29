=== ApparelHub Shipping for WooCommerce ===
Contributors: apparelhub
Tags: woocommerce, shipping, print on demand, printful, printify
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 9.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Live shipping rates at checkout for ApparelHub products: Printful items priced live, Printify items at a flat rate you set in ApparelHub.

== Description ==

A single WooCommerce store can carry products from more than one fulfillment provider. This plugin gives each provider the right shipping treatment at checkout:

* Printful items are priced with live, auto calculated shipping.
* Printify items use a flat rate you set in your ApparelHub dashboard.

You manage the per provider rules in ApparelHub, so you never edit code or rate tables in WordPress. If the rate service is briefly unreachable, the plugin uses a configurable flat fallback so checkout always works.

Tax is handled by WooCommerce automated tax, configured to your registrations. This plugin is shipping only.

== Requirements ==

* WordPress 6.0 or newer
* WooCommerce 7.0 or newer
* PHP 7.4 or newer
* An ApparelHub account with a connected WooCommerce store and a shipping token

== Installation ==

1. Upload and activate the plugin (Plugins > Add New > Upload Plugin).
2. Go to WooCommerce > Settings > ApparelHub, paste your shipping token, and click Test connection.
3. Go to WooCommerce > Settings > Shipping, open your zone, and add the ApparelHub Shipping method.

See the included docs/INSTALL.md for a full walkthrough.

== Frequently Asked Questions ==

= Does this handle tax? =

No. Tax is handled by WooCommerce automated tax. This plugin is shipping only.

= What happens if ApparelHub cannot be reached at checkout? =

The plugin shows a configurable flat fallback rate so the customer can still check out. Orders that used the fallback are flagged in ApparelHub for review.

= Where do I set the Printful and Printify rates? =

In your ApparelHub dashboard, under your store's shipping settings. The plugin reads those rules at checkout.

== Changelog ==

= 1.0.0 =
* Initial release: live Printful rates and flat Printify rate at WooCommerce checkout, with a flat fallback.
