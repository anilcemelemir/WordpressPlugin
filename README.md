# Event Manager — WordPress Plugin

A comprehensive WordPress plugin for managing events, built inside a Docker-based development environment. Developed as a technical audition task demonstrating WordPress best practices including custom post types, taxonomies, meta boxes, REST API integration, front-end templates, RSVP functionality, shortcodes, filtering, security hardening, i18n support, email notifications, transient caching, PSR-12 code quality, and unit testing.

---

## Table of Contents

1. [Requirements](#requirements)
2. [Installation & Setup](#installation--setup)
3. [Docker Environment](#docker-environment)
4. [Plugin Features](#plugin-features)
5. [Usage Guide](#usage-guide)
6. [REST API](#rest-api)
7. [Shortcodes](#shortcodes)
8. [Templates & Theme Override](#templates--theme-override)
9. [RSVP System](#rsvp-system)
10. [Email Notifications](#email-notifications)
11. [Filtering & Search](#filtering--search)
12. [Performance & Caching](#performance--caching)
13. [Security](#security)
14. [Code Quality](#code-quality)
15. [Testing](#testing)
16. [Sample Data](#sample-data)
17. [Plugin Structure](#plugin-structure)
18. [Evaluation Criteria Summary](#evaluation-criteria-summary)

---

## Requirements

| Requirement   | Version |
|---------------|---------|
| PHP           | 7.4+    |
| WordPress     | 5.0+    |
| MySQL         | 8.0+    |
| Docker        | Latest  |
| Docker Compose| v2+     |

---

## Installation & Setup

### 1. Clone the Repository

```bash
git clone <repo-url> WordpressPlugin
cd WordpressPlugin
```

### 2. Start the Docker Environment

```bash
docker-compose up -d
```

### 3. Install WordPress (First Run)

```bash
docker-compose run --rm wpcli core install \
  --url="http://localhost:8081" \
  --title="Event Manager Dev" \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=admin@example.com
```

### 4. Activate the Plugin

```bash
docker-compose run --rm wpcli plugin activate event-manager
```

### 5. Set Permalink Structure (Required for REST API)

```bash
docker-compose run --rm wpcli rewrite structure '/%postname%/'
docker-compose run --rm wpcli rewrite flush
```

### 6. Access WordPress

| URL | Purpose |
|-----|---------|
| http://localhost:8081 | Front-end |
| http://localhost:8081/wp-admin | Admin dashboard |
| http://localhost:8081/wp-json/wp/v2/event | REST API endpoint |

Default credentials: `admin` / `admin`

### Stop / Tear Down

```bash
docker-compose down          # Stop containers
docker-compose down -v       # Stop and remove all data
```

---

## Docker Environment

### Services

| Service   | Container  | Port | Description            |
|-----------|------------|------|------------------------|
| wordpress | wp_site    | 8081 | WordPress application  |
| db        | wp_mysql   | 3306 | MySQL 8.0 database     |
| wpcli     | wp_cli     | —    | WP-CLI command runner  |

### WP-CLI Usage

```bash
# General pattern
docker-compose run --rm wpcli <command>

# Examples
docker-compose run --rm wpcli --info
docker-compose run --rm wpcli plugin list
docker-compose run --rm wpcli post list --post_type=event
```

---

## Plugin Features

### Custom Post Type: `event`

- Public, has archive at `/event/`
- REST API enabled (`show_in_rest: true`, `rest_base: 'event'`)
- Supports: title, editor, author, thumbnail, excerpt, comments
- Gutenberg / Block Editor compatible
- Custom menu icon: `dashicons-calendar-alt`

### Custom Taxonomy: `event_type`

- Hierarchical (category-style)
- REST API enabled
- Linked exclusively to the `event` post type

### Meta Fields

| Meta Key                  | Type   | Description                   |
|---------------------------|--------|-------------------------------|
| `_event_manager_date`     | string | Event date in `YYYY-MM-DD` format |
| `_event_manager_location` | string | Event location (free text)    |
| `_event_rsvp_list`        | array  | Array of user IDs who RSVP'd |

### Admin Enhancements

- **Meta Box**: "Event Details" with Date (date picker) and Location fields
- **Custom Columns**: Event Date and Location columns in the admin list table
- **Sortable Columns**: Click column headers to sort by date or location

### i18n / Internationalization

- Text domain: `event-manager`
- All user-facing strings wrapped in `__()`, `_e()`, `_x()`, `esc_html__()`, etc.
- POT file included at `languages/event-manager.pot`

---

## Usage Guide

### Creating an Event

1. Navigate to **Events → Add New** in the admin dashboard
2. Enter a title and content
3. In the **Event Details** meta box (below the editor):
   - Set the **Event Date** (date picker)
   - Enter the **Event Location**
4. Assign one or more **Event Types** from the sidebar taxonomy box
5. Optionally set a **Featured Image**
6. Publish the event

### Viewing Events

- **Single Event**: `http://localhost:8081/event/<slug>/`
- **Archive Page**: `http://localhost:8081/event/`
- **Shortcode**: Use `[event_list]` on any page/post

---

## REST API

The plugin exposes events and their meta fields through the WordPress REST API.

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET    | `/wp-json/wp/v2/event` | List all events |
| GET    | `/wp-json/wp/v2/event/<id>` | Get single event |
| POST   | `/wp-json/wp/v2/event` | Create event (auth required) |
| PUT    | `/wp-json/wp/v2/event/<id>` | Update event (auth required) |
| DELETE | `/wp-json/wp/v2/event/<id>` | Delete event (auth required) |

### Meta Fields in REST Response

```json
{
  "id": 42,
  "title": { "rendered": "Summer Music Festival" },
  "meta": {
    "_event_manager_date": "2025-08-15",
    "_event_manager_location": "Central Park, NYC"
  }
}
```

### Example Requests

```bash
# List events
curl http://localhost:8081/wp-json/wp/v2/event

# List events with specific type (taxonomy term ID)
curl http://localhost:8081/wp-json/wp/v2/event?event_type=3

# Create event (requires authentication)
curl -X POST http://localhost:8081/wp-json/wp/v2/event \
  -H "Content-Type: application/json" \
  -u admin:admin \
  -d '{
    "title": "New Event",
    "status": "publish",
    "meta": {
      "_event_manager_date": "2025-09-20",
      "_event_manager_location": "Convention Center"
    }
  }'
```

---

## Shortcodes

### `[event_list]`

Displays a responsive grid of event cards with optional filtering.

#### Attributes

| Attribute     | Default | Description |
|---------------|---------|-------------|
| `limit`       | `12`    | Number of events to display |
| `type`        | `""`    | Filter by Event Type slug |
| `date_from`   | `""`    | Show events from this date (YYYY-MM-DD) |
| `date_to`     | `""`    | Show events until this date (YYYY-MM-DD) |
| `search`      | `""`    | Search keyword |
| `columns`     | `3`     | Number of grid columns (1–4) |
| `show_filters`| `true`  | Show filter form above the grid |

#### Examples

```
[event_list]
[event_list limit="6" columns="2"]
[event_list type="conference" date_from="2025-01-01"]
[event_list show_filters="false" limit="3"]
```

---

## Templates & Theme Override

The plugin includes its own templates that can be overridden by the active theme.

### Template Hierarchy

| Template | Plugin Path | Theme Override Path |
|----------|-------------|---------------------|
| Single Event | `templates/single-event.php` | `yourtheme/event-manager/single-event.php` |
| Event Archive | `templates/archive-event.php` | `yourtheme/event-manager/archive-event.php` |

To customize a template, copy it from the plugin's `templates/` directory into your theme's `event-manager/` directory and modify as needed.

---

## RSVP System

Logged-in users can RSVP to events via an AJAX-powered interface on the single event page.

### How It Works

1. A logged-in user views a single event page
2. Clicks **"Confirm RSVP"** to register attendance
3. The button changes to **"Cancel RSVP"** with a count
4. User IDs are stored in the `_event_rsvp_list` post meta array

### Technical Details

- **AJAX Actions**: `event_manager_rsvp` (authenticated), returns JSON
- **Security**: `check_ajax_referer()` validates the nonce `event_manager_rsvp_nonce`
- **Duplicate Prevention**: Users cannot RSVP more than once
- **Guest Handling**: Non-logged-in users see a "Please log in to RSVP" message

---

## Email Notifications

The plugin sends automatic email notifications using `wp_mail()` when events are published or updated.

### New Event Published

- **Trigger**: Event status transitions to `publish` for the first time.
- **Recipients**: All registered users.
- **Content**: HTML email with event title, date, location, excerpt, and a "View Event" CTA button.

### Event Updated

- **Trigger**: An already-published event is saved again.
- **Recipients**: Only users who have RSVP'd to that specific event.
- **Content**: HTML email notifying them of the update with a link to the event.

### Technical Details

- **Hook**: `transition_post_status` detects publish and update transitions.
- **Privacy**: Uses BCC for bulk sending — recipient emails are hidden from each other.
- **Batching**: Sends in batches of 50 (configurable via `event_manager_email_batch_size` filter) to avoid server limits.
- **Extensible**: Filter hooks for recipients (`event_manager_{type}_recipients`), email HTML (`event_manager_email_html`), and headers (`event_manager_email_headers`).
- **Autosave Safe**: Notifications are skipped during WordPress autosave.

---

## Filtering & Search

### Archive Page Filters

The event archive page (`/event/`) includes a filter bar with:

- **Search**: Keyword search across event titles/content
- **Event Type**: Dropdown to filter by taxonomy term
- **Date From / Date To**: Date range filter
- **Apply Filters** button

Filters modify the main WordPress query via `pre_get_posts` and use `meta_query` for date ranges.

### Shortcode Filters

The `[event_list]` shortcode includes the same filter UI when `show_filters="true"` (default).

---

## Performance & Caching

The plugin uses the WordPress Transients API to cache heavy queries and reduce database load.

### What Is Cached

| Cache Target | Key Prefix | TTL | Description |
|-------------|-----------|-----|-------------|
| Shortcode output | `em_sc_` | 1 hour | Full HTML output of `[event_list]` keyed by query args hash |
| Event type terms | `em_terms_` | 12 hours | `get_terms()` results for dropdown rendering |

### Cache Invalidation

Caches are automatically flushed when:
- An event is created, updated, trashed, or permanently deleted (`save_post_event`, `trashed_post`, `before_delete_post`).
- An `event_type` taxonomy term is created, edited, or deleted.
- WordPress autosaves and revisions are excluded from triggering cache flushes.

### Configuration

Cache TTL constants can be overridden in `wp-config.php`:

```php
define( 'EVENT_MANAGER_CACHE_TTL', 2 * HOUR_IN_SECONDS );       // Query cache (default: 1 hour)
define( 'EVENT_MANAGER_TERMS_CACHE_TTL', 24 * HOUR_IN_SECONDS ); // Terms cache (default: 12 hours)
```

### Developer API

```php
// Generate a cache key
$key = event_manager_cache_key( 'my_prefix', $args );

// Get/set cached values
$value = event_manager_cache_get( $key );
event_manager_cache_set( $key, $value, HOUR_IN_SECONDS );

// Flush all plugin caches
event_manager_flush_cache();

// Get cached event types (auto-caching wrapper)
$terms = event_manager_get_cached_event_types();
```

---

## Security

The plugin implements multiple layers of security:

| Layer | Implementation |
|-------|---------------|
| **Nonce Verification** | `wp_nonce_field()` / `wp_verify_nonce()` on meta box saves |
| **AJAX Nonce** | `check_ajax_referer()` on RSVP requests |
| **Capability Checks** | `current_user_can('edit_post')` before saving meta |
| **Input Sanitization** | `sanitize_text_field()`, `wp_unslash()` on all inputs |
| **Date Validation** | Regex pattern `YYYY-MM-DD` + PHP `checkdate()` for impossible dates |
| **Output Escaping** | `esc_html()`, `esc_attr()`, `esc_url()` on all output |
| **Autosave Guard** | Skips meta save during `DOING_AUTOSAVE` |
| **Post Type Guard** | Verifies post type before saving meta |
| **REST Auth Callback** | `edit_posts` capability required for REST meta writes |
| **Clean Uninstall** | `uninstall.php` removes all plugin data (options, meta, posts, caches) |

---

## Code Quality

All code follows **PSR-12** coding standards and is thoroughly documented with **PHPDoc**.

### Documentation Standards

- **File-level docblocks**: Every PHP file has `@package`, `@subpackage`, `@since`, `@author` tags.
- **Function-level docblocks**: All functions include `@since`, `@param`, `@return`, and where applicable `@global`, `@see`, and `@throws` tags.
- **Inline comments**: Complex logic blocks are annotated with explanatory comments.
- **Changelog tags**: `@since 1.0.0` for original functions, `@since 1.1.0` for new additions.

### Code Organisation

- Consistent file naming with clear responsibility separation.
- Each include file is self-contained and self-hooking.
- Filter and action hooks are documented with `@since` and parameter descriptions.
- Return type declarations on class methods (`:void`, `:string`, etc.).

---

## Testing

### PHPUnit Test Suite

The plugin includes a comprehensive test suite with **53 tests** across 3 test files:

| Test File | Tests | Coverage Area |
|-----------|-------|---------------|
| `test-cpt-registration.php` | 15 | CPT & taxonomy registration, labels, REST support |
| `test-meta-data.php` | 18 | Meta save, validation, security, sanitization |
| `test-rsvp.php` | 20 | RSVP confirm/cancel, duplicates, multi-user, stress, data integrity |

### Running Tests

```bash
# 1. Set up WordPress test library (first time only)
docker exec wp_site bash /var/www/html/wp-content/plugins/event-manager/scripts/setup-wp-tests.sh

# 2. Run all tests inside Docker
docker exec wp_site bash -c "cd /var/www/html/wp-content/plugins/event-manager && WP_TESTS_DIR=/tmp/wordpress-tests-lib ./vendor/bin/phpunit --verbose"

# 3. Run specific test file
docker exec wp_site bash -c "cd /var/www/html/wp-content/plugins/event-manager && WP_TESTS_DIR=/tmp/wordpress-tests-lib ./vendor/bin/phpunit tests/test-cpt-registration.php"

# 4. Run specific test method
docker exec wp_site bash -c "cd /var/www/html/wp-content/plugins/event-manager && WP_TESTS_DIR=/tmp/wordpress-tests-lib ./vendor/bin/phpunit --filter test_event_post_type_exists"
```

### Test Highlights

- **Date Validation**: Tests impossible dates (Feb 30), leap years, future dates, edge formats
- **Security Tests**: Nonce bypass, wrong post type, unauthorized user (subscriber role)
- **RSVP Stress Test**: 50 concurrent users confirming/canceling
- **Input Sanitization**: HTML injection, XSS attempt detection

---

## Sample Data

Generate sample events using the included WP-CLI script:

```bash
docker-compose run --rm wpcli eval-file /var/www/html/wp-content/plugins/event-manager/scripts/generate-sample-data.php
```

This creates:
- 5 Event Type terms (Conference, Workshop, Webinar, Meetup, Hackathon)
- 10 sample events with dates, locations, and assigned types
- İf you can't create the sample datas, i also included .xml file that i exported from my local environment.

---

## Plugin Structure

```
plugins/event-manager/
├── event-manager.php              # Main plugin file & bootstrapper
├── uninstall.php                  # Clean uninstall handler
├── composer.json                  # Composer dependencies (PHPUnit)
├── readme.txt                     # WordPress.org readme
├── phpunit.xml                    # PHPUnit configuration
│
├── includes/
│   ├── class-event-manager.php    # Core plugin class
│   ├── post-types.php             # Event CPT registration
│   ├── taxonomies.php             # Event Type taxonomy
│   ├── meta-boxes.php             # Meta box UI + save logic
│   ├── admin-columns.php          # Custom sortable admin columns
│   ├── rest-api.php               # REST API meta registration
│   ├── template-loader.php        # Template loader with theme override
│   ├── enqueue.php                # Front-end CSS/JS enqueuing
│   ├── rsvp.php                   # AJAX RSVP handler
│   ├── shortcode.php              # [event_list] shortcode (cached)
│   ├── filtering.php              # Archive query filtering
│   ├── cache.php                  # Transient cache management
│   └── notifications.php          # Email notification system (wp_mail)
│
├── templates/
│   ├── single-event.php           # Single event template
│   └── archive-event.php          # Archive template with filters
│
├── assets/
│   ├── css/
│   │   └── style.css              # Responsive front-end styles
│   └── js/
│       └── rsvp.js                # jQuery AJAX RSVP handler
│
├── languages/
│   └── event-manager.pot          # Translation template
│
├── scripts/
│   └── generate-sample-data.php   # WP-CLI sample data generator
│
└── tests/
    ├── bootstrap.php              # Test bootstrap
    ├── test-cpt-registration.php  # CPT & taxonomy tests (15)
    ├── test-meta-data.php         # Meta-data tests (18)
    └── test-rsvp.php              # RSVP tests (20)
```

---

## Evaluation Criteria Summary

| Criteria | Implementation |
|----------|---------------|
| **Custom Post Type** | `event` CPT with full REST API support, archive, Gutenberg compatible |
| **Custom Taxonomy** | `event_type` — hierarchical, REST-enabled, linked to events |
| **Meta Boxes** | Event Date (with validation) and Location fields with block editor compatibility |
| **Admin Columns** | Sortable Date and Location columns in the admin list table |
| **REST API** | Full CRUD via `/wp-json/wp/v2/event` with meta fields exposed |
| **Templates** | Custom single + archive templates with theme override support |
| **RSVP** | AJAX-based confirm/cancel with nonce protection and duplicate prevention |
| **Shortcode** | `[event_list]` with 7 attributes, filter UI, responsive grid, transient-cached output |
| **Filtering** | Search, taxonomy, date range on archive and shortcode |
| **Email Notifications** | `wp_mail()` HTML emails on publish (all users) and update (RSVP'd users), BCC privacy, batch sending |
| **Performance** | Transient caching for shortcode queries and taxonomy terms, automatic cache invalidation |
| **Security** | Nonces, capability checks, sanitization, escaping, autosave guards |
| **Code Quality** | PSR-12 standards, comprehensive PHPDoc on every file/function, `@since`/`@param`/`@return` tags |
| **i18n** | Full internationalization with POT file |
| **Testing** | 53 PHPUnit tests covering CPT, meta, RSVP, security |
| **Docker** | Complete dev environment with WordPress, MySQL, WP-CLI |
| **Documentation** | This comprehensive README + inline code comments |

## Note

The project and code documentation were done using the Gemini 3 Flash model, while unit tests were performed using Claude 3.5 Sonnet due to my lack of knowledge in the area.
