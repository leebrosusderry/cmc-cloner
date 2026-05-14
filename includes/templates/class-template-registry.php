<?php
/**
 * Template Registry — loads every file in includes/templates/defs/ and
 * exposes them in a canonical order.
 *
 * Each definition file must return an associative array with:
 *   - slug           (string) unique identifier, e.g. "refund-policy"
 *   - label          (string) human label for menus and UI
 *   - description    (string) short one-liner shown in the Prompts page
 *   - skeletons      (string[]) skeleton names used by the Variation Engine (Phase 4)
 *   - default_prompt (string) full prompt text with {{variable}} placeholders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Template_Registry {

    private const ORDER = [
        'contact-us',
        'about-us',
        'refund-policy',
        'cancellation-policy',
        'return-policy',
        'shipping-policy',
        'privacy-policy',
        'terms-of-service',
        'dmca-policy',
        'track-your-order',
        'faq',
        'payment-policy',
        'cookie-policy',
        // Niche-specific (optional, filtered by applies_to_keywords):
        'size-guide',
    ];

    private static ?array $cache = null;

    public static function all(): array {
        if ( self::$cache !== null ) {
            return self::$cache;
        }

        $dir   = CMC_CLONER_DIR . 'includes/templates/defs/';
        $files = glob( $dir . '*.php' ) ?: [];
        $raw   = [];

        foreach ( $files as $file ) {
            $def = include $file;
            if ( is_array( $def ) && ! empty( $def['slug'] ) ) {
                $raw[ (string) $def['slug'] ] = $def;
            }
        }

        $ordered = [];
        foreach ( self::ORDER as $slug ) {
            if ( isset( $raw[ $slug ] ) ) {
                $ordered[ $slug ] = $raw[ $slug ];
                unset( $raw[ $slug ] );
            }
        }
        // Any template not listed in ORDER gets appended at the end.
        foreach ( $raw as $slug => $def ) {
            $ordered[ $slug ] = $def;
        }

        self::$cache = (array) apply_filters( 'cmc_templates', $ordered );
        return self::$cache;
    }

    public static function get( string $slug ): ?array {
        $all = self::all();
        return $all[ $slug ] ?? null;
    }

    public static function labels(): array {
        $out = [];
        foreach ( self::all() as $slug => $def ) {
            $out[ $slug ] = (string) ( $def['label'] ?? $slug );
        }
        return $out;
    }

    public static function default_prompt( string $slug ): string {
        $tpl = self::get( $slug );
        return $tpl ? (string) ( $tpl['default_prompt'] ?? '' ) : '';
    }

    /**
     * Whether a template is "optional" — present in the registry but
     * only relevant to certain industries (see `applies_to_keywords`).
     * Optional templates are excluded from default bulk-generate runs
     * unless the configured industry matches.
     */
    public static function is_optional( string $slug ): bool {
        $tpl = self::get( $slug );
        return $tpl ? (bool) ( $tpl['is_optional'] ?? false ) : false;
    }

    /**
     * Test whether an OPTIONAL template should be activated for the
     * given industry slug / label. Performs a case-insensitive
     * substring search against each `applies_to_keywords` entry.
     *
     * Returns true when:
     *   - the template is NOT optional (always applies), OR
     *   - the template has no `applies_to_keywords` (applies to all), OR
     *   - any keyword is found inside `$industry` (substring match).
     */
    public static function applies_to_industry( string $slug, string $industry ): bool {
        $tpl = self::get( $slug );
        if ( $tpl === null ) {
            return false;
        }
        if ( empty( $tpl['is_optional'] ) ) {
            return true;
        }
        $keywords = (array) ( $tpl['applies_to_keywords'] ?? [] );
        if ( empty( $keywords ) ) {
            return true;
        }
        $haystack = strtolower( $industry );
        foreach ( $keywords as $kw ) {
            $kw = strtolower( trim( (string) $kw ) );
            if ( $kw !== '' && strpos( $haystack, $kw ) !== false ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return only the templates that should appear in the bulk-generate
     * UI for the configured industry. Required templates always pass;
     * optional templates only appear when `applies_to_industry()`
     * returns true.
     *
     * @return array<string, array>  same shape as all(), filtered.
     */
    public static function for_industry( string $industry ): array {
        $out = [];
        foreach ( self::all() as $slug => $def ) {
            if ( empty( $def['is_optional'] ) ) {
                $out[ $slug ] = $def;
                continue;
            }
            if ( self::applies_to_industry( $slug, $industry ) ) {
                $out[ $slug ] = $def;
            }
        }
        return $out;
    }
}
