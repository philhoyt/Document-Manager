<?php
/**
 * Plugin capabilities.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Defines and manages the plugin's custom capabilities.
 */
class Capabilities {

	/**
	 * Hook into WordPress. No runtime hooks needed — caps are set at activation.
	 */
	public function register() {}

	/**
	 * Assign default capabilities on plugin activation.
	 */
	public function assign_default_caps() {
		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			$administrator->add_cap( 'manage_ph_documents' );
			$administrator->add_cap( 'upload_ph_documents' );
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( 'manage_ph_documents' );
			$editor->add_cap( 'upload_ph_documents' );
		}
	}

	/**
	 * Remove all plugin capabilities (called on uninstall).
	 */
	public function remove_caps() {
		$roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( 'manage_ph_documents' );
				$role->remove_cap( 'upload_ph_documents' );
			}
		}
	}
}
