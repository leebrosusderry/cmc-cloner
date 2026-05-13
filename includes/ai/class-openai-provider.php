<?php
/**
 * OpenAI Chat Completions provider.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_OpenAI_Provider implements CMC_AI_Provider {

    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const SYSTEM   = 'You are a senior e-commerce policy writer producing Google Merchant Center (GMC) compliant content. Output only the final page content with no explanations, no markdown code fences, no preamble.';

    public function __construct(
        private string $api_key,
        private string $model
    ) {}

    public function id(): string {
        return 'openai';
    }

    public function list_models(): array {
        return [ 'gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini' ];
    }

    public function generate( string $prompt, array $params = [] ): string {
        if ( $this->api_key === '' ) {
            throw new RuntimeException( 'OpenAI API key is not configured.' );
        }

        $body = [
            'model'       => $this->model,
            'messages'    => [
                [ 'role' => 'system', 'content' => self::SYSTEM ],
                [ 'role' => 'user',   'content' => $prompt ],
            ],
            'temperature' => (float) ( $params['temperature'] ?? 0.7 ),
            'max_tokens'  => (int)   ( $params['max_tokens']  ?? 4096 ),
        ];

        $response = wp_remote_post( self::ENDPOINT, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            throw new RuntimeException( 'OpenAI request failed: ' . $response->get_error_message() );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            $message = is_array( $data ) && isset( $data['error']['message'] )
                ? (string) $data['error']['message']
                : 'Unknown error';
            throw new RuntimeException( sprintf( 'OpenAI error (HTTP %d): %s', $code, $message ) );
        }

        return (string) ( $data['choices'][0]['message']['content'] ?? '' );
    }
}
