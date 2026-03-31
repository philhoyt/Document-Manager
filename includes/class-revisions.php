<?php
/**
 * Document revision management.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Static helpers for reading and writing document revision history.
 */
class Revisions {

	/**
	 * Get all revisions for a document.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_revisions( $post_id ) {
		$revisions = get_post_meta( $post_id, '_ph_document_revisions', true );
		return is_array( $revisions ) ? $revisions : array();
	}

	/**
	 * Push the current file attachment into revisions and set a new file.
	 *
	 * @param int    $post_id       Post ID.
	 * @param int    $attachment_id New attachment ID.
	 * @param string $label         Optional version label.
	 */
	public static function push_file_revision( $post_id, $attachment_id, $label = '' ) {
		$current_file_id = (int) get_post_meta( $post_id, '_ph_document_file_id', true );

		if ( $current_file_id > 0 ) {
			$revisions   = self::get_revisions( $post_id );
			$revisions[] = array(
				'attachment_id' => $current_file_id,
				'timestamp'     => time(),
				'user_id'       => get_current_user_id(),
				'label'         => sanitize_text_field( $label ),
			);
			update_post_meta( $post_id, '_ph_document_revisions', $revisions );
		}

		update_post_meta( $post_id, '_ph_document_file_id', $attachment_id );
	}

	/**
	 * Push the current URL into revisions and set a new URL.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $url     New destination URL.
	 */
	public static function push_url_revision( $post_id, $url ) {
		$current_url = get_post_meta( $post_id, '_ph_document_url', true );

		if ( ! empty( $current_url ) ) {
			$revisions   = self::get_revisions( $post_id );
			$revisions[] = array(
				'url'       => $current_url,
				'timestamp' => time(),
				'user_id'   => get_current_user_id(),
			);
			update_post_meta( $post_id, '_ph_document_revisions', $revisions );
		}

		update_post_meta( $post_id, '_ph_document_url', esc_url_raw( $url ) );
	}

	/**
	 * Restore a previous revision, pushing current into revisions.
	 *
	 * @param int $post_id        Post ID.
	 * @param int $revision_index Zero-based index into the revisions array.
	 * @return bool True on success, false if index is invalid.
	 */
	public static function restore_revision( $post_id, $revision_index ) {
		$revisions = self::get_revisions( $post_id );

		if ( ! isset( $revisions[ $revision_index ] ) ) {
			return false;
		}

		$to_restore = $revisions[ $revision_index ];

		// Remove the revision being restored from the array.
		array_splice( $revisions, $revision_index, 1 );

		if ( isset( $to_restore['attachment_id'] ) ) {
			// Restoring a file revision.
			$current_file_id = (int) get_post_meta( $post_id, '_ph_document_file_id', true );
			if ( $current_file_id > 0 ) {
				$revisions[] = array(
					'attachment_id' => $current_file_id,
					'timestamp'     => time(),
					'user_id'       => get_current_user_id(),
					'label'         => '',
				);
			}
			update_post_meta( $post_id, '_ph_document_file_id', $to_restore['attachment_id'] );
			update_post_meta( $post_id, '_ph_document_source_type', 'file' );
		} elseif ( isset( $to_restore['url'] ) ) {
			// Restoring a URL revision.
			$current_url = get_post_meta( $post_id, '_ph_document_url', true );
			if ( ! empty( $current_url ) ) {
				$revisions[] = array(
					'url'       => $current_url,
					'timestamp' => time(),
					'user_id'   => get_current_user_id(),
				);
			}
			update_post_meta( $post_id, '_ph_document_url', $to_restore['url'] );
			update_post_meta( $post_id, '_ph_document_source_type', 'url' );
		}

		update_post_meta( $post_id, '_ph_document_revisions', $revisions );

		// Create a WordPress post revision so history is trackable.
		wp_update_post( array( 'ID' => $post_id ) );

		return true;
	}
}
