<?php
/**
 * Plugin Name: Doctype Inserter
 * Plugin URI: https://github.com/cemfirat/wordpress-doctype-Inserter
 * Description: Adds a simple source-code message or advanced snippet immediately after the HTML doctype.
 * Version: 1.2.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Cem Firat
 * Author URI: https://cemfirat.com/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/cemfirat/wordpress-doctype-Inserter
 * Text Domain: doctype-inserter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DOCTYPE_INSERTER_VERSION', '1.2.0' );
define( 'DOCTYPE_INSERTER_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-doctype-inserter-output.php';
require_once __DIR__ . '/includes/class-doctype-inserter-updater.php';

new Doctype_Inserter_Updater( __FILE__ );

add_action( 'admin_menu', 'doctype_inserter_admin_menu' );
add_action( 'admin_init', 'doctype_inserter_register_settings' );
add_action( 'admin_post_doctype_inserter_check', 'doctype_inserter_check_output' );
add_action( 'template_redirect', 'doctype_inserter_start_buffer', 99 );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'doctype_inserter_action_links' );

/** Register the settings page. */
function doctype_inserter_admin_menu() {
	add_options_page( 'Doctype Inserter', 'Doctype Inserter', 'manage_options', 'doctype-inserter', 'doctype_inserter_settings_page' );
}

/** Register the simple-message settings while preserving the legacy raw-snippet option. */
function doctype_inserter_register_settings() {
	register_setting(
		'doctype_inserter_options',
		'doctype_inserter_enabled',
		array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => 'doctype_inserter_validate_enabled',
			'show_in_rest'      => false,
		)
	);
	register_setting(
		'doctype_inserter_options',
		'doctype_inserter_mode',
		array(
			'type'              => 'string',
			'default'           => 'comment',
			'sanitize_callback' => 'doctype_inserter_validate_mode',
			'show_in_rest'      => false,
		)
	);
	register_setting(
		'doctype_inserter_options',
		'doctype_inserter_comment',
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'doctype_inserter_validate_comment',
			'show_in_rest'      => false,
		)
	);
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
}

/** Raw snippets require both site management and WordPress's unfiltered HTML capability. */
function doctype_inserter_can_edit() {
	return current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
}

/** Normalize the output toggle to a boolean. */
function doctype_inserter_validate_enabled( $value ) {
	return ! empty( $value );
}

/** Only the two supported modes may be stored. */
function doctype_inserter_validate_mode( $value ) {
	return 'advanced' === $value ? 'advanced' : 'comment';
}

/** Store simple messages as plain textarea text. */
function doctype_inserter_validate_comment( $value ) {
	if ( ! current_user_can( 'manage_options' ) || ! is_string( $value ) ) {
		$previous = get_option( 'doctype_inserter_comment', '' );
		return is_string( $previous ) ? $previous : '';
	}
	return sanitize_textarea_field( $value );
}

/** Validate access and type without corrupting intentionally supplied HTML or JavaScript. */
function doctype_inserter_validate_snippet( $value ) {
	if ( ! doctype_inserter_can_edit() || ! is_string( $value ) ) {
		add_settings_error( 'doctype_inserter_text', 'doctype_inserter_invalid', 'The advanced snippet was not saved. You need permission to manage options and save unfiltered HTML, and the snippet must be text.' );
		$previous = get_option( 'doctype_inserter_text', '' );
		return is_string( $previous ) ? $previous : '';
	}
	return $value;
}

/** Existing 1.0/1.1 snippets automatically remain in Advanced mode after upgrading. */
function doctype_inserter_get_mode() {
	$stored = get_option( 'doctype_inserter_mode', null );
	if ( 'comment' === $stored || 'advanced' === $stored ) {
		return $stored;
	}
	$legacy = get_option( 'doctype_inserter_text', '' );
	return is_string( $legacy ) && '' !== $legacy ? 'advanced' : 'comment';
}

/** New installs start enabled; an empty message still produces no output. */
function doctype_inserter_is_enabled() {
	$stored = get_option( 'doctype_inserter_enabled', null );
	return null === $stored ? true : (bool) $stored;
}

/** Convert plain text to a standards-safe HTML comment without executing user input. */
function doctype_inserter_comment_snippet( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return '';
	}
	// HTML comments cannot contain a double hyphen. Keep the message readable.
	$text = str_replace( '--', '- -', $text );
	return "<!--\n" . $text . "\n-->";
}

/** Return exactly what should be inserted for the current settings. */
function doctype_inserter_get_active_snippet() {
	if ( ! doctype_inserter_is_enabled() ) {
		return '';
	}
	if ( 'advanced' === doctype_inserter_get_mode() ) {
		$snippet = get_option( 'doctype_inserter_text', '' );
		return is_string( $snippet ) ? $snippet : '';
	}
	$message = get_option( 'doctype_inserter_comment', '' );
	return doctype_inserter_comment_snippet( is_string( $message ) ? $message : '' );
}

/** Render escaped settings; WordPress's Settings API checks the nonce on submission. */
function doctype_inserter_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$mode    = doctype_inserter_get_mode();
	$enabled = doctype_inserter_is_enabled();
	$comment = get_option( 'doctype_inserter_comment', '' );
	$comment = is_string( $comment ) ? $comment : '';
	$preview = 'advanced' === $mode ? get_option( 'doctype_inserter_text', '' ) : doctype_inserter_comment_snippet( $comment );
	$preview = is_string( $preview ) ? $preview : '';
	$check   = isset( $_GET['doctype-inserter-check'] ) ? sanitize_key( wp_unslash( $_GET['doctype-inserter-check'] ) ) : '';
	?>
	<div class="wrap">
		<h1>Doctype Inserter</h1>
		<p>Add a small public message directly after <code>&lt;!DOCTYPE html&gt;</code>. It is invisible in the page layout but readable in View Source.</p>

		<?php if ( 'found' === $check ) : ?>
			<div class="notice notice-success is-dismissible"><p>The active message was found on the home page.</p></div>
		<?php elseif ( 'missing' === $check ) : ?>
			<div class="notice notice-warning is-dismissible"><p>The active message was not found on the home page. Clear page/CDN caches and check HTML minification.</p></div>
		<?php elseif ( 'empty' === $check ) : ?>
			<div class="notice notice-info is-dismissible"><p>There is no active message to check.</p></div>
		<?php elseif ( 'error' === $check ) : ?>
			<div class="notice notice-error is-dismissible"><p>The home page could not be checked from WordPress.</p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'doctype_inserter_options' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Output</th>
					<td>
						<input type="hidden" name="doctype_inserter_enabled" value="0">
						<label><input type="checkbox" name="doctype_inserter_enabled" value="1" <?php checked( $enabled ); ?>> Enable source-code message</label>
						<p class="description">Pause output without deleting the saved message or advanced snippet.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Mode</th>
					<td>
						<label><input type="radio" name="doctype_inserter_mode" value="comment" <?php checked( 'comment', $mode ); ?>> Simple comment</label><br>
						<label><input type="radio" name="doctype_inserter_mode" value="advanced" <?php checked( 'advanced', $mode ); ?>> Advanced snippet</label>
						<p class="description">Existing snippets from earlier versions automatically stay in Advanced mode.</p>
					</td>
				</tr>
				<tr id="doctype-inserter-comment-row">
					<th scope="row"><label for="doctype_inserter_comment">Message</label></th>
					<td>
						<textarea id="doctype_inserter_comment" name="doctype_inserter_comment" rows="7" class="large-text" placeholder="Hello, developers!"><?php echo esc_textarea( $comment ); ?></textarea>
						<p class="description">Write normal text. Doctype Inserter wraps it in a safe HTML comment; double hyphens are made comment-safe automatically.</p>
						<p>
							<button type="button" class="button doctype-inserter-template" data-template="Website by Your Name — https://example.com">Website credits</button>
							<button type="button" class="button doctype-inserter-template" data-template="We're hiring! See our open roles: https://example.com/jobs">We're hiring</button>
							<button type="button" class="button doctype-inserter-template" data-template="Hello, developers! Thanks for looking under the hood.">Hello, developers</button>
						</p>
					</td>
				</tr>
				<tr id="doctype-inserter-advanced-row">
					<th scope="row"><label for="doctype_inserter_text">Advanced snippet</label></th>
					<td>
						<?php if ( doctype_inserter_can_edit() ) : ?>
							<?php doctype_inserter_field_render(); ?>
						<?php else : ?>
							<p>You need permission to save unfiltered HTML to edit the advanced snippet. On Multisite, this normally requires a Super Admin.</p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">Source preview</th>
					<td>
						<pre id="doctype-inserter-preview" style="max-width:900px;overflow:auto;padding:12px;background:#fff;border:1px solid #c3c4c7"><?php echo esc_html( "<!DOCTYPE html>\n" . $preview . "\n<html>" ); ?></pre>
						<p class="description">Preview only. Entered code is displayed as text here and is not executed.</p>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Save Changes' ); ?>
		</form>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1em">
			<input type="hidden" name="action" value="doctype_inserter_check">
			<?php wp_nonce_field( 'doctype_inserter_check' ); ?>
			<?php submit_button( 'Check home page output', 'secondary', 'submit', false ); ?>
			<span class="description"> Fetches the home page and checks whether the currently active output is present.</span>
		</form>

		<hr>
		<h2>What does this plugin do?</h2>
		<p>Doctype Inserter places one message immediately after the document doctype on ordinary WordPress front-end HTML pages. It is useful for credits, developer greetings, hiring messages and other small notes for people who inspect the page source.</p>
		<p>
			<a href="https://github.com/cemfirat/wordpress-doctype-Inserter" target="_blank" rel="noopener noreferrer">Documentation &amp; releases</a> ·
			<a href="https://github.com/cemfirat/wordpress-doctype-Inserter/issues" target="_blank" rel="noopener noreferrer">Report a bug or request a feature</a> ·
			<a href="https://cemfirat.com/" target="_blank" rel="noopener noreferrer">Cem Firat</a>
		</p>
	</div>
	<script>
	(function () {
		var message = document.getElementById('doctype_inserter_comment');
		var preview = document.getElementById('doctype-inserter-preview');
		var commentRow = document.getElementById('doctype-inserter-comment-row');
		var advancedRow = document.getElementById('doctype-inserter-advanced-row');
		var advanced = document.getElementById('doctype_inserter_text');
		var modes = document.querySelectorAll('input[name="doctype_inserter_mode"]');

		function safeComment(text) {
			return text ? '<!--\n' + text.replace(/--/g, '- -') + '\n-->' : '';
		}
		function currentMode() {
			var selected = document.querySelector('input[name="doctype_inserter_mode"]:checked');
			return selected ? selected.value : 'comment';
		}
		function refresh() {
			var mode = currentMode();
			commentRow.style.display = 'comment' === mode ? '' : 'none';
			advancedRow.style.display = 'advanced' === mode ? '' : 'none';
			var snippet = 'advanced' === mode ? (advanced ? advanced.value : '') : safeComment(message.value);
			preview.textContent = '<!DOCTYPE html>\n' + snippet + '\n<html>';
		}
		document.querySelectorAll('.doctype-inserter-template').forEach(function (button) {
			button.addEventListener('click', function () {
				message.value = button.getAttribute('data-template') || '';
				refresh();
				message.focus();
			});
		});
		modes.forEach(function (radio) { radio.addEventListener('change', refresh); });
		message.addEventListener('input', refresh);
		if (advanced) {
			advanced.addEventListener('input', refresh);
		}
		refresh();
	}());
	</script>
	<?php
}

/** Display the existing advanced value safely, including literal closing textarea tags. */
function doctype_inserter_field_render() {
	$value = get_option( 'doctype_inserter_text', '' );
	echo '<textarea id="doctype_inserter_text" name="doctype_inserter_text" rows="10" class="large-text code" spellcheck="false" aria-describedby="doctype-inserter-help">' . esc_textarea( is_string( $value ) ? $value : '' ) . '</textarea>';
	echo '<p id="doctype-inserter-help" class="description">Inserted exactly as saved. Only save code you trust; scripts or visible markup before &lt;html&gt; can affect browser parsing.</p>';
}

/** Check the home page for the exact currently active output. */
function doctype_inserter_check_output() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You are not allowed to check this output.' );
	}
	check_admin_referer( 'doctype_inserter_check' );

	$snippet = doctype_inserter_get_active_snippet();
	$status  = 'empty';
	if ( '' !== $snippet ) {
		$url      = add_query_arg( '_doctype_inserter_check', time(), home_url( '/' ) );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 10,
				'redirection' => 3,
			)
		);
		if ( is_wp_error( $response ) ) {
			$status = 'error';
		} else {
			$body   = wp_remote_retrieve_body( $response );
			$status = false !== strpos( $body, $snippet ) ? 'found' : 'missing';
		}
	}
	$target = add_query_arg( 'doctype-inserter-check', $status, admin_url( 'options-general.php?page=doctype-inserter' ) );
	wp_safe_redirect( $target );
	exit;
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
	$snippet = doctype_inserter_get_active_snippet();
	if ( '' === $snippet ) {
		return;
	}
	$handler = new Doctype_Inserter_Output( $snippet );
	$started = ob_start( array( $handler, 'handle' ), 8192 );
}
