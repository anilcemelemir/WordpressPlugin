<?php
/**
 * Transient Cache Management for Event Manager.
 *
 * Provides a centralized caching layer using the WordPress Transients API
 * to optimize performance for event queries, shortcode output, and
 * taxonomy term lookups. Automatically invalidates cache when events
 * are created, updated, or deleted.
 *
 * @package    Event_Manager
 * @subpackage Event_Manager/Includes
 * @since      1.1.0
 * @author     Developer
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default cache TTL for query results (1 hour).
 *
 * @since 1.1.0
 * @var int
 */
if ( ! defined( 'EVENT_MANAGER_CACHE_TTL' ) ) {
	define( 'EVENT_MANAGER_CACHE_TTL', HOUR_IN_SECONDS );
}

/**
 * Default cache TTL for taxonomy terms (12 hours).
 *
 * @since 1.1.0
 * @var int
 */
if ( ! defined( 'EVENT_MANAGER_TERMS_CACHE_TTL' ) ) {
	define( 'EVENT_MANAGER_TERMS_CACHE_TTL', 12 * HOUR_IN_SECONDS );
}

/**
 * Generate a unique transient key for a set of query arguments.
 *
 * Creates an MD5 hash of the JSON-encoded arguments, prefixed with
 * 'em_' for easy identification and bulk deletion.
 *
 * @since  1.1.0
 * @param  string $prefix A short prefix to categorize the cache entry (e.g., 'sc', 'archive').
 * @param  array  $args   The query arguments or parameters to hash.
 * @return string The generated transient key (max 172 characters for WordPress compatibility).
 */
function event_manager_cache_key( $prefix, $args = array() ) {
	$hash = md5( wp_json_encode( $args ) );
	return 'em_' . sanitize_key( $prefix ) . '_' . $hash;
}

/**
 * Retrieve a cached value from the transient store.
 *
 * Wraps get_transient() with the plugin's key convention.
 *
 * @since  1.1.0
 * @param  string $key The full transient key.
 * @return mixed The cached value, or false if not found or expired.
 */
function event_manager_cache_get( $key ) {
	return get_transient( $key );
}

/**
 * Store a value in the transient cache.
 *
 * @since  1.1.0
 * @param  string $key        The full transient key.
 * @param  mixed  $value      The data to cache.
 * @param  int    $expiration Optional. Cache TTL in seconds. Default EVENT_MANAGER_CACHE_TTL.
 * @return bool True if the value was set, false otherwise.
 */
function event_manager_cache_set( $key, $value, $expiration = 0 ) {
	if ( 0 === $expiration ) {
		$expiration = EVENT_MANAGER_CACHE_TTL;
	}
	return set_transient( $key, $value, $expiration );
}

/**
 * Flush all Event Manager transient caches.
 *
 * Queries the database for all transients matching the 'em_' prefix,
 * then deletes each via delete_transient() to properly clear both
 * the database entries and WordPress's in-memory object cache.
 *
 * @since  1.1.0
 * @global wpdb $wpdb WordPress database abstraction object.
 * @return int The number of transients successfully deleted.
 */
function event_manager_flush_cache() {
	global $wpdb;

	// Retrieve all plugin transient names from the database.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$transients = $wpdb->get_col(
		"SELECT REPLACE(option_name, '_transient_', '')
		 FROM {$wpdb->options}
		 WHERE option_name LIKE '\_transient\_em\_%'
		   AND option_name NOT LIKE '\_transient\_timeout\_em\_%'"
	);

	$count = 0;
	foreach ( $transients as $transient ) {
		if ( delete_transient( $transient ) ) {
			++$count;
		}
	}

	/**
	 * Fires after the plugin's transient cache has been flushed.
	 *
	 * @since 1.1.0
	 * @param int $count Number of transients successfully deleted.
	 */
	do_action( 'event_manager_cache_flushed', $count );

	return $count;
}

/**
 * Invalidate event caches when an event is saved or updated.
 *
 * Hooks into 'save_post_event' to ensure stale data is never
 * served after content changes.
 *
 * @since  1.1.0
 * @param  int     $post_id The saved post ID.
 * @param  WP_Post $post    The saved post object.
 * @param  bool    $update  Whether this is an update (true) or new post (false).
 * @return void
 */
function event_manager_bust_cache_on_save( $post_id, $post, $update ) {
	// Skip autosaves and revisions.
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	event_manager_flush_cache();
}
add_action( 'save_post_event', 'event_manager_bust_cache_on_save', 10, 3 );

/**
 * Invalidate event caches when an event is trashed or deleted.
 *
 * @since  1.1.0
 * @param  int $post_id The post ID being deleted or trashed.
 * @return void
 */
function event_manager_bust_cache_on_delete( $post_id ) {
	if ( 'event' === get_post_type( $post_id ) ) {
		event_manager_flush_cache();
	}
}
add_action( 'before_delete_post', 'event_manager_bust_cache_on_delete' );
add_action( 'trashed_post', 'event_manager_bust_cache_on_delete' );

/**
 * Invalidate caches when event_type taxonomy terms change.
 *
 * Ensures cached term dropdowns and filtered queries are
 * refreshed when terms are created, edited, or deleted.
 *
 * @since  1.1.0
 * @param  int    $term_id  The term ID.
 * @param  int    $tt_id    The term taxonomy ID.
 * @param  string $taxonomy The taxonomy slug.
 * @return void
 */
function event_manager_bust_cache_on_term_change( $term_id, $tt_id, $taxonomy ) {
	if ( 'event_type' === $taxonomy ) {
		event_manager_flush_cache();
	}
}
add_action( 'created_term', 'event_manager_bust_cache_on_term_change', 10, 3 );
add_action( 'edited_term', 'event_manager_bust_cache_on_term_change', 10, 3 );
add_action( 'delete_term', 'event_manager_bust_cache_on_term_change', 10, 3 );

/**
 * Get cached event type terms for dropdown rendering.
 *
 * Caches the result of get_terms() for the 'event_type' taxonomy
 * to avoid repeated database queries on archive and shortcode pages.
 *
 * @since  1.1.0
 * @param  array $args Optional. Arguments to pass to get_terms(). Default empty array.
 * @return array|WP_Error Array of WP_Term objects, or WP_Error on failure.
 */
function event_manager_get_cached_event_types( $args = array() ) {
	$defaults = array(
		'taxonomy'   => 'event_type',
		'hide_empty' => false,
	);

	$args = wp_parse_args( $args, $defaults );
	$key  = event_manager_cache_key( 'terms', $args );

	$cached = event_manager_cache_get( $key );
	if ( false !== $cached ) {
		return $cached;
	}

	$terms = get_terms( $args );

	if ( ! is_wp_error( $terms ) ) {
		event_manager_cache_set( $key, $terms, EVENT_MANAGER_TERMS_CACHE_TTL );
	}

	return $terms;
}
