<?php
/**
 * Tests for Custom Post Type and Taxonomy registration.
 *
 * @package Event_Manager
 */

class Test_CPT_Registration extends WP_UnitTestCase {

	/**
	 * Set up — ensure CPT and taxonomy are registered.
	 */
	public function setUp(): void {
		parent::setUp();
		event_manager_register_event_post_type();
		event_manager_register_event_type_taxonomy();
	}

	/* ---------------------------------------------------------------
	 * Custom Post Type Tests
	 * ------------------------------------------------------------- */

	/**
	 * Test that the 'event' post type is registered.
	 */
	public function test_event_post_type_exists() {
		$this->assertTrue( post_type_exists( 'event' ) );
	}

	/**
	 * Test that 'event' CPT is public.
	 */
	public function test_event_post_type_is_public() {
		$post_type = get_post_type_object( 'event' );
		$this->assertTrue( $post_type->public );
	}

	/**
	 * Test that 'event' CPT has show_in_rest enabled.
	 */
	public function test_event_post_type_show_in_rest() {
		$post_type = get_post_type_object( 'event' );
		$this->assertTrue( $post_type->show_in_rest );
	}

	/**
	 * Test that 'event' CPT has the correct rest_base.
	 */
	public function test_event_post_type_rest_base() {
		$post_type = get_post_type_object( 'event' );
		$this->assertEquals( 'event', $post_type->rest_base );
	}

	/**
	 * Test that 'event' CPT has archive enabled.
	 */
	public function test_event_post_type_has_archive() {
		$post_type = get_post_type_object( 'event' );
		$this->assertTrue( $post_type->has_archive );
	}

	/**
	 * Test that 'event' CPT is not hierarchical.
	 */
	public function test_event_post_type_not_hierarchical() {
		$post_type = get_post_type_object( 'event' );
		$this->assertFalse( $post_type->hierarchical );
	}

	/**
	 * Test that 'event' CPT supports expected features.
	 */
	public function test_event_post_type_supports() {
		$this->assertTrue( post_type_supports( 'event', 'title' ) );
		$this->assertTrue( post_type_supports( 'event', 'editor' ) );
		$this->assertTrue( post_type_supports( 'event', 'author' ) );
		$this->assertTrue( post_type_supports( 'event', 'thumbnail' ) );
		$this->assertTrue( post_type_supports( 'event', 'excerpt' ) );
		$this->assertTrue( post_type_supports( 'event', 'comments' ) );
	}

	/**
	 * Test that 'event' CPT does NOT support custom-fields (removed intentionally).
	 */
	public function test_event_post_type_no_custom_fields_support() {
		$this->assertFalse( post_type_supports( 'event', 'custom-fields' ) );
	}

	/**
	 * Test that 'event' CPT has the correct menu icon.
	 */
	public function test_event_post_type_menu_icon() {
		$post_type = get_post_type_object( 'event' );
		$this->assertEquals( 'dashicons-calendar-alt', $post_type->menu_icon );
	}

	/**
	 * Test that 'event' CPT labels are set correctly.
	 */
	public function test_event_post_type_labels() {
		$post_type = get_post_type_object( 'event' );
		$this->assertEquals( 'Events', $post_type->labels->name );
		$this->assertEquals( 'Event', $post_type->labels->singular_name );
		$this->assertEquals( 'Add New Event', $post_type->labels->add_new_item );
	}

	/**
	 * Test that an event post can be created.
	 */
	public function test_event_post_creation() {
		$post_id = $this->factory->post->create( array(
			'post_type'  => 'event',
			'post_title' => 'Test Event',
		) );

		$this->assertIsInt( $post_id );
		$this->assertGreaterThan( 0, $post_id );
		$this->assertEquals( 'event', get_post_type( $post_id ) );
		$this->assertEquals( 'Test Event', get_the_title( $post_id ) );
	}

	/* ---------------------------------------------------------------
	 * Taxonomy Tests
	 * ------------------------------------------------------------- */

	/**
	 * Test that 'event_type' taxonomy is registered.
	 */
	public function test_event_type_taxonomy_exists() {
		$this->assertTrue( taxonomy_exists( 'event_type' ) );
	}

	/**
	 * Test that 'event_type' taxonomy is hierarchical.
	 */
	public function test_event_type_taxonomy_is_hierarchical() {
		$tax = get_taxonomy( 'event_type' );
		$this->assertTrue( $tax->hierarchical );
	}

	/**
	 * Test that 'event_type' taxonomy has show_in_rest enabled.
	 */
	public function test_event_type_taxonomy_show_in_rest() {
		$tax = get_taxonomy( 'event_type' );
		$this->assertTrue( $tax->show_in_rest );
	}

	/**
	 * Test that 'event_type' taxonomy is linked to 'event' post type.
	 */
	public function test_event_type_taxonomy_object_type() {
		$tax = get_taxonomy( 'event_type' );
		$this->assertContains( 'event', $tax->object_type );
	}

	/**
	 * Test that 'event_type' taxonomy labels are correct.
	 */
	public function test_event_type_taxonomy_labels() {
		$tax = get_taxonomy( 'event_type' );
		$this->assertEquals( 'Event Types', $tax->labels->name );
		$this->assertEquals( 'Event Type', $tax->labels->singular_name );
	}

	/**
	 * Test assigning a taxonomy term to an event.
	 */
	public function test_assign_event_type_to_event() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'event' ) );
		$term    = wp_insert_term( 'Conference', 'event_type' );

		wp_set_object_terms( $post_id, $term['term_id'], 'event_type' );
		$terms = wp_get_object_terms( $post_id, 'event_type' );

		$this->assertCount( 1, $terms );
		$this->assertEquals( 'Conference', $terms[0]->name );
	}

	/**
	 * Test assigning multiple taxonomy terms to an event.
	 */
	public function test_assign_multiple_event_types() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'event' ) );
		$term1   = wp_insert_term( 'Workshop', 'event_type' );
		$term2   = wp_insert_term( 'Seminar', 'event_type' );

		wp_set_object_terms( $post_id, array( $term1['term_id'], $term2['term_id'] ), 'event_type' );
		$terms = wp_get_object_terms( $post_id, 'event_type', array( 'orderby' => 'name' ) );

		$this->assertCount( 2, $terms );
	}
}
