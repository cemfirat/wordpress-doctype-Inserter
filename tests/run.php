<?php
/** Dependency-free regression tests. Run: php tests/run.php */
error_reporting( E_ALL );
set_error_handler( function ( $severity, $message, $file, $line ) {
	throw new RuntimeException( "$message in $file:$line" );
} );
define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
$GLOBALS['caps'] = array( 'manage_options' => true, 'unfiltered_html' => true, 'update_plugins' => true );
$GLOBALS['option'] = '<!-- existing -->';
$GLOBALS['cache'] = false;
$GLOBALS['requests'] = 0;
$GLOBALS['errors'] = array();
$GLOBALS['context'] = '';
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function current_user_can( $cap ) { return ! empty( $GLOBALS['caps'][ $cap ] ); }
function get_option( ...$args ) { return $GLOBALS['option']; }
function add_settings_error( ...$args ) { $GLOBALS['errors'][] = $args; }
function get_site_transient( $key ) { return $GLOBALS['cache']; }
function set_site_transient( $key, $value, $ttl ) { $GLOBALS['cache'] = $value; $GLOBALS['ttl'] = $ttl; }
function delete_site_transient( $key ) { $GLOBALS['cache'] = false; }
function wp_remote_get( $url, $args ) { ++$GLOBALS['requests']; return $GLOBALS['response']; }
function wp_remote_retrieve_response_code( $r ) { return $r['response']['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( $value ) { return esc_html( $value ); }
function trailingslashit( $value ) { return rtrim( $value, '/' ) . '/'; }
function untrailingslashit( $value ) { return rtrim( $value, '/' ); }
function is_admin() { return 'admin' === $GLOBALS['context']; }
function wp_doing_ajax() { return 'ajax' === $GLOBALS['context']; }
function wp_doing_cron() { return 'cron' === $GLOBALS['context']; }
function is_feed() { return 'feed' === $GLOBALS['context']; }
function is_trackback() { return 'trackback' === $GLOBALS['context']; }
function is_robots() { return 'robots' === $GLOBALS['context']; }
function is_favicon() { return 'favicon' === $GLOBALS['context']; }
class WP_Error { public function __construct( ...$args ) {} }
require dirname( __DIR__ ) . '/doctype-inserter.php';

$count = 0;
function same( $expected, $actual, $label ) {
	global $count;
	++$count;
	if ( $expected !== $actual ) {
		throw new RuntimeException( $label . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) );
	}
}
function output( $html, $snippet = '<!-- test -->' ) {
	return ( new Doctype_Inserter_Output( $snippet ) )->handle( $html );
}
$snippet = '<script>const price = "$1 ${2} \\1 \\path";</script>';
same( "<!DOCTYPE html>\n" . $snippet . '<html>ok</html>', output( '<!DOCTYPE html><html>ok</html>', $snippet ), 'Preserve literal replacement metacharacters' );
same( "<!doctype HTML>\n0<html></html>", output( '<!doctype HTML><html></html>', '0' ), 'Insert a literal zero' );
foreach ( array( '<html>No doctype</html>', '{"html":"<!DOCTYPE html>"}', '<script>"<!DOCTYPE html>"</script>', '<!-- <!DOCTYPE html> -->', '<!DOCTYPE htmlish><html>', '<!DOCTYPE svg><svg/>', '<?xml version="1.0"?><!DOCTYPE html>' ) as $html ) {
	same( $html, output( $html ), 'Leave other documents and embedded examples unchanged' );
}
$prefix = "\xEF\xBB\xBF \n<!-- example <!DOCTYPE html> -->\n<!DoCtYpE\nhtml >";
same( $prefix . "\n<!-- test --><html>", output( $prefix . '<html>' ), 'BOM, comments, mixed case and whitespace' );
same( "<!DOCTYPE html>\n<!-- test --><!DOCTYPE html>", output( '<!DOCTYPE html><!DOCTYPE html>' ), 'Only insert once' );
$legacy = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
same( $legacy . "\n<!-- test --><html>", output( $legacy . '<html>' ), 'Keep legacy HTML doctypes' );
$html = "<!-- prefix -->\n<!DOCTYPE html><html>" . str_repeat( 'body', 20000 ) . '</html>';
$expected = str_replace( '<!DOCTYPE html>', "<!DOCTYPE html>\n<!-- test -->", $html );
foreach ( array( 1, 2, 7, 8192, 65536 ) as $size ) {
	$handler = new Doctype_Inserter_Output( '<!-- test -->' );
	$result = '';
	foreach ( str_split( $html, $size ) as $chunk ) {
		$result .= $handler->handle( $chunk, PHP_OUTPUT_HANDLER_FLUSH );
	}
	$result .= $handler->handle( '', PHP_OUTPUT_HANDLER_FINAL );
	same( $expected, $result, 'Split doctypes and streaming at chunk size ' . $size );
}
$long = str_repeat( ' ', 65536 ) . '<!DOCTYPE html>';
same( $long, output( $long ), 'Bound prefix scanning' );
$handler = new Doctype_Inserter_Output( 'snippet' );
same( '', $handler->handle( '<!DOC', PHP_OUTPUT_HANDLER_FLUSH ), 'Hold incomplete prefix' );
$handler->handle( 'discarded', PHP_OUTPUT_HANDLER_CLEAN );
same( "<!DOCTYPE html>\nsnippet", $handler->handle( '<!DOCTYPE html>' ), 'Cleaning removes pending bytes' );
foreach ( array( 'application/json', 'application/xml', 'text/plain', 'application/xhtml+xml', 'text/htmlish' ) as $type ) {
	same( false, Doctype_Inserter_Output::is_html_response( array( 'Content-Type: ' . $type ), 200 ), 'Skip ' . $type );
}
foreach ( array( 'Content-Disposition: attachment', 'Content-Length: 20', 'Content-Encoding: gzip' ) as $header ) {
	same( false, Doctype_Inserter_Output::is_html_response( array( $header ), 200 ), 'Skip fixed or encoded responses' );
}
foreach ( array( 204, 301, 302, 304 ) as $status ) {
	same( false, Doctype_Inserter_Output::is_html_response( array(), $status ), 'Skip status ' . $status );
}
same( true, Doctype_Inserter_Output::is_html_response( array( 'Content-Type: text/html; charset=UTF-8' ), 404 ), 'Allow HTML error pages' );
same( true, Doctype_Inserter_Output::is_html_response( array(), 200 ), 'Sniff the leading doctype when no content type is set' );

same( $snippet, doctype_inserter_validate_snippet( $snippet ), 'Authorized admins retain raw snippets' );
same( '', doctype_inserter_validate_snippet( '' ), 'An empty snippet disables insertion' );
$GLOBALS['caps']['unfiltered_html'] = false;
same( $GLOBALS['option'], doctype_inserter_validate_snippet( '<script>new</script>' ), 'Block restricted admins and Multisite site admins' );
$GLOBALS['caps']['unfiltered_html'] = true;
$GLOBALS['caps']['manage_options'] = false;
same( $GLOBALS['option'], doctype_inserter_validate_snippet( 'new' ), 'Require site-management capability too' );
$GLOBALS['caps']['manage_options'] = true;
same( $GLOBALS['option'], doctype_inserter_validate_snippet( array( 'bad' ) ), 'Reject array submissions without data loss' );
$GLOBALS['option'] = '</textarea><script>alert(1)</script>';
ob_start();
doctype_inserter_field_render();
$field = ob_get_clean();
same( false, strpos( $field, '</textarea><script>' ), 'Escape stored snippets in the settings screen' );

foreach ( array( 'admin', 'ajax', 'cron', 'feed', 'trackback', 'robots', 'favicon' ) as $context ) {
	$GLOBALS['context'] = $context;
	$level = ob_get_level();
	doctype_inserter_start_buffer();
	same( $level, ob_get_level(), 'No buffer for ' . $context );
}
$GLOBALS['context'] = '';
$_SERVER['REQUEST_METHOD'] = 'HEAD';
$level = ob_get_level();
doctype_inserter_start_buffer();
same( $level, ob_get_level(), 'No buffer for HEAD' );
$_SERVER['REQUEST_METHOD'] = 'GET';
$GLOBALS['option'] = '';
doctype_inserter_start_buffer();
same( $level, ob_get_level(), 'No buffer when disabled' );
$GLOBALS['option'] = '0';
ob_start();
doctype_inserter_start_buffer();
same( $level + 2, ob_get_level(), 'A literal zero enables insertion' );
doctype_inserter_start_buffer();
same( $level + 2, ob_get_level(), 'Do not start duplicate buffers' );
echo '<!DOCTYPE html><html>hello</html>';
ob_end_flush();
same( "<!DOCTYPE html>\n0<html>hello</html>", ob_get_clean(), 'Actual PHP output buffering inserts the snippet' );

$release = array(
	'tag_name' => 'v1.2.0', 'draft' => false, 'prerelease' => false,
	'body' => "Requires WordPress: 5.8\nRequires PHP: 7.4\n<script>unsafe</script>",
	'assets' => array( array( 'name' => 'doctype-inserter.zip', 'state' => 'uploaded', 'size' => 100,
		'browser_download_url' => Doctype_Inserter_Updater::REPOSITORY . '/releases/download/v1.2.0/doctype-inserter.zip' ) ),
);
$parsed = Doctype_Inserter_Updater::parse_release( $release );
same( '1.2.0', $parsed['version'], 'Parse stable version' );
same( '7.4', $parsed['requires_php'], 'Read release-specific requirements' );
foreach ( array( 'v1.2.0-beta', 'v1.2', '../1.2.0', '1.2.0', '' ) as $tag ) {
	$bad = $release; $bad['tag_name'] = $tag;
	same( false, Doctype_Inserter_Updater::parse_release( $bad ), 'Reject unstable or malformed tag' );
}
foreach ( array( 'draft', 'prerelease' ) as $flag ) {
	$bad = $release; $bad[ $flag ] = true;
	same( false, Doctype_Inserter_Updater::parse_release( $bad ), 'Reject ' . $flag );
}
foreach ( array( null, array(), array( 'tag_name' => array() ) ) as $bad ) {
	same( false, Doctype_Inserter_Updater::parse_release( $bad ), 'Ignore malformed JSON structures' );
}
foreach ( array( 'https://evil.test/plugin.zip', 'http://github.com/cemfirat/wordpress-doctype-Inserter/releases/download/v1.2.0/doctype-inserter.zip' ) as $url ) {
	$bad = $release; $bad['assets'][0]['browser_download_url'] = $url;
	same( false, Doctype_Inserter_Updater::parse_release( $bad ), 'Reject unexpected package URLs' );
}
$bad = $release; $bad['assets'] = array();
same( false, Doctype_Inserter_Updater::parse_release( $bad ), 'Ignore releases without ZIP' );
$bad = $release; $bad['body'] = 'No requirements';
same( false, Doctype_Inserter_Updater::parse_release( $bad ), 'Ignore incomplete metadata' );
$bad = $release; $bad['assets'][0]['size'] = 0;
same( false, Doctype_Inserter_Updater::parse_release( $bad ), 'Ignore empty assets' );
$GLOBALS['response'] = array( 'response' => array( 'code' => 200 ), 'body' => json_encode( $release ) );
$updater = new Doctype_Inserter_Updater( '/plugins/custom-folder/doctype-inserter.php' );
same( 'other', $updater->check_update( 'other', array(), 'other/plugin.php' ), 'Do not change other GitHub plugins' );
same( 0, $GLOBALS['requests'], 'Do not contact GitHub for another plugin' );
$update = $updater->check_update( false, array(), 'custom-folder/doctype-inserter.php' );
same( '1.2.0', $update['version'], 'Offer newer stable release' );
same( false, array_key_exists( 'autoupdate', $update ), 'Respect WordPress auto-update preference' );
$updater->get_release();
same( 1, $GLOBALS['requests'], 'Reuse successful response cache' );
same( 21600, $GLOBALS['ttl'], 'Six-hour success cache' );
$info = $updater->plugin_information( false, 'plugin_information', (object) array( 'slug' => 'doctype-inserter' ) );
same( false, strpos( $info->sections['changelog'], '<script>' ), 'Escape remote release notes' );
same( 'other', $updater->plugin_information( 'other', 'plugin_information', (object) array( 'slug' => 'other' ) ), 'Preserve other plugin details' );
$_GET['force-check'] = '1';
$updater->maybe_force_check();
same( false, $GLOBALS['cache'], 'Check Again clears cached metadata' );
foreach ( array( new WP_Error(), array( 'response' => array( 'code' => 403 ), 'body' => '' ), array( 'response' => array( 'code' => 200 ), 'body' => '{broken' ) ) as $response ) {
	$GLOBALS['cache'] = false; $GLOBALS['response'] = $response;
	same( false, $updater->get_release(), 'Network errors, rate limits and invalid JSON fail safely' );
	$requests = $GLOBALS['requests'];
	$updater->get_release();
	same( $requests, $GLOBALS['requests'], 'Cache failures to avoid retry storms' );
	same( 900, $GLOBALS['ttl'], 'Short failure cache' );
}
class Test_Filesystem {
	public $success = true;
	public $moved = array();
	public function is_file( $path ) { return true; }
	public function move( $from, $to ) { $this->moved = array( $from, $to ); return $this->success; }
}
$GLOBALS['wp_filesystem'] = new Test_Filesystem();
same( '/tmp/package/custom-folder/', $updater->preserve_directory( '/tmp/package/wordpress-doctype-Inserter/', '/tmp/package/', null, array( 'plugin' => 'custom-folder/doctype-inserter.php' ) ), 'Keep an existing custom installation directory' );
same( '/source/', $updater->preserve_directory( '/source/', '/tmp/', null, array( 'plugin' => 'other/plugin.php' ) ), 'Do not rename other plugins' );
$GLOBALS['wp_filesystem']->success = false;
same( true, is_wp_error( $updater->preserve_directory( '/source/', '/tmp/', null, array( 'plugin' => 'custom-folder/doctype-inserter.php' ) ) ), 'A failed move aborts safely' );
$GLOBALS['cache'] = $parsed;
$updater->clear_cache_after_update( null, array( 'type' => 'plugin', 'action' => 'update', 'plugins' => array( 'custom-folder/doctype-inserter.php' ) ) );
same( false, $GLOBALS['cache'], 'Clear metadata after bulk update' );
echo "Passed $count regression assertions on PHP " . PHP_VERSION . ".\n";
