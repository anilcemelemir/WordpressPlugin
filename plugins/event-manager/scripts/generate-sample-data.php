<?php
/**
 * Sample Data Generator for Event Manager Plugin
 *
 * Usage (via WP-CLI) also included in the README:
 * docker-compose run --rm wpcli eval-file /var/www/html/wp-content/plugins/event-manager/scripts/generate-sample-data.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prevent accidental double-run.
$existing = get_posts( array(
    'post_type'      => 'event',
    'posts_per_page' => 1,
    'meta_key'       => '_event_manager_sample_data',
    'meta_value'     => '1',
    'fields'         => 'ids',
) );

if ( ! empty( $existing ) ) {
    WP_CLI::warning( 'Sample data already exists. Delete existing sample events before re-running.' );
    return;
}

WP_CLI::log( '🚀 Generating sample data from XML source for Event Manager...' );

// ---------------------------------------------------------------------------
// 1. Create Event Type Terms
// ---------------------------------------------------------------------------
$event_types = array(
    'Conference' => 'Large multi-day professional gatherings with speakers and networking.',
    'Workshop'   => 'Hands-on, interactive sessions focused on skill-building.',
    'Webinar'    => 'Online presentations and discussions accessible remotely.',
    'Meetup'     => 'Informal local gatherings for community networking.',
    'Hackathon'  => 'Intensive coding events to build projects in a short timeframe.',
    'Networking' => 'Events focused on professional relationship building.',
    'Seminer'    => 'Educational sessions led by experts.'
);

$term_ids = array();

foreach ( $event_types as $name => $description ) {
    $term = term_exists( $name, 'event_type' );
    if ( ! $term ) {
        $term = wp_insert_term( $name, 'event_type', array(
            'description' => $description,
        ) );
        if ( is_wp_error( $term ) ) {
            continue;
        }
        WP_CLI::success( "Created event type: {$name}" );
    }
    $term_ids[ $name ] = is_array( $term ) ? $term['term_id'] : $term;
}

// ---------------------------------------------------------------------------
// 2. Create Sample Events
// ---------------------------------------------------------------------------
$events = array(
    array(
        'title'    => 'Advanced PHP Workshop',
        'content'  => 'A deep-dive into modern PHP development.',
        'date'     => '2026-03-13',
        'location' => 'İstanbul, Türkiye',
        'type'     => 'Workshop',
    ),
    array(
        'title'    => 'Gutenberg Block Development Webinar',
        'content'  => 'Learn how to create custom Gutenberg blocks from scratch.',
        'date'     => '2026-03-06',
        'location' => 'Online (Microsoft Teams)',
        'type'     => 'Webinar',
    ),
    array(
        'title'    => 'WordPress Developer Meetup',
        'content'  => 'Monthly gathering of WordPress developers in the downtown area.',
        'date'     => '2026-03-04',
        'location' => 'Some Coffee Shop, Türkiye',
        'type'     => 'Meetup',
    ),
    array(
        'title'    => 'Plugin Hackathon 2025',
        'content'  => '48-hour hackathon.',
        'date'     => '2026-04-13',
        'location' => 'Hottest Place in Earth, Adana, Türkiye',
        'type'     => 'Hackathon',
    ),
    array(
        'title'    => 'REST API Masterclass',
        'content'  => 'Detailed REST API Course.',
        'date'     => '2026-03-20',
        'location' => 'İstanbul, Türkiye',
        'type'     => 'Workshop',
    ),
    array(
        'title'    => 'WordPress Security Best Practices',
        'content'  => 'Online webinar covering essential security practices.',
        'date'     => '2026-03-09',
        'location' => 'Adana, Türkiye',
        'type'     => 'Webinar',
    )
);

$created_count = 0;

foreach ( $events as $event_data ) {
    $post_id = wp_insert_post( array(
        'post_title'   => $event_data['title'],
        'post_content' => $event_data['content'],
        'post_type'    => 'event',
        'post_status'  => 'publish',
    ) );

    if ( is_wp_error( $post_id ) ) {
        WP_CLI::warning( "Failed to create event '{$event_data['title']}': " . $post_id->get_error_message() );
        continue;
    }

    update_post_meta( $post_id, '_event_manager_date', $event_data['date'] );
    update_post_meta( $post_id, '_event_manager_location', $event_data['location'] );
    update_post_meta( $post_id, '_event_manager_sample_data', '1' );

    // Assign event type
    if ( isset( $term_ids[ $event_data['type'] ] ) ) {
        wp_set_object_terms( $post_id, (int) $term_ids[ $event_data['type'] ], 'event_type' );
    }

    WP_CLI::success( "Created event: {$event_data['title']} (Location: {$event_data['location']})" );
    $created_count++;
}

WP_CLI::log( '' );
WP_CLI::success( "✅ XML-based sample data generation complete! Created {$created_count} events." );