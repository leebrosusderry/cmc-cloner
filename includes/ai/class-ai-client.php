<?php
/**
 * Factory + high-level facade for AI providers. Wraps the selected provider
 * with a single retry on transport failure.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_AI_Client {

    public static function provider(): CMC_AI_Provider {
        $s        = CMC_Settings::get();
        $provider = $s['ai_provider'];

        if ( $provider === 'gemini' ) {
            return new CMC_Gemini_Provider(
                CMC_Settings::get_api_key( 'gemini' ),
                (string) $s['gemini_model']
            );
        }

        return new CMC_OpenAI_Provider(
            CMC_Settings::get_api_key( 'openai' ),
            (string) $s['openai_model']
        );
    }

    public static function generate( string $prompt ): string {
        $s       = CMC_Settings::get();
        $params  = [
            'temperature' => (float) $s['temperature'],
            'max_tokens'  => (int)   $s['max_tokens'],
        ];
        $client  = self::provider();
        $attempt = 0;
        $last    = null;

        while ( $attempt < 2 ) {
            try {
                return $client->generate( $prompt, $params );
            } catch ( Throwable $e ) {
                $last = $e;
                $attempt++;
            }
        }
        throw $last instanceof Throwable ? $last : new RuntimeException( 'AI generation failed.' );
    }

    public static function test(): array {
        try {
            $client = self::provider();
            $output = $client->generate(
                'Reply with exactly: OK',
                [ 'temperature' => 0.0, 'max_tokens' => 16 ]
            );
            return [
                'success'  => true,
                'provider' => $client->id(),
                'output'   => trim( (string) $output ),
            ];
        } catch ( Throwable $e ) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}
