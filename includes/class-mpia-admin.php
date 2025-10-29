<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPIA_Admin {
	const OPTION_KEY = 'mpia_settings';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_init', [ $this, 'settings' ] );
	}

	/**
	 * Añade la página de ajustes al menú de administración.
	 */
	public function menu() {
		add_options_page(
			__( 'Asistente Inteligente', 'asistente-inteligente' ),
			__( 'Asistente Inteligente', 'asistente-inteligente' ),
			'manage_options',
			'mpia-settings',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Registra los ajustes y campos del plugin.
	 */
	public function settings() {
		register_setting(
			'mpia_group',
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => [],
			]
		);

		add_settings_section(
			'mpia_main',
			__( 'Ajustes de API', 'asistente-inteligente' ),
			'__return_false',
			'mpia-settings'
		);

		add_settings_field(
			'api_key',
			__( 'API Key (OpenAI u otra)', 'asistente-inteligente' ),
			[ $this, 'field_text' ],
			'mpia-settings',
			'mpia_main',
			[
				'key'  => 'api_key',
				'type' => 'password',
			]
		);

		add_settings_field(
			'model',
			__( 'Modelo (ej. gpt-4o-mini)', 'asistente-inteligente' ),
			[ $this, 'field_text' ],
			'mpia-settings',
			'mpia_main',
			[
				'key'         => 'model',
				'type'        => 'text',
				'placeholder' => 'gpt-4o-mini',
			]
		);

		add_settings_field(
			'auto_display',
			__( 'Mostrar en todo el sitio', 'asistente-inteligente' ),
			[ $this, 'field_checkbox' ],
			'mpia-settings',
			'mpia_main',
			[
				'key'   => 'auto_display',
				'label' => __( 'Mostrar el chat en todas las páginas (sin shortcode)', 'asistente-inteligente' ),
			]
		);

		add_settings_field(
			'brand_color',
			__( 'Color de marca', 'asistente-inteligente' ),
			[ $this, 'field_color' ],
			'mpia-settings',
			'mpia_main',
			[
				'key'   => 'brand_color',
				'label' => __( 'Color principal del chat', 'asistente-inteligente' ),
			]
		);
	}

	/**
	 * Sanitiza los valores guardados.
	 */
	public function sanitize( $input ) {
		$out                  = [];
		$out['api_key']       = isset( $input['api_key'] ) ? sanitize_text_field( $input['api_key'] ) : '';
		$out['model']         = isset( $input['model'] ) ? sanitize_text_field( $input['model'] ) : 'gpt-4o-mini';
		$out['auto_display']  = ! empty( $input['auto_display'] ) ? 1 : 0;
		$out['brand_color']   = isset( $input['brand_color'] ) ? sanitize_hex_color( $input['brand_color'] ) : '';
		return $out;
	}

	/**
	 * Campo de texto genérico.
	 */
	public function field_text( $args ) {
		$opts = get_option( self::OPTION_KEY );
		$key  = esc_attr( $args['key'] );
		$type = esc_attr( $args['type'] ?? 'text' );
		$ph   = esc_attr( $args['placeholder'] ?? '' );
		$val  = isset( $opts[ $key ] ) ? esc_attr( $opts[ $key ] ) : '';

		printf(
			'<input type="%1$s" name="%2$s[%3$s]" value="%4$s" placeholder="%5$s" class="regular-text" />',
			esc_attr( $type ),
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $val ),
			esc_attr( $ph )
		);
	}

	/**
	 * Campo de checkbox.
	 */
	public function field_checkbox( $args ) {
		$opts = get_option( self::OPTION_KEY );
		$key  = esc_attr( $args['key'] );
		$lbl  = esc_html( $args['label'] ?? '' );
		$val  = ! empty( $opts[ $key ] ) ? 1 : 0;

		printf(
			'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label>',
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			checked( 1, $val, false ),
			$lbl
		);
	}

	/**
	 * Campo de color.
	 */
	public function field_color( $args ) {
		$opts = get_option( self::OPTION_KEY );
		$key  = esc_attr( $args['key'] );
		$lbl  = esc_html( $args['label'] ?? '' );
		$val  = isset( $opts[ $key ] ) ? sanitize_hex_color( $opts[ $key ] ) : '#0b57d0';

		printf(
			'<label>%1$s<br><input type="color" name="%2$s[%3$s]" value="%4$s" /></label>',
			$lbl,
			esc_attr( self::OPTION_KEY ),
			esc_attr( $key ),
			esc_attr( $val )
		);
	}

	/**
	 * Renderiza la página de ajustes.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Asistente Inteligente – Ajustes', 'asistente-inteligente' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'mpia_group' );
				do_settings_sections( 'mpia-settings' );
				submit_button( __( 'Guardar cambios', 'asistente-inteligente' ) );
				?>
			</form>
			<p>
				<em>
					<?php
					echo esc_html__(
						'Guarda aquí tu API Key. El endpoint REST del plugin la usará del lado del servidor. Esta clave no se comparte con terceros.',
						'asistente-inteligente'
					);
					?>
				</em>
			</p>
		</div>
		<?php
	}
}
