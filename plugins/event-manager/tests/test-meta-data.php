<?php
/**
 * Tests for meta-data saving — Event Date and Location fields.
 *
 * Covers nonce verification, sanitization, date validation,
 * and edge cases.
 *
 * @package Event_Manager
 */

class Test_Meta_Data extends WP_UnitTestCase {

	/**
	 * Admin user ID used for tests.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Test event post ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create an admin user.
		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );

		// Register CPT.
		event_manager_register_event_post_type();

		// Create a test event.
		$this->event_id = $this->factory->post->create( array(
			'post_type'  => 'event',
			'post_title' => 'Meta Test Event',
		) );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/* ---------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------- */

	/**
	 * Simulate saving meta via the save_post hook.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $date     Event date value.
	 * @param string $location Location value.
	 * @param bool   $nonce    Whether to include a valid nonce.
	 */
	private function simulate_save( $post_id, $date, $location, $nonce = true ) {
		$_POST = array();

		if ( $nonce ) {
			$_POST['event_manager_meta_nonce'] = wp_create_nonce( 'event_manager_save_meta' );
		}

		$_POST['event_manager_date']     = $date;
		$_POST['event_manager_location'] = $location;

		// Trigger the save.
		event_manager_save_meta( $post_id );

		// Clean up global.
		$_POST = array();
	}

	/* ---------------------------------------------------------------
	 * Event Date Tests
	 * ------------------------------------------------------------- */

	/**
	 * Test saving a valid event date.
	 */
	public function test_save_valid_date() {
		$this->simulate_save( $this->event_id, '2026-06-15', 'Test City' );
		$this->assertEquals( '2026-06-15', get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that an invalid date format is rejected.
	 */
	public function test_reject_invalid_date_format() {
		$this->simulate_save( $this->event_id, '15/06/2026', 'Test City' );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that an impossible date is rejected (Feb 30).
	 */
	public function test_reject_impossible_date() {
		$this->simulate_save( $this->event_id, '2026-02-30', 'Test City' );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that Feb 29 on a non-leap year is rejected.
	 */
	public function test_reject_feb_29_non_leap_year() {
		$this->simulate_save( $this->event_id, '2025-02-29', 'Test City' );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that Feb 29 on a leap year is accepted.
	 */
	public function test_accept_feb_29_leap_year() {
		$this->simulate_save( $this->event_id, '2028-02-29', 'Test City' );
		$this->assertEquals( '2028-02-29', get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that an empty date deletes the meta.
	 */
	public function test_empty_date_deletes_meta() {
		// First set a date.
		update_post_meta( $this->event_id, '_event_manager_date', '2026-06-15' );
		// Then save empty.
		$this->simulate_save( $this->event_id, '', 'Test City' );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that date with extra text is rejected.
	 */
	public function test_reject_date_with_extra_text() {
		$this->simulate_save( $this->event_id, '2026-06-15 extra', 'Test City' );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that date with month 13 is rejected.
	 */
	public function test_reject_invalid_month() {
		$this->simulate_save( $this->event_id, '2026-13-01', 'Test City' );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that HTML in date field is sanitized/rejected.
	 */
	public function test_reject_html_in_date() {
		$this->simulate_save( $this->event_id, '<script>alert("xss")</script>', 'Test City' );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/* ---------------------------------------------------------------
	 * Location Tests
	 * ------------------------------------------------------------- */

	/**
	 * Test saving a valid location.
	 */
	public function test_save_valid_location() {
		$this->simulate_save( $this->event_id, '2026-06-15', 'New York, NY' );
		$this->assertEquals( 'New York, NY', get_post_meta( $this->event_id, '_event_manager_location', true ) );
	}

	/**
	 * Test that an empty location deletes the meta.
	 */
	public function test_empty_location_deletes_meta() {
		update_post_meta( $this->event_id, '_event_manager_location', 'Old Location' );
		$this->simulate_save( $this->event_id, '2026-06-15', '' );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_location', true ) );
	}

	/**
	 * Test that HTML tags are stripped from location.
	 */
	public function test_location_html_stripped() {
		$this->simulate_save( $this->event_id, '2026-06-15', '<b>Bold City</b>' );
		$this->assertEquals( 'Bold City', get_post_meta( $this->event_id, '_event_manager_location', true ) );
	}

	/**
	 * Test that script tags are stripped from location.
	 */
	public function test_location_script_stripped() {
		$this->simulate_save( $this->event_id, '2026-06-15', '<script>alert("xss")</script>Safe City' );
		$saved = get_post_meta( $this->event_id, '_event_manager_location', true );
		$this->assertStringNotContainsString( '<script>', $saved );
		$this->assertStringContainsString( 'Safe City', $saved );
	}

	/* ---------------------------------------------------------------
	 * Security Tests
	 * ------------------------------------------------------------- */

	/**
	 * Test that save is rejected without a nonce.
	 */
	public function test_reject_save_without_nonce() {
		$this->simulate_save( $this->event_id, '2026-06-15', 'Test City', false );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_location', true ) );
	}

	/**
	 * Test that save is rejected with an invalid nonce.
	 */
	public function test_reject_save_with_invalid_nonce() {
		$_POST = array(
			'event_manager_meta_nonce' => 'invalid_nonce_value',
			'event_manager_date'       => '2026-06-15',
			'event_manager_location'   => 'Test City',
		);

		event_manager_save_meta( $this->event_id );
		$_POST = array();

		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that save is rejected for wrong post type.
	 */
	public function test_reject_save_for_wrong_post_type() {
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$_POST = array(
			'event_manager_meta_nonce' => wp_create_nonce( 'event_manager_save_meta' ),
			'event_manager_date'       => '2026-06-15',
			'event_manager_location'   => 'Test City',
		);

		event_manager_save_meta( $page_id );
		$_POST = array();

		$this->assertEmpty( get_post_meta( $page_id, '_event_manager_date', true ) );
	}

	/**
	 * Test that a subscriber cannot save meta.
	 */
	public function test_reject_save_by_subscriber() {
		$subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$this->simulate_save( $this->event_id, '2026-06-15', 'Test City' );

		$this->assertEmpty( get_post_meta( $this->event_id, '_event_manager_date', true ) );
	}

	/* ---------------------------------------------------------------
	 * REST API Sanitization Tests
	 * ------------------------------------------------------------- */

	/**
	 * Test the date sanitize callback used by REST API.
	 */
	public function test_sanitize_date_callback_valid() {
		$this->assertEquals( '2026-12-25', event_manager_sanitize_date( '2026-12-25' ) );
	}

	/**
	 * Test the date sanitize callback rejects invalid format.
	 */
	public function test_sanitize_date_callback_invalid_format() {
		$this->assertEquals( '', event_manager_sanitize_date( 'not-a-date' ) );
	}

	/**
	 * Test the date sanitize callback rejects impossible dates.
	 */
	public function test_sanitize_date_callback_impossible_date() {
		$this->assertEquals( '', event_manager_sanitize_date( '2026-02-30' ) );
	}

	/**
	 * Test the date sanitize callback returns empty for empty input.
	 */
	public function test_sanitize_date_callback_empty() {
		$this->assertEquals( '', event_manager_sanitize_date( '' ) );
	}
}
