<?php
/**
 * List table customization.
 *
 * @package PH\DocumentManager
 */

namespace PH\DocumentManager;

defined( 'ABSPATH' ) || exit;

/**
 * Adds custom columns and category filter to the ph_document list table.
 */
class List_Table {

	/**
	 * Hook into WordPress.
	 */
	public function register() {
		add_filter( 'manage_ph_document_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_ph_document_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-ph_document_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'restrict_manage_posts', array( $this, 'render_category_filter' ) );
		add_filter( 'parse_query', array( $this, 'apply_category_filter' ) );
	}

	/**
	 * Define list table columns.
	 *
	 * @param array $columns Default columns.
	 * @return array
	 */
	public function add_columns( $columns ) {
		$new = array();

		// Keep checkbox and title.
		if ( isset( $columns['cb'] ) ) {
			$new['cb'] = $columns['cb'];
		}
		$new['title'] = $columns['title'] ?? __( 'Title', 'ph-document-manager' );

		// Custom columns.
		$new['ph_type']        = __( 'Type', 'ph-document-manager' );
		$new['ph_destination'] = __( 'Destination', 'ph-document-manager' );
		$new['ph_category']    = __( 'Category', 'ph-document-manager' );

		// Keep date/modified.
		$new['date'] = $columns['date'] ?? __( 'Date', 'ph-document-manager' );

		return $new;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column slug.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'ph_type':
				$this->render_type_column( $post_id );
				break;

			case 'ph_destination':
				$this->render_destination_column( $post_id );
				break;

			case 'ph_category':
				$this->render_category_column( $post_id );
				break;
		}
	}

	/**
	 * Render the type badge column.
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_type_column( $post_id ) {
		$type = get_post_meta( $post_id, '_ph_document_source_type', true ) ?: 'file';

		if ( 'file' === $type ) {
			printf(
				'<span class="ph-doc-type-badge ph-doc-type-badge--file">%s</span>',
				esc_html__( 'File', 'ph-document-manager' )
			);
		} else {
			printf(
				'<span class="ph-doc-type-badge ph-doc-type-badge--url">%s</span>',
				esc_html__( 'URL', 'ph-document-manager' )
			);
		}
	}

	/**
	 * Render the destination column.
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_destination_column( $post_id ) {
		$type = get_post_meta( $post_id, '_ph_document_source_type', true ) ?: 'file';

		if ( 'file' === $type ) {
			$file_id = (int) get_post_meta( $post_id, '_ph_document_file_id', true );
			if ( $file_id > 0 ) {
				$url      = wp_get_attachment_url( $file_id );
				$filename = basename( get_attached_file( $file_id ) );
				if ( $url ) {
					printf(
						'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
						esc_url( $url ),
						esc_html( $filename ?: $url )
					);
				} else {
					echo '<span class="description">' . esc_html__( '(file missing)', 'ph-document-manager' ) . '</span>';
				}
			} else {
				echo '<span class="description">' . esc_html__( '—', 'ph-document-manager' ) . '</span>';
			}
		} else {
			$url = get_post_meta( $post_id, '_ph_document_url', true );
			if ( $url ) {
				printf(
					'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
					esc_url( $url ),
					esc_html( $url )
				);
			} else {
				echo '<span class="description">' . esc_html__( '—', 'ph-document-manager' ) . '</span>';
			}
		}
	}

	/**
	 * Render the category column.
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_category_column( $post_id ) {
		$terms = get_the_terms( $post_id, 'ph_document_category' );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			echo '—';
			return;
		}

		$term_links = array();
		foreach ( $terms as $term ) {
			$term_links[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'post_type'            => 'ph_document',
							'ph_document_category' => $term->slug,
						),
						admin_url( 'edit.php' )
					)
				),
				esc_html( $term->name )
			);
		}

		echo implode( ', ', $term_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Register sortable columns.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		$columns['ph_type'] = 'ph_type';
		return $columns;
	}

	/**
	 * Render the category filter dropdown.
	 *
	 * @param string $post_type Current post type.
	 */
	public function render_category_filter( $post_type ) {
		if ( 'ph_document' !== $post_type ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'ph_document_category',
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return;
		}

		$current = isset( $_GET['ph_document_category'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( $_GET['ph_document_category'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

		echo '<select name="ph_document_category" id="ph_document_category_filter">';
		echo '<option value="">' . esc_html__( 'All Categories', 'ph-document-manager' ) . '</option>';
		foreach ( $terms as $term ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $term->slug ),
				selected( $current, $term->slug, false ),
				esc_html( $term->name )
			);
		}
		echo '</select>';
	}

	/**
	 * Apply category filter to the query.
	 *
	 * @param \WP_Query $query Current query.
	 */
	public function apply_category_filter( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'ph_document' !== $query->get( 'post_type' ) ) {
			return;
		}

		$category = isset( $_GET['ph_document_category'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_text_field( wp_unslash( $_GET['ph_document_category'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

		if ( ! $category ) {
			return;
		}

		$tax_query = array(
			array(
				'taxonomy' => 'ph_document_category',
				'field'    => 'slug',
				'terms'    => $category,
			),
		);

		$query->set( 'tax_query', $tax_query );
	}
}
