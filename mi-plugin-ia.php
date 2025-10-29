<?php
/**
 * Plugin Name: Mi Plugin IA (Chatbot)
 * Description: Chatbot con endpoint REST y ajustes de API Key.
 * Version: 1.0.0
 * Author: Julián Ramos
 * Text Domain: mi-plugin-ia
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MPIA_PATH', plugin_dir_path( __FILE__ ) );
define( 'MPIA_URL', plugin_dir_url( __FILE__ ) );
define( 'MPIA_VERSION', '1.0.0' );

require_once MPIA_PATH . 'includes/class-mpia-admin.php';
require_once MPIA_PATH . 'includes/class-mpia-rest.php';

final class MPIA_Plugin {
    public function __construct() {
        add_shortcode( 'mi_plugin_ia_chat', [ $this, 'render_chat' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'rest_api_init', [ 'MPIA_REST', 'register_routes' ] );

        // ⬇️ Inyectar automáticamente el chat en el footer del front
        add_action( 'wp_footer', [ $this, 'inject_chat' ] );

        new MPIA_Admin();
    }

    // ... (render_chat y enqueue_assets ya los tienes)
            public function enqueue_assets() {
            // Solo cargar scripts y estilos en el frontend
            if ( is_admin() ) return;

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

            // Pasar variables de entorno al JS (solo si el shortcode o auto_display están activos)
            $nonce = wp_create_nonce( 'wp_rest' );
            wp_localize_script( 'mpia-chatbot', 'MPIA', [
                'restUrl'      => esc_url_raw( get_rest_url( null, '/mpia/v1/chat' ) ),
                'nonce'        => $nonce,
                'greeting'     => __( 'Hola, ¿cómo puedo ayudarte?', 'mi-plugin-ia' ),
                'firstOpenKey' => 'mpia_first_open_v1'
            ]);
        }

    public function inject_chat() {
        if ( is_admin() ) return;                 // no en admin
        if ( wp_doing_ajax() ) return;            // no en llamadas AJAX

        // (Opcional) si tienes un ajuste "auto_display", respétalo
        if ( function_exists('get_option') ) {
            $opts = get_option( MPIA_Admin::OPTION_KEY, [] );
            if ( isset($opts['auto_display']) && ! (int) $opts['auto_display'] ) {
                return; // el usuario prefiere usar shortcode
            }
        }

        // Evita duplicado si ya se imprimió una vez
        static $printed = false; 
        if ( $printed ) return; 
        $printed = true;

        // Estilos y scripts (si aún no están encolados)
        if ( ! wp_style_is( 'mpia-chatbot', 'enqueued' ) ) {
            wp_enqueue_style( 'mpia-chatbot', MPIA_URL . 'assets/css/chatbot.css', [], MPIA_VERSION );
        }
        if ( ! wp_script_is( 'mpia-chatbot', 'enqueued' ) ) {
            wp_enqueue_script( 'mpia-chatbot', MPIA_URL . 'assets/js/chatbot.js', [ 'jquery' ], MPIA_VERSION, true );
        }

        // Pasar settings al JS
        $nonce = wp_create_nonce( 'wp_rest' );
        wp_localize_script( 'mpia-chatbot', 'MPIA', [
            'restUrl'      => esc_url_raw( get_rest_url( null, '/mpia/v1/chat' ) ),
            'nonce'        => $nonce,
            'greeting'     => __( 'Hola, ¿cómo puedo ayudarte?', 'mi-plugin-ia' ),
            'firstOpenKey' => 'mpia_first_open_v1',
        ] );

        // 🎨 Color de marca desde Ajustes → Mi Plugin IA
        $brand = '';
        if ( ! empty( $opts ) && ! empty( $opts['brand_color'] ) ) {
            $brand = sanitize_hex_color( $opts['brand_color'] );
        }
        if ( $brand ) {
            // Aplica el color a la variable CSS global usada por el chat
            echo '<style>:root{ --mpia-primary: ' . esc_attr( $brand ) . '; }</style>';
        }

        // Pintar la plantilla del chat
        include MPIA_PATH . 'includes/template-chat.php';
    }

}

new MPIA_Plugin();
