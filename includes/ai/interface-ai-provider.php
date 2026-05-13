<?php
/**
 * Contract every AI provider must implement.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface CMC_AI_Provider {

    /**
     * Send a prompt to the provider and return the plain-text completion.
     *
     * @param string $prompt Full prompt text.
     * @param array  $params Runtime parameters, e.g. ['temperature' => float, 'max_tokens' => int].
     * @throws RuntimeException On transport, auth, or API-side errors.
     */
    public function generate( string $prompt, array $params = [] ): string;

    /**
     * List of model identifiers this provider exposes in the Settings dropdown.
     *
     * @return string[]
     */
    public function list_models(): array;

    /**
     * Stable provider id, e.g. "openai" or "gemini".
     */
    public function id(): string;
}
