<?php
/** Standalone test of the palette migration, without modifying a WordPress database. */
$options = array();
$deleted_transients = array();
$enqueued = array();
$shell_request = false;
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function get_stylesheet_directory() {
	return dirname( __DIR__ );
}
function get_stylesheet_directory_uri() {
	return 'https://example.test/theme';
}
function apply_filters( $name, $value ) {
	global $shell_request;
	return 'ieltssci_is_shell_request' === $name ? $shell_request : $value;
}
function wp_enqueue_style( $handle, ...$args ) {
	global $enqueued;
	$enqueued[] = 'style:' . $handle;
}
function wp_enqueue_script( $handle, ...$args ) {
	global $enqueued;
	$enqueued[] = 'script:' . $handle;
}
function get_option( $key, $default = false ) {
	global $options;
	return $options[ $key ] ?? $default;
}
function add_option( $key, $value, ...$args ) {
	global $options;
	if ( array_key_exists( $key, $options ) ) {
		return false;
	}
	$options[ $key ] = $value;
	return true;
}
function update_option( $key, $value ) {
	global $options;
	$options[ $key ] = $value;
	return true;
}
function delete_transient( $key ) {
	global $deleted_transients;
	$deleted_transients[] = $key;
}
require dirname( __DIR__ ) . '/functions.php';

function check( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$original = array(
	'accent_color' => '#FF0000',
	'logo_size' => 123,
	'boss_body_font_family' => array( 'font-size' => '19px' ),
);
$options = array( 'studyieltsvn_design_installed' => true, 'buddyboss_theme_options' => $original );
studyieltsvn_update_palette();
$updated = $options['buddyboss_theme_options'];
check( '#006891' === $updated['accent_color'] && '#1DB0F6' === $updated['primary_button_background']['regular'], 'Palette was not updated' );
check( 123 === $updated['logo_size'] && '19px' === $updated['boss_body_font_family']['font-size'], 'Unrelated settings changed' );
check( $original === $options['studyieltsvn_palette_options_backup'], 'Original settings were not backed up' );
check( ! empty( $options['studyieltsvn_palette_updated'] ) && in_array( 'buddyboss_theme_compressed_custom_css', $deleted_transients, true ), 'Update marker or cache invalidation missing' );
$options['buddyboss_theme_options']['accent_color'] = '#123456';
studyieltsvn_update_palette();
check( '#123456' === $options['buddyboss_theme_options']['accent_color'], 'A later customization was overwritten' );
studyieltsvn_enqueue_color_scheme();
check( array( 'style:studyieltsvn-color-scheme', 'script:studyieltsvn-color-scheme' ) === $enqueued, 'Native color-mode assets missing' );
$enqueued = array();
$shell_request = true;
studyieltsvn_enqueue_color_scheme();
check( array() === $enqueued, 'Native color-mode assets leaked into the React shell' );
echo "PASS: palette migration, backup, cache, idempotence and shell isolation.\n";
