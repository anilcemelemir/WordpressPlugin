<?php
/**
 * The core plugin class.
 *
 * Orchestrates the initialisation of custom post types, taxonomies,
 * and delegates to self-hooking modules for meta boxes, admin columns,
 * REST API, templates, enqueue, RSVP, shortcodes, filtering, caching,
 * and email notifications.
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
 * Class Event_Manager
 *
 * Main plugin orchestration class. Registers all WordPress hooks
 * needed for the Event CPT and Event Type taxonomy.
 *
 * @since 1.0.0
 */
class Event_Manager {

	/**
	 * Initialise the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Constructor — intentionally empty.
		// All hook registration happens in run().
	}

	/**
	 * Run the plugin — register all core hooks.
	 *
	 * Registers the custom post type and taxonomy on 'init'.
	 * Other modules (meta-boxes, admin-columns, rest-api, etc.) are
	 * self-hooking via add_action/add_filter in their own files.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function run(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Register the Event custom post type.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_post_types(): void {
		event_manager_register_event_post_type();
	}

	/**
	 * Register the Event Type custom taxonomy.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_taxonomies(): void {
		event_manager_register_event_type_taxonomy();
	}
}
