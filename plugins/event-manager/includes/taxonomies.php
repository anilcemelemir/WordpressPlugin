<?php
/**
 * Register the Event Type custom taxonomy.
 *
 * Defines a hierarchical taxonomy for categorising events
 * into types such as Conference, Workshop, Webinar, etc.
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
 * Register the 'event_type' custom taxonomy.
 *
 * Creates a hierarchical (category-style) taxonomy linked
 * exclusively to the 'event' post type. Fully REST API enabled
 * and compatible with the block editor.
 *
 * @since  1.0.0
 * @return void
 * @see    register_taxonomy()
 */
function event_manager_register_event_type_taxonomy() {

	$labels = array(
		'name'                       => _x( 'Event Types', 'Taxonomy general name', 'event-manager' ),
		'singular_name'              => _x( 'Event Type', 'Taxonomy singular name', 'event-manager' ),
		'search_items'               => __( 'Search Event Types', 'event-manager' ),
		'popular_items'              => __( 'Popular Event Types', 'event-manager' ),
		'all_items'                  => __( 'All Event Types', 'event-manager' ),
		'parent_item'                => __( 'Parent Event Type', 'event-manager' ),
		'parent_item_colon'          => __( 'Parent Event Type:', 'event-manager' ),
		'edit_item'                  => __( 'Edit Event Type', 'event-manager' ),
		'view_item'                  => __( 'View Event Type', 'event-manager' ),
		'update_item'                => __( 'Update Event Type', 'event-manager' ),
		'add_new_item'               => __( 'Add New Event Type', 'event-manager' ),
		'new_item_name'              => __( 'New Event Type Name', 'event-manager' ),
		'separate_items_with_commas' => __( 'Separate event types with commas', 'event-manager' ),
		'add_or_remove_items'        => __( 'Add or remove event types', 'event-manager' ),
		'choose_from_most_used'      => __( 'Choose from the most used event types', 'event-manager' ),
		'not_found'                  => __( 'No event types found.', 'event-manager' ),
		'no_terms'                   => __( 'No event types', 'event-manager' ),
		'menu_name'                  => __( 'Event Types', 'event-manager' ),
		'items_list_navigation'      => __( 'Event Types list navigation', 'event-manager' ),
		'items_list'                 => __( 'Event Types list', 'event-manager' ),
		'back_to_items'              => __( '&larr; Back to Event Types', 'event-manager' ),
	);

	$args = array(
		'labels'            => $labels,
		'hierarchical'      => true,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'show_in_rest'      => true,
		'show_tagcloud'     => true,
		'rewrite'           => array( 'slug' => 'event-type' ),
	);

	register_taxonomy( 'event_type', array( 'event' ), $args );
}
