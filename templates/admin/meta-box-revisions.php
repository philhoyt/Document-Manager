<?php
/**
 * Revision history meta box template.
 *
 * @package PH\DocumentManager
 * @var int   $post_id   Post ID.
 * @var array $revisions Revision entries.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $revisions ) ) : ?>
	<p class="description"><?php esc_html_e( 'No revision history yet. Previous destinations will appear here when the document is updated.', 'ph-document-manager' ); ?></p>
<?php else : ?>
	<table class="widefat ph-doc-revisions-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Destination', 'ph-document-manager' ); ?></th>
				<th><?php esc_html_e( 'Date', 'ph-document-manager' ); ?></th>
				<th><?php esc_html_e( 'Uploaded By', 'ph-document-manager' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $revisions as $index => $revision ) : ?>
				<tr>
					<td>
						<?php if ( isset( $revision['attachment_id'] ) ) : ?>
							<?php
							$attachment_url = wp_get_attachment_url( $revision['attachment_id'] );
							$attached_file  = get_attached_file( $revision['attachment_id'] );
							$filename       = $attached_file ? basename( $attached_file ) : '';

							if ( '' !== $filename ) {
								$label = $filename;
							} elseif ( $attachment_url ) {
								$label = __( '(unknown file)', 'ph-document-manager' );
							} else {
								$label = __( '(file missing)', 'ph-document-manager' );
							}
							?>
							<?php if ( $attachment_url ) : ?>
								<a href="<?php echo esc_url( $attachment_url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( $label ); ?>
								</a>
							<?php else : ?>
								<span class="ph-doc-missing"><?php echo esc_html( $label ); ?></span>
							<?php endif; ?>
						<?php elseif ( isset( $revision['url'] ) ) : ?>
							<a href="<?php echo esc_url( $revision['url'] ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $revision['url'] ); ?>
							</a>
						<?php endif; ?>
					</td>
					<td>
						<?php
						echo esc_html(
							isset( $revision['timestamp'] )
								? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $revision['timestamp'] )
								: '—'
						);
						?>
					</td>
					<td>
						<?php
						if ( ! empty( $revision['user_id'] ) ) {
							$user = get_userdata( $revision['user_id'] );
							echo esc_html( $user ? $user->display_name : __( '(unknown)', 'ph-document-manager' ) );
						} else {
							echo '—';
						}
						?>
					</td>
					<td>
						<?php
						// A nonced GET link, not a form: the meta box renders inside the
						// core post form and nested <form> elements are dropped by the
						// HTML parser, which would hijack the post form's own submission.
						$restore_url = wp_nonce_url(
							add_query_arg(
								array(
									'action'         => 'ph_document_restore_revision',
									'post_id'        => $post_id,
									'revision_index' => $index,
								),
								admin_url( 'admin-post.php' )
							),
							'ph_document_restore_revision_' . $post_id . '_' . $index
						);
						?>
						<a href="<?php echo esc_url( $restore_url ); ?>" class="button button-small">
							<?php esc_html_e( 'Restore', 'ph-document-manager' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
