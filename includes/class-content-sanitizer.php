<?php
/**
 * Content Sanitizer — two responsibilities around company-info fields
 * that have no value configured (empty string in Settings):
 *
 *   1. prompt_guard_block() — an instruction appended to the AI prompt so
 *      the model avoids referencing unavailable {{placeholder}} fields in
 *      the first place.
 *   2. sanitize() — a safety-net cleanup on generated content that strips
 *      sentence fragments and HTML wrappers that would render broken
 *      English ("or call us at .") and any {{placeholder}} tokens the AI
 *      failed to consume.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Content_Sanitizer {

    /**
     * Map of company-info shortcode tag → settings field key.
     * nganh-hang is intentionally excluded: it has a default fallback and
     * is never empty at runtime.
     */
    private const TAG_FIELD_MAP = [
        'dia-chi'          => 'dia_chi',
        'so-dien-thoai'    => 'so_dien_thoai',
        'email-web'        => 'email_web',
        'ten-web'          => 'ten_web',
        'ten-doanh-nghiep' => 'ten_doanh_nghiep',
    ];

    /**
     * @return list<string>  Shortcode tags whose configured value is empty.
     */
    public static function empty_tags(): array {
        $s  = CMC_Settings::get();
        $ci = (array) ( $s['company_info'] ?? [] );

        $empty = [];
        foreach ( self::TAG_FIELD_MAP as $tag => $field ) {
            if ( trim( (string) ( $ci[ $field ] ?? '' ) ) === '' ) {
                $empty[] = $tag;
            }
        }
        return $empty;
    }

    /**
     * Company-info field keys whose `{{placeholder}}` values are substituted
     * literally into the prompt. Keys here are the placeholder names the AI
     * sees in prompts; values are the matching Settings field keys.
     *
     * nganh_hang / primary_color / service commitments have hardcoded
     * fallbacks, so they're never empty at prompt-build time — excluded.
     */
    private const PLACEHOLDER_FIELD_MAP = [
        'ten_web'          => 'ten_web',
        'ten_doanh_nghiep' => 'ten_doanh_nghiep',
        'email_web'        => 'email_web',
        'so_dien_thoai'    => 'so_dien_thoai',
        'dia_chi'          => 'dia_chi',
    ];

    /**
     * @return list<string>  Placeholder names whose configured value is empty.
     */
    public static function empty_placeholders(): array {
        $s  = CMC_Settings::get();
        $ci = (array) ( $s['company_info'] ?? [] );

        $empty = [];
        foreach ( self::PLACEHOLDER_FIELD_MAP as $placeholder => $field ) {
            if ( trim( (string) ( $ci[ $field ] ?? '' ) ) === '' ) {
                $empty[] = $placeholder;
            }
        }
        return $empty;
    }

    /**
     * Instruction block appended to the AI prompt telling the model not to
     * reference unavailable {{placeholder}} fields. Returns empty string
     * when every field has a value (no guard needed).
     *
     * Appended AFTER `strtr()` substitution in the Prompt Builder, so the
     * literal `{{placeholder}}` names shown here survive to reach the AI.
     */
    public static function prompt_guard_block(): string {
        $empty = self::empty_placeholders();
        if ( empty( $empty ) ) {
            return '';
        }
        $list = '{{' . implode( '}}, {{', $empty ) . '}}';

        return "\n\n[IMPORTANT — UNAVAILABLE FIELDS]\n"
            . "The following fields have NO value configured and were substituted as an empty string: {$list}.\n"
            . "Do NOT include any line, bullet, label, or sentence that would reference one of these fields. "
            . "Specifically: if the required section or skeleton asks you to write something like "
            . "\"Phone: \", \"Email: \", \"Address: \", or \"phone , email \" for one of these empty fields, "
            . "OMIT that fragment entirely — drop the label, the separator, and any surrounding parenthesis or bullet. "
            . "If a sentence would need one of these fields, rewrite it using only the fields that have values, "
            . "or skip that detail. Never invent placeholder values, fake phone numbers, fake emails, or stand-in addresses.\n";
    }

    /**
     * Site-wide scope / voice / consistency rules appended to every AI prompt.
     * Industry-agnostic — relies on the configured industry label and the
     * optional Store Focus field for the per-site niche signal.
     *
     * - Scope: if Store Focus is set it is the authoritative niche; otherwise
     *   write at the industry label's breadth. Either way, ignore sub-niche
     *   cues that came from the source page.
     * - Voice: same tone across every page of the site.
     * - Consistency: no invented facts that would conflict between pages.
     */
    public static function prompt_consistency_block(): string {
        $s          = CMC_Settings::get();
        $ci         = (array) ( $s['company_info'] ?? [] );
        $ten_web    = trim( (string) ( $ci['ten_web']              ?? '' ) );
        $focus      = trim( (string) ( $ci['dinh_huong_san_pham']  ?? '' ) );
        $nganh_slug = (string) ( $ci['nganh_hang'] ?? '' );
        $nganh_opts = CMC_Shortcodes::nganh_hang_options();
        $nganh_lbl  = (string) ( $nganh_opts[ $nganh_slug ] ?? $nganh_slug );

        $store_name = $ten_web !== '' ? $ten_web : 'this store';

        if ( $focus !== '' ) {
            $scope_line = "SCOPE — AUTHORITATIVE: the store's product focus is \"{$focus}\". "
                . "Write every example, category, story, and use-case at exactly this focus level. "
                . "This signal OVERRIDES any different niche implied by the source page or the broader industry label.";
        } else {
            $scope_line = "SCOPE: the store operates in \"{$nganh_lbl}\". "
                . "Write at this industry level. Do NOT narrow to a sub-niche (e.g., children, men's, women's, luxury, running, casual) "
                . "unless that sub-niche is literally part of the industry label above.";
        }

        return "\n\n[SCOPE, VOICE & CONSISTENCY — APPLIES TO EVERY PAGE OF {$store_name}]\n"
            . "{$scope_line}\n"
            . "Source-page bleed: the SOURCE PAGE below may belong to a store in a different sub-niche. "
            . "IGNORE its demographic/specialty framing. Strip any sub-niche specificity (target gender, "
            . "age group, activity, lifestyle) from names, use-cases, testimonials, and category lists — "
            . "rewrite with neutral language consistent with the SCOPE above.\n"
            . "Voice: warm, professional, concrete, concise. No marketing fluff (\"world-class\", "
            . "\"industry-leading\", \"one-stop solution\"), no exaggerations, no emojis, no guarantees "
            . "that are not stated in the source. Prefer specific nouns over adjectives.\n"
            . "Consistency across pages: never invent facts that would conflict with other pages of the "
            . "site — no invented founding years, headcounts, awards, founders, physical locations, "
            . "carriers, jurisdictions, or service guarantees not present in the source. Reuse store "
            . "name, industry, and contact details exactly as given in COMPANY CONTEXT.\n"
            . "Category-level language (industry-agnostic rule — applies to EVERY niche):\n"
            . "  Definition — a 'specific product sub-type' is any noun, in any language or "
            . "niche, that (a) names a concrete retail item more specific than the SCOPE above, "
            . "(b) could appear as a leaf in the Google Product Taxonomy or as a feed row, or "
            . "(c) naturally follows 'I bought a ___' / 'a box of ___'. If a noun passes any "
            . "of those tests, it is BANNED on this page.\n"
            . "  Diagnostic before writing each sentence — ask: 'could a GMC reviewer grep the "
            . "feed for this exact noun?' If yes, replace it with an abstract set noun.\n"
            . "  Banned patterns regardless of niche: (1) enumeration lists in any form — "
            . "'X, Y, and Z', 'including A, B, and C', 'such as P, Q, and R', 'from <thing> to "
            . "<thing>'; the list shape itself is banned before the nouns are even checked. "
            . "(2) sub-type nouns with modifiers — 'premium <thing>', 'handcrafted <thing>', "
            . "'<material> <thing>', '<colour> <thing>' — the dressing does not unban the noun. "
            . "(3) compound dodges — '<thing>-<thing>', '<thing> and <thing> sets', "
            . "'<thing>-style <thing>'. (4) specific materials tied to an item — 'leather <thing>' "
            . "is banned even though 'natural materials' alone is fine. (5) demographic / "
            . "use-case narrowing that implies a sub-type ('for runners', 'for toddlers', "
            . "'for new mums') unless that framing is literally part of the SCOPE above.\n"
            . "  Allowed vocabulary (industry-agnostic): the SCOPE label itself used at most "
            . "once; abstract set nouns — 'the range', 'our selection', 'pieces', 'essentials', "
            . "'favourites', 'the collection', 'what we carry', 'what we make'; emotional / "
            . "process language — 'carefully curated', 'thoughtfully chosen', 'built to last', "
            . "'made with care', 'designed around everyday life'; generic category-level "
            . "materials / values when NOT tied to an item — 'natural materials', 'honest "
            . "materials', 'quality fabrics', 'considered design'.\n"
            . "  Never invent collection, line, edit, capsule, or drop names. Only reuse a "
            . "collection name when it literally appears in the SOURCE PAGE below; otherwise "
            . "omit it entirely.\n";
    }

    /**
     * Cross-page numeric-consistency block — appended to every prompt so
     * the AI never writes a "shipping cut-off", "return window", "refund
     * processing time", "free-shipping threshold", or "below-threshold
     * fee" value that contradicts the same value on another generated
     * page. GMC reviewers compare Shipping ↔ Return & Refund ↔ FAQ ↔
     * Cancellation when auditing a store; any mismatch (e.g. FAQ says
     * "30 days" but Cancellation says "14 days") is treated as
     * Misrepresentation and can suspend the account.
     *
     * This block lists the CURRENT authoritative values resolved from
     * Settings and instructs the AI to use them literally — even when
     * the source page or a prior page on the site contradicts them.
     */
    public static function cross_page_consistency_block(): string {
        $cutoff   = CMC_Settings::shipping_commitment( 'shipping_cutoff_time' );
        $handling = CMC_Settings::shipping_commitment( 'shipping_handling_time' );
        $transit  = CMC_Settings::shipping_commitment( 'shipping_transit_time' );
        $total    = CMC_Settings::shipping_commitment( 'shipping_total_delivery' );
        $window   = CMC_Settings::service_commitment( 'return_window' );
        $refund   = CMC_Settings::service_commitment( 'refund_processing_time' );
        $rma      = CMC_Settings::service_commitment( 'rma_issuance_time' );
        $hours    = CMC_Settings::service_commitment( 'gio_lam_viec' );
        $resp     = CMC_Settings::service_commitment( 'response_time' );
        $s        = CMC_Settings::get();
        $free_thr = (string) ( $s['free_shipping_threshold'] ?? '$75' );
        $flat_fee = (string) ( $s['below_threshold_shipping_fee'] ?? '$4.99' );

        return "\n\n[CROSS-PAGE CONSISTENCY — APPLIES TO EVERY GENERATED PAGE]\n"
            . "These values are AUTHORITATIVE across the entire site. Whenever the page "
            . "mentions any of these topics, write the literal value listed here. Never "
            . "paraphrase a number into a different one, never round to a different bucket, "
            . "and never invent an alternative even if the SOURCE PAGE below carries a "
            . "different value. GMC compares these values across pages — any mismatch is "
            . "treated as Misrepresentation.\n"
            . "  • Order cut-off time:           {$cutoff}\n"
            . "  • Shipping handling time:       {$handling}\n"
            . "  • Shipping transit time:        {$transit}\n"
            . "  • Total estimated delivery:     {$total}\n"
            . "  • Return window:                {$window} from the date of delivery\n"
            . "  • Refund processing time:       {$refund}\n"
            . "  • RMA issuance time:            {$rma}\n"
            . "  • Free-shipping threshold:      {$free_thr}\n"
            . "  • Below-threshold flat fee:     {$flat_fee}\n"
            . "  • Support hours:                {$hours}\n"
            . "  • Response time:                {$resp}\n"
            . "TIMEZONE PRESERVATION: every mention of the cut-off or support hours MUST "
            . "preserve any timezone abbreviation already present (e.g. \"(MST)\", \"(PST)\", "
            . "\"(UTC+7)\"). Do NOT paraphrase or strip the parenthetical. If the value has "
            . "no timezone, do NOT invent one.\n"
            . "CANCELLATION ALIGNMENT: any cancellation rule on this page MUST match the "
            . "cut-off above — \"requests must reach support before {$cutoff} on the dispatch "
            . "day\". Do NOT introduce alternative cancel windows like \"within 1 hour of "
            . "ordering\" unless the SOURCE PAGE explicitly states them.\n"
            . "POLICY PAGE NAMES: when pointing to a sibling policy, use the canonical names — "
            . "\"Shipping Policy\", \"Return & Refund Policy\" (single combined page), "
            . "\"Cancellation Policy\", \"Privacy Policy\", \"Cookie Policy\". Never write "
            . "\"Return Policy\" or \"Refund Policy\" as separate pages.\n"
            . "LEGAL-NAME SPACING (TYPO PREVENTION): every reference to the store name and the legal "
            . "business name MUST be preceded by a space, comma, period, parenthesis, or sentence start — "
            . "NEVER concatenated to the previous word. Example mistakes to AVOID: \"the Controller{Name} \", "
            . "\"asProcessor{Name}\", \"by{Name}\", \"is{Name}\". Correct forms: \"the Controller, {Name}\", "
            . "\"as Processor, {Name}\", \"by {Name}\", \"is {Name}\". This applies in ALL phrasing — GDPR "
            . "controller/processor disclosures, copyright lines, contact blocks, and footer mentions.\n"
            . "CONTACT BLOCK FORMAT — FULL-SENTENCE ONLY: every contact-information line on EVERY page "
            . "(Privacy Policy, Terms, Cookie, Cancellation, DMCA, Contact, FAQ support Q/A, etc.) MUST "
            . "be a full sentence in flowing prose. NEVER emit bare \"Label: value\" pairs of any of the "
            . "following — they get stripped by wpautop and concatenate into typos like \"ControllerMadaraca LLC\", "
            . "\"Privacysupport@shop.com\", \"Email1234567890\":\n"
            . "   • BANNED labels: \"Controller:\", \"Processor:\", \"Privacy:\", \"Privacy Officer:\", "
            . "\"DPO:\", \"Email:\", \"E-mail:\", \"Phone:\", \"Tel:\", \"Address:\", \"Fax:\", \"Contact:\", "
            . "\"Support:\", \"Helpdesk:\", \"Hours:\", \"Hotline:\".\n"
            . "Instead, write the same information as inline sentences with verbs and connectives. The "
            . "opening phrase MUST be scoped to the CURRENT PAGE's topic — do NOT default to a "
            . "privacy-coded opener on a non-privacy page. Worked openers by page type (mirror the "
            . "shape, not the literal words):\n"
            . "   • Privacy Policy / Cookie Policy → \"For privacy questions or rights requests, email <email> or call <phone>; written mail may be sent to <address>.\"\n"
            . "   • Shipping Policy                → \"For shipping questions or help tracking an order, email <email> or call <phone>; written mail may be sent to <address>.\"\n"
            . "   • Return & Refund Policy         → \"For returns, exchanges, or refund questions, email <email> or call <phone>; written mail may be sent to <address>.\"\n"
            . "   • Cancellation Policy            → \"For order cancellation requests, email <email> or call <phone>; written mail may be sent to <address>.\"\n"
            . "   • Payment Method                 → \"For payment or billing questions, email <email> or call <phone>; written mail may be sent to <address>.\"\n"
            . "   • Terms of Service               → \"For questions about these terms, email <email> or call <phone>; written mail may be sent to <address>.\"\n"
            . "   • DMCA Policy                    → \"For copyright takedown notices, email <email>; written notices may be sent to <address>.\"\n"
            . "   • FAQ / Contact / About / generic → \"For any questions, email <email> or call <phone>; written mail may be sent to <address>.\"\n"
            . "The opening noun ('privacy questions', 'shipping questions', 'returns', 'order cancellation', "
            . "'payment or billing', 'these terms', 'copyright takedown notices') MUST match the topic of "
            . "the current page. Do NOT carry over wording from a different page. Inline punctuation "
            . "(commas, semicolons, \"or\") is mandatory in every variant.\n";
    }

    /**
     * Template-scoped compliance block. Currently only privacy-policy carries
     * extra rules: when the configured industry is kids/baby/toddler/nursery/
     * maternity etc., we require expanded COPPA language (parental consent,
     * age verification, expanded disclosure). For non-kids niches this
     * returns empty — privacy-policy still gets the baseline CCPA / Do Not
     * Sell / COPPA-under-13 language via the main prompt template.
     */
    public static function kids_compliance_block( string $template_slug ): string {
        if ( $template_slug !== 'privacy-policy' ) {
            return '';
        }
        if ( ! CMC_Settings::is_kids_niche() ) {
            return '';
        }

        return "\n\n[COPPA — KIDS/BABY NICHE — EXPANDED REQUIREMENTS]\n"
            . "This site's audience is children under 13, their parents, or guardians. "
            . "The Children's Privacy section (slot {{CHILDRENS_PRIVACY}}) must expand beyond the baseline "
            . "\"we do not knowingly collect data from children under 13\" language and include ALL of the following, "
            . "in clear plain English:\n"
            . "  - Age gate: purchases and account creation are intended for adults (18+) acting on behalf of a child.\n"
            . "  - Parental / guardian consent: if a child under 13 attempts to provide personal information, we require verifiable parental consent before collection or require a parent to place the order.\n"
            . "  - Scope of collection: the limited data categories that may still be collected from a child (e.g. first name for personalization on a gift order) and the narrow purposes we use them for.\n"
            . "  - No behavioral advertising to children: we do not serve targeted advertising to users we know to be under 13, nor share their data with ad networks for profiling.\n"
            . "  - Parental rights: a parent may review, correct, or request deletion of their child's information, and instructions on how to contact {{email_web}} to exercise those rights.\n"
            . "  - Safe harbor: we will promptly delete any data inadvertently collected from a child under 13 once identified.\n"
            . "Keep wording practical and GMC-safe — no specific ages beyond 13/18 unless the source says otherwise, no invented compliance program names, no fake certifications.\n";
    }

    /**
     * Safety-net cleanup of generated content against the current set of
     * empty shortcodes. Idempotent — safe to call twice.
     *
     * @param string           $content     Raw generated content (shortcodes + HTML).
     * @param list<string>|null $empty_tags Override detection; defaults to empty_tags().
     */
    public static function sanitize( string $content, ?array $empty_tags = null ): string {
        if ( $content === '' ) {
            return $content;
        }

        $tags = $empty_tags ?? self::empty_tags();
        foreach ( $tags as $tag ) {
            $content = self::strip_tag_fragments( $content, $tag );
        }

        $content = self::strip_unfilled_placeholders( $content );
        $content = self::repair_legal_name_concatenation( $content );

        return self::normalize_stray_punctuation( $content );
    }

    /**
     * Repair AI concatenation typos around the legal business name and
     * store name. Privacy / Terms / Cancellation prompts often emit
     * phrasing like "the data Controller {{ten_doanh_nghiep}}" — when
     * the AI paraphrases that, it occasionally drops the space before
     * the company name and produces output like "ControllerMadaraca LLC".
     *
     * The repair runs at output-time so it works for ANY business name
     * configured in Settings (no hard-coding). Prompt rules try to
     * prevent the issue upstream; this method is the defense-in-depth.
     *
     * Heuristic:
     *   1. Pull both names from Settings — {{ten_doanh_nghiep}} (legal)
     *      and {{ten_web}} (store).
     *   2. For each non-empty name, scan the content for occurrences
     *      where the name is preceded by a letter/digit (no whitespace
     *      or punctuation separator) and inject a single space.
     *   3. Skip when the preceding character is already a separator,
     *      tag bracket, or HTML attribute boundary so we never break
     *      legitimate markup like `class="...moonbeamtoys-..."` or
     *      shortcode attributes `name="Madaraca LLC"`.
     */
    public static function repair_legal_name_concatenation( string $content ): string {
        if ( $content === '' || ! class_exists( 'CMC_Settings' ) ) {
            return $content;
        }

        $settings = CMC_Settings::get();
        $ci       = (array) ( $settings['company_info'] ?? [] );
        $names    = array_filter( [
            trim( (string) ( $ci['ten_doanh_nghiep'] ?? '' ) ),
            trim( (string) ( $ci['ten_web']          ?? '' ) ),
        ], static function ( string $n ): bool {
            // Skip very short names (< 3 chars) — too generic, would
            // false-match. Real business names are always longer.
            return $n !== '' && mb_strlen( $n ) >= 3;
        } );
        if ( ! $names ) {
            return $content;
        }

        // Process longer names first so "Madaraca LLC" matches before
        // a partial "Madaraca" (avoids double-spacing).
        usort( $names, static function ( string $a, string $b ): int {
            return mb_strlen( $b ) <=> mb_strlen( $a );
        } );

        foreach ( $names as $name ) {
            $escaped = preg_quote( $name, '/' );
            // Insert a space when a word/letter/digit precedes the name
            // directly (no whitespace, no punctuation, no tag boundary).
            // Negative lookbehind for: whitespace, punctuation, tag chars,
            // attribute quote, slash — leaves only "letter/digit + name"
            // patterns to fix, e.g. "ControllerMadaraca LLC".
            $pattern = '/(?<=[A-Za-z0-9])' . $escaped . '/u';
            $content = (string) preg_replace( $pattern, ' ' . $name, $content );
        }

        return $content;
    }

    /**
     * Defense-in-depth: strip any `{{PLACEHOLDER}}` token that leaked through
     * the AI output (e.g. {{CONTACT_BLOCK}}, {{ten_web}}). Prompt substitution
     * resolves all known tokens at build time, so anything still present here
     * is noise that would render literally on the frontend.
     */
    private static function strip_unfilled_placeholders( string $content ): string {
        return (string) preg_replace( '/\{\{\s*[A-Za-z0-9_]+\s*\}\}/', '', $content );
    }

    /**
     * Remove sentence fragments and HTML wrappers that exist only to render
     * a single empty shortcode.
     */
    private static function strip_tag_fragments( string $content, string $tag ): string {
        $t = preg_quote( $tag, '/' );

        // Wrapper element whose only meaningful content is this tag (optionally
        // preceded by a single label span like `<span ...__key>Call</span>`).
        $content = (string) preg_replace(
            '/<(span|div|li|p)\b[^>]*>\s*(?:<span[^>]*>[^<]*<\/span>\s*)?\[' . $t . '\]\s*<\/\1>\s*/i',
            '',
            $content
        );

        // Conjunction-led fragment: ", or call us at [tag]" / " and email us at [tag]"
        $content = (string) preg_replace(
            '/\s*(?:,\s*)?\b(?:or|and)\s+(?:(?:please\s+)?(?:call|contact|email|reach|write(?:\s+to)?|visit)\s+us\s+)?(?:at|on|via|by|through)\s+\[' . $t . '\]/i',
            '',
            $content
        );

        // Parenthetical: " ([tag])" / " (Phone: [tag])"
        $content = (string) preg_replace(
            '/\s*\([^()\n]*\[' . $t . '\][^()\n]*\)/',
            '',
            $content
        );

        // Lead-in fragment: " at [tag]" / " on [tag]" / " via [tag]"
        $content = (string) preg_replace(
            '/\s+(?:at|on|via|by|through)\s+\[' . $t . '\]/i',
            '',
            $content
        );

        // Bare leftover (including any `[tag attr=...]` form).
        $content = (string) preg_replace( '/\[' . $t . '(?:\s[^\]]*)?\]/i', '', $content );

        return $content;
    }

    /**
     * Clean up doubled punctuation, empty parens, and excess whitespace that
     * the fragment removals can leave behind.
     */
    private static function normalize_stray_punctuation( string $content ): string {
        // " ." / " ," / " !" → attach to preceding word.
        $content = (string) preg_replace( '/[ \t]+([.,;:!?])/', '$1', $content );
        // ",," / ".." → single.
        $content = (string) preg_replace( '/([.,;:!?])\1+/', '$1', $content );
        // ",." / ":," / ";." — trailing mid-punct before terminal punct: drop the mid.
        $content = (string) preg_replace( '/[,;:]+([.!?])/', '$1', $content );
        // Empty parentheses left after wiping their contents.
        $content = (string) preg_replace( '/\([ \t]*\)/', '', $content );
        // Empty inline HTML tags that only held a tag we stripped.
        $content = (string) preg_replace( '/<(span|li|p)\b[^>]*>\s*<\/\1>/i', '', $content );
        // Multiple spaces inside a line.
        $content = (string) preg_replace( '/[ \t]{2,}/', ' ', $content );
        // More than one blank line in a row.
        $content = (string) preg_replace( "/\n{3,}/", "\n\n", $content );

        return $content;
    }
}
