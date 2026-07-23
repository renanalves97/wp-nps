<?php
/**
 * Template do formulário NPS.
 *
 * Variáveis disponíveis (definidas em NPS_Feedback_System::render_shortcode()):
 *
 * @var array  $atts Atributos do shortcode (ex: 'titulo').
 * @var string $uid  Identificador único desta instância do formulário.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="sentir-nps-container">
	<form id="<?php echo esc_attr( $uid ); ?>" class="sentir-nps-form" novalidate>
		<div class="sentir-nps-title"><?php echo esc_html( $atts['titulo'] ); ?></div>

		<div class="sentir-nps-scale">
			<?php for ( $i = 0; $i <= 10; $i++ ) : ?>
			<div class="sentir-nps-scale-item">
				<input
					type="radio"
					id="<?php echo esc_attr( $uid . '-nota-' . $i ); ?>"
					name="nota"
					value="<?php echo esc_attr( $i ); ?>"
					<?php echo 0 === $i ? 'required' : ''; ?>
				>
				<label for="<?php echo esc_attr( $uid . '-nota-' . $i ); ?>"><?php echo esc_html( $i ); ?></label>
			</div>
			<?php endfor; ?>
		</div>

		<div class="sentir-nps-scale-labels">
			<span>0 — Nada provável</span>
			<span>10 — Extremamente provável</span>
		</div>

		<div class="sentir-nps-field">
			<label for="<?php echo esc_attr( $uid . '-nome' ); ?>">Nome</label>
			<input type="text" id="<?php echo esc_attr( $uid . '-nome' ); ?>" name="nome" placeholder="Seu nome completo" required>
		</div>

		<div class="sentir-nps-field">
			<label for="<?php echo esc_attr( $uid . '-email' ); ?>">Email</label>
			<input type="email" id="<?php echo esc_attr( $uid . '-email' ); ?>" name="email" placeholder="seu.email@exemplo.com" required>
		</div>

		<div class="sentir-nps-field">
			<label for="<?php echo esc_attr( $uid . '-motivo' ); ?>">Em poucas palavras, descreva o que motivou a sua nota.</label>
			<textarea id="<?php echo esc_attr( $uid . '-motivo' ); ?>" name="motivo" placeholder="Escreva aqui a sua opinião..." required></textarea>
		</div>

		<p style="position: absolute !important; left: -9999px !important; top: -9999px !important; width: 1px !important; height: 1px !important; overflow: hidden !important; margin: 0 !important; padding: 0 !important;" aria-hidden="true">
			<label for="<?php echo esc_attr( $uid . '-website' ); ?>">Website</label>
			<input type="text" id="<?php echo esc_attr( $uid . '-website' ); ?>" name="nps_website" tabindex="-1" autocomplete="off">
		</p>

		<button type="submit" class="sentir-nps-btn">Submeter</button>

		<div class="sentir-nps-message" role="status" aria-live="polite"></div>
	</form>
</div>
