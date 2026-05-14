<?php
/**
 * Company-info shortcodes — registered only when the "Enable built-in shortcodes"
 * toggle is ON in Settings. When OFF, the plugin stays out of the way and the
 * legacy info plugin remains in full control.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Shortcodes {

    public const TAGS = [
        'dia-chi', 'so-dien-thoai', 'email-web', 'ten-web', 'ten-website', 'ten-doanh-nghiep', 'nganh-hang',
        'gio-lam-viec', 'response-time', 'rma-time', 'refund-time', 'cancellation-time',
    ];

    public static function register(): void {
        $s = CMC_Settings::get();
        if ( empty( $s['enable_builtin_shortcodes'] ) ) {
            return;
        }

        add_shortcode( 'dia-chi',          [ self::class, 'sc_dia_chi' ] );
        add_shortcode( 'so-dien-thoai',    [ self::class, 'sc_so_dien_thoai' ] );
        add_shortcode( 'email-web',        [ self::class, 'sc_email_web' ] );
        add_shortcode( 'ten-web',          [ self::class, 'sc_ten_web' ] );
        add_shortcode( 'ten-website',      [ self::class, 'sc_ten_website' ] );
        add_shortcode( 'ten-doanh-nghiep', [ self::class, 'sc_ten_doanh_nghiep' ] );
        add_shortcode( 'nganh-hang',       [ self::class, 'sc_nganh_hang' ] );
        add_shortcode( 'gio-lam-viec',     [ self::class, 'sc_gio_lam_viec' ] );
        add_shortcode( 'response-time',    [ self::class, 'sc_response_time' ] );
        add_shortcode( 'rma-time',         [ self::class, 'sc_rma_time' ] );
        add_shortcode( 'refund-time',      [ self::class, 'sc_refund_time' ] );
        add_shortcode( 'cancellation-time',[ self::class, 'sc_cancellation_time' ] );

        if ( is_admin() ) {
            add_action( 'admin_notices', [ self::class, 'maybe_conflict_notice' ] );
        }
    }

    public static function nganh_hang_options(): array {
        static $cache = null;
        if ( $cache !== null ) {
            return $cache;
        }
        $file     = CMC_CLONER_DIR . 'config/nganh-hang-options.php';
        $defaults = is_file( $file ) ? (array) require $file : [];
        $cache    = (array) apply_filters( 'cmc_nganh_hang_options', $defaults );
        return $cache;
    }

    /**
     * Grouped form of nganh_hang_options(), parsing the `// ==================== LABEL ====================`
     * comment dividers in config/nganh-hang-options.php to produce optgroup-ready
     * buckets. Entries added via the cmc_nganh_hang_options filter that don't
     * appear in the source file go into an "Other" group.
     *
     * @return array<string, array<string, string>>  [group_label => [slug => label]]
     */
    public static function nganh_hang_grouped_options(): array {
        static $cache = null;
        if ( $cache !== null ) {
            return $cache;
        }

        $flat = self::nganh_hang_options();
        $file = CMC_CLONER_DIR . 'config/nganh-hang-options.php';
        $src  = is_file( $file ) ? (string) file_get_contents( $file ) : '';

        $grouped = [];
        $current = '';
        $seen    = [];

        if ( $src !== '' ) {
            $lines = preg_split( "/\r\n|\r|\n/", $src );
            foreach ( $lines as $line ) {
                if ( preg_match( '~//\s*={5,}\s*(.+?)\s*={5,}~', $line, $m ) ) {
                    $current = trim( $m[1] );
                    if ( ! isset( $grouped[ $current ] ) ) {
                        $grouped[ $current ] = [];
                    }
                    continue;
                }
                if ( preg_match( "~^\s*'([a-z0-9_\-]+)'\s*=>~", $line, $m ) ) {
                    $slug = $m[1];
                    if ( ! isset( $flat[ $slug ] ) || isset( $seen[ $slug ] ) ) {
                        continue;
                    }
                    $bucket = $current !== '' ? $current : 'Other';
                    if ( ! isset( $grouped[ $bucket ] ) ) {
                        $grouped[ $bucket ] = [];
                    }
                    $grouped[ $bucket ][ $slug ] = $flat[ $slug ];
                    $seen[ $slug ]               = true;
                }
            }
        }

        foreach ( $flat as $slug => $label ) {
            if ( ! isset( $seen[ $slug ] ) ) {
                $grouped['Other']          = $grouped['Other'] ?? [];
                $grouped['Other'][ $slug ] = $label;
            }
        }

        $cache = (array) apply_filters( 'cmc_nganh_hang_grouped_options', $grouped );
        return $cache;
    }

    private static function company_info( string $field ): string {
        $s = CMC_Settings::get();
        return (string) ( $s['company_info'][ $field ] ?? '' );
    }

    public static function sc_dia_chi(): string {
        return esc_html( self::company_info( 'dia_chi' ) );
    }

    public static function sc_so_dien_thoai(): string {
        return esc_html( self::company_info( 'so_dien_thoai' ) );
    }

    public static function sc_email_web(): string {
        return esc_html( self::company_info( 'email_web' ) );
    }

    public static function sc_ten_web(): string {
        return esc_html( self::company_info( 'ten_web' ) );
    }

    public static function sc_ten_website(): string {
        return esc_html( self::company_info( 'ten_website' ) );
    }

    public static function sc_ten_doanh_nghiep(): string {
        return esc_html( self::company_info( 'ten_doanh_nghiep' ) );
    }

    public static function sc_nganh_hang(): string {
        $slug = self::company_info( 'nganh_hang' );
        $opts = self::nganh_hang_options();
        return esc_html( $opts[ $slug ] ?? $slug );
    }

    public static function sc_gio_lam_viec(): string {
        return esc_html( CMC_Settings::service_commitment( 'gio_lam_viec' ) );
    }

    public static function sc_response_time(): string {
        return esc_html( CMC_Settings::service_commitment( 'response_time' ) );
    }

    public static function sc_rma_time(): string {
        return esc_html( CMC_Settings::service_commitment( 'rma_issuance_time' ) );
    }

    public static function sc_refund_time(): string {
        return esc_html( CMC_Settings::service_commitment( 'refund_processing_time' ) );
    }

    public static function sc_cancellation_time(): string {
        // Hard-fallback to "5 business days" if the settings chain
        // returns empty — the shortcode MUST always render a real
        // duration to keep generated copy GMC-safe.
        $value = trim( (string) CMC_Settings::service_commitment( 'cancellation_refund_time' ) );
        return esc_html( $value !== '' ? $value : '5 business days' );
    }

    public static function maybe_conflict_notice(): void {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $on_cmc_screen = $screen && strpos( (string) $screen->id, 'cmc-cloner' ) !== false;
        if ( ! $on_cmc_screen ) {
            return;
        }

        $active = get_option( 'active_plugins', [] );
        foreach ( (array) $active as $plugin_file ) {
            if ( $plugin_file === CMC_CLONER_BASENAME ) {
                continue;
            }
            $slug = dirname( $plugin_file );
            if ( $slug === '.' || $slug === '' ) {
                continue;
            }
            if ( stripos( $slug, 'shortcode' ) !== false || stripos( $slug, 'web-info' ) !== false || stripos( $slug, 'info-shortcode' ) !== false ) {
                $tags_html = '<code>[' . implode( ']</code>, <code>[', self::TAGS ) . ']</code>';
                printf(
                    '<div class="notice notice-warning"><p><strong>CMC Cloner:</strong> Detected another shortcode plugin (<code>%s</code>) which may collide with %s. Deactivate it, or turn OFF <em>Enable built-in shortcodes</em> in Settings.</p></div>',
                    esc_html( $plugin_file ),
                    $tags_html
                );
                return;
            }
        }
    }
}
