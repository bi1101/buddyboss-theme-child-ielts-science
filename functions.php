<?php
require_once __DIR__ . '/inc/virtual-checkout.php';

/** Load the child stylesheet for the site's theme-level WooCommerce overrides. */
function studyieltsvn_enqueue_child_styles() {
	$stylesheet = get_stylesheet_directory() . '/style.css';
	wp_enqueue_style(
		'studyieltsvn-child',
		get_stylesheet_uri(),
		array(),
		file_exists( $stylesheet ) ? (string) filemtime( $stylesheet ) : null
	);
}
add_action( 'wp_enqueue_scripts', 'studyieltsvn_enqueue_child_styles', 20 );

/** Apply the bundled design once; subsequent edits belong to the site owner. */
function studyieltsvn_setup_design() {
	if ( get_option( 'studyieltsvn_design_installed' ) ) {
		return;
	}

	$preset = json_decode( file_get_contents( get_stylesheet_directory() . '/config/buddyboss-preset.json' ), true );
	if ( ! is_array( $preset ) || ! post_type_exists( 'buddyboss_fonts' ) ) {
		return;
	}

	$families = array(
		'Linotte' => array(
			300 => 'linotte/Linotte-Light.woff2',
			400 => 'linotte/Linotte-Regular.woff2',
			600 => 'linotte/Linotte-SemiBold.woff2',
			700 => 'linotte/Linotte-Bold.woff2',
			900 => 'linotte/Linotte-Heavy.woff2',
		),
		// BuddyBoss emits unquoted @font-face names; a standalone digit is invalid CSS.
		'Baloo Bhaina' => array_fill_keys( array( 400, 500, 600, 700, 800 ), 'BalooBhaina2.ttf' ),
	);
	foreach ( $families as $family => $files ) {
		$existing = get_posts( array(
			'post_type'      => 'buddyboss_fonts',
			'post_status'    => 'publish',
			'title'          => $family,
			'posts_per_page' => 1,
		) );
		if ( $existing ) {
			continue;
		}
		$faces = array();
		foreach ( $files as $weight => $file ) {
			$faces[] = array(
				'font_weight' => (string) $weight,
				'font_style'  => 'normal',
				pathinfo( $file, PATHINFO_EXTENSION ) => array(
					'url' => get_stylesheet_directory_uri() . '/assets/fonts/' . $file,
				),
			);
		}
		$font_id = wp_insert_post( array(
			'post_type'   => 'buddyboss_fonts',
			'post_status' => 'publish',
			'post_title'  => $family,
			'meta_input'  => array( 'buddyboss_font_face' => $faces ),
		), true );
		if ( is_wp_error( $font_id ) ) {
			return;
		}
	}

	$options = get_option( 'buddyboss_theme_options', array() );
	add_option( 'studyieltsvn_design_options_backup', $options, '', false );
	update_option( 'buddyboss_theme_options', array_replace_recursive( $options, $preset ) );
	delete_transient( 'buddyboss_theme_compressed_custom_css' );
	add_option( 'studyieltsvn_design_installed', true, '', false );
}
// Run after BuddyBoss registers its Custom Fonts post type, including existing child-theme installs.
add_action( 'init', 'studyieltsvn_setup_design', 20 );

/** Update the old color preset once without changing typography or other settings. */
function studyieltsvn_update_palette() {
	if ( ! get_option( 'studyieltsvn_design_installed' ) || get_option( 'studyieltsvn_palette_updated' ) ) {
		return;
	}

	$preset = json_decode( file_get_contents( get_stylesheet_directory() . '/config/buddyboss-preset.json' ), true );
	$options = get_option( 'buddyboss_theme_options', array() );
	if ( ! is_array( $preset ) || ! is_array( $options ) ) {
		error_log( 'Study IELTS: unable to update the BuddyBoss color palette.' );
		return;
	}

	$keys = array(
		'accent_color', 'body_background', 'primary_button_background', 'primary_button_border',
		'primary_button_text_color', 'secondary_button_background', 'secondary_button_border',
		'header_links_hover', 'sidenav_text_color', 'sidenav_menu_background_color',
		'sidenav_count_text_color', 'sidenav_count_background_color', 'footer_menu_link_color',
		'login_register_link_color', 'login_register_button_background_color',
		'login_register_button_border_color', 'login_register_button_text_color',
		'label_background_color', 'label_text_color',
	);
	$palette = array_intersect_key( $preset, array_flip( $keys ) );
	if ( count( $palette ) !== count( $keys ) ) {
		error_log( 'Study IELTS: the BuddyBoss color preset is incomplete.' );
		return;
	}

	add_option( 'studyieltsvn_palette_options_backup', $options, '', false );
	if ( false === get_option( 'studyieltsvn_palette_options_backup' ) ) {
		error_log( 'Study IELTS: unable to back up the BuddyBoss color palette.' );
		return;
	}
	$updated = array_replace_recursive( $options, $palette );
	update_option( 'buddyboss_theme_options', $updated );
	if ( get_option( 'buddyboss_theme_options' ) !== $updated ) {
		error_log( 'Study IELTS: unable to save the BuddyBoss color palette.' );
		return;
	}
	delete_transient( 'buddyboss_theme_compressed_custom_css' );
	if ( ! add_option( 'studyieltsvn_palette_updated', true, '', false ) && ! get_option( 'studyieltsvn_palette_updated' ) ) {
		error_log( 'Study IELTS: unable to record the BuddyBoss palette update.' );
	}
}
add_action( 'init', 'studyieltsvn_update_palette', 21 );

/** Match native BuddyBoss pages to the React app's color-mode preference. */
function studyieltsvn_enqueue_color_scheme() {
	if ( apply_filters( 'ieltssci_is_shell_request', false ) ) {
		return;
	}

	foreach ( array( 'css', 'js' ) as $extension ) {
		$relative = '/assets/color-scheme.' . $extension;
		$path = get_stylesheet_directory() . $relative;
		$version = file_exists( $path ) ? (string) filemtime( $path ) : null;
		if ( 'css' === $extension ) {
			wp_enqueue_style( 'studyieltsvn-color-scheme', get_stylesheet_directory_uri() . $relative, array( 'studyieltsvn-child' ), $version );
		} else {
			wp_enqueue_script( 'studyieltsvn-color-scheme', get_stylesheet_directory_uri() . $relative, array(), $version, false );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'studyieltsvn_enqueue_color_scheme', 21 );

/**
 * Keep BuddyBoss's site chrome from styling the React shell.
 *
 * @return void
 */
function studyieltsvn_dequeue_shell_theme_assets() {
	if ( ! apply_filters( 'ieltssci_is_shell_request', false ) ) {
		return;
	}

	foreach ( array(
		'bb_theme_block-buddypanel-style-css',
		'buddyboss-theme-buddypress',
		'buddyboss-theme-css',
		'buddyboss-theme-fonts',
		'buddyboss-theme-icons-map',
		'buddyboss-theme-icons',
		'buddyboss-theme-magnific-popup-css',
		'buddyboss-theme-select2-css',
		'buddyboss-theme-template',
		'buddyboss-theme-woocommerce',
		'buddyboss-theme-forums',
		'buddyboss-theme-plugins',
		'buddyboss_legacy',
		'redux-extendify-styles',
	) as $handle ) {
		wp_dequeue_style( $handle );
	}

	foreach ( array(
		'buddyboss-theme-cookie-js',
		'boss-menu-js',
		'boss-fitvids-js',
		'boss-slick-js',
		'boss-panelslider-js',
		'boss-sticky-js',
		'boss-jssocials-js',
		'buddyboss-theme-main-js',
		'boss-validate-js',
		'bp-nouveau-magnific-popup',
		'select2-js',
		'progressbar-js',
		'mousewheel-js',
		'buddyboss-theme-woocommerce-js',
	) as $handle ) {
		wp_dequeue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'studyieltsvn_dequeue_shell_theme_assets', 999 );
