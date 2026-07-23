<?php
/**
 * Plugin Name:       NPS Feedback System
 * Description:       Formulário de avaliação NPS via shortcode [formulario_nps], com registo das submissões num Custom Post Type, ecrã de configurações (cores e e-mails) e envio de e-mails condicionais com base na nota atribuída.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Renan Alves
 * Author URI:        https://renanalves.com.br/
 * Text Domain:       nps-feedback-system
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Acesso direto não permitido.
}

define( 'NPS_FEEDBACK_VERSION', '1.0.0' );
define( 'NPS_FEEDBACK_PATH', plugin_dir_path( __FILE__ ) );
define( 'NPS_FEEDBACK_URL', plugin_dir_url( __FILE__ ) );

require_once NPS_FEEDBACK_PATH . 'includes/class-nps-cpt.php';
require_once NPS_FEEDBACK_PATH . 'includes/class-nps-settings.php';
require_once NPS_FEEDBACK_PATH . 'includes/class-nps-mailer.php';
require_once NPS_FEEDBACK_PATH . 'includes/class-nps-ajax.php';

/**
 * Classe principal de arranque do plugin.
 */
final class NPS_Feedback_System {

	/**
	 * Instância única (singleton).
	 *
	 * @var NPS_Feedback_System|null
	 */
	private static $instance = null;

	/**
	 * Contador usado para gerar IDs únicos por instância do shortcode.
	 *
	 * @var int
	 */
	private static $contador_instancias = 0;

	/**
	 * Devolve a instância única do plugin.
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
	 * Construtor privado — regista os módulos e hooks do plugin.
	 */
	private function __construct() {
		NPS_CPT::init();
		NPS_Settings::init();
		NPS_Mailer::init();
		NPS_Ajax::init();

		add_shortcode( 'formulario_nps', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Regista (sem forçar o carregamento de) os assets do formulário.
	 * São efetivamente colocados em fila apenas quando o shortcode é usado.
	 */
	public function register_assets() {
		$opts = NPS_Settings::get_options();

		wp_register_style(
			'nps-feedback-style',
			NPS_FEEDBACK_URL . 'assets/css/nps-style.css',
			array(),
			NPS_FEEDBACK_VERSION
		);

		// Injeta as cores configuradas no ecrã de definições como variáveis CSS.
		wp_add_inline_style(
			'nps-feedback-style',
			sprintf(
				'.sentir-nps-container{--nps-primary:%1$s;--nps-primary-hover:%2$s;}',
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
	 * Callback do shortcode [formulario_nps].
	 *
	 * @param array $atts Atributos do shortcode.
	 * @return string HTML do formulário.
	 */
	public function render_shortcode( $atts ) {
		wp_enqueue_style( 'nps-feedback-style' );
		wp_enqueue_script( 'nps-feedback-script' );

		$atts = shortcode_atts(
			array(
				'titulo' => 'Tendo em conta a sua experiência connosco, qual a probabilidade de nos recomendar a um familiar ou amigo?',
			),
			$atts,
			'formulario_nps'
		);

		// Identificador único para evitar colisões de IDs se o shortcode for usado mais que uma vez na
		// mesma página. Usa um contador manual (em vez de wp_unique_id(), que só existe a partir do
		// WP 5.9) para manter compatibilidade com o "Requires at least" declarado no cabeçalho do plugin.
		$uid = 'nps-' . ++self::$contador_instancias;

		ob_start();
		include NPS_FEEDBACK_PATH . 'templates/nps-form-template.php';
		return ob_get_clean();
	}
}

NPS_Feedback_System::instance();

/**
 * Garante que as regras de reescrita são atualizadas ao ativar/desativar o plugin,
 * já que é registado um Custom Post Type.
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
