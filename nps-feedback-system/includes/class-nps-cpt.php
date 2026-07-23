<?php
/**
 * Registo e personalização do Custom Post Type "Avaliações NPS".
 *
 * @package NPS_Feedback_System
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPS_CPT {

	/**
	 * Regista os hooks do módulo.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'show_all_statuses_in_admin' ) );
		add_filter( 'manage_nps_avaliacao_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_nps_avaliacao_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
	}

	/**
	 * Regista o Custom Post Type "nps_avaliacao".
	 */
	public static function register_post_type() {
		register_post_type(
			'nps_avaliacao',
			array(
				'labels'          => array(
					'name'          => __( 'Avaliações NPS', 'nps-feedback-system' ),
					'singular_name' => __( 'Avaliação NPS', 'nps-feedback-system' ),
					'menu_name'     => __( 'Avaliações NPS', 'nps-feedback-system' ),
					'all_items'     => __( 'Todas as Avaliações', 'nps-feedback-system' ),
					'add_new'       => __( 'Adicionar Nova', 'nps-feedback-system' ),
					'edit_item'     => __( 'Ver Avaliação', 'nps-feedback-system' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-star-filled',
				'supports'        => array( 'title', 'editor', 'author' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Garante que a listagem no painel mostra avaliações de qualquer autor
	 * (incluindo as submetidas por visitantes anónimos), independentemente do estado.
	 *
	 * @param WP_Query $query Query principal do admin.
	 */
	public static function show_all_statuses_in_admin( $query ) {
		if ( is_admin() && $query->is_main_query() && 'nps_avaliacao' === $query->get( 'post_type' ) ) {
			$query->set( 'post_status', array( 'publish', 'pending', 'draft', 'future', 'private' ) );
		}
	}

	/**
	 * Define as colunas da tabela de listagem no admin.
	 *
	 * @param array $columns Colunas por omissão.
	 * @return array
	 */
	public static function columns( $columns ) {
		return array(
			'cb'    => '<input type="checkbox" />',
			'title' => __( 'Cliente', 'nps-feedback-system' ),
			'nota'  => __( 'Nota NPS', 'nps-feedback-system' ),
			'email' => __( 'E-mail', 'nps-feedback-system' ),
			'date'  => __( 'Data', 'nps-feedback-system' ),
		);
	}

	/**
	 * Preenche o conteúdo das colunas personalizadas.
	 *
	 * @param string $column  Nome da coluna.
	 * @param int    $post_id ID do post.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'nota' === $column ) {
			$nota = get_post_meta( $post_id, 'nps_nota', true );
			echo '<strong>' . esc_html( $nota ) . ' / 10</strong>';
		}

		if ( 'email' === $column ) {
			echo esc_html( get_post_meta( $post_id, 'nps_email', true ) );
		}
	}
}
