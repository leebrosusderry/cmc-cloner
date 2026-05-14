<?php
/**
 * AI-driven product title rewriter — strips brand-name leakage,
 * promotional spam, and ALL-CAPS / marketplace identifiers from titles
 * imported by Woo POD (Amazon scrape) so they pass GMC review.
 *
 * Design constraints (per operator brief, 2026-05):
 *   - Compliance over flair: GMC pass is the only goal. Output should
 *     be CORRECT (rule-following), not necessarily clever.
 *   - Operate on EVERY product, even ones already clean — the format
 *     pass also fixes ALL-CAPS / overlong titles, not just brand
 *     leakage.
 *   - Skip products with `_cmc_title_rewritten_at` postmeta on a
 *     repeat run, so re-clicking the button doesn't burn AI credits
 *     on already-processed rows. Operator can revert + rerun if needed.
 *   - Keep the original title in `_cmc_original_title` so `revert_*`
 *     can put it back exactly. Stored once, never overwritten by a
 *     subsequent rewrite.
 *
 * GMC title rules baked into the prompt:
 *   - 40–100 characters (target band).
 *   - No protected trademarks (CMC_Schema::PROTECTED_BRANDS list).
 *   - No promotional words (sale, free, deal, best, premium, etc.).
 *   - No ALL-CAPS phrases — Title Case only.
 *   - No emoji, ★ symbols, parenthetical SEO bursts.
 *   - No marketplace identifiers (Amazon, Prime, Choice, Bestseller).
 *   - Format: <Product type> <key descriptors> [material/color/size].
 *
 * Errors are non-fatal: if the AI call throws or returns garbage, we
 * keep the original title untouched and surface the error in the batch
 * report so the operator can retry just those rows.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Title_Rewriter {

    public const META_ORIGINAL          = '_cmc_original_title';
    public const META_ORIGINAL_DESC      = '_cmc_original_description';
    public const META_ORIGINAL_SLUG      = '_cmc_original_slug';
    public const META_REWRITTEN_AT       = '_cmc_title_rewritten_at';
    // Soft-fail marker: title saved BUT some Amazon-shape rules were
    // still tripped after 2 attempts. The product is removed from the
    // batch queue (won't infinite-retry) and flagged for admin review.
    public const META_VALIDATION_WARNING = '_cmc_title_validation_warning';
    // Hard-fail marker: AI produced output that violates a critical
    // rule (protected brand, ALL-CAPS, etc.) on both attempts. The
    // original title is kept; product is removed from the batch queue
    // so the rewriter doesn't loop forever. Operator can revert + retry.
    public const META_PROCESSING_ERROR   = '_cmc_title_processing_error';
    // Soft signal: AI description was saved but tripped a quality
    // check (too short, too long, possible brand leak). The
    // description still went live — this meta only flags the row for
    // admin review so a future pass can scan for products that need
    // manual cleanup.
    public const META_DESC_QUALITY_WARNING = '_cmc_desc_quality_warning';

    /**
     * Title length band. Outside = treat as AI garbage and keep the
     * original.
     *
     * The current numbers target a "store-catalogue" voice (4–7 words,
     * 25–65 chars) instead of the marketplace "attribute-stacked" shape
     * (Amazon listings routinely hit 8–15 words and 100+ chars). GMC
     * reviewers flag long attribute-stacked titles as "still looks like
     * a supplier listing" even when no specific brand words are present
     * — the SHAPE itself is the giveaway. Keeping titles short forces
     * the model to pick the ONE primary attribute instead of stacking.
     */
    // MIN_LEN guards against AI fragments ("Mug", "Hat", "Ring") — bare
    // 1-word outputs that lost product context. Anything ≥10 chars is
    // a real store-voice title ("Plush Toy" = 9 → still fails; "Wool
    // Throw" = 10 → passes). Earlier this was 25, which mistakenly
    // killed every clean 3-word title the new store-voice prompt
    // explicitly recommends ("12-Inch Plush Toy" = 17 chars,
    // "Plug-in Pest Repellent" = 22 chars). The mismatch made ~40%
    // of products fail HARD validation despite the AI doing its job.
    private const MIN_LEN     = 10;
    private const MAX_LEN     = 65;     // SOFT ceiling — over this triggers a warning, not a reject
    private const TARGET_MIN  = 10;
    private const TARGET_MAX  = 65;
    private const MAX_WORDS   = 7;      // SOFT ceiling — over this triggers a warning, not a reject
    // HARD ceilings — over these, the title is rejected outright and
    // the product is flagged with META_PROCESSING_ERROR so the batch
    // doesn't keep retrying a product the model can't size-down on.
    private const HARD_MAX_LEN   = 90;
    private const HARD_MAX_WORDS = 10;

    /**
     * Description length band — measured on PLAIN TEXT (no HTML).
     *
     * AI outputs plain text (no `<p>`, `<ul>`, `<li>`, `<strong>`)
     * because page builders (Flatsome, Elementor, Divi) apply theme
     * styling to `<ul>` / `<strong>` inconsistently and sometimes
     * break the product-detail layout. wpautop() on the front-end
     * wraps each paragraph in `<p>` automatically — same visual
     * outcome with zero risk.
     *
     * Length targets (updated): ~120–200 words (≈600–1200 chars).
     * Three paragraphs instead of two: an intro + a details prose
     * paragraph + a feature line. Hard floor 100 chars filters true
     * fragments; hard ceiling 2500 chars gives generous headroom.
     */
    private const DESC_MIN_TEXT_LEN  = 100;
    private const DESC_MAX_TEXT_LEN  = 2500;   // hard ceiling
    private const DESC_TARGET_MIN    = 600;    // soft floor (~120 words)
    private const DESC_TARGET_MAX    = 1300;   // soft ceiling (~250 words)

    /**
     * Generic placeholder used when the AI couldn't produce a usable
     * description (validation failed or AI catastrophically failed).
     * Short, GMC-safe, applicable to any industry. Defined once here
     * so both fallback paths use the exact same string.
     */
    private const DESC_FALLBACK_PLACEHOLDER = 'Please refer to the product images and the title for details and specifications.';

    /**
     * Count products eligible for rewrite. Excludes the auto-draft +
     * trash buckets (those aren't shown to GMC reviewers anyway) and
     * skips rows that already carry the rewritten-at marker so the
     * scan number reflects what `rewrite_batch()` will actually call
     * the AI on.
     *
     * @return array{total:int, pending:int, already:int}
     */
    public static function scan(): array {
        global $wpdb;

        $total = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
              WHERE post_type = 'product'
                AND post_status NOT IN ('auto-draft','trash')"
        );

        $already = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
               FROM {$wpdb->posts} p
               INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                       AND pm.meta_key = %s
              WHERE p.post_type   = 'product'
                AND p.post_status NOT IN ('auto-draft','trash')",
            self::META_REWRITTEN_AT
        ) );

        return [
            'total'   => $total,
            'pending' => max( 0, $total - $already ),
            'already' => $already,
        ];
    }

    /**
     * Pull the next slice of un-rewritten products and rewrite their
     * titles with AI. Caller paginates by re-invoking until `done` is
     * true. Each call is bounded by `$limit` so a 30 s LSAPI window
     * covers ~5 OpenAI round-trips comfortably.
     *
     * @return array{
     *   processed:int, succeeded:int, failed:int, skipped:int,
     *   remaining:int, done:bool,
     *   samples:list<array{id:int, before:string, after:string, error?:string}>,
     * }
     */
    public static function rewrite_batch( int $limit = 5 ): array {
        global $wpdb;

        $limit = max( 1, min( 20, $limit ) );

        // Raise budgets so multi-second AI calls don't trip LSAPI.
        @set_time_limit( 120 );

        // Auto-clear stale processing-error markers from previous runs.
        // Without this, products that failed under an older code version
        // (e.g. when MIN_LEN was 25 and false-rejected clean 17-char
        // titles) stay parked forever and the batch reports done=true
        // while those products still have their original supplier
        // titles. With the current code path catastrophic failures
        // re-mark on retry, so this cleanup is cost-free for genuine
        // failures and unblocks legacy false-positives in one shot.
        $wpdb->delete( $wpdb->postmeta, [ 'meta_key' => self::META_PROCESSING_ERROR ] );

        // Eligible IDs = products without the rewritten-at marker.
        // The processing-error marker is no longer used as an exclusion
        // gate (cleared above on every batch call) — the only marker
        // that removes a product from the queue is rewritten-at, which
        // is set on EVERY successful save including save-with-warning.
        // ORDER BY ID ASC keeps the loop deterministic so a re-run
        // picks up where it left off.
        $ids = (array) $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID
               FROM {$wpdb->posts} p
               LEFT JOIN {$wpdb->postmeta} pm_done ON pm_done.post_id = p.ID
                                                  AND pm_done.meta_key = %s
              WHERE p.post_type   = 'product'
                AND p.post_status NOT IN ('auto-draft','trash')
                AND pm_done.meta_id IS NULL
              ORDER BY p.ID ASC
              LIMIT %d",
            self::META_REWRITTEN_AT,
            $limit
        ) );

        $processed     = 0;
        $succeeded     = 0;
        $warned_hard   = 0;  // saved but with HARD severity warning (urgent review)
        $warned_soft   = 0;  // saved but with SOFT severity warning (optional review)
        $failed        = 0;  // catastrophic AI failure — no title saved
        $skipped       = 0;
        $samples       = [];

        foreach ( $ids as $id ) {
            $id  = (int) $id;
            $res = self::rewrite_product( $id );
            $processed++;

            $status   = (string) ( $res['status'] ?? '' );
            $severity = (string) ( $res['severity'] ?? '' );
            if ( $status === 'ok' || $status === 'ok_with_warning' ) {
                $succeeded++;
                if ( $severity === 'hard' ) { $warned_hard++; }
                if ( $severity === 'soft' ) { $warned_soft++; }
                if ( count( $samples ) < 5 ) {
                    $samples[] = [
                        'id'       => $id,
                        'before'   => (string) $res['before'],
                        'after'    => (string) $res['after'],
                        'severity' => $severity ?: null,
                        'warning'  => ( $severity !== '' ) ? (array) ( $res['warning'] ?? [] ) : null,
                    ];
                }
            } elseif ( $status === 'skipped' ) {
                $skipped++;
            } else {
                $failed++;
                if ( count( $samples ) < 5 ) {
                    $samples[] = [
                        'id'     => $id,
                        'before' => (string) $res['before'],
                        'after'  => '',
                        'error'  => (string) ( $res['error'] ?? '' ),
                    ];
                }
            }
        }

        $remaining = self::count_remaining();
        return [
            'processed'   => $processed,
            'succeeded'   => $succeeded,
            'warned_hard' => $warned_hard,
            'warned_soft' => $warned_soft,
            'warned'      => $warned_hard + $warned_soft, // legacy alias
            'failed'      => $failed,
            'skipped'     => $skipped,
            'remaining'   => $remaining,
            'done'        => ( $remaining === 0 ),
            'samples'     => $samples,
        ];
    }

    /**
     * Rewrite a single product's title + full description in ONE AI
     * call. The model returns a JSON object `{ title, description }`
     * which we parse, validate per-field, and write back to the post.
     *
     * Description-touched policy: we only overwrite `post_content`
     * when the AI produced a USABLE description (passes length +
     * brand + HTML-sanitisation gates). If only the title is usable,
     * we still update the title so a partial recovery is better than
     * none.
     *
     * @return array{status:string, before:string, after:string, error?:string}
     */
    public static function rewrite_product( int $product_id ): array {
        $post = get_post( $product_id );
        if ( ! $post || $post->post_type !== 'product' ) {
            return [
                'status' => 'error',
                'before' => '',
                'after'  => '',
                'error'  => 'Product not found',
            ];
        }

        $title_before = (string) $post->post_title;
        $desc_before  = (string) $post->post_content;

        if ( $title_before === '' ) {
            update_post_meta( $product_id, self::META_REWRITTEN_AT, time() );
            return [ 'status' => 'skipped', 'before' => '', 'after' => '' ];
        }

        // Up to 2 attempts per product with HARD / SOFT tiered validation.
        //
        // After the loop ends, the outcome is exactly one of:
        //   (a) CLEAN     — HARD pass + SOFT pass → save title, mark done.
        //   (b) WARNING   — HARD pass + SOFT fail (after 2 attempts) → save
        //                   title anyway, stamp META_VALIDATION_WARNING so
        //                   the admin UI can flag it for review, mark done.
        //   (c) ERROR     — HARD fail (after 2 attempts) → don't save,
        //                   stamp META_PROCESSING_ERROR so the batch loop
        //                   removes the product from the queue (no
        //                   infinite retry). Operator can revert + retry.
        //
        // Outcomes (b) and (c) BOTH guarantee 1-pass processing — the
        // product never gets reprocessed by the next batch run.
        $max_attempts    = 2;
        $title_after     = '';
        $parsed          = null;
        $tiered          = [ 'hard' => [], 'soft' => [] ];
        $retry_feedback  = '';
        $last_raw        = '';
        $last_error      = '';
        $had_ai_failure  = false;

        for ( $attempt = 1; $attempt <= $max_attempts; $attempt++ ) {
            try {
                $last_raw = self::ai_rewrite( $title_before, $desc_before, $retry_feedback );
            } catch ( \Throwable $t ) {
                $last_error      = 'AI: ' . $t->getMessage();
                $had_ai_failure  = true;
                continue;
            }

            $parsed = self::parse_json_output( $last_raw );
            if ( ! is_array( $parsed ) || empty( $parsed['title'] ) ) {
                $last_error     = 'AI returned invalid JSON / missing title field';
                $had_ai_failure = true;
                $retry_feedback = "PREVIOUS ATTEMPT FAILED: the output was not a valid JSON object with non-empty `title` and `description` keys. Return EXACTLY the JSON object shape specified above — no markdown fences, no commentary.\n\n";
                continue;
            }

            $title_after = self::sanitise_output( (string) $parsed['title'] );
            $tiered      = self::validation_reasons_tiered( $title_after );

            if ( $tiered['hard'] === [] && $tiered['soft'] === [] ) {
                break; // clean — exit the loop
            }

            // Build the retry feedback footer with the combined HARD + SOFT
            // reasons so the model knows exactly what to fix.
            $all = array_merge( $tiered['hard'], $tiered['soft'] );
            $last_error     = sprintf(
                'Title validation failed: %s — AI output was: "%s"',
                implode( '; ', $all ),
                $title_after
            );
            $retry_feedback = sprintf(
                "PREVIOUS ATTEMPT FAILED with title \"%s\". Reasons:\n  - %s\nRe-generate the title with these specific issues fixed. Stay inside the BANNED shape patterns list above. Keep the description structure unchanged unless that part also failed.\n\n",
                $title_after,
                implode( "\n  - ", $all )
            );
        }

        // Outcome (c) CATASTROPHIC — AI threw, or returned malformed JSON,
        // on BOTH attempts. We have no title to save. Stamp the error
        // marker so the next batch run knows this product needs a
        // retry; operator can clear the marker + retry later.
        //
        // 100% description-overwrite guarantee: even though the title
        // stays as the (Amazon) original here, the description MUST
        // be overwritten with the safe placeholder — otherwise the
        // long marketplace-style description survives and gets
        // flagged by GMC review. The inconsistent visual state
        // (Amazon title + placeholder description) is itself the
        // operator's signal that "this product needs to be re-run".
        if ( $had_ai_failure && ( ! is_array( $parsed ) || empty( $parsed['title'] ) ) ) {
            // Snapshot original description ONCE so revert can restore
            // it exactly. Same metadata_exists() guard as the success
            // path so a previous snapshot isn't overwritten.
            if ( ! metadata_exists( 'post', $product_id, self::META_ORIGINAL_DESC ) ) {
                add_post_meta( $product_id, self::META_ORIGINAL_DESC, $desc_before, true );
            }
            wp_update_post( [
                'ID'           => $product_id,
                'post_content' => self::DESC_FALLBACK_PLACEHOLDER,
            ] );
            clean_post_cache( $product_id );

            update_post_meta( $product_id, self::META_PROCESSING_ERROR, [
                'at'       => time(),
                'reason'   => $last_error,
                'attempts' => $max_attempts,
                'severity' => 'catastrophic',
            ] );
            return [
                'status'      => 'error',
                'before'      => $title_before,
                'after'       => '',
                'desc_status' => 'placeholder',
                'error'       => $last_error !== ''
                    ? $last_error . sprintf( ' (after %d attempt%s) — description overwritten with placeholder', $max_attempts, $max_attempts === 1 ? '' : 's' )
                    : 'AI failed after retries; description overwritten with placeholder',
            ];
        }

        // From here onward: we HAVE a title from the AI (even if it
        // still trips validation). The 1-run-guarantee policy is: SAVE
        // the AI output regardless of HARD/SOFT validation status — a
        // partially-flawed title is still leagues better than the
        // pre-rewrite supplier title, and the warning marker lets the
        // admin review-and-fix the small minority that need cleanup.
        //
        //   - HARD reasons remain → save + HARD severity warning (review urgent)
        //   - SOFT reasons remain → save + SOFT severity warning (review optional)
        //   - No reasons          → save clean, no warning
        //
        // This change replaces the old "block HARD-failed titles" path,
        // which left some products with their original supplier title
        // and made the batch run look like it terminated early.
        $hard_warning_reasons = $tiered['hard'];
        $soft_warning_reasons = $tiered['soft'];
        $warning_severity     = $hard_warning_reasons !== [] ? 'hard' : ( $soft_warning_reasons !== [] ? 'soft' : '' );
        $warning_reasons_all  = array_merge( $hard_warning_reasons, $soft_warning_reasons );

        // ---- Description (best-effort) ----
        // If the AI gave us a clean description, we overwrite. If not,
        // we leave the original description in place and still keep the
        // new title — partial success is better than rejecting the row.
        // Description policy — ALWAYS save the AI-generated description
        // when one exists, regardless of whether it matches the ideal
        // length / format. Validation is no longer a hard gate: a
        // slightly short or slightly long AI description is still leagues
        // better than the generic placeholder. The only path that
        // produces the placeholder now is "AI returned an empty
        // description value" (parsed['description'] missing or after
        // sanitization the string is empty) — which is genuinely rare.
        //
        // Brand-leaked descriptions (very rare on rewrites that ALREADY
        // produced a clean title) still get saved here; the admin can
        // review and fix manually. The trade-off matches the operator's
        // explicit preference: never see the placeholder, accept that a
        // tiny minority of descriptions may need manual cleanup.
        $desc_after_raw    = isset( $parsed['description'] ) ? (string) $parsed['description'] : '';
        $desc_after_clean  = self::sanitise_description( $desc_after_raw );

        if ( $desc_after_clean !== '' ) {
            $desc_to_save = $desc_after_clean;
            $desc_status  = 'rewritten';
            // Soft quality signal — stamp a meta when the description
            // is unusually short, unusually long, or has a brand leak
            // so an admin scan can surface "needs review" rows
            // without blocking the save. The meta is cleared when the
            // description is clean.
            $desc_warning_reasons = self::description_quality_warnings( $desc_after_clean );
            if ( $desc_warning_reasons !== [] ) {
                update_post_meta( $product_id, self::META_DESC_QUALITY_WARNING, [
                    'at'       => time(),
                    'reasons'  => $desc_warning_reasons,
                    'len'      => function_exists( 'mb_strlen' ) ? mb_strlen( $desc_after_clean ) : strlen( $desc_after_clean ),
                ] );
            } else {
                delete_post_meta( $product_id, self::META_DESC_QUALITY_WARNING );
            }
        } else {
            // AI legitimately gave no description content (parsed value
            // was missing / empty / pure whitespace / pure HTML that
            // stripped to nothing). Placeholder is the only option.
            $desc_to_save = self::DESC_FALLBACK_PLACEHOLDER;
            $desc_status  = 'placeholder';
            delete_post_meta( $product_id, self::META_DESC_QUALITY_WARNING );
        }

        // Save originals (once each) so revert can restore exactly.
        // We check `metadata_exists()` rather than an empty-string
        // comparison so the snapshot is never overwritten on a re-run
        // — and so a legitimately empty original (rare: title-less
        // import, blank description) is still recorded as a known
        // empty rather than mistaken for "never saved".
        if ( ! metadata_exists( 'post', $product_id, self::META_ORIGINAL ) ) {
            add_post_meta( $product_id, self::META_ORIGINAL, $title_before, true );
        }
        // Always snapshot the original description before we overwrite
        // (revert restores the exact pre-rewrite state regardless of
        // whether the new content came from AI or the placeholder).
        if ( ! metadata_exists( 'post', $product_id, self::META_ORIGINAL_DESC ) ) {
            add_post_meta( $product_id, self::META_ORIGINAL_DESC, $desc_before, true );
        }

        $update = [
            'ID'           => $product_id,
            'post_title'   => $title_after,
            'post_content' => $desc_to_save,  // always overwrite
        ];

        // Re-derive the URL slug from the new title so /product/<slug>/
        // matches the rewritten title. Two pieces of WordPress core
        // handle the legacy-link safety net automatically:
        //   1. `wp_unique_post_slug()` ensures uniqueness across all
        //      product rows so we don't collide with another item.
        //   2. `wp_insert_post_data` records the previous post_name in
        //      the `_wp_old_slug` postmeta array, and WP's 404 fallback
        //      `wp_old_slug_redirect()` translates a hit on the old URL
        //      into a 301 to the new URL. So existing inbound links
        //      (Google index, ads, social shares) keep working.
        //
        // Original-slug snapshot is saved for EVERY rewritten product —
        // not just the ones whose slug actually changes. Two reasons:
        //   - Postmeta surface stays consistent across the catalogue
        //     (every rewritten row has the four `_cmc_original_*`
        //     markers, so audit + revert are uniform).
        //   - Rare but real: a future code change might re-derive
        //     slugs differently; having the original on every row
        //     means revert always has something to put back.
        // We use `metadata_exists()` instead of an empty-string check
        // because a brand-new product with `post_name = ''` would
        // otherwise look indistinguishable from "never saved" on the
        // next pass.
        $existing_slug = (string) $post->post_name;
        if ( ! metadata_exists( 'post', $product_id, self::META_ORIGINAL_SLUG ) ) {
            add_post_meta( $product_id, self::META_ORIGINAL_SLUG, $existing_slug, true );
        }

        $desired_slug = sanitize_title( $title_after );
        if ( $desired_slug !== '' && $desired_slug !== $existing_slug ) {
            $unique = wp_unique_post_slug(
                $desired_slug,
                $product_id,
                (string) $post->post_status,
                'product',
                (int) $post->post_parent
            );
            $update['post_name'] = $unique;
        }

        wp_update_post( $update );
        update_post_meta( $product_id, self::META_REWRITTEN_AT, time() );

        // Warning marker. Set when the saved title still tripped HARD
        // or SOFT reasons after retry — lets the admin UI surface
        // "needs review" without blocking the catalogue. Severity =
        // 'hard' for brand-leak / ALL-CAPS / oversize (review urgent),
        // 'soft' for Amazon-shape patterns (review optional). Cleared
        // when the title is clean so a re-run removes stale warnings.
        if ( $warning_severity !== '' ) {
            update_post_meta( $product_id, self::META_VALIDATION_WARNING, [
                'at'       => time(),
                'severity' => $warning_severity,
                'reasons'  => $warning_reasons_all,
                'title'    => $title_after,
                'attempts' => $max_attempts,
            ] );
        } else {
            delete_post_meta( $product_id, self::META_VALIDATION_WARNING );
        }
        // Clear any stale processing-error marker from a previous run
        // — the title was saved, the product is no longer "errored".
        delete_post_meta( $product_id, self::META_PROCESSING_ERROR );

        clean_post_cache( $product_id );

        return [
            'status'      => ( $warning_severity === '' ? 'ok' : 'ok_with_warning' ),
            'severity'    => $warning_severity,    // '' / 'soft' / 'hard'
            'before'      => $title_before,
            'after'       => $title_after,
            'desc_status' => $desc_status, // 'rewritten' | 'placeholder' — original is never kept
            'warning'     => $warning_reasons_all,
        ];
    }

    /**
     * Restore originals for the next slice of rewritten products.
     *
     * @return array{processed:int, reverted:int, remaining:int, done:bool}
     */
    public static function revert_batch( int $limit = 50 ): array {
        global $wpdb;

        $limit = max( 1, min( 200, $limit ) );

        $ids = (array) $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT p.ID
               FROM {$wpdb->posts} p
               INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                                              AND pm.meta_key = %s
              WHERE p.post_type   = 'product'
                AND p.post_status NOT IN ('auto-draft','trash')
              ORDER BY p.ID ASC
              LIMIT %d",
            self::META_REWRITTEN_AT,
            $limit
        ) );

        $reverted = 0;
        foreach ( $ids as $id ) {
            $id      = (int) $id;
            $update  = [ 'ID' => $id ];
            $touched = false;

            // metadata_exists() lets us tell apart "snapshot not taken"
            // from "snapshot taken with empty value" — both return ''
            // through get_post_meta() on its own. Restoring an empty
            // string is a legitimate revert (the row genuinely had
            // post_name='' or post_content='' before the rewrite).
            if ( metadata_exists( 'post', $id, self::META_ORIGINAL ) ) {
                $update['post_title'] = (string) get_post_meta( $id, self::META_ORIGINAL, true );
                $touched = true;
            }
            if ( metadata_exists( 'post', $id, self::META_ORIGINAL_DESC ) ) {
                $update['post_content'] = (string) get_post_meta( $id, self::META_ORIGINAL_DESC, true );
                $touched = true;
            }
            // Slug revert. WP will once again append the rewrite slug
            // to `_wp_old_slug` so EVERY slug this product has ever
            // had ends up 301-ing back to the restored original.
            if ( metadata_exists( 'post', $id, self::META_ORIGINAL_SLUG ) ) {
                $update['post_name'] = (string) get_post_meta( $id, self::META_ORIGINAL_SLUG, true );
                $touched = true;
            }

            if ( $touched ) {
                wp_update_post( $update );
                $reverted++;
            }
            // Always clear the markers so the row leaves the "rewritten"
            // bucket — even when the snapshots were missing (legacy row
            // from before the metadata_exists() fix).
            delete_post_meta( $id, self::META_REWRITTEN_AT );
            delete_post_meta( $id, self::META_ORIGINAL );
            delete_post_meta( $id, self::META_ORIGINAL_DESC );
            delete_post_meta( $id, self::META_ORIGINAL_SLUG );
            clean_post_cache( $id );
        }

        $remaining = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
               FROM {$wpdb->posts} p
               INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                                              AND pm.meta_key = %s
              WHERE p.post_type   = 'product'
                AND p.post_status NOT IN ('auto-draft','trash')",
            self::META_REWRITTEN_AT
        ) );

        return [
            'processed' => count( $ids ),
            'reverted'  => $reverted,
            'remaining' => $remaining,
            'done'      => $remaining === 0,
        ];
    }

    // ---------------- Internals ----------------

    private static function count_remaining(): int {
        global $wpdb;
        // Mirror rewrite_batch()'s eligibility filter: only the
        // rewritten-at marker removes a product from the queue.
        // Processing-error markers are auto-cleared at the start of
        // every batch so they don't block legitimate retries.
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
              LEFT JOIN {$wpdb->postmeta} pm_done ON pm_done.post_id = p.ID
                                                 AND pm_done.meta_key = %s
              WHERE p.post_type   = 'product'
                AND p.post_status NOT IN ('auto-draft','trash')
                AND pm_done.meta_id IS NULL",
            self::META_REWRITTEN_AT
        ) );
    }

    /**
     * Build the prompt + run it through the configured AI provider.
     * Uses tight settings (low temperature, capped tokens) because the
     * task is rule-following, not creativity.
     */
    private static function ai_rewrite( string $original_title, string $original_description = '', string $retry_feedback = '' ): string {
        $protected = self::protected_brands_csv();
        $tmin      = (int) self::TARGET_MIN;
        $tmax      = (int) self::TARGET_MAX;

        // Trim source description to keep the prompt under model token
        // limits — Amazon scrapes can exceed 5000 chars and we only
        // need enough context for the AI to identify product type +
        // attributes. Strip HTML first so the model sees clean text,
        // not div/span noise.
        $desc_source = trim( wp_strip_all_tags( $original_description ) );
        if ( function_exists( 'mb_substr' ) ) {
            $desc_source = mb_substr( $desc_source, 0, 1500 );
        } else {
            $desc_source = substr( $desc_source, 0, 1500 );
        }
        if ( $desc_source === '' ) {
            $desc_source = '(no description provided — infer from title)';
        }

        // STRATEGY: don't ask the model to "rewrite" the title — that
        // pulls it into paraphrasing the source, which keeps brand-
        // adjacent vocabulary (model names, character names, licensing
        // mentions) intact. Instead, frame the task as a 2-step
        // EXTRACT + COMPOSE flow:
        //   Step 1 — read the source ONLY to identify what the product
        //            actually IS (product type) and 1–3 neutral
        //            attributes (material, colour, size, qty).
        //   Step 2 — compose a fresh title from the canonical product
        //            type + those attributes, without echoing source
        //            wording.
        //
        // This is the only reliable way to strip sneaky brand mentions
        // we can't enumerate exhaustively — model numbers
        // ("Air Max 90", "iPhone 15 Pro"), character names ("Mickey",
        // "Pokémon", "Batman"), licensed-style suffixes ("Disney-
        // licensed", "Marvel-inspired"), and celebrity / designer
        // signature lines all disappear naturally because we never ask
        // the model to keep them.
        //
        // The few-shot examples teach the model the exact "simple,
        // generic, no brand, no model, no licensing" voice we want.
        // Output stays compliance-first, not catchy.
        $max_words = (int) self::MAX_WORDS;
        $prompt = "You produce GMC-compliant e-commerce product copy. The ONLY goal is to pass Google Merchant Center review — output does NOT need to be catchy, SEO-rich, or close in meaning to the source. Plain and generic is good.\n\n"
            . "TASK\n"
            . "Read the SOURCE only to identify what the product IS (generic product type) plus ONE primary attribute (material OR colour OR size OR intended user — pick the single most defining one, do NOT stack multiple). Then COMPOSE BRAND-NEW copy from scratch. Do NOT paraphrase the source — invent fresh, generic, descriptive text. Sneaky brand mentions in the source (model numbers, character names, licensing wording) MUST disappear in your output.\n\n"
            . "VOICE — STORE CATALOGUE, NOT MARKETPLACE LISTING\n"
            . "Write titles the way a real online store (Nordstrom, Allbirds, Crate & Barrel, IKEA) names a product on its own catalogue page — NOT the way Amazon / eBay / supplier portals stack keywords for SEO. A GMC reviewer who sees an attribute-stacked title flags it as \"still looks like a supplier listing\" even when no specific brand words are present. The SHAPE of the title is the giveaway. Imagine your title sitting alone on the product page of a real store — short, clean, ONE attribute.\n\n"
            . "OUTPUT FORMAT — return EXACTLY one valid JSON object with two keys, nothing else:\n"
            . "{\n"
            . "  \"title\": \"...\",\n"
            . "  \"description\": \"...\"\n"
            . "}\n"
            . "No markdown fences, no \"Output:\", no commentary before or after the JSON.\n\n"
            . "TITLE FIELD\n"
            . "Format: <One primary attribute> <Generic product type>   — aim for 3 to {$max_words} words total, {$tmin}–{$tmax} characters. SHORTER is BETTER — a clean 3-word title (\"Plug-in Pest Repellent\", \"12-Inch Plush Toy\", \"Wool Throw Blanket\") is the ideal target. Don’t pad with extra attributes just to hit a length number. The only HARD floor is {$tmin} characters (anything below is a fragment); aim above that comfortably, but don’t exceed {$tmax}.\n"
            . "Examples of the right shape: \"Men's Leather Running Shoe\", \"Black Plug-in Pest Repellent\", \"Round Wall Mirror\", \"12-Inch Plush Toy\", \"Ceramic Coffee Mug\", \"Wool Throw Blanket\".\n"
            . "STRICTLY BANNED shape patterns (these are the marketplace tells GMC reviewers flag):\n"
            . "  • Stacked attributes — \"Leather Upper Black White Men\" (4 attrs stacked). Pick ONE.\n"
            . "  • Pack/Set count suffixes — \"1 Pack\", \"2 Pack\", \"Single Pack\", \"Set of 3\", \"Pack of 5\". Drop them entirely.\n"
            . "  • Dash separators — \"Running Shoe - Black - Leather - Men\". Never use \" - \" in a title.\n"
            . "  • \"for X\" attachments — \"Pest Repellent for Home Office Warehouse\". Use-cases belong in the description, not the title.\n"
            . "  • Two colours back-to-back — \"Black White Men\", \"Red Blue Set\". Pick ONE colour.\n"
            . "  • Parenthetical content — \"(Gift for Mom)\", \"(2024 Edition)\", \"(Set of 4)\". Strip parens entirely.\n"
            . "  • Comma-separated attribute lists — \"Shoe, Leather, Black, Men\". Use a single phrase.\n"
            . "Word count cap: HARD max {$max_words} words. If you draft 8+ words, drop attributes until it fits.\n\n"
            . "DESCRIPTION FIELD — PLAIN TEXT, NO HTML.\n"
            . "MANDATORY: this field MUST be non-empty and MUST contain real content describing the product. Returning an empty string, a placeholder, or a single short sentence is a HARD failure — the downstream pipeline will overwrite an empty description with a generic placeholder you do NOT want surfaced to GMC reviewers. Always produce the full 3-paragraph body below; if specific facts are missing, infer generic-but-plausible context from the product type and stay factual.\n"
            . "Output exactly THREE paragraphs separated by blank lines. Target 120–200 words total (about 600–1200 characters). Hard max 2500 characters.\n"
            . "Paragraph 1 — INTRO (2–3 sentences): what the product is, the main use case, and a brief design / quality observation. Plain prose, no labels.\n"
            . "Paragraph 2 — DETAILS (2–3 sentences): explain key features and benefits in natural prose. Cover how the product is used, what makes it suitable for the stated purpose, any practical notes (fit, handling, performance). Stay factual — describe what's there, do not invent specs.\n"
            . "Paragraph 3 — FEATURE LINE (single line, mandatory): inline list of 3–5 concrete attributes with labels. Each entry follows the pattern \"<Label>: <short value>.\" — use these labels in this order (drop any you don't have a real value for; do NOT invent attributes):\n"
            . "  Material: ... Size: ... Key feature: ... Suitable for: ...\n"
            . "ABSOLUTELY FORBIDDEN:\n"
            . "  • Any HTML tag — no <p>, <ul>, <li>, <strong>, <em>, <br>, <div>, <span>, etc. Output pure plain text.\n"
            . "  • Markdown — no **bold**, no *italics*, no - bullet lists, no # headings.\n"
            . "  • A fourth paragraph, more than 3 paragraphs.\n"
            . "  • Care / shipping / warranty claims, store policy, customer reviews, testimonials, affiliate cross-sells, external URLs, bare email addresses.\n"
            . "  • Marketing fluff: \"premium\", \"top-quality\", \"100% guaranteed\", \"perfect for\", \"must-have\", \"world-class\".\n"
            . "wpautop on the front-end wraps each paragraph in <p> automatically — you do NOT need to add any HTML.\n\n"
            . "HARD RULES — apply to BOTH title AND description:\n"
            . "1. Title Case for the title. Description in normal sentence case. No ALL-CAPS words longer than 4 chars (USB / LED / HDMI are fine).\n"
            . "2. NO emoji, ★, ☆, ❤, ▶, decorative symbols, repeated punctuation (!!, ??, ...), exclamation marks.\n"
            . "3. NO promotional / superlative words anywhere: sale, free, deal, best, premium, ultimate, perfect, top, hot, amazing, must-have, bestseller, no.1, top-rated, high quality, 100%, guaranteed, exclusive, limited time.\n"
            . "4. NO marketplace / fulfilment identifiers: Amazon, Prime, Amazon's Choice, eBay, Etsy, Walmart, Target, Costco, Shein, Temu, Editor's Pick.\n"
            . "5. NO parenthetical SEO bursts in title (\"(Gift for Mom Sister Friend Christmas)\", \"(2024 New Edition)\").\n"
            . "6. NO PROTECTED BRAND NAMES anywhere in title or description. The following are strictly forbidden — if any appear in the source, drop them and use a generic descriptor:\n"
            . "   {$protected}\n"
            . "7. NO MODEL NAMES / NUMBERS that imply a specific branded product (Air Max 90, iPhone 15 Pro, Galaxy S24, PS5, RTX 4090, AirPods Pro, Kindle Paperwhite, Surface Pro, MacBook Air). Use the generic product type instead.\n"
            . "8. NO CHARACTER / FRANCHISE / MOVIE / GAME / ANIME NAMES anywhere (Mickey, Donald, Disney, Marvel, Avengers, Spider-Man, Batman, Star Wars, Harry Potter, Pokémon, Pikachu, Hello Kitty, Sanrio, Naruto, One Piece, Minecraft, Fortnite, Roblox, Mario, Sonic, Frozen, Paw Patrol). Use neutral descriptors (\"plush toy\", \"figurine\", \"sticker set\", \"trading card holder\").\n"
            . "9. NO LICENSING / SIGNATURE PATTERNS anywhere: drop \"officially licensed\", \"licensed by ...\", \"X-style\", \"X-inspired\", \"X-themed\", \"X-edition\", \"signature collection\", \"designer\", \"co-branded\".\n"
            . "10. NO CELEBRITY / DESIGNER / ATHLETE NAMES anywhere (Taylor Swift, Beyoncé, Michael Jordan, Kobe, Travis Scott, Yeezy, Jordan, Versace, Dior, Gucci).\n"
            . "11. NO TECHNICAL / SCIENTIFIC / COMPOUND ADJECTIVES that read like proprietary product names — even when they're real English words. The following are FORBIDDEN as descriptors because manual reviewers misread them as brand tokens: Ultrasonic, Hypersonic, Aerosonic, Subsonic, Cryotech, Cryogenic, Bioflex, Smartpro, ProMax, UltraPro, NanoTech, MegaSeal, HyperFlex, TurboMax, MaxBoost, PowerSync, BioSync, EcoSmart, SmartShield, Aerodynamic-X, Neuro-anything. Replace with plain everyday words (Electronic, High-Frequency, Plug-in, Cooling, Flexible, Smart) — when no plain replacement fits, just DROP the adjective entirely and use the bare product type. Examples: \"Ultrasonic Pest Repellent\" → \"Electronic Pest Repellent\" or \"Plug-in Pest Repellent\" or just \"Pest Repellent\". \"NanoTech Phone Case\" → \"Slim Phone Case\". \"HyperFlex Yoga Mat\" → \"Flexible Yoga Mat\". Rule of thumb: if a word LOOKS branded but isn't in any English dictionary you'd use in everyday conversation, drop it.\n"
            . "12. Description must NOT include affiliate cross-sells (\"pairs perfectly with our other products...\"), customer reviews, testimonials, store policy text, shipping promises, or warranty claims.\n"
            . "13. Description must NOT include external URLs or bare email addresses.\n\n"
            . "EXAMPLES — study these carefully. Notice the SHAPE: 3–5 words, ONE primary attribute, no pack count, no dash, no parens, no \"for X\" tail. Your JSON output must match this voice:\n\n"
            . "INPUT TITLE: Nike Air Max 90 Men's Athletic Running Shoe (Black/White) - Genuine Leather Upper - Sport & Casual Wear\n"
            . "INPUT DESCRIPTION (excerpt): Nike's iconic Air Max 90 silhouette returns with premium genuine leather upper... Officially licensed. Pairs with Nike Air Force 1.\n"
            . "OUTPUT:\n"
            . "{\"title\":\"Men's Leather Running Shoe\",\"description\":\"This men's running shoe pairs a leather upper with a cushioned foam midsole, designed for daily training and casual everyday wear. The construction balances support and flexibility, making it suited to both light running sessions and longer hours on the feet.\\n\\nA standard lace-up closure ensures a secure fit across different foot shapes, while the foam midsole absorbs impact during walking and running. The leather upper resists scuffs and wears in over time, settling into a natural look that works for both gym sessions and weekend outings. Easy to clean with a soft cloth — no special care required for daily use.\\n\\nMaterial: genuine leather upper, foam midsole. Colour: black with white accents. Closure: standard lace-up. Suitable for: light running, gym workouts, and everyday wear.\"}\n\n"
            . "INPUT TITLE: Disney Officially Licensed Mickey Mouse Plush Toy 12 inch (Perfect Gift for Kids)\n"
            . "INPUT DESCRIPTION (excerpt): This officially licensed Disney Mickey Mouse plush is the perfect gift! Bestseller 2024. ★★★★★\n"
            . "OUTPUT:\n"
            . "{\"title\":\"12-Inch Plush Toy\",\"description\":\"A 12-inch soft plush toy designed in a friendly cartoon-character style, suited to nursery decor and everyday play. The plush is hand-sized for younger children, making it easy to carry, cuddle, and display on shelves or beds.\\n\\nThe exterior uses a short-pile plush fabric with a soft texture, while the interior is filled with polyester fibre for a gentle, even feel when squeezed. Care is straightforward: spot clean only — avoid machine washing to preserve shape and surface finish. Stitched with attention to seams and finished with non-toxic materials suitable for children's play.\\n\\nMaterial: short-pile plush exterior with polyester fibre filling. Size: approximately 12 inches tall. Care: spot clean only. Suitable for: children aged 3 and up, nursery decor, and play.\"}\n\n"
            . "INPUT TITLE: Ultrasonic Pest Repellent Plug-in, Electronic Mouse Traps & Insect Repellent, Pest Control for Rodent, Roach, Ant, Squirrel, Spider, Bugs, Mouse, Rat, Bat for Home, Office, Warehouse Black 1 Pack\n"
            . "INPUT DESCRIPTION (excerpt): Premium ultrasonic technology drives away rodents and insects safely without chemicals.\n"
            . "OUTPUT:\n"
            . "{\"title\":\"Plug-in Pest Repellent\",\"description\":\"This plug-in pest repellent is designed for indoor use in homes, offices, and warehouse spaces, providing a chemical-free way to discourage common indoor pests. The unit runs quietly during normal use, making it suitable for areas where a louder pest-control method would be disruptive.\\n\\nInstallation is straightforward: plug into any standard wall outlet — no setup, wiring, or additional accessories required. The repellent targets rodents and common insects and operates continuously while plugged in. Coverage is a single-room indoor area; larger spaces benefit from adding more units across rooms. Easy to relocate when needed.\\n\\nType: electronic plug-in unit. Coverage: single-room indoor area. Target pests: rodents and common insects. Colour: black. Suitable for: home, office, and warehouse interiors.\"}\n\n"
            . "Notice in example 1 how 8 source attributes (model name, men, athletic, running, black, white, leather, sport) collapsed to JUST 3 words: \"Men's Leather Running Shoe\". The model number disappeared (Air Max 90 → just \"Running Shoe\"). \"Ultrasonic\" dropped too — it READS like a brand even though it's an English word. Attribute stacks like \"Black White Men\" / \"Black Single Pack\" are FORBIDDEN — pick ONE attribute and drop the rest.\n\n"
            . $retry_feedback
            . "Now produce a single compliant JSON object for the SOURCE below. Output ONLY the JSON.\n\n"
            . "SOURCE TITLE: " . $original_title . "\n"
            . "SOURCE DESCRIPTION: " . $desc_source;

        // CMC_AI_Client is the configured wrapper — uses the global
        // `temperature` + `max_tokens` from Settings. We don't override
        // here because the user may have tuned them for their flow;
        // the few-shot examples + tight rule list keep output stable
        // even at 0.7 temperature.
        return CMC_AI_Client::generate( $prompt );
    }

    private static function protected_brands_csv(): string {
        // Pull from CMC_Schema's curated list so the title rewriter and
        // the schema brand-blocker stay in lock-step.
        if ( ! class_exists( 'CMC_Schema' ) ) {
            return 'Nike, Adidas, Apple, Samsung, Sony, IKEA, Amazon, Disney';
        }
        $reflection = new ReflectionClass( 'CMC_Schema' );
        $list       = [];
        if ( $reflection->hasConstant( 'PROTECTED_BRANDS' ) ) {
            $list = (array) $reflection->getConstant( 'PROTECTED_BRANDS' );
        }
        $list = (array) apply_filters( 'cmc_product_schema_protected_brands', $list );
        // Trim whitespace + cap to ~80 entries so the prompt stays
        // under the model's context window with room for the source
        // title.
        $list = array_slice( array_filter( array_map( 'trim', $list ) ), 0, 80 );
        return implode( ', ', $list );
    }

    /**
     * Pull the first balanced JSON object out of the raw model
     * response. Models wrap output in markdown fences ```json ... ```
     * inconsistently; some prepend "Output:" or "Here's the JSON:";
     * a few add commentary after the closing brace. We locate the
     * first `{` and walk forward until the brace stack returns to
     * zero, then `json_decode()` that slice.
     *
     * Returns the decoded associative array on success, or null when
     * the response contains no parseable JSON object.
     *
     * @return array<string,mixed>|null
     */
    private static function parse_json_output( string $raw ): ?array {
        if ( $raw === '' ) { return null; }

        // Strip ```json ... ``` fences first so the brace walk doesn't
        // see literal backticks as part of the payload.
        $stripped = preg_replace( '/```(?:json)?\s*(.*?)```/s', '$1', $raw );
        $stripped = is_string( $stripped ) ? $stripped : $raw;

        $first = strpos( $stripped, '{' );
        if ( $first === false ) { return null; }

        $depth     = 0;
        $in_string = false;
        $escape    = false;
        $end       = -1;
        $len       = strlen( $stripped );
        for ( $i = $first; $i < $len; $i++ ) {
            $ch = $stripped[ $i ];
            if ( $escape )            { $escape    = false;        continue; }
            if ( $ch === '\\' && $in_string ) { $escape = true;    continue; }
            if ( $ch === '"' )        { $in_string = ! $in_string; continue; }
            if ( $in_string )         { continue; }
            if ( $ch === '{' )        { $depth++; }
            elseif ( $ch === '}' )    { $depth--; if ( $depth === 0 ) { $end = $i; break; } }
        }
        if ( $end < 0 ) { return null; }

        $json = substr( $stripped, $first, $end - $first + 1 );
        // Repair raw control chars inside string values — common when the
        // model emits a literal newline in the description value instead
        // of the JSON escape "\n". PHP json_decode rejects raw newlines
        // / tabs / carriage returns inside string values per RFC 8259;
        // pre-escaping them lets the otherwise-well-formed payload
        // round-trip cleanly.
        $json = self::repair_json_control_chars( $json );
        $data = json_decode( $json, true );
        return is_array( $data ) ? $data : null;
    }

    /**
     * Walk a JSON payload and escape raw control characters that occur
     * INSIDE quoted string values. Outside strings the chars pass
     * through unchanged (so newlines between key/value pairs stay
     * valid JSON whitespace).
     */
    private static function repair_json_control_chars( string $json ): string {
        $out       = '';
        $in_string = false;
        $escape    = false;
        $len       = strlen( $json );
        for ( $i = 0; $i < $len; $i++ ) {
            $ch = $json[ $i ];
            if ( $escape ) {
                $out   .= $ch;
                $escape = false;
                continue;
            }
            if ( $in_string && $ch === '\\' ) {
                $out   .= $ch;
                $escape = true;
                continue;
            }
            if ( $ch === '"' ) {
                $in_string = ! $in_string;
                $out      .= $ch;
                continue;
            }
            if ( $in_string ) {
                if ( $ch === "\n" ) { $out .= '\\n'; continue; }
                if ( $ch === "\r" ) { $out .= '\\r'; continue; }
                if ( $ch === "\t" ) { $out .= '\\t'; continue; }
            }
            $out .= $ch;
        }
        return $out;
    }

    /**
     * Sanitise an AI-generated description into PLAIN TEXT.
     *
     * The model is now instructed to output plain text with two
     * paragraphs separated by a blank line. This method:
     *   - strips any HTML the model emits anyway (it occasionally
     *     wraps in <p>...</p> out of habit) via wp_strip_all_tags();
     *   - decodes over-escaped slashes / entities;
     *   - removes "Description:" labels and ``` fences emitted
     *     inside the JSON value;
     *   - collapses spaces / tabs to single spaces but preserves
     *     paragraph breaks (double newline) so wpautop() can wrap
     *     each paragraph in <p> on the front-end.
     */
    private static function sanitise_description( string $raw ): string {
        if ( $raw === '' ) { return ''; }
        $s = trim( $raw );

        // Decode double-escaped slashes / entities the model occasionally
        // emits inside JSON string values.
        $s = str_replace( [ '<\\/', '\\/' ], [ '</', '/' ], $s );

        // Strip wrapping label / code fence that some models sneak in.
        $s = preg_replace( '/^(description)\s*:\s*/i', '', $s );
        $s = preg_replace( '/```(?:html|text)?\s*(.*?)```/s', '$1', $s );

        // Strip ALL HTML tags. Any <p>, <ul>, <li>, <strong> etc. the
        // model emits despite the prompt is dropped — wpautop() on the
        // front-end re-adds paragraph wrapping cleanly.
        $s = (string) wp_strip_all_tags( $s, false );

        // Decode HTML entities so &amp; doesn't show literally.
        $s = html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

        // Normalise newlines: collapse Windows / Mac line-endings to \n.
        $s = str_replace( [ "\r\n", "\r" ], "\n", $s );

        // Collapse runs of spaces / tabs (but NOT newlines — paragraph
        // breaks must survive).
        $s = preg_replace( '/[ \t]+/u', ' ', $s );

        // Collapse 3+ consecutive newlines down to exactly 2 (one
        // paragraph break). 1 newline stays as a soft line break.
        $s = preg_replace( "/\n{3,}/", "\n\n", $s );

        // Trim each line + global trim.
        $s = preg_replace( '/[ \t]*\n[ \t]*/', "\n", $s );

        return trim( $s );
    }

    /**
     * Soft quality scan over a saved AI description. Returns a list
     * of human-readable warning strings — empty list = clean. This
     * is informational only: the description is saved regardless of
     * what this function returns. The output is persisted in
     * `META_DESC_QUALITY_WARNING` so admin scans can surface rows
     * that may want manual review.
     *
     * @return list<string>
     */
    private static function description_quality_warnings( string $text ): array {
        $warnings = [];
        if ( $text === '' ) {
            return $warnings;
        }
        $len = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
        if ( $len < self::DESC_MIN_TEXT_LEN ) {
            $warnings[] = sprintf( 'unusually short (len=%d, target ≥%d)', $len, self::DESC_MIN_TEXT_LEN );
        }
        if ( $len > self::DESC_MAX_TEXT_LEN ) {
            $warnings[] = sprintf( 'unusually long (len=%d, target ≤%d)', $len, self::DESC_MAX_TEXT_LEN );
        }
        if ( self::contains_protected_brand( $text ) ) {
            $warnings[] = 'contains protected brand — review for GMC compliance';
        }
        if ( self::contains_sneaky_brand_signal( $text ) ) {
            $warnings[] = 'contains sneaky brand signal — review for GMC compliance';
        }
        return $warnings;
    }

    /**
     * Description validator — plain-text only.
     *
     * Soft-fail policy mirrors the title pipeline:
     *   - empty / below DESC_MIN_TEXT_LEN          → reject (fragment)
     *   - above DESC_MAX_TEXT_LEN                  → reject (out of band)
     *   - brand leak / sneaky brand signal         → reject (GMC risk)
     *   - else                                     → accept (in target or close)
     *
     * Anything inside [DESC_TARGET_MIN, DESC_TARGET_MAX] is the ideal
     * band but we don't reject titles outside that range as long as the
     * hard bounds and brand checks pass — losing a slightly long-or-short
     * description is worse than keeping the supplier original.
     */
    private static function passes_description_validation( string $text ): bool {
        if ( $text === '' ) { return false; }

        $len = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
        if ( $len < self::DESC_MIN_TEXT_LEN ) { return false; }
        if ( $len > self::DESC_MAX_TEXT_LEN ) { return false; }

        if ( self::contains_protected_brand( $text ) )      { return false; }
        if ( self::contains_sneaky_brand_signal( $text ) )  { return false; }

        return true;
    }

    /**
     * Strip the model's common output-wrapper patterns: surrounding
     * quotes, leading "Title:" / "Output:" labels, markdown bold or
     * code fences, trailing periods.
     */
    private static function sanitise_output( string $raw ): string {
        $s = trim( $raw );
        // Drop ```fences```, inline `code`, **bold**.
        $s = preg_replace( '/```[a-zA-Z]*\n?(.*?)```/s', '$1', $s );
        $s = trim( $s, " \t\n\r\0\x0B`*\"'“”‘’" );
        // Drop "Title:" / "Rewritten:" / "Output:" prefix labels.
        $s = preg_replace( '/^(title|rewritten title|rewritten|output|new title)\s*:\s*/i', '', $s );
        // Collapse newlines — output must be one line.
        $s = preg_replace( '/\s+/', ' ', $s );
        $s = trim( $s );
        // Strip trailing period — product titles don't take one.
        $s = rtrim( $s, '.' );
        return $s;
    }

    private static function passes_validation( string $title ): bool {
        return self::validation_reasons( $title ) === [];
    }

    /**
     * Return the list of human-readable reasons a title fails the
     * compliance gate. Empty list = title passes. Used both by the
     * boolean `passes_validation()` and by `rewrite_product()` so the
     * error message surfaced in the UI tells the operator WHICH rule
     * tripped — the old "Title validation failed (len=46,
     * contains_protected=no)" line didn't say which check rejected
     * the row.
     *
     * @return list<string>
     */
    private static function validation_reasons( string $title ): array {
        $tiered = self::validation_reasons_tiered( $title );
        return array_merge( $tiered['hard'], $tiered['soft'] );
    }

    /**
     * Two-tier validation. HARD reasons are non-negotiable (protected
     * brand leakage, ALL-CAPS bursts, hard length / word ceilings) and
     * mean the AI output cannot be saved as-is. SOFT reasons are minor
     * Amazon-shape patterns (slightly long, "for X" tail, two colours
     * stacked, parenthetical content) — caller may save the title with
     * a warning flag rather than retrying forever.
     *
     * Returns `[ 'hard' => list<string>, 'soft' => list<string> ]`.
     */
    private static function validation_reasons_tiered( string $title ): array {
        $hard = [];
        $soft = [];

        if ( $title === '' ) {
            return [ 'hard' => [ 'empty output' ], 'soft' => [] ];
        }

        $len = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );

        // Hard length / word ceilings — output unsavable beyond these.
        if ( $len > self::HARD_MAX_LEN ) {
            $hard[] = sprintf( 'far too long (len=%d, hard-max=%d) — drop most attributes', $len, self::HARD_MAX_LEN );
        } elseif ( $len > self::MAX_LEN ) {
            $soft[] = sprintf( 'slightly long (len=%d, soft-max=%d) — Amazon-shaped, prefer to drop 1–2 words', $len, self::MAX_LEN );
        }
        if ( $len < self::MIN_LEN ) {
            // Title-too-short is a hard issue — likely the model
            // returned a fragment or just a colour. Keep retrying.
            $hard[] = sprintf( 'too short (len=%d, min=%d)', $len, self::MIN_LEN );
        }

        $word_count = count( preg_split( '/\s+/u', trim( $title ) ) ?: [] );
        if ( $word_count > self::HARD_MAX_WORDS ) {
            $hard[] = sprintf(
                'far too many words (got=%d, hard-max=%d) — output is essentially a marketplace listing',
                $word_count,
                self::HARD_MAX_WORDS
            );
        } elseif ( $word_count > self::MAX_WORDS ) {
            $soft[] = sprintf(
                'slightly too many words (got=%d, soft-max=%d) — try to drop 1–2 stacked attributes',
                $word_count,
                self::MAX_WORDS
            );
        }

        // Brand leakage is ALWAYS hard — these patterns put the page
        // at GMC Misrepresentation risk and must not be saved.
        if ( self::contains_protected_brand( $title ) ) {
            $hard[] = 'contains protected brand';
        }
        if ( preg_match_all( '/\b[A-Z]{5,}\b/', $title, $m ) ) {
            $hard[] = 'ALL-CAPS word found: ' . implode( ', ', array_unique( $m[0] ) );
        }
        $sneaky = self::sneaky_brand_signal_reason( $title );
        if ( $sneaky !== '' ) {
            $hard[] = $sneaky;
        }

        // Amazon-shape patterns are SOFT — annoying but the title is
        // still better than the original supplier copy. Worth saving
        // with a warning flag rather than failing a product entirely.
        $amazon_shape = self::amazon_shape_reason( $title );
        if ( $amazon_shape !== '' ) {
            $soft[] = $amazon_shape;
        }

        return [ 'hard' => $hard, 'soft' => $soft ];
    }

    /**
     * Detect "Amazon listing shape" patterns in a title — composition
     * tells the model copies from marketplace listings even when no
     * specific brand word is present. Returns a human-readable reason
     * string when a pattern matches, or '' when the title is shape-clean.
     */
    private static function amazon_shape_reason( string $title ): string {
        $t = ' ' . strtolower( $title ) . ' ';

        // Numeric pack/set suffixes — classic marketplace SEO tail.
        // Matches: " 1 Pack", " 2 Pack", " 12 Pack ", " 3-Pack",
        //          " Single Pack", " Set of 3", " Pack of 5".
        if ( preg_match( '/\b(?:\d+\s*[-]?\s*pack|single\s+pack|set\s+of\s+\d+|pack\s+of\s+\d+)\b/u', $t ) ) {
            return 'Amazon-shape: pack/set suffix detected (e.g. "1 Pack", "Set of 3") — drop the count suffix; a store title doesn\'t advertise quantity in the name';
        }

        // Dash-separated attribute chains — "X - Y - Z" or "X - Y" are
        // marketplace tells. A clean store title doesn\'t use " - " as a
        // separator between attributes.
        if ( substr_count( $t, ' - ' ) >= 1 ) {
            return 'Amazon-shape: contains " - " separator — replace with a single compact phrase, no dash chains';
        }

        // "for X" attachments — Amazon stuffs use-cases via " for Home",
        // " for Office", " for Kids". A store title doesn\'t enumerate
        // use-cases inline.
        if ( preg_match( '/\bfor\s+(home|office|kitchen|bedroom|bathroom|kids|adults|men|women|gift|gifts|christmas|birthday|wedding|party|outdoor|indoor|car)\b/u', $t ) ) {
            return 'Amazon-shape: "for <use-case>" attachment detected — store titles don\'t inline use-cases; drop it or move to description';
        }

        // Two or more colour words back-to-back ("Black White Men",
        // "Red Blue Green"). A store picks ONE colour.
        $colour_pat = '/\b(black|white|brown|grey|gray|silver|gold|red|blue|green|yellow|pink|purple|orange|navy|beige|cream|tan|ivory|charcoal|burgundy)\s+(black|white|brown|grey|gray|silver|gold|red|blue|green|yellow|pink|purple|orange|navy|beige|cream|tan|ivory|charcoal|burgundy)\b/u';
        if ( preg_match( $colour_pat, $t ) ) {
            return 'Amazon-shape: two colour words stacked — pick ONE colour, drop the rest';
        }

        // Comma-separated attribute lists ("Shoe, Leather, Black, Men").
        if ( substr_count( $title, ',' ) >= 2 ) {
            return 'Amazon-shape: 2+ commas — attribute list shape; rewrite as a single short phrase';
        }

        // Parenthetical SEO bursts — "(2024)", "(New Edition)",
        // "(Gift)", "(Set of 4)".
        if ( preg_match( '/\(([^)]{2,40})\)/u', $title ) ) {
            return 'Amazon-shape: parenthetical content in title — strip the parentheses and their content';
        }

        return '';
    }

    /**
     * Pattern-check for the categories of brand-adjacent vocabulary the
     * `PROTECTED_BRANDS` exact-match list can't catch:
     *
     *   - Promo / superlative words: sale, free, premium, bestseller…
     *   - Marketplace / fulfilment identifiers: Amazon, Prime, Choice…
     *   - Licensing patterns: "officially licensed", "X-inspired",
     *     "X-themed", "signature collection", "designer".
     *   - Famous franchise + character names: Mickey, Disney, Marvel,
     *     Pokémon, Star Wars, Harry Potter, Hello Kitty, Minecraft…
     *
     * Substring + word-boundary mix; case-insensitive. Anything
     * matching → reject the AI output and fall back to the original
     * title (the operator can revert + rerun if they want another go).
     */
    private static function contains_sneaky_brand_signal( string $title ): bool {
        return self::sneaky_brand_signal_reason( $title ) !== '';
    }

    /**
     * Return a specific reason string when the title trips one of the
     * sneaky-brand gates, or '' when it doesn't. The reason includes
     * the offending needle so the UI error log tells the operator
     * exactly which token to look for in the AI output.
     */
    private static function sneaky_brand_signal_reason( string $title ): string {
        // All four blocklists are now word-boundary matched (`\b…\b/iu`)
        // instead of plain `strpos()`. The old substring match produced
        // serious false positives: "Ultrasonic" hit franchise "sonic",
        // "Prime Time" hit marketplace "prime ", "Authority" hit
        // celebrity "thor" inside "authorithoritory" etc. The `/u`
        // Unicode flag lets `\b` work on accented forms like
        // "Pokémon" too. `preg_quote()` is mandatory because some
        // tokens contain regex metas (".", "#", "-").
        $matchers = [
            'promotional word'              => self::promo_list(),
            'marketplace identifier'        => self::marketplace_list(),
            'franchise / character'         => self::franchise_list(),
            'celebrity / designer name'     => self::celebrity_list(),
            'brand-like tech adjective'     => self::tech_adjective_list(),
            'site-banned word'              => self::user_banned_list(),
        ];
        foreach ( $matchers as $label => $needles ) {
            foreach ( $needles as $needle ) {
                $needle = trim( $needle );
                if ( $needle === '' ) { continue; }
                if ( preg_match( '/\b' . preg_quote( $needle, '/' ) . '\b/iu', $title ) ) {
                    return $label . ': "' . $needle . '"';
                }
            }
        }

        // Licensing / signature patterns.
        if ( preg_match( '/\b(licensed|signature|designer|co-branded)\b/i', $title, $m ) ) {
            return 'licensing pattern: "' . strtolower( $m[1] ) . '"';
        }

        // "X-style / X-themed / X-inspired / X-edition / X-licensed"
        // pattern. The old regex `\b\w+-(style|themed|...)\b` was too
        // wide — it rejected legitimate generic adjectives like
        // "vintage-style", "nature-themed", "bat-themed" (when the
        // product title genuinely was about bats). Tighten the gate
        // to ONLY reject when the prefix is a protected brand or a
        // known franchise / character (Disney-themed, Marvel-style,
        // Mickey-inspired). Generic adjective prefixes pass through.
        if ( preg_match_all( '/\b([a-z]+)\-(?:style|inspired|themed|edition|licensed)\b/i', $title, $m ) ) {
            foreach ( $m[1] as $prefix ) {
                $p = strtolower( $prefix );
                if ( self::contains_protected_brand( $p )
                    || in_array( $p, self::franchise_list(), true ) ) {
                    return 'brand "' . $p . '"-themed/style pattern';
                }
            }
        }

        return '';
    }

    /**
     * Promo / superlative blocklist. Words that imply marketing claims
     * or rankings GMC rejects on sight.
     *
     * @return list<string>
     */
    private static function promo_list(): array {
        return [
            'bestseller', 'best seller', 'best-seller', 'amazing',
            'must-have', 'must have', 'no.1', '#1', 'top-rated',
            'guaranteed', 'limited time', 'limited-time', 'official',
            'officially licensed', 'licensed by',
        ];
    }

    /**
     * Marketplace / fulfilment identifiers GMC rejects because they
     * imply a relationship with a third-party retailer.
     *
     * @return list<string>
     */
    private static function marketplace_list(): array {
        return [
            'amazon', "amazon's choice", 'prime',
            "editor's pick", 'walmart', 'costco', 'shein', 'temu',
        ];
    }

    /**
     * Single source of truth for the franchise / character / studio
     * blocklist. Used by both `sneaky_brand_signal_reason()` AND the
     * "X-themed/style" prefix check — keeping one list means the two
     * gates never disagree about whether "Mickey" is famous enough
     * to reject.
     *
     * "elsa" / "olaf" are deliberately short — relying on word-
     * boundary regex to avoid matching substrings like "Elsa-Maria"
     * or "Olafsson"; the `\bX\b` walker handles that.
     *
     * @return list<string>  All lowercase.
     */
    private static function franchise_list(): array {
        return [
            'mickey', 'minnie', 'donald duck', 'goofy', 'pluto',
            'disney', 'pixar', 'marvel', 'avengers', 'spider-man', 'spiderman',
            'iron man', 'captain america', 'thor', 'hulk', 'batman',
            'superman', 'wonder woman', 'star wars', 'darth vader',
            'yoda', 'jedi', 'sith', 'harry potter', 'hogwarts',
            'pokémon', 'pokemon', 'pikachu', 'hello kitty', 'sanrio',
            'naruto', 'sasuke', 'one piece', 'luffy', 'minecraft',
            'fortnite', 'roblox', 'mario', 'sonic', 'zelda',
            'simpsons', 'frozen', 'elsa', 'olaf',
            'paw patrol', 'peppa pig', 'bluey', 'cocomelon',
        ];
    }

    /**
     * Celebrity / athlete / fashion-designer blocklist.
     *
     * @return list<string>
     */
    private static function celebrity_list(): array {
        return [
            'taylor swift', 'beyonce', 'beyoncé', 'rihanna', 'kanye',
            'michael jordan', 'jordan', 'kobe', 'lebron', 'travis scott',
            'yeezy', 'versace', 'dior', 'gucci', 'fendi', 'balenciaga',
        ];
    }

    /**
     * Brand-like technical adjectives. These ARE real English words
     * (Ultrasonic = sound above 20 kHz; Cryogenic = relating to low
     * temperatures) but manual GMC reviewers — especially non-English
     * ones — read them as proprietary product names because they have
     * the compound-Latinate cadence of a brand. Safer to reject the
     * AI output and let the operator rerun until the model picks a
     * plain everyday descriptor.
     *
     * Matched as whole words (`\bX\b/iu` in the caller), so common
     * substrings like "Sonic" inside "Ultrasonic" are not the issue
     * here — the issue is "Ultrasonic" *itself* as a standalone word.
     *
     * @return list<string>
     */
    private static function tech_adjective_list(): array {
        return [
            'ultrasonic', 'hypersonic', 'aerosonic', 'subsonic',
            'cryotech', 'cryogenic', 'bioflex', 'biosync',
            'smartpro', 'smartshield', 'promax', 'ultrapro',
            'nanotech', 'megaseal', 'hyperflex', 'turbomax',
            'maxboost', 'powersync', 'ecosmart', 'aerodynamic-x',
            'neurosync', 'thermotech',
        ];
    }

    /**
     * Operator-extendable banned-word list. Drop additional tokens
     * here per site via the `cmc_title_rewriter_banned_words` filter
     * — useful when a niche-specific word keeps slipping through that
     * the curated blocklists don't catch.
     *
     * Example (in a site-specific must-use plugin):
     *
     *     add_filter( 'cmc_title_rewriter_banned_words', function( $list ) {
     *         $list[] = 'ultrasonic';   // local audit reviewer flags this
     *         $list[] = 'thermomatic';  // niche pseudo-brand seen on amazon
     *         return $list;
     *     } );
     *
     * @return list<string>  Operator-banned words, all lowercase.
     */
    private static function user_banned_list(): array {
        $list = (array) apply_filters( 'cmc_title_rewriter_banned_words', [] );
        $out  = [];
        foreach ( $list as $w ) {
            $w = trim( strtolower( (string) $w ) );
            if ( $w !== '' ) { $out[] = $w; }
        }
        return $out;
    }

    private static function contains_protected_brand( string $title ): bool {
        if ( ! class_exists( 'CMC_Schema' ) ) {
            return false;
        }
        $reflection = new ReflectionClass( 'CMC_Schema' );
        if ( ! $reflection->hasConstant( 'PROTECTED_BRANDS' ) ) {
            return false;
        }
        $list  = (array) $reflection->getConstant( 'PROTECTED_BRANDS' );
        $list  = (array) apply_filters( 'cmc_product_schema_protected_brands', $list );
        $lower = strtolower( $title );
        foreach ( $list as $brand ) {
            $brand = strtolower( trim( (string) $brand ) );
            if ( $brand === '' ) { continue; }
            if ( strpos( $lower, $brand ) !== false ) {
                return true;
            }
        }
        return false;
    }

}
