<?php
/**
 * Plugin Name:       ApparelHub Shipping for WooCommerce
 * Plugin URI:        https://github.com/ApparelHub-AI/apparelhub-woocommerce-plugin
 * Description:       Live shipping rates at checkout for ApparelHub products. Printful items are auto calculated from live rates; Printify items use a flat rate you configure in ApparelHub. A configurable flat fallback keeps checkout working if the rate service is briefly unreachable.
 * Version:           1.0.0
 * Author:            ApparelHub
 * Author URI:        https://apparelhub.ai
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       apparelhub-shipping
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   9.4
 *
 * @package ApparelHub\Shipping
 */

defined( 'ABSPATH' ) || exit;

define( 'APPARELHUB_SHIPPING_VERSION', '1.0.0' );
define( 'APPARELHUB_SHIPPING_FILE', __FILE__ );
define( 'APPARELHUB_SHIPPING_PATH', plugin_dir_path( __FILE__ ) );
define( 'APPARELHUB_SHIPPING_URL', plugin_dir_url( __FILE__ ) );
define( 'APPARELHUB_SHIPPING_MIN_WC', '7.0' );

// Default ApparelHub API root. The plugin appends the service path
// (/agents/v1/service/woocommerce/...) to whatever root the merchant configures.
define( 'APPARELHUB_SHIPPING_DEFAULT_BASE_URL', 'https://api.apparelhub.ai' );
define( 'APPARELHUB_SHIPPING_API_PREFIX', '/agents/v1/service/woocommerce' );

// Header the plugin sends the per-store shipping token in. Backend resolves
// the WooCommerce integration from this token (least privilege).
define( 'APPARELHUB_SHIPPING_TOKEN_HEADER', 'X-ApparelHub-Shipping-Token' );

require_once APPARELHUB_SHIPPING_PATH . 'includes/class-apparelhub-shipping-plugin.php';

/**
 * Declare compatibility with WooCommerce features before WooCommerce boots:
 * High-Performance Order Storage (HPOS) and the Cart/Checkout Blocks.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', APPARELHUB_SHIPPING_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', APPARELHUB_SHIPPING_FILE, true );
		}
	}
);

/**
 * Boot the plugin once all plugins are loaded (so WooCommerce is available).
 */
add_action(
	'plugins_loaded',
	function () {
		ApparelHub_Shipping_Plugin::instance();
	}
);

/**
 * Activation: seed default options. The WooCommerce dependency is enforced at
 * runtime (admin notice) rather than blocking activation, so the merchant can
 * activate this and WooCommerce in any order.
 */
register_activation_hook(
	__FILE__,
	function () {
		if ( false === get_option( 'apparelhub_shipping_base_url', false ) ) {
			add_option( 'apparelhub_shipping_base_url', APPARELHUB_SHIPPING_DEFAULT_BASE_URL );
		}
		if ( false === get_option( 'apparelhub_shipping_token', false ) ) {
			add_option( 'apparelhub_shipping_token', '' );
		}
	}
);
