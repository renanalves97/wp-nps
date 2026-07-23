<?php
/**
 * NPS form template.
 *
 * Available variables (defined in NPS_Feedback_System::render_shortcode()):
 *
 * @var array  $atts Shortcode attributes (e.g. 'title').
 * @var string $uid  Unique identifier for this form instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="nps-container">
	<form id="<?php echo esc_attr( $uid ); ?>" class="nps-form" novalidate>
		<div class="nps-title"><?php echo esc_html( $atts['title'] ); ?></div>

		<div class="nps-scale">
			<?php for ( $i = 0; $i <= 10; $i++ ) : ?>
			<div class="nps-scale-item">
				<input
					type="radio"
					id="<?php echo esc_attr( $uid . '-score-' . $i ); ?>"
					name="score"
					value="<?php echo esc_attr( $i ); ?>"
					<?php echo 0 === $i ? 'required' : ''; ?>
				>
				<label for="<?php echo esc_attr( $uid . '-score-' . $i ); ?>"><?php echo esc_html( $i ); ?></label>
			</div>
			<?php endfor; ?>
		</div>

		<div class="nps-scale-labels">
			<span>0 — Not at all likely</span>
			<span>10 — Extremely likely</span>
		</div>

		<div class="nps-field">
			<label for="<?php echo esc_attr( $uid . '-name' ); ?>">Name</label>
			<input type="text" id="<?php echo esc_attr( $uid . '-name' ); ?>" name="name" placeholder="Your full name" required>
		</div>

		<div class="nps-field">
			<label for="<?php echo esc_attr( $uid . '-email' ); ?>">Email</label>
			<input type="email" id="<?php echo esc_attr( $uid . '-email' ); ?>" name="email" placeholder="your.email@example.com" required>
		</div>

		<div class="nps-field">
			<label for="<?php echo esc_attr( $uid . '-reason' ); ?>">In a few words, describe what led to your score.</label>
			<textarea id="<?php echo esc_attr( $uid . '-reason' ); ?>" name="reason" placeholder="Write your feedback here..." required></textarea>
		</div>

		<p style="position: absolute !important; left: -9999px !important; top: -9999px !important; width: 1px !important; height: 1px !important; overflow: hidden !important; margin: 0 !important; padding: 0 !important;" aria-hidden="true">
			<label for="<?php echo esc_attr( $uid . '-website' ); ?>">Website</label>
			<input type="text" id="<?php echo esc_attr( $uid . '-website' ); ?>" name="nps_website" tabindex="-1" autocomplete="off">
		</p>

		<button type="submit" class="nps-btn">Submit</button>

		<div class="nps-message" role="status" aria-live="polite"></div>
	</form>
</div>
