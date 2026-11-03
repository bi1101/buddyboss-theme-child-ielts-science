<?php
/** Virtual product checkout rules. */

class Studyieltsvn_Woo_Checkout {
	public function __construct() {
		add_filter( 'woocommerce_get_country_locale', array( $this, 'virtual_address_fields' ), 20 );
		add_filter( 'woocommerce_get_country_locale_default', array( $this, 'virtual_locale' ), 20 );
		add_filter( 'woocommerce_get_country_locale_base', array( $this, 'virtual_locale' ), 20 );
		add_filter( 'woocommerce_customer_get_billing_first_name', array( $this, 'account_first_name' ), 10, 2 );
		add_filter( 'woocommerce_customer_get_billing_last_name', array( $this, 'account_last_name' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'hide_country_selector' ), 20 );
	}

	private function is_virtual_cart() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return false;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( ! $item['data']->is_virtual() ) {
				return false;
			}
		}
		return true;
	}

	public function virtual_address_fields( $locales ) {
		if ( ! $this->is_virtual_cart() ) {
			return $locales;
		}
		foreach ( array_keys( WC()->countries->get_countries() ) as $country ) {
			$locales[ $country ] = $this->virtual_locale( $locales[ $country ] ?? array() );
		}
		return $locales;
	}

	public function virtual_locale( $locale ) {
		if ( $this->is_virtual_cart() ) {
			foreach ( array( 'company', 'address_1', 'address_2', 'city', 'state', 'postcode' ) as $field ) {
				$locale[ $field ]['hidden']   = true;
				$locale[ $field ]['required'] = false;
			}
		}
		return $locale;
	}

	public function hide_country_selector() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ! $this->is_virtual_cart() ) {
			return;
		}
		// WooCommerce requires country for locale resolution; keep its value while omitting the control.
		wp_register_style( 'studyieltsvn-virtual-checkout', false );
		wp_enqueue_style( 'studyieltsvn-virtual-checkout' );
		wp_add_inline_style( 'studyieltsvn-virtual-checkout', '#billing .wc-block-components-country-input{display:none}' );
	}

	public function account_first_name( $name, $customer ) {
		return $this->account_name( $name, $customer, 'first_name' );
	}

	public function account_last_name( $name, $customer ) {
		return $this->account_name( $name, $customer, 'last_name' );
	}

	private function account_name( $name, $customer, $key ) {
		if ( '' !== $name || ! $this->is_virtual_cart() || ! get_current_user_id() || $customer->get_id() !== get_current_user_id() ) {
			return $name;
		}
		return (string) get_user_meta( $customer->get_id(), $key, true );
	}
}

new Studyieltsvn_Woo_Checkout();
