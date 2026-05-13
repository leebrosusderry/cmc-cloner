<?php
/**
 * Review Seeder — writes a small number of US-sounding, deliberately
 * generic reviews onto selected products so GMC's "Missing reviews /
 * aggregate rating" check passes on a freshly cloned site.
 *
 * Strategy (Hướng C — Hybrid):
 *   1. Seed from a fixed local pool (zero AI cost, 1 click).
 *   2. Optional: polish every seeded review with the configured AI
 *      provider so the text is unique across cloned sites.
 *   3. Revert path — every seeded comment gets a marker meta
 *      `_cmc_seeded_review = 1`, so "Remove seeded reviews" can
 *      delete only the ones this plugin wrote.
 *
 * Distribution: 70/25/5 for 5/4/3 stars (looks natural; 100% 5★
 * is a GMC red flag). Emails use RFC 2606's reserved `example.com`
 * domain — always non-routable, never leaks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Review_Seeder {

    public const META_MARKER         = '_cmc_seeded_review';
    /**
     * Average review count per product (used by the UI for confirm
     * messages + scan hints). Actual count is randomised per product
     * via `pick_review_count()` to avoid the GMC-detectable pattern of
     * "every product has the exact same number of reviews + identical
     * 4.67 average".
     */
    public const REVIEWS_PER_PRODUCT = 4;

    // Pool sizes: enough variation that 3-per-product batches don't
    // repeat identical text across 10+ products in a row.

    /** @var list<string> */
    private const FIRST_NAMES = [
        'Matt', 'Sarah', 'Daniel', 'Jessica', 'Michael', 'Emily', 'David', 'Rachel',
        'Christopher', 'Ashley', 'James', 'Nicole', 'John', 'Amanda', 'Robert', 'Stephanie',
        'William', 'Melissa', 'Joshua', 'Jennifer', 'Andrew', 'Lauren', 'Kevin', 'Rebecca',
        'Brian', 'Katherine', 'Eric', 'Megan', 'Jason', 'Elizabeth', 'Thomas', 'Amber',
        'Ryan', 'Heather', 'Steven', 'Victoria', 'Matthew', 'Olivia', 'Nicholas', 'Abigail',
        'Timothy', 'Samantha', 'Anthony', 'Hannah', 'Jonathan', 'Natalie', 'Benjamin',
        'Tyler', 'Kyle', 'Alyssa', 'Adam', 'Claire', 'Sean', 'Madeline', 'Nathan', 'Kimberly',
        'Travis', 'Brittany', 'Patrick', 'Caroline',
    ];

    /** @var list<string> */
    private const LAST_INITIALS = [
        'A', 'B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L',
        'M', 'N', 'P', 'R', 'S', 'T', 'V', 'W',
    ];

    /** @var list<string> */
    private const POOL_5 = [
        'Exactly what I was looking for. Quality is solid and arrival was quicker than expected.',
        'Really happy with this purchase. It matches the description and feels well made.',
        'Great find. I was hesitant to order online but this exceeded my expectations.',
        'My order arrived quickly and packaging was secure. Will be buying from this shop again.',
        'This is my second order. Consistent quality and helpful customer service throughout.',
        'Bought as a gift and the recipient was thrilled. Looks just like the photos on the site.',
        'Customer service answered my question within a day. The item itself is excellent.',
        'Solid quality for the price. I would recommend the shop to friends.',
        'Better than I expected. Clean finish, no issues at all, shipping was on the faster side of the estimate.',
        'Honestly impressed. Smooth checkout, good email updates, and the item is exactly as described.',
        'Arrived on time and in perfect condition. I will be back for more.',
        'Everything about this order was a nice experience. Packaging, speed, and quality all checked out.',
        'Five stars for the whole experience — from checkout to unboxing.',
        'I did quite a bit of research before buying and I do not regret this choice at all.',
        'Very pleased. Felt like a careful, small shop and the item lived up to the listing.',
        'Would order again. Clean presentation and nothing felt cheap about it.',
        'The detail is genuinely good. Worth every cent in my opinion.',
        'Shipping was prompt and everything was protected well. Lovely overall.',
        'Friendly email confirmations and fast delivery. The item is just as described.',
        'Finally a shop that ships quickly and describes products accurately. Very happy.',
    ];

    /** @var list<string> */
    private const POOL_4 = [
        'Nice overall. Shipping took a few more days than I had hoped but the item itself is great.',
        'Good purchase. Only wish there were a few more options, otherwise very happy.',
        'Pleased with the quality. Minor packaging dent on arrival but nothing damaged inside.',
        'Matches the listing. Would be five stars if it had arrived a bit sooner.',
        'Solid item. I would recommend it, and customer service answered my follow-up quickly.',
        'Happy with my order. I would have liked slightly more detailed care instructions.',
        'Good value. Arrived in one piece and the build feels thoughtful.',
        'Overall a positive experience — I would just suggest adding a tracking email a bit earlier in the process.',
        'Really like it. A small detail did not match perfectly but the shop handled it kindly.',
    ];

    /** @var list<string> */
    private const POOL_3 = [
        'Decent. It does the job, though I expected a slightly different finish based on the photos.',
        'It is okay. Arrived safely and the shop was responsive when I asked a question.',
        'Average experience. Nothing wrong with the item, just not quite what I pictured.',
        'Fair for the price. The item is fine; the shipping notifications could be clearer.',
    ];

    // ---------- Scan / listing ----------

    /**
     * Read-only: list products for the UI picker. Returns up to $limit
     * items plus a running tally of already-seeded reviews site-wide so
     * the "Remove seeded reviews" button knows whether to show.
     *
     * @return array{
     *   products: list<array{id:int, title:string, sku:string, review_count:int, status:string}>,
     *   seeded_total: int,
     *   products_with_reviews: int,
     *   total_products: int,
     * }
     */
    public static function scan( int $limit = 300 ): array {
        global $wpdb;
        $limit = max( 10, min( 1000, $limit ) );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_status,
                    (SELECT pm.meta_value FROM {$wpdb->postmeta} pm
                       WHERE pm.post_id = p.ID AND pm.meta_key = '_sku' LIMIT 1) AS sku,
                    p.comment_count AS review_count
             FROM {$wpdb->posts} p
             WHERE p.post_type = 'product'
               AND p.post_status IN ('publish','draft','private')
             ORDER BY review_count ASC, p.ID ASC
             LIMIT %d",
            $limit
        ) );

        $products               = [];
        $products_with_reviews  = 0;

        foreach ( (array) $rows as $r ) {
            $rc = (int) $r->review_count;
            if ( $rc > 0 ) {
                $products_with_reviews++;
            }
            $products[] = [
                'id'           => (int) $r->ID,
                'title'        => (string) $r->post_title,
                'sku'          => (string) ( $r->sku ?? '' ),
                'review_count' => $rc,
                'status'       => (string) $r->post_status,
            ];
        }

        $total_products = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'product'
               AND post_status IN ('publish','draft','private')"
        );

        $seeded_total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->commentmeta}
             WHERE meta_key = %s AND meta_value = '1'",
            self::META_MARKER
        ) );

        return [
            'products'              => $products,
            'seeded_total'          => $seeded_total,
            'products_with_reviews' => $products_with_reviews,
            'total_products'        => $total_products,
        ];
    }

    // ---------- Seed ----------

    /**
     * Seed exactly REVIEWS_PER_PRODUCT reviews onto each target product.
     * Products with existing reviews are skipped unless $include_existing
     * is true (explicit "I know what I'm doing" opt-in from the UI).
     *
     * @param list<int> $product_ids
     * @return array{
     *   seeded:int, skipped:int, errors:list<string>,
     *   items:list<array{product_id:int, comment_ids:list<int>}>
     * }
     */
    public static function seed( array $product_ids, bool $include_existing = false ): array {
        $seeded  = 0;
        $skipped = 0;
        $errors  = [];
        $items   = [];

        foreach ( $product_ids as $pid ) {
            $pid = (int) $pid;
            if ( $pid <= 0 || get_post_type( $pid ) !== 'product' ) {
                $errors[] = sprintf( '#%d: not a product.', $pid );
                continue;
            }

            if ( ! $include_existing ) {
                $existing = (int) get_comments( [
                    'post_id'     => $pid,
                    'type'        => 'review',
                    'status'      => 'approve',
                    'count'       => true,
                ] );
                if ( $existing > 0 ) {
                    $skipped++;
                    continue;
                }
            }

            $comment_ids = [];
            // Per-product variable review count: keeps the denominator
            // varied across the catalogue so identical-average clustering
            // (the "6/14 products at exactly 4.67/5" red flag GMC AI
            // catches) doesn't happen.
            $review_count = self::pick_review_count();
            foreach ( self::pick_star_sequence( $review_count ) as $stars ) {
                $cid = self::insert_review( $pid, $stars );
                if ( $cid > 0 ) {
                    $comment_ids[] = $cid;
                    $seeded++;
                } else {
                    $errors[] = sprintf( '#%d: insert failed.', $pid );
                }
            }
            self::flush_product_caches( $pid );
            $items[] = [ 'product_id' => $pid, 'comment_ids' => $comment_ids ];
        }

        return [
            'seeded'  => $seeded,
            'skipped' => $skipped,
            'errors'  => $errors,
            'items'   => $items,
        ];
    }

    // ---------- AI polish ----------

    /**
     * Rewrite one seeded review via the configured AI provider, preserving
     * its star sentiment. Returns the new content so the client can update
     * its progress UI. The marker meta is kept in place so a revert still
     * removes the comment.
     */
    public static function ai_polish_one( int $comment_id ): array {
        $comment = get_comment( $comment_id );
        if ( ! $comment || (string) $comment->comment_type !== 'review' ) {
            return [ 'ok' => false, 'message' => 'Not a review comment.' ];
        }
        if ( (string) get_comment_meta( $comment_id, self::META_MARKER, true ) !== '1' ) {
            return [ 'ok' => false, 'message' => 'Not a seeded review.' ];
        }

        $stars = (int) get_comment_meta( $comment_id, 'rating', true );
        if ( $stars < 1 || $stars > 5 ) { $stars = 5; }

        $settings    = CMC_Settings::get();
        $nganh_slug  = (string) ( $settings['company_info']['nganh_hang'] ?? '' );
        $nganh_opts  = CMC_Shortcodes::nganh_hang_options();
        $nganh_label = (string) ( $nganh_opts[ $nganh_slug ] ?? $nganh_slug );

        $prompt = self::build_rewrite_prompt(
            (string) $comment->comment_content,
            $stars,
            $nganh_label
        );

        try {
            $out = CMC_AI_Client::generate( $prompt );
        } catch ( Throwable $e ) {
            return [ 'ok' => false, 'message' => $e->getMessage() ];
        }

        $cleaned = self::clean_ai_output( (string) $out );
        if ( $cleaned === '' ) {
            return [ 'ok' => false, 'message' => 'AI returned empty text.' ];
        }

        wp_update_comment( [
            'comment_ID'      => $comment_id,
            'comment_content' => $cleaned,
        ] );

        self::flush_product_caches( (int) $comment->comment_post_ID );

        return [ 'ok' => true, 'comment_id' => $comment_id, 'content' => $cleaned ];
    }

    /**
     * Return every seeded-review comment ID so the client can iterate and
     * call ai_polish_one in sequence with a progress bar.
     *
     * @return list<int>
     */
    public static function list_seeded_ids(): array {
        global $wpdb;
        $rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT comment_id FROM {$wpdb->commentmeta}
             WHERE meta_key = %s AND meta_value = '1'
             ORDER BY comment_id ASC",
            self::META_MARKER
        ) );
        return array_map( 'intval', (array) $rows );
    }

    // ---------- Remove ----------

    /**
     * Hard-delete every comment with the seeded marker. Used by the
     * "Remove seeded reviews" button. Real customer reviews are
     * untouched because they never have the marker meta.
     *
     * @return array{ deleted:int, products:list<int>, errors:list<string> }
     */
    public static function remove_all_seeded(): array {
        global $wpdb;
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT comment_id FROM {$wpdb->commentmeta}
             WHERE meta_key = %s AND meta_value = '1'",
            self::META_MARKER
        ) );

        $deleted     = 0;
        $errors      = [];
        $product_ids = [];

        foreach ( (array) $ids as $cid ) {
            $cid     = (int) $cid;
            $comment = get_comment( $cid );
            if ( ! $comment ) {
                continue;
            }
            $pid = (int) $comment->comment_post_ID;
            if ( wp_delete_comment( $cid, true ) ) {
                $deleted++;
                if ( $pid > 0 ) {
                    $product_ids[ $pid ] = true;
                }
            } else {
                $errors[] = sprintf( '#%d: delete failed.', $cid );
            }
        }

        foreach ( array_keys( $product_ids ) as $pid ) {
            self::flush_product_caches( (int) $pid );
        }

        return [
            'deleted'  => $deleted,
            'products' => array_keys( $product_ids ),
            'errors'   => $errors,
        ];
    }

    // ---------- Internals ----------

    /**
     * How many reviews to seed for a given product. Weighted bell with
     * a wide tail so the catalogue lands on a mix of denominators
     * (1, 2, 3, 4, 5, 6, 7) — different denominators × different sums
     * = naturally varied averages across the storefront.
     *
     * Distribution (cumulative):
     *   1 review →  6%   (rare; lands on 5.0 / 4.0 / 3.0 exact)
     *   2 reviews → 14% (avg 4.5 / 5.0 / 4.0 / 3.5 / 3.0)
     *   3 reviews → 22% (avg 4.67 / 4.33 / 5.0 / 4.0 / 3.67)
     *   4 reviews → 22% (avg 4.75 / 4.50 / 4.25 / 5.0 / 4.0)
     *   5 reviews → 16% (avg 4.6 / 4.8 / 4.4 / 4.2 / 5.0)
     *   6 reviews → 12% (avg 4.83 / 4.67 / 4.5 / 4.33 / 5.0)
     *   7 reviews →  8% (avg 4.71 / 4.86 / 4.57 / 4.43 / 5.0)
     *
     * No bucket dominates → avg ratings spread across ~15 distinct
     * decimal values site-wide instead of clustering at 4.67.
     */
    private static function pick_review_count(): int {
        $r = random_int( 1, 100 );
        if ( $r <=  6 ) { return 1; }
        if ( $r <= 20 ) { return 2; }
        if ( $r <= 42 ) { return 3; }
        if ( $r <= 64 ) { return 4; }
        if ( $r <= 80 ) { return 5; }
        if ( $r <= 92 ) { return 6; }
        return 7;
    }

    /**
     * Weighted random sequence of star values for the requested review
     * count. The previous implementation forced any all-5★ result to
     * 5+5+4 to "avoid the all-5★ red flag" — but combined with a fixed
     * 3-review count it produced the OPPOSITE red flag: 71% of products
     * landing on the exact same 4.67 average, which GMC AI flags as
     * fake. We now let the natural distribution play out: small N and
     * lucky rolls can legitimately yield 5.0; per-product chance of a
     * 3★ review is high enough to scatter averages across the 4.0–4.9
     * range.
     *
     * @return list<int>
     */
    private static function pick_star_sequence( int $count ): array {
        $out = [];
        for ( $i = 0; $i < $count; $i++ ) {
            $out[] = self::pick_star();
        }
        // Occasional "controversy spread" — for ~10% of products with
        // 3+ reviews, force one slot to a lower star (3) so the page
        // shows a realistic spread instead of all-positive. Mimics
        // real consumer behaviour where any popular product attracts
        // at least one critic.
        if ( $count >= 3 && random_int( 1, 100 ) <= 10 ) {
            $idx        = random_int( 0, $count - 1 );
            $out[ $idx ] = 3;
        }
        return $out;
    }

    /**
     * Per-review star pick. Distribution adjusted from 70/25/5 to
     * 60/30/10 (5★/4★/3★) so 3★ reviews are common enough to scatter
     * averages naturally — the old 5% rate meant a 3★ almost never
     * appeared in a 3-pick sample, leaving every product clustered at
     * either 4.67 or 5.0.
     */
    private static function pick_star(): int {
        $r = random_int( 1, 100 );
        if ( $r <= 60 ) { return 5; }
        if ( $r <= 90 ) { return 4; }
        return 3;
    }

    private static function pick_pool( int $stars ): string {
        $pool = match ( $stars ) {
            5       => self::POOL_5,
            4       => self::POOL_4,
            default => self::POOL_3,
        };
        return $pool[ random_int( 0, count( $pool ) - 1 ) ];
    }

    private static function insert_review( int $product_id, int $stars ): int {
        $first   = self::FIRST_NAMES[ random_int( 0, count( self::FIRST_NAMES ) - 1 ) ];
        $initial = self::LAST_INITIALS[ random_int( 0, count( self::LAST_INITIALS ) - 1 ) ];
        $author  = $first . ' ' . $initial . '.';

        // Email: {firstname}.{initial}{4digits}@example.com
        // example.com is IANA-reserved (RFC 2606) and guaranteed non-routable.
        $suffix = str_pad( (string) random_int( 0, 9999 ), 4, '0', STR_PAD_LEFT );
        $email  = strtolower( $first ) . '.' . strtolower( $initial ) . $suffix . '@example.com';

        // Random date 15–90 days ago, random time of day.
        $days_ago = random_int( 15, 90 );
        $secs_in  = random_int( 0, DAY_IN_SECONDS - 1 );
        $ts       = time() - ( $days_ago * DAY_IN_SECONDS ) - $secs_in;
        $local    = wp_date( 'Y-m-d H:i:s', $ts );
        $gmt      = gmdate( 'Y-m-d H:i:s', $ts );

        $content = self::pick_pool( $stars );

        $cid = wp_insert_comment( [
            'comment_post_ID'      => $product_id,
            'comment_author'       => $author,
            'comment_author_email' => $email,
            'comment_author_IP'    => '',
            'comment_content'      => $content,
            'comment_type'         => 'review',
            'comment_approved'     => 1,
            'comment_date'         => $local,
            'comment_date_gmt'     => $gmt,
            'user_id'              => 0,
        ] );

        if ( ! $cid ) {
            return 0;
        }

        update_comment_meta( (int) $cid, 'rating', $stars );
        update_comment_meta( (int) $cid, 'verified', 0 );
        update_comment_meta( (int) $cid, self::META_MARKER, 1 );

        return (int) $cid;
    }

    /**
     * Refresh WooCommerce's per-product caches so the new aggregate rating
     * + review count surface immediately on the frontend and admin list.
     */
    private static function flush_product_caches( int $product_id ): void {
        if ( function_exists( 'wc_delete_product_transients' ) ) {
            wc_delete_product_transients( $product_id );
        }
        if ( class_exists( 'WC_Comments' ) && method_exists( 'WC_Comments', 'clear_transients' ) ) {
            WC_Comments::clear_transients( $product_id );
        }
        // Recompute the product's post.comment_count column so WC's
        // `$product->get_review_count()` and admin columns stay in sync.
        wp_update_comment_count_now( $product_id );
        clean_post_cache( $product_id );
    }

    private static function build_rewrite_prompt( string $original, int $stars, string $nganh ): string {
        $nganh_line = $nganh !== '' ? "Industry context: {$nganh} store." : '';
        return <<<PROMPT
You are rewriting a single product review so it is textually unique across cloned sites while keeping the same sentiment.

Rules:
- Keep the same {$stars}-star sentiment (positive for 5, positive-with-minor-qualification for 4, mixed-neutral for 3).
- 1–3 sentences. Plain American English. No lists, no emojis.
- Do NOT name any brand, celebrity, competitor, or specific product-noun (no "shoes", "dress", "dumbbell", "handbag", etc.). Keep it generic — "the item", "my order", "this purchase", "the shop".
- Do NOT promise guarantees, warranties, or price claims.
- Do NOT copy any sentence verbatim from the original.
- Output ONLY the rewritten review text. No quotes, no headings, no commentary.

{$nganh_line}

Original:
{$original}
PROMPT;
    }

    private static function clean_ai_output( string $text ): string {
        $t = trim( $text );
        // Strip code fences if the model wrapped the answer.
        if ( preg_match( '/^```(?:[a-zA-Z]+)?\s*\n(.*)\n```\s*$/s', $t, $m ) ) {
            $t = trim( $m[1] );
        }
        // Strip surrounding quotes if present.
        $t = trim( $t, "\"'" );
        // Keep to a reasonable length.
        if ( function_exists( 'mb_substr' ) && mb_strlen( $t ) > 600 ) {
            $t = mb_substr( $t, 0, 600 );
        }
        return wp_strip_all_tags( $t );
    }
}
