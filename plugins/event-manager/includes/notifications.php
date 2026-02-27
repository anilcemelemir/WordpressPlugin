<?php
/**
 * Email Notification System for Event Manager.
 *
 * Sends email notifications via wp_mail() when events are
 * published or updated. Notifies all registered users on
 * new event publication, and RSVP'd users on event updates.
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
 * Send email notification when an event is first published or updated.
 *
 * Hooks into 'transition_post_status' to detect when an event
 * transitions to 'publish' status for the first time, or is
 * updated while already published.
 *
 * @since  1.1.0
 * @param  string  $new_status The new post status.
 * @param  string  $old_status The previous post status.
 * @param  WP_Post $post       The post object.
 * @return void
 */
function event_manager_notify_on_status_change( $new_status, $old_status, $post ) {
	// Only act on 'event' post type.
	if ( 'event' !== $post->post_type ) {
		return;
	}

	// Prevent duplicate notifications during autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// New publication: status changing TO 'publish' from anything other than 'publish'.
	if ( 'publish' === $new_status && 'publish' !== $old_status ) {
		event_manager_send_new_event_email( $post );
		return;
	}

	// Update: already published and being saved again.
	if ( 'publish' === $new_status && 'publish' === $old_status ) {
		event_manager_send_updated_event_email( $post );
	}
}
add_action( 'transition_post_status', 'event_manager_notify_on_status_change', 10, 3 );

/**
 * Send "New Event Published" email to all registered users.
 *
 * Retrieves all users with registered accounts and sends
 * each a notification about the newly published event.
 * Recipients list is filterable via 'event_manager_new_event_recipients'.
 *
 * @since  1.1.0
 * @param  WP_Post $post The published event post object.
 * @return void
 */
function event_manager_send_new_event_email( $post ) {
	$recipients = event_manager_get_notification_recipients( 'new_event', $post );

	if ( empty( $recipients ) ) {
		return;
	}

	$event_date     = get_post_meta( $post->ID, '_event_manager_date', true );
	$event_location = get_post_meta( $post->ID, '_event_manager_location', true );
	$event_url      = get_permalink( $post->ID );
	$site_name      = get_bloginfo( 'name' );

	$subject = sprintf(
		/* translators: 1: Site name 2: Event title */
		__( '[%1$s] New Event: %2$s', 'event-manager' ),
		$site_name,
		$post->post_title
	);

	$message = event_manager_build_email_html( array(
		'heading'  => __( 'New Event Published!', 'event-manager' ),
		'title'    => $post->post_title,
		'date'     => $event_date,
		'location' => $event_location,
		'excerpt'  => wp_trim_words( wp_strip_all_tags( $post->post_content ), 40, '...' ),
		'url'      => $event_url,
		'cta_text' => __( 'View Event Details', 'event-manager' ),
	) );

	$headers = event_manager_get_email_headers();

	// Use BCC for bulk sending to protect recipient privacy.
	event_manager_send_bulk_email( $recipients, $subject, $message, $headers );

	/**
	 * Fires after new event notification emails are sent.
	 *
	 * @since 1.1.0
	 * @param WP_Post $post       The event post object.
	 * @param array   $recipients Array of recipient email addresses.
	 */
	do_action( 'event_manager_after_new_event_notification', $post, $recipients );
}

/**
 * Send "Event Updated" email to RSVP'd users.
 *
 * Only notifies users who have confirmed their RSVP for the
 * specific event being updated. Recipients list is filterable
 * via 'event_manager_updated_event_recipients'.
 *
 * @since  1.1.0
 * @param  WP_Post $post The updated event post object.
 * @return void
 */
function event_manager_send_updated_event_email( $post ) {
	$recipients = event_manager_get_notification_recipients( 'updated_event', $post );

	if ( empty( $recipients ) ) {
		return;
	}

	$event_date     = get_post_meta( $post->ID, '_event_manager_date', true );
	$event_location = get_post_meta( $post->ID, '_event_manager_location', true );
	$event_url      = get_permalink( $post->ID );
	$site_name      = get_bloginfo( 'name' );

	$subject = sprintf(
		/* translators: 1: Site name 2: Event title */
		__( '[%1$s] Event Updated: %2$s', 'event-manager' ),
		$site_name,
		$post->post_title
	);

	$message = event_manager_build_email_html( array(
		'heading'  => __( 'Event Details Updated', 'event-manager' ),
		'title'    => $post->post_title,
		'date'     => $event_date,
		'location' => $event_location,
		'excerpt'  => wp_trim_words( wp_strip_all_tags( $post->post_content ), 40, '...' ),
		'url'      => $event_url,
		'cta_text' => __( 'View Updated Event', 'event-manager' ),
	) );

	$headers = event_manager_get_email_headers();

	// Use BCC for bulk sending to protect recipient privacy.
	event_manager_send_bulk_email( $recipients, $subject, $message, $headers );

	/**
	 * Fires after event update notification emails are sent.
	 *
	 * @since 1.1.0
	 * @param WP_Post $post       The event post object.
	 * @param array   $recipients Array of recipient email addresses.
	 */
	do_action( 'event_manager_after_updated_event_notification', $post, $recipients );
}

/**
 * Send an email to multiple recipients using BCC for privacy.
 *
 * Sends a single email with all recipients in BCC to avoid
 * exposing user email addresses to each other and to reduce
 * the number of wp_mail() calls for better performance.
 *
 * @since  1.1.0
 * @param  array  $recipients Array of email addresses.
 * @param  string $subject    The email subject line.
 * @param  string $message    The email body (HTML).
 * @param  array  $headers    Array of email header strings.
 * @return bool Whether the email was sent successfully.
 */
function event_manager_send_bulk_email( $recipients, $subject, $message, $headers ) {
	if ( empty( $recipients ) ) {
		return false;
	}

	$admin_email = get_option( 'admin_email' );

	// Add recipients as BCC headers for privacy.
	foreach ( $recipients as $email ) {
		$headers[] = 'Bcc: ' . $email;
	}

	/**
	 * Filters the batch size for bulk email sending.
	 *
	 * Limits the number of BCC recipients per wp_mail() call
	 * to prevent server-side email limits from blocking delivery.
	 *
	 * @since 1.1.0
	 * @param int $batch_size Maximum BCC recipients per email. Default 50.
	 */
	$batch_size = apply_filters( 'event_manager_email_batch_size', 50 );

	// Send in batches if recipient count exceeds batch size.
	if ( count( $recipients ) > $batch_size ) {
		$batches = array_chunk( $recipients, $batch_size );
		$success = true;

		foreach ( $batches as $batch ) {
			$batch_headers = event_manager_get_email_headers();
			foreach ( $batch as $email ) {
				$batch_headers[] = 'Bcc: ' . $email;
			}
			if ( ! wp_mail( $admin_email, $subject, $message, $batch_headers ) ) {
				$success = false;
			}
		}

		return $success;
	}

	return wp_mail( $admin_email, $subject, $message, $headers );
}

/**
 * Get notification recipient email addresses.
 *
 * For new events: returns all registered user emails.
 * For updates: returns emails of users who RSVP'd to the event.
 * Results are filterable via 'event_manager_{$type}_recipients' hook.
 *
 * @since  1.1.0
 * @param  string  $type 'new_event' or 'updated_event'.
 * @param  WP_Post $post The event post object.
 * @return array Array of unique, valid email addresses.
 */
function event_manager_get_notification_recipients( $type, $post ) {
	$emails = array();

	if ( 'new_event' === $type ) {
		// Notify all registered users for new events.
		$users = get_users( array(
			'fields' => array( 'user_email' ),
		) );

		foreach ( $users as $user ) {
			if ( is_email( $user->user_email ) ) {
				$emails[] = $user->user_email;
			}
		}
	} elseif ( 'updated_event' === $type ) {
		// Notify only RSVP'd users for event updates.
		$rsvp_list = get_post_meta( $post->ID, '_event_rsvp_list', true );
		$rsvp_list = is_array( $rsvp_list ) ? $rsvp_list : array();

		foreach ( $rsvp_list as $user_id ) {
			$user = get_userdata( (int) $user_id );
			if ( $user && is_email( $user->user_email ) ) {
				$emails[] = $user->user_email;
			}
		}
	}

	// Remove duplicates.
	$emails = array_unique( $emails );

	/**
	 * Filters the notification recipient email addresses.
	 *
	 * Allows third-party code to add, remove, or replace the
	 * recipients list for a specific notification type.
	 *
	 * @since 1.1.0
	 * @param array   $emails Array of recipient email addresses.
	 * @param string  $type   Notification type: 'new_event' or 'updated_event'.
	 * @param WP_Post $post   The event post object.
	 */
	return apply_filters( "event_manager_{$type}_recipients", $emails, $type, $post );
}

/**
 * Build an HTML email body for event notifications.
 *
 * Uses inline CSS for maximum email client compatibility.
 * The generated HTML is filterable via 'event_manager_email_html'.
 *
 * @since  1.1.0
 * @param  array $args {
 *     Email content arguments.
 *
 *     @type string $heading  The email heading text.
 *     @type string $title    The event title.
 *     @type string $date     The event date (YYYY-MM-DD format).
 *     @type string $location The event location.
 *     @type string $excerpt  A short excerpt of the event content.
 *     @type string $url      The full URL to the event page.
 *     @type string $cta_text The call-to-action button text.
 * }
 * @return string The complete HTML email body.
 */
function event_manager_build_email_html( $args ) {
	$defaults = array(
		'heading'  => '',
		'title'    => '',
		'date'     => '',
		'location' => '',
		'excerpt'  => '',
		'url'      => '',
		'cta_text' => __( 'View Event', 'event-manager' ),
	);

	$args      = wp_parse_args( $args, $defaults );
	$site_name = get_bloginfo( 'name' );

	// Format the date for display.
	$formatted_date = '';
	if ( ! empty( $args['date'] ) ) {
		$timestamp = strtotime( $args['date'] );
		if ( false !== $timestamp ) {
			$formatted_date = date_i18n( get_option( 'date_format' ), $timestamp );
		} else {
			$formatted_date = esc_html( $args['date'] );
		}
	}

	ob_start();
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
	</head>
	<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;">
			<tr>
				<td align="center" style="padding:30px 15px;">
					<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
						<!-- Header -->
						<tr>
							<td style="background-color:#0073aa;padding:30px 40px;text-align:center;">
								<h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">
									<?php echo esc_html( $args['heading'] ); ?>
								</h1>
							</td>
						</tr>
						<!-- Body -->
						<tr>
							<td style="padding:30px 40px;">
								<h2 style="margin:0 0 15px;color:#1d2327;font-size:22px;">
									<?php echo esc_html( $args['title'] ); ?>
								</h2>

								<?php if ( ! empty( $formatted_date ) ) : ?>
									<p style="margin:8px 0;color:#50575e;font-size:14px;">
										<strong><?php esc_html_e( 'Date:', 'event-manager' ); ?></strong>
										<?php echo esc_html( $formatted_date ); ?>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $args['location'] ) ) : ?>
									<p style="margin:8px 0;color:#50575e;font-size:14px;">
										<strong><?php esc_html_e( 'Location:', 'event-manager' ); ?></strong>
										<?php echo esc_html( $args['location'] ); ?>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $args['excerpt'] ) ) : ?>
									<p style="margin:20px 0;color:#3c434a;font-size:15px;line-height:1.6;">
										<?php echo esc_html( $args['excerpt'] ); ?>
									</p>
								<?php endif; ?>

								<table role="presentation" cellpadding="0" cellspacing="0" style="margin:25px 0 10px;">
									<tr>
										<td style="background-color:#0073aa;border-radius:4px;">
											<a href="<?php echo esc_url( $args['url'] ); ?>"
											   style="display:inline-block;padding:12px 28px;color:#ffffff;font-size:15px;font-weight:600;text-decoration:none;">
												<?php echo esc_html( $args['cta_text'] ); ?>
											</a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<!-- Footer -->
						<tr>
							<td style="background-color:#f0f0f1;padding:20px 40px;text-align:center;">
								<p style="margin:0;color:#787c82;font-size:12px;">
									<?php
									printf(
										/* translators: %s: Site name */
										esc_html__( 'This email was sent by %s. You are receiving this because you are a registered user.', 'event-manager' ),
										esc_html( $site_name )
									);
									?>
								</p>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
	</html>
	<?php
	$html = ob_get_clean();

	/**
	 * Filters the HTML email body before sending.
	 *
	 * @since 1.1.0
	 * @param string $html The complete HTML email body.
	 * @param array  $args The email content arguments.
	 */
	return apply_filters( 'event_manager_email_html', $html, $args );
}

/**
 * Get default email headers for HTML notifications.
 *
 * Sets content type to HTML and configures the From header
 * using the WordPress site name and admin email address.
 *
 * @since  1.1.0
 * @return array Array of email header strings.
 */
function event_manager_get_email_headers() {
	$from_name  = get_bloginfo( 'name' );
	$from_email = get_option( 'admin_email' );

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		sprintf( 'From: %s <%s>', $from_name, $from_email ),
	);

	/**
	 * Filters the email headers used for event notifications.
	 *
	 * @since 1.1.0
	 * @param array $headers Array of email header strings.
	 */
	return apply_filters( 'event_manager_email_headers', $headers );
}
