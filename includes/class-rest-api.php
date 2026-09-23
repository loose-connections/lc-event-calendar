<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers GET /wp-json/lc/v1/events, which the calendar's JavaScript
 * fetches. Combines both post types into one JSON array, expanding all
 * three workshop schedule types into calendar-ready entries. Recurring
 * workshops are expressed as FullCalendar recurring-event objects, which
 * FullCalendar expands itself with no extra JS needed.
 */
class LC_Event_Calendar_REST {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route(
			'lc/v1',
			'/events',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_events' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function get_events() {
		$events = array();

		self::add_events( $events );
		self::add_workshops( $events );

		return rest_ensure_response( $events );
	}

	/**
	 * get_the_title() runs the 'the_title' filter, which includes
	 * wptexturize() - that turns a straight apostrophe into the HTML
	 * entity &#8217; as literal text inside the string (not a decoded
	 * character). Sent to the browser as-is and set via textContent,
	 * that shows up as the literal characters "&#8217;" instead of an
	 * apostrophe. Decoding entities here, once, keeps every consumer of
	 * this endpoint (the calendar tooltip, any future use) simple.
	 */
	private static function clean_text( $text ) {
		if ( ! $text ) {
			return $text;
		}

		return html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Events: unchanged, single start/end date fields.
	 */
	private static function add_events( array &$events ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'event',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		foreach ( $query->posts as $post ) {
			$start_raw = get_field( 'lc_event_start_date', $post->ID, false );
			$end_raw   = get_field( 'lc_event_end_date', $post->ID, false );

			if ( ! $start_raw ) {
				continue;
			}

			$events[] = array(
				'id'          => $post->ID,
				'title'       => self::clean_text( get_the_title( $post ) ),
				'start'       => str_replace( ' ', 'T', $start_raw ),
				'end'         => $end_raw ? str_replace( ' ', 'T', $end_raw ) : null,
				'url'         => get_permalink( $post ),
				'type'        => 'event',
				'description' => self::clean_text( get_field( 'lc_event_short_description', $post->ID ) ),
				'image'       => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
			);
		}
	}

	/**
	 * Workshops: three possible schedule types.
	 */
	private static function add_workshops( array &$events ) {
		$query = new WP_Query(
			array(
				'post_type'      => 'workshop',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			)
		);

		foreach ( $query->posts as $post ) {
			$title       = self::clean_text( get_the_title( $post ) );
			$url         = get_permalink( $post );
			$schedule    = get_field( 'lc_schedule_type', $post->ID );
			$description = self::clean_text( get_field( 'lc_workshop_short_description', $post->ID ) );
			$image       = get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null;

			if ( 'dates' === $schedule ) {
				self::add_specific_dates( $events, $post->ID, $title, $url, $description, $image );
				continue;
			}

			if ( 'recurring_weekly' === $schedule ) {
				self::add_recurring_weekly( $events, $post->ID, $title, $url, $description, $image );
				continue;
			}

			if ( 'recurring_monthly' === $schedule ) {
				self::add_recurring_monthly( $events, $post->ID, $title, $url, $description, $image );
				continue;
			}
		}
	}

	/**
	 * Specific dates: one calendar entry per session row.
	 *
	 * No "false" here on purpose: for a repeater, ACF's raw mode keys each
	 * row by its internal field key instead of its name, which breaks the
	 * lookups below. The normal, formatted fetch keeps the field names we
	 * need. See the "Repeater raw-vs-formatted bug" note in the README.
	 */
	private static function add_specific_dates( array &$events, $post_id, $title, $url, $description, $image ) {
		$sessions = get_field( 'lc_workshop_sessions', $post_id );

		if ( ! $sessions || ! is_array( $sessions ) ) {
			return;
		}

		foreach ( $sessions as $row ) {
			$start_raw = $row['lc_session_start'] ?? null;
			$end_raw   = $row['lc_session_end'] ?? null;

			if ( ! $start_raw ) {
				continue;
			}

			$events[] = array(
				'id'          => $post_id . '-' . md5( $start_raw ),
				'title'       => $title,
				'start'       => str_replace( ' ', 'T', $start_raw ),
				'end'         => $end_raw ? str_replace( ' ', 'T', $end_raw ) : null,
				'url'         => $url,
				'type'        => 'workshop',
				'description' => $description,
				'image'       => $image,
			);
		}
	}

	/**
	 * Repeats weekly: one recurring definition, FullCalendar expands it itself.
	 */
	private static function add_recurring_weekly( array &$events, $post_id, $title, $url, $description, $image ) {
		$days       = get_field( 'lc_recur_days', $post_id );
		$start_time = get_field( 'lc_recur_start_time', $post_id );
		$end_time   = get_field( 'lc_recur_end_time', $post_id );
		$recur_from = get_field( 'lc_recur_start_date', $post_id );
		$recur_to   = get_field( 'lc_recur_end_date', $post_id );

		if ( ! $days || ! $start_time ) {
			return;
		}

		// convert Monday=1..Sunday=7 into FullCalendar's Sunday=0..Saturday=6
		$fc_days = array_map(
			function ( $d ) {
				return (int) $d % 7;
			},
			$days
		);

		$events[] = array(
			'id'          => $post_id . '-weekly',
			'title'       => $title,
			'daysOfWeek'  => $fc_days,
			'startTime'   => $start_time,
			'endTime'     => $end_time ?: null,
			'startRecur'  => $recur_from ? self::ymd_to_dashes( $recur_from ) : null,
			'endRecur'    => $recur_to ? self::ymd_to_dashes( $recur_to ) : null,
			'url'         => $url,
			'type'        => 'workshop',
			'description' => $description,
			'image'       => $image,
		);
	}

	/**
	 * Repeats monthly: work out the actual matching dates ourselves, from
	 * three months in the past to twelve months ahead (or the recurrence's
	 * own from/to range if narrower).
	 */
	private static function add_recurring_monthly( array &$events, $post_id, $title, $url, $description, $image ) {
		$week_num   = get_field( 'lc_recur_month_week', $post_id );
		$day_num    = get_field( 'lc_recur_month_day', $post_id );
		$start_time = get_field( 'lc_recur_start_time', $post_id );
		$end_time   = get_field( 'lc_recur_end_time', $post_id );
		$recur_from = get_field( 'lc_recur_start_date', $post_id );
		$recur_to   = get_field( 'lc_recur_end_date', $post_id );

		if ( ! $week_num || ! $day_num || ! $start_time ) {
			return;
		}

		$day_names  = array( 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday' );
		$week_names = array( 1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', -1 => 'last' );

		$today         = new DateTime( 'today' );
		$horizon_start = ( clone $today )->modify( '-3 months' );
		$horizon_end   = ( clone $today )->modify( '+12 months' );

		$window_start = $horizon_start;
		if ( $recur_from ) {
			$from_date = DateTime::createFromFormat( 'Ymd', $recur_from );
			if ( $from_date && $from_date > $window_start ) {
				$window_start = $from_date;
			}
		}

		$window_end = $horizon_end;
		if ( $recur_to ) {
			$to_date = DateTime::createFromFormat( 'Ymd', $recur_to );
			if ( $to_date && $to_date < $window_end ) {
				$window_end = $to_date;
			}
		}

		$week_label = $week_names[ (int) $week_num ] ?? null;
		$day_label  = $day_names[ (int) $day_num ] ?? null;

		if ( ! $week_label || ! $day_label ) {
			return;
		}

		$cursor = new DateTime( $window_start->format( 'Y-m-01' ) );

		while ( $cursor <= $window_end ) {
			$month_label = $cursor->format( 'F Y' );
			$occurrence  = strtotime( "{$week_label} {$day_label} of {$month_label}" );

			if ( false !== $occurrence ) {
				$occurrence_date = ( new DateTime() )->setTimestamp( $occurrence );

				if ( $occurrence_date >= $window_start && $occurrence_date <= $window_end ) {
					$start_dt = $occurrence_date->format( 'Y-m-d' ) . 'T' . $start_time;
					$end_dt   = $end_time ? $occurrence_date->format( 'Y-m-d' ) . 'T' . $end_time : null;

					$events[] = array(
						'id'          => $post_id . '-' . $occurrence_date->format( 'Ymd' ),
						'title'       => $title,
						'start'       => $start_dt,
						'end'         => $end_dt,
						'url'         => $url,
						'type'        => 'workshop',
						'description' => $description,
						'image'       => $image,
					);
				}
			}

			$cursor->modify( '+1 month' );
		}
	}

	private static function ymd_to_dashes( $ymd ) {
		$date = DateTime::createFromFormat( 'Ymd', $ymd );
		return $date ? $date->format( 'Y-m-d' ) : null;
	}
}
