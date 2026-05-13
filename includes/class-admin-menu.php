<?php
/**
 * Registers the top-level CMC Cloner admin menu and its sub-pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Admin_Menu {

    public const MENU_SLUG     = 'cmc-cloner';
    public const PROMPTS_SLUG  = 'cmc-cloner-prompts';
    public const SETTINGS_SLUG = 'cmc-cloner-settings';

    public static function init(): void {
        add_action( 'admin_menu',     [ self::class, 'register' ] );
        add_action( 'admin_bar_menu', [ self::class, 'register_admin_bar' ], 80 );
    }

    public static function register_admin_bar( WP_Admin_Bar $bar ): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Top admin-bar entry now lands on Settings — that's the
        // sub-page at slot #1 in the menu, so a single click takes
        // the operator to the same place the sidebar's first item does.
        $bar->add_node( [
            'id'    => 'cmc-cloner',
            'title' => 'CMC Cloner',
            'href'  => admin_url( 'admin.php?page=' . self::SETTINGS_SLUG ),
            'meta'  => [ 'title' => 'CMC Cloner — quick access' ],
        ] );

        // Quick-access dropdown mirrors the sidebar order so the
        // operator's muscle-memory works identically in both surfaces.
        $items = [
            'cmc-cloner-settings'   => [ '1. Settings',         self::SETTINGS_SLUG ],
            'cmc-cloner-pages'      => [ '2. Build pages',      self::MENU_SLUG ],
            'cmc-cloner-site-setup' => [ '3. Products & Home',  CMC_Setup_Controller::MENU_SLUG ],
            'cmc-cloner-prompts'    => [ 'Prompts',             self::PROMPTS_SLUG ],
        ];
        foreach ( $items as $id => $pair ) {
            $bar->add_node( [
                'parent' => 'cmc-cloner',
                'id'     => $id,
                'title'  => $pair[0],
                'href'   => admin_url( 'admin.php?page=' . $pair[1] ),
            ] );
        }
    }

    public static function register(): void {
        // Top-level menu uses SETTINGS_SLUG so the auto-generated first
        // submenu is "1. Settings" (WP creates a duplicate of the
        // top-level entry as the first submenu item; routing it to
        // Settings is the cleanest way to land users on the right
        // page when they click the parent label).
        add_menu_page(
            'CMC Cloner',
            'CMC Cloner',
            'manage_options',
            self::SETTINGS_SLUG,
            [ CMC_Settings::class, 'render_page' ],
            'dashicons-admin-page',
            58
        );

        // (1) Settings — re-add as its own submenu so we can override
        // the auto-generated label (which mirrors the top-level
        // "CMC Cloner" string and otherwise can't be relabelled).
        add_submenu_page(
            self::SETTINGS_SLUG,
            'Settings',
            '1. Settings',
            'manage_options',
            self::SETTINGS_SLUG,
            [ CMC_Settings::class, 'render_page' ]
        );

        // (2) Build pages — keeps its 'cmc-cloner' slug so any
        // bookmark to admin.php?page=cmc-cloner still resolves.
        add_submenu_page(
            self::SETTINGS_SLUG,
            'Build pages',
            '2. Build pages',
            'manage_options',
            self::MENU_SLUG,
            [ CMC_Pages_Controller::class, 'render_page' ]
        );

        // (3) Products & Home — Site Setup screen, renamed.
        add_submenu_page(
            self::SETTINGS_SLUG,
            'Products & Home',
            '3. Products & Home',
            'manage_options',
            CMC_Setup_Controller::MENU_SLUG,
            [ CMC_Setup_Controller::class, 'render_page' ]
        );

        // (4) Prompts — kept as plain "Prompts" per spec.
        add_submenu_page(
            self::SETTINGS_SLUG,
            'Prompts',
            'Prompts',
            'manage_options',
            self::PROMPTS_SLUG,
            [ CMC_Prompts::class, 'render_page' ]
        );
    }
}
