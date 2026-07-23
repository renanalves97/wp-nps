<?php
/**
 * Plugin settings screen: colors, internal notification email, and the
 * content of the emails sent to the customer.
 *
 * @package NPS_Feedback_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPS_Settings {

	const OPTION_KEY  = 'nps_feedback_options';
	const GROUP       = 'nps_feedback_settings_group';
	const PAGE_SLUG   = 'nps-feedback-settings';

	/**
	 * Registers the module's hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Default values for all of the plugin's options.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'primary_color'          => '#0f3b66',
			'hover_color'            => '#0a2948',
			'sender_name'            => get_bloginfo( 'name' ),
			'admin_email'            => get_option( 'admin_email' ),
			'google_review_url'      => '',
			'email_promoter_subject' => 'Thank you for your feedback',
			'email_promoter_body'    => "<p>Thank you so much for your excellent rating. We're delighted to know you had such a positive experience with us.</p>\n<p>Your trust and recognition are a great motivation for us to keep improving every day.</p>",
			'email_detractor_subject' => 'We appreciate your feedback',
			'email_detractor_body'    => "<p>We sincerely appreciate the time you took to rate our services and share your opinion with us.</p>\n<p>Your feedback is essential for us to identify areas for improvement and offer an ever better experience. Our team will review your comments carefully.</p>",
		);
	}

	/**
	 * Returns the saved options, falling back to the default values.
	 *
	 * @return array
	 */
	public static function get_options() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * Adds the settings submenu under the "NPS Submissions" menu.
	 */
	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=nps_submission',
			__( 'NPS Settings', 'nps-feedback-system' ),
			__( 'Settings', 'nps-feedback-system' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Registers the option, sections, and fields via the Settings API.
	 */
	public static function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);

		add_settings_section( 'nps_section_appearance', __( 'Appearance', 'nps-feedback-system' ), '__return_false', self::PAGE_SLUG );
		self::add_color_field( 'primary_color', __( 'Primary Color', 'nps-feedback-system' ), 'nps_section_appearance' );
		self::add_color_field( 'hover_color', __( 'Hover Color (button)', 'nps-feedback-system' ), 'nps_section_appearance' );

		add_settings_section( 'nps_section_notifications', __( 'Notifications', 'nps-feedback-system' ), '__return_false', self::PAGE_SLUG );
		self::add_text_field(
			'sender_name',
			__( 'Sender Name', 'nps-feedback-system' ),
			'nps_section_notifications',
			__( 'Name shown as the sender of the emails (e.g., your company name). The technical sending address used by the server is kept, to ensure emails keep being delivered.', 'nps-feedback-system' )
		);
		self::add_text_field(
			'admin_email',
			__( 'Admin Email(s)', 'nps-feedback-system' ),
			'nps_section_notifications',
			__( 'Receives a notification for every submitted rating. For multiple addresses, separate them with a comma.', 'nps-feedback-system' )
		);
		self::add_text_field(
			'google_review_url',
			__( 'Google Review URL', 'nps-feedback-system' ),
			'nps_section_notifications',
			__( 'Used by the review button sent to promoter customers (score ≥ 8). Leave blank to hide the review button/link from the form and emails.', 'nps-feedback-system' )
		);

		$placeholders_desc = __( 'This text appears right after the greeting ("Hello, {name}!"). The score/reason summary box, the Google button, and the closing signature are added automatically, just like in the original template — no need to write them here.', 'nps-feedback-system' );

		add_settings_section( 'nps_section_promoter', __( 'Customer Email — Promoters (score ≥ 8)', 'nps-feedback-system' ), '__return_false', self::PAGE_SLUG );
		self::add_text_field( 'email_promoter_subject', __( 'Subject', 'nps-feedback-system' ), 'nps_section_promoter' );
		self::add_editor_field(
			'email_promoter_body',
			__( 'Intro Text', 'nps-feedback-system' ),
			'nps_section_promoter',
			$placeholders_desc
		);

		add_settings_section( 'nps_section_detractor', __( 'Customer Email — Detractors/Neutrals (score < 8)', 'nps-feedback-system' ), '__return_false', self::PAGE_SLUG );
		self::add_text_field( 'email_detractor_subject', __( 'Subject', 'nps-feedback-system' ), 'nps_section_detractor' );
		self::add_editor_field( 'email_detractor_body', __( 'Intro Text', 'nps-feedback-system' ), 'nps_section_detractor', $placeholders_desc );
	}

	/**
	 * Registers a color field.
	 *
	 * @param string $field   Option key.
	 * @param string $label   Field label.
	 * @param string $section Section it belongs to.
	 */
	private static function add_color_field( $field, $label, $section ) {
		add_settings_field(
			$field,
			$label,
			array( __CLASS__, 'field_color' ),
			self::PAGE_SLUG,
			$section,
			array( 'field' => $field )
		);
	}

	/**
	 * Registers a plain text field.
	 *
	 * @param string $field   Option key.
	 * @param string $label   Field label.
	 * @param string $section Section it belongs to.
	 * @param string $desc    Helper text (optional).
	 */
	private static function add_text_field( $field, $label, $section, $desc = '' ) {
		add_settings_field(
			$field,
			$label,
			array( __CLASS__, 'field_text' ),
			self::PAGE_SLUG,
			$section,
			array(
				'field' => $field,
				'desc'  => $desc,
			)
		);
	}

	/**
	 * Registers a rich text editor field (wp_editor).
	 *
	 * @param string $field   Option key.
	 * @param string $label   Field label.
	 * @param string $section Section it belongs to.
	 * @param string $desc    Helper text (optional).
	 */
	private static function add_editor_field( $field, $label, $section, $desc = '' ) {
		add_settings_field(
			$field,
			$label,
			array( __CLASS__, 'field_editor' ),
			self::PAGE_SLUG,
			$section,
			array(
				'field' => $field,
				'desc'  => $desc,
			)
		);
	}

	/**
	 * Renders a color field (color picker).
	 *
	 * @param array $args Field arguments.
	 */
	public static function field_color( $args ) {
		$opts     = self::get_options();
		$key      = $args['field'];
		$defaults = self::defaults();

		printf(
			'<input type="text" class="nps-color-picker" name="%1$s[%2$s]" value="%3$s" data-default-color="%4$s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $opts[ $key ] ),
			esc_attr( $defaults[ $key ] )
		);
	}

	/**
	 * Renders a plain text field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function field_text( $args ) {
		$opts = self::get_options();
		$key  = $args['field'];

		printf(
			'<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" />',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $opts[ $key ] )
		);

		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
		}
	}

	/**
	 * Renders a rich text editor field (wp_editor).
	 *
	 * @param array $args Field arguments.
	 */
	public static function field_editor( $args ) {
		$opts = self::get_options();
		$key  = $args['field'];

		// wp_editor doesn't allow hyphens/underscores in the editor ID.
		$editor_id = str_replace( '_', '', $key );

		wp_editor(
			$opts[ $key ],
			$editor_id,
			array(
				'textarea_name' => self::OPTION_KEY . '[' . $key . ']',
				'textarea_rows' => 10,
				'media_buttons' => false,
				'teeny'         => true,
			)
		);

		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . wp_kses_post( $args['desc'] ) . '</p>';
		}
	}

	/**
	 * Sanitizes the data submitted from the settings screen.
	 *
	 * @param array $input Raw submitted data.
	 * @return array Sanitized data.
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$output   = array();
		$input    = is_array( $input ) ? $input : array();

		foreach ( array( 'primary_color', 'hover_color' ) as $key ) {
			$color            = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$output[ $key ]   = $color ? $color : $defaults[ $key ];
		}

		$output['sender_name'] = ! empty( $input['sender_name'] ) ? sanitize_text_field( $input['sender_name'] ) : $defaults['sender_name'];

		$emails               = isset( $input['admin_email'] ) ? explode( ',', $input['admin_email'] ) : array();
		$emails               = array_filter( array_map( 'sanitize_email', array_map( 'trim', $emails ) ), 'is_email' );
		$output['admin_email'] = ! empty( $emails ) ? implode( ', ', $emails ) : $defaults['admin_email'];

		$output['google_review_url'] = ! empty( $input['google_review_url'] )
			? esc_url_raw( $input['google_review_url'] )
			: '';

		foreach ( array( 'email_promoter_subject', 'email_detractor_subject' ) as $key ) {
			$output[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $defaults[ $key ];
		}

		foreach ( array( 'email_promoter_body', 'email_detractor_body' ) as $key ) {
			$output[ $key ] = isset( $input[ $key ] ) ? wp_kses_post( $input[ $key ] ) : $defaults[ $key ];
		}

		return $output;
	}

	/**
	 * Loads the native WordPress color picker only on the plugin's settings page.
	 *
	 * @param string $hook Current admin page slug.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== $_GET['page'] ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'nps-feedback-admin',
			NPS_FEEDBACK_URL . 'assets/js/nps-admin.js',
			array( 'wp-color-picker' ),
			NPS_FEEDBACK_VERSION,
			true
		);
	}

	/**
	 * Renders the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Settings — NPS Feedback System', 'nps-feedback-system' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save Changes', 'nps-feedback-system' ) );
				?>
			</form>
		</div>
		<?php
	}
}
