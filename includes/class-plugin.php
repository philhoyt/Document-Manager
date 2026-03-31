<?php
/**
 * Plugin orchestrator.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Wires all service classes together and calls their register() methods.
 */
class Plugin {

	/**
	 * Register all plugin hooks and services.
	 */
	public function register() {
		( new Post_Type() )->register();
		( new Capabilities() )->register();
		( new Meta() )->register();
		( new Redirect() )->register();
		( new Sitemap() )->register();
		( new Settings() )->register();
		( new List_Table() )->register();
		( new Rest() )->register();
		( new Admin\Edit_Screen() )->register();
		( new Admin\Assets() )->register();
	}
}
