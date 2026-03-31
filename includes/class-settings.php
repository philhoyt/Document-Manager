<?php
/**
 * Plugin settings page.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the plugin settings page.
 */
class Settings {

	/**
	 * Option names.
	 */
	const OPTION_SLUG        = 'ph_document_manager_slug';
	const OPTION_CAPABILITY  = 'ph_document_manager_capability';
	const OPTION_FALLBACK    = 'ph_document_manager_fallback_url';

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'update_option_' . self::OPTION_SLUG, array( $this, 'flush_on_slug_change' ) );
	}

	/**
	 * Add settings page under the Documents menu.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=ph_document',
			__( 'Document Manager Settings', 'ph-document-manager' ),
			__( 'Settings', 'ph-document-manager' ),
			'manage_options',
			'ph-document-manager-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			'ph_document_manager',
			self::OPTION_SLUG,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
				'default'           => 'documents',
			)
		);

		register_setting(
			'ph_document_manager',
			self::OPTION_CAPABILITY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'editor',
			)
		);

		register_setting(
			'ph_document_manager',
			self::OPTION_FALLBACK,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);

		add_settings_section(
			'ph_document_manager_general',
			__( 'General', 'ph-document-manager' ),
			'__return_false',
			'ph_document_manager'
		);

		add_settings_field(
			self::OPTION_SLUG,
			__( 'Permalink Slug', 'ph-document-manager' ),
			array( $this, 'render_slug_field' ),
			'ph_document_manager',
			'ph_document_manager_general'
		);

		add_settings_field(
			self::OPTION_FALLBACK,
			__( '404 Fallback URL', 'ph-document-manager' ),
			array( $this, 'render_fallback_field' ),
			'ph_document_manager',
			'ph_document_manager_general'
		);
	}

	/**
	 * Render the slug field.
	 */
	public function render_slug_field() {
		$value = get_option( self::OPTION_SLUG, 'documents' );
		printf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" />
			<p class="description">%3$s</p>',
			esc_attr( self::OPTION_SLUG ),
			esc_attr( $value ),
			esc_html__( 'The URL slug used for document permalinks. Default: documents', 'ph-document-manager' )
		);
	}

	/**
	 * Render the fallback URL field.
	 */
	public function render_fallback_field() {
		$value = get_option( self::OPTION_FALLBACK, '' );
		printf(
			'<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="https://" />
			<p class="description">%3$s</p>',
			esc_attr( self::OPTION_FALLBACK ),
			esc_attr( $value ),
			esc_html__( 'Where to redirect if a document has no valid destination. Leave blank to return a 404.', 'ph-document-manager' )
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require PH_DOCUMENT_MANAGER_DIR . 'templates/admin/settings-page.php';
	}

	/**
	 * Flush rewrite rules when the slug option changes.
	 */
	public function flush_on_slug_change() {
		flush_rewrite_rules();
	}
}
