<?php
/**
 * Document save validation.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Server-side validation on save_post. Holds document at draft if required
 * destination is missing.
 */
class Validation {

	/**
	 * Nonce action name (shared with Edit_Screen).
	 */
	const NONCE_ACTION = 'ph_document_meta_nonce';

	/**
	 * Nonce field name.
	 */
	const NONCE_FIELD = 'ph_document_meta_nonce';

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'save_post_ph_document', array( $this, 'validate_on_save' ), 10, 3 );
		add_action( 'admin_post_ph_document_restore_revision', array( $this, 'handle_restore_revision' ) );
	}

	/**
	 * Validate the document destination on save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an update.
	 */
	public function validate_on_save( $post_id, $post, $update ) {
		// Skip auto-saves, AJAX, REST, and non-publish/update actions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Verify nonce.
		if (
			! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		// Only validate when publishing or updating a published post.
		if ( ! in_array( $post->post_status, array( 'publish', 'future', 'pending' ), true ) ) {
			return;
		}

		$source_type = isset( $_POST['_ph_document_source_type'] )
			? sanitize_text_field( wp_unslash( $_POST['_ph_document_source_type'] ) )
			: get_post_meta( $post_id, '_ph_document_source_type', true );

		$error = $this->get_validation_error( $post_id, $source_type );

		if ( ! $error ) {
			return;
		}

		// Force post back to draft without triggering this hook again.
		remove_action( 'save_post_ph_document', array( $this, 'validate_on_save' ), 10 );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		add_action( 'save_post_ph_document', array( $this, 'validate_on_save' ), 10, 3 );

		set_transient( 'ph_doc_validation_error_' . $post_id, $error, 45 );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post'   => $post_id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			)
		);
		exit;
	}

	/**
	 * Handle the restore revision admin-post action.
	 */
	public function handle_restore_revision() {
		if (
			! isset( $_POST['ph_restore_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ph_restore_nonce'] ) ), 'ph_document_restore_revision' )
		) {
			wp_die( esc_html__( 'Security check failed.', 'ph-document-manager' ) );
		}

		$post_id        = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$revision_index = isset( $_POST['revision_index'] ) ? absint( $_POST['revision_index'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'manage_ph_documents' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'ph-document-manager' ) );
		}

		$success = Revisions::restore_revision( $post_id, $revision_index );

		$redirect = add_query_arg(
			array(
				'post'           => $post_id,
				'action'         => 'edit',
				'ph_doc_restored' => $success ? '1' : '0',
			),
			admin_url( 'post.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Get the validation error message, or null if valid.
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $source_type 'file' or 'url'.
	 * @return string|null
	 */
	private function get_validation_error( $post_id, $source_type ) {
		// Nonce is verified in validate_on_save() before this method is called.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( 'file' === $source_type ) {
			$file_id = isset( $_POST['_ph_document_file_id'] )
				? absint( $_POST['_ph_document_file_id'] )
				: (int) get_post_meta( $post_id, '_ph_document_file_id', true );

			if ( $file_id <= 0 ) {
				return __( 'A file must be selected before this document can be published.', 'ph-document-manager' );
			}
		}

		if ( 'url' === $source_type ) {
			$url = isset( $_POST['_ph_document_url'] )
				? esc_url_raw( wp_unslash( $_POST['_ph_document_url'] ) )
				: get_post_meta( $post_id, '_ph_document_url', true );

			if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				return __( 'A valid URL must be entered before this document can be published.', 'ph-document-manager' );
			}
		}

		return null;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
