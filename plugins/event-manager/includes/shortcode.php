<?php
/**
 * Event List Shortcode for Event Manager.
 *
 * Renders a responsive, styled grid of events with optional
 * filtering and transient-cached queries for performance.
 *
 * Attributes:
 *   limit        — Number of events to show (default: 12).
 *   type         — Event Type taxonomy slug to filter by.
 *   date_from    — Start date filter (YYYY-MM-DD).
 *   date_to      — End date filter (YYYY-MM-DD).
 *   search       — Search keyword for title/content.
 *   columns      — Grid columns 1–4 (default: 3).
 *   show_filters — Show filter form above grid (default: false).
 *
 * Usage:
 *   [event_list]
 *   [event_list limit="6" type="conference"]
 *   [event_list show_filters="true" limit="9"]
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
 * Register the [event_list] shortcode.
 *
 * @since  1.0.0
 * @return void
 */
function event_manager_register_shortcode() {
	add_shortcode( 'event_list', 'event_manager_shortcode_output' );
}
add_action( 'init', 'event_manager_register_shortcode' );

/**
 * Render the [event_list] shortcode output.
 *
 * Builds a WP_Query with the provided attributes, caches the
 * complete HTML output using the Transients API, and returns
 * a responsive card grid of matching events.
 *
 * @since  1.0.0
 * @since  1.1.0 Added transient caching for query results.
 * @param  array|string $atts Shortcode attributes from the post content.
 * @return string Rendered HTML output.
 */
function event_manager_shortcode_output( $atts ) {

	$atts = shortcode_atts( array(
		'limit'        => 12,
		'type'         => '',
		'date_from'    => '',
		'date_to'      => '',
		'search'       => '',
		'columns'      => 3,
		'show_filters' => 'false',
	), $atts, 'event_list' );

	// Sanitize attributes.
	$limit        = absint( $atts['limit'] );
	$type         = sanitize_text_field( $atts['type'] );
	$date_from    = sanitize_text_field( $atts['date_from'] );
	$date_to      = sanitize_text_field( $atts['date_to'] );
	$search       = sanitize_text_field( $atts['search'] );
	$columns      = absint( $atts['columns'] );
	$show_filters = filter_var( $atts['show_filters'], FILTER_VALIDATE_BOOLEAN );

	// Allow GET overrides when filters are shown.
	if ( $show_filters ) {
		if ( isset( $_GET['em_sc_type'] ) ) {
			$type = sanitize_text_field( wp_unslash( $_GET['em_sc_type'] ) );
		}
		if ( isset( $_GET['em_sc_from'] ) ) {
			$date_from = sanitize_text_field( wp_unslash( $_GET['em_sc_from'] ) );
		}
		if ( isset( $_GET['em_sc_to'] ) ) {
			$date_to = sanitize_text_field( wp_unslash( $_GET['em_sc_to'] ) );
		}
		if ( isset( $_GET['em_sc_search'] ) ) {
			$search = sanitize_text_field( wp_unslash( $_GET['em_sc_search'] ) );
		}
	}

	// Build query args.
	$query_args = array(
		'post_type'      => 'event',
		'posts_per_page' => $limit ? $limit : 12,
		'post_status'    => 'publish',
		'orderby'        => 'meta_value',
		'meta_key'       => '_event_manager_date',
		'order'          => 'ASC',
	);

	// Taxonomy filter.
	if ( ! empty( $type ) ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'event_type',
				'field'    => 'slug',
				'terms'    => $type,
			),
		);
	}

	// Meta query for date range.
	$meta_query = array();
	if ( ! empty( $date_from ) ) {
		$meta_query[] = array(
			'key'     => '_event_manager_date',
			'value'   => $date_from,
			'compare' => '>=',
			'type'    => 'DATE',
		);
	}
	if ( ! empty( $date_to ) ) {
		$meta_query[] = array(
			'key'     => '_event_manager_date',
			'value'   => $date_to,
			'compare' => '<=',
			'type'    => 'DATE',
		);
	}
	if ( ! empty( $meta_query ) ) {
		$meta_query['relation']    = 'AND';
		$query_args['meta_query']  = isset( $query_args['meta_query'] ) ? array_merge( $query_args['meta_query'], $meta_query ) : $meta_query;
	}

	// Search filter.
	if ( ! empty( $search ) ) {
		$query_args['s'] = $search;
	}

	// Enqueue styles when shortcode is used.
	event_manager_shortcode_enqueue_styles();

	// --- Transient Cache: check for cached output ---
	$cache_key    = event_manager_cache_key( 'sc', $query_args );
	$cached_html  = event_manager_cache_get( $cache_key );

	if ( false !== $cached_html ) {
		return $cached_html;
	}

	$events = new WP_Query( $query_args );

	ob_start();
	?>

	<div class="em-shortcode-wrap" data-columns="<?php echo esc_attr( $columns ); ?>">

		<?php if ( $show_filters ) : ?>
			<div class="em-filters em-sc-filters">
				<form method="get" class="em-filter-form">

					<div class="em-filter-row">
						<div class="em-filter-field">
							<label for="em-sc-search"><?php esc_html_e( 'Search', 'event-manager' ); ?></label>
							<input type="text" id="em-sc-search" name="em_sc_search"
								value="<?php echo esc_attr( $search ); ?>"
								placeholder="<?php esc_attr_e( 'Search events...', 'event-manager' ); ?>" />
						</div>

						<div class="em-filter-field">
							<label for="em-sc-type"><?php esc_html_e( 'Event Type', 'event-manager' ); ?></label>
							<?php
							wp_dropdown_categories( array(
								'taxonomy'        => 'event_type',
								'name'            => 'em_sc_type',
								'id'              => 'em-sc-type',
								'show_option_all' => __( 'All Types', 'event-manager' ),
								'value_field'     => 'slug',
								'selected'        => $type,
								'hide_empty'      => false,
							) );
							?>
						</div>

						<div class="em-filter-field">
							<label for="em-sc-from"><?php esc_html_e( 'From', 'event-manager' ); ?></label>
							<input type="date" id="em-sc-from" name="em_sc_from" value="<?php echo esc_attr( $date_from ); ?>" />
						</div>

						<div class="em-filter-field">
							<label for="em-sc-to"><?php esc_html_e( 'To', 'event-manager' ); ?></label>
							<input type="date" id="em-sc-to" name="em_sc_to" value="<?php echo esc_attr( $date_to ); ?>" />
						</div>
					</div>

					<div class="em-filter-actions">
						<button type="submit" class="em-btn em-btn-filter"><?php esc_html_e( 'Filter', 'event-manager' ); ?></button>
					</div>

				</form>
			</div>
		<?php endif; ?>

		<div class="em-event-grid em-grid-cols-<?php echo esc_attr( $columns ); ?>">
			<?php if ( $events->have_posts() ) : ?>
				<?php while ( $events->have_posts() ) : $events->the_post(); ?>

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
									<?php foreach ( $event_types as $et ) : ?>
										<span class="em-type-badge"><?php echo esc_html( $et->name ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<h3 class="em-card-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>

							<div class="em-card-meta">
								<?php $ev_date = get_post_meta( get_the_ID(), '_event_manager_date', true ); ?>
								<?php if ( ! empty( $ev_date ) ) : ?>
									<span class="em-card-date">
										<span class="dashicons dashicons-calendar-alt"></span>
										<?php
										$ts = strtotime( $ev_date );
										echo esc_html( false !== $ts ? date_i18n( get_option( 'date_format' ), $ts ) : $ev_date );
										?>
									</span>
								<?php endif; ?>

								<?php $ev_loc = get_post_meta( get_the_ID(), '_event_manager_location', true ); ?>
								<?php if ( ! empty( $ev_loc ) ) : ?>
									<span class="em-card-location">
										<span class="dashicons dashicons-location"></span>
										<?php echo esc_html( $ev_loc ); ?>
									</span>
								<?php endif; ?>
							</div>

							<div class="em-card-excerpt">
								<?php echo esc_html( wp_trim_words( get_the_excerpt(), 15, '...' ) ); ?>
							</div>

							<a href="<?php the_permalink(); ?>" class="em-btn em-btn-details">
								<?php esc_html_e( 'View Details', 'event-manager' ); ?>
							</a>
						</div>
					</div>

				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			<?php else : ?>
				<p class="em-no-events"><?php esc_html_e( 'No events found.', 'event-manager' ); ?></p>
			<?php endif; ?>
		</div>

	</div>

	<?php
	$output = ob_get_clean();

	// --- Transient Cache: store rendered output ---
	event_manager_cache_set( $cache_key, $output, EVENT_MANAGER_CACHE_TTL );

	return $output;
}
