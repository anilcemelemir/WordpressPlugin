<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all plugin data from the database including:
 * - All event posts and their meta data
 * - Event type taxonomy terms
 * - Plugin transient caches
 * - Plugin options
 *
 * @package    Event_Manager
 * @since      1.0.0
 * @since      1.1.0 Added transient cache cleanup.
 * @author     Developer
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Flush all plugin transient caches.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '\_transient\_em\_%'
	    OR option_name LIKE '\_transient\_timeout\_em\_%'"
);

// Delete all event posts and their postmeta.
$event_posts = get_posts(
	array(
		'post_type'      => 'event',
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
	)
);

foreach ( $event_posts as $post_id ) {
	wp_delete_post( $post_id, true );
}

// Delete all event_type taxonomy terms.
$terms = get_terms(
	array(
		'taxonomy'   => 'event_type',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);

if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( $term_id, 'event_type' );
	}
}
