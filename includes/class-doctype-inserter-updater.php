<?php
/** Updates from stable releases in this plugin's public GitHub repository. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Doctype_Inserter_Updater {
	const REPOSITORY = 'https://github.com/cemfirat/wordpress-doctype-Inserter';
	const API_URL = 'https://api.github.com/repos/cemfirat/wordpress-doctype-Inserter/releases/latest';
	const CACHE_KEY = 'doctype_inserter_release_v1';
	const SLUG = 'doctype-inserter';
	private $basename;

	public function __construct( $file ) {
		$this->basename = plugin_basename( $file );
		add_filter( 'update_plugins_github.com', array( $this, 'check_update' ), 10, 3 );
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'preserve_directory' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( $this, 'clear_cache_after_update' ), 10, 2 );
		add_action( 'load-update-core.php', array( $this, 'maybe_force_check' ) );
	}

	/** WordPress compares the returned version and controls auto-update preferences. */
	public function check_update( $update, $plugin_data, $plugin_file ) {
		if ( $plugin_file !== $this->basename ) {
			return $update;
		}
		$release = $this->get_release();
		if ( ! $release ) {
			return $update;
		}
		return array(
			'id'           => self::REPOSITORY,
			'slug'         => self::SLUG,
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires'     => $release['requires'],
			'requires_php' => $release['requires_php'],
		);
	}

	/** Allow WordPress's explicit Check Again action to bypass our metadata cache. */
	public function maybe_force_check() {
		if ( current_user_can( 'update_plugins' ) && isset( $_GET['force-check'] ) && '1' === $_GET['force-check'] ) {
			delete_site_transient( self::CACHE_KEY );
		}
	}

	/** Cache successful lookups for six hours and failures for fifteen minutes. */
	public function get_release() {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return is_array( $cached ) && ! empty( $cached['version'] ) ? $cached : false;
		}
		$response = wp_remote_get(
			self::API_URL,
			array(
				'timeout'             => 8,
				'redirection'         => 0,
				'limit_response_size' => 131072,
				'headers'             => array(
					'Accept'               => 'application/vnd.github+json',
					'X-GitHub-Api-Version' => '2022-11-28',
					'User-Agent'           => 'Doctype-Inserter/' . DOCTYPE_INSERTER_VERSION,
				),
			)
		);
		$release = false;
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$release = self::parse_release( json_decode( wp_remote_retrieve_body( $response ), true ) );
		}
		set_site_transient( self::CACHE_KEY, $release ? $release : array(), $release ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );
		return $release;
	}

	/** Accept only complete stable releases with our exact ZIP asset and requirements. */
	public static function parse_release( $data ) {
		if ( ! is_array( $data ) || ! isset( $data['draft'], $data['prerelease'], $data['tag_name'], $data['body'], $data['assets'] )
			|| false !== $data['draft'] || false !== $data['prerelease'] || ! is_string( $data['tag_name'] )
			|| ! is_string( $data['body'] ) || ! is_array( $data['assets'] )
			|| ! preg_match( '/\Av([0-9]+\.[0-9]+\.[0-9]+)\z/', $data['tag_name'], $version ) ) {
			return false;
		}
		// Requirements are generated from plugin headers by the release workflow.
		if ( ! preg_match( '/^Requires WordPress: ([0-9]+\.[0-9]+(?:\.[0-9]+)?)\r?$/m', $data['body'], $wp )
			|| ! preg_match( '/^Requires PHP: ([0-9]+\.[0-9]+(?:\.[0-9]+)?)\r?$/m', $data['body'], $php ) ) {
			return false;
		}
		$package = self::REPOSITORY . '/releases/download/' . $data['tag_name'] . '/doctype-inserter.zip';
		foreach ( $data['assets'] as $asset ) {
			if ( is_array( $asset ) && isset( $asset['name'], $asset['browser_download_url'], $asset['state'], $asset['size'] )
				&& 'doctype-inserter.zip' === $asset['name'] && $package === $asset['browser_download_url']
				&& 'uploaded' === $asset['state'] && is_numeric( $asset['size'] ) && $asset['size'] > 0 ) {
				return array(
					'version'      => $version[1],
					'url'          => self::REPOSITORY . '/releases/tag/' . $data['tag_name'],
					'package'      => $package,
					'requires'     => $wp[1],
					'requires_php' => $php[1],
					'notes'        => $data['body'],
				);
			}
		}
		return false;
	}

	/** Supply the native View version details dialog without rendering remote HTML. */
	public function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! is_object( $args ) || ! isset( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}
		$release = $this->get_release();
		if ( ! $release ) {
			return $result;
		}
		return (object) array(
			'name'          => 'Doctype Inserter',
			'slug'          => self::SLUG,
			'version'       => $release['version'],
			'author'        => 'Cem Firat',
			'homepage'      => self::REPOSITORY,
			'requires'      => $release['requires'],
			'requires_php'  => $release['requires_php'],
			'download_link' => $release['package'],
			'sections'      => array(
				'description' => '<p>Inserts a custom snippet immediately after the HTML doctype on front-end pages.</p>',
				'changelog'   => '<pre>' . esc_html( $release['notes'] ) . '</pre>',
			),
		);
	}

	/** Keep existing folder names so updates do not create a second plugin or deactivate it. */
	public function preserve_directory( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( ! is_array( $hook_extra ) || ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $source;
		}
		$directory = dirname( $this->basename );
		// A legacy single-file installation can be updated manually into the packaged folder.
		if ( '.' === $directory ) {
			return new WP_Error( 'doctype_inserter_single_file', 'Install the release ZIP in its own plugin folder once before using automatic updates.' );
		}
		$destination = trailingslashit( $remote_source ) . $directory . '/';
		if ( untrailingslashit( $source ) === untrailingslashit( $destination ) ) {
			return $source;
		}
		global $wp_filesystem;
		if ( ! $wp_filesystem || ! $wp_filesystem->is_file( trailingslashit( $source ) . 'doctype-inserter.php' )
			|| ! $wp_filesystem->move( $source, $destination ) ) {
			return new WP_Error( 'doctype_inserter_move_failed', 'The update could not preserve the existing plugin directory. Please check filesystem permissions and try again.' );
		}
		return $destination;
	}

	public function clear_cache_after_update( $upgrader, $options ) {
		if ( isset( $options['type'], $options['action'] ) && 'plugin' === $options['type'] && 'update' === $options['action']
			&& ( ( isset( $options['plugin'] ) && $options['plugin'] === $this->basename )
				|| ( isset( $options['plugins'] ) && in_array( $this->basename, $options['plugins'], true ) ) ) ) {
			delete_site_transient( self::CACHE_KEY );
		}
	}
}
