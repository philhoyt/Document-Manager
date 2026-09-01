<?php
/**
 * Tests for the revision history helpers.
 *
 * @package PH\DocumentManager
 */

declare(strict_types=1);

use PH\DocumentManager\Revisions;

/**
 * Covers Revisions::push_file_revision(), push_url_revision() and
 * restore_revision().
 *
 * The restore path is the one that shipped broken twice, so it is covered in
 * both directions including the remove-then-restore round trip.
 */
class Test_PH_Document_Revisions extends WP_UnitTestCase {

	/**
	 * Document under test.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Create a document for each test.
	 */
	public function set_up() {
		parent::set_up();

		$this->post_id = self::factory()->post->create(
			array(
				'post_type'   => 'ph_document',
				'post_status' => 'publish',
				'post_title'  => 'Test document',
			)
		);
	}

	/**
	 * The first file assigned has nothing to archive.
	 */
	public function test_first_file_creates_no_revision() {
		Revisions::push_file_revision( $this->post_id, 101 );

		$this->assertSame( array(), Revisions::get_revisions( $this->post_id ) );
		$this->assertSame( 101, (int) get_post_meta( $this->post_id, '_ph_document_file_id', true ) );
	}

	/**
	 * Replacing a file archives the outgoing one.
	 */
	public function test_replacing_a_file_archives_the_previous_one() {
		Revisions::push_file_revision( $this->post_id, 101 );
		Revisions::push_file_revision( $this->post_id, 102 );

		$revisions = Revisions::get_revisions( $this->post_id );

		$this->assertCount( 1, $revisions );
		$this->assertSame( 101, (int) $revisions[0]['attachment_id'] );
		$this->assertSame( 102, (int) get_post_meta( $this->post_id, '_ph_document_file_id', true ) );
	}

	/**
	 * Clearing the file must archive it rather than drop it.
	 *
	 * Regression: save_meta() used to write file_id 0 directly, losing the
	 * outgoing attachment from history entirely.
	 */
	public function test_clearing_the_file_still_archives_it() {
		Revisions::push_file_revision( $this->post_id, 101 );
		Revisions::push_file_revision( $this->post_id, 0 );

		$revisions = Revisions::get_revisions( $this->post_id );

		$this->assertCount( 1, $revisions );
		$this->assertSame( 101, (int) $revisions[0]['attachment_id'] );
		$this->assertSame( 0, (int) get_post_meta( $this->post_id, '_ph_document_file_id', true ) );
	}

	/**
	 * Restoring swaps the archived file in and the current file out.
	 */
	public function test_restore_swaps_current_and_archived_file() {
		Revisions::push_file_revision( $this->post_id, 101 );
		Revisions::push_file_revision( $this->post_id, 102 );

		$this->assertTrue( Revisions::restore_revision( $this->post_id, 0 ) );

		$this->assertSame( 101, (int) get_post_meta( $this->post_id, '_ph_document_file_id', true ) );
		$this->assertSame( 'file', get_post_meta( $this->post_id, '_ph_document_source_type', true ) );

		$revisions = Revisions::get_revisions( $this->post_id );
		$this->assertCount( 1, $revisions, 'The restored entry should leave history, not duplicate into it.' );
		$this->assertSame( 102, (int) $revisions[0]['attachment_id'] );
	}

	/**
	 * A removed file can be restored again.
	 */
	public function test_remove_then_restore_round_trip() {
		Revisions::push_file_revision( $this->post_id, 101 );
		Revisions::push_file_revision( $this->post_id, 102 );
		Revisions::push_file_revision( $this->post_id, 0 );

		$revisions = Revisions::get_revisions( $this->post_id );
		$this->assertCount( 2, $revisions );

		// Index 1 is the file that was current when it was cleared.
		$this->assertTrue( Revisions::restore_revision( $this->post_id, 1 ) );
		$this->assertSame( 102, (int) get_post_meta( $this->post_id, '_ph_document_file_id', true ) );
	}

	/**
	 * An out-of-range index is rejected rather than fataling.
	 */
	public function test_restore_rejects_an_invalid_index() {
		Revisions::push_file_revision( $this->post_id, 101 );

		$this->assertFalse( Revisions::restore_revision( $this->post_id, 99 ) );
	}

	/**
	 * URL revisions archive and restore, and force the source type across.
	 */
	public function test_url_revision_round_trip() {
		Revisions::push_url_revision( $this->post_id, 'https://example.com/one.pdf' );
		$this->assertSame( array(), Revisions::get_revisions( $this->post_id ) );

		Revisions::push_url_revision( $this->post_id, 'https://example.com/two.pdf' );
		$revisions = Revisions::get_revisions( $this->post_id );
		$this->assertCount( 1, $revisions );
		$this->assertSame( 'https://example.com/one.pdf', $revisions[0]['url'] );

		$this->assertTrue( Revisions::restore_revision( $this->post_id, 0 ) );
		$this->assertSame( 'https://example.com/one.pdf', get_post_meta( $this->post_id, '_ph_document_url', true ) );
		$this->assertSame( 'url', get_post_meta( $this->post_id, '_ph_document_source_type', true ) );
	}
}
