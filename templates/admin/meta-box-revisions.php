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
							$filename       = basename( get_attached_file( $revision['attachment_id'] ) );
							?>
							<?php if ( $attachment_url ) : ?>
								<a href="<?php echo esc_url( $attachment_url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( $filename ?: __( '(unknown file)', 'ph-document-manager' ) ); ?>
								</a>
							<?php else : ?>
								<span class="ph-doc-missing"><?php echo esc_html( $filename ?: __( '(file missing)', 'ph-document-manager' ) ); ?></span>
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
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="ph_document_restore_revision" />
							<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>" />
							<input type="hidden" name="revision_index" value="<?php echo esc_attr( $index ); ?>" />
							<?php wp_nonce_field( 'ph_document_restore_revision', 'ph_restore_nonce' ); ?>
							<button type="submit" class="button button-small">
								<?php esc_html_e( 'Restore', 'ph-document-manager' ); ?>
							</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
