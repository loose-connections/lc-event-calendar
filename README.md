# LC Event Calendar

A combined "What's On" calendar for Events and Workshops, built by Loose Connections as a reusable, installable WordPress plugin (not a set of code snippets). Originally developed for a client site as loose PHP snippets; rebuilt here as a proper plugin so it can be installed on any site.

## What this plugin does

A single "What's On" page shows a calendar (month grid + list view toggle) combining two custom post types, `event` and `workshop`. Clicking a date shows a hover tooltip with the title, a short description and a featured image; clicking navigates straight to that post. Each individual event/workshop post can also display its own dates directly on its own page (next date, full list of upcoming dates, or a plain-text recurrence rule), independently of the shared calendar.

Workshops support three different ways of being scheduled, so staff never have to manually re-date or republish a recurring workshop:

- **Specific dates** - one or more individually chosen date/time pairs (e.g. a term's worth of sessions).
- **Repeats weekly** - runs on one or more days of the week, indefinitely or until a set end date.
- **Repeats monthly** - runs on a pattern like "the first Saturday of every month".

Events only ever have a single start/end date pair.

## Requirements

- WordPress 6.0+, PHP 7.4+.
- **ACF Pro** (Advanced Custom Fields Pro) active - required for the Repeater field used by "Specific dates" workshops. The plugin shows an admin notice if this is missing.
- Elementor, or any page builder/theme, for placing the shortcodes. Nothing here depends on Elementor specifically.

## What's different from a snippets-based build

This plugin is fully self-contained:

- The `event` and `workshop` post types are registered by the plugin (`includes/class-post-types.php`). If a site already has these post types registered elsewhere, add `add_filter( 'lc_event_calendar_skip_post_type_registration', '__return_true' );` before `init` to stop the plugin registering its own.
- Every ACF field is registered in PHP (`includes/class-acf-fields.php`) via `acf_add_local_field_group()`. There is no JSON file to import through Custom Fields -> Tools; the fields exist as soon as the plugin is active.
- All field names are prefixed `lc_` to avoid clashing with anything else on a site.
- Colours are filterable, not hardcoded, so the plugin can be re-themed per site (see Theming below) rather than carrying one client's brand colours.
- CSS and JS live in real asset files (`assets/css/`, `assets/js/`) rather than being built as PHP strings, which also removes the "PHP interpolating JS as a variable" failure mode entirely (see Known gotchas below).

## Installation

1. Make sure ACF Pro is installed and active.
2. Install the plugin (upload the zip via Plugins -> Add New -> Upload Plugin, or unzip into `/wp-content/plugins/`).
3. Activate **LC Event Calendar**. This registers the Event and Workshop post types and every ACF field automatically - nothing to import.
4. Test the REST endpoint by visiting `/wp-json/lc/v1/events` directly in a browser; it should return a JSON array (empty until you add some events/workshops).
5. Add a page for your calendar and place `[lc_calendar]` on it.
6. On the single Event/Workshop template, add whichever of `[lc_next_date]`, `[lc_schedule_rule]`, `[lc_upcoming_dates]` or `[lc_upcoming_events]` you want, in whatever layout suits the site.
7. Add events and workshops. Set each event's Start Date/End Date. Set each workshop's Schedule Type and fill in the fields that appear (see the team guide below).
8. Make sure every event/workshop has a Featured Image set, since it's used in the calendar hover tooltip.
9. Test one of each schedule type (a specific-dates workshop, a weekly one, a monthly one if used, and a plain event) on both the calendar and the single-post shortcodes.

## Auto-updates

This plugin isn't on WordPress.org, so without any extra setup, updating it on a live site means deactivating, deleting and re-uploading a new zip every time, and losing update notices entirely. `includes/class-updater.php` fixes that using WordPress's own "Update URI" plugin header (built into core since WP 5.8), pointed at a GitHub repo's Releases. No bundled third-party library, and nothing calls out to anywhere except GitHub's API.

Once it's set up, a site with this plugin installed shows a normal "Update available" notice on the Plugins page, with a one-click update, exactly like a WordPress.org plugin.

**One-time setup:**

1. Create a GitHub repository for this plugin (public is simplest; see "Private repos" below if it needs to stay private).
2. Push this plugin's code to it (`git init`, `git add .`, `git commit`, add the GitHub remote, `git push`).
3. Open `includes/class-updater.php` and change the default in `repo_slug()` from `'your-org/lc-event-calendar'` to your real `owner/repo` (e.g. `'loose-connections/lc-event-calendar'`). Commit and push that change too.
4. Install this build on each client site once, the normal way (upload the zip, activate). From here on, updates are automatic.

**Shipping a new version, from then on:**

1. Bump the `Version` header in `lc-event-calendar.php` (and `LC_EVENT_CALENDAR_VERSION`), commit, push.
2. Build the zip the same way as always (zip the `lc-event-calendar` folder).
3. On GitHub, create a **Release** with a tag matching the version (`1.0.2`, or `v1.0.2` - both work), and **attach the built zip to the release as an asset**. This last step matters: the updater specifically looks for an attached `.zip` file, because its internal folder is already named `lc-event-calendar` and installs cleanly. A release with no attached zip is ignored (sites simply won't see an update for it), rather than risking a broken auto-generated archive.
4. Sites pick up the new version within the cache window (up to 12 hours), or immediately if someone clicks "Check again" on the Plugins page or visits Dashboard -> Updates.

**Private repos:** if the repo can't be public, generate a fine-grained GitHub personal access token with read-only access to that repo's contents, and set it per site via the `lc_event_calendar_github_token` filter (in a small site-specific snippet or mu-plugin, never committed into this plugin's own repo):

```php
add_filter( 'lc_event_calendar_github_token', function () {
    return 'github_pat_xxxxxxxxxxxx';
} );
```

## Theming / colours

Colours default to a neutral palette and are filterable so each site can re-theme the calendar without editing plugin files. Add this to the theme's `functions.php`, or a snippet:

```php
add_filter( 'lc_event_calendar_colors', function ( $colors ) {
    return array(
        'event'      => '#68003B', // Event colour
        'workshop'   => '#E35000', // Workshop colour, today highlight
        'text'       => '#161616', // Body/heading text, weekday header background
        'light_bg'   => '#F1F1F1', // Card/list-item backgrounds
        'border'     => '#ECECEC',
        'hover'      => '#840157', // Button hover state
        'today_tint' => 'rgba(227, 80, 0, 0.08)',
    );
} );
```

Any key you leave out keeps its default.

## Shortcodes reference

| Shortcode | Where to use | What it shows |
|---|---|---|
| `[lc_calendar]` | Once, on the main What's On page | Full FullCalendar month grid + list view toggle |
| `[lc_schedule_rule]` | On a single event/workshop page | Plain-text recurrence pattern (e.g. "Every Tuesday" / "2:00PM - 4:00PM" / date range). Only outputs anything for Repeats weekly / Repeats monthly workshops |
| `[lc_next_date]` | On a single event/workshop page | Just the single next upcoming date, plain text (no box), same styling as `lc_schedule_rule` |
| `[lc_upcoming_dates limit="6"]` | On a single event/workshop page | Full boxed list of every upcoming date, one by one. Works for events and any workshop schedule type |
| `[lc_upcoming_events limit="6"]` | On a single event/workshop page | Functionally identical to `lc_upcoming_dates`, just a separate tag name so it can be used independently |

All five accept an optional `post_id` attribute; without it they default to whichever post the shortcode sits on (works correctly inside a shared single template applied across every event/workshop).

A typical single-post layout: `[lc_next_date]` near the top as a highlight, `[lc_schedule_rule]` beneath it if it's a recurring workshop, then `[lc_upcoming_dates]` or `[lc_upcoming_events limit="10"]` further down for the full list.

## Day-of-week numbering convention

This build deliberately uses **Monday = 1 ... Sunday = 7** everywhere (matching PHP's native `DateTime::format('N')`), not the ACF/JavaScript default of Sunday = 0 ... Saturday = 6. This applies to both the "Days of the Week" checkbox and "Which Day" select on the Workshop Schedule fields. The only place a conversion to the JS/FullCalendar Sunday = 0 convention happens is inside the REST endpoint when building the `daysOfWeek` array for FullCalendar (via `% 7`) - don't change this convention without also updating that line.

The Schedule Type field stores plain lowercase text, never numbers: exactly one of `dates`, `recurring_weekly`, or `recurring_monthly`.

## Team quick-reference (for whoever adds workshop dates day to day)

A plain-English guide for non-technical staff adding/updating workshops, no code involved.

**Which Schedule Type to pick:**
- **Specific dates** - for hand-picked individual dates (a term's worth of sessions, a one-off run, anything irregular). Safe default if unsure.
- **Repeats weekly** - runs the same day(s) every week, indefinitely or until a known end date.
- **Repeats monthly** - runs on a pattern like "the first Saturday of every month".

**Specific dates:** Set Schedule Type, then under Session Dates click "Add Date" and fill in Start/End for each session. Add more rows any time, including for future terms, on the same post - no need to create a new post.

**Repeats weekly:** Set Schedule Type, tick every day it runs on, set Start Time/End Time, set Recurring From, leave Recurring Until blank unless there's a known end date. It then keeps appearing automatically, nothing further to do.

**Repeats monthly:** Same as weekly, but set Which Week (First/Second/Third/Fourth/Last) and Which Day instead of ticking days.

**General notes:** Changing Schedule Type just switches which fields are shown; it doesn't delete data in the other sections, so it's safe to try a different option and switch back. If a workshop stops running, unpublish/trash the post as normal, no other cleanup needed. This only applies to Workshops - Events keep using the simple single start/end date fields since they're normally one-off.

## Known gotchas (do not reintroduce)

- **Repeater raw-vs-formatted bug:** `get_field('lc_workshop_sessions', $post_id, false)` (the "raw" third argument) returns each repeater row keyed by internal field key instead of field name, which silently breaks any code expecting name-based keys. Always fetch `lc_workshop_sessions` WITHOUT the `false` argument (default, formatted mode). ACF's `date_time_picker` return format is already `Y-m-d H:i:s`, so formatted mode gives the same precision as raw would, with correct array keys.
- **JS-in-PHP-string interpolation:** the original snippets-based build kept the calendar's JavaScript inside a PHP double-quoted string via `wp_add_inline_script()`, where a literal `$` followed by `{` or a variable-looking name would silently break the snippet through PHP variable interpolation. This plugin avoids that failure mode entirely by keeping the JS as a real static file (`assets/js/calendar.js`) with no PHP inside it at all; per-site values (the REST URL, colours) are passed in via `wp_localize_script()` instead. If this file is ever edited, there's simply no PHP-interpolation risk to worry about - but don't reintroduce inline `wp_add_inline_script()` PHP-string JS without the same caution.
- **FullCalendar v6 button spacing:** use `margin-inline-start`, not `margin-left`, when spacing toolbar buttons - FullCalendar v6 uses the logical CSS property for RTL support.
- **Toggle button "jump" on click:** always give every button state (active and inactive) the same border width (`1px solid transparent` as baseline) so nothing shifts by a couple of pixels on click.
- **Tooltip image not full width:** WordPress's theme-level `img { max-width:100%; height:auto; }` reset overrides custom tooltip image sizing unless `!important` is added to `width`, `max-width` and `height` on `.lc-event-tooltip img`.

## File structure

```
lc-event-calendar/
├── lc-event-calendar.php          Main plugin file (header, bootstrap, activation hooks)
├── includes/
│   ├── class-post-types.php       Registers 'event' and 'workshop' post types
│   ├── class-acf-fields.php       Registers every ACF field group in PHP
│   ├── class-rest-api.php         GET /wp-json/lc/v1/events
│   ├── class-calendar.php         FullCalendar enqueue, colours, [lc_calendar]
│   ├── class-shortcodes.php       The four per-post shortcodes
│   └── class-updater.php          Self-hosted auto-updates via GitHub Releases
├── assets/
│   ├── css/
│   │   ├── calendar.css           Calendar grid/list styling + tooltip
│   │   └── shortcodes.css         Per-post shortcode styling
│   └── js/
│       └── calendar.js            FullCalendar init, fetch, tooltip, click-through
├── readme.txt                     Standard WordPress plugin readme
└── README.md                      This file
```

## Brand colours used by default

| Name | Hex | Used for |
|---|---|---|
| Event colour | `#2563EB` | Event colour, button theme |
| Workshop colour | `#F97316` | Workshop colour, today highlight, active toggle |
| Text | `#111827` | Body/heading text, weekday header background |
| Light | `#F3F4F6` | Card/list-item backgrounds |
| Hover | `#1D4ED8` | Button hover state |

Override any of these per site with the `lc_event_calendar_colors` filter (see Theming above).
