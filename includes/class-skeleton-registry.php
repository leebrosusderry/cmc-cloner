<?php
/**
 * Skeleton Registry — enumerates and loads layout skeletons from
 * skeletons/{template-slug}/skeleton-{n}.php.
 *
 * Each skeleton file returns a Flatsome UX Builder shortcode string with
 * {{PLACEHOLDER}} tokens that the AI must fill.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Skeleton_Registry {

    public static function load( string $template_slug, string $skeleton_slug ): ?string {
        $file = self::file_path( $template_slug, $skeleton_slug );
        if ( ! is_file( $file ) ) {
            return null;
        }
        $content = include $file;
        return is_string( $content ) ? $content : null;
    }

    public static function available( string $template_slug ): array {
        $dir = self::dir_path( $template_slug );
        if ( ! is_dir( $dir ) ) {
            return [];
        }
        $files = glob( $dir . 'skeleton-*.php' ) ?: [];
        $out   = [];
        foreach ( $files as $f ) {
            $out[] = basename( $f, '.php' );
        }
        sort( $out, SORT_NATURAL );
        return $out;
    }

    private static function dir_path( string $template_slug ): string {
        return CMC_CLONER_DIR . 'skeletons/' . $template_slug . '/';
    }

    private static function file_path( string $template_slug, string $skeleton_slug ): string {
        return self::dir_path( $template_slug ) . $skeleton_slug . '.php';
    }
}
