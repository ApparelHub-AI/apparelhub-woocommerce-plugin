<?php
/**
 * Main plugin bootstrap.
 *
 * @package ApparelHub\Shipping
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ApparelHub_Shipping_Plugin
 *
 * Wires up the settings tab and the shipping method, and guards against
 * WooCommerce being inactive.
 */
final class ApparelHub_Shipping_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var ApparelHub_Shipping_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Return the singleton instance.
	 *
	 * @return ApparelHub_Shipping_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: load textdomain and either boot or show a dependency notice.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( ! $this->woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		$this->includes();

		// Register the shipping method.
		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_shipping_method' ) );

		// Settings tab (admin only).
		if ( is_admin() ) {
			new ApparelHub_Shipping_Settings();
		}
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'apparelhub-shipping', false, dirname( plugin_basename( APPARELHUB_SHIPPING_FILE ) ) . '/languages' );
	}

	/**
	 * Pull in the class files that depend on WooCommerce being present.
	 *
	 * @return void
	 */
	private function includes() {
		require_once APPARELHUB_SHIPPING_PATH . 'includes/class-apparelhub-shipping-api-client.php';
		require_once APPARELHUB_SHIPPING_PATH . 'includes/class-apparelhub-shipping-settings.php';
		// The shipping method class is loaded lazily inside register_shipping_method
		// so it only loads after WC_Shipping_Method is defined.
	}

	/**
	 * Register the ApparelHub shipping method with WooCommerce.
	 *
	 * @param array $methods Existing shipping methods.
	 * @return array
	 */
	public function register_shipping_method( $methods ) {
		require_once APPARELHUB_SHIPPING_PATH . 'includes/class-apparelhub-shipping-method.php';
		$methods['apparelhub_shipping'] = 'ApparelHub_Shipping_Method';
		return $methods;
	}

	/**
	 * Is WooCommerce active?
	 *
	 * @return bool
	 */
	public function woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Admin notice shown when WooCommerce is not active.
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'ApparelHub Shipping requires WooCommerce to be installed and active.', 'apparelhub-shipping' );
		echo '</p></div>';
	}

	/**
	 * Configured ApparelHub API base URL (no trailing slash).
	 *
	 * @return string
	 */
	public static function get_base_url() {
		$url = (string) get_option( 'apparelhub_shipping_base_url', APPARELHUB_SHIPPING_DEFAULT_BASE_URL );
		return untrailingslashit( trim( $url ) );
	}

	/**
	 * Configured per-store shipping token.
	 *
	 * @return string
	 */
	public static function get_token() {
		return trim( (string) get_option( 'apparelhub_shipping_token', '' ) );
	}

	/**
	 * Build an API client from the stored connection settings.
	 *
	 * @param int $timeout Request timeout in seconds.
	 * @return ApparelHub_Shipping_Api_Client
	 */
	public static function api_client( $timeout = 6 ) {
		return new ApparelHub_Shipping_Api_Client( self::get_base_url(), self::get_token(), (int) $timeout );
	}
}
