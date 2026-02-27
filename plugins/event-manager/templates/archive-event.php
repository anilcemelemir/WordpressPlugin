<?php
/**
 * Template: Archive Event
 *
 * Displays the event archive page with a filter bar (search,
 * event type dropdown, date range) and a responsive card grid
 * of published events with pagination.
 *
 * This template can be overridden by copying it to:
 *   yourtheme/event-manager/archive-event.php
 *
 * @package    Event_Manager
 * @subpackage Event_Manager/Templates
 * @since      1.0.0
 * @author     Developer
 */

get_header(); ?>

<div class="em-archive-wrap">

	<header class="em-archive-header">
		<?php if ( is_tax( 'event_type' ) ) : ?>
			<h1 class="em-archive-title">
				<?php
				/* translators: %s: Taxonomy term name */
				printf( esc_html__( 'Events: %s', 'event-manager' ), esc_html( single_term_title( '', false ) ) );
				?>
			</h1>
		<?php else : ?>
			<h1 class="em-archive-title"><?php esc_html_e( 'Events', 'event-manager' ); ?></h1>
		<?php endif; ?>
	</header>

	<?php // Filters Section. ?>
	<div class="em-filters">
		<form method="get" class="em-filter-form" action="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>">

			<div class="em-filter-row">
				<div class="em-filter-field">
					<label for="em-search"><?php esc_html_e( 'Search Events', 'event-manager' ); ?></label>
					<input
						type="text"
						id="em-search"
						name="em_search"
						value="<?php echo esc_attr( isset( $_GET['em_search'] ) ? sanitize_text_field( wp_unslash( $_GET['em_search'] ) ) : '' ); ?>"
						placeholder="<?php esc_attr_e( 'Search by title or content...', 'event-manager' ); ?>"
					/>
				</div>

				<div class="em-filter-field">
					<label for="em-type"><?php esc_html_e( 'Event Type', 'event-manager' ); ?></label>
					<?php
					$selected_type = isset( $_GET['em_type'] ) ? sanitize_text_field( wp_unslash( $_GET['em_type'] ) ) : '';
					wp_dropdown_categories( array(
						'taxonomy'        => 'event_type',
						'name'            => 'em_type',
						'id'              => 'em-type',
						'show_option_all' => __( 'All Types', 'event-manager' ),
						'value_field'     => 'slug',
						'selected'        => $selected_type,
						'hide_empty'      => false,
					) );
					?>
				</div>

				<div class="em-filter-field">
					<label for="em-date-from"><?php esc_html_e( 'From Date', 'event-manager' ); ?></label>
					<input
						type="date"
						id="em-date-from"
						name="em_date_from"
						value="<?php echo esc_attr( isset( $_GET['em_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['em_date_from'] ) ) : '' ); ?>"
					/>
				</div>

				<div class="em-filter-field">
					<label for="em-date-to"><?php esc_html_e( 'To Date', 'event-manager' ); ?></label>
					<input
						type="date"
						id="em-date-to"
						name="em_date_to"
						value="<?php echo esc_attr( isset( $_GET['em_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['em_date_to'] ) ) : '' ); ?>"
					/>
				</div>
			</div>

			<div class="em-filter-actions">
				<button type="submit" class="em-btn em-btn-filter">
					<?php esc_html_e( 'Filter Events', 'event-manager' ); ?>
				</button>
				<a href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>" class="em-btn em-btn-reset">
					<?php esc_html_e( 'Reset', 'event-manager' ); ?>
				</a>
			</div>

		</form>
	</div>

	<?php // Event Grid. ?>
	<div class="em-event-grid">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>

				<div class="em-event-card">
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>" class="em-card-image">
							<?php the_post_thumbnail( 'medium' ); ?>
						</a>
					<?php else : ?>
						<a href="<?php the_permalink(); ?>" class="em-card-image em-card-no-image">
							<span class="dashicons dashicons-calendar-alt"></span>
						</a>
					<?php endif; ?>

					<div class="em-card-body">
						<?php
						$event_types = get_the_terms( get_the_ID(), 'event_type' );
						if ( ! empty( $event_types ) && ! is_wp_error( $event_types ) ) :
						?>
							<div class="em-card-types">
								<?php foreach ( $event_types as $type ) : ?>
									<a href="<?php echo esc_url( get_term_link( $type ) ); ?>" class="em-type-badge">
										<?php echo esc_html( $type->name ); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<h3 class="em-card-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>

						<div class="em-card-meta">
							<?php $event_date = get_post_meta( get_the_ID(), '_event_manager_date', true ); ?>
							<?php if ( ! empty( $event_date ) ) : ?>
								<span class="em-card-date">
									<span class="dashicons dashicons-calendar-alt"></span>
									<?php
									$ts = strtotime( $event_date );
									echo esc_html( false !== $ts ? date_i18n( get_option( 'date_format' ), $ts ) : $event_date );
									?>
								</span>
							<?php endif; ?>

							<?php $event_location = get_post_meta( get_the_ID(), '_event_manager_location', true ); ?>
							<?php if ( ! empty( $event_location ) ) : ?>
								<span class="em-card-location">
									<span class="dashicons dashicons-location"></span>
									<?php echo esc_html( $event_location ); ?>
								</span>
							<?php endif; ?>
						</div>

						<div class="em-card-excerpt">
							<?php echo esc_html( wp_trim_words( get_the_excerpt(), 20, '...' ) ); ?>
						</div>

						<a href="<?php the_permalink(); ?>" class="em-btn em-btn-details">
							<?php esc_html_e( 'View Details', 'event-manager' ); ?>
						</a>
					</div>
				</div>

			<?php endwhile; ?>
		<?php else : ?>
			<p class="em-no-events"><?php esc_html_e( 'No events found matching your criteria.', 'event-manager' ); ?></p>
		<?php endif; ?>
	</div>

	<?php // Pagination. ?>
	<div class="em-pagination">
		<?php
		the_posts_pagination( array(
			'mid_size'  => 2,
			'prev_text' => __( '&laquo; Previous', 'event-manager' ),
			'next_text' => __( 'Next &raquo;', 'event-manager' ),
		) );
		?>
	</div>

</div>

<?php get_footer(); ?>
