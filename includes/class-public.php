<?php
/**
 * Public (front-end) asset loader for cloned pages.
 *
 * On any singular page that CMC Cloner has generated (meta `_cmc_cloned`),
 * enqueues the base CSS plus a short inline CSS block that injects
 * per-site CSS variables derived from the stored style seed and the
 * configured primary color.
 *
 * Site-wide CSS variables (--nt-primary, --nt-radius) are also injected
 * on every front-end page via print_root_vars(), so the homepage Base CSS
 * pasted into the child theme can reference var(--nt-primary) and pick up
 * the shop's primary color from CMC Settings without any user action.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Public {

    public const HANDLE = 'cmc-cloner-public';

    public static function init(): void {
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_if_cloned' ] );
        add_filter( 'body_class',         [ self::class, 'body_class' ] );
        // Priority 5 — runs before theme styles so the child theme's
        // var(--nt-primary) references resolve to the shop's color.
        add_action( 'wp_head',            [ self::class, 'print_root_vars' ], 5 );
        // Priority 101 — runs after theme styles so user's Custom CSS
        // wins over both the theme and the base homepage CSS.
        add_action( 'wp_head',            [ self::class, 'print_custom_css' ], 101 );
    }

    /**
     * Inject site-wide CSS variables into <head> on every front-end page.
     *
     * Outputs a single inline <style> block:
     *   :root { --nt-primary: #xxxxxx; --nt-radius: 24px; }
     *
     * The Base CSS (pasted once into the child theme) references these
     * variables via var(--nt-primary), so the shop's primary color flows
     * through automatically — change the color in CMC Settings, hit Save,
     * the whole site re-skins on next page load. No user action on CSS.
     */
    public static function print_root_vars(): void {
        if ( is_admin() ) {
            return;
        }
        if ( ! class_exists( 'CMC_Settings' ) ) {
            return;
        }
        $settings = CMC_Settings::get();
        $primary  = (string) ( $settings['primary_color'] ?? '#2ec4b6' );
        $primary  = self::esc_color( $primary );

        // Derive a small palette from the single Settings primary color so
        // child theme CSS can use a richer set of variables without each
        // site having to define them. Homepage skeletons (L1..L8) reference
        // these directly:
        //   --nt-primary       : the Settings hex.
        //   --nt-primary-rgb   : "r, g, b" tuple — for `rgba(var(--nt-primary-rgb), .12)`.
        //   --nt-primary-soft  : 92% lighter tint for soft backgrounds.
        //   --nt-primary-dark  : 14% darker shade for hover / contrast.
        $rgb       = self::hex_to_rgb_triple( $primary );
        $soft      = self::esc_color( self::shade( $primary, 92 ) );
        $dark      = self::esc_color( self::shade( $primary, -14 ) );

        printf(
            '<style id="cmc-cloner-root-vars">:root{--nt-primary:%s;--nt-primary-rgb:%s;--nt-primary-soft:%s;--nt-primary-dark:%s;--nt-radius:24px;}</style>' . "\n",
            esc_attr( $primary ),
            esc_attr( $rgb ),
            esc_attr( $soft ),
            esc_attr( $dark )
        );
    }

    /**
     * Convert "#RRGGBB" → "r, g, b" comma-separated tuple suitable for
     * inlining into `rgba(var(--nt-primary-rgb), .12)`. Falls back to the
     * default cyan tuple on malformed input.
     */
    private static function hex_to_rgb_triple( string $hex ): string {
        $hex = ltrim( trim( $hex ), '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
            return '46, 196, 182';
        }
        return sprintf(
            '%d, %d, %d',
            hexdec( substr( $hex, 0, 2 ) ),
            hexdec( substr( $hex, 2, 2 ) ),
            hexdec( substr( $hex, 4, 2 ) )
        );
    }

    /**
     * Prints the site-wide custom CSS saved in the Site Setup screen.
     * Runs late so it wins over theme stylesheets, just like a child theme
     * loaded after the parent.
     */
    public static function print_custom_css(): void {
        if ( ! class_exists( 'CMC_Setup_Controller' ) ) {
            return;
        }
        $css = CMC_Setup_Controller::get_custom_css();
        if ( $css === '' ) {
            return;
        }
        echo "<style id=\"cmc-cloner-custom-css\">\n" . $css . "\n</style>\n";
    }

    public static function body_class( array $classes ): array {
        if ( ! is_singular( 'page' ) ) {
            return $classes;
        }
        $post_id = (int) get_queried_object_id();
        if ( $post_id <= 0 || ! get_post_meta( $post_id, '_cmc_cloned', true ) ) {
            return $classes;
        }
        $skeleton = (string) get_post_meta( $post_id, '_cmc_skeleton', true );
        if ( preg_match( '/^skeleton-(\d+)$/', $skeleton, $m ) ) {
            $classes[] = 'cmc-skel-' . (int) $m[1];
        }
        return $classes;
    }

    public static function enqueue_if_cloned(): void {
        if ( ! is_singular( 'page' ) ) {
            return;
        }
        $post_id = (int) get_queried_object_id();
        if ( $post_id <= 0 ) {
            return;
        }
        if ( ! get_post_meta( $post_id, '_cmc_cloned', true ) ) {
            return;
        }

        wp_enqueue_style(
            self::HANDLE,
            CMC_CLONER_URL . 'assets/public/css/cloned.css',
            [],
            CMC_CLONER_VERSION
        );

        wp_enqueue_script(
            self::HANDLE . '-contact-form',
            CMC_CLONER_URL . 'assets/public/js/contact-form.js',
            [],
            CMC_CLONER_VERSION,
            true
        );

        $seed     = (int) get_post_meta( $post_id, '_cmc_style_seed', true );
        $settings = CMC_Settings::get();
        $primary  = (string) ( $settings['primary_color'] ?: '#2ec4b6' );
        $tokens   = CMC_Variation_Engine::style_tokens( $seed );

        wp_add_inline_style( self::HANDLE, self::build_inline_css( $tokens, $primary ) );
    }

    private static function build_inline_css( array $tokens, string $primary ): string {
        $radius  = (int)    $tokens['radius'];
        $pad     = (int)    $tokens['section_padding'];
        $align   = (string) $tokens['heading_align'];
        $shade   = (string) $tokens['accent_shade'];

        $accent     = self::shade( $primary, $shade === 'lighten' ? 14 : -14 );
        $soft_bg    = self::shade( $primary, 92 );  // very light tint
        $border_sub = self::shade( $primary, 80 );  // light tint for borders

        $align_css = in_array( $align, [ 'left', 'center' ], true ) ? $align : 'left';

        return sprintf(
            '.cmc-section{--cmc-primary:%s;--cmc-accent:%s;--cmc-soft-bg:%s;--cmc-border-sub:%s;--cmc-radius:%dpx;--cmc-section-pad:%dpx;--cmc-heading-align:%s;}',
            self::esc_color( $primary ),
            self::esc_color( $accent ),
            self::esc_color( $soft_bg ),
            self::esc_color( $border_sub ),
            max( 0, min( 24, $radius ) ),
            max( 12, min( 56, $pad ) ),
            $align_css
        );
    }

    /**
     * Blend a hex color toward white (positive percent) or black (negative).
     * percent in [-100, 100]. Invalid input returns input unchanged.
     */
    private static function shade( string $hex, int $percent ): string {
        $hex = ltrim( trim( $hex ), '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
            return '#2ec4b6';
        }
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $percent = max( -100, min( 100, $percent ) );
        if ( $percent >= 0 ) {
            $r = (int) round( $r + ( 255 - $r ) * ( $percent / 100 ) );
            $g = (int) round( $g + ( 255 - $g ) * ( $percent / 100 ) );
            $b = (int) round( $b + ( 255 - $b ) * ( $percent / 100 ) );
        } else {
            $f = 1 + ( $percent / 100 );
            $r = (int) round( $r * $f );
            $g = (int) round( $g * $f );
            $b = (int) round( $b * $f );
        }
        return sprintf( '#%02x%02x%02x', max( 0, min( 255, $r ) ), max( 0, min( 255, $g ) ), max( 0, min( 255, $b ) ) );
    }

    private static function esc_color( string $value ): string {
        return preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ? $value : '#2ec4b6';
    }
}
