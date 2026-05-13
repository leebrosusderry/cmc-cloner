<?php
/**
 * Settings — stores and renders all plugin configuration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Settings {

    public const OPTION_KEY   = 'cmc_cloner_settings';
    public const NONCE_ACTION = 'cmc_cloner_save_settings';

    /**
     * Fallback values for the "Service Commitments" fields. Used by both
     * the settings form (placeholder) and runtime accessors
     * (service_commitment()) so that leaving a field blank falls back to
     * GMC-safe defaults instead of an empty string.
     */
    public const SERVICE_DEFAULTS = [
        'gio_lam_viec'           => 'Monday – Saturday, 8:00 AM – 5:00 PM (MST). Closed: Sunday & US Public Holidays',
        'response_time'          => 'within 1 business day',
        'rma_issuance_time'      => 'within 2 business days of approval',
        'refund_processing_time' => 'within 5 business days after we receive the returned item',
        // Return window — the period during which the customer may
        // initiate a return after delivery. Default 30 days fits clothing
        // / general merchandise; override for niche-specific shops (food
        // ~14 days, mattresses ~100 nights, electronics ~30 days). Plain
        // duration phrasing — the prompts append "from the date of
        // delivery" themselves so a value like "30 days" reads naturally.
        'return_window'          => '30 days',
    ];

    /**
     * Fallback values for the shipping-timeframe settings used by the
     * Shipping Policy generator. Top-level (not inside company_info)
     * because they feed prompts only, not frontend shortcodes —
     * mirroring the pattern of free_shipping_threshold.
     */
    public const SHIPPING_DEFAULTS = [
        'shipping_cutoff_time'     => '2:00 PM PST',
        'shipping_handling_time'   => '1–3 business days (Mon–Sat)',
        'shipping_transit_time'    => '5–10 business days (Mon–Sat)',
        'shipping_total_delivery'  => '6–13 business days from order placement',
    ];

    public const SKELETON_VARIANT_MIN = 1;
    public const SKELETON_VARIANT_MAX = 4;

    public static function init(): void {
        add_action( 'admin_init',            [ self::class, 'maybe_migrate' ] );
        add_action( 'admin_init',            [ self::class, 'handle_save' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue' ] );
    }

    /**
     * Backfill settings added in later versions for sites that upgraded
     * in place without triggering the activation hook.
     */
    public static function maybe_migrate(): void {
        $saved = get_option( self::OPTION_KEY, false );
        if ( $saved === false || ! is_array( $saved ) ) {
            return;
        }
        $dirty = false;

        if ( ! isset( $saved['skeleton_variant_mode'] ) || ! isset( $saved['skeleton_variant_number'] ) ) {
            $saved['skeleton_variant_mode']   = $saved['skeleton_variant_mode']   ?? 'auto';
            $saved['skeleton_variant_number'] = $saved['skeleton_variant_number'] ?? self::random_variant_number();
            $dirty = true;
        }

        foreach ( self::SHIPPING_DEFAULTS as $ship_key => $ship_default ) {
            if ( ! array_key_exists( $ship_key, $saved ) ) {
                $saved[ $ship_key ] = $ship_default;
                $dirty = true;
            }
        }

        // Backfill the optional founding_year key for installs that predate it.
        // Default is empty string — About Us prompt then falls back to its
        // year-less Case B wording, which is also the behaviour the previous
        // version of the prompt used, so existing sites don't change output.
        if ( ! array_key_exists( 'founding_year', $saved ) ) {
            $saved['founding_year'] = '';
            $dirty = true;
        }

        // Backfill company_info keys added in later versions so the raw
        // stored blob always has a slot for each input field. Without this,
        // installs that predate a new field (e.g. ten_website) could persist
        // a company_info array without the new key — and any caching layer
        // between the form render and the save read could drop the posted
        // value silently on its way back in.
        if ( ! isset( $saved['company_info'] ) || ! is_array( $saved['company_info'] ) ) {
            $saved['company_info'] = [];
        }
        $ci_defaults = self::defaults()['company_info'];
        foreach ( $ci_defaults as $key => $default_value ) {
            if ( ! array_key_exists( $key, $saved['company_info'] ) ) {
                $saved['company_info'][ $key ] = $default_value;
                $dirty = true;
            }
        }

        // API-key encryption migration: old installs encrypted the
        // OpenAI / Gemini key with an AUTH_KEY+SECURE_AUTH_SALT-derived
        // key. AUTH_KEY is per-site, so cloning the DB to a new install
        // (where wp-config.php was regenerated) breaks decryption and
        // forces the operator to re-enter every key.
        //
        // The new format uses a plugin-static key so the cipher is
        // portable across clones. We migrate idempotently by trying:
        //   1. decrypt with the static key — if it works, already migrated.
        //   2. decrypt with the legacy key — if it works, we're on the
        //      original site, so re-encrypt with the static key and save.
        // This way the operator only has to open `wp-admin` once on the
        // SOURCE site after upgrading the plugin; future clones decrypt
        // automatically with no UI step.
        foreach ( [ 'openai', 'gemini' ] as $provider ) {
            $field  = $provider . '_api_key';
            $cipher = (string) ( $saved[ $field ] ?? '' );
            if ( $cipher === '' ) {
                continue;
            }
            // Already encrypted with the static key — nothing to do.
            if ( CMC_Crypto::decrypt_with_static( $cipher ) !== '' ) {
                continue;
            }
            // Legacy cipher: try to recover plaintext using this site's
            // AUTH_KEY material. Will fail on a freshly-cloned site that
            // never ran the migration on the source — that case is
            // unrecoverable and the operator must re-enter the key once.
            $plain = CMC_Crypto::decrypt_with_legacy( $cipher );
            if ( $plain === '' ) {
                continue;
            }
            $saved[ $field ] = CMC_Crypto::encrypt( $plain );
            $dirty = true;
        }

        if ( $dirty ) {
            update_option( self::OPTION_KEY, $saved );
        }
    }

    public static function install_defaults(): void {
        if ( get_option( self::OPTION_KEY ) === false ) {
            $defaults = self::defaults();
            $defaults['skeleton_variant_number'] = self::random_variant_number();
            update_option( self::OPTION_KEY, $defaults );
            return;
        }
        $saved = (array) get_option( self::OPTION_KEY, [] );
        if ( ! isset( $saved['skeleton_variant_mode'] ) || ! isset( $saved['skeleton_variant_number'] ) ) {
            $saved['skeleton_variant_mode']   = $saved['skeleton_variant_mode']   ?? 'auto';
            $saved['skeleton_variant_number'] = $saved['skeleton_variant_number'] ?? self::random_variant_number();
            update_option( self::OPTION_KEY, $saved );
        }
    }

    public static function random_variant_number(): int {
        return random_int( self::SKELETON_VARIANT_MIN, self::SKELETON_VARIANT_MAX );
    }

    /**
     * True when the active theme is Flatsome (or a Flatsome child).
     * Used by `handle_save()` to skip the typography theme_mod writes
     * on installs that aren't running Flatsome — those keys would just
     * sit unused otherwise.
     */
    public static function flatsome_active(): bool {
        $theme   = wp_get_theme();
        $name    = strtolower( (string) $theme->get( 'Name' ) );
        $template = strtolower( (string) $theme->get_template() );
        return $name === 'flatsome'
            || strpos( $name, 'flatsome' ) !== false
            || $template === 'flatsome';
    }

    /**
     * Curated list of Google Fonts surfaced in the Site basics dropdown.
     * Loaded on demand from `config/google-fonts.php` (cached for the
     * request) and passed through the `cmc_google_fonts_options` filter
     * so site-specific code can extend the list without forking the
     * plugin.
     *
     * @return array<string,string>  Font name → font name (combobox-friendly).
     */
    public static function google_fonts_options(): array {
        static $cached = null;
        if ( $cached !== null ) {
            return $cached;
        }
        $list = include CMC_CLONER_DIR . 'config/google-fonts.php';
        if ( ! is_array( $list ) ) {
            $list = [];
        }
        // Caller can return either ['Roboto', 'Lato', ...] or
        // ['Roboto' => 'Roboto', ...] — normalise to the latter.
        $out = [];
        foreach ( $list as $key => $name ) {
            $name = is_string( $name ) ? trim( $name ) : '';
            if ( $name === '' ) { continue; }
            $out[ $name ] = $name;
        }
        /**
         * Filters the Google Fonts list rendered in Site basics.
         *
         * @param array<string,string> $out  Font name → font name map.
         */
        $out = (array) apply_filters( 'cmc_google_fonts_options', $out );
        $cached = $out;
        return $cached;
    }

    public static function skeleton_variant_number(): int {
        $s = self::get();
        $n = (int) ( $s['skeleton_variant_number'] ?? self::SKELETON_VARIANT_MIN );
        if ( $n < self::SKELETON_VARIANT_MIN || $n > self::SKELETON_VARIANT_MAX ) {
            $n = self::SKELETON_VARIANT_MIN;
        }
        return $n;
    }

    /**
     * Pick and persist a fresh random variant number. Used by the
     * "Re-randomize" button on the Settings page.
     */
    public static function randomize_skeleton_variant(): int {
        $current = self::get();
        $n       = self::random_variant_number();
        $current['skeleton_variant_mode']   = 'auto';
        $current['skeleton_variant_number'] = $n;
        update_option( self::OPTION_KEY, $current );
        return $n;
    }

    public static function defaults(): array {
        return [
            'ai_provider'               => 'openai',
            'openai_api_key'            => '',
            'openai_model'              => 'gpt-4o-mini',
            'gemini_api_key'            => '',
            'gemini_model'              => 'gemini-1.5-flash',
            'max_tokens'                => 4096,
            'temperature'               => 0.7,
            'primary_color'             => '#2ec4b6',
            'founding_year'             => '',
            'flatsome_font_family'      => '',
            'free_shipping_threshold'      => '$75',
            'below_threshold_shipping_fee' => '$4.99',
            'shipping_cutoff_time'         => self::SHIPPING_DEFAULTS['shipping_cutoff_time'],
            'shipping_handling_time'       => self::SHIPPING_DEFAULTS['shipping_handling_time'],
            'shipping_transit_time'        => self::SHIPPING_DEFAULTS['shipping_transit_time'],
            'shipping_total_delivery'      => self::SHIPPING_DEFAULTS['shipping_total_delivery'],
            'enable_builtin_shortcodes' => 1,
            'skeleton_variant_mode'     => 'auto',
            'skeleton_variant_number'   => self::SKELETON_VARIANT_MIN,
            // Live Chat (front-end floating panel, GMC trust signal).
            // Submissions are acknowledged with a fake success message —
            // no email is sent. Empty button_label = circular icon-only.
            'livechat_enabled'          => 1,
            'livechat_button_label'     => '',
            'company_info'              => [
                'dia_chi'                => '',
                'so_dien_thoai'          => '',
                'email_web'              => '',
                'ten_web'                => '',
                'ten_website'            => '',
                'ten_doanh_nghiep'       => '',
                'nganh_hang'             => 'fashion',
                'dinh_huong_san_pham'    => '',
                'gio_lam_viec'           => '',
                'response_time'          => '',
                'rma_issuance_time'      => '',
                'refund_processing_time' => '',
                'return_window'          => '',
            ],
        ];
    }

    /**
     * Effective value for a Service Commitment field: saved value if the
     * user filled it in, otherwise the hardcoded GMC-safe default. Called
     * by the four `[gio-lam-viec]` / `[response-time]` / `[rma-time]` /
     * `[refund-time]` shortcodes and by the Prompt Builder.
     */
    public static function service_commitment( string $key ): string {
        $s     = self::get();
        $saved = trim( (string) ( $s['company_info'][ $key ] ?? '' ) );
        if ( $saved !== '' ) {
            return $saved;
        }
        return (string) ( self::SERVICE_DEFAULTS[ $key ] ?? '' );
    }

    /**
     * Effective value for a shipping-timeframe setting. Mirrors
     * service_commitment() but reads from the top-level settings array
     * (these are not exposed as frontend shortcodes).
     */
    public static function shipping_commitment( string $key ): string {
        $s     = self::get();
        $saved = trim( (string) ( $s[ $key ] ?? '' ) );
        if ( $saved !== '' ) {
            return $saved;
        }
        return (string) ( self::SHIPPING_DEFAULTS[ $key ] ?? '' );
    }

    public static function get(): array {
        $saved = get_option( self::OPTION_KEY, [] );
        return array_replace_recursive( self::defaults(), is_array( $saved ) ? $saved : [] );
    }

    /**
     * Slugs and slug-prefixes whose primary audience is under-13. Used to gate
     * expanded COPPA language in Privacy Policy generation. Standalone items
     * (no trailing dash) match the exact slug; items ending in `-` are prefix
     * matches against the slug.
     */
    private const KIDS_NICHE_PREFIXES = [
        'kids-', 'baby-', 'toddler-', 'newborn-', 'nursery-', 'maternity-',
    ];
    private const KIDS_NICHE_SLUGS = [
        'cribs-bassinets', 'changing-tables', 'playpens-play-yards',
        'diapers-potty', 'disposable-diapers', 'cloth-diapers', 'wipes',
        'diaper-bags', 'potty-training', 'sippy-cups', 'bibs-burp-cloths',
        'breast-pumps', 'teethers', 'soft-toys',
        'childrens-books', 'educational-toys', 'stem-toys', 'coding-toys',
        'science-kits', 'building-sets-blocks', 'lego-sets', 'dolls-accessories',
    ];

    public static function is_kids_niche(): bool {
        $s    = self::get();
        $slug = (string) ( $s['company_info']['nganh_hang'] ?? '' );
        if ( $slug === '' ) {
            return false;
        }
        foreach ( self::KIDS_NICHE_PREFIXES as $prefix ) {
            if ( strpos( $slug, $prefix ) === 0 ) {
                return true;
            }
        }
        return in_array( $slug, self::KIDS_NICHE_SLUGS, true );
    }

    public static function get_api_key( string $provider ): string {
        $s         = self::get();
        $encrypted = (string) ( $s[ $provider . '_api_key' ] ?? '' );
        return $encrypted === '' ? '' : CMC_Crypto::decrypt( $encrypted );
    }

    public static function has_api_key( string $provider ): bool {
        $s = self::get();
        return (string) ( $s[ $provider . '_api_key' ] ?? '' ) !== '';
    }

    /**
     * Push Company Info → Yoast SEO Site Representation (`wpseo_titles`).
     *
     * Yoast's organisation fields aren't site-aware: they sit in a
     * serialized `wp_options` blob and survive a clone unchanged, so the
     * source site's brand keeps showing in the schema-graph (and the
     * Yoast wizard) on every cloned install. This bridges the gap by
     * mirroring our own Company Info into the four fields that drive
     * Yoast's Organization knowledge-graph node.
     *
     * Mapping:
     *   - company_name           ← ten_doanh_nghiep, fallback ten_web
     *   - company_alternate_name ← ten_web
     *   - company_or_person      ← 'company' (force Organization, not Person)
     *   - company_logo           ← REPLACE old host with home_url() host
     *                              when the saved logo URL points at
     *                              the source site's domain
     *
     * Idempotent: builds the desired array, diffs against the current
     * `wpseo_titles`, calls `update_option` only when something actually
     * changed. Safe to run on every Save Settings.
     *
     * @param array<string, mixed> $company  Sanitised company_info from handle_save().
     */
    public static function sync_yoast_organization( array $company ): void {
        // Bail when Yoast isn't installed — `wpseo_titles` either doesn't
        // exist or belongs to a different plugin we shouldn't touch.
        $current = get_option( 'wpseo_titles', null );
        if ( ! is_array( $current ) ) {
            return;
        }

        $ten_doanh_nghiep = trim( (string) ( $company['ten_doanh_nghiep'] ?? '' ) );
        $ten_web          = trim( (string) ( $company['ten_web']          ?? '' ) );

        $org_name      = $ten_doanh_nghiep !== '' ? $ten_doanh_nghiep : $ten_web;
        $org_alt_name  = $ten_web;

        // Skip the whole sync when neither name is configured —
        // overwriting Yoast's existing values with empty strings would
        // be worse than leaving the source-site values in place.
        if ( $org_name === '' && $org_alt_name === '' ) {
            return;
        }

        $changed = false;
        $next    = $current;

        if ( $org_name !== '' && (string) ( $next['company_name'] ?? '' ) !== $org_name ) {
            $next['company_name'] = $org_name;
            $changed = true;
        }
        if ( $org_alt_name !== '' && (string) ( $next['company_alternate_name'] ?? '' ) !== $org_alt_name ) {
            $next['company_alternate_name'] = $org_alt_name;
            $changed = true;
        }
        if ( ( $next['company_or_person'] ?? '' ) !== 'company' ) {
            $next['company_or_person'] = 'company';
            $changed = true;
        }

        // Logo URL host repair — only rewrite if the stored URL points
        // at a host different from `home_url()`'s host (i.e. the source
        // site after a clone). We deliberately leave the URL alone when
        // the host already matches, even if the path is something
        // unexpected, because the operator may have set a custom logo.
        $logo_url = (string) ( $next['company_logo'] ?? '' );
        if ( $logo_url !== '' ) {
            $current_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
            $logo_host    = (string) wp_parse_url( $logo_url,  PHP_URL_HOST );
            if ( $current_host !== '' && $logo_host !== '' && $logo_host !== $current_host ) {
                $rewritten = preg_replace(
                    '#^(https?://)' . preg_quote( $logo_host, '#' ) . '#i',
                    '$1' . $current_host,
                    $logo_url,
                    1
                );
                if ( is_string( $rewritten ) && $rewritten !== '' && $rewritten !== $logo_url ) {
                    $next['company_logo'] = $rewritten;
                    $changed = true;
                }
            }
        }

        if ( $changed ) {
            update_option( 'wpseo_titles', $next );
            // Yoast caches the indexable for the homepage / system pages
            // when it builds schema; bumping their `version` forces a
            // rebuild that picks up the new organisation fields. We do
            // not have direct access to indexable IDs here, so the
            // existing `migrate_yoast_indexable_domain()` helper (which
            // also fired earlier in handle_save) will catch them on
            // the next request anyway via `version = 0`.
        }
    }

    public static function handle_save(): void {
        if ( empty( $_POST['cmc_cloner_save'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Forbidden', 403 );
        }
        check_admin_referer( self::NONCE_ACTION );

        $current = self::get();
        $new     = $current;

        $new['ai_provider']   = in_array( $_POST['ai_provider'] ?? '', [ 'openai', 'gemini' ], true )
            ? $_POST['ai_provider']
            : 'openai';

        $new['openai_model']  = sanitize_text_field( wp_unslash( $_POST['openai_model'] ?? 'gpt-4o-mini' ) );
        $new['gemini_model']  = sanitize_text_field( wp_unslash( $_POST['gemini_model'] ?? 'gemini-1.5-flash' ) );
        $new['max_tokens']    = max( 256, min( 16384, (int) ( $_POST['max_tokens'] ?? 4096 ) ) );
        $new['temperature']   = max( 0.0, min( 2.0, (float) ( $_POST['temperature'] ?? 0.7 ) ) );

        $color = sanitize_hex_color( $_POST['primary_color'] ?? '' );
        $new['primary_color'] = $color ?: '#2ec4b6';

        // Founding year: optional, must be a plausible 4-digit year in
        // [1900, current_year + 1]. Anything else (empty / non-numeric /
        // out-of-range) falls back to empty so the About Us prompt skips
        // the "Founded in <year>" sentence entirely.
        $founding_raw = sanitize_text_field( wp_unslash( $_POST['founding_year'] ?? '' ) );
        $founding_raw = trim( $founding_raw );
        $new['founding_year'] = '';
        if ( preg_match( '/^\d{4}$/', $founding_raw ) ) {
            $year_int    = (int) $founding_raw;
            $upper_bound = (int) wp_date( 'Y' ) + 1;
            if ( $year_int >= 1900 && $year_int <= $upper_bound ) {
                $new['founding_year'] = $founding_raw;
            }
        }

        // Flatsome Google Font: must match an entry in our curated list
        // (allowlist). Anything else falls back to empty so the Save
        // pass leaves Flatsome's typography mods untouched.
        $font_raw    = sanitize_text_field( wp_unslash( $_POST['flatsome_font_family'] ?? '' ) );
        $font_options = self::google_fonts_options();
        $new['flatsome_font_family'] = isset( $font_options[ $font_raw ] ) ? $font_raw : '';

        $threshold = sanitize_text_field( wp_unslash( $_POST['free_shipping_threshold'] ?? '' ) );
        $new['free_shipping_threshold'] = trim( $threshold ) !== '' ? $threshold : '$75';

        $below_fee = sanitize_text_field( wp_unslash( $_POST['below_threshold_shipping_fee'] ?? '' ) );
        $new['below_threshold_shipping_fee'] = trim( $below_fee ) !== '' ? $below_fee : '$4.99';

        foreach ( self::SHIPPING_DEFAULTS as $ship_key => $ship_default ) {
            $posted = sanitize_text_field( wp_unslash( (string) ( $_POST[ $ship_key ] ?? '' ) ) );
            $new[ $ship_key ] = trim( $posted ) !== '' ? $posted : $ship_default;
        }

        $new['enable_builtin_shortcodes'] = empty( $_POST['enable_builtin_shortcodes'] ) ? 0 : 1;

        // Live Chat — 2 options only. Submissions are fake-acknowledged
        // (no real email), so no recipient field is needed.
        $new['livechat_enabled']      = empty( $_POST['livechat_enabled'] ) ? 0 : 1;
        $new['livechat_button_label'] = sanitize_text_field( wp_unslash( $_POST['livechat_button_label'] ?? '' ) );

        $posted_variant = sanitize_text_field( wp_unslash( $_POST['skeleton_variant'] ?? 'auto' ) );
        if ( $posted_variant === 'auto' ) {
            $new['skeleton_variant_mode'] = 'auto';
            if ( ( $current['skeleton_variant_mode'] ?? '' ) !== 'auto' ) {
                $new['skeleton_variant_number'] = self::random_variant_number();
            }
        } elseif ( ctype_digit( $posted_variant ) ) {
            $picked = (int) $posted_variant;
            if ( $picked >= self::SKELETON_VARIANT_MIN && $picked <= self::SKELETON_VARIANT_MAX ) {
                $new['skeleton_variant_mode']   = 'manual';
                $new['skeleton_variant_number'] = $picked;
            }
        }

        $posted_openai = trim( (string) wp_unslash( $_POST['openai_api_key'] ?? '' ) );
        if ( $posted_openai !== '' ) {
            $new['openai_api_key'] = CMC_Crypto::encrypt( $posted_openai );
        }
        $posted_gemini = trim( (string) wp_unslash( $_POST['gemini_api_key'] ?? '' ) );
        if ( $posted_gemini !== '' ) {
            $new['gemini_api_key'] = CMC_Crypto::encrypt( $posted_gemini );
        }

        $ci            = (array) ( $_POST['company_info'] ?? [] );
        $existing_ci   = (array) ( $current['company_info'] ?? [] );
        $nganh_options = CMC_Shortcodes::nganh_hang_options();
        $nganh_posted  = sanitize_key( (string) ( $ci['nganh_hang'] ?? ( $existing_ci['nganh_hang'] ?? '' ) ) );

        // Preserve the existing stored value if a field is missing from the
        // POST body (defensive: keeps saves idempotent across plugin
        // upgrades and prevents caching layers that drop unexpected keys
        // from silently clearing company_info fields).
        $pick_text = static function ( string $key ) use ( $ci, $existing_ci ): string {
            if ( array_key_exists( $key, $ci ) ) {
                return sanitize_text_field( wp_unslash( (string) $ci[ $key ] ) );
            }
            return (string) ( $existing_ci[ $key ] ?? '' );
        };
        $pick_email = static function ( string $key ) use ( $ci, $existing_ci ): string {
            if ( array_key_exists( $key, $ci ) ) {
                return sanitize_email( wp_unslash( (string) $ci[ $key ] ) );
            }
            return (string) ( $existing_ci[ $key ] ?? '' );
        };

        $new['company_info'] = [
            'dia_chi'                => $pick_text( 'dia_chi' ),
            'so_dien_thoai'          => $pick_text( 'so_dien_thoai' ),
            'email_web'              => $pick_email( 'email_web' ),
            'ten_web'                => $pick_text( 'ten_web' ),
            'ten_website'            => $pick_text( 'ten_website' ),
            'ten_doanh_nghiep'       => $pick_text( 'ten_doanh_nghiep' ),
            'nganh_hang'             => isset( $nganh_options[ $nganh_posted ] ) ? $nganh_posted : array_key_first( $nganh_options ),
            'dinh_huong_san_pham'    => $pick_text( 'dinh_huong_san_pham' ),
            'gio_lam_viec'           => $pick_text( 'gio_lam_viec' ),
            'response_time'          => $pick_text( 'response_time' ),
            'rma_issuance_time'      => $pick_text( 'rma_issuance_time' ),
            'refund_processing_time' => $pick_text( 'refund_processing_time' ),
            'return_window'          => $pick_text( 'return_window' ),
        ];

        update_option( self::OPTION_KEY, $new );

        // Auto-sync the brand Primary Color into Flatsome's own theme
        // mod (`color_primary`). Flatsome compiles `--fs-color-primary`
        // and `--primary-color` from this mod into its <style id=
        // "custom-css"> block on the front-end, and any
        // section / row / button using the Flatsome native variables
        // would otherwise keep showing the source-site's primary color
        // (e.g. #111111) instead of our `#43a047`. We only write when
        // the values actually diverge so resaving Settings is a no-op.
        $cmc_primary = (string) ( $new['primary_color'] ?? '' );
        if ( $cmc_primary !== '' && self::flatsome_active() ) {
            $flat_primary = (string) get_theme_mod( 'color_primary', '' );
            if ( strcasecmp( $flat_primary, $cmc_primary ) !== 0 ) {
                set_theme_mod( 'color_primary', $cmc_primary );
                // Clear any cached compiled-CSS files Flatsome / Kirki
                // may have written so the new color shows up on the
                // next request without a manual Customizer re-save.
                delete_option( 'kirki_remote_url_contents_cache' );
                delete_option( 'flatsome_extra_css_cache' );
            }
        }

        // Auto-apply the chosen Google Font to Flatsome's typography
        // theme_mods. Flatsome stores typography settings via Kirki
        // under `theme_mod` keys `type_texts` (body), `type_headings`,
        // `type_nav`, and `type_alt`, each as `[font-family, variant]`.
        // Writing the same family to all four keeps the storefront
        // visually consistent without forcing the operator to open the
        // Customizer. No-op when the font is empty (e.g. cleared) or
        // when the active theme isn't Flatsome.
        $font_family = trim( (string) ( $new['flatsome_font_family'] ?? '' ) );
        if ( $font_family !== '' && self::flatsome_active() ) {
            $current_theme_mod = get_theme_mod( 'type_texts' );
            // Only write if it actually changed — avoids redundant
            // writes on every Save when the field hasn't been touched.
            $current_family = is_array( $current_theme_mod )
                ? (string) ( $current_theme_mod['font-family'] ?? '' )
                : '';
            if ( $current_family !== $font_family ) {
                set_theme_mod( 'type_texts',    [ 'font-family' => $font_family, 'variant' => 'regular' ] );
                set_theme_mod( 'type_headings', [ 'font-family' => $font_family, 'variant' => '700'     ] );
                set_theme_mod( 'type_nav',      [ 'font-family' => $font_family, 'variant' => '600'     ] );
                set_theme_mod( 'type_alt',      [ 'font-family' => $font_family, 'variant' => 'regular' ] );
                // Clear Kirki's local font-file cache so the new family
                // is fetched on the next front-end render instead of
                // serving the previously-downloaded family verbatim.
                delete_option( 'kirki_downloaded_font_files' );
                delete_option( 'kirki_remote_url_contents_cache' );
            }
        }

        // Auto-sync the WordPress site title (blogname) from `ten_web`.
        // Used to be a manual "Apply General" step on the Site Setup
        // page — folding it into Save Settings here removes a whole
        // round-trip on every fresh clone. We only write when the two
        // diverge, so editing other Settings fields without changing
        // `ten_web` is a no-op for blogname.
        $ten_web = trim( (string) ( $new['company_info']['ten_web'] ?? '' ) );
        if ( $ten_web !== '' && strlen( $ten_web ) <= 120 ) {
            $current_blogname = (string) get_option( 'blogname', '' );
            if ( $current_blogname !== $ten_web ) {
                update_option( 'blogname', sanitize_text_field( $ten_web ) );
                // Mirror what Site Setup → General used to write so the
                // setup-state checkmark for "site_title" stays correct
                // (and any callers reading that state still work).
                if ( class_exists( 'CMC_Setup_Controller' ) ) {
                    $state                 = CMC_Setup_Controller::get_state();
                    $state['site_title']   = [ 'value' => $ten_web, 'at' => time() ];
                    update_option( CMC_Setup_Controller::STATE_OPTION, $state );
                }
            }
        }

        // Auto-migrate Yoast SEO indexable URLs from the source-site
        // host to the current `home_url()` host. Solves the recurring
        // "yoast-schema-graph still references the old domain after a
        // clone" symptom — Yoast caches each post / term / homepage
        // permalink in the `wp_yoast_indexable` table when first viewed,
        // and that cache survives a DB clone. The helper auto-detects
        // the old host from existing rows; a no-op when no mismatch is
        // found, so this is cheap to run on every Save Settings.
        if ( class_exists( 'CMC_Image_Renamer' ) ) {
            CMC_Image_Renamer::migrate_yoast_indexable_domain();
        }

        // Auto-sync Yoast SEO → Site Representation → Organization name
        // / Alternate name from Company Info. After a DB clone these
        // fields keep showing the source site's brand because they live
        // in `wpseo_titles` (a wp_options blob) and never auto-update
        // from `home_url()` or `blogname`. We map:
        //   company_name           ← ten_doanh_nghiep (legal name),
        //                             falls back to ten_web if empty
        //   company_alternate_name ← ten_web (storefront name)
        //   company_or_person      ← 'company' (force Organization
        //                             schema instead of Person)
        //   company_logo           ← REPLACE old host with current host
        //                             so the schema-graph logo URL
        //                             resolves on the new domain
        // Idempotent — only touches `wpseo_titles` when something
        // actually differs from what's already saved there.
        self::sync_yoast_organization( $new['company_info'] ?? [] );

        wp_safe_redirect( add_query_arg(
            [ 'page' => 'cmc-cloner-settings', 'saved' => '1' ],
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    public static function enqueue( string $hook ): void {
        if ( strpos( $hook, 'cmc-cloner' ) === false ) {
            return;
        }
        wp_enqueue_style(  'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_style(
            'cmc-cloner-admin',
            CMC_CLONER_URL . 'assets/admin/css/admin.css',
            [ 'wp-color-picker' ],
            CMC_CLONER_VERSION
        );
        wp_enqueue_script(
            'cmc-cloner-admin',
            CMC_CLONER_URL . 'assets/admin/js/admin.js',
            [ 'jquery', 'wp-color-picker' ],
            CMC_CLONER_VERSION,
            true
        );
        $s = self::get();

        $skeletons_by_template = [];
        foreach ( CMC_Template_Registry::all() as $t ) {
            $slug = (string) ( $t['slug'] ?? '' );
            if ( $slug === '' ) {
                continue;
            }
            $skeletons_by_template[ $slug ] = CMC_Variation_Engine::available_skeletons( $slug );
        }

        wp_localize_script(
            'cmc-cloner-admin',
            'CMCCloner',
            [
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( CMC_Ajax::NONCE_ACTION ),
                'settingsUrl' => admin_url( 'admin.php?page=' . CMC_Admin_Menu::SETTINGS_SLUG ),
                'provider'    => (string) $s['ai_provider'],
                'providerLabel' => $s['ai_provider'] === 'gemini' ? 'Gemini' : 'OpenAI',
                'hasApiKey'   => [
                    'openai' => self::has_api_key( 'openai' ),
                    'gemini' => self::has_api_key( 'gemini' ),
                ],
                'skeletons'   => $skeletons_by_template,
                // True once the 4-step Woo POD Setup has succeeded for THIS
                // exact home_url. Cloning to a new domain resets this flag
                // because the source value won't match the new home_url.
                'podSetupDone' => ( (string) get_option( 'cmc_pod_setup_done_for', '' ) === home_url() ),
                'actions'     => [
                    'test'          => CMC_Ajax::ACTION_TEST,
                    'load'          => CMC_Ajax::ACTION_LOAD,
                    'preview'       => CMC_Ajax::ACTION_PREVIEW,
                    'generate'      => CMC_Ajax::ACTION_GENERATE,
                    'update'        => CMC_Ajax::ACTION_UPDATE,
                    'revert'        => CMC_Ajax::ACTION_REVERT,
                    'bulkOne'       => CMC_Ajax::ACTION_BULK_ONE,
                    'imgScan'       => CMC_Ajax::ACTION_IMG_SCAN,
                    'imgRename'     => CMC_Ajax::ACTION_IMG_RENAME,
                    'imgRevert'     => CMC_Ajax::ACTION_IMG_REVERT,
                    'imgMetaRepair' => CMC_Ajax::ACTION_IMG_META_REPAIR,
                    'imgDiagnose'   => CMC_Ajax::ACTION_IMG_DIAGNOSE,
                    'imgPurge'      => CMC_Ajax::ACTION_IMG_PURGE,
                    'productsScan'        => CMC_Ajax::ACTION_PRODUCTS_SCAN,
                    'productsDeleteBatch' => CMC_Ajax::ACTION_PRODUCTS_DELETE_BATCH,
                    'skuScan'             => CMC_Ajax::ACTION_SKU_SCAN,
                    'skuApplyBatch'       => CMC_Ajax::ACTION_SKU_APPLY_BATCH,
                    'skuRevertBatch'      => CMC_Ajax::ACTION_SKU_REVERT_BATCH,
                    'titleRewriteScan'    => CMC_Ajax::ACTION_TITLE_REWRITE_SCAN,
                    'titleRewriteBatch'   => CMC_Ajax::ACTION_TITLE_REWRITE_BATCH,
                    'titleRevertBatch'    => CMC_Ajax::ACTION_TITLE_REVERT_BATCH,
                    'runAllRenameCat'     => CMC_Ajax::ACTION_RUN_ALL_RENAME_CAT,
                    'runAllPickCat'       => CMC_Ajax::ACTION_RUN_ALL_PICK_CAT,
                    'runAllPodMarkDone'   => CMC_Ajax::ACTION_RUN_ALL_POD_MARK_DONE,
                    'runAllHealGuids'     => CMC_Ajax::ACTION_RUN_ALL_HEAL_GUIDS,
                    'reviewScan'          => CMC_Ajax::ACTION_REVIEW_SCAN,
                    'reviewSeed'          => CMC_Ajax::ACTION_REVIEW_SEED,
                    'reviewAiPolishOne'   => CMC_Ajax::ACTION_REVIEW_AI_POLISH_ONE,
                    'reviewRemove'        => CMC_Ajax::ACTION_REVIEW_REMOVE,
                    'skeletonRandomize' => CMC_Ajax::ACTION_SKELETON_RANDOMIZE,
                ],
                'bulkDelayMs' => 5000,
                'strings'     => [
                    'confirmRevert'   => 'Revert this page to the original content? The backup will be deleted.',
                    'confirmReplace'  => 'Replace the current generated content with a new generation?',
                    'noBackup'        => 'No backup found for this page.',
                    'unsavedChanges'  => 'You have unsaved changes. Leave without saving?',
                    'copied'          => 'Copied to clipboard.',
                    'copyFailed'      => 'Could not copy. Select the text manually.',
                    'missingKey'      => 'No API key for %s. Open Settings to add one.',
                    'imgPickCat'      => 'Pick a category to scan.',
                    'imgConfirmRun'   => "Rename %d image(s) across %d product(s)?\n\nOriginal filenames will be remembered so you can Revert later. Post content URLs will be auto-updated.",
                    'imgConfirmRevert'=> "Revert image filenames to their originals for %d product(s) in this category?",
                    'imgNoImages'     => 'No eligible images found in this category.',
                ],
            ]
        );
    }

    public static function render_page(): void {
        $s                  = self::get();
        $nganh_hang_options = CMC_Shortcodes::nganh_hang_options();
        $nganh_hang_grouped = CMC_Shortcodes::nganh_hang_grouped_options();
        include CMC_CLONER_DIR . 'includes/views/settings-page.php';
    }
}
