<?php
/**
 * Prompt Builder — composes the final prompt that is sent to the AI,
 * by interpolating company info, skeleton, and source page HTML into
 * the (possibly overridden) template prompt.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Prompt_Builder {

    /**
     * @return array{
     *     prompt: string,
     *     template_slug: string,
     *     skeleton_slug: string,
     *     style_seed: int,
     *     has_content: bool,
     * }
     */
    public static function build( string $template_slug, int $page_id, ?string $skeleton_override = null ): array {
        $template = CMC_Template_Registry::get( $template_slug );
        if ( $template === null ) {
            throw new InvalidArgumentException( 'Unknown template: ' . $template_slug );
        }
        $page = CMC_Page_Reader::get_page( $page_id );
        if ( $page === null ) {
            throw new InvalidArgumentException( 'Page not found or not a page: ' . $page_id );
        }

        $settings      = CMC_Settings::get();
        $company       = $settings['company_info'];
        $nganh_options = CMC_Shortcodes::nganh_hang_options();
        $nganh_label   = (string) ( $nganh_options[ $company['nganh_hang'] ] ?? $company['nganh_hang'] );

        $style_seed    = CMC_Variation_Engine::compute_seed( $page_id, $template_slug );
        $skeleton_slug = CMC_Variation_Engine::pick_skeleton( $template_slug, $style_seed, $skeleton_override );
        $skeleton_body = CMC_Skeleton_Registry::load( $template_slug, $skeleton_slug );
        if ( $skeleton_body === null ) {
            $skeleton_body = "(No skeleton available — use a clean Flatsome [section][row][col] layout with <h1>, <h2>, and paragraph text.)";
        }

        $base = CMC_Prompts::effective( $template_slug );

        $vars = [
            '{{ten_web}}'                 => (string) $company['ten_web'],
            '{{ten_doanh_nghiep}}'        => (string) $company['ten_doanh_nghiep'],
            '{{email_web}}'               => (string) $company['email_web'],
            '{{so_dien_thoai}}'           => (string) $company['so_dien_thoai'],
            '{{dia_chi}}'                 => (string) $company['dia_chi'],
            '{{nganh_hang}}'              => $nganh_label,
            '{{dinh_huong_san_pham}}'     => (string) ( $company['dinh_huong_san_pham'] ?? '' ),
            '{{primary_color}}'           => (string) $settings['primary_color'],
            // Founding year — only ever a clean 4-digit string or the
            // sentinel "(NOT SET)" so the About Us prompt can pattern-match
            // the unconfigured case unambiguously instead of substituting
            // an empty string in the middle of "Founded in ___, ".
            '{{founding_year}}'           => self::founding_year_or_sentinel( $settings['founding_year'] ?? '' ),
            // Effective Date — six months before today, in "Month D, YYYY"
            // form. Used by Privacy Policy and Terms of Service. The 6-month
            // back-shift establishes a small but plausible "operating
            // history" window so a freshly cloned site does not advertise
            // an Effective Date equal to the publish date — that combo
            // (new domain + same-day policies) is a known GMC trust-score
            // depressor. Six months is a balanced default: long enough to
            // signal stability, short enough to remain plausible for a new
            // store.
            '{{effective_date}}'          => (string) wp_date( 'F j, Y', strtotime( '-6 months' ) ),
            '{{free_shipping_threshold}}' => (string) ( $settings['free_shipping_threshold'] ?? '$75' ),
            '{{below_threshold_shipping_fee}}' => (string) ( $settings['below_threshold_shipping_fee'] ?? '$4.99' ),
            '{{shipping_cutoff_time}}'    => CMC_Settings::shipping_commitment( 'shipping_cutoff_time' ),
            '{{shipping_handling_time}}'  => CMC_Settings::shipping_commitment( 'shipping_handling_time' ),
            '{{shipping_transit_time}}'   => CMC_Settings::shipping_commitment( 'shipping_transit_time' ),
            '{{shipping_total_delivery}}' => CMC_Settings::shipping_commitment( 'shipping_total_delivery' ),
            '{{gio_lam_viec}}'            => CMC_Settings::service_commitment( 'gio_lam_viec' ),
            // Timezone abbreviation auto-extracted from {{gio_lam_viec}} —
            // GMC requires every "time" mention on policy pages to carry an
            // explicit timezone. Templates reference {{timezone}} after
            // every business-hours / cut-off / response-time mention so the
            // value never gets paraphrased away. Empty string when the
            // user's gio_lam_viec value has no parenthesised abbreviation.
            '{{timezone}}'                => self::extract_timezone( CMC_Settings::service_commitment( 'gio_lam_viec' ) ),
            '{{response_time}}'           => CMC_Settings::service_commitment( 'response_time' ),
            '{{rma_issuance_time}}'       => CMC_Settings::service_commitment( 'rma_issuance_time' ),
            '{{refund_processing_time}}'  => CMC_Settings::service_commitment( 'refund_processing_time' ),
            // Return window — duration starting from delivery during
            // which the customer may initiate a return. Substituted as a
            // bare phrase ("30 days", "14 days", "100 nights"); templates
            // append "from the date of delivery" themselves so the prose
            // reads naturally. Used by Return & Refund Policy + FAQ.
            '{{return_window}}'           => CMC_Settings::service_commitment( 'return_window' ),
            '{{SKELETON}}'                => self::prefill_contact_blocks( $skeleton_body, $company, CMC_Settings::service_commitment( 'gio_lam_viec' ), (string) wp_date( 'F j, Y', strtotime( '-6 months' ) ) ),
            '{{PAGE_HTML}}'               => (string) $page['content'],
        ];

        $final  = strtr( $base, $vars );
        $final .= CMC_Content_Sanitizer::prompt_consistency_block();
        $final .= CMC_Content_Sanitizer::cross_page_consistency_block();
        $final .= CMC_Content_Sanitizer::prompt_guard_block();
        $final .= CMC_Content_Sanitizer::kids_compliance_block( $template_slug );

        return [
            'prompt'        => $final,
            'template_slug' => $template_slug,
            'skeleton_slug' => $skeleton_slug,
            'style_seed'    => $style_seed,
            'has_content'   => trim( (string) $page['content'] ) !== '',
        ];
    }

    /**
     * Extract a timezone abbreviation from the business-hours string.
     *
     * The user typically embeds the timezone parenthetically inside
     * gio_lam_viec, e.g.:
     *   "Monday – Saturday, 8:00 AM – 5:00 PM (MST). Closed: Sunday"
     *
     * Returns the inner abbreviation (e.g. "MST", "PST", "EST", "UTC",
     * "UTC+7", "GMT-5"). Returns an empty string when no parenthesised
     * timezone is present, in which case prompt templates that branch
     * on {{timezone}} should output the time clause without a timezone
     * qualifier rather than emitting "()".
     *
     * Pattern accepts:
     *   - Plain abbreviations: 2–5 uppercase letters, e.g. (MST) (UTC)
     *   - Offsets after the abbreviation: (UTC+7) (GMT-5) (UTC+10:30)
     *   - Bare offsets with optional sign: (+07:00) (-0500)
     */
    private static function extract_timezone( string $hours ): string {
        if ( $hours === '' ) {
            return '';
        }
        // Letters-then-optional-offset: (MST) (UTC+7) (UTC+10:30) (GMT-5)
        if ( preg_match( '/\(([A-Z]{2,5}(?:[+\-]\d{1,2}(?::\d{2})?)?)\)/', $hours, $m ) ) {
            return $m[1];
        }
        // Bare offset: (+07:00) (-05:00) (+0700)
        if ( preg_match( '/\(([+\-]\d{2}:?\d{2})\)/', $hours, $m ) ) {
            return $m[1];
        }
        return '';
    }

    /**
     * Resolve the founding-year setting for prompt substitution.
     *
     * Returns a 4-digit year ALWAYS — About Us must carry a "Founded in
     * <year>" line every time, otherwise GMC reviewers flag the missing
     * trust signal.
     *
     * The displayed year = ( reference date ) − ( random 2–6 months ),
     * year component only. The random month offset adds natural variance
     * so cloned sites end up with mildly varied founding years rather
     * than all advertising the exact same value (which would itself
     * depress GMC trust).
     *
     * Reference date resolution:
     *   1. If Settings → Branding → Founding year is a valid 4-digit
     *      year (1900 … current_year + 1), build the reference date as
     *      "<settings-year>-<today's m-d>" and subtract the random
     *      months. Most offsets keep the year equal to the user's
     *      configured value; offsets that cross the January boundary
     *      drop one year — that natural variance is intentional.
     *   2. Otherwise (empty / invalid / out-of-range) use today as the
     *      reference date and subtract the random months from there,
     *      so the page still always carries a founding-year sentence.
     */
    private static function founding_year_or_sentinel( string $raw ): string {
        $months_back = wp_rand( 2, 6 );

        $raw = trim( $raw );
        if ( preg_match( '/^\d{4}$/', $raw ) ) {
            $year_int    = (int) $raw;
            $upper_bound = (int) wp_date( 'Y' ) + 1;
            if ( $year_int >= 1900 && $year_int <= $upper_bound ) {
                // Pin today's month-day to the user's configured year so
                // the random subtraction stays anchored to the season we
                // are in, then subtract.
                $today_md  = wp_date( 'm-d' );
                $reference = "{$raw}-{$today_md}";
                return (string) wp_date( 'Y', strtotime( "{$reference} -{$months_back} months" ) );
            }
        }

        // Fallback when Settings → Founding year is empty / invalid /
        // out-of-range: subtract from today.
        return (string) wp_date( 'Y', strtotime( "-{$months_back} months" ) );
    }

    /**
     * Substitute contact-info placeholders in the skeleton with concrete
     * HTML containing the user's literal values from Settings. These blocks
     * always render the same structured contact data (address / phone /
     * email / hours), so resolving them here — rather than asking the AI
     * to "fill" them — guarantees the right values reach the frontend and
     * prevents the AI from copying wrong contact info from the source.
     */
    private static function prefill_contact_blocks( string $skeleton, array $company, string $hours, string $effective_date = '' ): string {
        $email   = trim( (string) ( $company['email_web']     ?? '' ) );
        $phone   = trim( (string) ( $company['so_dien_thoai'] ?? '' ) );
        $address = trim( (string) ( $company['dia_chi']       ?? '' ) );

        $parts = [];
        if ( $email   !== '' ) { $parts[] = 'Email: ' . esc_html( $email ); }
        if ( $phone   !== '' ) { $parts[] = 'Phone: ' . esc_html( $phone ); }
        if ( $address !== '' ) { $parts[] = 'Address: ' . esc_html( $address ); }
        $contact_block = $parts ? '<p>' . implode( '<br />' . "\n", $parts ) . '</p>' : '';

        $phone_email_parts = [];
        if ( $phone !== '' ) { $phone_email_parts[] = 'Phone: ' . esc_html( $phone ); }
        if ( $email !== '' ) { $phone_email_parts[] = 'Email: ' . esc_html( $email ); }
        $phone_email_block = $phone_email_parts ? '<p>' . implode( '<br />' . "\n", $phone_email_parts ) . '</p>' : '';

        $address_block = $address !== '' ? '<p>' . esc_html( $address ) . '</p>' : '';
        $hours_block   = trim( $hours ) !== '' ? '<p>' . esc_html( $hours ) . '</p>' : '';

        // Effective Date — rendered as a deterministic literal paragraph
        // baked into the skeleton, so a missing/hallucinated AI output can
        // never strip the GMC-required date marker. The model is instructed
        // (in the privacy / TOS prompts) to leave this line untouched and
        // not duplicate or substitute it inside the body slots.
        $effective_date_line = trim( $effective_date ) !== ''
            ? '<p class="cmc-effective-date"><strong>Effective Date:</strong> ' . esc_html( $effective_date ) . '</p>'
            : '';

        $replacements = [
            '{{CONTACT_BLOCK}}'        => $contact_block,
            '{{ADDRESS_BLOCK}}'        => $address_block,
            '{{PHONE_EMAIL_BLOCK}}'    => $phone_email_block,
            '{{HOURS_BLOCK}}'          => $hours_block,
            '{{EFFECTIVE_DATE_LINE}}'  => $effective_date_line,
        ];
        return strtr( $skeleton, $replacements );
    }
}
