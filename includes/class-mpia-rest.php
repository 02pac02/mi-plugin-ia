<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPIA_REST {

	/**
	 * Registra la ruta REST del chatbot.
	 */
	public static function register_routes() {
		register_rest_route(
			'mpia/v1',
			'/chat',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'handle_chat' ],
				'permission_callback' => function () {
					$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] )
						? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) )
						: '';
					return wp_verify_nonce( $nonce, 'wp_rest' );
				},
			]
		);
	}

	/**
	 * Gestiona las solicitudes del chat.
	 */
	public static function handle_chat( WP_REST_Request $req ) {
		$data        = (array) $req->get_json_params();
		$userMessage = isset( $data['message'] ) ? sanitize_text_field( wp_strip_all_tags( $data['message'] ) ) : '';

		// Rate-limit por IP (8 req/min por defecto).
		$rl = self::check_rate_limit( 8, 60 );
		if ( ! $rl['allowed'] ) {
			$response = new WP_REST_Response(
				[
					'error' => sprintf(
						/* translators: %d: segundos restantes */
						__( 'Demasiadas solicitudes. Inténtalo en %ds.', 'asistente-inteligente' ),
						intval( $rl['retry_after'] )
					),
				],
				429
			);
			$response->header( 'Retry-After', (string) intval( $rl['retry_after'] ) );
			return $response;
		}

		if ( '' === $userMessage ) {
			return new WP_REST_Response(
				[ 'error' => __( 'Mensaje vacío.', 'asistente-inteligente' ) ],
				400
			);
		}

		// Obtener configuración guardada.
		$opts   = get_option( MPIA_Admin::OPTION_KEY, [] );
		$apiKey = isset( $opts['api_key'] ) ? sanitize_text_field( $opts['api_key'] ) : '';
		$model  = isset( $opts['model'] ) ? sanitize_text_field( $opts['model'] ) : 'gpt-4o-mini';

		// Si no hay API Key → modo demo local.
		if ( empty( $apiKey ) ) {
			return new WP_REST_Response(
				[
					'reply' => sprintf(
						/* translators: %s: mensaje del usuario */
						__( 'Demo local: has dicho “%s”. Configura tu API Key en Ajustes → Asistente Inteligente.', 'asistente-inteligente' ),
						esc_html( $userMessage )
					),
				],
				200
			);
		}

		// Preparar solicitud a la API de OpenAI.
		$body = wp_json_encode(
			[
				'model'       => $model,
				'messages'    => [
					[ 'role' => 'system', 'content' => 'Eres un asistente de una web en WordPress.' ],
					[ 'role' => 'user', 'content' => $userMessage ],
				],
				'temperature' => 0.2,
			]
		);

		$args = [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $apiKey,
			],
			'body'    => $body,
			'timeout' => 30,
		];

		$resp = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $args );

		if ( is_wp_error( $resp ) ) {
			return new WP_REST_Response(
				[
					'error' => sprintf(
						__( 'Error de conexión: %s', 'asistente-inteligente' ),
						esc_html( $resp->get_error_message() )
					),
				],
				500
			);
		}

		$code = wp_remote_retrieve_response_code( $resp );
		$body = wp_remote_retrieve_body( $resp );
		$json = json_decode( $body, true );

		if ( $code >= 400 || ! $json ) {
			$api_msg = isset( $json['error']['message'] )
				? sanitize_text_field( $json['error']['message'] )
				: __( 'Error desconocido en la API externa.', 'asistente-inteligente' );

			return new WP_REST_Response( [ 'error' => $api_msg ], 500 );
		}

		$reply = isset( $json['choices'][0]['message']['content'] )
			? sanitize_textarea_field( $json['choices'][0]['message']['content'] )
			: __( 'Sin respuesta.', 'asistente-inteligente' );

		return new WP_REST_Response( [ 'reply' => $reply ], 200 );
	}

	/**
	 * Devuelve la IP del cliente de forma segura.
	 */
	private static function client_ip() {
		foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				$ip = explode( ',', $ip )[0]; // primer valor
				return trim( $ip );
			}
		}
		return '0.0.0.0';
	}

	/**
	 * Control de frecuencia (rate limit) por IP.
	 */
	private static function check_rate_limit( $limit = 8, $window = 60 ) {
		$ip      = self::client_ip();
		$key     = 'mpia_rate_' . md5( $ip );
		$bucket  = get_transient( $key );
		$bucket  = is_array( $bucket ) ? $bucket : [ 'count' => 0, 'start' => time() ];
		$elapsed = time() - (int) $bucket['start'];

		if ( $elapsed >= $window ) {
			$bucket = [ 'count' => 0, 'start' => time() ];
		}

		if ( $bucket['count'] >= $limit ) {
			$retry = max( 1, $window - $elapsed );
			return [ 'allowed' => false, 'retry_after' => $retry ];
		}

		$bucket['count']++;
		set_transient( $key, $bucket, $window );
		return [ 'allowed' => true, 'retry_after' => 0 ];
	}
}
