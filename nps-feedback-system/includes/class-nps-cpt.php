<?php
/**
 * Registration and admin customization of the "NPS Submissions" Custom Post Type.
 *
 * @package NPS_Feedback_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPS_CPT {

	/**
	 * Registers the module's hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'show_all_statuses_in_admin' ) );
		add_filter( 'manage_nps_submission_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_nps_submission_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
	}

	/**
	 * Registers the "nps_submission" Custom Post Type.
	 */
	public static function register_post_type() {
		register_post_type(
			'nps_submission',
			array(
				'labels'          => array(
					'name'          => __( 'NPS Submissions', 'nps-feedback-system' ),
					'singular_name' => __( 'NPS Submission', 'nps-feedback-system' ),
					'menu_name'     => __( 'NPS Submissions', 'nps-feedback-system' ),
					'all_items'     => __( 'All Submissions', 'nps-feedback-system' ),
					'add_new'       => __( 'Add New', 'nps-feedback-system' ),
					'edit_item'     => __( 'View Submission', 'nps-feedback-system' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-star-filled',
				'supports'        => array( 'title', 'editor', 'author' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Ensures the admin list shows submissions from any author (including those
	 * submitted by anonymous visitors), regardless of status.
	 *
	 * @param WP_Query $query Main admin query.
	 */
	public static function show_all_statuses_in_admin( $query ) {
		if ( is_admin() && $query->is_main_query() && 'nps_submission' === $query->get( 'post_type' ) ) {
			$query->set( 'post_status', array( 'publish', 'pending', 'draft', 'future', 'private' ) );
		}
	}

	/**
	 * Defines the columns of the admin list table.
	 *
	 * @param array $columns Default columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		return array(
			'cb'    => '<input type="checkbox" />',
			'title' => __( 'Customer', 'nps-feedback-system' ),
			'score' => __( 'NPS Score', 'nps-feedback-system' ),
			'email' => __( 'Email', 'nps-feedback-system' ),
			'date'  => __( 'Date', 'nps-feedback-system' ),
		);
	}

	/**
	 * Renders the content of the custom columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'score' === $column ) {
			$score = get_post_meta( $post_id, 'nps_score', true );
			echo '<strong>' . esc_html( $score ) . ' / 10</strong>';
		}

		if ( 'email' === $column ) {
			echo esc_html( get_post_meta( $post_id, 'nps_email', true ) );
		}
	}
}
