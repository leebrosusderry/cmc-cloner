<?php
/**
 * AJAX endpoints for admin-facing features.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Ajax {

    public const NONCE_ACTION = 'cmc_cloner_ajax';

    public const ACTION_TEST           = 'cmc_cloner_test_api';
    public const ACTION_LOAD           = 'cmc_cloner_load_page';
    public const ACTION_PREVIEW        = 'cmc_cloner_preview_prompt';
    public const ACTION_GENERATE       = 'cmc_cloner_generate';
    public const ACTION_UPDATE         = 'cmc_cloner_update_page';
    public const ACTION_REVERT         = 'cmc_cloner_revert_page';
    public const ACTION_BULK_ONE       = 'cmc_cloner_bulk_one';
    public const ACTION_IMG_SCAN       = 'cmc_cloner_img_scan';
    public const ACTION_IMG_RENAME     = 'cmc_cloner_img_rename';
    public const ACTION_IMG_REVERT     = 'cmc_cloner_img_revert';
    public const ACTION_IMG_META_REPAIR = 'cmc_cloner_img_meta_repair';
    public const ACTION_IMG_DIAGNOSE    = 'cmc_cloner_img_diagnose';
    public const ACTION_IMG_PURGE       = 'cmc_cloner_img_purge';
    public const ACTION_PRODUCTS_SCAN         = 'cmc_cloner_products_scan';
    public const ACTION_PRODUCTS_DELETE_BATCH = 'cmc_cloner_products_delete_batch';
    public const ACTION_SKU_SCAN          = 'cmc_cloner_sku_scan';
    public const ACTION_SKU_APPLY_BATCH   = 'cmc_cloner_sku_apply_batch';
    public const ACTION_SKU_REVERT_BATCH  = 'cmc_cloner_sku_revert_batch';
    public const ACTION_TITLE_REWRITE_SCAN   = 'cmc_cloner_title_rewrite_scan';
    public const ACTION_TITLE_REWRITE_BATCH  = 'cmc_cloner_title_rewrite_batch';
    public const ACTION_TITLE_REVERT_BATCH   = 'cmc_cloner_title_revert_batch';
    public const ACTION_RUN_ALL_RENAME_CAT     = 'cmc_cloner_run_all_rename_cat';
    public const ACTION_RUN_ALL_PICK_CAT       = 'cmc_cloner_run_all_pick_cat';
    public const ACTION_RUN_ALL_POD_MARK_DONE  = 'cmc_cloner_run_all_pod_mark_done';
    public const ACTION_RUN_ALL_HEAL_GUIDS     = 'cmc_cloner_run_all_heal_guids';
    public const ACTION_RUN_ALL_SIZE_GUIDE     = 'cmc_cloner_run_all_size_guide';
    public const ACTION_RUN_ALL_BUILD_SUBCATS  = 'cmc_cloner_run_all_build_subcats';
    public const ACTION_REVERT_SUBCATS         = 'cmc_cloner_revert_subcats';
    public const ACTION_VARIATION_NORMALIZE_BATCH = 'cmc_cloner_variation_normalize_batch';
    public const ACTION_REVIEW_SCAN          = 'cmc_cloner_review_scan';
    public const ACTION_REVIEW_SEED          = 'cmc_cloner_review_seed';
    public const ACTION_REVIEW_AI_POLISH_ONE = 'cmc_cloner_review_ai_polish_one';
    public const ACTION_REVIEW_REMOVE        = 'cmc_cloner_review_remove';
    public const ACTION_SKELETON_RANDOMIZE = 'cmc_cloner_skeleton_randomize';

    public static function init(): void {
        add_action( 'wp_ajax_' . self::ACTION_TEST,           [ self::class, 'handle_test_api' ] );
        add_action( 'wp_ajax_' . self::ACTION_LOAD,           [ self::class, 'handle_load_page' ] );
        add_action( 'wp_ajax_' . self::ACTION_PREVIEW,        [ self::class, 'handle_preview_prompt' ] );
        add_action( 'wp_ajax_' . self::ACTION_GENERATE,       [ self::class, 'handle_generate' ] );
        add_action( 'wp_ajax_' . self::ACTION_UPDATE,         [ self::class, 'handle_update_page' ] );
        add_action( 'wp_ajax_' . self::ACTION_REVERT,         [ self::class, 'handle_revert_page' ] );
        add_action( 'wp_ajax_' . self::ACTION_BULK_ONE,       [ self::class, 'handle_bulk_one' ] );
        add_action( 'wp_ajax_' . self::ACTION_IMG_SCAN,       [ self::class, 'handle_img_scan' ] );
        add_action( 'wp_ajax_' . self::ACTION_IMG_RENAME,     [ self::class, 'handle_img_rename' ] );
        add_action( 'wp_ajax_' . self::ACTION_IMG_REVERT,     [ self::class, 'handle_img_revert' ] );
        add_action( 'wp_ajax_' . self::ACTION_IMG_META_REPAIR, [ self::class, 'handle_img_meta_repair' ] );
        add_action( 'wp_ajax_' . self::ACTION_IMG_DIAGNOSE,    [ self::class, 'handle_img_diagnose' ] );
        add_action( 'wp_ajax_' . self::ACTION_IMG_PURGE,       [ self::class, 'handle_img_purge' ] );
        add_action( 'wp_ajax_' . self::ACTION_PRODUCTS_SCAN,         [ self::class, 'handle_products_scan' ] );
        add_action( 'wp_ajax_' . self::ACTION_PRODUCTS_DELETE_BATCH, [ self::class, 'handle_products_delete_batch' ] );
        add_action( 'wp_ajax_' . self::ACTION_SKU_SCAN,         [ self::class, 'handle_sku_scan' ] );
        add_action( 'wp_ajax_' . self::ACTION_SKU_APPLY_BATCH,  [ self::class, 'handle_sku_apply_batch' ] );
        add_action( 'wp_ajax_' . self::ACTION_SKU_REVERT_BATCH, [ self::class, 'handle_sku_revert_batch' ] );
        add_action( 'wp_ajax_' . self::ACTION_TITLE_REWRITE_SCAN,  [ self::class, 'handle_title_rewrite_scan' ] );
        add_action( 'wp_ajax_' . self::ACTION_TITLE_REWRITE_BATCH, [ self::class, 'handle_title_rewrite_batch' ] );
        add_action( 'wp_ajax_' . self::ACTION_TITLE_REVERT_BATCH,  [ self::class, 'handle_title_revert_batch' ] );
        add_action( 'wp_ajax_' . self::ACTION_RUN_ALL_RENAME_CAT,    [ self::class, 'handle_run_all_rename_cat' ] );
        add_action( 'wp_ajax_' . self::ACTION_RUN_ALL_PICK_CAT,      [ self::class, 'handle_run_all_pick_cat' ] );
        add_action( 'wp_ajax_' . self::ACTION_RUN_ALL_POD_MARK_DONE, [ self::class, 'handle_run_all_pod_mark_done' ] );
        add_action( 'wp_ajax_' . self::ACTION_RUN_ALL_HEAL_GUIDS,    [ self::class, 'handle_run_all_heal_guids' ] );
        add_action( 'wp_ajax_' . self::ACTION_RUN_ALL_SIZE_GUIDE,    [ self::class, 'handle_run_all_size_guide' ] );
        add_action( 'wp_ajax_' . self::ACTION_RUN_ALL_BUILD_SUBCATS, [ self::class, 'handle_run_all_build_subcats' ] );
        add_action( 'wp_ajax_' . self::ACTION_REVERT_SUBCATS,        [ self::class, 'handle_revert_subcats' ] );
        add_action( 'wp_ajax_' . self::ACTION_VARIATION_NORMALIZE_BATCH, [ self::class, 'handle_variation_normalize_batch' ] );
        add_action( 'wp_ajax_' . self::ACTION_REVIEW_SCAN,          [ self::class, 'handle_review_scan' ] );
        add_action( 'wp_ajax_' . self::ACTION_REVIEW_SEED,          [ self::class, 'handle_review_seed' ] );
        add_action( 'wp_ajax_' . self::ACTION_REVIEW_AI_POLISH_ONE, [ self::class, 'handle_review_ai_polish_one' ] );
        add_action( 'wp_ajax_' . self::ACTION_REVIEW_REMOVE,        [ self::class, 'handle_review_remove' ] );
        add_action( 'wp_ajax_' . self::ACTION_SKELETON_RANDOMIZE, [ self::class, 'handle_skeleton_randomize' ] );
    }

    public static function handle_skeleton_randomize(): void {
        self::guard();
        $number = CMC_Settings::randomize_skeleton_variant();
        wp_send_json_success( [ 'number' => $number ] );
    }

    public static function handle_test_api(): void {
        self::guard();
        $result = CMC_AI_Client::test();
        if ( ! empty( $result['success'] ) ) {
            wp_send_json_success( $result );
        }
        wp_send_json_error( $result );
    }

    public static function handle_load_page(): void {
        self::guard();
        $page_id = (int) ( $_POST['page_id'] ?? 0 );
        $page    = CMC_Page_Reader::get_page( $page_id );
        if ( $page === null ) {
            wp_send_json_error( [ 'message' => 'Page not found.' ], 404 );
        }
        wp_send_json_success( [ 'page' => $page ] );
    }

    public static function handle_preview_prompt(): void {
        self::guard();
        $page_id           = (int) ( $_POST['page_id'] ?? 0 );
        $template_slug     = sanitize_key( (string) ( $_POST['template_slug'] ?? '' ) );
        $raw_override      = sanitize_key( (string) ( $_POST['skeleton_slug'] ?? '' ) );
        $skeleton_override = $raw_override !== '' ? $raw_override : null;

        try {
            $built = CMC_Prompt_Builder::build( $template_slug, $page_id, $skeleton_override );
        } catch ( Throwable $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ], 400 );
        }
        wp_send_json_success( $built );
    }

    public static function handle_generate(): void {
        self::guard();
        $prompt = (string) wp_unslash( $_POST['prompt'] ?? '' );
        if ( trim( $prompt ) === '' ) {
            wp_send_json_error( [ 'message' => 'Prompt is empty.' ], 400 );
        }

        $settings = CMC_Settings::get();
        $params   = [
            'max_tokens'  => (int)   $settings['max_tokens'],
            'temperature' => (float) $settings['temperature'],
        ];

        try {
            $output = CMC_AI_Client::generate( $prompt, $params );
        } catch ( Throwable $e ) {
            wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
        }

        $cleaned = CMC_Content_Sanitizer::sanitize(
            self::strip_code_fences( (string) $output )
        );

        wp_send_json_success( [
            'content'       => $cleaned,
            'raw'           => (string) $output,
            'provider'      => (string) $settings['ai_provider'],
        ] );
    }

    public static function handle_update_page(): void {
        self::guard();
        $page_id       = (int) ( $_POST['page_id'] ?? 0 );
        $content       = (string) wp_unslash( $_POST['content'] ?? '' );
        $template_slug = sanitize_key( (string) ( $_POST['template_slug'] ?? '' ) );
        $skeleton_slug = sanitize_key( (string) ( $_POST['skeleton_slug'] ?? '' ) );
        $style_seed    = (int)   ( $_POST['style_seed'] ?? 0 );

        if ( trim( $content ) === '' ) {
            wp_send_json_error( [ 'message' => 'Content is empty.' ], 400 );
        }
        if ( $template_slug === '' || $skeleton_slug === '' ) {
            wp_send_json_error( [ 'message' => 'Template or skeleton missing.' ], 400 );
        }

        $content = CMC_Content_Sanitizer::sanitize( $content );

        $result = CMC_Page_Writer::update( $page_id, $content, $template_slug, $skeleton_slug, $style_seed );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
        }

        $page = CMC_Page_Reader::get_page( $page_id );
        wp_send_json_success( [ 'page' => $page ] );
    }

    /**
     * Atomic bulk step: build prompt → call AI → sanitize → save in one request.
     * Used by the bulk-generate UI to process one page per AJAX call.
     */
    public static function handle_bulk_one(): void {
        self::guard();
        $page_id       = (int) ( $_POST['page_id'] ?? 0 );
        $template_slug = sanitize_key( (string) ( $_POST['template_slug'] ?? '' ) );

        if ( $page_id <= 0 ) {
            wp_send_json_error( [ 'message' => 'Missing page_id.' ], 400 );
        }
        if ( $template_slug === '' ) {
            wp_send_json_error( [ 'message' => 'Missing template.' ], 400 );
        }

        $start = microtime( true );

        try {
            $built = CMC_Prompt_Builder::build( $template_slug, $page_id, null );
        } catch ( Throwable $e ) {
            wp_send_json_error( [ 'message' => 'Prompt build failed: ' . $e->getMessage() ], 400 );
        }

        $settings = CMC_Settings::get();
        $params   = [
            'max_tokens'  => (int)   $settings['max_tokens'],
            'temperature' => (float) $settings['temperature'],
        ];

        // Validator runs only on templates whose copy is most prone to
        // industry-leak. Contact Us deliberately omitted — that page is
        // mostly contact details and forcing industry words there reads
        // as keyword-stuffing. Adding more templates here later is a
        // one-line change. Homepage doesn't validate server-side because
        // the plugin only emits a prompt, not HTML.
        $validate_template = in_array( $template_slug, [ 'about-us' ], true );
        $industry          = (string) ( CMC_Settings::get()['company_info']['nganh_hang'] ?? '' );
        // Resolve the niche slug to its human label so the validator's
        // mention count matches what the prompt actually instructs the
        // AI to write ("Fashion & Apparel", not "fashion-apparel").
        if ( $industry !== '' ) {
            $nganh_options = CMC_Shortcodes::nganh_hang_options();
            $industry      = (string) ( $nganh_options[ $industry ] ?? $industry );
        }

        $max_attempts = $validate_template ? 3 : 1; // 1 initial + 2 retries
        $prompt       = $built['prompt'];
        $output       = '';
        $content      = '';
        $last_reasons = [];
        $attempts     = 0;

        for ( $i = 0; $i < $max_attempts; $i++ ) {
            $attempts++;
            try {
                $output = CMC_AI_Client::generate( $prompt, $params );
            } catch ( Throwable $e ) {
                wp_send_json_error( [ 'message' => 'AI generation failed: ' . $e->getMessage() ], 500 );
            }
            $content = CMC_Content_Sanitizer::sanitize(
                self::strip_code_fences( (string) $output )
            );
            if ( trim( $content ) === '' ) {
                wp_send_json_error( [ 'message' => 'AI returned empty content.' ], 500 );
            }

            if ( ! $validate_template ) {
                break;
            }

            $verdict = CMC_Content_Validator::validate( $content, $template_slug, $industry );
            if ( $verdict['pass'] ) {
                $last_reasons = [];
                break;
            }
            $last_reasons = $verdict['reasons'];

            // Out of retry budget — keep the last attempt and flag it.
            if ( $i === $max_attempts - 1 ) {
                break;
            }

            // Re-prompt with the failure reasons appended as a footer so
            // the next attempt has explicit corrective guidance.
            $prompt = $built['prompt'] . CMC_Content_Validator::format_retry_footer( $verdict['reasons'] );
        }

        $result = CMC_Page_Writer::update(
            $page_id,
            $content,
            $built['template_slug'],
            $built['skeleton_slug'],
            (int) $built['style_seed']
        );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => 'Save failed: ' . $result->get_error_message() ], 500 );
        }

        // Stamp / clear a postmeta warning marker so the admin UI can
        // surface "this page failed validation N times — review it"
        // on the Pages screen.
        if ( $validate_template ) {
            if ( ! empty( $last_reasons ) ) {
                update_post_meta( $page_id, '_cmc_validation_warning', [
                    'reasons'  => array_slice( $last_reasons, 0, 5 ),
                    'attempts' => $attempts,
                    'at'       => time(),
                ] );
            } else {
                delete_post_meta( $page_id, '_cmc_validation_warning' );
            }
        }

        $duration_ms = (int) round( ( microtime( true ) - $start ) * 1000 );
        $page        = CMC_Page_Reader::get_page( $page_id );

        wp_send_json_success( [
            'page'              => $page,
            'template_slug'     => $built['template_slug'],
            'skeleton_slug'     => $built['skeleton_slug'],
            'duration_ms'       => $duration_ms,
            'validation_attempts' => $attempts,
            'validation_passed'   => empty( $last_reasons ),
            'validation_reasons'  => $last_reasons,
        ] );
    }

    /**
     * Read-only: list products in a category and their rename-eligible
     * image counts. Used to prime the preview table in Site Setup.
     */
    public static function handle_img_scan(): void {
        self::guard();
        $term_id         = (int) ( $_POST['term_id'] ?? 0 );
        $include_subcats = ! empty( $_POST['include_subcats'] );
        if ( $term_id <= 0 ) {
            wp_send_json_error( [ 'message' => 'Pick a category first.' ], 400 );
        }
        wp_send_json_success( CMC_Image_Renamer::scan_category( $term_id, $include_subcats ) );
    }

    /**
     * Write: rename every eligible image for one product. The client
     * calls this endpoint once per product to keep each request small
     * and give the progress UI a clean granularity.
     */
    public static function handle_img_rename(): void {
        self::guard();
        $product_id = (int) ( $_POST['product_id'] ?? 0 );
        if ( $product_id <= 0 ) {
            wp_send_json_error( [ 'message' => 'Missing product_id.' ], 400 );
        }
        wp_send_json_success( CMC_Image_Renamer::rename_product( $product_id ) );
    }

    /**
     * Write: revert every renamed image for one product to its stored
     * original filename.
     */
    public static function handle_img_revert(): void {
        self::guard();
        $product_id = (int) ( $_POST['product_id'] ?? 0 );
        if ( $product_id <= 0 ) {
            wp_send_json_error( [ 'message' => 'Missing product_id.' ], 400 );
        }
        wp_send_json_success( CMC_Image_Renamer::revert_product( $product_id ) );
    }

    /**
     * Heal attachments where `_wp_attachment_metadata[file]` lost its
     * YYYY/MM subdir prefix (symptom: srcset URLs 404 and product card
     * images render blank while `src` still resolves). Call with
     * `apply=0` to report mismatches, `apply=1` to write the fix.
     */
    public static function handle_img_meta_repair(): void {
        self::guard();
        $apply           = ! empty( $_POST['apply'] );
        $offset          = max( 0, (int) ( $_POST['offset'] ?? 0 ) );
        $limit           = (int) ( $_POST['limit'] ?? 30 );
        $term_id         = max( 0, (int) ( $_POST['term_id'] ?? 0 ) );
        $include_subcats = ! empty( $_POST['include_subcats'] );
        $result = CMC_Image_Renamer::repair_metadata_paths( $apply, $offset, $limit, $term_id, $include_subcats );
        wp_send_json_success( $result );
    }

    /**
     * Dump raw DB state + rendered <img> for one attachment so we can
     * tell whether a broken srcset is caused by DB metadata or by a
     * runtime filter. Input: attachment ID, product ID, product slug,
     * or a filename fragment.
     */
    public static function handle_img_diagnose(): void {
        self::guard();
        $needle = (string) wp_unslash( $_POST['needle'] ?? '' );
        if ( trim( $needle ) === '' ) {
            wp_send_json_error( [ 'message' => 'Pass an attachment ID, product ID, product slug, or filename fragment.' ], 400 );
        }
        $aid = CMC_Image_Renamer::resolve_attachment_id( $needle );
        if ( $aid <= 0 ) {
            wp_send_json_error( [ 'message' => 'No attachment found for: ' . $needle ], 404 );
        }
        wp_send_json_success( CMC_Image_Renamer::diagnose_attachment( $aid ) );
    }

    /**
     * Force-trigger every cache-purge hook the renamer fires at end-of-batch,
     * exposed as a one-click button so the admin can re-run it after manual
     * edits, when the rename's auto-purge happened before the cache plugin
     * was hooked, or when stale `data-o_*` snapshots persist due to LiteSpeed
     * Image Optimizer caching.
     */
    public static function handle_img_purge(): void {
        self::guard();
        $fired = CMC_Image_Renamer::purge_external_caches();
        wp_send_json_success( [
            'fired'   => $fired,
            'message' => empty( $fired )
                ? 'No known cache plugin detected — only generic WP object cache was flushed. Add WP Mail SMTP, LiteSpeed Cache, WP Rocket, etc. for richer purge coverage.'
                : 'Purge triggered for: ' . implode( ', ', $fired ) . '. If you still see stale URLs, hard-reload the browser (Ctrl+Shift+R) and purge any external CDN (Cloudflare).',
        ] );
    }

    /**
     * Read-only: count how many products / variations / attachments would
     * be wiped. Used to prime the confirm dialog in Site Setup.
     */
    public static function handle_products_scan(): void {
        self::guard();
        wp_send_json_success( CMC_Products_Eraser::scan() );
    }

    /**
     * Write: force-delete the next batch of products (plus their variations
     * and directly-attached media). The client loops until `done=true` so
     * each HTTP round-trip stays small and the progress bar can update.
     * Requires the literal string `OK` in `confirm` so the UI's type-to-confirm
     * gate is mirrored server-side.
     */
    public static function handle_products_delete_batch(): void {
        self::guard();

        $confirm = (string) wp_unslash( $_POST['confirm'] ?? '' );
        if ( trim( $confirm ) !== 'OK' ) {
            wp_send_json_error( [ 'message' => 'Type OK to confirm.' ], 400 );
        }

        $batch_size = max( 1, min( 50, (int) ( $_POST['batch_size'] ?? 20 ) ) );
        $ids = CMC_Products_Eraser::next_batch( $batch_size );

        $totals = [
            'product'     => 0,
            'variations'  => 0,
            'attachments' => 0,
            'errors'      => [],
        ];

        foreach ( $ids as $pid ) {
            // `delete_one()` routes each ID — product OR orphan variation
            // — to the right delete path. Phase 2 of the batch (orphan
            // variations) only kicks in after every product is gone.
            $r = CMC_Products_Eraser::delete_one( $pid );
            $totals['product']     += (int) $r['product'];
            $totals['variations']  += (int) $r['variations'];
            $totals['attachments'] += (int) $r['attachments'];
            if ( ! empty( $r['errors'] ) ) {
                foreach ( $r['errors'] as $e ) {
                    $totals['errors'][] = $e;
                }
            }
        }

        $remaining = CMC_Products_Eraser::remaining_deletable();
        wp_send_json_success( [
            'deleted_in_batch' => count( $ids ),
            'batch'            => $totals,
            'remaining'        => $remaining,
            'done'             => $remaining === 0,
        ] );
    }

    /**
     * Read-only: count eligible Amazon-style SKUs and return a small
     * preview sample for the UI. See CMC_Sku_Normalizer::scan().
     */
    public static function handle_sku_scan(): void {
        self::guard();
        wp_send_json_success( CMC_Sku_Normalizer::scan() );
    }

    /**
     * Write: rewrite the next batch of foreign SKUs. Client loops until
     * `done=true` the same way the products-eraser batch loop does.
     */
    public static function handle_sku_apply_batch(): void {
        self::guard();
        $batch_size = max( 1, min( 100, (int) ( $_POST['batch_size'] ?? 25 ) ) );
        wp_send_json_success( CMC_Sku_Normalizer::apply_batch( $batch_size ) );
    }

    /**
     * Write: restore original SKUs for the next batch of products that
     * still have `_cmc_original_sku` stored.
     */
    public static function handle_sku_revert_batch(): void {
        self::guard();
        $batch_size = max( 1, min( 200, (int) ( $_POST['batch_size'] ?? 50 ) ) );
        wp_send_json_success( CMC_Sku_Normalizer::revert_batch( $batch_size ) );
    }

    /**
     * Read-only: count products eligible for AI title rewrite. The
     * pending number is what the batch loop will actually call the AI
     * on (already-rewritten rows are excluded by the rewriter itself).
     */
    public static function handle_title_rewrite_scan(): void {
        self::guard();
        wp_send_json_success( CMC_Title_Rewriter::scan() );
    }

    /**
     * Write: rewrite the next batch of product titles via AI. Default
     * batch size is small (5) because each AI call takes ~2–3 s — at
     * 5 per request we stay comfortably inside the 30 s LSAPI window.
     */
    public static function handle_title_rewrite_batch(): void {
        self::guard();
        $batch_size = max( 1, min( 20, (int) ( $_POST['batch_size'] ?? 5 ) ) );
        wp_send_json_success( CMC_Title_Rewriter::rewrite_batch( $batch_size ) );
    }

    /**
     * Write: restore original product titles for the next batch of
     * products that still have `_cmc_original_title` stored.
     */
    public static function handle_title_revert_batch(): void {
        self::guard();
        $batch_size = max( 1, min( 200, (int) ( $_POST['batch_size'] ?? 50 ) ) );
        wp_send_json_success( CMC_Title_Rewriter::revert_batch( $batch_size ) );
    }

    /**
     * Run-All orchestrator helper: rename the primary product_cat term
     * to the configured Industry, promote it as default, delete every
     * other term. Wraps the pure logic in CMC_Setup_Controller so the
     * orchestrator JS can sequence it alongside the other steps.
     */
    public static function handle_run_all_rename_cat(): void {
        self::guard();
        $result = CMC_Setup_Controller::rename_category_for_industry();
        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result, 400 );
        }
    }

    /**
     * Run-All orchestrator helper: return the product_cat term IDs
     * that currently exist on the site, sorted by product count
     * descending so the first entry is the one Run All should use
     * for the Image Rename step. Solves the "category dropdown is
     * stale after Rename Category Name" problem — the dropdown is
     * rendered server-side at page load; we re-query at orchestrator
     * time so the orchestrator picks the FRESH term, not whichever
     * term happened to be first in the dropdown at page load.
     */
    public static function handle_run_all_pick_cat(): void {
        self::guard();
        if ( ! taxonomy_exists( 'product_cat' ) ) {
            wp_send_json_success( [ 'terms' => [], 'top_id' => 0 ] );
        }
        $terms = get_terms( [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'number'     => 50,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ] );
        $out = [];
        if ( is_array( $terms ) ) {
            foreach ( $terms as $t ) {
                $out[] = [
                    'id'    => (int) $t->term_id,
                    'name'  => (string) $t->name,
                    'slug'  => (string) $t->slug,
                    'count' => (int) $t->count,
                ];
            }
        }
        wp_send_json_success( [
            'terms'  => $out,
            // Prefer the most-populated term (not Uncategorized if it
            // somehow re-appeared with zero products). Fall back to
            // any term when no products have been imported yet.
            'top_id' => $out ? (int) $out[0]['id'] : 0,
        ] );
    }

    /**
     * Mark Woo POD Setup as done for the current site. The flag is keyed
     * by home_url() so a freshly cloned site (different domain) re-runs
     * the setup automatically even though the options table came over
     * with the clone.
     */
    public static function handle_run_all_pod_mark_done(): void {
        self::guard();
        update_option( 'cmc_pod_setup_done_for', home_url(), false );
        wp_send_json_success( [ 'done' => true ] );
    }

    /**
     * Walk attachments in batches and patch any `guid` that drifted
     * from the `_wp_attached_file`-derived URL. Backfill for the
     * pre-fix renames where wp_update_post() silently dropped the
     * guid update.
     *
     * Input:  offset (int), limit (int, default 100)
     * Output: { checked, updated, total, next_offset, done }
     */
    public static function handle_run_all_heal_guids(): void {
        self::guard();
        $offset = max( 0, (int) ( $_POST['offset'] ?? 0 ) );
        $limit  = max( 1, min( 500, (int) ( $_POST['limit'] ?? 100 ) ) );
        wp_send_json_success( CMC_Image_Renamer::heal_stale_guids_batch( $offset, $limit ) );
    }

    /**
     * Run-All step: keep the niche-specific Size Guide page in sync with
     * the configured industry. Bidirectional — covers the "cloned to a
     * non-fashion site" case so the user never has to clean up manually.
     *
     * Behaviour matrix (industry-match × page-exists):
     *   match + no page    → create empty `size-guide` page, AI fill,
     *                        attach link to footer block. Toggle-back
     *                        case: any previously-demoted draft with the
     *                        same slug is reused + re-published, not
     *                        duplicated.
     *   match + page exists → re-publish if needed, AI regenerate,
     *                         ensure footer link present.
     *   no match + page    → demote to draft, strip the footer link
     *                        (keeps slug stable + backup intact).
     *   no match + no page → no-op (skipped:true).
     *
     * Not idempotent in the matching branch: re-running Run All always
     * regenerates content. The very first pre-plugin `post_content`
     * stays preserved in `_cmc_original_content`, so subsequent regens
     * only clobber the previous AI output, never the user's original.
     */
    public static function handle_run_all_size_guide(): void {
        self::guard();

        // 1. Industry gate (computed exactly like CMC_Pages_Controller so
        //    the same haystack drives both UI filter and Run-All gating).
        $settings = CMC_Settings::get();
        $slug     = (string) ( $settings['company_info']['nganh_hang'] ?? '' );
        $opts     = CMC_Shortcodes::nganh_hang_options();
        $label    = (string) ( $opts[ $slug ] ?? '' );
        $haystack = trim( $slug . ' ' . $label );
        $applies  = CMC_Template_Registry::applies_to_industry( 'size-guide', $haystack );

        // 2. Locate the page (may not exist yet).
        $existing = get_page_by_path( 'size-guide', OBJECT, 'page' );
        $page_id  = $existing ? (int) $existing->ID : 0;
        $created  = false;

        // Also look up any draft we previously demoted on a prior pass,
        // so toggling industry back to fashion re-publishes the same
        // page instead of creating a sibling. `get_page_by_path` only
        // returns published posts; a direct query covers the draft case.
        if ( $page_id <= 0 ) {
            global $wpdb;
            $draft_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status IN ('draft','pending','private') AND post_name=%s LIMIT 1",
                'page',
                'size-guide'
            ) );
            if ( $draft_id > 0 ) {
                $page_id = $draft_id;
            }
        }

        // 3. Cleanup branch — industry no longer needs a Size Guide.
        if ( ! $applies ) {
            if ( $page_id <= 0 ) {
                wp_send_json_success( [
                    'skipped' => true,
                    'reason'  => 'industry-not-applicable',
                    'message' => 'Industry "' . $label . '" does not need a Size Guide — nothing to do.',
                ] );
            }
            // Strip the footer link first so the URL stops 404-ing on
            // sites still hitting it before the page goes private.
            CMC_Size_Guide::detach_slug( 'size-guide' );

            // Demote to draft rather than trash: keeps the slug stable
            // (trash appends `__trashed`), keeps `_cmc_original_content`
            // backup intact, and makes the re-publish path on toggle-back
            // a single status flip with no slug-collision worry.
            $demoted = wp_update_post( [
                'ID'          => $page_id,
                'post_status' => 'draft',
            ], true );
            if ( is_wp_error( $demoted ) ) {
                wp_send_json_error( [ 'message' => 'Could not draft Size Guide page: ' . $demoted->get_error_message() ], 500 );
            }
            wp_send_json_success( [
                'page_id' => $page_id,
                'created' => false,
                'skipped' => false,
                'cleaned' => true,
                'message' => 'Industry "' . $label . '" no longer needs Size Guide — page set to draft + footer link removed.',
            ] );
        }

        // 4. Matching branch — make sure the page exists AND is published.
        if ( $page_id <= 0 ) {
            $inserted = wp_insert_post( [
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => 'Size Guide',
                'post_name'    => 'size-guide',
                'post_content' => '',
                'comment_status' => 'closed',
                'ping_status'    => 'closed',
            ], true );
            if ( is_wp_error( $inserted ) ) {
                wp_send_json_error( [ 'message' => 'Could not create Size Guide page: ' . $inserted->get_error_message() ], 500 );
            }
            $page_id = (int) $inserted;
            $created = true;
        } else {
            // Existing page might be a previously-drafted one — re-publish.
            $current_status = (string) get_post_status( $page_id );
            if ( $current_status !== 'publish' ) {
                wp_update_post( [
                    'ID'          => $page_id,
                    'post_status' => 'publish',
                ] );
            }
        }

        // 5. Build prompt + call AI + save. Same pattern as handle_bulk_one
        //    but inlined here so a Run-All retry doesn't depend on the
        //    Pages bulk UI being open.
        try {
            $built = CMC_Prompt_Builder::build( 'size-guide', $page_id, null );
        } catch ( Throwable $e ) {
            wp_send_json_error( [ 'message' => 'Prompt build failed: ' . $e->getMessage() ], 500 );
        }

        $params = [
            'max_tokens'  => (int)   $settings['max_tokens'],
            'temperature' => (float) $settings['temperature'],
        ];

        try {
            $output = CMC_AI_Client::generate( $built['prompt'], $params );
        } catch ( Throwable $e ) {
            wp_send_json_error( [ 'message' => 'AI generation failed: ' . $e->getMessage() ], 500 );
        }

        $content = CMC_Content_Sanitizer::sanitize(
            self::strip_code_fences( (string) $output )
        );
        if ( trim( $content ) === '' ) {
            wp_send_json_error( [ 'message' => 'AI returned empty content.' ], 500 );
        }

        $result = CMC_Page_Writer::update(
            $page_id,
            $content,
            $built['template_slug'],
            $built['skeleton_slug'],
            (int) $built['style_seed']
        );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => 'Save failed: ' . $result->get_error_message() ], 500 );
        }

        wp_send_json_success( [
            'page_id' => $page_id,
            'created' => $created,
            'skipped' => false,
            'message' => $created ? 'Page created + content generated + added to footer.' : 'Content regenerated (overwrote previous version) + footer ensured.',
        ] );
    }

    /**
     * Run-All step: split the single niche product_cat into 5-8 GMC-
     * friendly sub-categories via one AI call, then distribute existing
     * products into those sub-categories by title-keyword scoring.
     * Idempotent — re-running re-uses the cached plan and only assigns
     * products that don't yet carry a backup snapshot.
     *
     * Heavy implementation lives in CMC_Category_Builder; this handler
     * is a thin guard + envelope.
     */
    public static function handle_run_all_build_subcats(): void {
        self::guard();
        $result = CMC_Category_Builder::run();
        if ( empty( $result['success'] ) ) {
            wp_send_json_error( [ 'message' => (string) ( $result['message'] ?? 'Failed.' ) ], 500 );
        }
        wp_send_json_success( $result );
    }

    /**
     * Manual revert for the sub-category build: restores every product's
     * pre-distribution term assignment from `_cmc_orig_cats` postmeta,
     * then deletes only the terms tagged `_cmc_auto_created_subcat=1`.
     * Surfaced from the Site Setup screen — not part of Run All.
     */
    public static function handle_revert_subcats(): void {
        self::guard();
        $result = CMC_Category_Builder::revert();
        wp_send_json_success( $result );
    }

    /**
     * Paginated variation-attribute-term cleanup. Each call walks
     * `$batch` terms starting at `$offset`, applies Layer 1+2
     * normalization, dedupes, and returns counters + a few samples.
     * Client paginates until `done = true`.
     */
    public static function handle_variation_normalize_batch(): void {
        self::guard();
        $offset = max( 0, (int) ( $_POST['offset'] ?? 0 ) );
        $batch  = max( 1, min( 200, (int) ( $_POST['batch_size'] ?? 50 ) ) );
        wp_send_json_success( CMC_Variation_Normalizer::apply( $offset, $batch ) );
    }

    // ---------- Review Seeder ----------

    public static function handle_review_scan(): void {
        self::guard();
        wp_send_json_success( CMC_Review_Seeder::scan() );
    }

    /**
     * Write: seed reviews for the selected products. Products with existing
     * reviews are skipped unless `include_existing=1` is passed explicitly.
     */
    public static function handle_review_seed(): void {
        self::guard();
        $raw_ids  = (array) ( $_POST['product_ids'] ?? [] );
        $ids      = array_values( array_filter( array_map( 'intval', $raw_ids ), static fn( $v ) => $v > 0 ) );
        $include  = ! empty( $_POST['include_existing'] );
        if ( empty( $ids ) ) {
            wp_send_json_error( [ 'message' => 'Pick at least one product.' ], 400 );
        }
        wp_send_json_success( CMC_Review_Seeder::seed( $ids, $include ) );
    }

    /**
     * Write: polish one seeded review with the configured AI provider.
     * The client loops over every seeded comment ID returned by scan()
     * so each AJAX round-trip stays small and the progress bar updates
     * one review at a time.
     */
    public static function handle_review_ai_polish_one(): void {
        self::guard();
        $comment_id = (int) ( $_POST['comment_id'] ?? 0 );
        if ( $comment_id <= 0 ) {
            // No ID passed → return the queue so the client can drive the loop.
            wp_send_json_success( [ 'queue' => CMC_Review_Seeder::list_seeded_ids() ] );
        }
        $result = CMC_Review_Seeder::ai_polish_one( $comment_id );
        if ( empty( $result['ok'] ) ) {
            wp_send_json_error( [ 'message' => (string) ( $result['message'] ?? 'Polish failed.' ), 'comment_id' => $comment_id ], 400 );
        }
        wp_send_json_success( $result );
    }

    /**
     * Write: delete every comment marked as a seeded review. Real customer
     * reviews are untouched because they never have the marker meta.
     */
    public static function handle_review_remove(): void {
        self::guard();
        wp_send_json_success( CMC_Review_Seeder::remove_all_seeded() );
    }

    public static function handle_revert_page(): void {
        self::guard();
        $page_id = (int) ( $_POST['page_id'] ?? 0 );

        $result = CMC_Page_Writer::revert( $page_id );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
        }

        $page = CMC_Page_Reader::get_page( $page_id );
        wp_send_json_success( [ 'page' => $page ] );
    }

    private static function guard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Forbidden' ], 403 );
        }
        check_ajax_referer( self::NONCE_ACTION, 'nonce' );
    }

    /**
     * AI providers often wrap output in ```…``` fences. Strip a single
     * wrapping fence if present so the saved content is clean shortcodes/HTML.
     */
    private static function strip_code_fences( string $text ): string {
        $trimmed = trim( $text );
        if ( preg_match( '/^```[a-zA-Z0-9_-]*\s*\n(.*)\n```\s*$/s', $trimmed, $m ) ) {
            return trim( $m[1] );
        }
        return $trimmed;
    }
}
