<?php
/**
 * Prompts — storage and admin handlers for per-template prompt overrides.
 *
 * Storage: a single option `cmc_cloner_prompts` mapping template slug to the
 * overridden prompt string. When a slug is missing from the option, the
 * default prompt defined in the template file is used.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Prompts {

    public const OPTION_KEY   = 'cmc_cloner_prompts';
    public const NONCE_ACTION = 'cmc_cloner_save_prompt';

    public static function init(): void {
        add_action( 'admin_init', [ self::class, 'handle_save' ] );
        add_action( 'admin_init', [ self::class, 'handle_reset' ] );
    }

    public static function all_overrides(): array {
        $saved = get_option( self::OPTION_KEY, [] );
        return is_array( $saved ) ? $saved : [];
    }

    public static function effective( string $slug ): string {
        $overrides = self::all_overrides();
        if ( isset( $overrides[ $slug ] ) && $overrides[ $slug ] !== '' ) {
            return (string) $overrides[ $slug ];
        }
        return CMC_Template_Registry::default_prompt( $slug );
    }

    public static function is_overridden( string $slug ): bool {
        $overrides = self::all_overrides();
        return isset( $overrides[ $slug ] ) && $overrides[ $slug ] !== '';
    }

    public static function save( string $slug, string $content ): void {
        $overrides           = self::all_overrides();
        $content             = trim( $content );
        $overrides[ $slug ]  = $content;
        update_option( self::OPTION_KEY, $overrides );
    }

    public static function reset( string $slug ): void {
        $overrides = self::all_overrides();
        unset( $overrides[ $slug ] );
        update_option( self::OPTION_KEY, $overrides );
    }

    public static function handle_save(): void {
        if ( empty( $_POST['cmc_prompt_save'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Forbidden', 403 );
        }
        check_admin_referer( self::NONCE_ACTION );

        $slug = sanitize_key( $_POST['template_slug'] ?? '' );
        if ( CMC_Template_Registry::get( $slug ) === null ) {
            return;
        }

        $content = (string) wp_unslash( $_POST['prompt_content'] ?? '' );
        self::save( $slug, $content );

        wp_safe_redirect( add_query_arg(
            [
                'page'  => CMC_Admin_Menu::PROMPTS_SLUG,
                'saved' => $slug,
            ],
            admin_url( 'admin.php' )
        ) . '#tab-' . $slug );
        exit;
    }

    public static function handle_reset(): void {
        if ( empty( $_POST['cmc_prompt_reset'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Forbidden', 403 );
        }
        check_admin_referer( self::NONCE_ACTION );

        $slug = sanitize_key( $_POST['template_slug'] ?? '' );
        if ( CMC_Template_Registry::get( $slug ) === null ) {
            return;
        }
        self::reset( $slug );

        wp_safe_redirect( add_query_arg(
            [
                'page'  => CMC_Admin_Menu::PROMPTS_SLUG,
                'reset' => $slug,
            ],
            admin_url( 'admin.php' )
        ) . '#tab-' . $slug );
        exit;
    }

    public static function render_page(): void {
        $templates = CMC_Template_Registry::all();
        include CMC_CLONER_DIR . 'includes/views/prompts-page.php';
    }
}
