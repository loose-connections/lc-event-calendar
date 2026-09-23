<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers every ACF field this plugin needs, in PHP, via
 * acf_add_local_field_group(). Nothing needs to be manually imported
 * through Custom Fields -> Tools on a new site; these fields exist as
 * soon as the plugin is active and ACF Pro is running.
 *
 * Field naming convention: every field name is prefixed lc_ to avoid
 * clashing with anything else on the site. All date/time fields use
 * Monday = 1 ... Sunday = 7 (matching PHP's native DateTime::format('N')),
 * NOT the ACF/JavaScript default of Sunday = 0 ... Saturday = 6. The only
 * place that gets converted to FullCalendar's Sunday = 0 convention is in
 * the REST endpoint, via % 7.
 */
class LC_Event_Calendar_ACF_Fields {

	public static function init() {
		add_action( 'acf/init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		self::register_event_fields();
		self::register_workshop_short_description();
		self::register_workshop_schedule();
	}

	/**
	 * Base date/description fields for the 'event' post type. Events only
	 * ever have a single start/end date pair.
	 */
	private static function register_event_fields() {
		acf_add_local_field_group(
			array(
				'key'      => 'group_lc_event_details',
				'title'    => 'Event Details',
				'fields'   => array(
					array(
						'key'            => 'field_lc_event_start_date',
						'label'          => 'Start Date',
						'name'           => 'lc_event_start_date',
						'type'           => 'date_time_picker',
						'required'       => 1,
						'display_format' => 'j F y | g:ia',
						'return_format'  => 'Y-m-d H:i:s',
						'first_day'      => 1,
					),
					array(
						'key'            => 'field_lc_event_end_date',
						'label'          => 'End Date',
						'name'           => 'lc_event_end_date',
						'type'           => 'date_time_picker',
						'required'       => 0,
						'display_format' => 'j F y | g:ia',
						'return_format'  => 'Y-m-d H:i:s',
						'first_day'      => 1,
					),
					array(
						'key'          => 'field_lc_event_short_description',
						'label'        => 'Short Description',
						'name'         => 'lc_event_short_description',
						'type'         => 'textarea',
						'instructions' => 'Used in the calendar hover tooltip. Keep this to around 140 characters.',
						'rows'         => 3,
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'event',
						),
					),
				),
			)
		);
	}

	/**
	 * Workshops get their short description here; their date/time fields
	 * live in the Workshop Schedule group below.
	 */
	private static function register_workshop_short_description() {
		acf_add_local_field_group(
			array(
				'key'      => 'group_lc_workshop_details',
				'title'    => 'Workshop Details',
				'fields'   => array(
					array(
						'key'          => 'field_lc_workshop_short_description',
						'label'        => 'Short Description',
						'name'         => 'lc_workshop_short_description',
						'type'         => 'textarea',
						'instructions' => 'Used in the calendar hover tooltip. Keep this to around 140 characters.',
						'rows'         => 3,
					),
				),
				'menu_order' => 0,
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'workshop',
						),
					),
				),
			)
		);
	}

	/**
	 * The three scheduling modes for workshops: specific dates, weekly
	 * recurrence and monthly recurrence.
	 */
	private static function register_workshop_schedule() {
		acf_add_local_field_group(
			array(
				'key'      => 'group_lc_workshop_schedule',
				'title'    => 'Workshop Schedule',
				'fields'   => array(
					array(
						'key'               => 'field_lc_schedule_type',
						'label'             => 'Schedule Type',
						'name'              => 'lc_schedule_type',
						'type'              => 'select',
						'required'          => 1,
						'conditional_logic' => 0,
						'choices'           => array(
							'dates'             => 'Specific dates',
							'recurring_weekly'  => 'Repeats weekly',
							'recurring_monthly' => 'Repeats monthly',
						),
						'default_value'     => 'dates',
						'return_format'     => 'value',
						'multiple'          => 0,
						'allow_null'        => 0,
						'ui'                => 0,
					),
					array(
						'key'               => 'field_lc_workshop_sessions',
						'label'             => 'Session Dates',
						'name'              => 'lc_workshop_sessions',
						'type'              => 'repeater',
						'instructions'      => 'Add one row per date this workshop is running.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'dates',
								),
							),
						),
						'layout'            => 'table',
						'button_label'      => 'Add Date',
						'sub_fields'        => array(
							array(
								'key'            => 'field_lc_session_start',
								'label'          => 'Start',
								'name'           => 'lc_session_start',
								'type'           => 'date_time_picker',
								'required'       => 1,
								'display_format' => 'j F y | g:ia',
								'return_format'  => 'Y-m-d H:i:s',
								'first_day'      => 1,
							),
							array(
								'key'            => 'field_lc_session_end',
								'label'          => 'End',
								'name'           => 'lc_session_end',
								'type'           => 'date_time_picker',
								'required'       => 0,
								'display_format' => 'j F y | g:ia',
								'return_format'  => 'Y-m-d H:i:s',
								'first_day'      => 1,
							),
						),
					),
					array(
						'key'               => 'field_lc_recur_days',
						'label'             => 'Days of the Week',
						'name'              => 'lc_recur_days',
						'type'              => 'checkbox',
						'instructions'      => 'Select every day this workshop runs on.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_weekly',
								),
							),
						),
						'choices'           => array(
							'1' => 'Monday',
							'2' => 'Tuesday',
							'3' => 'Wednesday',
							'4' => 'Thursday',
							'5' => 'Friday',
							'6' => 'Saturday',
							'7' => 'Sunday',
						),
						'allow_custom'      => 0,
						'default_value'     => array(),
						'layout'            => 'vertical',
						'toggle'            => 0,
						'return_format'     => 'value',
						'save_custom'       => 0,
					),
					array(
						'key'               => 'field_lc_recur_month_week',
						'label'             => 'Which Week',
						'name'              => 'lc_recur_month_week',
						'type'              => 'select',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_monthly',
								),
							),
						),
						'choices'           => array(
							'1'  => 'First',
							'2'  => 'Second',
							'3'  => 'Third',
							'4'  => 'Fourth',
							'-1' => 'Last',
						),
						'return_format'     => 'value',
						'multiple'          => 0,
						'allow_null'        => 0,
						'ui'                => 0,
					),
					array(
						'key'               => 'field_lc_recur_month_day',
						'label'             => 'Which Day',
						'name'              => 'lc_recur_month_day',
						'type'              => 'select',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_monthly',
								),
							),
						),
						'choices'           => array(
							'1' => 'Monday',
							'2' => 'Tuesday',
							'3' => 'Wednesday',
							'4' => 'Thursday',
							'5' => 'Friday',
							'6' => 'Saturday',
							'7' => 'Sunday',
						),
						'return_format'     => 'value',
						'multiple'          => 0,
						'allow_null'        => 0,
						'ui'                => 0,
					),
					array(
						'key'               => 'field_lc_recur_start_time',
						'label'             => 'Start Time',
						'name'              => 'lc_recur_start_time',
						'type'              => 'time_picker',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_weekly',
								),
							),
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_monthly',
								),
							),
						),
						'display_format'    => 'g:i a',
						'return_format'     => 'H:i:s',
					),
					array(
						'key'               => 'field_lc_recur_end_time',
						'label'             => 'End Time',
						'name'              => 'lc_recur_end_time',
						'type'              => 'time_picker',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_weekly',
								),
							),
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_monthly',
								),
							),
						),
						'display_format'    => 'g:i a',
						'return_format'     => 'H:i:s',
					),
					array(
						'key'               => 'field_lc_recur_start_date',
						'label'             => 'Recurring From',
						'name'              => 'lc_recur_start_date',
						'type'              => 'date_picker',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_weekly',
								),
							),
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_monthly',
								),
							),
						),
						'display_format'    => 'd/m/Y',
						'return_format'     => 'Ymd',
						'first_day'         => 1,
					),
					array(
						'key'               => 'field_lc_recur_end_date',
						'label'             => 'Recurring Until (optional)',
						'name'              => 'lc_recur_end_date',
						'type'              => 'date_picker',
						'instructions'      => 'Leave blank if this repeats indefinitely.',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_weekly',
								),
							),
							array(
								array(
									'field'    => 'field_lc_schedule_type',
									'operator' => '==',
									'value'    => 'recurring_monthly',
								),
							),
						),
						'display_format'    => 'd/m/Y',
						'return_format'     => 'Ymd',
						'first_day'         => 1,
					),
				),
				'menu_order' => 1,
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'workshop',
						),
					),
				),
			)
		);
	}
}
