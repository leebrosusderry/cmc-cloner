<?php
/**
 * Size Guide helper — keeps the niche-specific Size Guide page in sync
 * with the configured industry, including its footer link.
 *
 * Two responsibilities:
 *
 *   1. ATTACH — when CMC_Page_Writer saves a `size-guide` page, append a
 *      link to either Flatsome's footer block (preferred, since user is
 *      using Flatsome Footer Builder) or a registered nav-menu location.
 *   2. DETACH — when the Run-All step decides this clone shouldn't have
 *      a Size Guide page (industry changed away from apparel niches),
 *      strip the link by `data-cmc-slug` so the footer stops advertising
 *      a now-trashed page.
 *
 * Injection strategy (in order of preference):
 *
 *   1. FLATSOME UX MENU SHORTCODE — locate the existing Sitemap entry
 *      written as `[ux_menu_link text="Sitemap" ...]` (the way Flatsome
 *      Footer Builder authors menus) and inject a sibling
 *      `[ux_menu_link text="Size Guide" post="<id>"]` right after it,
 *      wrapped in HTML comments so detach can find it precisely.
 *
 *   2. HTML ANCHOR — for footer blocks that author menus as raw HTML,
 *      match `<li><a>Sitemap</a></li>` or `<a>Sitemap</a>` and inject
 *      a sibling carrying `data-cmc-slug`.
 *
 *   3. WRAPPER ROW (fallback) — if neither markup style is found,
 *      append `[row class="cmc-auto-footer-links"]…[/row]` at the end
 *      of the block. Styled via the `.cmc-auto-footer-links*` CSS
 *      hooks the child theme can target.
 *
 * Every injection carries one of two markers — `data-cmc-slug` for the
 * HTML cases or surrounding `<!-- cmc-auto:<slug> -->` comments for
 * the shortcode case — so detach can remove the right element
 * without touching human-edited links.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Size_Guide {

    /** CSS class on the wrapper [row] — unique enough to grep safely. */
    private const WRAPPER_CLASS = 'cmc-auto-footer-links';

    /** CSS class on the inner <ul> that holds the auto-managed links. */
    private const LIST_CLASS = 'cmc-auto-footer-links__list';

    /** CSS class on each <li> — also useful as a per-item selector. */
    private const ITEM_CLASS = 'cmc-auto-footer-link';

    /**
     * Visible text of the anchor link we try to inject AFTER. Stores
     * usually have "Sitemap" as the last entry in their footer
     * "Information" column, so injecting after it places the niche
     * link at the natural tail of that menu.
     *
     * Filterable via `cmc_size_guide_footer_anchor_text` for stores
     * whose footer uses a different last-item label (e.g. "FAQ",
     * "Help Center").
     */
    private const DEFAULT_ANCHOR_TEXT = 'Sitemap';

    /**
     * Theme nav-location slugs we consider "the footer menu" — checked
     * only as a fallback when no Flatsome footer block is configured.
     */
    private const FOOTER_LOCATIONS = [
        'footer_nav',
        'secondary_navigation',
        'footer',
    ];

    public static function init(): void {
        add_action( 'cmc_cloner_page_updated', [ self::class, 'maybe_attach_to_footer' ], 10, 3 );
    }

    /**
     * Listener: only the `size-guide` template triggers a footer attach.
     */
    public static function maybe_attach_to_footer( int $page_id, string $template_slug, string $skeleton_slug ): void {
        unset( $skeleton_slug );
        if ( $template_slug !== 'size-guide' || $page_id <= 0 ) {
            return;
        }
        self::attach_page( $page_id, 'size-guide' );
    }

    /**
     * Public entry-point for the Run-All cleanup branch. Removes any
     * footer link tagged with the given slug. Idempotent.
     */
    public static function detach_slug( string $slug ): void {
        self::detach_from_flatsome_block( $slug );
        self::detach_from_nav_menu( $slug );
    }

    // -------- ATTACH ------------------------------------------------------

    /**
     * Try Flatsome footer block first (covers the Footer Builder case),
     * then fall back to a registered nav menu. Silently no-ops when
     * neither target exists — link can be added manually.
     */
    private static function attach_page( int $page_id, string $slug ): void {
        $page = get_post( $page_id );
        if ( ! $page || $page->post_type !== 'page' ) {
            return;
        }

        if ( self::attach_to_flatsome_block( $page_id, $slug ) ) {
            return;
        }
        self::attach_to_nav_menu( $page_id );
    }

    /**
     * Locate the UX Block selected as the Flatsome footer, then either
     * inject the new link right after an existing "Sitemap" anchor
     * (preferred — link inherits column styling) or fall back to a
     * dedicated wrapper row appended at the end.
     *
     * Returns true when the block was found and the link is now present.
     */
    private static function attach_to_flatsome_block( int $page_id, string $slug ): bool {
        $block = self::get_flatsome_footer_block();
        if ( ! $block ) {
            return false;
        }

        $url   = get_permalink( $page_id );
        $title = wp_strip_all_tags( (string) get_the_title( $page_id ) );
        if ( ! $url || $title === '' ) {
            return false;
        }

        $content      = (string) $block->post_content;
        $marker_attr  = 'data-cmc-slug="' . esc_attr( $slug ) . '"';
        $marker_short = '<!-- cmc-auto:' . $slug . ' -->';

        // Auto-migrate: if a prior pass placed this slug somewhere
        // (e.g. the old bottom-row layout before the shortcode/anchor
        // refactor), strip it first so the fresh placement always wins.
        // Safe — we only touch elements/blocks we own via marker.
        $original = $content;
        if ( strpos( $content, $marker_attr ) !== false || strpos( $content, $marker_short ) !== false ) {
            $content = self::strip_slug_from_block_content( $content, $slug );
        }

        $new_content = self::inject_after_anchor( $content, $page_id, $url, $title, $slug );
        if ( $new_content === null ) {
            $new_content = self::append_wrapper_row( $content, $url, $title, $slug );
        }

        if ( $new_content === null || $new_content === $original ) {
            return false;
        }

        $update = wp_update_post( [
            'ID'           => (int) $block->ID,
            'post_content' => $new_content,
        ], true );

        return ! is_wp_error( $update );
    }

    /**
     * Pure-string variant of detach — returns the cleaned content
     * instead of writing it. Shared between attach (for migration) and
     * detach (for cleanup). Strips three injection styles in order:
     *
     *   - `<!-- cmc-auto:<slug> -->…<!-- /cmc-auto:<slug> -->` block
     *     (shortcode injection — covers `[ux_menu_link]` case)
     *   - `<li ... data-cmc-slug="<slug>">…</li>` (HTML list-item)
     *   - `<a ... data-cmc-slug="<slug>">…</a>` (HTML bare anchor)
     */
    private static function strip_slug_from_block_content( string $content, string $slug ): string {
        $slug_re      = preg_quote( esc_attr( $slug ), '/' );
        $marker_open  = preg_quote( '<!-- cmc-auto:' . $slug . ' -->', '/' );
        $marker_close = preg_quote( '<!-- /cmc-auto:' . $slug . ' -->', '/' );

        // 1. Comment-bracketed block (shortcode injection).
        $marker_pattern = '/\s*' . $marker_open . '.*?' . $marker_close . '\s*/s';
        $stripped       = preg_replace( $marker_pattern, "\n", $content, 1 );
        if ( is_string( $stripped ) ) {
            $content = $stripped;
        }

        // 2. List-item HTML carrying the data marker.
        $li_pattern = '/\s*<li\b[^>]*data-cmc-slug="' . $slug_re . '"[^>]*>.*?<\/li>\s*/s';
        $stripped   = preg_replace( $li_pattern, "\n", $content, 1 );
        if ( is_string( $stripped ) ) {
            $content = $stripped;
        }

        // 3. Bare anchor carrying the data marker.
        $a_pattern = '/\s*<a\b[^>]*data-cmc-slug="' . $slug_re . '"[^>]*>.*?<\/a>\s*/s';
        $stripped  = preg_replace( $a_pattern, "\n", $content, 1 );
        if ( is_string( $stripped ) ) {
            $content = $stripped;
        }

        // 4. Collapse the fallback wrapper [row] if its <ul> is empty.
        $empty_ul = '/<ul class="' . preg_quote( self::LIST_CLASS, '/' ) . '">\s*<\/ul>/s';
        if ( preg_match( $empty_ul, $content ) ) {
            $row_pat  = '/\s*\[row class="' . preg_quote( self::WRAPPER_CLASS, '/' ) . '"\].*?\[\/row\]\s*/s';
            $stripped = preg_replace( $row_pat, "\n", $content, 1 );
            if ( is_string( $stripped ) ) {
                $content = $stripped;
            }
        }

        return $content;
    }

    /**
     * Splice the new link in immediately after the configured anchor
     * (default: "Sitemap"). Tries three markup styles in priority
     * order — Flatsome `[ux_menu_link]` shortcode (most common in the
     * Footer Builder), then `<li><a>` list-item HTML, then bare `<a>`.
     *
     * Returns the patched content, or null if no anchor was found.
     */
    private static function inject_after_anchor( string $content, int $page_id, string $url, string $title, string $slug ): ?string {
        $anchor_text = (string) apply_filters( 'cmc_size_guide_footer_anchor_text', self::DEFAULT_ANCHOR_TEXT, $slug );
        $anchor_re   = preg_quote( $anchor_text, '/' );
        $anchor_pad  = '\s*' . $anchor_re . '\s*';
        $slug_attr   = esc_attr( $slug );

        // Pattern 0 (highest priority): Flatsome shortcode menu link.
        // Matches `[ux_menu_link text="Sitemap" ...]` (or single-quoted)
        // anywhere among the other attributes. Inserts a sibling
        // `[ux_menu_link text="<title>" post="<id>"]` wrapped in
        // HTML comments that act as detach markers. `post=<id>` lets
        // Flatsome resolve the permalink + automatic noindex/redirect
        // handling instead of hard-coding a URL.
        $ux_pattern = '/(\[ux_menu_link\b[^\]]*?\btext=(?:"' . $anchor_pad . '"|\'' . $anchor_pad . '\')[^\]]*\])/i';
        if ( preg_match( $ux_pattern, $content ) ) {
            $new_link = '[ux_menu_link text="' . esc_attr( $title ) . '" post="' . (int) $page_id . '"]';
            $marker_open  = '<!-- cmc-auto:' . $slug . ' -->';
            $marker_close = '<!-- /cmc-auto:' . $slug . ' -->';
            $injection    = "\n" . $marker_open . "\n" . $new_link . "\n" . $marker_close;
            return preg_replace( $ux_pattern, '$1' . $injection, $content, 1 );
        }

        // Pattern 1: anchor wrapped in <li>...</li>. Tolerant matcher —
        // allows nested inline tags ("<a><span>Sitemap</span></a>") and
        // arbitrary whitespace. Injects a new <li> right after the
        // closing </li> so it lands in the same list.
        $li_pattern = '/(<li\b[^>]*>\s*<a\b[^>]*>(?:(?!<\/a>).)*?'
                    . $anchor_re
                    . '(?:(?!<\/a>).)*?<\/a>\s*<\/li>)/is';
        if ( preg_match( $li_pattern, $content ) ) {
            $new_li = "\n" . '<li class="' . esc_attr( self::ITEM_CLASS ) . '" data-cmc-slug="' . $slug_attr . '">'
                    . '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>'
                    . '</li>';
            return preg_replace( $li_pattern, '$1' . $new_li, $content, 1 );
        }

        // Pattern 2: bare anchor. Same tolerance as above. Injects a
        // sibling <a> after </a> so the column's whitespace-separator
        // rendering picks it up like any other plain link.
        $a_pattern = '/(<a\b[^>]*>(?:(?!<\/a>).)*?'
                   . $anchor_re
                   . '(?:(?!<\/a>).)*?<\/a>)/is';
        if ( preg_match( $a_pattern, $content ) ) {
            $new_a = "\n" . '<a href="' . esc_url( $url ) . '" data-cmc-slug="' . $slug_attr . '">'
                   . esc_html( $title ) . '</a>';
            return preg_replace( $a_pattern, '$1' . $new_a, $content, 1 );
        }

        return null;
    }

    /**
     * Fallback: append a self-styled wrapper row at the end of the
     * footer block. Used only when no anchor link was found. Reuses
     * the existing `cmc-auto-footer-links*` CSS hooks so a single
     * child-theme stylesheet covers every clone.
     */
    private static function append_wrapper_row( string $content, string $url, string $title, string $slug ): string {
        $slug_attr  = esc_attr( $slug );
        $list_class = self::LIST_CLASS;

        $new_li = '<li class="' . esc_attr( self::ITEM_CLASS ) . '" data-cmc-slug="' . $slug_attr . '">'
                . '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>'
                . '</li>';

        if ( strpos( $content, $list_class ) === false ) {
            $row = "\n\n[row class=\"" . esc_attr( self::WRAPPER_CLASS ) . "\"]\n"
                 . "[col span=\"12\"]\n"
                 . '<ul class="' . esc_attr( $list_class ) . '">' . "\n"
                 . $new_li . "\n"
                 . "</ul>\n"
                 . "[/col]\n"
                 . "[/row]\n";
            return $content . $row;
        }

        // Wrapper already exists from a previous niche page — splice the
        // new <li> in just before the first </ul> that follows.
        $pattern = '/(' . preg_quote( $list_class, '/' ) . '"[^>]*>)((?:(?!<\/ul>).)*)<\/ul>/s';
        $new_content = preg_replace(
            $pattern,
            '$1$2' . "\n" . $new_li . "\n" . '</ul>',
            $content,
            1
        );
        return is_string( $new_content ) ? $new_content : $content;
    }

    /**
     * Fallback: a real WP nav menu is configured at one of the footer
     * locations. Add the page as a menu item if not already present.
     */
    private static function attach_to_nav_menu( int $page_id ): void {
        $menu_id = self::resolve_footer_menu_id();
        if ( $menu_id <= 0 ) {
            return;
        }

        $items = wp_get_nav_menu_items( $menu_id );
        if ( is_array( $items ) ) {
            foreach ( $items as $item ) {
                if ( (int) $item->object_id === $page_id && $item->type === 'post_type' && $item->object === 'page' ) {
                    return;
                }
            }
        }

        wp_update_nav_menu_item( $menu_id, 0, [
            'menu-item-object'    => 'page',
            'menu-item-object-id' => $page_id,
            'menu-item-type'      => 'post_type',
            'menu-item-title'     => wp_strip_all_tags( (string) get_the_title( $page_id ) ),
            'menu-item-status'    => 'publish',
        ] );
    }

    // -------- DETACH ------------------------------------------------------

    /**
     * Remove any element tagged with `data-cmc-slug="<slug>"` from the
     * Flatsome footer block. Handles both anchor-injection
     * (`<a data-cmc-slug>`) and fallback wrapper-row
     * (`<li data-cmc-slug>` inside the auto-managed `<ul>`). Collapses
     * an empty wrapper row when nothing else is left inside.
     */
    private static function detach_from_flatsome_block( string $slug ): void {
        $block = self::get_flatsome_footer_block();
        if ( ! $block ) {
            return;
        }

        $content      = (string) $block->post_content;
        $marker_attr  = 'data-cmc-slug="' . esc_attr( $slug ) . '"';
        $marker_short = '<!-- cmc-auto:' . $slug . ' -->';
        if ( strpos( $content, $marker_attr ) === false && strpos( $content, $marker_short ) === false ) {
            return;
        }

        $new_content = self::strip_slug_from_block_content( $content, $slug );
        if ( $new_content === $content ) {
            return;
        }

        wp_update_post( [
            'ID'           => (int) $block->ID,
            'post_content' => $new_content,
        ] );
    }

    /**
     * Remove the page from any footer nav-menu it's attached to.
     */
    private static function detach_from_nav_menu( string $slug ): void {
        $menu_id = self::resolve_footer_menu_id();
        if ( $menu_id <= 0 ) {
            return;
        }
        $page = get_page_by_path( $slug, OBJECT, 'page' );
        if ( ! $page ) {
            return;
        }
        $items = wp_get_nav_menu_items( $menu_id );
        if ( ! is_array( $items ) ) {
            return;
        }
        foreach ( $items as $item ) {
            if ( (int) $item->object_id === (int) $page->ID && $item->type === 'post_type' && $item->object === 'page' ) {
                wp_delete_post( (int) $item->ID, true );
            }
        }
    }

    // -------- LOOKUPS -----------------------------------------------------

    /**
     * Resolve the UX Block selected as Flatsome's footer. Flatsome stores
     * the block's *slug* (post_name) under theme_mod `footer_block`,
     * which it then renders via `[block id="<slug>"]`.
     */
    private static function get_flatsome_footer_block(): ?WP_Post {
        $block_slug = (string) get_theme_mod( 'footer_block', '' );
        if ( $block_slug === '' ) {
            return null;
        }
        // UX Builder blocks live under post_type=blocks.
        $block = get_page_by_path( $block_slug, OBJECT, 'blocks' );
        if ( $block instanceof WP_Post ) {
            return $block;
        }
        // Fallback: some installs store the numeric ID instead of slug.
        if ( ctype_digit( $block_slug ) ) {
            $p = get_post( (int) $block_slug );
            if ( $p && $p->post_type === 'blocks' ) {
                return $p;
            }
        }
        return null;
    }

    private static function resolve_footer_menu_id(): int {
        $locations = get_nav_menu_locations();
        if ( ! is_array( $locations ) ) {
            return 0;
        }
        foreach ( self::FOOTER_LOCATIONS as $loc ) {
            if ( ! empty( $locations[ $loc ] ) ) {
                return (int) $locations[ $loc ];
            }
        }
        return 0;
    }
}
