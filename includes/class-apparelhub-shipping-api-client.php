<?php
/**
 * HTTP client for the ApparelHub shipping rate service.
 *
 * @package ApparelHub\Shipping
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ApparelHub_Shipping_Api_Client
 *
 * Thin wrapper around the WordPress HTTP API for the two endpoints the plugin
 * needs: a ping (connection test) and the shipping-rates quote.
 */
class ApparelHub_Shipping_Api_Client {

	/**
	 * API base URL (no trailing slash), e.g. https://api.apparelhub.ai.
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * Per-store shipping token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	private $timeout;

	/**
	 * Constructor.
	 *
	 * @param string $base_url API root.
	 * @param string $token    Per-store shipping token.
	 * @param int    $timeout  Request timeout in seconds.
	 */
	public function __construct( $base_url, $token, $timeout = 6 ) {
		$this->base_url = untrailingslashit( (string) $base_url );
		$this->token    = (string) $token;
		$this->timeout  = max( 1, (int) $timeout );
	}

	/**
	 * True when both a base URL and a token are configured.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->base_url && '' !== $this->token;
	}

	/**
	 * Build a full service URL for a given path.
	 *
	 * @param string $path Path under the service prefix (leading slash optional).
	 * @return string
	 */
	private function service_url( $path ) {
		return $this->base_url . APPARELHUB_SHIPPING_API_PREFIX . '/' . ltrim( $path, '/' );
	}

	/**
	 * Common request headers, including the token.
	 *
	 * @return array
	 */
	private function headers() {
		return array(
			'Content-Type'                       => 'application/json',
			'Accept'                             => 'application/json',
			APPARELHUB_SHIPPING_TOKEN_HEADER     => $this->token,
		);
	}

	/**
	 * Connection test. Calls the lightweight ping endpoint.
	 *
	 * @return true|WP_Error
	 */
	public function ping() {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'not_configured', __( 'Set the ApparelHub API URL and shipping token first.', 'apparelhub-shipping' ) );
		}

		$response = wp_remote_get(
			$this->service_url( 'ping' ),
			array(
				'timeout' => $this->timeout,
				'headers' => $this->headers(),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 === $code ) {
			return true;
		}
		if ( 401 === $code || 403 === $code ) {
			return new WP_Error( 'unauthorized', __( 'The shipping token was rejected. Copy a fresh token from your ApparelHub dashboard.', 'apparelhub-shipping' ) );
		}

		return new WP_Error(
			'http_error',
			sprintf(
				/* translators: %d: HTTP status code. */
				__( 'Unexpected response from ApparelHub (HTTP %d).', 'apparelhub-shipping' ),
				$code
			)
		);
	}

	/**
	 * Request shipping rates for a set of items + destination.
	 *
	 * @param array  $items       List of array{ sku: string, quantity: int }.
	 * @param array  $destination Array with country, state, postcode, city keys.
	 * @param string $currency    ISO currency code.
	 * @return array|WP_Error Decoded response array, or WP_Error on failure.
	 */
	public function get_rates( $items, $destination, $currency ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'not_configured', __( 'ApparelHub shipping is not configured.', 'apparelhub-shipping' ) );
		}

		$body = wp_json_encode(
			array(
				'destination' => $destination,
				'items'       => array_values( $items ),
				'currency'    => $currency,
			)
		);

		$response = wp_remote_post(
			$this->service_url( 'shipping-rates' ),
			array(
				'timeout' => $this->timeout,
				'headers' => $this->headers(),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( 200 !== $code ) {
			$message = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : __( 'Rate request failed.', 'apparelhub-shipping' );
			return new WP_Error( 'rate_http_error', $message, array( 'status' => $code ) );
		}

		if ( ! is_array( $data ) || ! isset( $data['total'] ) ) {
			return new WP_Error( 'rate_bad_payload', __( 'Rate response was not in the expected format.', 'apparelhub-shipping' ) );
		}

		return $data;
	}
}
