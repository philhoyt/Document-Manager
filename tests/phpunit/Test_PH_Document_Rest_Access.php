<?php
/**
 * Tests for link-picker discovery and REST access control.
 *
 * @package PH\DocumentManager
 */

declare(strict_types=1);

/**
 * Covers the FUNC-01 / SEC-01 fix.
 *
 * Documents must be discoverable by the core link picker (which only considers
 * public post types) without becoming anonymously enumerable over REST. These
 * two pull against each other, so both halves are pinned here — flipping
 * 'public' back to false, or dropping either REST gate, should fail loudly.
 */
class Test_PH_Document_Rest_Access extends WP_Test_REST_TestCase {

	/**
	 * REST server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Published document used across the tests.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Boot a REST server and a published document.
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );

		$this->post_id = self::factory()->post->create(
			array(
				'post_type'   => 'ph_document',
				'post_status' => 'publish',
				'post_title'  => 'Findable Document',
			)
		);
		update_post_meta( $this->post_id, '_ph_document_source_type', 'url' );
		update_post_meta( $this->post_id, '_ph_document_url', 'https://example.com/doc.pdf' );
	}

	/**
	 * Reset the current user between tests.
	 */
	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * The link picker only queries public post types.
	 *
	 * Regression: the CPT shipped 'public' => false, so neither
	 * wp_link_query() nor the REST search handler could ever see it — the
	 * plugin's headline feature did not work.
	 */
	public function test_post_type_is_public_so_the_link_picker_can_find_it() {
		$public_types = get_post_types( array( 'public' => true ) );

		$this->assertContains(
			'ph_document',
			$public_types,
			'Both link pickers query get_post_types( public => true ); documents must be in that list.'
		);
	}

	/**
	 * Being public must not put documents into front-end site search.
	 */
	public function test_post_type_is_excluded_from_site_search() {
		$post_type = get_post_type_object( 'ph_document' );

		$this->assertTrue( (bool) $post_type->exclude_from_search );
	}

	/**
	 * An editor can find documents through the core search endpoint.
	 */
	public function test_editor_can_find_documents_via_search_endpoint() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/search' );
		$request->set_param( 'search', 'Findable' );
		$request->set_param( 'subtype', array( 'ph_document' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data(), 'The link picker must return documents for editors.' );
	}

	/**
	 * An anonymous visitor must not be able to enumerate via search.
	 *
	 * WP_REST_Post_Search_Handler ignores exclude_from_search, so making the
	 * post type public opens this route unless it is explicitly gated.
	 */
	public function test_anonymous_cannot_enumerate_via_search_endpoint() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wp/v2/search' );
		$request->set_param( 'search', 'Findable' );
		$request->set_param( 'subtype', array( 'ph_document' ) );
		$response = $this->server->dispatch( $request );

		$this->assertEmpty(
			$response->get_data(),
			'Documents must not be enumerable by anonymous visitors through /wp/v2/search.'
		);
	}

	/**
	 * The documents collection requires an editing capability.
	 */
	public function test_anonymous_cannot_list_the_documents_collection() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/documents' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );
	}

	/**
	 * A single document must be gated too, or the collection gate is bypassed
	 * by walking post IDs.
	 */
	public function test_anonymous_cannot_read_a_single_document() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/documents/' . $this->post_id );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );
	}

	/**
	 * An editor can still read the collection.
	 */
	public function test_editor_can_list_the_documents_collection() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/documents' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data() );
	}

	/**
	 * Other post types must not be filtered out of search for anonymous users.
	 */
	public function test_search_gate_does_not_affect_other_post_types() {
		self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Findable Article',
			)
		);

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wp/v2/search' );
		$request->set_param( 'search', 'Findable Article' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data(), 'Regular posts must still be searchable anonymously.' );
	}
}
