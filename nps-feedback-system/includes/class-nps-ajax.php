<?php
/**
 * AJAX processing of the NPS form submission.
 *
 * @package NPS_Feedback_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPS_Ajax {

	/**
	 * Registers the AJAX hooks (logged-in users and guests).
	 */
	public static function init() {
		add_action( 'wp_ajax_submit_nps_feedback', array( __CLASS__, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_submit_nps_feedback', array( __CLASS__, 'handle_submission' ) );
	}

	/**
	 * Validates, saves, and triggers the emails for the NPS form submission.
	 */
	public static function handle_submission() {
		check_ajax_referer( 'nps_feedback_nonce', 'nonce' );

		// Anti-spam honeypot: field hidden via inline style (doesn't depend on the
		// external CSS loading) that only bots tend to fill in. We pretend success
		// (without saving or sending anything) to avoid revealing to an automated
		// script that it was blocked.
		if ( ! empty( $_POST['nps_website'] ) ) {
			wp_send_json_success();
		}

		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$score  = isset( $_POST['score'] ) ? intval( $_POST['score'] ) : -1;
		$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( '' === $name || ! is_email( $email ) || $score < 0 || $score > 10 || '' === $reason ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid data. Please check the form and try again.', 'nps-feedback-system' ) )
			);
		}

		$data = compact( 'name', 'email', 'score', 'reason' );

		self::save_submission( $data );

		NPS_Mailer::send_admin_notification( $data );
		NPS_Mailer::send_customer_notification( $data );

		wp_send_json_success();
	}

	/**
	 * Saves the NPS submission as a Custom Post Type entry.
	 *
	 * @param array $data Already validated and sanitized form data.
	 * @return int|WP_Error ID of the created post, or WP_Error on failure.
	 */
	private static function save_submission( $data ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => sprintf(
					'%s (Score: %d)',
					'' !== $data['name'] ? $data['name'] : 'Customer',
					$data['score']
				),
				'post_content' => $data['reason'],
				'post_type'    => 'nps_submission',
				'post_status'  => 'publish',
				'post_author'  => 1,
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, 'nps_name', $data['name'] );
			update_post_meta( $post_id, 'nps_email', $data['email'] );
			update_post_meta( $post_id, 'nps_score', $data['score'] );
		}

		return $post_id;
	}
}
