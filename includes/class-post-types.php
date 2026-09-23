<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the 'event' and 'workshop' custom post types.
 *
 * If a site already has these post types registered elsewhere (a theme,
 * or a previous build), set:
 *   add_filter( 'lc_event_calendar_skip_post_type_registration', '__return_true' );
 * before 'init' to stop this plugin registering its own copies.
 */
class LC_Event_Calendar_Post_Types {

	public static function init() {
		if ( apply_filters( 'lc_event_calendar_skip_post_type_registration', false ) ) {
			return;
		}

		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		if ( ! post_type_exists( 'event' ) ) {
			register_post_type(
				'event',
				array(
					'labels'       => array(
						'name'          => __( 'Events', 'lc-event-calendar' ),
						'singular_name' => __( 'Event', 'lc-event-calendar' ),
						'add_new_item'  => __( 'Add New Event', 'lc-event-calendar' ),
						'edit_item'     => __( 'Edit Event', 'lc-event-calendar' ),
						'all_items'     => __( 'Events', 'lc-event-calendar' ),
						'search_items'  => __( 'Search Events', 'lc-event-calendar' ),
						'not_found'     => __( 'No events found', 'lc-event-calendar' ),
					),
					'public'       => true,
					'has_archive'  => true,
					'show_in_rest' => true,
					'menu_icon'    => 'dashicons-calendar-alt',
					'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
					'rewrite'      => array( 'slug' => 'events' ),
				)
			);
		}

		if ( ! post_type_exists( 'workshop' ) ) {
			register_post_type(
				'workshop',
				array(
					'labels'       => array(
						'name'          => __( 'Workshops', 'lc-event-calendar' ),
						'singular_name' => __( 'Workshop', 'lc-event-calendar' ),
						'add_new_item'  => __( 'Add New Workshop', 'lc-event-calendar' ),
						'edit_item'     => __( 'Edit Workshop', 'lc-event-calendar' ),
						'all_items'     => __( 'Workshops', 'lc-event-calendar' ),
						'search_items'  => __( 'Search Workshops', 'lc-event-calendar' ),
						'not_found'     => __( 'No workshops found', 'lc-event-calendar' ),
					),
					'public'       => true,
					'has_archive'  => true,
					'show_in_rest' => true,
					'menu_icon'    => 'dashicons-groups',
					'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
					'rewrite'      => array( 'slug' => 'workshops' ),
				)
			);
		}
	}
}
