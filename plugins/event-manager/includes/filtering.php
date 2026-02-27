<?php
/**
 * Advanced Archive Filtering for Event Manager.
 *
 * Modifies the main WordPress query on event archive and
 * event_type taxonomy pages to support filtering by keyword
 * search, Event Type, and date range via GET parameters.
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
 * Modify the main query for event archive filtering.
 *
 * Applies the following filters from GET parameters:
 * - em_search:    keyword search via WP_Query 's' parameter.
 * - em_type:      taxonomy slug filter via tax_query.
 * - em_date_from: minimum date filter via meta_query (>= comparison).
 * - em_date_to:   maximum date filter via meta_query (<= comparison).
 *
 * Also sets default ordering by _event_manager_date ASC.
 *
 * @since  1.0.0
 * @param  WP_Query $query The main WordPress query object.
 * @return void
 */
function event_manager_archive_filter_query( $query ) {

	// Only modify the main query on the front end for event archives.
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! ( is_post_type_archive( 'event' ) || is_tax( 'event_type' ) ) ) {
		return;
	}

	// Default ordering by event date.
	$query->set( 'meta_key', '_event_manager_date' );
	$query->set( 'orderby', 'meta_value' );
	$query->set( 'order', 'ASC' );

	$meta_query = array();

	// Keyword search.
	if ( isset( $_GET['em_search'] ) && ! empty( $_GET['em_search'] ) ) {
		$search = sanitize_text_field( wp_unslash( $_GET['em_search'] ) );
		$query->set( 's', $search );
	}

	// Event Type taxonomy filter.
	if ( isset( $_GET['em_type'] ) && ! empty( $_GET['em_type'] ) && '0' !== $_GET['em_type'] ) {
		$type_slug = sanitize_text_field( wp_unslash( $_GET['em_type'] ) );
		$tax_query = array(
			array(
				'taxonomy' => 'event_type',
				'field'    => 'slug',
				'terms'    => $type_slug,
			),
		);
		$query->set( 'tax_query', $tax_query );
	}

	// Date range: from.
	if ( isset( $_GET['em_date_from'] ) && ! empty( $_GET['em_date_from'] ) ) {
		$from = sanitize_text_field( wp_unslash( $_GET['em_date_from'] ) );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ) {
			$meta_query[] = array(
				'key'     => '_event_manager_date',
				'value'   => $from,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}
	}

	// Date range: to.
	if ( isset( $_GET['em_date_to'] ) && ! empty( $_GET['em_date_to'] ) ) {
		$to = sanitize_text_field( wp_unslash( $_GET['em_date_to'] ) );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ) {
			$meta_query[] = array(
				'key'     => '_event_manager_date',
				'value'   => $to,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}
	}

	if ( ! empty( $meta_query ) ) {
		$meta_query['relation'] = 'AND';
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'event_manager_archive_filter_query' );
