<?php
/**
 * Category Builder — splits the single niche product_cat term left by the
 * "Rename Category Name" step into 5–8 GMC-friendly sub-categories,
 * then distributes existing products into those sub-categories via
 * title-keyword scoring.
 *
 * Industry-agnostic. The sub-category list + keyword bank is produced
 * by a single AI call (one per Run-All pass; cached to a site option
 * so re-runs don't re-call AI) and validated against a strict shape
 * before it touches the database.
 *
 * Distribution algorithm (per product):
 *   1. Normalise title to lowercase, padded with spaces.
 *   2. For each sub-cat, score include keywords (word-boundary match,
 *      score = sum of keyword lengths — longer matches beat shorter).
 *      Any exclude-keyword hit disqualifies that sub-cat for this
 *      product entirely (e.g. "men" excludes "Women's Tops").
 *   3. Pick every sub-cat whose score is ≥ 50% of the top score, with
 *      a hard floor (max(3, max*0.5)). Multi-cat assignment by design —
 *      WC supports it and Reviewers expect a product like "Men's
 *      Cashmere Hoodie" to live under both "Men's Shirts" and
 *      "Hoodies & Pullovers".
 *   4. Unmatched products stay under the parent industry term.
 *
 * Revert-safe: every product whose term assignment changes gets its
 * prior term_id list saved to postmeta `_cmc_orig_cats`, and every
 * sub-cat term the plugin creates is tagged with termmeta
 * `_cmc_auto_created_subcat = 1`. The revert() entry-point restores
 * the meta-stored cats and deletes only the tagged terms — never the
 * parent niche term, never user-created terms.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Category_Builder {

    /** Per-product backup of pre-distribution term_id list. */
    public const META_ORIG_CATS = '_cmc_orig_cats';

    /** Termmeta flag on plugin-created sub-category terms. */
    public const META_AUTO_CREATED = '_cmc_auto_created_subcat';

    /** Cached plan from the AI (option). Re-used on subsequent re-runs. */
    public const OPT_PLAN = 'cmc_subcats_plan';

    /** Match threshold: pick sub-cats whose score is ≥ this fraction of max. */
    private const SCORE_TIE_TOLERANCE = 0.5;

    /** Hard floor below which a match is considered weak. */
    private const SCORE_FLOOR = 3;

    /**
     * Captures the most recent low-level error message so failures get
     * a useful explanation in the Run-All log line instead of a
     * generic "Failed." (which renders as "network error" in jQuery's
     * fail callback after our HTTP-500 envelope).
     */
    private static string $last_error = '';

    /**
     * Run the full build pipeline. Returns a summary the AJAX handler
     * surfaces in the Run-All timeline.
     *
     * @return array{success:bool, created:int, distributed:int, unmatched:int, parent_term_id:int, plan_size:int, per_subcat:array, message:string}
     */
    public static function run(): array {
        // 1. Resolve industry label.
        $settings  = CMC_Settings::get();
        $ind_slug  = (string) ( $settings['company_info']['nganh_hang'] ?? '' );
        $ind_opts  = CMC_Shortcodes::nganh_hang_options();
        $ind_label = (string) ( $ind_opts[ $ind_slug ] ?? '' );
        if ( $ind_label === '' ) {
            return self::fail( 'Industry not configured — skipping sub-categorisation.' );
        }

        // 2. Locate the parent term (the post-Rename niche cat).
        $parent_id = self::find_industry_term( $ind_label );
        if ( $parent_id <= 0 ) {
            return self::fail( 'No product category found — run "Rename Category Name" first.' );
        }

        // 3. Get / generate plan.
        self::$last_error = '';
        $plan             = self::load_or_generate_plan( $ind_label );
        if ( ! is_array( $plan ) || empty( $plan['subcategories'] ) ) {
            $detail = self::$last_error !== '' ? ' — ' . self::$last_error : '';
            return self::fail( 'AI did not return a valid sub-category plan' . $detail );
        }

        // 4. Create / look up sub-cat terms underneath the parent.
        $term_id_map = self::create_subcat_terms( $plan, $parent_id );
        if ( empty( $term_id_map ) ) {
            return self::fail( 'Could not create any sub-category terms.' );
        }

        // 5. Distribute products.
        $stats = self::distribute_products( $parent_id, $plan, $term_id_map );

        // 6. Cleanup empty sub-cats AI suggested but no products matched.
        $removed = self::prune_empty_subcats( $term_id_map, $stats['per_subcat'] );

        // 7. Flush WC caches so category counts update.
        if ( function_exists( 'wc_delete_product_transients' ) ) {
            wc_delete_product_transients();
        }
        wp_cache_flush();

        return [
            'success'         => true,
            'created'         => count( $term_id_map ) - $removed,
            'distributed'     => (int) $stats['matched'],
            'unmatched'       => (int) $stats['unmatched'],
            'parent_term_id'  => $parent_id,
            'plan_size'       => count( $plan['subcategories'] ),
            'per_subcat'      => $stats['per_subcat'],
            'pruned_empty'    => $removed,
            'message'         => sprintf(
                '%d sub-cats created (%d pruned), %d products distributed (%d unmatched).',
                count( $term_id_map ) - $removed,
                $removed,
                (int) $stats['matched'],
                (int) $stats['unmatched']
            ),
        ];
    }

    /**
     * Revert: walk every product carrying our backup meta, restore its
     * prior term assignment, then delete every term tagged as
     * plugin-created. Idempotent.
     */
    public static function revert(): array {
        $product_ids = get_posts( [
            'post_type'      => 'product',
            'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
            'numberposts'    => -1,
            'fields'         => 'ids',
            'meta_query'     => [ [ 'key' => self::META_ORIG_CATS, 'compare' => 'EXISTS' ] ],
            'suppress_filters' => true,
        ] );
        $restored = 0;
        foreach ( $product_ids as $pid ) {
            $orig = get_post_meta( (int) $pid, self::META_ORIG_CATS, true );
            if ( is_array( $orig ) && ! empty( $orig ) ) {
                $term_ids = array_values( array_unique( array_map( 'intval', $orig ) ) );
                wp_set_object_terms( (int) $pid, $term_ids, 'product_cat', false );
                $restored++;
            }
            delete_post_meta( (int) $pid, self::META_ORIG_CATS );
        }

        // Delete plugin-created sub-cat terms. Must enumerate via
        // termmeta because the WP_Term_Query meta_query path is
        // sometimes silently disabled by caching plugins.
        global $wpdb;
        $term_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT tm.term_id FROM {$wpdb->termmeta} tm WHERE tm.meta_key = %s AND tm.meta_value = '1'",
            self::META_AUTO_CREATED
        ) );
        $deleted = 0;
        foreach ( (array) $term_ids as $tid ) {
            $tid = (int) $tid;
            if ( $tid <= 0 ) { continue; }
            $r = wp_delete_term( $tid, 'product_cat' );
            if ( $r === true ) {
                $deleted++;
            }
        }

        delete_option( self::OPT_PLAN );
        if ( function_exists( 'wc_delete_product_transients' ) ) {
            wc_delete_product_transients();
        }

        return [
            'success'  => true,
            'restored' => $restored,
            'deleted'  => $deleted,
            'message'  => sprintf( 'Restored %d products, deleted %d auto sub-cats.', $restored, $deleted ),
        ];
    }

    // ---------- internal pipeline ----------

    /**
     * Find the active niche term — the parent under which sub-cats land.
     *
     * Priority:
     *   1. A non-default term whose name exactly matches the industry
     *      label (the "Rename Category Name" step renames the niche cat
     *      to {{nganh_hang}}, so this is the canonical state).
     *   2. The non-default term with the most products (fallback when
     *      rename hasn't run or the user renamed it back).
     */
    private static function find_industry_term( string $industry_label ): int {
        $terms = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'number'     => 100,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ] );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return 0;
        }

        $default_term_id = (int) get_option( 'default_product_cat', 0 );
        $by_name = null;
        $by_count = null;
        foreach ( $terms as $t ) {
            $tid = (int) $t->term_id;
            if ( $tid === $default_term_id || $t->slug === 'uncategorized' ) {
                continue;
            }
            // Skip if this is itself a sub-cat the plugin created on a
            // previous run — we want the parent, not a child.
            if ( (int) get_term_meta( $tid, self::META_AUTO_CREATED, true ) === 1 ) {
                continue;
            }
            if ( $by_name === null && strcasecmp( (string) $t->name, $industry_label ) === 0 ) {
                $by_name = $tid;
            }
            if ( $by_count === null ) {
                $by_count = $tid;
            }
        }
        return (int) ( $by_name ?? $by_count ?? 0 );
    }

    /**
     * Look up cached AI plan, or build one via a single AI call.
     */
    private static function load_or_generate_plan( string $industry_label ): ?array {
        $cached = get_option( self::OPT_PLAN );
        if ( is_array( $cached )
            && ! empty( $cached['industry'] )
            && strcasecmp( (string) $cached['industry'], $industry_label ) === 0
            && ! empty( $cached['subcategories'] ) ) {
            return $cached;
        }

        $prompt = self::build_prompt( $industry_label );
        try {
            // CMC_AI_Client::generate takes ONE arg; provider params come
            // from settings (temperature, max_tokens). Don't pass a
            // second argument — it'll trigger a TypeError that the AJAX
            // wrapper rethrows as HTTP 500 (jQuery shows "network error").
            $raw = (string) CMC_AI_Client::generate( $prompt );
        } catch ( \Throwable $e ) {
            self::$last_error = 'AI call failed: ' . $e->getMessage();
            return null;
        }

        $plan = self::parse_ai_plan( $raw );
        if ( $plan === null ) {
            self::$last_error = 'AI returned invalid JSON (first 200 chars: ' . substr( trim( $raw ), 0, 200 ) . ')';
            return null;
        }
        $plan['industry'] = $industry_label;
        update_option( self::OPT_PLAN, $plan, false );
        return $plan;
    }

    /**
     * Prompt the AI to propose sub-categories + keyword banks for the
     * given industry. Output must be strict JSON — markdown fences are
     * stripped during parse.
     */
    private static function build_prompt( string $industry_label ): string {
        return <<<PROMPT
ROLE: You are a Google Merchant Center (GMC) taxonomy expert.

INPUT
Industry: {$industry_label}

TASK
Propose 5 to 8 sub-categories suitable as WooCommerce product_cat terms underneath the industry above. Each sub-category must be a PRODUCT TYPE noun (what is sold). For each sub-category also produce a keyword bank used to assign existing products via simple title-string matching.

OUTPUT RULES
1. Sub-category names: 2 to 4 English words, Title Case, no emoji, no trailing punctuation.
2. Slugs: kebab-case, ASCII lowercase + hyphens only.
3. include_keywords: 6 to 15 lowercase tokens (single words OR 2-word phrases) — these are substrings that a product TITLE belonging to this sub-category may contain. Pick distinctive product-type words ("blouse", "hoodie", "running shoes"), NOT generic adjectives ("premium", "classic", "stylish").
4. exclude_keywords: 0 to 8 lowercase tokens that DISQUALIFY a product from this sub-category. Useful when two sub-cats overlap on a noun but differ on an audience prefix (e.g. include "tops" for Women's Tops, exclude "men", "mens", "boys", "kids").
5. Industry-agnostic: this prompt runs for any niche (fashion, pet supplies, electronics, home, kids, etc.). Adjust your sub-cats to the input industry, do NOT default to fashion.

BANNED sub-category styles (do NOT use these):
- Marketing labels: "Best Sellers", "New Arrivals", "Featured", "Sale", "Trending"
- Quality tiers: "Premium Collection", "Top Picks", "Exclusive"
- Audience-only without product noun: "For Her", "For Him" — use "Women's Tops", "Men's Shirts" instead
- Price-range / season / material-alone names: "Under \$50", "Winter", "Cotton"
- Generic catch-alls: "Other", "Misc", "Accessories" (alone — only OK with a qualifier like "Phone Accessories")

OUTPUT FORMAT — return VALID JSON ONLY, no markdown fences, no commentary, no preface, no trailing text:
{
  "subcategories": [
    {
      "slug": "kebab-case-slug",
      "name": "Title Case Name",
      "include_keywords": ["kw1", "kw2"],
      "exclude_keywords": ["kw1"]
    }
  ]
}
PROMPT;
    }

    /**
     * Strip fences, parse JSON, validate shape. Returns the plan or null.
     */
    private static function parse_ai_plan( string $raw ): ?array {
        $raw = trim( $raw );
        // Strip ```json ... ``` or ``` ... ``` fences if AI ignored the rule.
        if ( preg_match( '/```(?:json)?\s*(.+?)\s*```/s', $raw, $m ) ) {
            $raw = trim( $m[1] );
        }
        $data = json_decode( $raw, true );
        if ( ! is_array( $data ) || empty( $data['subcategories'] ) || ! is_array( $data['subcategories'] ) ) {
            return null;
        }
        $out = [ 'subcategories' => [] ];
        foreach ( $data['subcategories'] as $entry ) {
            if ( ! is_array( $entry ) ) { continue; }
            $slug = sanitize_title( (string) ( $entry['slug'] ?? '' ) );
            $name = trim( wp_strip_all_tags( (string) ( $entry['name'] ?? '' ) ) );
            if ( $slug === '' || $name === '' ) { continue; }
            $inc = array_values( array_filter( array_map( static function ( $k ) {
                return strtolower( trim( (string) $k ) );
            }, (array) ( $entry['include_keywords'] ?? [] ) ), static function ( $k ) {
                return $k !== '' && strlen( $k ) <= 40;
            } ) );
            $exc = array_values( array_filter( array_map( static function ( $k ) {
                return strtolower( trim( (string) $k ) );
            }, (array) ( $entry['exclude_keywords'] ?? [] ) ), static function ( $k ) {
                return $k !== '' && strlen( $k ) <= 40;
            } ) );
            if ( empty( $inc ) ) { continue; }
            $out['subcategories'][] = [
                'slug'             => $slug,
                'name'             => $name,
                'include_keywords' => $inc,
                'exclude_keywords' => $exc,
            ];
        }
        return empty( $out['subcategories'] ) ? null : $out;
    }

    /**
     * Create (or look up existing) sub-cat terms underneath the parent.
     * Returns slug → term_id map.
     */
    private static function create_subcat_terms( array $plan, int $parent_id ): array {
        $map = [];
        foreach ( $plan['subcategories'] as $sc ) {
            $slug = (string) $sc['slug'];
            $name = (string) $sc['name'];

            // term_exists matches BY SLUG regardless of parent. If the
            // slug clashes with a term outside our parent, append a
            // disambiguator so we don't accidentally hijack it.
            $existing = term_exists( $slug, 'product_cat' );
            if ( $existing && is_array( $existing ) ) {
                $term_id   = (int) $existing['term_id'];
                $term_obj  = get_term( $term_id, 'product_cat' );
                if ( $term_obj instanceof WP_Term && (int) $term_obj->parent === $parent_id ) {
                    $map[ $slug ] = $term_id;
                    // Ensure auto-created flag for revert eligibility,
                    // but only when it's already plugin-owned.
                    if ( (int) get_term_meta( $term_id, self::META_AUTO_CREATED, true ) !== 1 ) {
                        update_term_meta( $term_id, self::META_AUTO_CREATED, 1 );
                    }
                    continue;
                }
                // Slug taken by a term elsewhere — disambiguate.
                $slug = $slug . '-' . substr( md5( (string) $parent_id ), 0, 6 );
            }

            $result = wp_insert_term( $name, 'product_cat', [
                'slug'   => $slug,
                'parent' => $parent_id,
            ] );
            if ( is_wp_error( $result ) ) {
                continue;
            }
            $term_id = (int) $result['term_id'];
            update_term_meta( $term_id, self::META_AUTO_CREATED, 1 );
            $map[ (string) $sc['slug'] ] = $term_id;
        }
        return $map;
    }

    /**
     * Walk every product under the parent term, score against the plan,
     * and assign matched sub-cats. Multi-cat by design.
     */
    private static function distribute_products( int $parent_id, array $plan, array $term_id_map ): array {
        $product_ids = get_posts( [
            'post_type'      => 'product',
            'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
            'numberposts'    => -1,
            'fields'         => 'ids',
            'tax_query'      => [ [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $parent_id,
                'include_children' => false,
            ] ],
            'suppress_filters' => true,
        ] );

        $stats = [
            'total'      => count( $product_ids ),
            'matched'    => 0,
            'unmatched'  => 0,
            'per_subcat' => [],
        ];
        $subcats = $plan['subcategories'];

        foreach ( $product_ids as $pid ) {
            $pid   = (int) $pid;
            $title = (string) get_the_title( $pid );
            if ( $title === '' ) {
                $stats['unmatched']++;
                continue;
            }

            $scores       = self::score_product( $title, $subcats );
            $matched_slugs = self::pick_matching_subcats( $scores );
            if ( empty( $matched_slugs ) ) {
                $stats['unmatched']++;
                continue;
            }

            // Backup pre-distribution cats (only once per product —
            // re-runs don't clobber the original snapshot).
            if ( get_post_meta( $pid, self::META_ORIG_CATS, true ) === '' ) {
                $current = wp_get_object_terms( $pid, 'product_cat', [ 'fields' => 'ids' ] );
                if ( ! is_wp_error( $current ) ) {
                    update_post_meta( $pid, self::META_ORIG_CATS, array_map( 'intval', $current ) );
                }
            }

            $new_term_ids = [ $parent_id ];
            foreach ( $matched_slugs as $slug ) {
                if ( isset( $term_id_map[ $slug ] ) ) {
                    $new_term_ids[] = $term_id_map[ $slug ];
                    $stats['per_subcat'][ $slug ] = ( $stats['per_subcat'][ $slug ] ?? 0 ) + 1;
                }
            }
            $new_term_ids = array_values( array_unique( array_map( 'intval', $new_term_ids ) ) );
            wp_set_object_terms( $pid, $new_term_ids, 'product_cat', false );
            $stats['matched']++;
        }
        return $stats;
    }

    /**
     * Score a product title against every sub-category in the plan.
     * Returns slug → score (or -INF when an exclude keyword fires).
     */
    private static function score_product( string $title, array $subcats ): array {
        $hay = ' ' . strtolower( $title ) . ' '; // pad for word-boundary regex
        $scores = [];
        foreach ( $subcats as $sc ) {
            $slug = (string) $sc['slug'];

            // Exclude first — any hit disqualifies this sub-cat.
            $disqualified = false;
            foreach ( $sc['exclude_keywords'] as $kw ) {
                if ( $kw === '' ) { continue; }
                if ( preg_match( '/\b' . preg_quote( $kw, '/' ) . '\b/u', $hay ) ) {
                    $disqualified = true;
                    break;
                }
            }
            if ( $disqualified ) {
                $scores[ $slug ] = -PHP_INT_MAX;
                continue;
            }

            $score = 0;
            foreach ( $sc['include_keywords'] as $kw ) {
                if ( $kw === '' ) { continue; }
                if ( preg_match( '/\b' . preg_quote( $kw, '/' ) . '\b/u', $hay ) ) {
                    // Weight by keyword length — longer matches beat
                    // shorter ones, so "running shoes" beats "shoes".
                    $score += max( 1, strlen( $kw ) );
                }
            }
            $scores[ $slug ] = $score;
        }
        return $scores;
    }

    /**
     * Pick every sub-cat whose score is within tie-tolerance of max,
     * provided that max clears the hard floor. Returns slugs.
     */
    private static function pick_matching_subcats( array $scores ): array {
        if ( empty( $scores ) ) { return []; }
        $max = max( $scores );
        if ( $max < self::SCORE_FLOOR ) { return []; }
        $threshold = max( self::SCORE_FLOOR, $max * self::SCORE_TIE_TOLERANCE );
        $picked = [];
        foreach ( $scores as $slug => $s ) {
            if ( $s >= $threshold ) {
                $picked[] = $slug;
            }
        }
        return $picked;
    }

    /**
     * Drop sub-cat terms that ended up with zero products. AI sometimes
     * proposes a sub-cat that doesn't fit the actual catalog — pruning
     * keeps the storefront tidy + avoids empty breadcrumb nodes.
     */
    private static function prune_empty_subcats( array $term_id_map, array $per_subcat ): int {
        $deleted = 0;
        foreach ( $term_id_map as $slug => $term_id ) {
            $count = (int) ( $per_subcat[ $slug ] ?? 0 );
            if ( $count > 0 ) { continue; }
            // Only delete plugin-owned terms (safety check).
            if ( (int) get_term_meta( (int) $term_id, self::META_AUTO_CREATED, true ) !== 1 ) {
                continue;
            }
            $r = wp_delete_term( (int) $term_id, 'product_cat' );
            if ( $r === true ) {
                $deleted++;
            }
        }
        return $deleted;
    }

    private static function fail( string $msg ): array {
        return [
            'success'        => false,
            'created'        => 0,
            'distributed'    => 0,
            'unmatched'      => 0,
            'parent_term_id' => 0,
            'plan_size'      => 0,
            'per_subcat'     => [],
            'message'        => $msg,
        ];
    }
}
