<?php
/**
 * Variation Engine — picks a skeleton and derives deterministic style tokens
 * based on a seed, so that the same site produces consistent visuals while
 * two different sites produce different visuals from the same source.
 *
 * Phase 3 ships a minimal implementation: one skeleton per template and a
 * basic style_tokens() helper. Phase 4 expands the skeleton pool to 4 per
 * template and enables real seed-based random selection.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Variation_Engine {

    public static function compute_seed( int $page_id, string $template_slug ): int {
        $material = (string) get_site_url() . '|' . $template_slug . '|' . $page_id;
        return (int) sprintf( '%u', crc32( $material ) );
    }

    public static function pick_skeleton( string $template_slug, int $seed, ?string $override = null ): string {
        $available = CMC_Skeleton_Registry::available( $template_slug );
        if ( empty( $available ) ) {
            return 'skeleton-1';
        }
        if ( $override !== null && $override !== '' && in_array( $override, $available, true ) ) {
            return $override;
        }
        if ( count( $available ) === 1 ) {
            return $available[0];
        }

        $variant   = CMC_Settings::skeleton_variant_number();
        $preferred = 'skeleton-' . $variant;
        if ( in_array( $preferred, $available, true ) ) {
            return $preferred;
        }
        return $available[ ( $variant - 1 ) % count( $available ) ];
    }

    public static function available_skeletons( string $template_slug ): array {
        return CMC_Skeleton_Registry::available( $template_slug );
    }

    /**
     * Deterministic style tokens for per-page CSS variable injection.
     * Used by Phase 4 to render the inline <style> block.
     */
    public static function style_tokens( int $seed ): array {
        $pick = static function ( array $choices, int $salt ) use ( $seed ): mixed {
            $index = abs( ( $seed * 2654435761 + $salt ) ) % count( $choices );
            return $choices[ $index ];
        };

        return [
            'radius'          => (int)    $pick( [ 0, 4, 8, 12 ],   1 ),
            'section_padding' => (int)    $pick( [ 20, 28, 36, 44 ], 2 ),
            'heading_align'   => (string) $pick( [ 'left', 'center' ], 3 ),
            'accent_shade'    => (string) $pick( [ 'lighten', 'darken' ], 4 ),
        ];
    }
}
