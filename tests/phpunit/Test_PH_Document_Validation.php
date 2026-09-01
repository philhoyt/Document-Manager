<?php
/**
 * Tests for save validation and the restore admin-post handler.
 *
 * @package PH\DocumentManager
 */

declare(strict_types=1);

use PH\DocumentManager\Validation;

/**
 * Covers Validation::hold_invalid_at_draft() and the hook wiring around it.
 *
 * Two shipped bugs are pinned here: the Validation service was never
 * registered at all (so the restore action 400'd and validation never ran),
 * and validation used to redirect and exit from inside save_post, truncating
 * the rest of the save.
 */
class Test_PH_Document_Validation extends WP_UnitTestCase {

	/**
	 * Build the $_POST payload the classic editor submits for a document.
	 *
	 * @param array $overrides Values to change.
	 * @return array
	 */
	private function editor_payload( $overrides = array() ) {
		return array_merge(
			array(
				Validation::NONCE_FIELD    => wp_create_nonce( Validation::NONCE_ACTION ),
				'_ph_document_source_type' => 'file',
				'_ph_document_file_id'     => '0',
				'_ph_document_url'         => '',
			),
			$overrides
		);
	}

	/**
	 * Reset superglobals between tests.
	 */
	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * The restore action must be registered.
	 *
	 * Regression: Plugin::register() omitted the Validation service entirely,
	 * so admin-post.php found no handler and returned wp_die( '', 400 ) — a
	 * blank page that read as a fatal error.
	 */
	public function test_restore_action_is_registered() {
		$this->assertNotFalse(
			has_action( 'admin_post_ph_document_restore_revision' ),
			'The restore handler must be hooked, or admin-post.php returns a blank 400.'
		);
	}

	/**
	 * Validation must run before the row is written, not from save_post.
	 */
	public function test_validation_runs_on_wp_insert_post_data() {
		$this->assertNotFalse(
			has_filter( 'wp_insert_post_data' ),
			'Validation must filter the data pre-write so the save is not truncated.'
		);
	}

	/**
	 * Publishing a file document with no file must be held at draft.
	 */
	public function test_publishing_without_a_file_is_held_at_draft() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'ph_document',
				'post_status' => 'draft',
			)
		);

		$_POST = $this->editor_payload();

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		$this->assertSame( 'draft', get_post_status( $post_id ) );
		$this->assertNotEmpty( get_transient( 'ph_doc_validation_error_' . $post_id ) );
	}

	/**
	 * Publishing a URL document with no URL must be held at draft.
	 */
	public function test_publishing_without_a_url_is_held_at_draft() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'ph_document',
				'post_status' => 'draft',
			)
		);

		$_POST = $this->editor_payload(
			array(
				'_ph_document_source_type' => 'url',
				'_ph_document_url'         => '',
			)
		);

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		$this->assertSame( 'draft', get_post_status( $post_id ) );
	}

	/**
	 * A document with a valid destination publishes normally.
	 */
	public function test_publishing_with_a_valid_file_succeeds() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'ph_document',
				'post_status' => 'draft',
			)
		);

		$_POST = $this->editor_payload( array( '_ph_document_file_id' => '123' ) );

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		$this->assertSame( 'publish', get_post_status( $post_id ) );
		$this->assertFalse( get_transient( 'ph_doc_validation_error_' . $post_id ) );
	}

	/**
	 * Validation must not fire without a valid nonce.
	 */
	public function test_validation_is_skipped_without_a_nonce() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'ph_document',
				'post_status' => 'draft',
			)
		);

		$_POST = $this->editor_payload();
		unset( $_POST[ Validation::NONCE_FIELD ] );

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		$this->assertSame(
			'publish',
			get_post_status( $post_id ),
			'Without the meta nonce this is not an editor save, so validation must not interfere.'
		);
	}

	/**
	 * Other post types must be untouched by the filter.
	 */
	public function test_other_post_types_are_unaffected() {
		$_POST = $this->editor_payload();

		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$this->assertSame( 'publish', get_post_status( $post_id ) );
	}
}
