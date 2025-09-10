<?php
/**
 * @package BuddyBoss Child
 * The parent theme functions are located at /buddyboss-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */


/****************************** THEME SETUP ******************************/

/**
 * Sets up theme for translation
 *
 * @since BuddyBoss Child 1.0.0
 */
function buddyboss_theme_child_languages() {
	/**
	 * Makes child theme available for translation.
	 * Translations can be added into the /languages/ directory.
	 */

	// Translate text from the PARENT theme.
	load_theme_textdomain( 'buddyboss-theme', get_stylesheet_directory() . '/languages' );

	// Translate text from the CHILD theme only.
	// Change 'buddyboss-theme' instances in all child theme files to 'buddyboss-theme-child'.
	// load_theme_textdomain( 'buddyboss-theme-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'buddyboss_theme_child_languages' );

/**
 * Enqueues scripts and styles for child theme front-end.
 *
 * @since Boss Child Theme  1.0.0
 */
function buddyboss_theme_child_scripts_styles() {
	/**
	 * Scripts and Styles loaded by the parent theme can be unloaded if needed
	 * using wp_deregister_script or wp_deregister_style.
	 *
	 * See the WordPress Codex for more information about those functions:
	 * http://codex.wordpress.org/Function_Reference/wp_deregister_script
	 * http://codex.wordpress.org/Function_Reference/wp_deregister_style
	 */

	// Styles
	wp_enqueue_style( 'buddyboss-child-css', get_stylesheet_directory_uri() . '/assets/css/custom.css' );

	// Javascript
	wp_enqueue_script( 'buddyboss-child-js', get_stylesheet_directory_uri() . '/assets/js/custom.js' );
}
add_action( 'wp_enqueue_scripts', 'buddyboss_theme_child_scripts_styles', 9999 );


/****************************** CUSTOM FUNCTIONS */


// Add custom fields to WooCommerce product editor
add_action( 'woocommerce_product_options_advanced', 'add_custom_advanced_fields' );
add_action( 'woocommerce_product_after_variable_attributes', 'add_custom_discount_field', 1, 3 ); // Lower priority to move fields to top
add_action( 'woocommerce_process_product_meta', 'save_custom_advanced_fields' );
add_action( 'woocommerce_save_product_variation', 'save_custom_text_field', 10, 2 );

// Display custom fields in the Advanced tab
function add_custom_advanced_fields() {
	global $product_object;

	// Display template selection dropdown
	if ( $product_object->get_type() == 'variable-subscription' ) {
		echo '<div class="options_group">';

		// Custom template selection
		woocommerce_wp_select(
			array(
				'id'          => '_custom_template_selection',
				'label'       => __( 'Select Custom Template', 'woocommerce' ),
				'description' => __( 'Choose a template layout for this product.', 'woocommerce' ),
				'options'     => array(
					'default'         => __( 'Default Layout', 'woocommerce' ),
					'custom_layout_1' => __( 'Custom Layout 1', 'woocommerce' ),
				),
			)
		);

		// Custom color field
		woocommerce_wp_text_input(
			array(
				'id'          => '_custom_product_color',
				'label'       => __( 'Product Color', 'woocommerce' ),
				'description' => __( 'Enter the color for this product.', 'woocommerce' ),
				'desc_tip'    => true,
				'value'       => get_post_meta( $product_object->get_id(), '_custom_product_color', true ),
			)
		);

		echo '</div>';
	}
}

// Save custom fields
function save_custom_advanced_fields( $post_id ) {
	// Save template selection
	$selected_template = isset( $_POST['_custom_template_selection'] ) ? sanitize_text_field( $_POST['_custom_template_selection'] ) : 'default';
	update_post_meta( $post_id, '_custom_template_selection', $selected_template );

	// Save color field
	$product_color = isset( $_POST['_custom_product_color'] ) ? sanitize_text_field( $_POST['_custom_product_color'] ) : '';
	update_post_meta( $post_id, '_custom_product_color', $product_color );
}

// Display the custom fields in each variation
function add_custom_discount_field( $loop, $variation_data, $variation ) {
	// Custom Variation Title Field with tooltip
	woocommerce_wp_text_input(
		array(
			'id'            => 'custom_variation_title_field_' . $variation->ID,
			'name'          => 'custom_variation_title_field[' . $variation->ID . ']',
			'label'         => __( 'Custom Variation Title', 'woocommerce' ),
			'value'         => get_post_meta( $variation->ID, '_custom_variation_title_field', true ),
			'wrapper_class' => 'form-field form-row', // Adds both classes to the <p> wrapper
			'desc_tip'      => true, // Enables the tooltip icon
			'description'   => __( 'Enter a custom title for this variation.', 'woocommerce' ), // Tooltip text
		)
	);
	// Custom Discount Text Field with tooltip
	woocommerce_wp_text_input(
		array(
			'id'            => 'custom_discount_text_field_' . $variation->ID,
			'name'          => 'custom_discount_text_field[' . $variation->ID . ']',
			'label'         => __( 'Discount Text', 'woocommerce' ),
			'value'         => get_post_meta( $variation->ID, '_custom_discount_text_field', true ),
			'wrapper_class' => 'form-field form-row', // Adds both classes to the <p> wrapper
			'desc_tip'      => true, // Enables the tooltip icon
			'description'   => __( 'Enter a discount description for this variation.', 'woocommerce' ), // Tooltip text
		)
	);
	// Custom Monthly Pricing Field
	woocommerce_wp_text_input(
		array(
			'id'                => 'custom_monthly_pricing_field_' . $variation->ID,
			'name'              => 'custom_monthly_pricing_field[' . $variation->ID . ']',
			'label'             => __( 'Monthly Pricing', 'woocommerce' ),
			'value'             => get_post_meta( $variation->ID, '_custom_monthly_pricing_field', true ),
			'wrapper_class'     => 'form-field form-row required-field', // Adds both classes to the <p> wrapper
			'desc_tip'          => true, // Enables the tooltip icon
			'description'       => __( 'Enter the monthly price for this variation. This field is required.', 'woocommerce' ), // Tooltip text
			'custom_attributes' => array(
				'required' => 'required', // Make it required
			),
		)
	);
}

// Save the custom fields for each variation
function save_custom_text_field( $variation_id, $i ) {
	if ( isset( $_POST['custom_discount_text_field'][ $variation_id ] ) ) {
		update_post_meta( $variation_id, '_custom_discount_text_field', sanitize_text_field( $_POST['custom_discount_text_field'][ $variation_id ] ) );
	}
	if ( isset( $_POST['custom_variation_title_field'][ $variation_id ] ) ) {
		update_post_meta( $variation_id, '_custom_variation_title_field', sanitize_text_field( $_POST['custom_variation_title_field'][ $variation_id ] ) );
	}
	// Save the custom monthly pricing field
	if ( isset( $_POST['custom_monthly_pricing_field'][ $variation_id ] ) ) {
		update_post_meta( $variation_id, '_custom_monthly_pricing_field', sanitize_text_field( $_POST['custom_monthly_pricing_field'][ $variation_id ] ) );
	}
}
