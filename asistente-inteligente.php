<?php
/**
 * Plugin Name: Asistente Inteligente
 * Description: Asistente virtual con integración OpenAI (GPT-4o). Añade un chatbot moderno y personalizable a tu sitio.
 * Version: 1.0.0
 * Author: Julian Ramos
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: asistente-inteligente
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MPIA_PATH', plugin_dir_path( __FILE__ ) );
define( 'MPIA_URL', plugin_dir_url( __FILE__ ) );
define( 'MPIA_VERSION', '1.0.0' );

require_once MPIA_PATH . 'includes/class-mpia-admin.php';
require_once MPIA_PATH . 'includes/class-mpia-rest.php';

final class MPIA_Plugin {

	public function __construct() {
		add_shortcode( 'asistente_inteligente_chat', [ $this, 'render_chat' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'rest_api_init', [ 'MPIA_REST', 'register_routes' ] );
		add_action( 'wp_footer', [ $this, 'inject_chat' ] );
		new MPIA_Admin();
	}

	/**
	 * Encola scripts y estilos del chat.
	 */
	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'mpia-chatbot',
			MPIA_URL . 'assets/css/chatbot.css',
			[],
			MPIA_VERSION
		);

		wp_enqueue_script(
			'mpia-chatbot',
			MPIA_URL . 'assets/js/chatbot.js',
			[ 'jquery' ],
			MPIA_VERSION,
			true
		);

		$nonce = wp_create_nonce( 'wp_rest' );

		wp_localize_script(
			'mpia-chatbot',
			'MPIA',
			[
				'restUrl'      => esc_url_raw( get_rest_url( null, '/mpia/v1/chat' ) ),
				'nonce'        => $nonce,
				'greeting'     => __( 'Hola, ¿cómo puedo ayudarte?', 'asistente-inteligente' ),
				'firstOpenKey' => 'mpia_first_open_v1',
			]
		);
	}

	/**
	 * Inyecta el chat automáticamente en el frontend.
	 */
	public function inject_chat() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		if ( function_exists( 'get_option' ) ) {
			$opts = get_option( MPIA_Admin::OPTION_KEY, [] );
			if ( isset( $opts['auto_display'] ) && ! (int) $opts['auto_display'] ) {
				return;
			}
		}

		static $printed = false;
		if ( $printed ) {
			return;
		}
		$printed = true;

		if ( ! wp_style_is( 'mpia-chatbot', 'enqueued' ) ) {
			wp_enqueue_style( 'mpia-chatbot', MPIA_URL . 'assets/css/chatbot.css', [], MPIA_VERSION );
		}
		if ( ! wp_script_is( 'mpia-chatbot', 'enqueued' ) ) {
			wp_enqueue_script( 'mpia-chatbot', MPIA_URL . 'assets/js/chatbot.js', [ 'jquery' ], MPIA_VERSION, true );
		}

		$nonce = wp_create_nonce( 'wp_rest' );
		wp_localize_script(
			'mpia-chatbot',
			'MPIA',
			[
				'restUrl'      => esc_url_raw( get_rest_url( null, '/mpia/v1/chat' ) ),
				'nonce'        => $nonce,
				'greeting'     => __( 'Hola, ¿cómo puedo ayudarte?', 'asistente-inteligente' ),
				'firstOpenKey' => 'mpia_first_open_v1',
			]
		);

		// 🎨 Color de marca desde Ajustes → Asistente Inteligente.
		$brand = '';
		if ( ! empty( $opts ) && ! empty( $opts['brand_color'] ) ) {
			$brand = sanitize_hex_color( $opts['brand_color'] );
		}
		if ( $brand ) {
			echo '<style>:root{ --mpia-primary:' . esc_attr( $brand ) . '; }</style>';
		}

		// Imprimir plantilla del chat (asegurando escape seguro).
		$template_path = MPIA_PATH . 'includes/template-chat.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}

new MPIA_Plugin();
