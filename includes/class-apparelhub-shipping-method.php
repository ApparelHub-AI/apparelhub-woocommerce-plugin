<?php
/**
 * ApparelHub shipping method.
 *
 * @package ApparelHub\Shipping
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ApparelHub_Shipping_Method
 *
 * A WooCommerce shipping method that asks ApparelHub for a live rate at
 * checkout (Printful items priced live, Printify items at the flat rate the
 * merchant configured in ApparelHub) and falls back to a local flat rate if
 * the rate service is briefly unreachable, so checkout never breaks.
 */
class ApparelHub_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Shipping zone instance id.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'apparelhub_shipping';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'ApparelHub Shipping', 'apparelhub-shipping' );
		$this->method_description = __( 'Live shipping from ApparelHub: Printful items are auto calculated; Printify items use your flat rate. A local flat fallback applies if the rate service is briefly unreachable.', 'apparelhub-shipping' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

		$this->init();
	}

	/**
	 * Load settings.
	 *
	 * @return void
	 */
	public function init() {
		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title', __( 'Shipping', 'apparelhub-shipping' ) );
		$this->enabled = $this->get_option( 'enabled', 'yes' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Instance setting fields. Note: the per-provider live/flat policy lives in
	 * ApparelHub. The only money setting here is the LOCAL fallback, which must
	 * work even when ApparelHub is unreachable.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'enabled'             => array(
				'title'   => __( 'Enable', 'apparelhub-shipping' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable ApparelHub Shipping for this zone', 'apparelhub-shipping' ),
				'default' => 'yes',
			),
			'title'               => array(
				'title'       => __( 'Label shown to customers', 'apparelhub-shipping' ),
				'type'        => 'text',
				'default'     => __( 'Shipping', 'apparelhub-shipping' ),
				'desc_tip'    => true,
				'description' => __( 'The shipping label your customers see at checkout.', 'apparelhub-shipping' ),
			),
			'fallback_first'      => array(
				'title'       => __( 'Fallback: first item', 'apparelhub-shipping' ),
				'type'        => 'text',
				'default'     => '5.99',
				'desc_tip'    => true,
				'description' => __( 'Flat amount used for the first item if ApparelHub is briefly unreachable.', 'apparelhub-shipping' ),
			),
			'fallback_additional' => array(
				'title'       => __( 'Fallback: each additional item', 'apparelhub-shipping' ),
				'type'        => 'text',
				'default'     => '2.50',
				'desc_tip'    => true,
				'description' => __( 'Flat amount added per additional item in the fallback case.', 'apparelhub-shipping' ),
			),
			'cache_minutes'       => array(
				'title'       => __( 'Cache minutes', 'apparelhub-shipping' ),
				'type'        => 'text',
				'default'     => '10',
				'desc_tip'    => true,
				'description' => __( 'How long to cache a quote for the same cart and address. Reduces calls during checkout. Set 0 to disable.', 'apparelhub-shipping' ),
			),
			'timeout'             => array(
				'title'       => __( 'Request timeout (seconds)', 'apparelhub-shipping' ),
				'type'        => 'text',
				'default'     => '6',
				'desc_tip'    => true,
				'description' => __( 'How long to wait for ApparelHub before using the fallback rate.', 'apparelhub-shipping' ),
			),
			'debug'               => array(
				'title'   => __( 'Debug logging', 'apparelhub-shipping' ),
				'type'    => 'checkbox',
				'label'   => __( 'Log rate requests and fallbacks to WooCommerce logs', 'apparelhub-shipping' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * Calculate shipping for a package.
	 *
	 * @param array $package WooCommerce shipping package.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		if ( 'yes' !== $this->enabled ) {
			return;
		}

		$items     = $this->collect_items( $package );
		$total_qty = 0;
		foreach ( $items as $item ) {
			$total_qty += (int) $item['quantity'];
		}

		// Nothing shippable in this package.
		if ( empty( $items ) ) {
			return;
		}

		$destination = $this->collect_destination( $package );
		$currency    = get_woocommerce_currency();
		$cache_min   = absint( $this->get_option( 'cache_minutes', '10' ) );
		$cache_key   = 'apparelhub_ship_' . md5( wp_json_encode( array( $items, $destination, $currency, $this->instance_id, APPARELHUB_SHIPPING_VERSION ) ) );

		// Served from cache?
		if ( $cache_min > 0 ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) && isset( $cached['cost'] ) ) {
				$this->add_apparelhub_rate( (float) $cached['cost'], $package, isset( $cached['meta'] ) ? $cached['meta'] : array() );
				return;
			}
		}

		$timeout = max( 1, absint( $this->get_option( 'timeout', '6' ) ) );
		$client  = ApparelHub_Shipping_Plugin::api_client( $timeout );
		$result  = $client->get_rates( $items, $destination, $currency );

		if ( is_wp_error( $result ) ) {
			$this->log( 'Rate request failed (' . $result->get_error_code() . '): ' . $result->get_error_message() . '. Using fallback.' );
			$this->add_fallback_rate( $package, $total_qty );
			return;
		}

		$cost = isset( $result['total'] ) ? (float) $result['total'] : null;
		if ( null === $cost ) {
			$this->log( 'Rate response missing total. Using fallback.' );
			$this->add_fallback_rate( $package, $total_qty );
			return;
		}

		$meta = array(
			'apparelhub_breakdown'     => isset( $result['breakdown'] ) ? $result['breakdown'] : array(),
			'apparelhub_fallback_used' => ! empty( $result['fallback_used'] ),
		);

		$this->add_apparelhub_rate( $cost, $package, $meta );

		if ( $cache_min > 0 ) {
			set_transient(
				$cache_key,
				array(
					'cost' => $cost,
					'meta' => $meta,
				),
				$cache_min * MINUTE_IN_SECONDS
			);
		}

		if ( ! empty( $result['fallback_used'] ) ) {
			$this->log( 'ApparelHub returned a server-side fallback rate for this quote.' );
		}
	}

	/**
	 * Build the items list (sku + quantity) from a package.
	 *
	 * @param array $package Shipping package.
	 * @return array
	 */
	private function collect_items( $package ) {
		$items = array();
		if ( empty( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
			return $items;
		}

		foreach ( $package['contents'] as $values ) {
			$product = isset( $values['data'] ) ? $values['data'] : null;
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			// Virtual / downloadable products do not ship.
			if ( $product->is_virtual() ) {
				continue;
			}
			$items[] = array(
				'sku'      => (string) $product->get_sku(),
				'quantity' => isset( $values['quantity'] ) ? (int) $values['quantity'] : 1,
			);
		}

		return $items;
	}

	/**
	 * Build the destination payload from a package.
	 *
	 * @param array $package Shipping package.
	 * @return array
	 */
	private function collect_destination( $package ) {
		$dest = isset( $package['destination'] ) ? $package['destination'] : array();
		return array(
			'country'  => isset( $dest['country'] ) ? $dest['country'] : '',
			'state'    => isset( $dest['state'] ) ? $dest['state'] : '',
			'postcode' => isset( $dest['postcode'] ) ? $dest['postcode'] : '',
			'city'     => isset( $dest['city'] ) ? $dest['city'] : '',
		);
	}

	/**
	 * Add the ApparelHub rate to the package.
	 *
	 * @param float $cost    Shipping cost.
	 * @param array $package Shipping package.
	 * @param array $meta    Rate meta data.
	 * @return void
	 */
	private function add_apparelhub_rate( $cost, $package, $meta = array() ) {
		$this->add_rate(
			array(
				'id'        => $this->get_rate_id(),
				'label'     => $this->title,
				'cost'      => max( 0, (float) $cost ),
				'package'   => $package,
				'meta_data' => $meta,
				'calc_tax'  => 'per_order',
			)
		);
	}

	/**
	 * Add the local flat fallback rate.
	 *
	 * @param array $package   Shipping package.
	 * @param int   $total_qty Total item quantity in the package.
	 * @return void
	 */
	private function add_fallback_rate( $package, $total_qty ) {
		$first      = (float) wc_format_decimal( $this->get_option( 'fallback_first', '5.99' ) );
		$additional = (float) wc_format_decimal( $this->get_option( 'fallback_additional', '2.50' ) );
		$extra      = max( 0, (int) $total_qty - 1 );
		$cost       = $first + ( $additional * $extra );

		$this->add_apparelhub_rate(
			$cost,
			$package,
			array( 'apparelhub_fallback_used' => true )
		);
	}

	/**
	 * Write a debug log line when debug logging is enabled.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private function log( $message ) {
		if ( 'yes' !== $this->get_option( 'debug', 'no' ) ) {
			return;
		}
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->debug( $message, array( 'source' => 'apparelhub-shipping' ) );
		}
	}
}
