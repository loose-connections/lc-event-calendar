<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Four shortcodes for use on an individual event/workshop's own page (via
 * a shortcode block or widget, on a shared single template or on
 * individual posts). All default to the current post if no post_id
 * attribute is given.
 */
class LC_Event_Calendar_Shortcodes {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

		add_shortcode( 'lc_schedule_rule', array( __CLASS__, 'schedule_rule' ) );
		add_shortcode( 'lc_next_date', array( __CLASS__, 'next_date' ) );
		add_shortcode( 'lc_upcoming_dates', array( __CLASS__, 'upcoming_dates' ) );
		add_shortcode( 'lc_upcoming_events', array( __CLASS__, 'upcoming_dates' ) );
	}

	public static function enqueue_assets() {
		wp_enqueue_style(
			'lc-event-calendar-shortcodes',
			LC_EVENT_CALENDAR_URL . 'assets/css/shortcodes.css',
			array(),
			LC_EVENT_CALENDAR_VERSION
		);
	}

	/**
	 * [lc_schedule_rule] — the recurrence RULE only, e.g.
	 * "Every Tuesday" / "2:00PM - 4:00PM" / "Running from 1 September 2026 to 31 December 2026"
	 * Only produces output for workshops set to Repeats weekly or Repeats
	 * monthly. Events and Specific-dates workshops don't have a "rule", so
	 * this returns nothing for them.
	 */
	public static function schedule_rule( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
			),
			$atts
		);

		$post_id = (int) $atts['post_id'];

		if ( ! $post_id ) {
			return '';
		}

		$summary = self::get_schedule_summary( $post_id );

		if ( ! $summary ) {
			return '';
		}

		$html  = '<div class="lc-schedule-summary"><span class="lc-day-line">' . esc_html( $summary['pattern'] ) . '</span>';
		if ( $summary['time'] ) {
			$html .= '<span class="lc-time-line">' . esc_html( $summary['time'] ) . '</span>';
		}
		if ( $summary['range'] ) {
			$html .= '<span class="lc-range">' . esc_html( $summary['range'] ) . '</span>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * [lc_next_date] — just the SINGLE next upcoming date, plain text,
	 * styled like the schedule rule (no box/background).
	 */
	public static function next_date( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
			),
			$atts
		);

		$post_id = (int) $atts['post_id'];

		if ( ! $post_id ) {
			return '';
		}

		$sessions = self::get_upcoming_sessions( $post_id, 1 );

		if ( empty( $sessions ) ) {
			return '<p class="lc-next-date-empty">' . esc_html__( 'No upcoming dates at the moment.', 'lc-event-calendar' ) . '</p>';
		}

		$session = $sessions[0];
		$start   = $session['start'];
		$end     = $session['end'];

		$date_label = $start->format( 'l, j F Y' );
		$time_label = self::format_time( $start->format( 'H:i:s' ) );

		if ( $end ) {
			$time_label .= ' - ' . self::format_time( $end->format( 'H:i:s' ) );
		}

		return '<div class="lc-next-date-block"><span class="lc-day-line">' . esc_html( $date_label ) . '</span><span class="lc-time-line">' . esc_html( $time_label ) . '</span></div>';
	}

	/**
	 * [lc_upcoming_dates] / [lc_upcoming_events] — every ACTUAL upcoming
	 * date, one by one, in a boxed list. The two shortcode tags are
	 * functionally identical, so they can be used independently wherever
	 * a list of dates is needed.
	 */
	public static function upcoming_dates( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
				'limit'   => 6,
			),
			$atts
		);

		$post_id = (int) $atts['post_id'];
		$limit   = (int) $atts['limit'];

		if ( ! $post_id ) {
			return '';
		}

		$post_type = get_post_type( $post_id );
		$sessions  = self::get_upcoming_sessions( $post_id, $limit );

		if ( empty( $sessions ) ) {
			return '<p class="lc-upcoming-dates-empty">' . esc_html__( 'No upcoming dates at the moment.', 'lc-event-calendar' ) . '</p>';
		}

		$type_class = 'workshop' === $post_type ? 'lc-upcoming-dates--workshop' : 'lc-upcoming-dates--event';

		$html = '<ul class="lc-upcoming-dates ' . esc_attr( $type_class ) . '">';

		foreach ( $sessions as $session ) {
			$start = $session['start'];
			$end   = $session['end'];

			$date_label = $start->format( 'l, j F Y' );
			$time_label = self::format_time( $start->format( 'H:i:s' ) );

			if ( $end ) {
				$time_label .= ' - ' . self::format_time( $end->format( 'H:i:s' ) );
			}

			$html .= '<li><span class="lc-date">' . esc_html( $date_label ) . '</span><span class="lc-time">' . esc_html( $time_label ) . '</span></li>';
		}

		$html .= '</ul>';

		return $html;
	}

	// ---- shared helpers ----

	private static function get_schedule_summary( $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( 'workshop' !== $post_type ) {
			return null;
		}

		$schedule = get_field( 'lc_schedule_type', $post_id );

		if ( 'recurring_weekly' === $schedule ) {
			$days       = get_field( 'lc_recur_days', $post_id );
			$start_time = get_field( 'lc_recur_start_time', $post_id );
			$end_time   = get_field( 'lc_recur_end_time', $post_id );
			$recur_from = get_field( 'lc_recur_start_date', $post_id );
			$recur_to   = get_field( 'lc_recur_end_date', $post_id );

			if ( ! $days || ! $start_time ) {
				return null;
			}

			$day_names  = array( 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday' );
			$day_labels = array_filter(
				array_map(
					function ( $d ) use ( $day_names ) {
						return $day_names[ (int) $d ] ?? '';
					},
					$days
				)
			);

			$day_pattern  = 'Every ' . implode( ' and ', $day_labels );
			$time_pattern = self::format_time( $start_time );
			if ( $end_time ) {
				$time_pattern .= ' - ' . self::format_time( $end_time );
			}

			return array(
				'pattern' => $day_pattern,
				'time'    => $time_pattern,
				'range'   => self::format_range( $recur_from, $recur_to ),
			);
		}

		if ( 'recurring_monthly' === $schedule ) {
			$week_num   = get_field( 'lc_recur_month_week', $post_id );
			$day_num    = get_field( 'lc_recur_month_day', $post_id );
			$start_time = get_field( 'lc_recur_start_time', $post_id );
			$end_time   = get_field( 'lc_recur_end_time', $post_id );
			$recur_from = get_field( 'lc_recur_start_date', $post_id );
			$recur_to   = get_field( 'lc_recur_end_date', $post_id );

			if ( ! $week_num || ! $day_num || ! $start_time ) {
				return null;
			}

			$day_names  = array( 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday' );
			$week_names = array( 1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', -1 => 'Last' );

			$day_pattern = trim( ( $week_names[ (int) $week_num ] ?? '' ) . ' ' . ( $day_names[ (int) $day_num ] ?? '' ) ) . ' of the month';

			$time_pattern = self::format_time( $start_time );
			if ( $end_time ) {
				$time_pattern .= ' - ' . self::format_time( $end_time );
			}

			return array(
				'pattern' => $day_pattern,
				'time'    => $time_pattern,
				'range'   => self::format_range( $recur_from, $recur_to ),
			);
		}

		return null;
	}

	private static function format_time( $time_24h ) {
		$t = DateTime::createFromFormat( 'H:i:s', $time_24h );
		return $t ? $t->format( 'g:iA' ) : $time_24h;
	}

	private static function format_range( $from_ymd, $to_ymd ) {
		$from = $from_ymd ? DateTime::createFromFormat( 'Ymd', $from_ymd ) : null;
		$to   = $to_ymd ? DateTime::createFromFormat( 'Ymd', $to_ymd ) : null;

		if ( $from && $to ) {
			return 'Running from ' . $from->format( 'j F Y' ) . ' to ' . $to->format( 'j F Y' );
		}
		if ( $from ) {
			return 'Running from ' . $from->format( 'j F Y' );
		}
		return null;
	}

	/**
	 * Turns a raw ACF date/time value into a DateTime, trying the exact
	 * format ACF stores internally first, then falling back to a couple
	 * of other shapes just in case, so a single unexpected value never
	 * silently drops a whole date.
	 */
	private static function parse_datetime( $raw ) {
		$raw = is_string( $raw ) ? trim( $raw ) : '';

		if ( ! $raw ) {
			return null;
		}

		$formats = array( 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'd/m/Y H:i:s', 'd/m/Y H:i' );

		foreach ( $formats as $format ) {
			$dt = DateTime::createFromFormat( $format, $raw );
			if ( $dt instanceof DateTime ) {
				return $dt;
			}
		}

		$timestamp = strtotime( $raw );
		return $timestamp ? ( new DateTime() )->setTimestamp( $timestamp ) : null;
	}

	private static function get_upcoming_sessions( $post_id, $limit = 6 ) {
		$post_type = get_post_type( $post_id );
		$now       = new DateTime( 'now' );
		$sessions  = array();

		// --- Events: single date pair ---
		if ( 'event' === $post_type ) {
			$start_raw = get_field( 'lc_event_start_date', $post_id, false );
			$end_raw   = get_field( 'lc_event_end_date', $post_id, false );

			$start = self::parse_datetime( $start_raw );
			$end   = $end_raw ? self::parse_datetime( $end_raw ) : null;

			if ( $start ) {
				$sessions[] = array(
					'start' => $start,
					'end'   => $end,
				);
			}

			return $sessions;
		}

		// --- Workshops: three possible schedule types ---
		$schedule = get_field( 'lc_schedule_type', $post_id );

		if ( 'dates' === $schedule ) {
			// No "false" here on purpose: see the note in class-rest-api.php
			// about the repeater raw-vs-formatted bug.
			$rows = get_field( 'lc_workshop_sessions', $post_id );

			if ( $rows && is_array( $rows ) ) {
				foreach ( $rows as $row ) {
					$start_raw = $row['lc_session_start'] ?? null;
					$end_raw   = $row['lc_session_end'] ?? null;

					$start = self::parse_datetime( $start_raw );

					if ( ! $start ) {
						continue;
					}

					$end = $end_raw ? self::parse_datetime( $end_raw ) : null;

					if ( $start >= $now ) {
						$sessions[] = array(
							'start' => $start,
							'end'   => $end,
						);
					}
				}
			}

			usort(
				$sessions,
				function ( $a, $b ) {
					return $a['start'] <=> $b['start'];
				}
			);

			return array_slice( $sessions, 0, $limit );
		}

		if ( 'recurring_weekly' === $schedule ) {
			$days       = get_field( 'lc_recur_days', $post_id );
			$start_time = get_field( 'lc_recur_start_time', $post_id );
			$end_time   = get_field( 'lc_recur_end_time', $post_id );
			$recur_from = get_field( 'lc_recur_start_date', $post_id );
			$recur_to   = get_field( 'lc_recur_end_date', $post_id );

			if ( ! $days || ! $start_time ) {
				return array();
			}

			$from_date    = $recur_from ? DateTime::createFromFormat( 'Ymd', $recur_from ) : null;
			$window_start = ( $from_date && $from_date > $now ) ? $from_date : $now;
			$window_end   = $recur_to ? DateTime::createFromFormat( 'Ymd', $recur_to ) : ( clone $now )->modify( '+12 months' );

			// field choices use Monday=1..Sunday=7, same as PHP's 'N' format
			$target_days = array_map( 'intval', $days );

			$cursor = clone $window_start;
			$cursor->setTime( 0, 0 );

			while ( $cursor <= $window_end && count( $sessions ) < $limit ) {
				if ( in_array( (int) $cursor->format( 'N' ), $target_days, true ) ) {
					list( $h, $m ) = array_map( 'intval', explode( ':', $start_time ) );
					$start = ( clone $cursor )->setTime( $h, $m );

					if ( $start >= $now ) {
						$end = null;
						if ( $end_time ) {
							list( $eh, $em ) = array_map( 'intval', explode( ':', $end_time ) );
							$end = ( clone $cursor )->setTime( $eh, $em );
						}
						$sessions[] = array(
							'start' => $start,
							'end'   => $end,
						);
					}
				}
				$cursor->modify( '+1 day' );
			}

			return $sessions;
		}

		if ( 'recurring_monthly' === $schedule ) {
			$week_num   = get_field( 'lc_recur_month_week', $post_id );
			$day_num    = get_field( 'lc_recur_month_day', $post_id );
			$start_time = get_field( 'lc_recur_start_time', $post_id );
			$end_time   = get_field( 'lc_recur_end_time', $post_id );
			$recur_from = get_field( 'lc_recur_start_date', $post_id );
			$recur_to   = get_field( 'lc_recur_end_date', $post_id );

			if ( ! $week_num || ! $day_num || ! $start_time ) {
				return array();
			}

			$day_names  = array( 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday' );
			$week_names = array( 1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', -1 => 'last' );

			$week_label = $week_names[ (int) $week_num ] ?? null;
			$day_label  = $day_names[ (int) $day_num ] ?? null;

			if ( ! $week_label || ! $day_label ) {
				return array();
			}

			$from_date    = $recur_from ? DateTime::createFromFormat( 'Ymd', $recur_from ) : null;
			$window_start = ( $from_date && $from_date > $now ) ? $from_date : $now;
			$window_end   = $recur_to ? DateTime::createFromFormat( 'Ymd', $recur_to ) : ( clone $now )->modify( '+18 months' );

			$cursor = new DateTime( $window_start->format( 'Y-m-01' ) );

			while ( $cursor <= $window_end && count( $sessions ) < $limit ) {
				$month_label = $cursor->format( 'F Y' );
				$occurrence  = strtotime( "{$week_label} {$day_label} of {$month_label}" );

				if ( false !== $occurrence ) {
					$occurrence_date = ( new DateTime() )->setTimestamp( $occurrence );

					if ( $occurrence_date >= $window_start && $occurrence_date <= $window_end && $occurrence_date >= $now ) {
						list( $h, $m ) = array_map( 'intval', explode( ':', $start_time ) );
						$start = ( clone $occurrence_date )->setTime( $h, $m );

						$end = null;
						if ( $end_time ) {
							list( $eh, $em ) = array_map( 'intval', explode( ':', $end_time ) );
							$end = ( clone $occurrence_date )->setTime( $eh, $em );
						}

						$sessions[] = array(
							'start' => $start,
							'end'   => $end,
						);
					}
				}

				$cursor->modify( '+1 month' );
			}

			return $sessions;
		}

		return array();
	}
}
