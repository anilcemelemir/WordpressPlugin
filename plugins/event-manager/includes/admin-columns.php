<?php
/**
 * Custom Admin Columns for the Event Post Type.
 *
 * Adds sortable Event Date and Location columns to the
 * admin list table for the 'event' post type.
 *
 * @package    Event_Manager
 * @subpackage Event_Manager/Includes
 * @since      1.0.0
 * @author     Developer
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom columns to the Event admin list table.
 *
 * Inserts 'Event Date' and 'Location' columns immediately
 * after the 'Title' column.
 *
 * @since  1.0.0
 * @param  array $columns Existing column definitions keyed by slug.
 * @return array Modified column definitions with custom columns inserted.
 */
function event_manager_custom_columns( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;

		// Insert custom columns right after the title column.
		if ( 'title' === $key ) {
			$new_columns['event_date']     = __( 'Event Date', 'event-manager' );
			$new_columns['event_location'] = __( 'Location', 'event-manager' );
		}
	}

	return $new_columns;
}
add_filter( 'manage_event_posts_columns', 'event_manager_custom_columns' );

/**
 * Populate custom column content for each event row.
 *
 * Renders the event date (formatted via date_i18n()) and
 * location in the corresponding custom columns.
 *
 * @since  1.0.0
 * @param  string $column  The column slug being rendered.
 * @param  int    $post_id The post ID for the current row.
 * @return void
 */
function event_manager_custom_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'event_date':
			$date = get_post_meta( $post_id, '_event_manager_date', true );
			if ( ! empty( $date ) ) {
				// Format the date for display using WordPress date format.
				$timestamp = strtotime( $date );
				if ( false !== $timestamp ) {
					echo esc_html( date_i18n( get_option( 'date_format' ), $timestamp ) );
				} else {
					echo esc_html( $date );
				}
			} else {
				echo '&mdash;';
			}
			break;

		case 'event_location':
			$location = get_post_meta( $post_id, '_event_manager_location', true );
			echo ! empty( $location ) ? esc_html( $location ) : '&mdash;';
			break;
	}
}
add_action( 'manage_event_posts_custom_column', 'event_manager_custom_column_content', 10, 2 );

/**
 * Register custom columns as sortable.
 *
 * @since  1.0.0
 * @param  array $columns Existing sortable column definitions.
 * @return array Modified sortable column definitions.
 */
function event_manager_sortable_columns( $columns ) {
	$columns['event_date']     = 'event_date';
	$columns['event_location'] = 'event_location';
	return $columns;
}
add_filter( 'manage_edit-event_sortable_columns', 'event_manager_sortable_columns' );

/**
 * Handle sorting by custom columns in admin list queries.
 *
 * Modifies the WP_Query meta_key and orderby parameters when
 * the user clicks a sortable custom column header.
 *
 * @since  1.0.0
 * @param  WP_Query $query The current admin list query.
 * @return void
 */
function event_manager_custom_column_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	if ( 'event_date' === $orderby ) {
		$query->set( 'meta_key', '_event_manager_date' );
		$query->set( 'orderby', 'meta_value' );
	}

	if ( 'event_location' === $orderby ) {
		$query->set( 'meta_key', '_event_manager_location' );
		$query->set( 'orderby', 'meta_value' );
	}
}
add_action( 'pre_get_posts', 'event_manager_custom_column_orderby' );
