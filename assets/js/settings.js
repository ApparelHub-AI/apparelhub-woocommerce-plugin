/**
 * ApparelHub Shipping: "Test connection" button on WooCommerce > Settings > ApparelHub.
 *
 * Sends the values currently in the form (so the merchant can test before
 * saving) to the plugin's admin-ajax handler and shows the result inline.
 * Strings and the nonce arrive via the apparelhubShippingSettings object.
 */
( function () {
	var config = window.apparelhubShippingSettings;
	var btn = document.getElementById( 'apparelhub-shipping-test' );
	if ( ! btn || ! config ) {
		return;
	}

	btn.addEventListener( 'click', function () {
		var result = document.getElementById( 'apparelhub-shipping-test-result' );
		var urlEl = document.getElementById( 'apparelhub_shipping_base_url' );
		var tokenEl = document.getElementById( 'apparelhub_shipping_token' );

		result.textContent = config.i18n.testing;
		result.style.color = '';

		var data = new FormData();
		data.append( 'action', 'apparelhub_shipping_test_connection' );
		data.append( 'nonce', config.nonce );
		data.append( 'base_url', urlEl ? urlEl.value : '' );
		data.append( 'token', tokenEl ? tokenEl.value : '' );

		fetch( config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( json ) {
				if ( json.data && json.data.message ) {
					result.textContent = json.data.message;
				} else {
					result.textContent = json.success ? config.i18n.ok : config.i18n.failed;
				}
				result.style.color = json.success ? '#1a7f37' : '#b32d2e';
			} )
			.catch( function () {
				result.textContent = config.i18n.unreachable;
				result.style.color = '#b32d2e';
			} );
	} );
} )();
