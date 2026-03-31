<?php
/**
 * Permalink redirect handler.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Intercepts ph_document permalink requests and 302-redirects to the destination.
 */
class Redirect {

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'template_redirect', array( $this, 'handle_redirect' ) );
	}

	/**
	 * Handle the redirect for ph_document posts.
	 */
	public function handle_redirect() {
		if ( ! is_singular( 'ph_document' ) ) {
			return;
		}

		$post_id     = get_the_ID();
		$source_type = get_post_meta( $post_id, '_ph_document_source_type', true );
		$destination = $this->resolve_destination( $post_id, $source_type );

		// Always send noindex/nofollow on document permalink responses.
		header( 'X-Robots-Tag: noindex, nofollow' );

		if ( $destination ) {
			// External redirects are intentional — this plugin's purpose is to redirect
			// to user-configured URLs and media files which may be on external domains.
			wp_redirect( $destination, 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}

		// No valid destination — use fallback or 404.
		$fallback = get_option( Settings::OPTION_FALLBACK, '' );
		if ( $fallback ) {
			wp_redirect( esc_url_raw( $fallback ), 302 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}

		wp_die(
			esc_html__( 'This document has no destination configured.', 'ph-document-manager' ),
			esc_html__( 'Document Not Found', 'ph-document-manager' ),
			array( 'response' => 404 )
		);
	}

	/**
	 * Resolve the redirect destination URL for a document.
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $source_type 'file' or 'url'.
	 * @return string|false Destination URL or false if unresolvable.
	 */
	private function resolve_destination( $post_id, $source_type ) {
		if ( 'file' === $source_type ) {
			$file_id = (int) get_post_meta( $post_id, '_ph_document_file_id', true );
			if ( $file_id > 0 ) {
				$url = wp_get_attachment_url( $file_id );
				return $url ?: false;
			}
			return false;
		}

		if ( 'url' === $source_type ) {
			$url = get_post_meta( $post_id, '_ph_document_url', true );
			return ! empty( $url ) ? $url : false;
		}

		return false;
	}
}
