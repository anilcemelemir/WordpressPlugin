<?php
/**
 * Template: Single Event
 *
 * Displays a single event with full details including title,
 * featured image, event date, location, taxonomy terms,
 * post content, and the RSVP attendance section.
 *
 * This template can be overridden by copying it to:
 *   yourtheme/event-manager/single-event.php
 *
 * @package    Event_Manager
 * @subpackage Event_Manager/Templates
 * @since      1.0.0
 * @author     Developer
 */

get_header(); ?>

<div class="em-single-event-wrap">
	<?php while ( have_posts() ) : the_post(); ?>

		<article id="event-<?php the_ID(); ?>" <?php post_class( 'em-single-event' ); ?>>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="em-featured-image">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>

			<div class="em-event-content">

				<h1 class="em-event-title"><?php the_title(); ?></h1>

				<div class="em-event-meta">
					<?php
					$event_date     = get_post_meta( get_the_ID(), '_event_manager_date', true );
					$event_location = get_post_meta( get_the_ID(), '_event_manager_location', true );
					$event_types    = get_the_terms( get_the_ID(), 'event_type' );
					?>

					<?php if ( ! empty( $event_date ) ) : ?>
						<div class="em-meta-item em-meta-date">
							<span class="em-meta-icon dashicons dashicons-calendar-alt"></span>
							<span class="em-meta-label"><?php esc_html_e( 'Date:', 'event-manager' ); ?></span>
							<span class="em-meta-value">
								<?php
								$timestamp = strtotime( $event_date );
								echo esc_html( false !== $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : $event_date );
								?>
							</span>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $event_location ) ) : ?>
						<div class="em-meta-item em-meta-location">
							<span class="em-meta-icon dashicons dashicons-location"></span>
							<span class="em-meta-label"><?php esc_html_e( 'Location:', 'event-manager' ); ?></span>
							<span class="em-meta-value"><?php echo esc_html( $event_location ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $event_types ) && ! is_wp_error( $event_types ) ) : ?>
						<div class="em-meta-item em-meta-types">
							<span class="em-meta-icon dashicons dashicons-tag"></span>
							<span class="em-meta-label"><?php esc_html_e( 'Type:', 'event-manager' ); ?></span>
							<span class="em-meta-value">
								<?php
								$type_links = array();
								foreach ( $event_types as $type ) {
									$type_links[] = '<a href="' . esc_url( get_term_link( $type ) ) . '">' . esc_html( $type->name ) . '</a>';
								}
								echo wp_kses_post( implode( ', ', $type_links ) );
								?>
							</span>
						</div>
					<?php endif; ?>
				</div>

				<div class="em-event-description">
					<?php the_content(); ?>
				</div>

				<?php // RSVP Section. ?>
				<div class="em-rsvp-section">
					<h3><?php esc_html_e( 'RSVP', 'event-manager' ); ?></h3>

					<?php if ( is_user_logged_in() ) : ?>
						<?php
						$current_user_id = get_current_user_id();
						$rsvp_list       = get_post_meta( get_the_ID(), '_event_rsvp_list', true );
						$rsvp_list       = is_array( $rsvp_list ) ? $rsvp_list : array();
						$has_rsvp        = in_array( $current_user_id, $rsvp_list, true );
						$rsvp_count      = count( $rsvp_list );
						?>

						<p class="em-rsvp-count">
							<?php
							/* translators: %d: Number of attendees */
							printf( esc_html( _n( '%d person attending', '%d people attending', $rsvp_count, 'event-manager' ) ), (int) $rsvp_count );
							?>
						</p>

						<div id="em-rsvp-container" data-event-id="<?php echo esc_attr( get_the_ID() ); ?>">
							<?php if ( $has_rsvp ) : ?>
								<p class="em-rsvp-status em-rsvp-confirmed">
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'You are attending this event!', 'event-manager' ); ?>
								</p>
								<button type="button" class="em-rsvp-btn em-rsvp-cancel" data-action="cancel">
									<?php esc_html_e( 'Cancel Attendance', 'event-manager' ); ?>
								</button>
							<?php else : ?>
								<button type="button" class="em-rsvp-btn em-rsvp-confirm" data-action="confirm">
									<?php esc_html_e( 'Confirm Attendance', 'event-manager' ); ?>
								</button>
							<?php endif; ?>
						</div>

					<?php else : ?>
						<p class="em-rsvp-login-notice">
							<?php
							printf(
								/* translators: %s: Login URL */
								wp_kses_post( __( 'Please <a href="%s">log in</a> to RSVP for this event.', 'event-manager' ) ),
								esc_url( wp_login_url( get_permalink() ) )
							);
							?>
						</p>
					<?php endif; ?>
				</div>

			</div>

		</article>

	<?php endwhile; ?>
</div>

<?php get_footer(); ?>
