<?php
/**
 * Plugin Name: Doctype Inserter
 * Plugin URI: https://github.com/cemfirat/wordpress-doctype-Inserter
 * Description: Inserts a custom snippet immediately after the HTML doctype on front-end pages.
 * Version: 1.1.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Cem Firat
 * Update URI: https://github.com/cemfirat/wordpress-doctype-Inserter
 * Text Domain: doctype-inserter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DOCTYPE_INSERTER_VERSION', '1.1.0' );
define( 'DOCTYPE_INSERTER_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-doctype-inserter-output.php';
require_once __DIR__ . '/includes/class-doctype-inserter-updater.php';

new Doctype_Inserter_Updater( __FILE__ );

add_action( 'admin_menu', 'doctype_inserter_admin_menu' );
add_action( 'admin_init', 'doctype_inserter_register_settings' );
add_action( 'template_redirect', 'doctype_inserter_start_buffer', 99 );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'doctype_inserter_action_links' );

/** Register the settings page. */
function doctype_inserter_admin_menu() {
	add_options_page( 'Doctype Inserter', 'Doctype Inserter', 'manage_options', 'doctype-inserter', 'doctype_inserter_settings_page' );
}

/** Preserve the option name used by version 1.0. */
function doctype_inserter_register_settings() {
	register_setting(
		'doctype_inserter_options',
		'doctype_inserter_text',
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'doctype_inserter_validate_snippet',
			'show_in_rest'      => false,
		)
	);
	add_settings_section( 'doctype_inserter_main', 'Snippet after the HTML doctype', '__return_false', 'doctype-inserter' );
	add_settings_field(
		'doctype_inserter_field',
		'Custom snippet',
		'doctype_inserter_field_render',
		'doctype-inserter',
		'doctype_inserter_main',
		array( 'label_for' => 'doctype_inserter_text' )
	);
}

/** Raw snippets require both site management and WordPress's unfiltered HTML capability. */
function doctype_inserter_can_edit() {
	return current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
}

/** Validate access and type without corrupting intentionally supplied HTML or JavaScript. */
function doctype_inserter_validate_snippet( $value ) {
	if ( ! doctype_inserter_can_edit() || ! is_string( $value ) ) {
		add_settings_error( 'doctype_inserter_text', 'doctype_inserter_invalid', 'The snippet was not saved. You need permission to manage options and save unfiltered HTML, and the snippet must be text.' );
		$previous = get_option( 'doctype_inserter_text', '' );
		return is_string( $previous ) ? $previous : '';
	}
	return $value;
}

/** Render escaped settings; WordPress's Settings API checks the nonce on submission. */
function doctype_inserter_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1>Doctype Inserter</h1>
		<p>Add a snippet immediately after the HTML doctype on front-end pages. Leave it empty to disable insertion.</p>
		<p>HTML comments are recommended here. Visible content or scripts before the opening HTML element can change how browsers parse the page. Only save code you trust.</p>
		<?php if ( doctype_inserter_can_edit() ) : ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'doctype_inserter_options' );
				do_settings_sections( 'doctype-inserter' );
				submit_button( 'Save Changes' );
				?>
			</form>
		<?php else : ?>
			<p>You need permission to save unfiltered HTML to edit this snippet. On Multisite, this normally requires a Super Admin.</p>
		<?php endif; ?>
		<p>Updates are checked through GitHub during WordPress plugin update checks. Automatic installation follows your WordPress auto-update settings.</p>
	</div>
	<?php
}

/** Display the existing value safely, including literal closing textarea tags. */
function doctype_inserter_field_render() {
	$value = get_option( 'doctype_inserter_text', '' );
	echo '<textarea id="doctype_inserter_text" name="doctype_inserter_text" rows="10" class="large-text code" spellcheck="false" aria-describedby="doctype-inserter-help">' . esc_textarea( is_string( $value ) ? $value : '' ) . '</textarea>';
	echo '<p id="doctype-inserter-help" class="description">The snippet is inserted once, exactly as saved. Clear your page cache after changing it. Pages without an HTML doctype are left unchanged.</p>';
}

/** Link directly from the Plugins screen. */
function doctype_inserter_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=doctype-inserter' ) ) . '">Settings</a>' );
	return $links;
}

/** Start only for ordinary front-end documents, and do no buffering when disabled. */
function doctype_inserter_start_buffer() {
	static $started = false;
	if ( $started || is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_trackback()
		|| is_robots() || is_favicon() || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI )
		|| ( isset( $_SERVER['REQUEST_METHOD'] ) && 'HEAD' === $_SERVER['REQUEST_METHOD'] )
		|| headers_sent() ) {
		return;
	}
	$snippet = get_option( 'doctype_inserter_text', '' );
	if ( ! is_string( $snippet ) || '' === $snippet ) {
		return;
	}
	$handler = new Doctype_Inserter_Output( $snippet );
	$started = ob_start( array( $handler, 'handle' ), 8192 );
}
