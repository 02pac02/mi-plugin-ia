<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MPIA_REST {
    public static function register_routes() {
        register_rest_route( 'mpia/v1', '/chat', [
            'methods'  => 'POST',
            'callback' => [ __CLASS__, 'handle_chat' ],
            'permission_callback' => function () {
                return wp_verify_nonce( $_SERVER['HTTP_X_WP_NONCE'] ?? '', 'wp_rest' );
            }
        ] );
    }

    public static function handle_chat( WP_REST_Request $req ) {
        $data = $req->get_json_params();
        $userMessage = isset( $data['message'] ) ? wp_strip_all_tags( $data['message'] ) : '';
        // Rate-limit por IP (8 req/min por defecto)
$rl = self::check_rate_limit( 8, 60 );
if ( ! $rl['allowed'] ) {
    $response = new WP_REST_Response( [ 'error' => 'Demasiadas solicitudes. Inténtalo en ' . $rl['retry_after'] . 's.' ], 429 );
    $response->header( 'Retry-After', (string) $rl['retry_after'] );
    return $response;
}


        if ( $userMessage === '' ) {
            return new WP_REST_Response( [ 'error' => 'Mensaje vacío' ], 400 );
        }

        // Opciones
        $opts = get_option( MPIA_Admin::OPTION_KEY, [] );
        $apiKey = $opts['api_key'] ?? '';
        $model  = $opts['model']  ?? 'gpt-4o-mini';

        // Si no hay API key, hacemos respuesta "eco" para demo
        if ( empty( $apiKey ) ) {
            return [
                'reply' => 'Demo local: has dicho “' . $userMessage . '”. Configura tu API Key en Ajustes → Mi Plugin IA.'
            ];
        }

        // ---- EJEMPLO de llamada a OpenAI (server-side) ----
        // Sustituye por tu endpoint preferido si no usas OpenAI.
        $body = wp_json_encode([
            'model' => $model,
            'messages' => [
                [ 'role' => 'system', 'content' => 'Eres un asistente de una web en WordPress.' ],
                [ 'role' => 'user',   'content' => $userMessage ],
            ],
            'temperature' => 0.2,
        ]);

        $resp = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'body'    => $body,
            'timeout' => 30,
        ]);

        if ( is_wp_error( $resp ) ) {
            return new WP_REST_Response( [ 'error' => $resp->get_error_message() ], 500 );
        }

        $code = wp_remote_retrieve_response_code( $resp );
        $json = json_decode( wp_remote_retrieve_body( $resp ), true );

        if ( $code >= 400 || ! $json ) {
            return new WP_REST_Response( [ 'error' => 'Error en la API externa.' ], 500 );
        }

        $reply = $json['choices'][0]['message']['content'] ?? 'Sin respuesta';
        return [ 'reply' => $reply ];
    }
    private static function client_ip() {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            return sanitize_text_field($ip);
        }
    }
    return '0.0.0.0';
}

    private static function check_rate_limit( $limit = 8, $window = 60 ) {
        $ip = self::client_ip();
        $key = 'mpia_rate_' . md5($ip);
        $bucket = get_transient( $key );
        if ( ! is_array( $bucket ) ) {
            $bucket = [ 'count' => 0, 'start' => time() ];
        }
        $elapsed = time() - (int) $bucket['start'];

        if ( $elapsed >= $window ) {
            // reinicia ventana
            $bucket = [ 'count' => 0, 'start' => time() ];
        }

        if ( $bucket['count'] >= $limit ) {
            $retry = max(1, $window - $elapsed);
            return [ 'allowed' => false, 'retry_after' => $retry ];
        }

        // incrementa y guarda
        $bucket['count']++;
        set_transient( $key, $bucket, $window );
        return [ 'allowed' => true, 'retry_after' => 0 ];
    }

}
