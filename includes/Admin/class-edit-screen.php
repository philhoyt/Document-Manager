<?php
/**
 * Edit screen meta boxes and save handler.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager\Admin;

defined( 'ABSPATH' ) || exit;

use PH\DocumentManager\Revisions;
use PH\DocumentManager\Validation;

/**
 * Registers meta boxes and handles their save/display logic.
 */
class Edit_Screen {

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'add_meta_boxes_ph_document', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_ph_document', array( $this, 'save_meta' ), 5, 2 );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
		add_action( 'edit_form_after_title', array( $this, 'render_source_type_toggle' ) );
	}

	/**
	 * Register meta boxes.
	 */
	public function register_meta_boxes() {
		add_meta_box(
			'ph-doc-file',
			__( 'File', 'ph-document-manager' ),
			array( $this, 'render_file_meta_box' ),
			'ph_document',
			'normal',
			'high'
		);

		add_meta_box(
			'ph-doc-url',
			__( 'Destination URL', 'ph-document-manager' ),
			array( $this, 'render_url_meta_box' ),
			'ph_document',
			'normal',
			'high'
		);

		add_meta_box(
			'ph-doc-revisions',
			__( 'Revision History', 'ph-document-manager' ),
			array( $this, 'render_revisions_meta_box' ),
			'ph_document',
			'normal',
			'default'
		);

		add_meta_box(
			'ph-doc-permalink',
			__( 'Document Link', 'ph-document-manager' ),
			array( $this, 'render_permalink_meta_box' ),
			'ph_document',
			'side',
			'high'
		);
	}

	/**
	 * Render the source type toggle above the title.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_source_type_toggle( $post ) {
		if ( 'ph_document' !== $post->post_type ) {
			return;
		}

		$source_type = get_post_meta( $post->ID, '_ph_document_source_type', true ) ?: 'file';

		wp_nonce_field( Validation::NONCE_ACTION, Validation::NONCE_FIELD );
		?>
		<div class="ph-doc-source-toggle" id="ph-doc-source-toggle">
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Document Type', 'ph-document-manager' ); ?></legend>
				<label>
					<input
						type="radio"
						name="_ph_document_source_type"
						value="file"
						<?php checked( $source_type, 'file' ); ?>
					/>
					<?php esc_html_e( 'File', 'ph-document-manager' ); ?>
				</label>
				<label>
					<input
						type="radio"
						name="_ph_document_source_type"
						value="url"
						<?php checked( $source_type, 'url' ); ?>
					/>
					<?php esc_html_e( 'URL', 'ph-document-manager' ); ?>
				</label>
			</fieldset>
		</div>
		<?php
	}

	/**
	 * Render the file meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_file_meta_box( $post ) {
		$source_type = get_post_meta( $post->ID, '_ph_document_source_type', true ) ?: 'file';
		$file_id     = (int) get_post_meta( $post->ID, '_ph_document_file_id', true );
		$file_name   = '';
		$file_size   = 0;
		$file_date   = '';

		if ( $file_id > 0 ) {
			$attached_file = get_attached_file( $file_id );
			$file_name     = $attached_file ? basename( $attached_file ) : '';
			$file_size     = $attached_file && file_exists( $attached_file ) ? filesize( $attached_file ) : 0;
			$post_obj      = get_post( $file_id );
			$file_date     = $post_obj
				? wp_date( get_option( 'date_format' ), strtotime( $post_obj->post_date ) )
				: '';
		}

		$wrap_style = ( 'url' === $source_type ) ? 'display:none;' : '';
		echo '<div class="ph-doc-file-wrap" id="ph-doc-file-wrap" style="' . esc_attr( $wrap_style ) . '">';
		require PH_DOCUMENT_MANAGER_DIR . 'templates/admin/meta-box-file.php';
		echo '</div>';
	}

	/**
	 * Render the URL meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_url_meta_box( $post ) {
		$source_type = get_post_meta( $post->ID, '_ph_document_source_type', true ) ?: 'file';
		$url         = get_post_meta( $post->ID, '_ph_document_url', true );

		$wrap_style = ( 'file' === $source_type ) ? 'display:none;' : '';
		echo '<div class="ph-doc-url-wrap" id="ph-doc-url-wrap" style="' . esc_attr( $wrap_style ) . '">';
		require PH_DOCUMENT_MANAGER_DIR . 'templates/admin/meta-box-url.php';
		echo '</div>';
	}

	/**
	 * Render the revision history meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_revisions_meta_box( $post ) {
		$revisions = Revisions::get_revisions( $post->ID );
		$post_id   = $post->ID;
		require PH_DOCUMENT_MANAGER_DIR . 'templates/admin/meta-box-revisions.php';
	}

	/**
	 * Render the permalink/copy link meta box.
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function render_permalink_meta_box( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p class="description">' . esc_html__( 'Save the document to generate a permalink.', 'ph-document-manager' ) . '</p>';
			return;
		}

		$permalink = get_permalink( $post->ID );
		?>
		<p>
			<a href="<?php echo esc_url( $permalink ); ?>" target="_blank" rel="noopener noreferrer" class="ph-doc-permalink-link">
				<?php echo esc_html( $permalink ); ?>
			</a>
		</p>
		<button
			type="button"
			id="ph-doc-copy-link"
			class="button"
			data-url="<?php echo esc_attr( $permalink ); ?>"
		><?php esc_html_e( 'Copy Link', 'ph-document-manager' ); ?></button>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function save_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if (
			! isset( $_POST[ Validation::NONCE_FIELD ] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ Validation::NONCE_FIELD ] ) ), Validation::NONCE_ACTION )
		) {
			return;
		}

		// Save source type.
		if ( isset( $_POST['_ph_document_source_type'] ) ) {
			$source_type = sanitize_text_field( wp_unslash( $_POST['_ph_document_source_type'] ) );
			$source_type = in_array( $source_type, array( 'file', 'url' ), true ) ? $source_type : 'file';
			update_post_meta( $post_id, '_ph_document_source_type', $source_type );
		}

		// Save file (push revision if changing).
		if ( isset( $_POST['_ph_document_file_id'] ) ) {
			$new_file_id     = absint( $_POST['_ph_document_file_id'] );
			$current_file_id = (int) get_post_meta( $post_id, '_ph_document_file_id', true );

			if ( $new_file_id > 0 && $new_file_id !== $current_file_id ) {
				Revisions::push_file_revision( $post_id, $new_file_id );
			} elseif ( 0 === $new_file_id ) {
				update_post_meta( $post_id, '_ph_document_file_id', 0 );
			}
		}

		// Save URL (push revision if changing).
		if ( isset( $_POST['_ph_document_url'] ) ) {
			$new_url     = esc_url_raw( wp_unslash( $_POST['_ph_document_url'] ) );
			$current_url = get_post_meta( $post_id, '_ph_document_url', true );

			if ( $new_url !== $current_url ) {
				Revisions::push_url_revision( $post_id, $new_url );
			}
		}
	}

	/**
	 * Display admin notices from transients.
	 */
	public function render_notices() {
		$screen = get_current_screen();
		if ( ! $screen || 'ph_document' !== $screen->post_type ) {
			return;
		}

		// Reading URL params for display only — no form processing, no nonce needed.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( ! $post_id ) {
			return;
		}

		// Validation error notice.
		$error = get_transient( 'ph_doc_validation_error_' . $post_id );
		if ( $error ) {
			delete_transient( 'ph_doc_validation_error_' . $post_id );
			printf(
				'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'Document not published.', 'ph-document-manager' ),
				esc_html( $error )
			);
		}

		// Restore success notice.
		if ( isset( $_GET['ph_doc_restored'] ) ) {
			if ( '1' === $_GET['ph_doc_restored'] ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Revision restored successfully.', 'ph-document-manager' ) . '</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Could not restore that revision.', 'ph-document-manager' ) . '</p></div>';
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}
}
