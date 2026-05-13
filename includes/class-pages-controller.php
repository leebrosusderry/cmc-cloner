<?php
/**
 * Pages Controller — renders the "CMC Cloner → Pages" admin screen.
 * All runtime interactions (load page, preview prompt, generate, update,
 * revert) are handled by CMC_Ajax.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Pages_Controller {

    /**
     * Keyword aliases that map a page slug/title to a template slug.
     * Used by the bulk-generate UI to pre-select a template per row.
     */
    private const AUTO_MATCH_KEYWORDS = [
        'contact-us'          => [ 'contact', 'contact-us', 'contact-me', 'lien-he' ],
        'about-us'            => [ 'about', 'about-us', 'about-me', 'gioi-thieu' ],
        // The `return-policy` slug is the combined "Return & Refund Policy"
        // template (single GMC-required return URL). Legacy refund slugs
        // (`refund`, `refunds`, `refund-policy`) route here too, and any
        // combined slug like `return-refund-policy` or `returns-and-refunds`
        // lands here as well. The exact-alias pass (step 2 in
        // auto_match_template) catches these before the substring pass has
        // a chance to mis-match a bare "refund" substring against a no-
        // longer-existent separate Refund Policy template.
        'return-policy'       => [
            'return', 'returns', 'return-policy', 'returns-policy',
            'refund', 'refunds', 'refund-policy',
            'return-refund', 'return-refund-policy', 'return-and-refund-policy',
            'returns-refunds', 'returns-and-refunds', 'returns-refund-policy',
        ],
        'shipping-policy'     => [ 'shipping', 'shipping-policy', 'delivery', 'delivery-policy' ],
        'privacy-policy'      => [ 'privacy', 'privacy-policy' ],
        'terms-of-service'    => [ 'terms', 'tos', 'terms-of-service', 'terms-and-conditions', 'terms-conditions', 'billing', 'billing-terms', 'billing-terms-conditions', 'billing-policy' ],
        'cancellation-policy' => [ 'cancellation', 'cancel', 'cancellation-policy' ],
        'payment-policy'      => [ 'payment', 'payment-policy', 'payment-method', 'payment-methods' ],
        'cookie-policy'       => [ 'cookie', 'cookies', 'cookie-policy' ],
        'dmca-policy'         => [ 'dmca', 'dmca-policy', 'dmca-notice' ],
        'track-your-order'    => [ 'track', 'tracking', 'order-tracking', 'track-order', 'track-your-order' ],
        'faq'                 => [ 'faq', 'faqs', 'questions' ],
    ];

    public static function render_page(): void {
        $pages            = CMC_Page_Reader::list_pages();
        $templates        = CMC_Template_Registry::all();
        $template_keys    = array_keys( CMC_Template_Registry::labels() );

        foreach ( $pages as &$p ) {
            $p['auto_template'] = self::auto_match_template(
                (string) ( $p['slug'] ?? '' ),
                (string) ( $p['title'] ?? '' ),
                $template_keys
            );
        }
        unset( $p );

        include CMC_CLONER_DIR . 'includes/views/pages-page.php';
    }

    /**
     * Guess the best template slug for a given page slug/title.
     * Returns '' if no confident match was found — the row is then
     * shown with a "— Pick template —" fallback and the checkbox disabled.
     */
    private static function auto_match_template( string $slug, string $title, array $registered ): string {
        $title_slug = strtolower( preg_replace( '/\s+/', '-', trim( $title ) ) );
        $candidates = array_filter( [ strtolower( $slug ), $title_slug ] );

        // 1. Exact match against a registered template slug.
        foreach ( $candidates as $c ) {
            if ( in_array( $c, $registered, true ) ) {
                return $c;
            }
        }

        // 2. Exact match against a known keyword alias.
        foreach ( $candidates as $c ) {
            foreach ( self::AUTO_MATCH_KEYWORDS as $template => $kws ) {
                if ( ! in_array( $template, $registered, true ) ) {
                    continue;
                }
                if ( in_array( $c, $kws, true ) ) {
                    return $template;
                }
            }
        }

        // 3. Substring match against any keyword alias.
        foreach ( $candidates as $c ) {
            if ( $c === '' ) {
                continue;
            }
            foreach ( self::AUTO_MATCH_KEYWORDS as $template => $kws ) {
                if ( ! in_array( $template, $registered, true ) ) {
                    continue;
                }
                foreach ( $kws as $kw ) {
                    if ( $kw !== '' && strpos( $c, $kw ) !== false ) {
                        return $template;
                    }
                }
            }
        }

        return '';
    }
}
