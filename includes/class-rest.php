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
		add_filter( 'rest_post_search_query', array( $this, 'filter_search_query' ) );
	}

	/**
	 * Hide documents from the core REST search endpoint for anonymous visitors.
	 *
	 * WP_REST_Post_Search_Handler builds its subtypes from every post type
	 * registered 'public' => true and does not consult exclude_from_search, so
	 * making documents link-picker discoverable also exposes them at
	 * /wp/v2/search, which is readable without authentication. Without this,
	 * gating Rest_Documents_Controller would just move enumeration one route
	 * over. Users who can edit content still see documents, so the link picker
	 * keeps working.
	 *
	 * @param array $args WP_Query arguments for the search.
	 * @return array
	 */
	public function filter_search_query( $args ) {
		if ( current_user_can( 'edit_posts' ) ) {
			return $args;
		}

		if ( empty( $args['post_type'] ) ) {
			return $args;
		}

		$types = array_values( array_diff( (array) $args['post_type'], array( 'ph_document' ) ) );

		if ( $types ) {
			$args['post_type'] = $types;
			return $args;
		}

		// Documents were the only type requested. Force an empty result set
		// rather than letting WP_Query fall back to its default post type.
		$args['post__in'] = array( 0 );

		return $args;
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
		$data['source_type'] = Meta::get_source_type( $post->ID );
		$response->set_data( $data );
		return $response;
	}
}
