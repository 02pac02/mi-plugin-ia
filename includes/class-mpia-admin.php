<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MPIA_Admin {
    const OPTION_KEY = 'mpia_settings';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_init', [ $this, 'settings' ] );
    }

    public function menu() {
        add_options_page(
            'Mi Plugin IA',
            'Mi Plugin IA',
            'manage_options',
            'mpia-settings',
            [ $this, 'render_page' ]
        );
    }

    public function settings() {
        register_setting( 'mpia_group', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [ $this, 'sanitize' ],
            'default' => []
        ] );

        add_settings_section( 'mpia_main', 'Ajustes de API', '__return_false', 'mpia-settings' );

        add_settings_field(
            'api_key',
            'API Key (OpenAI u otra)',
            [ $this, 'field_text' ],
            'mpia-settings',
            'mpia_main',
            [ 'key' => 'api_key', 'type' => 'password' ]
        );

        add_settings_field(
            'model',
            'Modelo (ej. gpt-4o-mini)',
            [ $this, 'field_text' ],
            'mpia-settings',
            'mpia_main',
            [ 'key' => 'model', 'type' => 'text', 'placeholder' => 'gpt-4o-mini' ]
        );
        add_settings_field(
            'auto_display',
            'Mostrar en todo el sitio',
            [ $this, 'field_checkbox' ],
            'mpia-settings',
            'mpia_main',
            [ 'key' => 'auto_display', 'label' => 'Mostrar el chat en todas las páginas (sin shortcode)' ]
        );
        add_settings_field(
            'brand_color',
            'Color de marca',
            [ $this, 'field_color' ],
            'mpia-settings',
            'mpia_main',
            [ 'key' => 'brand_color', 'label' => 'Color principal del chat' ]
        );

    }

    public function sanitize( $input ) {
        $out = [];
        $out['api_key'] = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
        $out['model']   = isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : 'gpt-4o-mini';
        $out['auto_display'] = ! empty( $input['auto_display'] ) ? 1 : 0;
        $out['brand_color'] = isset( $input['brand_color'] ) ? sanitize_hex_color( $input['brand_color'] ) : '';
        return $out;
    }

    public function field_text( $args ) {
        $opts = get_option( self::OPTION_KEY );
        $key  = esc_attr( $args['key'] );
        $type = esc_attr( $args['type'] ?? 'text' );
        $ph   = esc_attr( $args['placeholder'] ?? '' );
        $val  = isset( $opts[ $key ] ) ? $opts[ $key ] : '';
        printf(
            '<input type="%1$s" name="%2$s[%3$s]" value="%4$s" placeholder="%5$s" class="regular-text" />',
            $type,
            esc_attr( self::OPTION_KEY ),
            $key,
            esc_attr( $val ),
            $ph
        );
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1>Mi Plugin IA – Ajustes</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'mpia_group' );
                do_settings_sections( 'mpia-settings' );
                submit_button();
                ?>
            </form>
            <p><em>Guarda aquí tu API Key. El endpoint REST del plugin la usará del lado servidor.</em></p>
        </div>
        <?php
    }
    public function field_checkbox( $args ) {
        $opts = get_option( self::OPTION_KEY );
        $key  = esc_attr( $args['key'] );
        $lbl  = esc_html( $args['label'] ?? '' );
        $val  = ! empty( $opts[ $key ] ) ? 1 : 0;
        printf(
            '<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label>',
            esc_attr( self::OPTION_KEY ),
            $key,
            checked( 1, $val, false ),
            $lbl
        );
    }
    public function inject_chat() {
            if ( is_admin() ) return;
            $opts = get_option( MPIA_Admin::OPTION_KEY, [] );
            $auto = isset( $opts['auto_display'] ) ? (int) $opts['auto_display'] : 1; // por defecto ON
            if ( ! $auto ) return;

            static $printed = false; if ( $printed ) return; $printed = true;
            // (resto igual)
        }
        public function field_color( $args ) {
            $opts = get_option( self::OPTION_KEY );
            $key  = esc_attr( $args['key'] );
            $lbl  = esc_html( $args['label'] ?? '' );
            $val  = isset( $opts[ $key ] ) ? $opts[ $key ] : '#0b57d0';
            printf(
                '<label>%s<br><input type="color" name="%s[%s]" value="%s" /></label>',
                $lbl,
                esc_attr( self::OPTION_KEY ),
                $key,
                esc_attr( $val )
            );
        }

}
