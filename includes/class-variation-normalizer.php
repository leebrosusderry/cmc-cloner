<?php
/**
 * CMC Cloner — Variation Attribute Normalizer.
 *
 * Cleans up the messy attribute term names that Amazon imports leave
 * behind on cloned sites:
 *
 *   "01black"           → "Black"
 *   "02heathergrey"     → "Heather Grey"
 *   "06milk Tea"        → "Milk Tea"
 *   "1# Dark Green"     → "Dark Green"
 *   "Off-white"         → "Off-White"
 *   "Light Pink 1"      → "Light Pink"      (then merged with existing "Light Pink")
 *   "100% Cotton"       → "Cotton"          (material family)
 *   "xs"                → "XS"              (size family)
 *
 * The plugin only runs on review-only sites (cloned sites that exist
 * solely to pass GMC review, not to take real customer orders), so the
 * normalizer is designed to lean AGGRESSIVE — losing slightly more
 * information than is strictly necessary is preferable to leaving any
 * GMC-flaggable formatting in place.
 *
 * Architecture — two layers:
 *
 *   LAYER 1 (generic, applied to every attribute regardless of family):
 *     - Strip leading numeric / symbol prefix:  "01", "1#", "1.", "(3)", "[2]"
 *     - Strip trailing duplicate suffix:        "Light Pink 1" → "Light Pink"
 *     - Strip stray hash / asterisk symbols
 *     - Collapse whitespace
 *     - Smart Title Case
 *
 *   LAYER 2 (family-specific, applied on top of Layer 1):
 *     - color    → split compound lowercase ("heathergrey" → "Heather Grey")
 *     - size     → standardize abbreviations ("xs" → "XS")
 *     - material → strip "100%" qualifier ("100% Cotton" → "Cotton")
 *     - capacity → standardize unit spacing ("16fl oz" → "16 fl oz")
 *
 * Dedupe — after rename, if two terms in the same taxonomy end up with
 * the same name, the oldest (lowest term_id) is kept canonical and the
 * newer one is merged (relationships reassigned, term deleted).
 *
 * Revert — `_cmc_original_term_name` term meta is set BEFORE rename so
 * a future revert task can restore the originals exactly.
 *
 * Defensive guards — pure-numeric values ("2020", "8.5", "12x14") are
 * never touched; the regex only strips a numeric prefix when the
 * remaining string still contains letters.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Variation_Normalizer {

    /** Term meta key holding the original name before any rename. */
    private const META_BACKUP_KEY = '_cmc_original_term_name';

    /** Option key holding the audit log of the most recent run. */
    public const OPTION_LAST_RUN = 'cmc_variation_normalize_last_run';

    /**
     * Color words used by `normalize_color()` to split compound
     * lowercase names like "heathergrey" → "Heather Grey". Order
     * matters slightly — longer words first so "lightblue" matches
     * "blue" not "light" + "blue".
     */
    private const COLOR_WORDS = [
        // 2-syllable / compound candidates first (greedy matchers).
        'turquoise', 'magenta', 'indigo', 'maroon', 'lavender',
        'crimson', 'scarlet', 'fuchsia', 'salmon', 'coral',
        'apricot', 'peach', 'olive', 'khaki', 'taupe',
        // Plain color words.
        'grey', 'gray', 'blue', 'white', 'black', 'red', 'pink',
        'green', 'yellow', 'brown', 'orange', 'purple', 'beige',
        'tan', 'navy', 'gold', 'silver', 'cream', 'rose', 'mint',
        'tea', 'teal', 'ivory', 'burgundy', 'charcoal', 'plum',
    ];

    /** Standard size-abbreviation map (case-insensitive lookup). */
    private const SIZE_ABBREV = [
        'xs'    => 'XS',
        's'     => 'S',
        'm'     => 'M',
        'l'     => 'L',
        'xl'    => 'XL',
        'xxl'   => 'XXL',
        'xxxl'  => 'XXXL',
        '2xl'   => '2XL',
        '3xl'   => '3XL',
        '4xl'   => '4XL',
        '5xl'   => '5XL',
        'os'    => 'OS',  // One Size
    ];

    /** Words to leave lowercased inside Title Case (unless first word). */
    private const TITLE_CASE_SMALL = [
        'and', 'or', 'of', 'in', 'on', 'with', 'a', 'an', 'the', 'for', 'to', 'at', 'by',
    ];

    // ============================================================
    // Public API
    // ============================================================

    /**
     * Normalize a single term name based on the attribute family.
     * Pure function — no DB side effects. Useful for previews / tests.
     */
    public static function normalize( string $taxonomy, string $value ): string {
        $generic = self::normalize_generic( $value );
        $family  = self::detect_family( $taxonomy );
        switch ( $family ) {
            case 'color':    return self::normalize_color( $generic );
            case 'size':     return self::normalize_size( $generic );
            case 'material': return self::normalize_material( $generic );
            case 'capacity': return self::normalize_capacity( $generic );
            default:         return $generic;
        }
    }

    /**
     * Apply normalization to all WooCommerce product-attribute terms.
     *
     * Batched so a 5000-term catalogue doesn't trip the LSAPI timeout
     * on a single AJAX call. The caller (Run-All orchestrator + JS)
     * paginates until `done = true`.
     *
     * Returns the audit data for this batch + cumulative totals.
     *
     * @return array{
     *   processed:int, renamed:int, merged:int, skipped:int,
     *   next_offset:int, total:int, done:bool,
     *   samples:list<array{action:string,old:string,new:string,taxonomy:string}>
     * }
     */
    public static function apply( int $offset = 0, int $batch = 50 ): array {
        global $wpdb;

        $batch  = max( 1, min( 200, $batch ) );
        $offset = max( 0, $offset );

        // Allow a generous PHP runtime — wp_update_term + slug fix +
        // postmeta sync per term is fast individually but a 100-term
        // batch can land in the multi-second range.
        @set_time_limit( 120 );

        // All registered product attribute taxonomies (e.g. pa_color,
        // pa_size, pa_material). We deliberately operate only on
        // product-attribute taxonomies — not custom taxonomies that
        // happen to be attached to products via shared terms.
        $taxonomies = self::all_product_attribute_taxonomies();
        if ( empty( $taxonomies ) ) {
            self::set_last_run( [
                'at'      => time(),
                'note'    => 'No product-attribute taxonomies registered',
                'total'   => 0,
                'renamed' => 0,
                'merged'  => 0,
                'samples' => [],
            ] );
            return [
                'processed'    => 0, 'renamed' => 0, 'merged' => 0, 'skipped' => 0,
                'next_offset'  => 0, 'total' => 0, 'done' => true, 'samples' => [],
            ];
        }
        $tax_placeholders = implode( ',', array_fill( 0, count( $taxonomies ), '%s' ) );

        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->terms} t
               INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
              WHERE tt.taxonomy IN ($tax_placeholders)",
            ...$taxonomies
        ) );

        $params   = array_merge( $taxonomies, [ $batch, $offset ] );
        $terms    = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.term_id, t.name, t.slug, tt.term_taxonomy_id, tt.taxonomy
               FROM {$wpdb->terms} t
               INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
              WHERE tt.taxonomy IN ($tax_placeholders)
              ORDER BY tt.taxonomy ASC, t.term_id ASC
              LIMIT %d OFFSET %d",
            ...$params
        ) );

        $renamed_count = 0;
        $merged_count  = 0;
        $skipped       = 0;
        $samples       = [];

        foreach ( (array) $terms as $term ) {
            $old_name = (string) $term->name;
            $new_name = self::normalize( (string) $term->taxonomy, $old_name );

            // Nothing to do when normalization leaves the name unchanged
            // OR when normalization would yield an empty string.
            if ( $new_name === '' || $new_name === $old_name ) {
                $skipped++;
                continue;
            }

            // Does a different term in the same taxonomy already use
            // the new name? If yes, merge instead of rename.
            $existing = self::find_term_by_name( $new_name, (string) $term->taxonomy );
            if ( $existing && (int) $existing->term_id !== (int) $term->term_id ) {
                $result = self::merge_terms(
                    (int) $existing->term_id,
                    (int) $term->term_id,
                    (string) $term->taxonomy,
                    $old_name
                );
                if ( $result ) {
                    $merged_count++;
                    if ( count( $samples ) < 10 ) {
                        $samples[] = [
                            'action'   => 'merged',
                            'old'      => $old_name,
                            'new'      => $new_name,
                            'taxonomy' => (string) $term->taxonomy,
                        ];
                    }
                }
                continue;
            }

            // Simple rename. Save backup, then update the term — both
            // name AND slug — so the front-end URL also matches.
            self::backup_original_name( (int) $term->term_id, $old_name );
            $new_slug = sanitize_title( $new_name );
            $result   = wp_update_term( (int) $term->term_id, (string) $term->taxonomy, [
                'name' => $new_name,
                'slug' => $new_slug,
            ] );
            if ( is_wp_error( $result ) ) {
                $skipped++;
                continue;
            }
            // After the rename, WooCommerce variations still carry the
            // OLD slug copy in their `attribute_pa_<slug>` postmeta —
            // resync so storefront variation pickers don't break.
            self::sync_variation_postmeta_slug(
                (string) $term->taxonomy,
                (string) $term->slug,
                $new_slug
            );

            $renamed_count++;
            if ( count( $samples ) < 10 ) {
                $samples[] = [
                    'action'   => 'renamed',
                    'old'      => $old_name,
                    'new'      => $new_name,
                    'taxonomy' => (string) $term->taxonomy,
                ];
            }
        }

        $processed   = count( (array) $terms );
        $next_offset = $offset + $processed;
        $done        = ( $next_offset >= $total ) || $processed === 0;

        // On the last batch, persist a summary so the admin UI can
        // surface "what happened on the most recent normalize run".
        if ( $done ) {
            $previous = self::last_run_log();
            self::set_last_run( [
                'at'      => time(),
                'total'   => $total,
                'renamed' => ( (int) ( $previous['renamed'] ?? 0 ) ) + $renamed_count,
                'merged'  => ( (int) ( $previous['merged']  ?? 0 ) ) + $merged_count,
                'skipped' => ( (int) ( $previous['skipped'] ?? 0 ) ) + $skipped,
                'samples' => array_merge( (array) ( $previous['samples'] ?? [] ), $samples ),
            ] );
        } elseif ( $offset === 0 ) {
            // First batch of a fresh run — clear the previous summary
            // so the cumulative numbers start at 0.
            self::set_last_run( [
                'at'      => time(),
                'total'   => $total,
                'renamed' => $renamed_count,
                'merged'  => $merged_count,
                'skipped' => $skipped,
                'samples' => $samples,
            ] );
        } else {
            // Mid-run batch — append samples / counters.
            $previous = self::last_run_log();
            self::set_last_run( [
                'at'      => time(),
                'total'   => $total,
                'renamed' => ( (int) ( $previous['renamed'] ?? 0 ) ) + $renamed_count,
                'merged'  => ( (int) ( $previous['merged']  ?? 0 ) ) + $merged_count,
                'skipped' => ( (int) ( $previous['skipped'] ?? 0 ) ) + $skipped,
                'samples' => array_merge( (array) ( $previous['samples'] ?? [] ), $samples ),
            ] );
        }

        // After the FINAL batch only: scan every variable product for
        // duplicate variations created by the term merges. Without
        // this, a product that originally had a variation for
        // "01black + Size M" AND another for "Black + Size M" ends up
        // with two product_variation rows sharing the same final
        // attribute combo — the storefront then renders both as
        // separate variants (or duplicate cards on shop pages that
        // expand variations). Scoped to "final batch only" so we don't
        // pay the scan cost on every batch call.
        $dedupe = [ 'deleted' => 0, 'samples' => [] ];
        if ( $done ) {
            $dedupe = self::dedupe_duplicate_variations();
            $previous = self::last_run_log();
            $previous['dedupe'] = $dedupe;
            self::set_last_run( $previous );
        }

        return [
            'processed'        => $processed,
            'renamed'          => $renamed_count,
            'merged'           => $merged_count,
            'skipped'          => $skipped,
            'next_offset'      => $next_offset,
            'total'            => $total,
            'done'             => $done,
            'samples'          => $samples,
            'dedupe_deleted'   => $dedupe['deleted'],
        ];
    }

    /**
     * Find and delete duplicate variations created by term merges.
     *
     * After we collapse "01black" → "Black", every variation that
     * was on "01black" gets its `attribute_pa_color` postmeta
     * rewritten to "black". If the same product already had a
     * variation on the original "Black" term, both rows now share
     * the same attribute combination — WC + most themes will
     * display both, looking like product duplication.
     *
     * Strategy: group variations by their full attribute signature
     * within each parent. Keep the lowest-ID variation, hard-delete
     * the rest. Hard-delete (not trash) so the products screen and
     * the storefront agree on the count immediately.
     *
     * @return array{ deleted:int, samples:list<array<string,mixed>> }
     */
    public static function dedupe_duplicate_variations(): array {
        global $wpdb;

        @set_time_limit( 180 );

        // Parent IDs of every variable product that still has at least
        // one non-trashed variation. We only touch products that own
        // variations, so the scan is bounded to the variable catalogue
        // — simple shops without variations cost nothing.
        $parent_ids = (array) $wpdb->get_col(
            "SELECT DISTINCT post_parent FROM {$wpdb->posts}
              WHERE post_type   = 'product_variation'
                AND post_status NOT IN ('trash','auto-draft')"
        );

        $deleted = 0;
        $samples = [];
        $affected_parents = [];

        foreach ( $parent_ids as $parent_id ) {
            $parent_id = (int) $parent_id;
            if ( $parent_id <= 0 ) { continue; }

            $variation_ids = (array) $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                  WHERE post_parent = %d
                    AND post_type   = 'product_variation'
                    AND post_status NOT IN ('trash','auto-draft')
                  ORDER BY ID ASC",
                $parent_id
            ) );

            if ( count( $variation_ids ) < 2 ) { continue; }

            $seen = []; // signature => kept_variation_id
            foreach ( $variation_ids as $var_id ) {
                $var_id = (int) $var_id;
                $attrs  = (array) $wpdb->get_results( $wpdb->prepare(
                    "SELECT meta_key, meta_value FROM {$wpdb->postmeta}
                      WHERE post_id = %d
                        AND meta_key LIKE 'attribute\\_%%'",
                    $var_id
                ), ARRAY_A );

                $map = [];
                foreach ( $attrs as $row ) {
                    $map[ (string) $row['meta_key'] ] = (string) $row['meta_value'];
                }
                ksort( $map );
                $signature = md5( wp_json_encode( $map ) );

                if ( isset( $seen[ $signature ] ) ) {
                    // Duplicate — hard-delete (force=true) and remember
                    // we touched this parent so the product transients
                    // can be flushed below.
                    wp_delete_post( $var_id, true );
                    $deleted++;
                    $affected_parents[ $parent_id ] = true;
                    if ( count( $samples ) < 20 ) {
                        $samples[] = [
                            'parent_id'             => $parent_id,
                            'deleted_variation_id'  => $var_id,
                            'kept_variation_id'     => (int) $seen[ $signature ],
                            'signature'             => $map,
                        ];
                    }
                } else {
                    $seen[ $signature ] = $var_id;
                }
            }
        }

        // Flush WooCommerce product caches for every parent we touched.
        // WC stores variation lists / price ranges in transients —
        // without this flush, the admin product table and the shop
        // page may keep displaying the deleted variations for hours.
        if ( $affected_parents && function_exists( 'wc_delete_product_transients' ) ) {
            foreach ( array_keys( $affected_parents ) as $pid ) {
                wc_delete_product_transients( (int) $pid );
            }
        }

        return [
            'deleted' => $deleted,
            'samples' => $samples,
        ];
    }

    /**
     * Restore every term whose name was changed by a previous run.
     * Used for emergency rollback only — not exposed in the Run-All
     * UI, the operator calls this manually if normalize went wrong.
     *
     * @return array{ reverted:int, missing:int }
     */
    public static function revert(): array {
        global $wpdb;

        $rows = (array) $wpdb->get_results( $wpdb->prepare(
            "SELECT tm.term_id, tm.meta_value AS original_name, tt.taxonomy
               FROM {$wpdb->termmeta} tm
               INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id
              WHERE tm.meta_key = %s",
            self::META_BACKUP_KEY
        ) );

        $reverted = 0;
        $missing  = 0;
        foreach ( $rows as $row ) {
            $term_id  = (int) $row->term_id;
            $original = (string) $row->original_name;
            $taxonomy = (string) $row->taxonomy;
            if ( $original === '' ) {
                $missing++;
                continue;
            }
            $result = wp_update_term( $term_id, $taxonomy, [
                'name' => $original,
                'slug' => sanitize_title( $original ),
            ] );
            if ( ! is_wp_error( $result ) ) {
                delete_term_meta( $term_id, self::META_BACKUP_KEY );
                $reverted++;
            } else {
                $missing++;
            }
        }
        return [ 'reverted' => $reverted, 'missing' => $missing ];
    }

    /**
     * Return the audit log written by the last `apply()` run. Empty
     * array when no run has been recorded.
     */
    public static function last_run_log(): array {
        $log = get_option( self::OPTION_LAST_RUN, [] );
        return is_array( $log ) ? $log : [];
    }

    // ============================================================
    // Layer 1 — generic rules (apply to every attribute family)
    // ============================================================

    public static function normalize_generic( string $value ): string {
        $value = trim( $value );
        if ( $value === '' ) { return ''; }

        // Defensive guard: pure-numeric values like "2020", "8.5",
        // "12x14" — never touch these, they're often legitimate
        // measurements / model years.
        if ( ! preg_match( '/[A-Za-z]/u', $value ) ) {
            return $value;
        }

        // 1. Strip leading numeric / symbol prefix:
        //    "01black"   → "black"
        //    "1# Dark"   → "Dark"
        //    "(3) Red"   → "Red"
        //    "2. Green"  → "Green"
        //    Only when the remainder still contains letters (guards
        //    against destroying purely-numeric values caught by the
        //    earlier check, plus mixed-format edges).
        $candidate = preg_replace( '/^[\s\[\(]*\d+[\s\.\)\]\-#:]+\s*/u', '', $value );
        if ( is_string( $candidate ) && preg_match( '/[A-Za-z]/u', $candidate ) ) {
            $value = $candidate;
        }

        // 2. Strip trailing dupe-suffix:
        //    "Light Pink 1" → "Light Pink"
        //    "Black 2"      → "Black"
        $candidate = preg_replace( '/\s+\d+\s*$/u', '', $value );
        if ( is_string( $candidate ) && preg_match( '/[A-Za-z]/u', $candidate ) ) {
            $value = $candidate;
        }

        // 3. Strip stray symbols that carry no semantic meaning here.
        $value = str_replace( [ '#', '*', '~', '|' ], '', $value );

        // 4. Collapse interior whitespace.
        $value = (string) preg_replace( '/\s+/u', ' ', $value );

        // 5. Trim.
        $value = trim( $value );

        // 6. Title Case (with smart hyphen / apostrophe handling).
        return self::title_case_smart( $value );
    }

    // ============================================================
    // Layer 2 — family-specific rules
    // ============================================================

    public static function normalize_color( string $value ): string {
        if ( $value === '' ) { return ''; }
        // Split compound lowercase: "heathergrey" → "heather grey".
        // Insert a space BEFORE any color-word that is glued onto a
        // lowercase letter run. Word-boundary on the right so we don't
        // accidentally split inside compounds like "Black".
        foreach ( self::COLOR_WORDS as $word ) {
            $pattern = '/([a-z])(' . preg_quote( $word, '/' ) . ')(?=\s|$|[A-Z])/iu';
            $value   = (string) preg_replace( $pattern, '$1 $2', $value );
        }
        // Re-title-case after splitting (the split may have produced
        // "heather Grey" instead of "Heather Grey").
        return self::title_case_smart( $value );
    }

    public static function normalize_size( string $value ): string {
        if ( $value === '' ) { return ''; }
        $key = strtolower( trim( $value ) );
        if ( isset( self::SIZE_ABBREV[ $key ] ) ) {
            return self::SIZE_ABBREV[ $key ];
        }
        // Leave numeric sizes alone ("8.5", "32", "M/L" etc.). The
        // generic title-case already ran, that's good enough.
        return $value;
    }

    public static function normalize_material( string $value ): string {
        if ( $value === '' ) { return ''; }
        // Strip leading "100%" qualifier — common on Amazon listings
        // and adds no value for GMC review.
        $value = (string) preg_replace( '/^100%\s+/u', '', $value );
        return $value;
    }

    public static function normalize_capacity( string $value ): string {
        if ( $value === '' ) { return ''; }
        // Insert a space between digit and unit when missing:
        //   "16fl oz" → "16 fl oz"
        //   "8oz"     → "8 oz"
        //   "237ml"   → "237 ml"
        $value = (string) preg_replace( '/(\d)([a-zA-Z]{1,4}\b)/u', '$1 $2', $value );
        return self::title_case_smart( $value );
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * Family detection — keyword match on the taxonomy slug. New
     * families are added here as the keyword list grows.
     */
    public static function detect_family( string $taxonomy ): string {
        $slug = strtolower( $taxonomy );
        if ( preg_match( '/colou?r/u', $slug ) )                  return 'color';
        if ( preg_match( '/size|fit|cut/u', $slug ) )             return 'size';
        if ( preg_match( '/material|fabric|finish/u', $slug ) )   return 'material';
        if ( preg_match( '/capacity|volume|weight|ounce/u', $slug ) ) return 'capacity';
        return 'generic';
    }

    /**
     * Title-case the value with "small words" (and / or / of / etc.)
     * left lowercased after the first word. Also capitalises after a
     * hyphen so "off-white" becomes "Off-White".
     */
    private static function title_case_smart( string $value ): string {
        if ( $value === '' ) { return ''; }
        $lower = mb_strtolower( $value, 'UTF-8' );

        // Walk word-by-word. Split on whitespace + hyphen, keep
        // delimiters so we can reassemble exactly.
        $parts = preg_split( '/(\s+|-)/u', $lower, -1, PREG_SPLIT_DELIM_CAPTURE );
        if ( ! is_array( $parts ) ) {
            return $value;
        }
        $output     = '';
        $word_index = 0;
        foreach ( $parts as $token ) {
            if ( $token === '' )            { continue; }
            if ( preg_match( '/^\s+$/u', $token ) || $token === '-' ) {
                $output .= $token;
                continue;
            }
            // Words after a hyphen still get capitalised regardless of
            // the "small word" rule — "Off-White" not "Off-white".
            $is_first_or_after_hyphen = ( $word_index === 0 )
                || ( substr( $output, -1 ) === '-' );
            if ( ! $is_first_or_after_hyphen && in_array( $token, self::TITLE_CASE_SMALL, true ) ) {
                $output .= $token;
            } else {
                $output .= mb_strtoupper( mb_substr( $token, 0, 1, 'UTF-8' ), 'UTF-8' )
                    . mb_substr( $token, 1, null, 'UTF-8' );
            }
            $word_index++;
        }
        return $output;
    }

    /**
     * Return every registered product-attribute taxonomy (`pa_*`).
     * Wraps WooCommerce's helper when present, falls back to a direct
     * DB scan so the normalizer works even on a Woo-disabled site
     * (rare, but the cloner sometimes runs on staging without Woo).
     */
    private static function all_product_attribute_taxonomies(): array {
        if ( function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
            $names = (array) wc_get_attribute_taxonomy_names();
            return array_values( array_unique( array_filter( $names ) ) );
        }
        global $wpdb;
        $rows = $wpdb->get_col(
            "SELECT DISTINCT taxonomy FROM {$wpdb->term_taxonomy} WHERE taxonomy LIKE 'pa\\_%'"
        );
        return array_values( array_filter( (array) $rows ) );
    }

    /**
     * Look up an existing term by name within a taxonomy. Used to
     * detect rename-vs-merge cases.
     */
    private static function find_term_by_name( string $name, string $taxonomy ): ?\WP_Term {
        $term = get_term_by( 'name', $name, $taxonomy );
        return ( $term instanceof \WP_Term ) ? $term : null;
    }

    /**
     * Persist the original term name so revert() can restore it. Only
     * sets the backup the FIRST time — subsequent rewrites preserve
     * the very-first original.
     */
    private static function backup_original_name( int $term_id, string $original_name ): void {
        $existing = get_term_meta( $term_id, self::META_BACKUP_KEY, true );
        if ( $existing === '' ) {
            add_term_meta( $term_id, self::META_BACKUP_KEY, $original_name, true );
        }
    }

    /**
     * Merge `$dup_term_id` into `$canonical_term_id` within the same
     * taxonomy. Reassigns every variation that referenced the dup
     * to the canonical term, then deletes the dup. Returns true on
     * success.
     */
    private static function merge_terms( int $canonical_term_id, int $dup_term_id, string $taxonomy, string $dup_old_name ): bool {
        global $wpdb;

        $canonical_term = get_term( $canonical_term_id, $taxonomy );
        $dup_term       = get_term( $dup_term_id, $taxonomy );
        if ( ! ( $canonical_term instanceof \WP_Term ) || ! ( $dup_term instanceof \WP_Term ) ) {
            return false;
        }

        // Record the canonical-side backup so a future revert knows
        // BOTH the canonical-original and the merged-original names.
        // We append, not overwrite — a single term can collect
        // multiple originals over multiple runs.
        add_term_meta( $canonical_term_id, self::META_BACKUP_KEY, $dup_old_name, false );

        // Reassign every relationship from dup → canonical. WP+WC
        // handle the rest (variations resolve through term_taxonomy_id).
        $wpdb->update(
            $wpdb->term_relationships,
            [ 'term_taxonomy_id' => $canonical_term->term_taxonomy_id ],
            [ 'term_taxonomy_id' => $dup_term->term_taxonomy_id ]
        );

        // Sync the variation postmeta slug copy — WooCommerce stores
        // the slug as a string inside `attribute_pa_<taxonomy>` meta
        // and won't auto-update when we move relationships.
        self::sync_variation_postmeta_slug( $taxonomy, $dup_term->slug, $canonical_term->slug );

        // Delete the dup term (and its term_taxonomy row).
        wp_delete_term( $dup_term_id, $taxonomy );

        return true;
    }

    /**
     * WooCommerce variations cache the attribute slug as a string in
     * `attribute_<taxonomy>` postmeta — independent of the
     * term_relationships row. When we rename or merge, that string
     * also has to move so the variation picker doesn't blank out.
     */
    private static function sync_variation_postmeta_slug( string $taxonomy, string $old_slug, string $new_slug ): void {
        if ( $old_slug === '' || $new_slug === '' || $old_slug === $new_slug ) {
            return;
        }
        global $wpdb;
        $meta_key = 'attribute_' . $taxonomy;
        $wpdb->update(
            $wpdb->postmeta,
            [ 'meta_value' => $new_slug ],
            [ 'meta_key' => $meta_key, 'meta_value' => $old_slug ]
        );
    }

    /**
     * Persist the audit log for the most recent run. Single entry —
     * we don't keep history beyond the latest run (one option row
     * keeps the options table lean and the admin UI simple).
     */
    private static function set_last_run( array $log ): void {
        // Cap the samples array at a reasonable size (the audit UI
        // only ever shows the first 50).
        if ( isset( $log['samples'] ) && is_array( $log['samples'] ) && count( $log['samples'] ) > 50 ) {
            $log['samples'] = array_slice( $log['samples'], 0, 50 );
        }
        update_option( self::OPTION_LAST_RUN, $log, false );
    }
}
