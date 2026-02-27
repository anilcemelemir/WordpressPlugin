<?php
/**
 * PHPUnit bootstrap file for Event Manager plugin tests.
 *
 * Uses the WordPress test library (wp-phpunit).
 *
 * @package Event_Manager
 */

// Determine the path to the WordPress test suite.
// By default, looks for the WP_TESTS_DIR environment variable.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	// Fall back to /tmp/wordpress-tests-lib (standard for Docker/CI).
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Check if the test suite is installed.
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress test library at {$_tests_dir}.\n";
	echo "Set the WP_TESTS_DIR environment variable to point to it.\n";
	exit( 1 );
}

// Load the WordPress test functions.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin for testing.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/event-manager.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
