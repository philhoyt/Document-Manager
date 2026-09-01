<?php
/**
 * REST controller for the ph_document post type.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Restricts ph_document REST reads to users who can edit content.
 *
 * The post type is registered 'public' => true so the core link picker can
 * find it — both wp_link_query() and WP_REST_Post_Search_Handler only consider
 * public post types. That also makes /wp/v2/documents readable by anyone,
 * because WP_REST_Posts_Controller::check_read_permission() lets any published
 * post through regardless of the post type's public flag.
 *
 * Documents are meant to be followed by permalink, not enumerated, so gate the
 * collection here. Permalinks stay public: Redirect::handle_redirect() is
 * unaffected, and sharing a document URL still works for logged-out visitors.
 *
 * The gate is 'edit_posts' rather than 'manage_ph_documents' so that any user
 * who can write content can insert a document link, not just the roles that
 * administer documents.
 */
class Rest_Documents_Controller extends \WP_REST_Posts_Controller {

	/**
	 * Require an editing capability before listing documents.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has read access, WP_Error otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$allowed = $this->check_browse_capability();

		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		return parent::get_items_permissions_check( $request );
	}

	/**
	 * Require an editing capability before reading a single document.
	 *
	 * Gating the single-item route too, otherwise the collection restriction is
	 * trivially bypassed by walking post IDs.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return true|\WP_Error True if the request has read access, WP_Error otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$allowed = $this->check_browse_capability();

		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		return parent::get_item_permissions_check( $request );
	}

	/**
	 * Capability gate shared by both read checks.
	 *
	 * @return true|\WP_Error
	 */
	private function check_browse_capability() {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new \WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to browse documents.', 'ph-document-manager' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}
}
