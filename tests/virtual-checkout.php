<?php
/** Run with wp eval-file on a local site with WooCommerce and the child theme active. */
$cart = WC()->cart;
$countries = WC()->countries;
$user_id = get_current_user_id();
$check = function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
try {
	WC()->cart = new WC_Cart();
	$virtual = new WC_Product_Simple();
	$virtual->set_virtual( true );
	WC()->cart->set_cart_contents( array( 'virtual' => array( 'data' => $virtual, 'quantity' => 1 ) ) );
	WC()->countries = new WC_Countries();
	$locales = WC()->countries->get_country_locale();
	foreach ( array( 'US', 'VN', 'AQ', 'default' ) as $country ) {
		foreach ( array( 'address_1', 'address_2', 'city', 'state', 'postcode' ) as $field ) {
			$check( $locales[ $country ][ $field ]['hidden'] && ! $locales[ $country ][ $field ]['required'], 'Virtual address field still required' );
		}
	}
	$admin = get_user_by( 'login', 'admin' );
	wp_set_current_user( $admin->ID );
	$customer = new WC_Customer( $admin->ID );
	$customer->set_billing_first_name( '' );
	$check( get_user_meta( $admin->ID, 'first_name', true ) === $customer->get_billing_first_name(), 'Profile name not prefilled' );
	$customer->set_billing_first_name( 'Edited name' );
	$check( 'Edited name' === $customer->get_billing_first_name(), 'Explicit billing name overwritten' );
	$guest = new WC_Customer();
	$check( '' === $guest->get_billing_first_name(), 'Guest inherited an account name' );
	$order = new WC_Order();
	$order->set_address( array( 'first_name' => 'Test', 'last_name' => 'Buyer', 'country' => 'US', 'email' => 'buyer@example.com' ), 'billing' );
	$controller = new Automattic\WooCommerce\StoreApi\Utilities\OrderController();
	$validate = new ReflectionMethod( $controller, 'validate_addresses' );
	$validate->invoke( $controller, $order, false );
	$physical = new WC_Product_Simple();
	WC()->cart->set_cart_contents( array( 'virtual' => array( 'data' => $virtual, 'quantity' => 1 ), 'physical' => array( 'data' => $physical, 'quantity' => 1 ) ) );
	WC()->countries = new WC_Countries();
	$locales = WC()->countries->get_country_locale();
	$check( empty( $locales['US']['address_1']['hidden'] ), 'Mixed cart lost address collection' );
	$failed = false;
	try {
		$validate->invoke( $controller, $order, false );
	} catch ( Automattic\WooCommerce\StoreApi\Exceptions\RouteException $e ) {
		$failed = true;
	}
	$check( $failed, 'Physical cart accepted an incomplete address' );
	echo "PASS: virtual fields, profile prefill, guest isolation, Store API validation, mixed cart.\n";
} finally {
	WC()->cart = $cart;
	WC()->countries = $countries;
	wp_set_current_user( $user_id );
}
