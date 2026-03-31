<?php
/**
 * Admin asset enqueueing.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues admin scripts and styles for Document Manager screens.
 */
class Assets {

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue assets on the appropriate admin screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue( $hook_suffix ) {
		$screen = get_current_screen();
		if ( ! $screen || 'ph_document' !== $screen->post_type ) {
			return;
		}

		if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			$this->enqueue_edit_screen();
		}

		if ( 'edit.php' === $hook_suffix ) {
			$this->enqueue_list_table();
		}
	}

	/**
	 * Enqueue assets for the edit screen.
	 */
	private function enqueue_edit_screen() {
		$asset_file = PH_DOCUMENT_MANAGER_DIR . 'build/edit-screen.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array( 'dependencies' => array(), 'version' => PH_DOCUMENT_MANAGER_VERSION );

		// Ensure wp-media is available for the wp.media frame.
		$dependencies = array_unique( array_merge( $asset['dependencies'], array( 'wp-api-fetch' ) ) );

		wp_enqueue_media();

		wp_enqueue_script(
			'ph-doc-edit-screen',
			PH_DOCUMENT_MANAGER_URL . 'build/edit-screen.js',
			$dependencies,
			$asset['version'],
			true
		);

		wp_localize_script(
			'ph-doc-edit-screen',
			'phDocumentManager',
			array(
				'mediaTitle'  => __( 'Select or Upload Document', 'ph-document-manager' ),
				'mediaButton' => __( 'Use This File', 'ph-document-manager' ),
				'copiedText'  => __( 'Copied!', 'ph-document-manager' ),
				'copyText'    => __( 'Copy Link', 'ph-document-manager' ),
				'removeText'  => __( 'Remove', 'ph-document-manager' ),
				'noFileText'  => __( 'No file selected.', 'ph-document-manager' ),
			)
		);

		if ( file_exists( PH_DOCUMENT_MANAGER_DIR . 'build/edit-screen.css' ) ) {
			wp_enqueue_style(
				'ph-doc-edit-screen',
				PH_DOCUMENT_MANAGER_URL . 'build/edit-screen.css',
				array(),
				$asset['version']
			);
		}
	}

	/**
	 * Enqueue assets for the list table.
	 */
	private function enqueue_list_table() {
		$asset_file = PH_DOCUMENT_MANAGER_DIR . 'build/list-table.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array( 'dependencies' => array(), 'version' => PH_DOCUMENT_MANAGER_VERSION );

		if ( file_exists( PH_DOCUMENT_MANAGER_DIR . 'build/list-table.css' ) ) {
			wp_enqueue_style(
				'ph-doc-list-table',
				PH_DOCUMENT_MANAGER_URL . 'build/list-table.css',
				array(),
				$asset['version']
			);
		}
	}
}
