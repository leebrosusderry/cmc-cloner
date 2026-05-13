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

    /**
     * Title length band. Outside = treat as AI garbage and keep the
     * original.
     */
    private const MIN_LEN     = 30;
    private const MAX_LEN     = 140;
    private const TARGET_MIN  = 40;
    private const TARGET_MAX  = 100;

    /**
     * Description length band — measured AFTER stripping HTML tags so
     * "100–300 words ≈ 600–2000 chars of plain text" is what we
     * actually validate. The HTML wrapper (`<p>`, `<ul>`, `<li>` etc.)
     * adds maybe 100–300 chars on top — generous upper bound 3000 to
     * accommodate that.
     */
    private const DESC_MIN_TEXT_LEN = 200;
    private const DESC_MAX_HTML_LEN = 3000;

    /**
     * HTML tags allowed in the description output. Anything else is
     * stripped via wp_kses() — script/style/img/iframe/h1-h6/table
     * etc. all dropped. Six tags are enough for "intro paragraph +
     * bullet list + closing paragraph".
     *
     * @return array<string,array<string,bool>>
     */
    private static function description_allowed_html(): array {
        return [
            'p'      => [],
            'ul'     => [],
            'li'     => [],
            'strong' => [],
            'em'     => [],
            'br'     => [],
        ];
    }

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

        // Eligible IDs = products that don't yet have the rewritten-at
        // marker. ORDER BY ID ASC keeps the loop deterministic so a
        // re-run after a partial failure picks up where it left off.
        $ids = (array) $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID
               FROM {$wpdb->posts} p
               LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                                              AND pm.meta_key = %s
              WHERE p.post_type   = 'product'
                AND p.post_status NOT IN ('auto-draft','trash')
                AND pm.meta_id IS NULL
              ORDER BY p.ID ASC
              LIMIT %d",
            self::META_REWRITTEN_AT,
            $limit
        ) );

        $processed = 0;
        $succeeded = 0;
        $failed    = 0;
        $skipped   = 0;
        $samples   = [];

        foreach ( $ids as $id ) {
            $id  = (int) $id;
            $res = self::rewrite_product( $id );
            $processed++;

            if ( $res['status'] === 'ok' ) {
                $succeeded++;
                if ( count( $samples ) < 5 ) {
                    $samples[] = [
                        'id'     => $id,
                        'before' => (string) $res['before'],
                        'after'  => (string) $res['after'],
                    ];
                }
            } elseif ( $res['status'] === 'skipped' ) {
                $skipped++;
            } else {
                $failed++;
                if ( count( $samples ) < 5 ) {
                    $samples[] = [
                        'id'     => $id,
                        'before' => (string) $res['before'],
                        'after'  => '',
                        'error'  => (string) $res['error'],
                    ];
                }
            }
        }

        $remaining = self::count_remaining();
        return [
            'processed' => $processed,
            'succeeded' => $succeeded,
            'failed'    => $failed,
            'skipped'   => $skipped,
            'remaining' => $remaining,
            'done'      => ( $remaining === 0 ),
            'samples'   => $samples,
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

        try {
            $raw = self::ai_rewrite( $title_before, $desc_before );
        } catch ( \Throwable $t ) {
            return [
                'status' => 'error',
                'before' => $title_before,
                'after'  => '',
                'error'  => 'AI: ' . $t->getMessage(),
            ];
        }

        $parsed = self::parse_json_output( $raw );
        if ( ! is_array( $parsed ) || empty( $parsed['title'] ) ) {
            return [
                'status' => 'error',
                'before' => $title_before,
                'after'  => '',
                'error'  => 'AI returned invalid JSON / missing title field',
            ];
        }

        // ---- Title (required) ----
        $title_after = self::sanitise_output( (string) $parsed['title'] );
        $reasons     = self::validation_reasons( $title_after );
        if ( $reasons !== [] ) {
            // Show the AI's actual output so the operator can see what
            // tripped the gate — combined with the specific rule list
            // this turns "Title validation failed" from a black box
            // into something actionable (operator can spot e.g.
            // "Marvel-themed" leaking through and adjust the source
            // description / blocklist filter).
            return [
                'status' => 'error',
                'before' => $title_before,
                'after'  => $title_after,
                'error'  => sprintf(
                    'Title validation failed: %s — AI output was: "%s"',
                    implode( '; ', $reasons ),
                    $title_after
                ),
            ];
        }

        // ---- Description (best-effort) ----
        // If the AI gave us a clean description, we overwrite. If not,
        // we leave the original description in place and still keep the
        // new title — partial success is better than rejecting the row.
        $desc_after_raw   = isset( $parsed['description'] ) ? (string) $parsed['description'] : '';
        $desc_after_clean = self::sanitise_description( $desc_after_raw );
        $desc_usable      = ( $desc_after_clean !== '' && self::passes_description_validation( $desc_after_clean ) );

        // Save originals (once each) so revert can restore exactly.
        // We check `metadata_exists()` rather than an empty-string
        // comparison so the snapshot is never overwritten on a re-run
        // — and so a legitimately empty original (rare: title-less
        // import, blank description) is still recorded as a known
        // empty rather than mistaken for "never saved".
        if ( ! metadata_exists( 'post', $product_id, self::META_ORIGINAL ) ) {
            add_post_meta( $product_id, self::META_ORIGINAL, $title_before, true );
        }
        if ( $desc_usable && ! metadata_exists( 'post', $product_id, self::META_ORIGINAL_DESC ) ) {
            add_post_meta( $product_id, self::META_ORIGINAL_DESC, $desc_before, true );
        }

        $update = [
            'ID'         => $product_id,
            'post_title' => $title_after,
        ];
        if ( $desc_usable ) {
            $update['post_content'] = $desc_after_clean;
        }

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
        clean_post_cache( $product_id );

        return [
            'status'      => 'ok',
            'before'      => $title_before,
            'after'       => $title_after,
            'desc_status' => $desc_usable ? 'rewritten' : 'kept-original',
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
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
              LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                                             AND pm.meta_key = %s
              WHERE p.post_type   = 'product'
                AND p.post_status NOT IN ('auto-draft','trash')
                AND pm.meta_id IS NULL",
            self::META_REWRITTEN_AT
        ) );
    }

    /**
     * Build the prompt + run it through the configured AI provider.
     * Uses tight settings (low temperature, capped tokens) because the
     * task is rule-following, not creativity.
     */
    private static function ai_rewrite( string $original_title, string $original_description = '' ): string {
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
        $prompt = "You produce GMC-compliant e-commerce product copy. The ONLY goal is to pass Google Merchant Center review — output does NOT need to be catchy, SEO-rich, or close in meaning to the source. Plain and generic is good.\n\n"
            . "TASK\n"
            . "Read the SOURCE only to identify what the product IS (generic product type) plus 1–3 neutral attributes (material, colour, size, set quantity, intended user). Then COMPOSE BRAND-NEW copy from scratch. Do NOT paraphrase the source — invent fresh, generic, descriptive text. Sneaky brand mentions in the source (model numbers, character names, licensing wording) MUST disappear in your output.\n\n"
            . "OUTPUT FORMAT — return EXACTLY one valid JSON object with two keys, nothing else:\n"
            . "{\n"
            . "  \"title\": \"...\",\n"
            . "  \"description\": \"...\"\n"
            . "}\n"
            . "No markdown fences, no \"Output:\", no commentary before or after the JSON.\n\n"
            . "TITLE FIELD\n"
            . "Format: <Generic product type> <1–3 attributes>\n"
            . "Length: {$tmin}–{$tmax} characters. Hard max 140.\n"
            . "Examples: \"Wall Picture Frame Wood Black 8x10\", \"Athletic Running Shoe Leather Black Men\", \"Silicone Smartphone Case Clear\".\n\n"
            . "DESCRIPTION FIELD\n"
            . "Length: 100–300 words (target ~150). Hard max 3000 characters of HTML.\n"
            . "Format: simple HTML, exactly this shape:\n"
            . "  <p>One short intro paragraph (2–3 sentences) saying what the product is and the main use case.</p>\n"
            . "  <ul>\n"
            . "    <li><strong>Material:</strong> ...</li>\n"
            . "    <li><strong>Size / Dimensions:</strong> ...</li>\n"
            . "    <li><strong>Key feature:</strong> ...</li>\n"
            . "    <li><strong>Suitable for:</strong> ...</li>\n"
            . "    (4–6 bullets total — drop a row if you don't have a real attribute to put there; do NOT invent attributes)\n"
            . "  </ul>\n"
            . "  <p>One short closing sentence about typical use, care, or who would use it.</p>\n"
            . "Allowed HTML tags ONLY: <p>, <ul>, <li>, <strong>, <em>, <br>. Anything else (script, style, img, iframe, h1–h6, table, span, div, a, button) is FORBIDDEN and will be stripped.\n\n"
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
            . "EXAMPLES — study these. Your JSON output must match this voice:\n\n"
            . "INPUT TITLE: Nike Air Max 90 Men's Athletic Running Shoe (Black/White) - Genuine Leather Upper - Sport & Casual Wear\n"
            . "INPUT DESCRIPTION (excerpt): Nike's iconic Air Max 90 silhouette returns with premium genuine leather upper... Officially licensed. Pairs with Nike Air Force 1.\n"
            . "OUTPUT:\n"
            . "{\"title\":\"Athletic Running Shoe Leather Upper Black White Men\",\"description\":\"<p>This men's athletic running shoe combines a leather upper with a cushioned midsole for daily training and casual wear.</p><ul><li><strong>Material:</strong> genuine leather upper, foam midsole</li><li><strong>Colour:</strong> black with white accents</li><li><strong>Closure:</strong> standard lace-up</li><li><strong>Suitable for:</strong> light running, gym workouts, everyday wear</li></ul><p>Comfortable for long-wear sessions and easy to clean with a soft cloth.</p>\"}\n\n"
            . "INPUT TITLE: Disney Officially Licensed Mickey Mouse Plush Toy 12 inch (Perfect Gift for Kids)\n"
            . "INPUT DESCRIPTION (excerpt): This officially licensed Disney Mickey Mouse plush is the perfect gift! Bestseller 2024. ★★★★★\n"
            . "OUTPUT:\n"
            . "{\"title\":\"Cartoon Character Plush Toy 12 Inch\",\"description\":\"<p>A 12-inch soft plush toy designed in a friendly cartoon-character style, suitable for nursery decor and play.</p><ul><li><strong>Material:</strong> short-pile plush exterior with polyester fibre filling</li><li><strong>Size:</strong> approximately 12 inches tall</li><li><strong>Care:</strong> spot clean only, do not machine wash</li><li><strong>Suitable for:</strong> children aged 3 and up</li></ul><p>Lightweight and easy to carry, with a soft texture suitable for cuddling and display.</p>\"}\n\n"
            . "INPUT TITLE: Ultrasonic Pest Repellent Plug-in, Electronic Mouse Traps & Insect Repellent, Pest Control for Rodent, Roach, Ant, Squirrel, Spider, Bugs, Mouse, Rat, Bat for Home, Office, Warehouse Black 1 Pack\n"
            . "INPUT DESCRIPTION (excerpt): Premium ultrasonic technology drives away rodents and insects safely without chemicals.\n"
            . "OUTPUT:\n"
            . "{\"title\":\"Electronic Pest Repellent Plug-in Black Single Pack\",\"description\":\"<p>This plug-in pest repellent is designed for indoor use in homes, offices, and warehouse spaces, providing a chemical-free way to discourage common pests.</p><ul><li><strong>Type:</strong> electronic plug-in unit</li><li><strong>Coverage:</strong> single-room indoor area</li><li><strong>Target pests:</strong> rodents and common insects</li><li><strong>Colour:</strong> black</li></ul><p>Simple to install in any standard wall outlet and operates quietly during normal use.</p>\"}\n\n"
            . "Notice how \"Ultrasonic\" was dropped from the title — it READS like a brand even though it's an English word. \"Electronic\" is the safe everyday replacement.\n\n"
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
        $data = json_decode( $json, true );
        return is_array( $data ) ? $data : null;
    }

    /**
     * Sanitise an AI-generated description: strip every HTML tag
     * outside the 6-tag allowlist (script, style, img, iframe, h1-h6,
     * span, div, a, button, etc. all dropped); flatten weird whitespace;
     * decode any over-escaped entities; trim leading/trailing junk
     * markers ("Description:", code fences) the model occasionally
     * emits inside the JSON value.
     */
    private static function sanitise_description( string $raw ): string {
        if ( $raw === '' ) { return ''; }
        $s = trim( $raw );
        // Some models double-escape the slash in </p>. Decode once.
        $s = str_replace( [ '<\\/', '\\/' ], [ '</', '/' ], $s );
        // Strip wrapping label / fence the model sneaks INSIDE the
        // JSON value.
        $s = preg_replace( '/^(description)\s*:\s*/i', '', $s );
        $s = preg_replace( '/```(?:html)?\s*(.*?)```/s', '$1', $s );
        // Whitelist HTML — drops script/style/img/h1-h6/etc.
        $s = wp_kses( $s, self::description_allowed_html() );
        // Collapse runs of whitespace inside text but keep tag structure.
        $s = preg_replace( '/[ \t]+/', ' ', $s );
        $s = preg_replace( '/\s*\n\s*/', "\n", $s );
        return trim( $s );
    }

    /**
     * Description-side validator. The plain-text length (after stripping
     * HTML) must fall within [DESC_MIN_TEXT_LEN, …]; the HTML wrapper
     * itself is capped at DESC_MAX_HTML_LEN. Then the same brand-leak
     * gates we apply to titles run on the plain text.
     */
    private static function passes_description_validation( string $html ): bool {
        if ( $html === '' ) { return false; }

        $html_len = function_exists( 'mb_strlen' ) ? mb_strlen( $html ) : strlen( $html );
        if ( $html_len > self::DESC_MAX_HTML_LEN ) { return false; }

        $text     = wp_strip_all_tags( $html );
        $text_len = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
        if ( $text_len < self::DESC_MIN_TEXT_LEN ) { return false; }

        if ( self::contains_protected_brand( $text ) )      { return false; }
        if ( self::contains_sneaky_brand_signal( $text ) )  { return false; }

        // Reject if anything got through the HTML allowlist that
        // shouldn't have — defensive belt-and-suspenders against a
        // wp_kses bypass we missed.
        if ( preg_match( '/<\s*(script|style|iframe|img|h[1-6]|table|a\s|button|form)/i', $html ) ) {
            return false;
        }

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
        $reasons = [];
        if ( $title === '' ) {
            return [ 'empty output' ];
        }
        $len = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );
        if ( $len < self::MIN_LEN ) {
            $reasons[] = sprintf( 'too short (len=%d, min=%d)', $len, self::MIN_LEN );
        }
        if ( $len > self::MAX_LEN ) {
            $reasons[] = sprintf( 'too long (len=%d, max=%d)', $len, self::MAX_LEN );
        }
        if ( self::contains_protected_brand( $title ) ) {
            $reasons[] = 'contains protected brand';
        }
        // All-caps word ≥5 chars (allow USB / LED / HDMI / RGB).
        if ( preg_match_all( '/\b[A-Z]{5,}\b/', $title, $m ) ) {
            $reasons[] = 'ALL-CAPS word found: ' . implode( ', ', array_unique( $m[0] ) );
        }
        // Sneaky brand signals — promo, marketplaces, franchises, licensing.
        $sneaky = self::sneaky_brand_signal_reason( $title );
        if ( $sneaky !== '' ) {
            $reasons[] = $sneaky;
        }
        return $reasons;
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
