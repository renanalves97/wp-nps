<?php
/**
 * Disparo de e-mails internos (equipa) e externos (cliente) da avaliação NPS.
 * Conteúdo, destinatários e cores são geridos no ecrã de Configurações do plugin.
 *
 * @package NPS_Feedback_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPS_Mailer {

	/**
	 * Regista os hooks do módulo (registo de falhas de envio).
	 */
	public static function init() {
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_failure' ) );
	}

	/**
	 * Envia o e-mail interno com o resumo da avaliação para a equipa.
	 *
	 * @param array $dados Dados da avaliação (nome, email, nota, motivo).
	 */
	public static function send_admin_notification( $dados ) {
		$opts = NPS_Settings::get_options();

		$destinatarios = array_map( 'trim', explode( ',', $opts['admin_email'] ) );
		$destinatarios = apply_filters( 'nps_feedback_admin_recipients', $destinatarios, $dados );

		$assunto = sprintf( 'Avaliação NPS: Nota %d - %s', $dados['nota'], $dados['nome'] );

		$mensagem  = "Novo registo de NPS recebido:\n\n";
		$mensagem .= "Nome: {$dados['nome']}\n";
		$mensagem .= "E-mail: {$dados['email']}\n";
		$mensagem .= "Nota: {$dados['nota']}/10\n";
		$mensagem .= "Motivo: \"{$dados['motivo']}\"\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$enviado = self::enviar( $destinatarios, $assunto, $mensagem, $headers, $opts['sender_name'] );

		if ( ! $enviado ) {
			self::log( 'Falha ao enviar notificação interna para: ' . implode( ', ', $destinatarios ) );
		}
	}

	/**
	 * Envia o e-mail de agradecimento ao cliente, com conteúdo condicional à nota.
	 * Nota >= 8 (promotor): inclui o botão de avaliação pública no Google.
	 * Nota < 8: agradecimento simples pelo feedback.
	 *
	 * @param array $dados Dados da avaliação (nome, email, nota, motivo).
	 */
	public static function send_customer_notification( $dados ) {
		$opts = NPS_Settings::get_options();

		// De propósito, NÃO definimos aqui nenhum cabeçalho "From" ou "Reply-To" com um
		// endereço de e-mail diferente do técnico usado pelo servidor: um remetente que
		// não corresponda ao domínio do site (ex: um Gmail/Outlook configurado como
		// "E-mail do Administrador") viola o DMARC desses provedores e faz com que o
		// servidor de destino descarte a mensagem em silêncio. O nome de exibição do
		// remetente é seguro de personalizar (não afeta SPF/DKIM/DMARC) e é aplicado
		// via filtro em self::enviar().
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$is_promotor = $dados['nota'] >= 8;

		$assunto = $is_promotor ? $opts['email_promotor_assunto'] : $opts['email_detrator_assunto'];
		$corpo   = self::build_email_body(
			$is_promotor ? $opts['email_promotor_corpo'] : $opts['email_detrator_corpo'],
			$dados,
			$opts,
			$is_promotor
		);

		$enviado = self::enviar( $dados['email'], $assunto, $corpo, $headers, $opts['sender_name'] );

		if ( ! $enviado ) {
			self::log( 'Falha ao enviar e-mail ao cliente: ' . $dados['email'] );
		}
	}

	/**
	 * Envia o e-mail usando wp_mail(), personalizando apenas o NOME de exibição do
	 * remetente (via o filtro nativo 'wp_mail_from_name'). O endereço de e-mail
	 * técnico usado para enviar não é alterado, para preservar a entregabilidade.
	 *
	 * @param string|array $to          Destinatário(s).
	 * @param string       $assunto     Assunto do e-mail.
	 * @param string       $mensagem    Corpo do e-mail.
	 * @param array        $headers     Cabeçalhos do e-mail.
	 * @param string       $sender_name Nome a exibir como remetente.
	 * @return bool Resultado de wp_mail().
	 */
	private static function enviar( $to, $assunto, $mensagem, $headers, $sender_name ) {
		$definir_nome = function () use ( $sender_name ) {
			return $sender_name;
		};

		add_filter( 'wp_mail_from_name', $definir_nome );
		$enviado = wp_mail( $to, $assunto, $mensagem, $headers );
		remove_filter( 'wp_mail_from_name', $definir_nome );

		return $enviado;
	}

	/**
	 * Regista falhas de envio no log de erros do PHP, para facilitar o diagnóstico
	 * (ex: rejeição por SPF/DKIM, credenciais SMTP inválidas, etc.).
	 *
	 * @param WP_Error $wp_error Erro devolvido pelo PHPMailer via a hook 'wp_mail_failed'.
	 */
	public static function log_failure( $wp_error ) {
		self::log( 'wp_mail_failed: ' . $wp_error->get_error_message() );
	}

	/**
	 * Escreve uma linha no log de erros do PHP, prefixada para fácil identificação.
	 *
	 * @param string $mensagem Mensagem a registar.
	 */
	private static function log( $mensagem ) {
		error_log( '[NPS Feedback System] ' . $mensagem );
	}

	/**
	 * Monta o HTML do e-mail ao cliente, replicando exatamente a estrutura do
	 * template original (cabeçalho colorido, saudação, texto livre, quadro de
	 * resumo, chamada para ação/link alternativo, separador e assinatura).
	 * Apenas o texto de introdução ({@see $texto_livre}) é definido pelo
	 * utilizador no ecrã de Configurações; o resto é sempre igual, para garantir
	 * a mesma formatação em qualquer envio.
	 *
	 * @param string $texto_livre  Parágrafo(s) de introdução configurados nas definições.
	 * @param array  $dados        Dados da avaliação (nome, email, nota, motivo).
	 * @param array  $opts         Opções do plugin (cores, URL do Google, etc.).
	 * @param bool   $is_promotor  Se verdadeiro, mostra o convite/botão de avaliação no Google.
	 * @return string HTML final do e-mail.
	 */
	private static function build_email_body( $texto_livre, $dados, $opts, $is_promotor ) {
		$primary    = esc_attr( $opts['primary_color'] );
		$google_url = esc_url( $opts['google_review_url'] );

		$texto_livre = wp_kses_post( strtr( $texto_livre, array( '{nome}' => esc_html( $dados['nome'] ) ) ) );

		// Quadro destacado com o resumo da avaliação — idêntico ao template original.
		$resumo = sprintf(
			'<div style="background-color: #f8fafc; border-left: 4px solid %1$s; padding: 16px; margin: 24px 0; border-radius: 4px;">' .
				'<h4 style="margin: 0 0 12px 0; color: %1$s; font-size: 14px; text-transform: uppercase;">Cópia do seu registo:</h4>' .
				'<p style="margin: 4px 0;"><strong>Probabilidade de recomendação:</strong> %2$s / 10</p>' .
				'<p style="margin: 4px 0;"><strong>O que motivou a sua nota:</strong> &quot;%3$s&quot;</p>' .
			'</div>',
			$primary,
			esc_html( $dados['nota'] ),
			esc_html( $dados['motivo'] )
		);

		if ( $is_promotor ) {
			// Convite + botão + link alternativo — idêntico ao template original.
			$cta = sprintf(
				'<p>Se ainda não o fez, ficaríamos muito gratos se deixasse também a sua avaliação pública no Google. A sua opinião ajuda muitas outras pessoas a conhecerem o nosso trabalho:</p>' .
				'<div style="text-align: center; margin: 24px 0;">' .
					'<a href="%1$s" target="_blank" style="background-color: %2$s; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; display: inline-block;">Avaliar no Google</a>' .
				'</div>' .
				'<p style="font-size: 13px; color: #666666; text-align: center;">' .
					'Se o botão não funcionar, aceda através da hiperligação:<br>' .
					'<a href="%1$s" style="color: %2$s;">%1$s</a>' .
				'</p>',
				$google_url,
				$primary
			);
		} else {
			$cta = '<p>Se necessitar de algum esclarecimento adicional, estamos à sua inteira disposição.</p>';
		}

		$titulo_cabecalho = $is_promotor ? 'Agradecemos o seu feedback!' : 'Recebemos a sua mensagem';

		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333333; line-height: 1.6; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
			<div style="background-color: <?php echo $primary; ?>; color: #ffffff; padding: 24px; text-align: center;">
				<h2 style="margin: 0; font-size: 20px;"><?php echo esc_html( $titulo_cabecalho ); ?></h2>
			</div>
			<div style="padding: 24px;">
				<p>Olá, <strong><?php echo esc_html( $dados['nome'] ); ?></strong>!</p>
				<?php echo $texto_livre; ?>
				<?php echo $resumo; ?>
				<?php echo $cta; ?>
				<hr style="border: 0; border-top: 1px solid #eeeeee; margin: 24px 0;">
				<p style="margin-bottom: 0;">Com os melhores cumprimentos,<br><strong>A nossa equipa</strong></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
