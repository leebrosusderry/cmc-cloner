<?php
/**
 * AES-256-CBC encryption for API keys at rest.
 *
 * Encryption key is derived from a plugin-static constant so that the
 * ciphertext stored in `wp_options` survives a site clone (DB copy) —
 * the cloned site can decrypt the same blob without the operator having
 * to re-enter the OpenAI / Gemini key. The plugin-static key is kept in
 * source instead of wp-config.php specifically because wp-config.php is
 * usually regenerated on the destination site by the migration tool, and
 * AUTH_KEY-derived encryption breaks under that workflow.
 *
 * For backwards compatibility, decrypt() falls back to the legacy
 * AUTH_KEY+SECURE_AUTH_SALT-derived key so previously-saved keys remain
 * readable. Once the user re-saves the settings page, the value is
 * re-encrypted with the new static key and the legacy path becomes a
 * no-op.
 *
 * Note on threat model: the input boxes on the Settings page are
 * `type=password` with empty value and a masked placeholder, so the
 * plaintext key never reaches the browser. An attacker who has both DB
 * access AND the plugin source can decrypt the stored value — that is
 * the cost of supporting cross-site cloning.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Crypto {

    private const METHOD = 'aes-256-cbc';

    /**
     * Plugin-static seed used to derive the encryption key. Changing
     * this value rotates the key for every install — existing ciphertexts
     * will fail to decrypt with the new key but the legacy AUTH_KEY
     * fallback in decrypt() still recovers historical data.
     */
    private const STATIC_SEED = 'cmc-cloner|static-key|v1|do-not-change-without-rotating';

    public static function encrypt( string $plaintext ): string {
        if ( $plaintext === '' ) {
            return '';
        }
        $iv_len = openssl_cipher_iv_length( self::METHOD );
        $iv     = openssl_random_pseudo_bytes( $iv_len );
        $cipher = openssl_encrypt( $plaintext, self::METHOD, self::static_key(), OPENSSL_RAW_DATA, $iv );
        if ( $cipher === false ) {
            return '';
        }
        return base64_encode( $iv . $cipher );
    }

    public static function decrypt( string $payload ): string {
        $static = self::decrypt_with_static( $payload );
        if ( $static !== '' ) {
            return $static;
        }
        return self::decrypt_with_legacy( $payload );
    }

    /**
     * Decrypt using ONLY the plugin-static key. Returns '' if the payload
     * was encrypted with a different key (e.g. legacy AUTH_KEY-derived
     * material). Used by the migration path to detect ciphertexts that
     * still need to be re-encrypted with the static key.
     */
    public static function decrypt_with_static( string $payload ): string {
        $parts = self::split_payload( $payload );
        if ( $parts === null ) {
            return '';
        }
        [ $iv, $cipher ] = $parts;
        $plain = openssl_decrypt( $cipher, self::METHOD, self::static_key(), OPENSSL_RAW_DATA, $iv );
        return ( $plain === false ) ? '' : $plain;
    }

    /**
     * Decrypt using ONLY the legacy AUTH_KEY+SECURE_AUTH_SALT-derived key.
     * Used by the migration path to recover plaintext from pre-static
     * ciphertexts AND by decrypt() as the last-resort fallback when the
     * static path fails. Returns '' on failure (different site's
     * AUTH_KEY, corrupt payload, etc.).
     */
    public static function decrypt_with_legacy( string $payload ): string {
        $parts = self::split_payload( $payload );
        if ( $parts === null ) {
            return '';
        }
        [ $iv, $cipher ] = $parts;
        $plain = openssl_decrypt( $cipher, self::METHOD, self::legacy_key(), OPENSSL_RAW_DATA, $iv );
        return ( $plain === false ) ? '' : $plain;
    }

    /**
     * Split a base64 payload into [iv, ciphertext]. Returns null when
     * the payload is malformed (invalid base64 or shorter than the IV).
     *
     * @return array{0:string,1:string}|null
     */
    private static function split_payload( string $payload ): ?array {
        if ( $payload === '' ) {
            return null;
        }
        $raw = base64_decode( $payload, true );
        if ( $raw === false ) {
            return null;
        }
        $iv_len = openssl_cipher_iv_length( self::METHOD );
        if ( strlen( $raw ) <= $iv_len ) {
            return null;
        }
        return [ substr( $raw, 0, $iv_len ), substr( $raw, $iv_len ) ];
    }

    public static function mask( string $plaintext ): string {
        $len = strlen( $plaintext );
        if ( $len === 0 ) {
            return '';
        }
        if ( $len <= 8 ) {
            return str_repeat( '•', $len );
        }
        return substr( $plaintext, 0, 4 ) . str_repeat( '•', max( 4, $len - 8 ) ) . substr( $plaintext, -4 );
    }

    private static function static_key(): string {
        return hash( 'sha256', self::STATIC_SEED, true );
    }

    private static function legacy_key(): string {
        $material  = defined( 'AUTH_KEY' )         ? AUTH_KEY         : '';
        $material .= defined( 'SECURE_AUTH_SALT' ) ? SECURE_AUTH_SALT : '';
        if ( $material === '' ) {
            $material = 'cmc-cloner-weak-fallback-' . ( defined( 'DB_NAME' ) ? DB_NAME : 'wordpress' );
        }
        return hash( 'sha256', $material, true );
    }
}
