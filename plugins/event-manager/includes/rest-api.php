<?php
/**
 * REST API Meta Registration for Event Manager.
 *
 * Exposes _event_manager_date and _event_manager_location as
 * readable/writable fields on the Event post type via the
 * WordPress REST API. Includes sanitisation and auth callbacks.
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
 * Register meta fields for the REST API.
 *
 * Makes _event_manager_date and _event_manager_location available
 * in the 'meta' object of REST API responses and requests.
 * Write access requires the 'edit_posts' capability.
 *
 * @since  1.0.0
 * @return void
 * @see    register_post_meta()
 */
function event_manager_register_rest_meta() {

	// Event Date meta field.
	register_post_meta( 'event', '_event_manager_date', array(
		'type'              => 'string',
		'description'       => __( 'The date of the event in YYYY-MM-DD format.', 'event-manager' ),
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'event_manager_sanitize_date',
		'auth_callback'     => function () {
			return current_user_can( 'edit_posts' );
		},
	) );

	// Event Location meta field.
	register_post_meta( 'event', '_event_manager_location', array(
		'type'              => 'string',
		'description'       => __( 'The location or venue of the event.', 'event-manager' ),
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => function () {
			return current_user_can( 'edit_posts' );
		},
	) );
}
add_action( 'init', 'event_manager_register_rest_meta' );

/**
 * Sanitise callback for the event date meta field.
 *
 * Validates that the value matches YYYY-MM-DD format and
 * represents a real calendar date using PHP's checkdate().
 * Used by both register_post_meta() and the REST API.
 *
 * @since  1.0.0
 * @param  string $value The raw date value from the request.
 * @return string Sanitised date string in YYYY-MM-DD format, or empty string if invalid.
 */
function event_manager_sanitize_date( $value ) {
	$value = sanitize_text_field( $value );

	if ( empty( $value ) ) {
		return '';
	}

	// Must match YYYY-MM-DD.
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return '';
	}

	// Must be a real calendar date.
	$parts = explode( '-', $value );
	if ( ! checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] ) ) {
		return '';
	}

	return $value;
}
