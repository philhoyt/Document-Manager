<?php
/**
 * Post meta registration.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Registers post meta for ph_document posts.
 */
class Meta {

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_meta' ) );
	}

	/**
	 * Register all document meta keys.
	 */
	public function register_post_meta() {
		register_post_meta(
			'ph_document',
			'_ph_document_source_type',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'file',
				'show_in_rest'      => array(
					'name' => 'source_type',
				),
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => array( $this, 'sanitize_source_type' ),
			)
		);

		register_post_meta(
			'ph_document',
			'_ph_document_file_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => array(
					'name' => 'file_id',
				),
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => 'absint',
			)
		);

		register_post_meta(
			'ph_document',
			'_ph_document_url',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => array(
					'name' => 'document_url',
				),
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => 'esc_url_raw',
			)
		);

		register_post_meta(
			'ph_document',
			'_ph_document_revisions',
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'show_in_rest'      => false,
				'auth_callback'     => array( $this, 'auth_callback' ),
				'sanitize_callback' => array( $this, 'sanitize_revisions' ),
			)
		);
	}

	/**
	 * Auth callback — requires manage_ph_documents capability.
	 *
	 * @return bool
	 */
	public function auth_callback() {
		return current_user_can( 'manage_ph_documents' );
	}

	/**
	 * Sanitize source type — must be 'file' or 'url'.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_source_type( $value ) {
		return in_array( $value, array( 'file', 'url' ), true ) ? $value : 'file';
	}

	/**
	 * Sanitize revisions array structure.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	public function sanitize_revisions( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $value as $revision ) {
			if ( ! is_array( $revision ) ) {
				continue;
			}
			if ( isset( $revision['attachment_id'] ) ) {
				$sanitized[] = array(
					'attachment_id' => absint( $revision['attachment_id'] ),
					'timestamp'     => absint( $revision['timestamp'] ?? 0 ),
					'user_id'       => absint( $revision['user_id'] ?? 0 ),
					'label'         => sanitize_text_field( $revision['label'] ?? '' ),
				);
			} elseif ( isset( $revision['url'] ) ) {
				$sanitized[] = array(
					'url'       => esc_url_raw( $revision['url'] ),
					'timestamp' => absint( $revision['timestamp'] ?? 0 ),
					'user_id'   => absint( $revision['user_id'] ?? 0 ),
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Get a document's source type, falling back to 'file'.
	 *
	 * The meta row is absent on documents created before a type was chosen, and
	 * five callers were each applying their own default. Keep it in one place.
	 *
	 * @param int $post_id Post ID.
	 * @return string Either 'file' or 'url'.
	 */
	public static function get_source_type( $post_id ) {
		$type = get_post_meta( $post_id, '_ph_document_source_type', true );

		return ( 'url' === $type ) ? 'url' : 'file';
	}
}
