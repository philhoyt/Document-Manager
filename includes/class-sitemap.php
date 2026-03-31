<?php
/**
 * Sitemap exclusion filters.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Excludes ph_document from all sitemap implementations.
 */
class Sitemap {

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		// WordPress core sitemaps.
		add_filter( 'wp_sitemaps_post_types', array( $this, 'exclude_from_core_sitemap' ) );

		// Yoast SEO.
		add_filter( 'wpseo_sitemap_exclude_post_type', array( $this, 'exclude_from_yoast' ), 10, 2 );

		// RankMath.
		add_filter( 'rank_math_sitemap_entry', array( $this, 'exclude_from_rankmath' ), 10, 3 );
	}

	/**
	 * Exclude ph_document from WordPress core sitemaps.
	 *
	 * @param array $post_types Registered post types for sitemap.
	 * @return array
	 */
	public function exclude_from_core_sitemap( $post_types ) {
		unset( $post_types['ph_document'] );
		return $post_types;
	}

	/**
	 * Exclude ph_document from Yoast SEO sitemaps.
	 *
	 * @param bool   $excluded  Whether already excluded.
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function exclude_from_yoast( $excluded, $post_type ) {
		if ( 'ph_document' === $post_type ) {
			return true;
		}
		return $excluded;
	}

	/**
	 * Exclude ph_document from RankMath sitemaps.
	 *
	 * @param array  $entry     Sitemap entry.
	 * @param string $post_type Post type slug.
	 * @param object $post      Post object.
	 * @return array|false
	 */
	public function exclude_from_rankmath( $entry, $post_type, $post ) {
		if ( 'ph_document' === $post_type ) {
			return false;
		}
		return $entry;
	}
}
