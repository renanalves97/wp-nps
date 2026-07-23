<?php
/**
 * Ecrã de configurações do plugin: cores, e-mail de notificação interna
 * e conteúdo dos e-mails enviados ao cliente.
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
	 * Regista os hooks do módulo.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Valores por omissão de todas as opções do plugin.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'primary_color'          => '#0f3b66',
			'hover_color'            => '#0a2948',
			'sender_name'            => get_bloginfo( 'name' ),
			'admin_email'            => get_option( 'admin_email' ),
			'google_review_url'      => 'https://g.page/r/CXo3aAtkfExDEBM/review',
			'email_promotor_assunto' => 'Agradecemos o seu feedback',
			'email_promotor_corpo'   => "<p>Muito obrigado pela sua excelente avaliação. Ficamos muito felizes por saber que teve uma experiência tão positiva connosco.</p>\n<p>A sua confiança e o seu reconhecimento são uma grande motivação para continuarmos a melhorar todos os dias.</p>",
			'email_detrator_assunto' => 'Agradecemos a sua opinião',
			'email_detrator_corpo'   => "<p>Agradecemos sinceramente o tempo dedicado a avaliar os nossos serviços e a partilhar a sua opinião connosco.</p>\n<p>O seu feedback é essencial para identificarmos pontos de melhoria e podermos oferecer uma experiência cada vez melhor. A nossa equipa irá analisar o seu comentário com toda a atenção.</p>",
		);
	}

	/**
	 * Devolve as opções gravadas, com fallback para os valores por omissão.
	 *
	 * @return array
	 */
	public static function get_options() {
		$saved = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * Adiciona o submenu de configurações dentro do menu "Avaliações NPS".
	 */
	public static function register_menu() {
		add_submenu_page(
			'edit.php?post_type=nps_avaliacao',
			__( 'Configurações NPS', 'nps-feedback-system' ),
			__( 'Configurações', 'nps-feedback-system' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Regista a opção, secções e campos através da Settings API.
	 */
	public static function register_settings() {
		register_setting(
			self::GROUP,
			self::OPTION_KEY,
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);

		add_settings_section( 'nps_section_aparencia', __( 'Aparência', 'nps-feedback-system' ), '__return_false', self::PAGE_SLUG );
		self::add_color_field( 'primary_color', __( 'Cor Primária', 'nps-feedback-system' ), 'nps_section_aparencia' );
		self::add_color_field( 'hover_color', __( 'Cor de Hover (botão)', 'nps-feedback-system' ), 'nps_section_aparencia' );

		add_settings_section( 'nps_section_notificacoes', __( 'Notificações', 'nps-feedback-system' ), '__return_false', self::PAGE_SLUG );
		self::add_text_field(
			'sender_name',
			__( 'Nome do Remetente', 'nps-feedback-system' ),
			'nps_section_notificacoes',
			__( 'Nome exibido como remetente dos e-mails (ex: o nome da sua empresa). O endereço técnico de envio do servidor é mantido, para garantir que os e-mails continuam a ser entregues.', 'nps-feedback-system' )
		);
		self::add_text_field(
			'admin_email',
			__( 'E-mail(s) do Administrador', 'nps-feedback-system' ),
			'nps_section_notificacoes',
			__( 'Recebe uma notificação por cada avaliação submetida. Para vários endereços, separe por vírgula.', 'nps-feedback-system' )
		);
		self::add_text_field(
			'google_review_url',
			__( 'URL da Avaliação Google', 'nps-feedback-system' ),
			'nps_section_notificacoes',
			__( 'Usado no botão de avaliação enviado a clientes promotores (nota ≥ 8).', 'nps-feedback-system' )
		);

		$placeholders_desc = __( 'Este texto aparece logo após a saudação ("Olá, {nome}!"). O quadro com a nota/motivo, o botão do Google e a assinatura final são adicionados automaticamente, tal como no modelo original — não é preciso escrevê-los aqui.', 'nps-feedback-system' );

		add_settings_section( 'nps_section_promotor', __( 'E-mail ao Cliente — Promotores (nota ≥ 8)', 'nps-feedback-system' ), '__return_false', self::PAGE_SLUG );
		self::add_text_field( 'email_promotor_assunto', __( 'Assunto', 'nps-feedback-system' ), 'nps_section_promotor' );
		self::add_editor_field(
			'email_promotor_corpo',
			__( 'Texto de Introdução', 'nps-feedback-system' ),
			'nps_section_promotor',
			$placeholders_desc
		);

		add_settings_section( 'nps_section_detrator', __( 'E-mail ao Cliente — Detratores/Neutros (nota < 8)', 'nps-feedback-system' ), '__return_false', self::PAGE_SLUG );
		self::add_text_field( 'email_detrator_assunto', __( 'Assunto', 'nps-feedback-system' ), 'nps_section_detrator' );
		self::add_editor_field( 'email_detrator_corpo', __( 'Texto de Introdução', 'nps-feedback-system' ), 'nps_section_detrator', $placeholders_desc );
	}

	/**
	 * Regista um campo de cor.
	 *
	 * @param string $field   Chave da opção.
	 * @param string $label   Rótulo do campo.
	 * @param string $section Secção a que pertence.
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
	 * Regista um campo de texto simples.
	 *
	 * @param string $field   Chave da opção.
	 * @param string $label   Rótulo do campo.
	 * @param string $section Secção a que pertence.
	 * @param string $desc    Texto de apoio (opcional).
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
	 * Regista um campo de editor de texto rico (wp_editor).
	 *
	 * @param string $field   Chave da opção.
	 * @param string $label   Rótulo do campo.
	 * @param string $section Secção a que pertence.
	 * @param string $desc    Texto de apoio (opcional).
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
	 * Renderiza um campo de cor (color picker).
	 *
	 * @param array $args Argumentos do campo.
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
	 * Renderiza um campo de texto simples.
	 *
	 * @param array $args Argumentos do campo.
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
	 * Renderiza um campo de editor de texto rico (wp_editor).
	 *
	 * @param array $args Argumentos do campo.
	 */
	public static function field_editor( $args ) {
		$opts = self::get_options();
		$key  = $args['field'];

		// wp_editor não permite hífenes/underscores no ID do editor.
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
	 * Sanitiza os dados submetidos no ecrã de configurações.
	 *
	 * @param array $input Dados brutos submetidos.
	 * @return array Dados sanitizados.
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$output   = array();
		$input    = is_array( $input ) ? $input : array();

		foreach ( array( 'primary_color', 'hover_color' ) as $key ) {
			$cor              = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$output[ $key ]   = $cor ? $cor : $defaults[ $key ];
		}

		$output['sender_name'] = ! empty( $input['sender_name'] ) ? sanitize_text_field( $input['sender_name'] ) : $defaults['sender_name'];

		$emails               = isset( $input['admin_email'] ) ? explode( ',', $input['admin_email'] ) : array();
		$emails               = array_filter( array_map( 'sanitize_email', array_map( 'trim', $emails ) ), 'is_email' );
		$output['admin_email'] = ! empty( $emails ) ? implode( ', ', $emails ) : $defaults['admin_email'];

		$output['google_review_url'] = ! empty( $input['google_review_url'] )
			? esc_url_raw( $input['google_review_url'] )
			: $defaults['google_review_url'];

		foreach ( array( 'email_promotor_assunto', 'email_detrator_assunto' ) as $key ) {
			$output[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $defaults[ $key ];
		}

		foreach ( array( 'email_promotor_corpo', 'email_detrator_corpo' ) as $key ) {
			$output[ $key ] = isset( $input[ $key ] ) ? wp_kses_post( $input[ $key ] ) : $defaults[ $key ];
		}

		return $output;
	}

	/**
	 * Carrega o color picker nativo do WordPress apenas na página de configurações do plugin.
	 *
	 * @param string $hook Slug da página de admin atual.
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
	 * Renderiza a página de configurações.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Configurações — NPS Feedback System', 'nps-feedback-system' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Guardar Alterações', 'nps-feedback-system' ) );
				?>
			</form>
		</div>
		<?php
	}
}
