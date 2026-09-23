<?php
/**
 * Plugin Name: LC Event Calendar
 * Plugin URI: https://www.looseconnections.uk
 * Description: A combined "What's On" calendar for Events and Workshops (month grid and list view), with three workshop scheduling modes: specific dates, weekly recurrence and monthly recurrence. Built by Loose Connections.
 * Version: 1.1.0
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Author: Loose Connections
 * Author URI: https://www.looseconnections.uk
 * Text Domain: lc-event-calendar
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI: https://github.com/your-org/lc-event-calendar
 *
 * The "Update URI" line above is what makes this plugin check GitHub for
 * updates instead of WordPress.org. Its host (github.com) just needs to
 * stay a real github.com URL; the actual repo it checks is set in
 * includes/class-updater.php. See README.md "Auto-updates" before
 * relying on this on a live site.
 *
 * LC Event Calendar requires Advanced Custom Fields Pro (for the Repeater
 * field type used by the workshop's "Specific dates" schedule) and,
 * for shortcode placement, works well with Elementor Pro, though the
 * shortcodes can be dropped into any theme or page builder.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LC_EVENT_CALENDAR_VERSION', '1.1.0' );
define( 'LC_EVENT_CALENDAR_FILE', __FILE__ );
define( 'LC_EVENT_CALENDAR_PATH', plugin_dir_path( __FILE__ ) );
define( 'LC_EVENT_CALENDAR_URL', plugin_dir_url( __FILE__ ) );

require_once LC_EVENT_CALENDAR_PATH . 'includes/class-post-types.php';
require_once LC_EVENT_CALENDAR_PATH . 'includes/class-acf-fields.php';
require_once LC_EVENT_CALENDAR_PATH . 'includes/class-rest-api.php';
require_once LC_EVENT_CALENDAR_PATH . 'includes/class-calendar.php';
require_once LC_EVENT_CALENDAR_PATH . 'includes/class-shortcodes.php';
require_once LC_EVENT_CALENDAR_PATH . 'includes/class-updater.php';

/**
 * Main plugin bootstrap. Kept as a single class with static methods so
 * nothing here collides with other plugins or the active theme.
 */
final class LC_Event_Calendar {

	/**
	 * Wire up every sub-module. Each include file only registers its own
	 * hooks here; none of them run any logic just by being loaded.
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'check_dependencies' ) );

		LC_Event_Calendar_Post_Types::init();
		LC_Event_Calendar_ACF_Fields::init();
		LC_Event_Calendar_REST::init();
		LC_Event_Calendar_Calendar::init();
		LC_Event_Calendar_Shortcodes::init();
		LC_Event_Calendar_Updater::init();
	}

	/**
	 * ACF Pro is a hard requirement (the Repeater field powers "Specific
	 * dates" workshops). Warn in wp-admin rather than fatal-erroring the
	 * site if it's missing or inactive.
	 */
	public static function check_dependencies() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'missing_acf_notice' ) );
		}
	}

	public static function missing_acf_notice() {
		echo '<div class="notice notice-error"><p>' .
			esc_html__( 'LC Event Calendar requires Advanced Custom Fields PRO (for the Repeater field) to be installed and active. The calendar and shortcodes will not work correctly until it is.', 'lc-event-calendar' ) .
			'</p></div>';
	}

	/**
	 * Post types need to be registered before flushing rewrite rules on
	 * activation, so we register them directly here rather than waiting
	 * for the 'init' hook to fire on its own.
	 */
	public static function activate() {
		LC_Event_Calendar_Post_Types::register();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}

LC_Event_Calendar::init();

register_activation_hook( __FILE__, array( 'LC_Event_Calendar', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LC_Event_Calendar', 'deactivate' ) );
