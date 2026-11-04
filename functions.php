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
