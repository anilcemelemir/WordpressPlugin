<?php
/**
 * Front-End Asset Enqueuing for Event Manager.
 *
 * Registers and enqueues CSS stylesheets and the Dashicons
 * library on event-related pages and when the shortcode is used.
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
 * Enqueue plugin styles and Dashicons on front-end event pages.
 *
 * Conditionally loads assets only on single event, event archive,
 * and event_type taxonomy pages to minimise impact on other pages.
 *
 * @since  1.0.0
 * @return void
 */
function event_manager_enqueue_assets() {

	// Load styles on event pages and when the shortcode might be present.
	if ( is_singular( 'event' ) || is_post_type_archive( 'event' ) || is_tax( 'event_type' ) ) {
		event_manager_enqueue_styles();
	}
}
add_action( 'wp_enqueue_scripts', 'event_manager_enqueue_assets' );

/**
 * Enqueue the main plugin stylesheet and Dashicons.
 *
 * Guards against double-enqueuing and is also called by
 * the shortcode renderer to ensure styles are available
 * when [event_list] is used outside event archive pages.
 *
 * @since  1.0.0
 * @return void
 */
function event_manager_enqueue_styles() {
	// Main stylesheet.
	if ( ! wp_style_is( 'event-manager-style', 'enqueued' ) ) {
		wp_enqueue_style(
			'event-manager-style',
			EVENT_MANAGER_PLUGIN_URL . 'assets/css/style.css',
			array(),
			EVENT_MANAGER_VERSION
		);
	}

	// Dashicons (needed for icons in templates).
	wp_enqueue_style( 'dashicons' );
}

/**
 * Ensure styles are loaded when the shortcode is rendered.
 *
 * Proxy function called by event_manager_shortcode_output()
 * to enqueue styles on any page containing the [event_list] shortcode.
 *
 * @since  1.0.0
 * @return void
 */
function event_manager_shortcode_enqueue_styles() {
	event_manager_enqueue_styles();
}
