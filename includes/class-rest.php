<?php
/**
 * REST API field additions.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes computed fields for ph_document in the REST API,
 * enabling the core link picker to show useful context.
 */
class Rest {

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_fields' ) );
		add_filter( 'rest_prepare_ph_document', array( $this, 'add_computed_fields' ), 10, 3 );
	}

	/**
	 * Register REST fields for ph_document.
	 */
	public function register_fields() {
		register_rest_field(
			'ph_document',
			'destination_url',
			array(
				'get_callback'    => array( $this, 'get_destination_url' ),
				'update_callback' => null,
				'schema'          => array(
					'description' => __( 'The resolved destination URL for this document.', 'ph-document-manager' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * Get the resolved destination URL for a document.
	 *
	 * @param array $post Array of post data.
	 * @return string
	 */
	public function get_destination_url( $post ) {
		$post_id     = $post['id'];
		$source_type = get_post_meta( $post_id, '_ph_document_source_type', true );

		if ( 'file' === $source_type ) {
			$file_id = (int) get_post_meta( $post_id, '_ph_document_file_id', true );
			if ( $file_id > 0 ) {
				return (string) wp_get_attachment_url( $file_id );
			}
		}

		if ( 'url' === $source_type ) {
			return (string) get_post_meta( $post_id, '_ph_document_url', true );
		}

		return '';
	}

	/**
	 * Add source_type to REST response for the link picker context.
	 *
	 * @param \WP_REST_Response $response REST response.
	 * @param \WP_Post          $post     Post object.
	 * @param \WP_REST_Request  $request  Request object.
	 * @return \WP_REST_Response
	 */
	public function add_computed_fields( $response, $post, $request ) {
		$data                = $response->get_data();
		$data['source_type'] = get_post_meta( $post->ID, '_ph_document_source_type', true ) ?: 'file';
		$response->set_data( $data );
		return $response;
	}
}
