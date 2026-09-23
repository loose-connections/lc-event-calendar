=== LC Event Calendar ===
Contributors: looseconnections
Tags: events, workshops, calendar, fullcalendar, acf
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A combined Events and Workshops "What's On" calendar, with three workshop scheduling modes (specific dates, weekly recurrence, monthly recurrence), built by Loose Connections.

== Description ==

LC Event Calendar shows a single calendar (month grid + list view toggle) combining two custom post types, Event and Workshop. Clicking a date shows a hover tooltip with the title, a short description and a featured image, and clicking navigates straight to that post.

Each individual event or workshop can also display its own dates on its own page (next date, full list of upcoming dates, or a plain-text recurrence rule), independently of the shared calendar.

Workshops support three scheduling modes so staff never have to manually re-date or republish a recurring workshop:

* Specific dates - one or more individually chosen date/time pairs.
* Repeats weekly - runs on one or more days of the week, indefinitely or until a set end date.
* Repeats monthly - runs on a pattern like "the first Saturday of every month".

Events always use a single start/end date pair.

Requires Advanced Custom Fields PRO (for the Repeater field used by "Specific dates" workshops). All ACF fields are registered in PHP by the plugin itself; there is nothing to import manually.

See README.md in the plugin folder for the full shortcode reference and a plain-English guide for non-technical staff adding workshop dates.

== Installation ==

1. Make sure Advanced Custom Fields PRO is installed and active.
2. Upload the lc-event-calendar folder to /wp-content/plugins/, or upload the zip via Plugins -> Add New -> Upload Plugin.
3. Activate the plugin. This registers the Event and Workshop post types and all of the plugin's ACF fields automatically.
4. Add a page for your calendar and place the [lc_calendar] shortcode on it.
5. Add [lc_next_date], [lc_schedule_rule], [lc_upcoming_dates] and/or [lc_upcoming_events] to your single Event/Workshop template, in whichever layout you prefer.
6. Add events and workshops, setting each workshop's Schedule Type.

== Changelog ==

= 1.1.0 =
* Added self-hosted auto-updates via GitHub Releases (includes/class-updater.php), so sites can get "Update available" notices and one-click updates in wp-admin instead of manual zip re-uploads. See README.md "Auto-updates" for one-time setup.

= 1.0.1 =
* Calendar tooltip now shows the event/workshop date and time.
* Calendar tooltip description is clamped to 3 lines, however long the short description field is, so the tooltip stays compact.
* Fixed titles containing an apostrophe (or other punctuation WordPress converts to an HTML entity) showing as literal text like "&#8217;" in the tooltip instead of a proper apostrophe.

= 1.0.0 =
* Initial release.
