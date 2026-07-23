<?php
/**
 * Plugin Name:       NPS Feedback System
 * Description:       NPS rating form via the [nps_form] shortcode, with submissions logged to a Custom Post Type, a settings screen (colors and emails), and conditional email notifications based on the score given.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Renan Alves
 * Author URI:        https://renanalves.com.br/
 * Text Domain:       nps-feedback-system
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direct access not allowed.
}

define( 'NPS_FEEDBACK_VERSION', '1.0.0' );
define( 'NPS_FEEDBACK_PATH', plugin_dir_path( __FILE__ ) );
define( 'NPS_FEEDBACK_URL', plugin_dir_url( __FILE__ ) );

require_once NPS_FEEDBACK_PATH . 'includes/class-nps-cpt.php';
require_once NPS_FEEDBACK_PATH . 'includes/class-nps-settings.php';
require_once NPS_FEEDBACK_PATH . 'includes/class-nps-mailer.php';
require_once NPS_FEEDBACK_PATH . 'includes/class-nps-ajax.php';

/**
 * Main bootstrap class for the plugin.
 */
final class NPS_Feedback_System {

	/**
	 * Singleton instance.
	 *
	 * @var NPS_Feedback_System|null
	 */
	private static $instance = null;

	/**
	 * Counter used to generate unique IDs per shortcode instance.
	 *
	 * @var int
	 */
	private static $instance_counter = 0;

	/**
	 * Returns the plugin's singleton instance.
	 *
	 * @return NPS_Feedback_System
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — registers the plugin's modules and hooks.
	 */
	private function __construct() {
		NPS_CPT::init();
		NPS_Settings::init();
		NPS_Mailer::init();
		NPS_Ajax::init();

		add_shortcode( 'nps_form', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Registers (without forcing the loading of) the form's assets.
	 * They are only actually enqueued when the shortcode is used.
	 */
	public function register_assets() {
		$opts = NPS_Settings::get_options();

		wp_register_style(
			'nps-feedback-style',
			NPS_FEEDBACK_URL . 'assets/css/nps-style.css',
			array(),
			NPS_FEEDBACK_VERSION
		);

		// Injects the colors configured in the settings screen as CSS variables.
		wp_add_inline_style(
			'nps-feedback-style',
			sprintf(
				'.nps-container{--nps-primary:%1$s;--nps-primary-hover:%2$s;}',
				esc_attr( $opts['primary_color'] ),
				esc_attr( $opts['hover_color'] )
			)
		);

		wp_register_script(
			'nps-feedback-script',
			NPS_FEEDBACK_URL . 'assets/js/nps-script.js',
			array(),
			NPS_FEEDBACK_VERSION,
			true
		);

		wp_localize_script(
			'nps-feedback-script',
			'NPSFeedback',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'nps_feedback_nonce' ),
				'googleReviewUrl' => esc_url( $opts['google_review_url'] ),
			)
		);
	}

	/**
	 * Callback for the [nps_form] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Form HTML.
	 */
	public function render_shortcode( $atts ) {
		wp_enqueue_style( 'nps-feedback-style' );
		wp_enqueue_script( 'nps-feedback-script' );

		$atts = shortcode_atts(
			array(
				'title' => 'Based on your experience with us, how likely are you to recommend us to a friend or colleague?',
			),
			$atts,
			'nps_form'
		);

		// Unique identifier to avoid ID collisions if the shortcode is used more than
		// once on the same page. Uses a manual counter (instead of wp_unique_id(), which
		// only exists from WP 5.9 onward) to keep compatibility with the "Requires at
		// least" declared in the plugin header.
		$uid = 'nps-' . ++self::$instance_counter;

		ob_start();
		include NPS_FEEDBACK_PATH . 'templates/nps-form-template.php';
		return ob_get_clean();
	}
}

NPS_Feedback_System::instance();

/**
 * Ensures rewrite rules are refreshed on activation/deactivation, since a
 * Custom Post Type is registered.
 */
if ( ! function_exists( 'nps_feedback_activate' ) ) {
	function nps_feedback_activate() {
		NPS_CPT::register_post_type();
		flush_rewrite_rules();
	}
}
register_activation_hook( __FILE__, 'nps_feedback_activate' );

if ( ! function_exists( 'nps_feedback_deactivate' ) ) {
	function nps_feedback_deactivate() {
		flush_rewrite_rules();
	}
}
register_deactivation_hook( __FILE__, 'nps_feedback_deactivate' );
