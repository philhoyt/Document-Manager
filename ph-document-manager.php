<?php
/**
 * Plugin Name: Document Manager
 * Plugin URI:  https://github.com/philhoyt/ph-document-manager
 * Description: Manage files and URLs as stable permalink redirects discoverable via the core link picker.
 * Version:     1.0.0
 * Author:      Phil Hoyt
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ph-document-manager
 *
 * @package PH\DocumentManager
 */

defined( 'ABSPATH' ) || exit;

define( 'PH_DOCUMENT_MANAGER_VERSION', '1.0.0' );
define( 'PH_DOCUMENT_MANAGER_DIR', plugin_dir_path( __FILE__ ) );
define( 'PH_DOCUMENT_MANAGER_URL', plugin_dir_url( __FILE__ ) );

require_once PH_DOCUMENT_MANAGER_DIR . 'vendor/autoload.php';

// Activation / deactivation hooks.
register_activation_hook( __FILE__, 'ph_document_manager_activate' );
register_deactivation_hook( __FILE__, 'ph_document_manager_deactivate' );

/**
 * Plugin activation.
 */
function ph_document_manager_activate() {
	$capabilities = new PH\DocumentManager\Capabilities();
	$capabilities->assign_default_caps();

	$post_type = new PH\DocumentManager\Post_Type();
	$post_type->register_post_type();
	$post_type->register_taxonomy();

	flush_rewrite_rules();
}

/**
 * Plugin deactivation.
 */
function ph_document_manager_deactivate() {
	flush_rewrite_rules();
}

// Boot the plugin.
add_action(
	'plugins_loaded',
	function () {
		$plugin = new PH\DocumentManager\Plugin();
		$plugin->register();
	}
);
