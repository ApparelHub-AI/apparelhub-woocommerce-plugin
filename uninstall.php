<?php
/**
 * Uninstall cleanup.
 *
 * Removes plugin options and any cached rate transients when the plugin is
 * deleted from the WordPress admin. Shipping-zone method instances are managed
 * by WooCommerce and are left untouched.
 *
 * @package ApparelHub\Shipping
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'apparelhub_shipping_base_url' );
delete_option( 'apparelhub_shipping_token' );

global $wpdb;

// Remove cached rate transients (apparelhub_ship_*).
$like         = $wpdb->esc_like( '_transient_apparelhub_ship_' ) . '%';
$like_timeout = $wpdb->esc_like( '_transient_timeout_apparelhub_ship_' ) . '%';

// phpcs:disable WordPress.DB.DirectDatabaseQuery
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_timeout ) );
// phpcs:enable WordPress.DB.DirectDatabaseQuery
