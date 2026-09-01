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
	 * ID of the document held back at draft during this request, or 0 if none.
	 *
	 * @var int
	 */
	private $held_post_id = 0;

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_filter( 'wp_insert_post_data', array( $this, 'hold_invalid_at_draft' ), 10, 2 );
		add_filter( 'redirect_post_location', array( $this, 'filter_redirect_location' ), 10, 2 );
		add_action( 'admin_post_ph_document_restore_revision', array( $this, 'handle_restore_revision' ) );
	}

	/**
	 * Hold a document at draft when it has no usable destination.
	 *
	 * Runs on wp_insert_post_data, before the row is written, so an invalid
	 * document is never published in the first place. An earlier version did
	 * this from save_post and then redirected and exited, which truncated the
	 * rest of wp_insert_post() — the generic save_post and wp_insert_post
	 * actions other plugins hook — along with wp_set_post_lock() and sticky
	 * handling in edit_post(). Correcting the status up front also avoids a
	 * second write, so other plugins' save handlers fire once and no spurious
	 * post revision is created.
	 *
	 * @param array $data    Sanitized post data headed for the database.
	 * @param array $postarr Raw post data as submitted.
	 * @return array
	 */
	public function hold_invalid_at_draft( $data, $postarr ) {
		// Skip anything that is not a classic-editor save of a document.
		if ( 'ph_document' !== $data['post_type'] ) {
			return $data;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $data;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $data;
		}

		// Only validate when publishing or scheduling.
		if ( ! in_array( $data['post_status'], array( 'publish', 'future', 'pending' ), true ) ) {
			return $data;
		}

		// Verify nonce.
		if (
			! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			return $data;
		}

		$post_id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;

		$source_type = isset( $_POST['_ph_document_source_type'] )
			? sanitize_text_field( wp_unslash( $_POST['_ph_document_source_type'] ) )
			: get_post_meta( $post_id, '_ph_document_source_type', true );

		$error = $this->get_validation_error( $post_id, $source_type );

		if ( ! $error ) {
			return $data;
		}

		$data['post_status'] = 'draft';
		$this->held_post_id  = $post_id;

		set_transient( 'ph_doc_validation_error_' . $post_id, $error, 45 );

		return $data;
	}

	/**
	 * Strip core's success message when a document was held back at draft.
	 *
	 * Core would otherwise append "Post published." to the redirect, directly
	 * contradicting the validation notice shown on the edit screen.
	 *
	 * @param string $location Redirect URL core chose.
	 * @param int    $post_id  Post ID being redirected to.
	 * @return string
	 */
	public function filter_redirect_location( $location, $post_id ) {
		if ( ! $this->held_post_id || (int) $post_id !== $this->held_post_id ) {
			return $location;
		}

		return remove_query_arg( 'message', $location );
	}

	/**
	 * Handle the restore revision admin-post action.
	 */
	public function handle_restore_revision() {
		$post_id        = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$revision_index = isset( $_GET['revision_index'] ) ? absint( $_GET['revision_index'] ) : 0;

		check_admin_referer( 'ph_document_restore_revision_' . $post_id . '_' . $revision_index );

		if ( ! $post_id || 'ph_document' !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'That document could not be found.', 'ph-document-manager' ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'ph-document-manager' ) );
		}

		$success = Revisions::restore_revision( $post_id, $revision_index );

		$redirect = add_query_arg(
			array(
				'post'            => $post_id,
				'action'          => 'edit',
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
		// Nonce is verified in hold_invalid_at_draft() before this method is called.
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
