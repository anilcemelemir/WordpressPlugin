<?php
/**
 * RSVP AJAX Handler for Event Manager.
 *
 * Handles confirm/cancel RSVP actions via WordPress AJAX.
 * Stores attendee data as an array of User IDs in the
 * _event_rsvp_list post meta field. Includes nonce verification,
 * login checks, and duplicate prevention.
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
 * Enqueue RSVP front-end JavaScript on single event pages.
 *
 * Registers the rsvp.js script with jQuery dependency and
 * localises it with AJAX URL, nonce, and translatable strings.
 *
 * @since  1.0.0
 * @return void
 */
function event_manager_enqueue_rsvp_scripts() {
	if ( ! is_singular( 'event' ) ) {
		return;
	}

	wp_enqueue_script(
		'event-manager-rsvp',
		EVENT_MANAGER_PLUGIN_URL . 'assets/js/rsvp.js',
		array( 'jquery' ),
		EVENT_MANAGER_VERSION,
		true
	);

	wp_localize_script( 'event-manager-rsvp', 'emRSVP', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'event_manager_rsvp_nonce' ),
		'strings'  => array(
			'confirm'      => __( 'Confirm Attendance', 'event-manager' ),
			'cancel'       => __( 'Cancel Attendance', 'event-manager' ),
			'attending'    => __( 'You are attending this event!', 'event-manager' ),
			'error'        => __( 'Something went wrong. Please try again.', 'event-manager' ),
			'login'        => __( 'You must be logged in to RSVP.', 'event-manager' ),
			/* translators: %d: Number of attendees */
			'count_single' => __( '%d person attending', 'event-manager' ),
			/* translators: %d: Number of attendees */
			'count_plural' => __( '%d people attending', 'event-manager' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'event_manager_enqueue_rsvp_scripts' );

/**
 * Handle RSVP AJAX request for authenticated users.
 *
 * Processes 'confirm' and 'cancel' actions:
 * - confirm: adds the current user ID to the RSVP list (no duplicates).
 * - cancel: removes the current user ID and re-indexes the array.
 *
 * Security: validates nonce, checks login status, sanitises inputs,
 * and verifies the event exists with correct post type.
 *
 * @since  1.0.0
 * @return void Sends JSON response via wp_send_json_success/error().
 */
function event_manager_handle_rsvp() {

	// 1. Verify nonce — CSRF protection.
	check_ajax_referer( 'event_manager_rsvp_nonce', 'nonce' );

	// 2. Verify user is logged in.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'You must be logged in to RSVP.', 'event-manager' ) ), 403 );
	}

	// 3. Validate and sanitize inputs.
	$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
	$action   = isset( $_POST['rsvp_action'] ) ? sanitize_text_field( wp_unslash( $_POST['rsvp_action'] ) ) : '';

	if ( ! $event_id || ! in_array( $action, array( 'confirm', 'cancel' ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid request.', 'event-manager' ) ), 400 );
	}

	// 4. Verify the event exists and is the correct post type.
	$event = get_post( $event_id );
	if ( ! $event || 'event' !== $event->post_type ) {
		wp_send_json_error( array( 'message' => __( 'Event not found.', 'event-manager' ) ), 404 );
	}

	// 5. Get current RSVP list.
	$user_id   = get_current_user_id();
	$rsvp_list = get_post_meta( $event_id, '_event_rsvp_list', true );
	$rsvp_list = is_array( $rsvp_list ) ? $rsvp_list : array();

	// 6. Process the action.
	if ( 'confirm' === $action ) {
		if ( ! in_array( $user_id, $rsvp_list, true ) ) {
			$rsvp_list[] = $user_id;
		}
	} elseif ( 'cancel' === $action ) {
		$rsvp_list = array_values( array_diff( $rsvp_list, array( $user_id ) ) );
	}

	// 7. Save updated list.
	update_post_meta( $event_id, '_event_rsvp_list', $rsvp_list );

	// 8. Return response.
	wp_send_json_success( array(
		'rsvp_status' => in_array( $user_id, $rsvp_list, true ) ? 'confirmed' : 'cancelled',
		'rsvp_count'  => count( $rsvp_list ),
	) );
}
add_action( 'wp_ajax_event_manager_rsvp', 'event_manager_handle_rsvp' );

/**
 * Handle RSVP AJAX request for unauthenticated users.
 *
 * Returns a 403 JSON error instructing the user to log in.
 *
 * @since  1.0.0
 * @return void Sends JSON error response.
 */
function event_manager_handle_rsvp_nopriv() {
	wp_send_json_error( array( 'message' => __( 'You must be logged in to RSVP.', 'event-manager' ) ), 403 );
}
add_action( 'wp_ajax_nopriv_event_manager_rsvp', 'event_manager_handle_rsvp_nopriv' );
