<?php
/**
 * PHPUnit bootstrap file.
 *
 * Loads the WordPress test suite environment when running integration tests.
 * For unit tests (tests/phpunit/unit/), Brain Monkey mocks are used instead.
 *
 * @package PH\DocumentManager
 */

declare(strict_types=1);

$ph_wp_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $ph_wp_tests_dir ) {
	$ph_wp_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $ph_wp_tests_dir . '/includes/functions.php' ) ) {
	// Unit test suite — no WordPress bootstrap needed.
	// Brain Monkey handles WordPress function mocks in unit tests.
	return;
}

// Required by the WP Core test bootstrap — points to the installed polyfills library.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );

require_once $ph_wp_tests_dir . '/includes/functions.php';

/**
 * Load the plugin into the test WordPress install.
 *
 * @return void
 */
function ph_document_manager_manually_load_plugin() {
	$plugin_file = dirname( __DIR__, 2 ) . '/ph-document-manager.php';

	if ( file_exists( $plugin_file ) ) {
		require $plugin_file;
	}
}
tests_add_filter( 'muplugins_loaded', 'ph_document_manager_manually_load_plugin' );

require $ph_wp_tests_dir . '/includes/bootstrap.php';
