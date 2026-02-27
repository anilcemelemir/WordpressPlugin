<?php
/**
 * Meta Boxes for the Event Custom Post Type.
 *
 * Provides the 'Event Details' meta box with Date and Location
 * fields, including nonce verification, strict input validation,
 * and sanitisation. Compatible with the block editor (Gutenberg).
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
 * Register the Event Details meta box.
 *
 * Adds a high-priority meta box on the 'event' edit screen
 * with block editor compatibility flags to ensure it renders
 * correctly in both the classic and Gutenberg editors.
 *
 * @since  1.0.0
 * @return void
 */
function event_manager_add_meta_boxes() {
	add_meta_box(
		'event_manager_details',
		__( 'Event Details', 'event-manager' ),
		'event_manager_render_meta_box',
		'event',
		'normal',
		'high',
		array(
			'__block_editor_compatible_meta_box' => true,
			'__back_compat_meta_box'            => false,
		)
	);
}
add_action( 'add_meta_boxes', 'event_manager_add_meta_boxes' );

/**
 * Render the Event Details meta box HTML.
 *
 * Outputs date (type="date") and location (type="text") input
 * fields with a nonce for CSRF protection. Uses div-based layout
 * for block editor compatibility.
 *
 * @since  1.0.0
 * @param  WP_Post $post The current post object being edited.
 * @return void
 */
function event_manager_render_meta_box( $post ) {

	// Generate a nonce field for CSRF protection.
	wp_nonce_field( 'event_manager_save_meta', 'event_manager_meta_nonce' );

	// Retrieve existing meta values.
	$event_date     = get_post_meta( $post->ID, '_event_manager_date', true );
	$event_location = get_post_meta( $post->ID, '_event_manager_location', true );

	?>
	<div class="event-manager-meta-box">
		<style>
			.event-manager-meta-box .em-field-row {
				margin-bottom: 15px;
			}
			.event-manager-meta-box .em-field-row:last-child {
				margin-bottom: 0;
			}
			.event-manager-meta-box label {
				display: block;
				font-weight: 600;
				margin-bottom: 5px;
			}
			.event-manager-meta-box input[type="date"],
			.event-manager-meta-box input[type="text"] {
				width: 100%;
				max-width: 400px;
				padding: 6px 8px;
			}
			.event-manager-meta-box .description {
				margin-top: 4px;
				color: #646970;
				font-style: italic;
			}
		</style>

		<div class="em-field-row">
			<label for="event_manager_date">
				<?php esc_html_e( 'Event Date', 'event-manager' ); ?>
			</label>
			<input
				type="date"
				id="event_manager_date"
				name="event_manager_date"
				value="<?php echo esc_attr( $event_date ); ?>"
			/>
			<p class="description">
				<?php esc_html_e( 'Select the date of the event (YYYY-MM-DD).', 'event-manager' ); ?>
			</p>
		</div>

		<div class="em-field-row">
			<label for="event_manager_location">
				<?php esc_html_e( 'Location', 'event-manager' ); ?>
			</label>
			<input
				type="text"
				id="event_manager_location"
				name="event_manager_location"
				value="<?php echo esc_attr( $event_location ); ?>"
				placeholder="<?php esc_attr_e( 'e.g. New York, NY', 'event-manager' ); ?>"
			/>
			<p class="description">
				<?php esc_html_e( 'Enter the location or venue of the event.', 'event-manager' ); ?>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Save the Event Details meta box data.
 *
 * Performs five security checks before saving:
 * 1. Nonce verification (CSRF protection)
 * 2. Autosave guard
 * 3. User capability check
 * 4. Post type verification
 * 5. Input validation (date regex + checkdate())
 *
 * @since  1.0.0
 * @param  int $post_id The ID of the post being saved.
 * @return void
 */
function event_manager_save_meta( $post_id ) {

	// 1. Verify nonce — prevent CSRF.
	if ( ! isset( $_POST['event_manager_meta_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['event_manager_meta_nonce'], 'event_manager_save_meta' ) ) {
		return;
	}

	// 2. Prevent saving during autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// 3. Check user permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// 4. Ensure we're saving the correct post type.
	if ( 'event' !== get_post_type( $post_id ) ) {
		return;
	}

	// 5. Sanitize and validate Event Date.
	if ( isset( $_POST['event_manager_date'] ) ) {
		$date_raw = sanitize_text_field( wp_unslash( $_POST['event_manager_date'] ) );

		// Strict date validation: must be YYYY-MM-DD and a real date.
		if ( empty( $date_raw ) ) {
			delete_post_meta( $post_id, '_event_manager_date' );
		} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_raw ) ) {
			$date_parts = explode( '-', $date_raw );
			if ( checkdate( (int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[0] ) ) {
				update_post_meta( $post_id, '_event_manager_date', $date_raw );
			}
		}
	}

	// 6. Sanitize and save Location.
	if ( isset( $_POST['event_manager_location'] ) ) {
		$location_raw = sanitize_text_field( wp_unslash( $_POST['event_manager_location'] ) );

		if ( empty( $location_raw ) ) {
			delete_post_meta( $post_id, '_event_manager_location' );
		} else {
			update_post_meta( $post_id, '_event_manager_location', $location_raw );
		}
	}
}
add_action( 'save_post', 'event_manager_save_meta' );
