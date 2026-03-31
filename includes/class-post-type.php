<?php
/**
 * Custom post type and taxonomy registration.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the ph_document CPT and ph_document_category taxonomy.
 */
class Post_Type {

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ), 0 );
		add_action( 'init', array( $this, 'register_taxonomy' ), 0 );
	}

	/**
	 * Register the ph_document custom post type.
	 */
	public function register_post_type() {
		$slug = get_option( 'ph_document_manager_slug', 'documents' );

		$labels = array(
			'name'               => _x( 'Documents', 'post type general name', 'ph-document-manager' ),
			'singular_name'      => _x( 'Document', 'post type singular name', 'ph-document-manager' ),
			'add_new'            => __( 'Add New', 'ph-document-manager' ),
			'add_new_item'       => __( 'Add New Document', 'ph-document-manager' ),
			'edit_item'          => __( 'Edit Document', 'ph-document-manager' ),
			'new_item'           => __( 'New Document', 'ph-document-manager' ),
			'view_item'          => __( 'View Document', 'ph-document-manager' ),
			'search_items'       => __( 'Search Documents', 'ph-document-manager' ),
			'not_found'          => __( 'No documents found.', 'ph-document-manager' ),
			'not_found_in_trash' => __( 'No documents found in Trash.', 'ph-document-manager' ),
			'all_items'          => __( 'All Documents', 'ph-document-manager' ),
			'menu_name'          => __( 'Documents', 'ph-document-manager' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'query_var'           => true,
			'rewrite'             => array(
				'slug'  => sanitize_title( $slug ),
				'feeds' => false,
			),
			'capability_type'     => 'post',
			'capabilities'        => array(
				'edit_post'          => 'manage_ph_documents',
				'read_post'          => 'manage_ph_documents',
				'delete_post'        => 'manage_ph_documents',
				'edit_posts'         => 'manage_ph_documents',
				'edit_others_posts'  => 'manage_ph_documents',
				'publish_posts'      => 'manage_ph_documents',
				'read_private_posts' => 'manage_ph_documents',
				'delete_posts'       => 'manage_ph_documents',
				'create_posts'       => 'manage_ph_documents',
			),
			'map_meta_cap'        => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-media-document',
			'supports'            => array( 'title', 'revisions' ),
			'taxonomies'          => array( 'ph_document_category' ),
			'rest_base'           => 'documents',
		);

		register_post_type( 'ph_document', $args );
	}

	/**
	 * Register the ph_document_category taxonomy.
	 */
	public function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Document Categories', 'taxonomy general name', 'ph-document-manager' ),
			'singular_name'     => _x( 'Document Category', 'taxonomy singular name', 'ph-document-manager' ),
			'search_items'      => __( 'Search Document Categories', 'ph-document-manager' ),
			'all_items'         => __( 'All Document Categories', 'ph-document-manager' ),
			'parent_item'       => __( 'Parent Document Category', 'ph-document-manager' ),
			'parent_item_colon' => __( 'Parent Document Category:', 'ph-document-manager' ),
			'edit_item'         => __( 'Edit Document Category', 'ph-document-manager' ),
			'update_item'       => __( 'Update Document Category', 'ph-document-manager' ),
			'add_new_item'      => __( 'Add New Document Category', 'ph-document-manager' ),
			'new_item_name'     => __( 'New Document Category Name', 'ph-document-manager' ),
			'menu_name'         => __( 'Categories', 'ph-document-manager' ),
		);

		$args = array(
			'labels'            => $labels,
			'public'            => false,
			'publicly_queryable' => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'rewrite'           => false,
			'show_admin_column' => false,
		);

		register_taxonomy( 'ph_document_category', array( 'ph_document' ), $args );
	}
}
