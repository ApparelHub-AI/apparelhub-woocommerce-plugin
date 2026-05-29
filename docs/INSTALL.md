# Install and setup guide

This guide takes you from zero to live per provider shipping on your WooCommerce store.

## Before you start

Make sure you have:

- A WooCommerce store (WordPress 6.0+, WooCommerce 7.0+, PHP 7.4+).
- WooCommerce automated tax already set up for your registrations. This plugin does not handle tax.
- An ApparelHub account with your WooCommerce store connected.
- Your ApparelHub **shipping token** (see Step 2).

## Step 1: Install the plugin

1. Download the latest `apparelhub-shipping.zip` from the [Releases](https://github.com/ApparelHub-AI/apparelhub-woocommerce-plugin/releases) page.
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**.
3. Choose the zip, click **Install Now**, then **Activate**.

If you see a notice that WooCommerce is required, install and activate WooCommerce first.

## Step 2: Get your shipping token

1. Sign in to ApparelHub.
2. Open your store's shipping settings.
3. Copy the **shipping token**. This token only allows shipping quotes for this one store, nothing else. You can rotate it any time, which immediately invalidates the old one.

While you are there, set your per provider rules:

- Printful: live rates, with a fallback flat amount.
- Printify: a flat rate (first item and each additional item).

These rules live in ApparelHub, so you manage them in one place and never edit anything in WordPress.

## Step 3: Connect the plugin

1. In WordPress, go to **WooCommerce > Settings > ApparelHub**.
2. Leave **ApparelHub API URL** at the default unless support tells you otherwise.
3. Paste your **Shipping token**.
4. Click **Save changes**, then **Test connection**.
5. You should see "Connected to ApparelHub." If you see a token error, copy a fresh token and try again.

## Step 4: Turn on the shipping method

1. Go to **WooCommerce > Settings > Shipping**.
2. Open the shipping zone where you want live rates (for example, your country, or a broader region).
3. Click **Add shipping method**, choose **ApparelHub Shipping**, and add it.
4. Click the method to edit it:
   - **Label shown to customers**: what the shipping line says at checkout (default "Shipping").
   - **Fallback: first item** and **each additional item**: only used if ApparelHub is briefly unreachable, so checkout never breaks. Set these to a sensible flat amount.
   - **Cache minutes**, **Request timeout**, **Debug logging**: leave the defaults unless you have a reason to change them.
5. Save.

Repeat for any other zones you want to cover.

## Step 5: Test a checkout

1. Add a Printful product to the cart and go to checkout. Enter an address. You should see a live shipping amount.
2. Add a Printify product. The total shipping should reflect your flat Printify rate added on top.
3. A cart with both providers shows one combined shipping amount.

## How shipping is calculated

When a customer enters their address, the plugin sends the cart items and destination to ApparelHub. ApparelHub prices the Printful items live and the Printify items at your flat rate, then returns one shipping amount. The customer pays that amount.

If ApparelHub cannot respond within the timeout, the plugin shows your fallback flat rate so the customer can still check out. Orders that used the fallback are flagged in ApparelHub so you can review them.

## Troubleshooting

**No shipping options at checkout**

- Confirm the ApparelHub Shipping method is added to the zone that matches the customer's address.
- Confirm the method is enabled.
- Run **Test connection** under WooCommerce > Settings > ApparelHub.

**Shipping always shows the fallback amount**

- Your token may be missing or rejected. Re-copy it from ApparelHub and Test connection.
- Turn on **Debug logging** on the method, reproduce the checkout, then check **WooCommerce > Status > Logs** (source `apparelhub-shipping`) for the reason.

**Wrong amounts**

- Per provider rates are set in ApparelHub, not in WordPress. Update them in your ApparelHub dashboard.
- The fallback amounts in the method settings only apply when ApparelHub is unreachable.

**Mixed cart behavior**

- A cart with Printful and Printify items shows a single combined shipping line. The breakdown is kept in ApparelHub for your records.
