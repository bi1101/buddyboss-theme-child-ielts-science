<?php
/** Virtual product checkout rules. */

class Studyieltsvn_Woo_Checkout {
	private $store_request = false;
	private $request_stack = array();
	public function __construct() {
		add_filter( 'woocommerce_get_country_locale', array( $this, 'virtual_address_fields' ), 20 );
		add_filter( 'woocommerce_get_country_locale_default', array( $this, 'virtual_locale' ), 20 );
		add_filter( 'woocommerce_get_country_locale_base', array( $this, 'virtual_locale' ), 20 );
		add_filter( 'woocommerce_customer_get_billing_first_name', array( $this, 'account_first_name' ), 10, 2 );
		add_filter( 'woocommerce_customer_get_billing_last_name', array( $this, 'account_last_name' ), 10, 2 );
		add_filter( 'rest_request_before_callbacks', array( $this, 'enter_request' ), 10, 3 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'leave_request' ), 10, 3 );
		add_filter( 'woocommerce_customer_get_billing_country', array( $this, 'billing_country' ) );
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'order_country' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'hide_country_selector' ), 20 );
		add_action( 'template_redirect', array( $this, 'redirect_empty_checkout' ), 5 );
	}

	/** Send empty-checkout visits to the shop instead of showing an empty cart. */
	public function redirect_empty_checkout() {
		if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->is_empty() ) {
			return;
		}

		wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
		exit;
	}

	private function is_virtual_cart() {
		if ( ! ( $this->store_request || ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) ) || ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return false;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( ! $item['data']->is_virtual() ) {
				return false;
			}
		}
		return true;
	}

	public function enter_request( $response, $handler, $request ) {
		$this->request_stack[] = $this->store_request;
		$this->store_request = (bool) preg_match( '#^/wc/store/v[0-9]+/(cart|checkout)(/|$)#', $request->get_route() );
		$this->clear_locale_cache();
		return $response;
	}

	public function leave_request( $response, $handler, $request ) {
		$this->store_request = (bool) array_pop( $this->request_stack );
		$this->clear_locale_cache();
		return $response;
	}

	private function clear_locale_cache() {
		if ( function_exists( 'WC' ) && WC()->countries ) {
			WC()->countries->locale = array();
		}
	}

	public function billing_country( $country ) {
		if ( '' === $country && $this->is_virtual_cart() ) {
			$base = WC()->countries->get_base_country();
			if ( isset( WC()->countries->get_allowed_countries()[ $base ] ) ) {
				return $base;
			}
		}
		return $country;
	}

	public function order_country( $order ) {
		if ( '' === $order->get_billing_country() ) {
			$order->set_billing_country( $this->billing_country( '' ) );
		}
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
		$country = WC()->customer ? WC()->customer->get_billing_country() : '';
		if ( ! isset( WC()->countries->get_allowed_countries()[ $country ] ) ) {
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
