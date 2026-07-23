<?php
/**
 * Processamento AJAX da submissão do formulário NPS.
 *
 * @package NPS_Feedback_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPS_Ajax {

	/**
	 * Regista os hooks AJAX (utilizadores autenticados e visitantes).
	 */
	public static function init() {
		add_action( 'wp_ajax_enviar_nps_wp', array( __CLASS__, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_enviar_nps_wp', array( __CLASS__, 'handle_submission' ) );
	}

	/**
	 * Valida, guarda e despoleta os e-mails da submissão do formulário NPS.
	 */
	public static function handle_submission() {
		check_ajax_referer( 'nps_feedback_nonce', 'nonce' );

		// Honeypot anti-spam: campo escondido via style inline (não depende do CSS
		// externo carregar) que só bots costumam preencher. Fingimos sucesso (sem
		// guardar nem enviar nada) para não revelar a um script automatizado que
		// foi bloqueado.
		if ( ! empty( $_POST['nps_website'] ) ) {
			wp_send_json_success();
		}

		$nome   = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$nota   = isset( $_POST['nota'] ) ? intval( $_POST['nota'] ) : -1;
		$motivo = isset( $_POST['motivo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['motivo'] ) ) : '';

		if ( '' === $nome || ! is_email( $email ) || $nota < 0 || $nota > 10 || '' === $motivo ) {
			wp_send_json_error(
				array( 'message' => __( 'Dados inválidos. Por favor, verifique o formulário e tente novamente.', 'nps-feedback-system' ) )
			);
		}

		$dados = compact( 'nome', 'email', 'nota', 'motivo' );

		self::guardar_avaliacao( $dados );

		NPS_Mailer::send_admin_notification( $dados );
		NPS_Mailer::send_customer_notification( $dados );

		wp_send_json_success();
	}

	/**
	 * Regista a avaliação NPS como Custom Post Type.
	 *
	 * @param array $dados Dados já validados e sanitizados do formulário.
	 * @return int|WP_Error ID do post criado, ou WP_Error em caso de falha.
	 */
	private static function guardar_avaliacao( $dados ) {
		$post_id = wp_insert_post(
			array(
				'post_title'   => sprintf(
					'%s (Nota: %d)',
					'' !== $dados['nome'] ? $dados['nome'] : 'Cliente',
					$dados['nota']
				),
				'post_content' => $dados['motivo'],
				'post_type'    => 'nps_avaliacao',
				'post_status'  => 'publish',
				'post_author'  => 1,
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, 'nps_nome', $dados['nome'] );
			update_post_meta( $post_id, 'nps_email', $dados['email'] );
			update_post_meta( $post_id, 'nps_nota', $dados['nota'] );
		}

		return $post_id;
	}
}
