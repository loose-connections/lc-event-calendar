<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Self-hosted auto-updates via GitHub Releases, using WordPress's native
 * "Update URI" plugin header mechanism (built into WP core since 5.8 -
 * no bundled third-party library needed). Once this is set up, a new
 * release shows up as a normal "Update available" notice on the Plugins
 * page with a one-click update, exactly like a WordPress.org plugin.
 *
 * SETUP (see README.md "Auto-updates" for the full walkthrough):
 *   1. Push this plugin to a GitHub repo.
 *   2. Change the default in self::repo_slug() below from
 *      'your-org/lc-event-calendar' to your real "owner/repo".
 *   3. Install this build on a site once, as normal (manually).
 *   4. For every release after that: bump the Version header in
 *      lc-event-calendar.php, commit, then create a GitHub Release
 *      whose tag matches the version (e.g. "1.0.2"), and attach the
 *      built lc-event-calendar.zip to it as a release asset. Sites
 *      pick up the update within the cache window below (or
 *      immediately if someone clicks "Check again" on the Plugins
 *      page).
 *
 * If the GitHub repo is private, set a fine-grained personal access
 * token (read-only, contents) via the 'lc_event_calendar_github_token'
 * filter - do this per site (e.g. in a small mu-plugin), never by
 * committing the token into this plugin's own repo.
 */
class LC_Event_Calendar_Updater {

	const CACHE_KEY = 'lc_event_calendar_update_check';

	public static function init() {
		add_filter( 'update_plugins_github.com', array( __CLASS__, 'check_for_update' ), 10, 3 );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 10, 3 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ) );
	}

	/**
	 * "owner/repo" on GitHub, e.g. 'loose-connections/lc-event-calendar'.
	 * Change the default here once before the first release, or override
	 * per site with the 'lc_event_calendar_github_repo' filter.
	 */
	private static function repo_slug() {
		return apply_filters( 'lc_event_calendar_github_repo', 'loose-connections/lc-event-calendar' );
	}

	private static function github_token() {
		return apply_filters( 'lc_event_calendar_github_token', '' );
	}

	public static function check_for_update( $update, $plugin_data, $plugin_file ) {
		if ( plugin_basename( LC_EVENT_CALENDAR_FILE ) !== $plugin_file ) {
			return $update;
		}

		$release = self::get_latest_release();

		if ( ! $release || empty( $release['tag_name'] ) ) {
			return $update;
		}

		$remote_version = ltrim( $release['tag_name'], 'v' );

		if ( ! version_compare( $remote_version, $plugin_data['Version'], '>' ) ) {
			return $update;
		}

		$package = self::get_package_url( $release );

		if ( ! $package ) {
			return $update;
		}

		return array(
			'id'          => 'github.com/' . self::repo_slug(),
			'slug'        => dirname( $plugin_file ),
			'plugin'      => $plugin_file,
			'new_version' => $remote_version,
			'url'         => 'https://github.com/' . self::repo_slug(),
			'package'     => $package,
		);
	}

	/**
	 * Looks for a .zip file attached to the release as an asset (the same
	 * built zip you'd otherwise upload by hand) - its top-level folder is
	 * already named "lc-event-calendar", so WordPress can install it
	 * directly with no further work. If a release has no attached zip,
	 * no update is offered (rather than guessing at GitHub's auto-generated
	 * source archive, whose folder name would need renaming mid-install).
	 */
	private static function get_package_url( $release ) {
		if ( empty( $release['assets'] ) || ! is_array( $release['assets'] ) ) {
			return null;
		}

		foreach ( $release['assets'] as $asset ) {
			$name = $asset['name'] ?? '';
			if ( ! empty( $asset['browser_download_url'] ) && preg_match( '/\.zip$/i', $name ) ) {
				return $asset['browser_download_url'];
			}
		}

		return null;
	}

	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || 'lc-event-calendar' !== $args->slug ) {
			return $result;
		}

		$release = self::get_latest_release();

		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'LC Event Calendar',
			'slug'          => 'lc-event-calendar',
			'version'       => ltrim( $release['tag_name'] ?? '', 'v' ),
			'author'        => '<a href="https://www.looseconnections.uk">Loose Connections</a>',
			'homepage'      => 'https://github.com/' . self::repo_slug(),
			'sections'      => array(
				'description' => 'A combined Events and Workshops "What\'s On" calendar.',
				'changelog'   => wpautop( wp_kses_post( $release['body'] ?? '' ) ),
			),
			'download_link' => self::get_package_url( $release ),
		);
	}

	private static function get_latest_release() {
		$cached = get_site_transient( self::CACHE_KEY );

		if ( false !== $cached ) {
			return $cached ?: null;
		}

		$args = array(
			'headers' => array( 'Accept' => 'application/vnd.github+json' ),
			'timeout' => 10,
		);

		$token = self::github_token();
		if ( $token ) {
			$args['headers']['Authorization'] = 'Bearer ' . $token;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::repo_slug() . '/releases/latest',
			$args
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Cache the miss too (briefly), so a misconfigured repo slug or
			// a rate limit doesn't get hit on every admin page load.
			set_site_transient( self::CACHE_KEY, array(), HOUR_IN_SECONDS );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			return null;
		}

		set_site_transient( self::CACHE_KEY, $body, 12 * HOUR_IN_SECONDS );

		return $body;
	}

	public static function clear_cache() {
		delete_site_transient( self::CACHE_KEY );
	}
}
