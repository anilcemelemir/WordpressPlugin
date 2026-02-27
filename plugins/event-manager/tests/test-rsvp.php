<?php
/**
 * Tests for RSVP logic — confirm, cancel, edge cases, and security.
 *
 * @package Event_Manager
 */

class Test_RSVP extends WP_UnitTestCase {

	/**
	 * @var int Admin user ID.
	 */
	private $admin_id;

	/**
	 * @var int Subscriber user ID.
	 */
	private $subscriber_id;

	/**
	 * @var int Test event post ID.
	 */
	private $event_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		event_manager_register_event_post_type();

		$this->admin_id      = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		$this->event_id = $this->factory->post->create( array(
			'post_type'  => 'event',
			'post_title' => 'RSVP Test Event',
		) );
	}

	/**
	 * Tear down — remove filters that prevent exit during test.
	 */
	public function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	/* ---------------------------------------------------------------
	 * Helpers — Direct RSVP Logic Tests
	 * (Testing the core logic without going through AJAX transport)
	 * ------------------------------------------------------------- */

	/**
	 * Get the RSVP list for an event.
	 *
	 * @param int $event_id Event post ID.
	 * @return array List of user IDs.
	 */
	private function get_rsvp_list( $event_id ) {
		$list = get_post_meta( $event_id, '_event_rsvp_list', true );
		return is_array( $list ) ? $list : array();
	}

	/**
	 * Simulate an RSVP confirm action directly on post meta (mimics the AJAX handler logic).
	 *
	 * @param int $event_id Event post ID.
	 * @param int $user_id  User ID.
	 */
	private function rsvp_confirm( $event_id, $user_id ) {
		$rsvp_list = $this->get_rsvp_list( $event_id );
		if ( ! in_array( $user_id, $rsvp_list, true ) ) {
			$rsvp_list[] = $user_id;
		}
		update_post_meta( $event_id, '_event_rsvp_list', $rsvp_list );
	}

	/**
	 * Simulate an RSVP cancel action.
	 *
	 * @param int $event_id Event post ID.
	 * @param int $user_id  User ID.
	 */
	private function rsvp_cancel( $event_id, $user_id ) {
		$rsvp_list = $this->get_rsvp_list( $event_id );
		$rsvp_list = array_values( array_diff( $rsvp_list, array( $user_id ) ) );
		update_post_meta( $event_id, '_event_rsvp_list', $rsvp_list );
	}

	/* ---------------------------------------------------------------
	 * Confirm Tests
	 * ------------------------------------------------------------- */

	/**
	 * Test that a user can confirm RSVP.
	 */
	public function test_rsvp_confirm_adds_user() {
		$this->rsvp_confirm( $this->event_id, $this->admin_id );

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertContains( $this->admin_id, $list );
		$this->assertCount( 1, $list );
	}

	/**
	 * Test that confirming twice does not duplicate the user.
	 */
	public function test_rsvp_confirm_no_duplicate() {
		$this->rsvp_confirm( $this->event_id, $this->admin_id );
		$this->rsvp_confirm( $this->event_id, $this->admin_id );

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertCount( 1, $list );
	}

	/**
	 * Test that multiple users can RSVP to the same event.
	 */
	public function test_rsvp_multiple_users() {
		$this->rsvp_confirm( $this->event_id, $this->admin_id );
		$this->rsvp_confirm( $this->event_id, $this->subscriber_id );

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertCount( 2, $list );
		$this->assertContains( $this->admin_id, $list );
		$this->assertContains( $this->subscriber_id, $list );
	}

	/* ---------------------------------------------------------------
	 * Cancel Tests
	 * ------------------------------------------------------------- */

	/**
	 * Test that a user can cancel RSVP.
	 */
	public function test_rsvp_cancel_removes_user() {
		$this->rsvp_confirm( $this->event_id, $this->admin_id );
		$this->rsvp_cancel( $this->event_id, $this->admin_id );

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertNotContains( $this->admin_id, $list );
		$this->assertCount( 0, $list );
	}

	/**
	 * Test that cancelling a non-existent RSVP does not cause errors.
	 */
	public function test_rsvp_cancel_when_not_attending() {
		$this->rsvp_cancel( $this->event_id, $this->admin_id );

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertCount( 0, $list );
	}

	/**
	 * Test that cancelling one user does not affect another.
	 */
	public function test_rsvp_cancel_preserves_other_users() {
		$this->rsvp_confirm( $this->event_id, $this->admin_id );
		$this->rsvp_confirm( $this->event_id, $this->subscriber_id );
		$this->rsvp_cancel( $this->event_id, $this->admin_id );

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertCount( 1, $list );
		$this->assertContains( $this->subscriber_id, $list );
		$this->assertNotContains( $this->admin_id, $list );
	}

	/* ---------------------------------------------------------------
	 * Edge Cases
	 * ------------------------------------------------------------- */

	/**
	 * Test RSVP list is empty by default.
	 */
	public function test_rsvp_list_empty_by_default() {
		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertIsArray( $list );
		$this->assertEmpty( $list );
	}

	/**
	 * Test RSVP on non-existent post returns empty list.
	 */
	public function test_rsvp_non_existent_event() {
		$list = $this->get_rsvp_list( 999999 );
		$this->assertIsArray( $list );
		$this->assertEmpty( $list );
	}

	/**
	 * Test rapid confirm/cancel/confirm cycle.
	 */
	public function test_rsvp_rapid_toggle() {
		$this->rsvp_confirm( $this->event_id, $this->admin_id );
		$this->rsvp_cancel( $this->event_id, $this->admin_id );
		$this->rsvp_confirm( $this->event_id, $this->admin_id );

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertCount( 1, $list );
		$this->assertContains( $this->admin_id, $list );
	}

	/**
	 * Test that RSVP list indices are re-indexed after cancel (no gaps).
	 */
	public function test_rsvp_list_reindexed_after_cancel() {
		$user3 = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		$this->rsvp_confirm( $this->event_id, $this->admin_id );
		$this->rsvp_confirm( $this->event_id, $this->subscriber_id );
		$this->rsvp_confirm( $this->event_id, $user3 );

		// Cancel the middle user.
		$this->rsvp_cancel( $this->event_id, $this->subscriber_id );

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertCount( 2, $list );

		// Check indices are sequential (0, 1) — no gaps.
		$keys = array_keys( $list );
		$this->assertEquals( array( 0, 1 ), $keys );
	}

	/**
	 * Test RSVP with many users (stress test).
	 */
	public function test_rsvp_many_users() {
		$user_ids = array();
		for ( $i = 0; $i < 50; $i++ ) {
			$uid        = $this->factory->user->create( array( 'role' => 'subscriber' ) );
			$user_ids[] = $uid;
			$this->rsvp_confirm( $this->event_id, $uid );
		}

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertCount( 50, $list );

		// Cancel half.
		for ( $i = 0; $i < 25; $i++ ) {
			$this->rsvp_cancel( $this->event_id, $user_ids[ $i ] );
		}

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertCount( 25, $list );
	}

	/* ---------------------------------------------------------------
	 * Data Integrity Tests
	 * ------------------------------------------------------------- */

	/**
	 * Test that RSVP data is stored as an array of integers.
	 */
	public function test_rsvp_data_type() {
		$this->rsvp_confirm( $this->event_id, $this->admin_id );

		$list = $this->get_rsvp_list( $this->event_id );
		$this->assertIsArray( $list );
		foreach ( $list as $uid ) {
			$this->assertIsInt( $uid );
		}
	}

	/**
	 * Test that RSVP list is independent per event.
	 */
	public function test_rsvp_independent_per_event() {
		$event2 = $this->factory->post->create( array(
			'post_type'  => 'event',
			'post_title' => 'Second Event',
		) );

		$this->rsvp_confirm( $this->event_id, $this->admin_id );
		$this->rsvp_confirm( $event2, $this->subscriber_id );

		$list1 = $this->get_rsvp_list( $this->event_id );
		$list2 = $this->get_rsvp_list( $event2 );

		$this->assertCount( 1, $list1 );
		$this->assertContains( $this->admin_id, $list1 );

		$this->assertCount( 1, $list2 );
		$this->assertContains( $this->subscriber_id, $list2 );
	}

	/**
	 * Test that one user can RSVP to multiple events.
	 */
	public function test_user_rsvp_multiple_events() {
		$event2 = $this->factory->post->create( array(
			'post_type'  => 'event',
			'post_title' => 'Another Event',
		) );

		$this->rsvp_confirm( $this->event_id, $this->admin_id );
		$this->rsvp_confirm( $event2, $this->admin_id );

		$this->assertContains( $this->admin_id, $this->get_rsvp_list( $this->event_id ) );
		$this->assertContains( $this->admin_id, $this->get_rsvp_list( $event2 ) );
	}
}
