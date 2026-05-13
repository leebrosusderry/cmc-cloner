<?php
/**
 * Google Gemini generateContent provider.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Gemini_Provider implements CMC_AI_Provider {

    private const ENDPOINT_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';
    private const SYSTEM            = 'You are a senior e-commerce policy writer producing Google Merchant Center (GMC) compliant content. Output only the final page content with no explanations, no markdown code fences, no preamble.';

    public function __construct(
        private string $api_key,
        private string $model
    ) {}

    public function id(): string {
        return 'gemini';
    }

    public function list_models(): array {
        return [ 'gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-2.0-flash' ];
    }

    public function generate( string $prompt, array $params = [] ): string {
        if ( $this->api_key === '' ) {
            throw new RuntimeException( 'Gemini API key is not configured.' );
        }

        $url = sprintf(
            self::ENDPOINT_TEMPLATE,
            rawurlencode( $this->model ),
            rawurlencode( $this->api_key )
        );

        $body = [
            'systemInstruction' => [
                'parts' => [ [ 'text' => self::SYSTEM ] ],
            ],
            'contents'          => [
                [
                    'role'  => 'user',
                    'parts' => [ [ 'text' => $prompt ] ],
                ],
            ],
            'generationConfig'  => [
                'temperature'     => (float) ( $params['temperature'] ?? 0.7 ),
                'maxOutputTokens' => (int)   ( $params['max_tokens']  ?? 4096 ),
            ],
        ];

        $response = wp_remote_post( $url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            throw new RuntimeException( 'Gemini request failed: ' . $response->get_error_message() );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $message = is_array( $data ) && isset( $data['error']['message'] )
                ? (string) $data['error']['message']
                : 'Unknown error';
            throw new RuntimeException( sprintf( 'Gemini error (HTTP %d): %s', $code, $message ) );
        }

        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        $text  = '';
        if ( is_array( $parts ) ) {
            foreach ( $parts as $part ) {
                if ( isset( $part['text'] ) ) {
                    $text .= (string) $part['text'];
                }
            }
        }
        return $text;
    }
}
