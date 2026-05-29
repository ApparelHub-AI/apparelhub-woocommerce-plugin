<?php
/**
 * Connection settings (WooCommerce settings tab) + connection test.
 *
 * @package ApparelHub\Shipping
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ApparelHub_Shipping_Settings
 *
 * Adds an "ApparelHub" tab under WooCommerce > Settings where the merchant
 * enters the ApparelHub API URL and their per-store shipping token, and runs
 * a connection test.
 */
class ApparelHub_Shipping_Settings {

	const TAB_ID = 'apparelhub';

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_' . self::TAB_ID, array( $this, 'output' ) );
		add_action( 'woocommerce_update_options_' . self::TAB_ID, array( $this, 'save' ) );
		add_action( 'woocommerce_admin_field_apparelhub_test_connection', array( $this, 'render_test_connection' ) );
		add_action( 'wp_ajax_apparelhub_shipping_test_connection', array( $this, 'ajax_test_connection' ) );
	}

	/**
	 * Add the settings tab.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs[ self::TAB_ID ] = __( 'ApparelHub', 'apparelhub-shipping' );
		return $tabs;
	}

	/**
	 * Field definitions for the tab.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return array(
			array(
				'title' => __( 'ApparelHub Shipping', 'apparelhub-shipping' ),
				'type'  => 'title',
				'desc'  => __( 'Connect this store to ApparelHub so live Printful rates and your flat Printify rate are returned at checkout. Set the per-provider rates in your ApparelHub dashboard.', 'apparelhub-shipping' ),
				'id'    => 'apparelhub_shipping_section',
			),
			array(
				'title'    => __( 'ApparelHub API URL', 'apparelhub-shipping' ),
				'desc'     => __( 'The ApparelHub API root. Leave the default unless ApparelHub support tells you otherwise.', 'apparelhub-shipping' ),
				'id'       => 'apparelhub_shipping_base_url',
				'type'     => 'text',
				'default'  => APPARELHUB_SHIPPING_DEFAULT_BASE_URL,
				'css'      => 'min-width:320px;',
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Shipping token', 'apparelhub-shipping' ),
				'desc'     => __( 'Copy this from your ApparelHub dashboard, under your store\'s shipping settings.', 'apparelhub-shipping' ),
				'id'       => 'apparelhub_shipping_token',
				'type'     => 'password',
				'default'  => '',
				'css'      => 'min-width:320px;',
				'desc_tip' => true,
			),
			array(
				'type' => 'apparelhub_test_connection',
				'id'   => 'apparelhub_shipping_test_connection',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'apparelhub_shipping_section',
			),
		);
	}

	/**
	 * Render the settings fields.
	 *
	 * @return void
	 */
	public function output() {
		WC_Admin_Settings::output_fields( self::get_settings() );
	}

	/**
	 * Persist the settings fields.
	 *
	 * @return void
	 */
	public function save() {
		WC_Admin_Settings::save_fields( self::get_settings() );
	}

	/**
	 * Custom field: a "Test connection" button + result area + inline script.
	 *
	 * @return void
	 */
	public function render_test_connection() {
		$nonce = wp_create_nonce( 'apparelhub_shipping_test' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html__( 'Connection', 'apparelhub-shipping' ); ?></th>
			<td class="forminp">
				<button type="button" class="button" id="apparelhub-shipping-test"><?php echo esc_html__( 'Test connection', 'apparelhub-shipping' ); ?></button>
				<span id="apparelhub-shipping-test-result" style="margin-left:10px;"></span>
				<script>
				( function () {
					var btn = document.getElementById( 'apparelhub-shipping-test' );
					if ( ! btn ) { return; }
					btn.addEventListener( 'click', function () {
						var result = document.getElementById( 'apparelhub-shipping-test-result' );
						var urlEl = document.getElementById( 'apparelhub_shipping_base_url' );
						var tokenEl = document.getElementById( 'apparelhub_shipping_token' );
						result.textContent = <?php echo wp_json_encode( __( 'Testing...', 'apparelhub-shipping' ) ); ?>;
						result.style.color = '';
						var data = new FormData();
						data.append( 'action', 'apparelhub_shipping_test_connection' );
						data.append( 'nonce', <?php echo wp_json_encode( $nonce ); ?> );
						data.append( 'base_url', urlEl ? urlEl.value : '' );
						data.append( 'token', tokenEl ? tokenEl.value : '' );
						fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } )
							.then( function ( r ) { return r.json(); } )
							.then( function ( json ) {
								result.textContent = json.data && json.data.message ? json.data.message : ( json.success ? 'OK' : 'Failed' );
								result.style.color = json.success ? '#1a7f37' : '#b32d2e';
							} )
							.catch( function () {
								result.textContent = <?php echo wp_json_encode( __( 'Could not reach the site. Try again.', 'apparelhub-shipping' ) ); ?>;
								result.style.color = '#b32d2e';
							} );
					} );
				} )();
				</script>
			</td>
		</tr>
		<?php
	}

	/**
	 * AJAX: test the connection with the values currently entered in the form
	 * (so the merchant can test before saving).
	 *
	 * @return void
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'apparelhub_shipping_test', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do that.', 'apparelhub-shipping' ) ) );
		}

		$base_url = isset( $_POST['base_url'] ) ? esc_url_raw( wp_unslash( $_POST['base_url'] ) ) : '';
		$token    = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

		if ( '' === $base_url ) {
			$base_url = ApparelHub_Shipping_Plugin::get_base_url();
		}
		if ( '' === $token ) {
			$token = ApparelHub_Shipping_Plugin::get_token();
		}

		$client = new ApparelHub_Shipping_Api_Client( $base_url, $token, 8 );
		$result = $client->ping();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Connected to ApparelHub.', 'apparelhub-shipping' ) ) );
	}
}
