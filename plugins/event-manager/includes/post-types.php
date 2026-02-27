<?php
/**
 * Register the Event custom post type.
 *
 * Defines the 'event' CPT with REST API support, archive pages,
 * Gutenberg compatibility, and a comprehensive label set.
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
 * Register the 'event' custom post type.
 *
 * Configures a fully public post type with:
 * - REST API enabled (rest_base: 'event')
 * - Archive page at /event/
 * - Support for title, editor, author, thumbnail, excerpt, comments
 * - Block editor (Gutenberg) compatibility
 *
 * @since  1.0.0
 * @return void
 * @see    register_post_type()
 */
function event_manager_register_event_post_type() {

	$labels = array(
		'name'                  => _x( 'Events', 'Post type general name', 'event-manager' ),
		'singular_name'         => _x( 'Event', 'Post type singular name', 'event-manager' ),
		'menu_name'             => _x( 'Events', 'Admin Menu text', 'event-manager' ),
		'name_admin_bar'        => _x( 'Event', 'Add New on Toolbar', 'event-manager' ),
		'add_new'               => __( 'Add New', 'event-manager' ),
		'add_new_item'          => __( 'Add New Event', 'event-manager' ),
		'new_item'              => __( 'New Event', 'event-manager' ),
		'edit_item'             => __( 'Edit Event', 'event-manager' ),
		'view_item'             => __( 'View Event', 'event-manager' ),
		'all_items'             => __( 'All Events', 'event-manager' ),
		'search_items'          => __( 'Search Events', 'event-manager' ),
		'parent_item_colon'     => __( 'Parent Events:', 'event-manager' ),
		'not_found'             => __( 'No events found.', 'event-manager' ),
		'not_found_in_trash'    => __( 'No events found in Trash.', 'event-manager' ),
		'featured_image'        => _x( 'Event Cover Image', 'Overrides the "Featured Image" phrase', 'event-manager' ),
		'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'event-manager' ),
		'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'event-manager' ),
		'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'event-manager' ),
		'archives'              => _x( 'Event archives', 'The post type archive label', 'event-manager' ),
		'insert_into_item'      => _x( 'Insert into event', 'Overrides the "Insert into post" phrase', 'event-manager' ),
		'uploaded_to_this_item' => _x( 'Uploaded to this event', 'Overrides the "Uploaded to this post" phrase', 'event-manager' ),
		'filter_items_list'     => _x( 'Filter events list', 'Screen reader text', 'event-manager' ),
		'items_list_navigation' => _x( 'Events list navigation', 'Screen reader text', 'event-manager' ),
		'items_list'            => _x( 'Events list', 'Screen reader text', 'event-manager' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'rest_base'          => 'event',
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'event' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 20,
		'menu_icon'          => 'dashicons-calendar-alt',
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
	);

	register_post_type( 'event', $args );
}
