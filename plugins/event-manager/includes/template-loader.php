<?php
/**
 * Custom Template Loader for the Event Post Type.
 *
 * Implements a template hierarchy that first checks the active
 * theme's event-manager/ directory, then falls back to the
 * plugin's templates/ directory. Ensures single-event.php and
 * archive-event.php render regardless of the active theme.
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
 * Load custom templates for the Event post type.
 *
 * Template priority: Active Theme > Plugin templates/ folder.
 * Hooked into 'template_include' filter.
 *
 * @since  1.0.0
 * @param  string $template The default template path selected by WordPress.
 * @return string The resolved template path (custom or original).
 */
function event_manager_template_loader( $template ) {

	if ( is_singular( 'event' ) ) {
		$custom = event_manager_locate_template( 'single-event.php' );
		if ( $custom ) {
			return $custom;
		}
	}

	if ( is_post_type_archive( 'event' ) || is_tax( 'event_type' ) ) {
		$custom = event_manager_locate_template( 'archive-event.php' );
		if ( $custom ) {
			return $custom;
		}
	}

	return $template;
}
add_filter( 'template_include', 'event_manager_template_loader' );

/**
 * Locate a template file in theme or plugin directories.
 *
 * Searches the following paths in order:
 * 1. {theme}/event-manager/{template_name}
 * 2. {plugin}/templates/{template_name}
 *
 * @since  1.0.0
 * @param  string $template_name The template file name (e.g., 'single-event.php').
 * @return string|false The full path to the located template, or false if not found.
 */
function event_manager_locate_template( $template_name ) {

	// 1. Check in the active theme: theme/event-manager/{template}.
	$theme_template = locate_template( 'event-manager/' . $template_name );
	if ( $theme_template ) {
		return $theme_template;
	}

	// 2. Fall back to plugin templates/ folder.
	$plugin_template = EVENT_MANAGER_PLUGIN_DIR . 'templates/' . $template_name;
	if ( file_exists( $plugin_template ) ) {
		return $plugin_template;
	}

	return false;
}
