<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads FullCalendar v6 from CDN (the "global" bundle, which includes both
 * month grid and list view, no separate plugins needed), enqueues this
 * plugin's own CSS/JS on top of it, and registers the [lc_calendar]
 * shortcode.
 *
 * Colours are filterable per site with 'lc_event_calendar_colors' rather
 * than hardcoded, so this plugin can be dropped onto any client site and
 * re-themed from that site's own functions.php or a snippet, without
 * editing plugin files.
 */
class LC_Event_Calendar_Calendar {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_shortcode( 'lc_calendar', array( __CLASS__, 'render_shortcode' ) );
	}

	public static function get_colors() {
		$defaults = array(
			'event'      => '#2563EB',
			'workshop'   => '#F97316',
			'text'       => '#111827',
			'light_bg'   => '#F3F4F6',
			'border'     => '#E5E7EB',
			'hover'      => '#1D4ED8',
			'today_tint' => 'rgba(249, 115, 22, 0.08)',
		);

		return wp_parse_args( apply_filters( 'lc_event_calendar_colors', array() ), $defaults );
	}

	public static function enqueue_assets() {
		wp_enqueue_style( 'fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@6/index.global.min.css', array(), null );
		wp_enqueue_script( 'fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6/index.global.min.js', array(), null, true );

		wp_enqueue_style(
			'lc-event-calendar',
			LC_EVENT_CALENDAR_URL . 'assets/css/calendar.css',
			array( 'fullcalendar-css' ),
			LC_EVENT_CALENDAR_VERSION
		);

		$colors = self::get_colors();

		// The CSS file itself is a static asset with no PHP inside it; the
		// only per-site value injected is this small block of CSS custom
		// properties, which the stylesheet above reads with var(...).
		$color_css = sprintf(
			':root, #lc-calendar { --lc-event-color: %1$s; --lc-workshop-color: %2$s; --lc-text-color: %3$s; --lc-light-bg: %4$s; --lc-border-color: %5$s; --lc-hover-color: %6$s; --lc-today-tint: %7$s; }',
			esc_html( $colors['event'] ),
			esc_html( $colors['workshop'] ),
			esc_html( $colors['text'] ),
			esc_html( $colors['light_bg'] ),
			esc_html( $colors['border'] ),
			esc_html( $colors['hover'] ),
			esc_html( $colors['today_tint'] )
		);
		wp_add_inline_style( 'lc-event-calendar', $color_css );

		wp_enqueue_script(
			'lc-event-calendar',
			LC_EVENT_CALENDAR_URL . 'assets/js/calendar.js',
			array( 'fullcalendar-js' ),
			LC_EVENT_CALENDAR_VERSION,
			true
		);

		// Everything the static JS file needs from PHP goes through this
		// localized object rather than being written into the script
		// itself, so calendar.js never has to interpolate PHP values.
		wp_localize_script(
			'lc-event-calendar',
			'lcEventCalendar',
			array(
				'restUrl'       => esc_url_raw( rest_url( 'lc/v1/events' ) ),
				'eventColor'    => $colors['event'],
				'workshopColor' => $colors['workshop'],
			)
		);
	}

	public static function render_shortcode() {
		return '<div id="lc-calendar"></div>';
	}
}
