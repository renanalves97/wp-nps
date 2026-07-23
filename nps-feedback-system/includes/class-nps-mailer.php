<?php
/**
 * Sends internal (team) and external (customer) emails for NPS submissions.
 * Content, recipients, and colors are managed on the plugin's Settings screen.
 *
 * @package NPS_Feedback_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPS_Mailer {

	/**
	 * Registers the module's hooks (logging of send failures).
	 */
	public static function init() {
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_failure' ) );
	}

	/**
	 * Sends the internal email with the submission summary to the team.
	 *
	 * @param array $data Submission data (name, email, score, reason).
	 */
	public static function send_admin_notification( $data ) {
		$opts = NPS_Settings::get_options();

		$recipients = array_map( 'trim', explode( ',', $opts['admin_email'] ) );
		$recipients = apply_filters( 'nps_feedback_admin_recipients', $recipients, $data );

		$subject = sprintf( 'NPS Submission: Score %d - %s', $data['score'], $data['name'] );

		$message  = "New NPS submission received:\n\n";
		$message .= "Name: {$data['name']}\n";
		$message .= "Email: {$data['email']}\n";
		$message .= "Score: {$data['score']}/10\n";
		$message .= "Reason: \"{$data['reason']}\"\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$sent = self::send( $recipients, $subject, $message, $headers, $opts['sender_name'] );

		if ( ! $sent ) {
			self::log( 'Failed to send internal notification to: ' . implode( ', ', $recipients ) );
		}
	}

	/**
	 * Sends the thank-you email to the customer, with content conditional on the score.
	 * Score >= 8 (promoter): includes the public Google review button, if configured.
	 * Score < 8: simple acknowledgment of the feedback.
	 *
	 * @param array $data Submission data (name, email, score, reason).
	 */
	public static function send_customer_notification( $data ) {
		$opts = NPS_Settings::get_options();

		// Intentionally, we do NOT set a "From" or "Reply-To" header here with an
		// email address different from the technical one used by the server: a
		// sender that doesn't match the site's domain (e.g. a Gmail/Outlook address
		// configured as the "Admin Email") violates those providers' DMARC policy and
		// causes the receiving server to silently drop the message. The sender's
		// display name is safe to customize (it doesn't affect SPF/DKIM/DMARC) and is
		// applied via a filter in self::send().
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$is_promoter = $data['score'] >= 8;

		$subject = $is_promoter ? $opts['email_promoter_subject'] : $opts['email_detractor_subject'];
		$body    = self::build_email_body(
			$is_promoter ? $opts['email_promoter_body'] : $opts['email_detractor_body'],
			$data,
			$opts,
			$is_promoter
		);

		$sent = self::send( $data['email'], $subject, $body, $headers, $opts['sender_name'] );

		if ( ! $sent ) {
			self::log( 'Failed to send email to customer: ' . $data['email'] );
		}
	}

	/**
	 * Sends the email via wp_mail(), customizing only the sender's display NAME
	 * (via the native 'wp_mail_from_name' filter). The technical email address used
	 * to send is not changed, to preserve deliverability.
	 *
	 * @param string|array $to          Recipient(s).
	 * @param string       $subject     Email subject.
	 * @param string       $message     Email body.
	 * @param array        $headers     Email headers.
	 * @param string       $sender_name Name to display as the sender.
	 * @return bool Result of wp_mail().
	 */
	private static function send( $to, $subject, $message, $headers, $sender_name ) {
		$set_name = function () use ( $sender_name ) {
			return $sender_name;
		};

		add_filter( 'wp_mail_from_name', $set_name );
		$sent = wp_mail( $to, $subject, $message, $headers );
		remove_filter( 'wp_mail_from_name', $set_name );

		return $sent;
	}

	/**
	 * Logs send failures to the PHP error log, to help with diagnostics
	 * (e.g. SPF/DKIM rejection, invalid SMTP credentials, etc.).
	 *
	 * @param WP_Error $wp_error Error returned by PHPMailer via the 'wp_mail_failed' hook.
	 */
	public static function log_failure( $wp_error ) {
		self::log( 'wp_mail_failed: ' . $wp_error->get_error_message() );
	}

	/**
	 * Writes a line to the PHP error log, prefixed for easy identification.
	 *
	 * @param string $message Message to log.
	 */
	private static function log( $message ) {
		error_log( '[NPS Feedback System] ' . $message );
	}

	/**
	 * Builds the customer email's HTML, replicating the original template's
	 * structure (colored header, greeting, free text, summary box, call to
	 * action/alternative link, divider, and signature). Only the intro text
	 * ({@see $intro_text}) is set by the user on the Settings screen; the rest
	 * is always the same, to guarantee consistent formatting across sends.
	 *
	 * @param string $intro_text  Intro paragraph(s) configured in the settings.
	 * @param array  $data        Submission data (name, email, score, reason).
	 * @param array  $opts        Plugin options (colors, Google URL, etc.).
	 * @param bool   $is_promoter If true, shows the Google review invite/button (when a URL is configured).
	 * @return string Final email HTML.
	 */
	private static function build_email_body( $intro_text, $data, $opts, $is_promoter ) {
		$primary    = esc_attr( $opts['primary_color'] );
		$google_url = esc_url( $opts['google_review_url'] );

		$intro_text = wp_kses_post( strtr( $intro_text, array( '{name}' => esc_html( $data['name'] ) ) ) );

		// Highlighted box with the submission summary — identical to the original template.
		$summary = sprintf(
			'<div style="background-color: #f8fafc; border-left: 4px solid %1$s; padding: 16px; margin: 24px 0; border-radius: 4px;">' .
				'<h4 style="margin: 0 0 12px 0; color: %1$s; font-size: 14px; text-transform: uppercase;">Copy of your submission:</h4>' .
				'<p style="margin: 4px 0;"><strong>Likelihood to recommend:</strong> %2$s / 10</p>' .
				'<p style="margin: 4px 0;"><strong>What led to your score:</strong> &quot;%3$s&quot;</p>' .
			'</div>',
			$primary,
			esc_html( $data['score'] ),
			esc_html( $data['reason'] )
		);

		if ( $is_promoter && ! empty( $google_url ) ) {
			// Invite + button + alternative link — identical to the original template.
			$cta = sprintf(
				'<p>If you haven\'t already, we would be very grateful if you could also leave your public review on Google. Your opinion helps many other people learn about our work:</p>' .
				'<div style="text-align: center; margin: 24px 0;">' .
					'<a href="%1$s" target="_blank" style="background-color: %2$s; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; display: inline-block;">Rate us on Google</a>' .
				'</div>' .
				'<p style="font-size: 13px; color: #666666; text-align: center;">' .
					'If the button doesn\'t work, use this link instead:<br>' .
					'<a href="%1$s" style="color: %2$s;">%1$s</a>' .
				'</p>',
				$google_url,
				$primary
			);
		} elseif ( ! $is_promoter ) {
			$cta = '<p>If you need any further clarification, we remain fully available to help.</p>';
		} else {
			// Promoter, but no Google Review URL configured — skip the call to action.
			$cta = '';
		}

		$header_title = $is_promoter ? 'Thank you for your feedback!' : 'We received your message';

		ob_start();
		?>
		<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333333; line-height: 1.6; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
			<div style="background-color: <?php echo $primary; ?>; color: #ffffff; padding: 24px; text-align: center;">
				<h2 style="margin: 0; font-size: 20px;"><?php echo esc_html( $header_title ); ?></h2>
			</div>
			<div style="padding: 24px;">
				<p>Hello, <strong><?php echo esc_html( $data['name'] ); ?></strong>!</p>
				<?php echo $intro_text; ?>
				<?php echo $summary; ?>
				<?php echo $cta; ?>
				<hr style="border: 0; border-top: 1px solid #eeeeee; margin: 24px 0;">
				<p style="margin-bottom: 0;">Best regards,<br><strong>Our team</strong></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
