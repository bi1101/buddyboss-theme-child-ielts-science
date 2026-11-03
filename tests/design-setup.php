<?php
/** Run with `wp eval-file tests/design-setup.php` on a local, disposable BuddyBoss site. */
$option_names = array( 'buddyboss_theme_options', 'studyieltsvn_design_installed', 'studyieltsvn_design_options_backup' );
$saved = array();
foreach ( $option_names as $name ) {
	$saved[ $name ] = get_option( $name );
}
$font_ids = get_posts( array( 'post_type' => 'buddyboss_fonts', 'posts_per_page' => -1, 'fields' => 'ids' ) );
$check = function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
try {
	foreach ( $font_ids as $id ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
	}
	delete_option( 'studyieltsvn_design_installed' );
	delete_option( 'studyieltsvn_design_options_backup' );
	$original = array( 'logo_size' => 123, 'boss_body_font_family' => array( 'font-size' => '19px' ) );
	update_option( 'buddyboss_theme_options', $original );
	set_transient( 'buddyboss_theme_compressed_custom_css', array( 'css' => 'old' ) );
	studyieltsvn_setup_design();
	$options = get_option( 'buddyboss_theme_options' );
	$check( get_option( 'studyieltsvn_design_installed' ), 'Setup did not complete' );
	$check( '#FFFFFF' === $options['body_background'] && '#FF0000' === $options['accent_color'], 'Palette missing' );
	$check( 123 === $options['logo_size'] && '19px' === $options['boss_body_font_family']['font-size'], 'Unrelated settings changed' );
	$check( 'Linotte' === $options['boss_body_font_family']['font-family'], 'Body font missing' );
	$check( $original === get_option( 'studyieltsvn_design_options_backup' ), 'Backup differs' );
	$check( false === get_transient( 'buddyboss_theme_compressed_custom_css' ), 'Stale CSS cache remains' );
	$fonts = get_posts( array( 'post_type' => 'buddyboss_fonts', 'posts_per_page' => -1 ) );
	$check( 2 === count( $fonts ), 'Expected two font families' );
	foreach ( $fonts as $font ) {
		$faces = get_post_meta( $font->ID, 'buddyboss_font_face', true );
		$check( 5 === count( $faces ), 'Font weights missing' );
		foreach ( $faces as $face ) {
			$url = $face['woff2']['url'] ?? $face['ttf']['url'];
			$path = str_replace( get_stylesheet_directory_uri(), get_stylesheet_directory(), $url );
			$check( is_file( $path ), 'Bundled font file missing' );
		}
	}
	$options['accent_color'] = '#123456';
	update_option( 'buddyboss_theme_options', $options );
	studyieltsvn_setup_design();
	$check( $options === get_option( 'buddyboss_theme_options' ), 'Reactivation overwrote edits' );
	// A retry must reuse the existing native font records.
	delete_option( 'studyieltsvn_design_installed' );
	studyieltsvn_setup_design();
	$check( 2 === count( get_posts( array( 'post_type' => 'buddyboss_fonts' ) ) ), 'Duplicate font records' );
	echo "PASS: palette, fonts, backup, cache, preserved options, and repeated setup.\n";
} finally {
	foreach ( get_posts( array( 'post_type' => 'buddyboss_fonts', 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $id ) {
		if ( ! in_array( $id, $font_ids, true ) ) {
			wp_delete_post( $id, true );
		}
	}
	foreach ( $font_ids as $id ) {
		wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	}
	foreach ( $saved as $name => $value ) {
		false === $value ? delete_option( $name ) : update_option( $name, $value );
	}
	delete_transient( 'buddyboss_theme_compressed_custom_css' );
}
