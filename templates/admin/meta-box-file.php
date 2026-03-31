<?php
/**
 * File meta box template.
 *
 * @package PH\DocumentManager
 * @var int    $post_id    Post ID.
 * @var int    $file_id    Current attachment ID.
 * @var string $file_name  Filename for display.
 * @var int    $file_size  File size in bytes.
 * @var string $file_date  Formatted upload date.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ph-doc-file-meta-box">
	<input
		type="hidden"
		id="ph-doc-file-id"
		name="_ph_document_file_id"
		value="<?php echo esc_attr( $file_id ); ?>"
	/>

	<div id="ph-doc-file-info" class="ph-doc-file-info <?php echo $file_id ? 'has-file' : ''; ?>">
		<?php if ( $file_id ) : ?>
			<span class="ph-doc-file-name"><?php echo esc_html( $file_name ); ?></span>
			<?php if ( $file_size ) : ?>
				<span class="ph-doc-file-size"><?php echo esc_html( size_format( $file_size ) ); ?></span>
			<?php endif; ?>
			<?php if ( $file_date ) : ?>
				<span class="ph-doc-file-date"><?php echo esc_html( $file_date ); ?></span>
			<?php endif; ?>
		<?php else : ?>
			<span class="ph-doc-no-file"><?php esc_html_e( 'No file selected.', 'ph-document-manager' ); ?></span>
		<?php endif; ?>
	</div>

	<p>
		<button
			type="button"
			id="ph-doc-select-file"
			class="button"
		><?php esc_html_e( 'Select or Upload File', 'ph-document-manager' ); ?></button>

		<?php if ( $file_id ) : ?>
			<button type="button" id="ph-doc-remove-file" class="button-link ph-doc-remove-link">
				<?php esc_html_e( 'Remove', 'ph-document-manager' ); ?>
			</button>
		<?php endif; ?>
	</p>

	<p class="description ph-doc-required-note">
		<?php esc_html_e( 'Required — a file must be selected to publish this document.', 'ph-document-manager' ); ?>
	</p>
</div>
