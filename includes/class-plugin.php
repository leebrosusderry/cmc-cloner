<?php
/**
 * Main plugin bootstrap — singleton responsible for loading dependencies
 * and wiring up hooks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Plugin {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->register_hooks();
    }

    private function load_dependencies(): void {
        $base = CMC_CLONER_DIR . 'includes/';
        require_once $base . 'class-crypto.php';
        require_once $base . 'class-ui.php';
        require_once $base . 'class-settings.php';
        require_once $base . 'class-shortcodes.php';
        require_once $base . 'ai/interface-ai-provider.php';
        require_once $base . 'ai/class-openai-provider.php';
        require_once $base . 'ai/class-gemini-provider.php';
        require_once $base . 'ai/class-ai-client.php';
        require_once $base . 'templates/class-template-registry.php';
        require_once $base . 'class-prompts.php';
        require_once $base . 'class-page-reader.php';
        require_once $base . 'class-page-writer.php';
        require_once $base . 'class-content-sanitizer.php';
        require_once $base . 'class-industry-blacklist.php';
        require_once $base . 'class-content-validator.php';
        require_once $base . 'class-skeleton-registry.php';
        require_once $base . 'class-variation-engine.php';
        require_once $base . 'class-prompt-builder.php';
        require_once $base . 'class-image-renamer.php';
        require_once $base . 'class-variation-normalizer.php';
        require_once $base . 'class-products-eraser.php';
        require_once $base . 'class-sku-normalizer.php';
        require_once $base . 'class-title-rewriter.php';
        require_once $base . 'class-review-seeder.php';
        require_once $base . 'class-pages-controller.php';
        require_once $base . 'class-size-guide.php';
        require_once $base . 'class-setup-controller.php';
        require_once $base . 'class-ajax.php';
        require_once $base . 'class-admin-menu.php';
        require_once $base . 'class-public.php';
        require_once $base . 'class-livechat.php';
        require_once $base . 'class-schema.php';
        require_once $base . 'class-migrations.php';
        require_once $base . 'class-updater.php';
    }

    private function register_hooks(): void {
        register_activation_hook( CMC_CLONER_FILE, [ $this, 'on_activate' ] );

        add_action( 'init', [ CMC_Shortcodes::class, 'register' ] );

        // Self-updater (GitHub Releases). Hooks into the admin-side
        // plugin update flow; safe to call on every request because
        // CMC_Updater::init() no-ops when CMC_CLONER_GITHUB_REPO is empty.
        CMC_Updater::init();

        // Run pending DB migrations on every load — cheap when nothing
        // to do (single option-read). MUST run before any class that
        // depends on the schema being current.
        CMC_Migrations::run_pending();

        CMC_Public::init();
        CMC_LiveChat::init();
        CMC_Admin_Menu::init();
        CMC_Image_Renamer::init();
        CMC_Schema::init();
        CMC_Size_Guide::init();

        if ( is_admin() ) {
            CMC_Settings::init();
            CMC_Prompts::init();
            CMC_Ajax::init();
            CMC_Setup_Controller::init();
        }
    }

    public function on_activate(): void {
        CMC_Settings::install_defaults();
        // Stamp the migration baseline on first install so a brand-new
        // site doesn't re-run historical migrations as if it were a
        // freshly-upgraded legacy install.
        CMC_Migrations::mark_baseline_on_fresh_install();
    }
}
