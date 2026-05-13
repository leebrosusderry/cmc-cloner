<?php
/**
 * SKU Normalizer — replaces Amazon ASIN-shaped SKUs (and other obvious
 * externally-sourced SKUs) with a short internal SKU derived from the
 * current Settings → Industry, so GMC reviewers do not see feed SKUs
 * that match the original marketplace listing.
 *
 * Input patterns we rewrite:
 *   - B0XXXXXXXX                  (Amazon ASIN)
 *   - B0XXXXXXXX_parent           (Amazon parent-variant)
 *   - B0XXXXXXXX_VAR1             (Amazon variation suffix)
 *   - Any 10-char UPPERCASE alnum starting with B0 (ASIN variants)
 *
 * Output shape:
 *   {PFX}-{00001}              for simple/parent products
 *   {PFX}-{00001}-V{00001}     for variations of a parent we just renamed
 *   {PFX}-{00001}-V{00001}     for orphan variations (rare)
 *
 *   where {PFX} is the first 3 letters of Settings → Industry slug
 *   (uppercase A-Z only, fallback "PRD") and counters are zero-padded 5.
 *
 * State:
 *   - Original SKU per product is stored in post meta `_cmc_original_sku`
 *     on write so Revert can restore it. Idempotent: if meta already
 *     exists we don't overwrite it (so repeated Apply calls never lose
 *     the true original).
 *   - Next counter is stored in option `cmc_sku_normalizer_counter`
 *     keyed by prefix: [ 'HBG' => 42, 'FSH' => 7 ]. Monotonic.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Sku_Normalizer {

    public const META_ORIGINAL   = '_cmc_original_sku';
    public const OPTION_COUNTERS = 'cmc_sku_normalizer_counter';

    /**
     * Regex for SKUs we consider "foreign" and should rewrite.
     * Case-sensitive — marketplace SKUs are always upper-case.
     *
     * ASIN_RE catches the canonical Amazon ASIN ("B0XXXXXXXX") with an
     * optional `_parent` / `_VAR1` suffix.
     *
     * FOREIGN_RE catches the broader bucket of "looks marketplace-y"
     * SKUs that the strict ASIN regex misses — random uppercase
     * alphanumeric codes ≥8 chars (e.g. `ZK00XX12`, `JCHX-A123`,
     * `0700XYZ45_VAR2`). Real-world Woo POD imports use many ID
     * shapes per supplier; the broad gate ensures we still rewrite
     * them even when they don't start with `B0`.
     *
     * INTERNAL_RE is the format `apply_batch()` produces — must NEVER
     * match a SKU we've already rewritten (`HBG-00001`,
     * `HBG-00001-V00001`), otherwise re-running Apply would burn
     * counters on already-clean rows.
     */
    private const ASIN_RE     = '/^(B0[A-Z0-9]{8})(?:[_\-].+)?$/';
    private const FOREIGN_RE  = '/^[A-Z0-9]{8,}(?:[_\-][A-Z0-9_]+)*$/';
    private const INTERNAL_RE = '/^[A-Z]{2,5}-\d{4,}(?:-V\d{4,})?$/';

    // ---------- Scan ----------

    /**
     * Read-only counts used to prime the UI.
     *
     * @return array{
     *   products:int, variations:int,
     *   eligible_products:int, eligible_variations:int,
     *   sample: list<array{id:int, type:string, old:string, parent_id:int}>
     * }
     */
    public static function scan(): array {
        global $wpdb;

        $products   = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'"
        );
        $variations = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product_variation'"
        );

        $rows = $wpdb->get_results(
            "SELECT p.ID, p.post_type, p.post_parent, pm.meta_value AS sku
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_sku'
             WHERE p.post_type IN ('product','product_variation')
               AND pm.meta_value <> ''
             ORDER BY p.post_type ASC, p.ID ASC"
        );

        $eligible_products   = 0;
        $eligible_variations = 0;
        $sample              = [];

        foreach ( (array) $rows as $r ) {
            $sku = (string) $r->sku;
            if ( ! self::is_foreign_sku( $sku ) ) {
                continue;
            }
            if ( $r->post_type === 'product' ) {
                $eligible_products++;
            } else {
                $eligible_variations++;
            }
            if ( count( $sample ) < 20 ) {
                $sample[] = [
                    'id'        => (int) $r->ID,
                    'type'      => (string) $r->post_type,
                    'old'       => $sku,
                    'parent_id' => (int) $r->post_parent,
                ];
            }
        }

        return [
            'products'            => $products,
            'variations'          => $variations,
            'eligible_products'   => $eligible_products,
            'eligible_variations' => $eligible_variations,
            'sample'              => $sample,
            'prefix'              => self::prefix_from_industry(),
        ];
    }

    // ---------- Apply ----------

    /**
     * Rewrite the next batch of foreign SKUs. Variations always follow
     * their parent: we rewrite the parent first (so the parent's new
     * root SKU is available) and then walk its variations.
     *
     * @return array{
     *   processed:int, remaining:int, done:bool,
     *   items: list<array{id:int, type:string, old:string, new:string, note?:string}>,
     *   errors: list<string>
     * }
     */
    public static function apply_batch( int $limit = 25 ): array {
        $limit = max( 1, min( 100, $limit ) );

        $parents = self::next_eligible_parents( $limit );

        $items  = [];
        $errors = [];

        foreach ( $parents as $pid ) {
            $old = self::read_sku( $pid );
            if ( $old === '' || ! self::is_foreign_sku( $old ) ) {
                continue;
            }

            $new = self::reserve_next_sku();
            $res = self::write_sku( $pid, $new, $old );
            if ( is_wp_error( $res ) ) {
                $errors[] = sprintf( '#%d: %s', $pid, $res->get_error_message() );
                continue;
            }
            $items[] = [ 'id' => $pid, 'type' => 'product', 'old' => $old, 'new' => $new ];

            // Variations of this product — rewrite in lockstep.
            $vids = self::variation_ids_of( $pid );
            $vn   = 0;
            foreach ( $vids as $vid ) {
                $vold = self::read_sku( $vid );
                if ( $vold === '' ) {
                    continue;
                }
                $vn++;
                $vnew = $new . '-V' . str_pad( (string) $vn, 5, '0', STR_PAD_LEFT );
                $vres = self::write_sku( $vid, $vnew, $vold );
                if ( is_wp_error( $vres ) ) {
                    $errors[] = sprintf( '#%d (var of #%d): %s', $vid, $pid, $vres->get_error_message() );
                    continue;
                }
                $items[] = [
                    'id' => $vid, 'type' => 'variation', 'old' => $vold, 'new' => $vnew,
                    'note' => 'parent #' . $pid,
                ];
            }
        }

        // Orphan variations (no parent or parent is not eligible) — process
        // any foreign-SKU variations left over so the feed is uniform.
        $orphans = self::next_eligible_orphan_variations( $limit );
        foreach ( $orphans as $vid ) {
            $vold = self::read_sku( $vid );
            if ( $vold === '' || ! self::is_foreign_sku( $vold ) ) {
                continue;
            }
            $vnew = self::reserve_next_sku() . '-V00001';
            $vres = self::write_sku( $vid, $vnew, $vold );
            if ( is_wp_error( $vres ) ) {
                $errors[] = sprintf( '#%d (orphan var): %s', $vid, $vres->get_error_message() );
                continue;
            }
            $items[] = [ 'id' => $vid, 'type' => 'variation', 'old' => $vold, 'new' => $vnew, 'note' => 'orphan' ];
        }

        $scan       = self::scan();
        $remaining  = $scan['eligible_products'] + $scan['eligible_variations'];

        return [
            'processed' => count( $items ),
            'remaining' => $remaining,
            'done'      => $remaining === 0,
            'items'     => $items,
            'errors'    => $errors,
        ];
    }

    // ---------- Revert ----------

    /**
     * Restore original SKUs from postmeta for the next batch of posts
     * that have `_cmc_original_sku` stored. Safe to run multiple times.
     *
     * @return array{
     *   processed:int, remaining:int, done:bool,
     *   items: list<array{id:int, old:string, new:string}>,
     *   errors: list<string>
     * }
     */
    public static function revert_batch( int $limit = 50 ): array {
        $limit = max( 1, min( 200, $limit ) );

        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
             WHERE p.post_type IN ('product','product_variation')
             ORDER BY p.ID ASC
             LIMIT %d",
            self::META_ORIGINAL,
            $limit
        ) );

        $items  = [];
        $errors = [];

        foreach ( (array) $ids as $pid ) {
            $pid      = (int) $pid;
            $original = (string) get_post_meta( $pid, self::META_ORIGINAL, true );
            if ( $original === '' ) {
                delete_post_meta( $pid, self::META_ORIGINAL );
                continue;
            }
            $current = self::read_sku( $pid );
            update_post_meta( $pid, '_sku', $original );
            delete_post_meta( $pid, self::META_ORIGINAL );
            if ( function_exists( 'wc_delete_product_transients' ) ) {
                wc_delete_product_transients( $pid );
            }
            $items[] = [ 'id' => $pid, 'old' => $current, 'new' => $original ];
        }

        $remaining = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
            self::META_ORIGINAL
        ) );

        return [
            'processed' => count( $items ),
            'remaining' => $remaining,
            'done'      => $remaining === 0,
            'items'     => $items,
            'errors'    => $errors,
        ];
    }

    // ---------- Internals ----------

    /**
     * True when the SKU should be rewritten to our internal format.
     * Three gates, in order:
     *   1. ASIN-shaped SKU → always yes.
     *   2. Internal-format SKU (`PFX-NNNNN` / `PFX-NNNNN-VNNNNN`) →
     *      already normalised, skip.
     *   3. Generic "foreign" pattern (uppercase alphanumeric ≥8 chars
     *      with optional `_`/`-` separators) → yes, this is a
     *      marketplace-style ID we want to replace.
     *
     * The `cmc_sku_normalizer_is_foreign` filter lets site-specific
     * code override the decision for edge cases — useful when a
     * supplier uses an SKU shape that slips past the generic regex.
     */
    public static function is_foreign_sku( string $sku ): bool {
        $sku = trim( $sku );
        if ( $sku === '' ) {
            return (bool) apply_filters( 'cmc_sku_normalizer_is_foreign', false, $sku );
        }
        if ( preg_match( self::ASIN_RE, $sku ) ) {
            return (bool) apply_filters( 'cmc_sku_normalizer_is_foreign', true, $sku );
        }
        // Skip already-normalised internal SKUs.
        if ( preg_match( self::INTERNAL_RE, $sku ) ) {
            return (bool) apply_filters( 'cmc_sku_normalizer_is_foreign', false, $sku );
        }
        // Generic foreign-looking pattern.
        if ( preg_match( self::FOREIGN_RE, $sku ) ) {
            return (bool) apply_filters( 'cmc_sku_normalizer_is_foreign', true, $sku );
        }
        return (bool) apply_filters( 'cmc_sku_normalizer_is_foreign', false, $sku );
    }

    private static function read_sku( int $post_id ): string {
        return (string) get_post_meta( $post_id, '_sku', true );
    }

    /**
     * Write $new_sku onto the product and preserve the pre-existing SKU
     * in `_cmc_original_sku` — but only the first time, so repeated
     * Apply calls never overwrite the true original. Also clears Woo's
     * product transients. Enforces SKU uniqueness across the site.
     */
    private static function write_sku( int $post_id, string $new_sku, string $old_sku ) {
        if ( $new_sku === '' ) {
            return new WP_Error( 'cmc_sku_empty', 'Refusing to write empty SKU.' );
        }

        // Uniqueness check: if another product already has this SKU, bail.
        global $wpdb;
        $conflict = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_sku' AND meta_value = %s AND post_id <> %d
             LIMIT 1",
            $new_sku,
            $post_id
        ) );
        if ( $conflict > 0 ) {
            return new WP_Error( 'cmc_sku_conflict', sprintf( 'SKU "%s" already used by #%d.', $new_sku, $conflict ) );
        }

        $existing_original = (string) get_post_meta( $post_id, self::META_ORIGINAL, true );
        if ( $existing_original === '' && $old_sku !== '' ) {
            update_post_meta( $post_id, self::META_ORIGINAL, $old_sku );
        }
        update_post_meta( $post_id, '_sku', $new_sku );

        if ( function_exists( 'wc_delete_product_transients' ) ) {
            wc_delete_product_transients( $post_id );
        }
        return true;
    }

    /**
     * @return list<int>
     */
    private static function next_eligible_parents( int $limit ): array {
        global $wpdb;
        // Pull more rows than needed; filter by regex in PHP to avoid
        // vendor-specific REGEXP support requirements on MySQL/MariaDB.
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID, pm.meta_value AS sku
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_sku'
             WHERE p.post_type = 'product' AND pm.meta_value <> ''
             ORDER BY p.ID ASC
             LIMIT %d",
            max( $limit * 4, 100 )
        ) );

        $out = [];
        foreach ( (array) $rows as $r ) {
            if ( self::is_foreign_sku( (string) $r->sku ) ) {
                $out[] = (int) $r->ID;
                if ( count( $out ) >= $limit ) {
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * Variations whose parent is NOT in the eligible-parent pool but the
     * variation itself has a foreign SKU. In practice rare.
     *
     * @return list<int>
     */
    private static function next_eligible_orphan_variations( int $limit ): array {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT v.ID, vm.meta_value AS sku, p.ID AS parent_id, ppm.meta_value AS parent_sku
             FROM {$wpdb->posts} v
             INNER JOIN {$wpdb->postmeta} vm ON vm.post_id = v.ID AND vm.meta_key = '_sku'
             LEFT  JOIN {$wpdb->posts} p ON p.ID = v.post_parent AND p.post_type = 'product'
             LEFT  JOIN {$wpdb->postmeta} ppm ON ppm.post_id = p.ID AND ppm.meta_key = '_sku'
             WHERE v.post_type = 'product_variation' AND vm.meta_value <> ''
             ORDER BY v.ID ASC
             LIMIT %d",
            max( $limit * 4, 100 )
        ) );

        $out = [];
        foreach ( (array) $rows as $r ) {
            $self_foreign   = self::is_foreign_sku( (string) $r->sku );
            $parent_foreign = self::is_foreign_sku( (string) ( $r->parent_sku ?? '' ) );
            // If the parent is also foreign it will be handled in the
            // parent-first pass; only pick orphans here.
            if ( $self_foreign && ! $parent_foreign ) {
                $out[] = (int) $r->ID;
                if ( count( $out ) >= $limit ) {
                    break;
                }
            }
        }
        return $out;
    }

    /**
     * @return list<int>
     */
    private static function variation_ids_of( int $parent_id ): array {
        global $wpdb;
        $rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'product_variation' AND post_parent = %d
             ORDER BY ID ASC",
            $parent_id
        ) );
        return array_map( 'intval', (array) $rows );
    }

    private static function prefix_from_industry(): string {
        $s        = CMC_Settings::get();
        $company  = (array) ( $s['company_info'] ?? [] );
        $slug     = (string) ( $company['nganh_hang'] ?? '' );
        $letters  = preg_replace( '/[^A-Za-z]/', '', $slug );
        $letters  = strtoupper( (string) $letters );
        if ( strlen( $letters ) < 3 ) {
            return 'PRD';
        }
        return substr( $letters, 0, 3 );
    }

    /**
     * Atomically reserve and return the next `{PFX}-{00001}` SKU.
     */
    private static function reserve_next_sku(): string {
        $prefix   = self::prefix_from_industry();
        $counters = (array) get_option( self::OPTION_COUNTERS, [] );
        $current  = (int) ( $counters[ $prefix ] ?? 0 );
        $next     = $current + 1;
        $counters[ $prefix ] = $next;
        update_option( self::OPTION_COUNTERS, $counters, false );
        return $prefix . '-' . str_pad( (string) $next, 5, '0', STR_PAD_LEFT );
    }
}
