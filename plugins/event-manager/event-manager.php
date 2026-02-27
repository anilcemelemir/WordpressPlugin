<?php
/**
 * Plugin Name:       Event Manager
 * Plugin URI:        https://example.com/event-manager
 * Description:       A comprehensive plugin to manage events with custom post types, taxonomies, meta boxes, REST API, email notifications, RSVP, and transient caching.
 * Version:           1.1.0
 * Author:            Developer
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       event-manager
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      7.4
 *
 * @package    Event_Manager
 * @author     Developer
 * @copyright  2026 Developer
 * @license    GPL-2.0+
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version.
 *
 * @since 1.0.0
 * @var string
 */
define( 'EVENT_MANAGER_VERSION', '1.1.0' );

/**
 * Absolute path to the plugin directory (with trailing slash).
 *
 * @since 1.0.0
 * @var string
 */
define( 'EVENT_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * URL to the plugin directory (with trailing slash).
 *
 * @since 1.0.0
 * @var string
 */
define( 'EVENT_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin text domain for i18n.
 *
 * @since 1.0.0
 * @var string
 */
define( 'EVENT_MANAGER_TEXT_DOMAIN', 'event-manager' );

/**
 * Load plugin textdomain for translations.
 *
 * Loads translation files from the plugin's /languages/ directory.
 * Hooked into 'init' to ensure WordPress locale functions are available.
 *
 * @since  1.0.0
 * @return void
 */
function event_manager_load_textdomain() {
	load_plugin_textdomain(
		'event-manager',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'event_manager_load_textdomain' );

/**
 * Include required plugin files.
 *
 * Files are loaded in dependency order: core class first, then
 * CPT/taxonomy registration, admin features, front-end features,
 * caching, and notifications.
 *
 * @since 1.0.0
 * @since 1.1.0 Added cache.php and notifications.php includes.
 */
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/class-event-manager.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/post-types.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/taxonomies.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/meta-boxes.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/admin-columns.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/rest-api.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/template-loader.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/enqueue.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/rsvp.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/shortcode.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/filtering.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/cache.php';
require_once EVENT_MANAGER_PLUGIN_DIR . 'includes/notifications.php';

/**
 * Initialise and run the plugin.
 *
 * Instantiates the core Event_Manager class and triggers
 * the registration of all hooks and WordPress integrations.
 *
 * @since  1.0.0
 * @return void
 */
function event_manager_run() {
	$plugin = new Event_Manager();
	$plugin->run();
}
event_manager_run();
