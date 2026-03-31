<?php
/**
 * URL meta box template.
 *
 * @package PH\DocumentManager
 * @var int    $post_id Post ID.
 * @var string $url     Current destination URL.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ph-doc-url-meta-box">
	<label for="ph-doc-url" class="screen-reader-text">
		<?php esc_html_e( 'Destination URL', 'ph-document-manager' ); ?>
	</label>
	<input
		type="url"
		id="ph-doc-url"
		name="_ph_document_url"
		value="<?php echo esc_attr( $url ); ?>"
		class="large-text"
		placeholder="https://"
	/>
	<p class="description ph-doc-required-note">
		<?php esc_html_e( 'Required — a valid URL must be entered to publish this document.', 'ph-document-manager' ); ?>
	</p>
</div>
