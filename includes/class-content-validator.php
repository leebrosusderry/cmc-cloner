<?php
/**
 * CMC Cloner — Generated-content validator.
 *
 * Server-side check that runs AFTER the AI returns content but BEFORE
 * we save it to wp_posts. Catches industry-leakage / under-anchoring
 * patterns that slip past the prompt-side rules, so we can re-prompt
 * the model with the specific failure reasons.
 *
 * Three checks:
 *
 *   1. INDUSTRY MENTION FREQUENCY — the configured `{{nganh_hang}}`
 *      value (or a close variant) must appear in the body text at
 *      least N times (per-template threshold). A page that never says
 *      what it sells is generic by definition; the GMC reviewer can't
 *      tell the industry from the body and flags it.
 *
 *   2. CROSS-DOMAIN BLACKLIST — runs `CMC_Industry_Blacklist::violations`
 *      against the body. Phrases like "your space" / "your wardrobe" /
 *      "your routine" are flagged unless the industry whitelists them.
 *
 *   3. CRITICAL-SECTION ANCHOR — for About Us specifically, the section
 *      that's supposed to name the offering ("What We Offer") must
 *      contain at least one industry mention. Soft-fails Contact Us
 *      because that page is mostly contact details.
 *
 * Failure model:
 *   - validate() returns `{ pass: bool, reasons: list<string> }`.
 *   - The caller (bulk-one AJAX handler) re-prompts up to 2 times with
 *     the reasons appended to the prompt as a "previous attempt failed"
 *     footer.
 *   - If still failing after 2 retries, the caller saves the final
 *     attempt anyway and flags the page with a postmeta warning so the
 *     user can review.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Content_Validator {

    /**
     * Per-template minimum mention count for `{{nganh_hang}}` in body text.
     *
     * Contact Us intentionally omitted — that page is mostly contact
     * details (address, email, phone, hours), forcing industry words
     * there reads as keyword-stuffing.
     */
    private const MIN_MENTIONS = [
        'about-us' => 2,
    ];

    /**
     * @return array{ pass:bool, reasons:list<string>, mentions:int, blacklist_hits:list<array{phrase:string,allowed_keywords:list<string>}> }
     */
    public static function validate( string $content, string $template_slug, string $industry ): array {
        $reasons    = [];
        $body       = self::extract_body_text( $content );
        $industry   = trim( $industry );

        if ( $industry === '' ) {
            // Without an industry configured we can't check anything
            // meaningful. Pass through so we don't block generation on
            // a half-set-up site.
            return [
                'pass'           => true,
                'reasons'        => [],
                'mentions'       => 0,
                'blacklist_hits' => [],
            ];
        }

        // -------- Check 1: industry mention frequency --------
        $mentions   = self::count_industry_mentions( $body, $industry );
        $threshold  = self::MIN_MENTIONS[ $template_slug ] ?? 1;
        if ( $mentions < $threshold ) {
            $reasons[] = sprintf(
                'Industry name "%s" appears only %d time(s) in body text — at least %d needed for %s. Body reads as industry-neutral; re-anchor by naming "%s" (or a close variant) explicitly in the relevant sections.',
                $industry,
                $mentions,
                $threshold,
                $template_slug,
                $industry
            );
        }

        // -------- Check 2: cross-domain blacklist --------
        $hits = CMC_Industry_Blacklist::violations( $body, $industry );
        if ( ! empty( $hits ) ) {
            $phrase_list = [];
            foreach ( $hits as $hit ) {
                $phrase_list[] = '"' . $hit['phrase'] . '"';
            }
            $reasons[] = sprintf(
                'Output contains industry-leak phrases that are wrong for "%s": %s. These phrases imply a different industry context. Rewrite with vocabulary that matches "%s" specifically.',
                $industry,
                implode( ', ', $phrase_list ),
                $industry
            );
        }

        // -------- Check 3: critical-section anchor (About Us only) --------
        if ( $template_slug === 'about-us' ) {
            // The slot is filled into the skeleton as the paragraph
            // immediately after a heading containing "Offer" or "What
            // We Offer". Cheap heuristic: grab text between that heading
            // and the next <h2>/<h3>, check for industry mention.
            $offer_chunk = self::extract_what_we_offer_chunk( $content );
            if ( $offer_chunk !== '' && self::count_industry_mentions( $offer_chunk, $industry ) < 1 ) {
                $reasons[] = sprintf(
                    'The "What We Offer" section does not name "%s" — that section is the GMC reviewer\'s primary signal for what the store sells. Mention "%s" at least once inside it.',
                    $industry,
                    $industry
                );
            }
        }

        return [
            'pass'           => empty( $reasons ),
            'reasons'        => $reasons,
            'mentions'       => $mentions,
            'blacklist_hits' => $hits,
        ];
    }

    /**
     * Strip Flatsome shortcodes + HTML tags down to just the human-readable
     * body text. Used to count mentions and run regex without false hits
     * on attribute values like `data-section="contact-us"`.
     */
    private static function extract_body_text( string $content ): string {
        // Strip [shortcode ...] and [/shortcode] — they often carry
        // attribute strings that contain the industry name as label,
        // but those don't count as body anchors.
        $stripped = preg_replace( '/\[[^\]]+\]/', ' ', $content );
        if ( ! is_string( $stripped ) ) {
            $stripped = $content;
        }
        // Strip HTML tags. wp_strip_all_tags also removes contents of
        // <script>/<style> which is exactly what we want.
        $text = wp_strip_all_tags( $stripped, true );
        // Collapse whitespace so " industry " (with space-padding for
        // word-boundary safety) works reliably.
        $text = preg_replace( '/\s+/u', ' ', $text );
        return $text === null ? '' : $text;
    }

    /**
     * Count case-insensitive mentions of the industry term (and the most
     * obvious morphological variants — lowercased, with/without trailing
     * "s") in `$body`. Returns 0 when industry is empty.
     */
    private static function count_industry_mentions( string $body, string $industry ): int {
        if ( $industry === '' || $body === '' ) {
            return 0;
        }
        $needles = [ $industry ];

        // Add a lowercase variant if the original wasn't already.
        $lower = mb_strtolower( $industry );
        if ( $lower !== $industry ) {
            $needles[] = $lower;
        }

        // Light plural / singular tolerance: "T-Shirts" ↔ "T-Shirt",
        // "Pet Supplies" ↔ "Pet Supply" are the same anchor for our
        // purposes. Strip a trailing 's' and add as another variant.
        $trim = rtrim( $lower, 's' );
        if ( $trim !== $lower && strlen( $trim ) >= 3 ) {
            $needles[] = $trim;
        }

        $body_l = mb_strtolower( $body );
        $count  = 0;
        $seen   = [];
        foreach ( $needles as $n ) {
            if ( $n === '' || isset( $seen[ $n ] ) ) { continue; }
            $seen[ $n ] = true;
            $count += substr_count( $body_l, $n );
        }
        return $count;
    }

    /**
     * Pull the body of the "What We Offer" section out of the rendered
     * About Us content. Returns the section text or '' if not located.
     *
     * Heuristic: find a heading whose text contains "Offer" or "What We
     * Offer" (case-insensitive), then grab everything until the next
     * <h2>/<h3>/<h1>.
     */
    private static function extract_what_we_offer_chunk( string $content ): string {
        if ( ! preg_match(
            '#<(h[1-4])[^>]*>\s*(?:[^<]*\b(?:What We Offer|Offerings|What We Sell|Offer)\b[^<]*)\s*</\1>(.+?)(?=<h[1-4]\b|$)#siu',
            $content,
            $m
        ) ) {
            return '';
        }
        return self::extract_body_text( $m[2] );
    }

    /**
     * Format a list of failure reasons as the prompt-augmentation block
     * we append to the retry prompt. Keeps the retry call self-contained
     * — the AI sees exactly what failed and what to fix.
     */
    public static function format_retry_footer( array $reasons ): string {
        if ( empty( $reasons ) ) { return ''; }
        $bullets = [];
        foreach ( $reasons as $r ) {
            $bullets[] = '- ' . $r;
        }
        return implode(
            "\n",
            [
                '',
                'PREVIOUS ATTEMPT FAILED automated validation. Reasons:',
                implode( "\n", $bullets ),
                '',
                'Re-generate the page with these reasons addressed. Keep all other rules from the original prompt intact.',
            ]
        );
    }
}
