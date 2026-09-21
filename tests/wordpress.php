<?php
/** Run with wp eval-file tests/wordpress.php after activating the packaged plugin. */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

function di_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	WP_CLI::log( 'PASS: ' . $message );
}
$basename = plugin_basename( DOCTYPE_INSERTER_FILE );
wp_set_current_user( 1 );
doctype_inserter_register_settings();
$snippet = '<!-- existing $1 \\1 -->';
update_option( 'doctype_inserter_text', $snippet );
di_assert( $snippet === get_option( 'doctype_inserter_text' ), 'A trusted administrator can save literal snippets.' );
$editor = wp_insert_user( array( 'user_login' => 'di_editor', 'user_pass' => wp_generate_password(), 'role' => 'editor' ) );
di_assert( ! is_wp_error( $editor ), 'Create a restricted test user.' );
wp_set_current_user( $editor );
update_option( 'doctype_inserter_text', '<script>unauthorized</script>' );
di_assert( $snippet === get_option( 'doctype_inserter_text' ), 'The real Settings API rejects unauthorized changes.' );
wp_set_current_user( 1 );

$release = array(
	'tag_name' => 'v1.3.0', 'draft' => false, 'prerelease' => false,
	'body' => "Requires WordPress: 5.8\nRequires PHP: 7.4\nIntegration fixture",
	'assets' => array( array( 'name' => 'doctype-inserter.zip', 'state' => 'uploaded', 'size' => 100,
		'browser_download_url' => Doctype_Inserter_Updater::REPOSITORY . '/releases/download/v1.3.0/doctype-inserter.zip' ) ),
);
$GLOBALS['di_release'] = $release;
add_filter( 'pre_http_request', function ( $preempt, $args, $url ) {
	if ( Doctype_Inserter_Updater::API_URL === $url ) {
		return array( 'headers' => array(), 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( $GLOBALS['di_release'] ) );
	}
	if ( false !== strpos( $url, 'api.wordpress.org/plugins/update-check/' ) ) {
		return array( 'headers' => array(), 'response' => array( 'code' => 200 ), 'body' => wp_json_encode( array( 'plugins' => array(), 'no_update' => array(), 'translations' => array() ) ) );
	}
	return $preempt;
}, 10, 3 );
delete_site_transient( Doctype_Inserter_Updater::CACHE_KEY );
delete_site_transient( 'update_plugins' );
wp_update_plugins();
$updates = get_site_transient( 'update_plugins' );
di_assert( isset( $updates->response[ $basename ] ), 'WordPress discovers a newer GitHub release.' );
di_assert( '1.3.0' === $updates->response[ $basename ]->new_version, 'WordPress receives the advertised release version.' );
di_assert( '5.8' === $updates->response[ $basename ]->requires, 'WordPress receives minimum platform requirements.' );

$GLOBALS['di_release']['tag_name'] = 'v' . DOCTYPE_INSERTER_VERSION;
$GLOBALS['di_release']['assets'][0]['browser_download_url'] = Doctype_Inserter_Updater::REPOSITORY . '/releases/download/v' . DOCTYPE_INSERTER_VERSION . '/doctype-inserter.zip';
delete_site_transient( Doctype_Inserter_Updater::CACHE_KEY );
delete_site_transient( 'update_plugins' );
wp_update_plugins();
$updates = get_site_transient( 'update_plugins' );
di_assert( ! isset( $updates->response[ $basename ] ) && isset( $updates->no_update[ $basename ] ), 'An equal release does not produce an update notification.' );

// Exercise the real ZIP upgrader with the actual release artifact at a local path.
// The test advertises a newer version but installs the current artifact to verify
// directory handling, state preservation and package structure without a fake release.
$updates->response[ $basename ] = (object) array( 'slug' => 'doctype-inserter', 'plugin' => $basename,
	'new_version' => '1.3.0', 'package' => getenv( 'DI_PACKAGE' ), 'url' => Doctype_Inserter_Updater::REPOSITORY );
set_site_transient( 'update_plugins', $updates );
$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
$result = $upgrader->bulk_upgrade( array( $basename ), array( 'clear_update_cache' => false ) );
di_assert( is_array( $result ) && isset( $result[ $basename ] ) && is_array( $result[ $basename ] ), 'The real WordPress ZIP upgrader succeeds.' );
di_assert( file_exists( WP_PLUGIN_DIR . '/' . $basename ), 'The existing plugin directory is preserved.' );
di_assert( is_plugin_active( $basename ), 'The plugin remains active after a bulk update.' );
di_assert( $snippet === get_option( 'doctype_inserter_text' ), 'The saved snippet survives an update.' );
WP_CLI::success( 'WordPress integration checks passed on ' . get_bloginfo( 'version' ) . '.' );
