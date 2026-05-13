<?php
/**
 * Image Renamer — renames product-image files so the filenames no longer
 * carry brand names / copyrighted tokens that Google Merchant Center
 * flags. All images that belong to a product (featured, gallery, and
 * per-variation) are renamed to the product's slug.
 *
 * Rename scope (applied atomically per attachment):
 *   1. The original file on disk
 *   2. Every generated size variant (`-150x150.jpg`, `-300x300.jpg`, …)
 *      plus the `-scaled.jpg` big-image WordPress creates for >2560px uploads
 *   3. `wp_posts` row: `post_title`, `post_name`, `guid`
 *   4. `_wp_attached_file` meta (`2025/04/foo.jpg`)
 *   5. `_wp_attachment_metadata` meta (the `file` + `sizes[*][file]` keys)
 *   6. Any `post_content` that references the old URL anywhere on the site
 *   7. Yoast SEO's `wp_yoast_indexable` table (og:image / twitter:image
 *      columns) so the rendered `<meta property="og:image">` tag updates
 *
 * Safety:
 *   - Images whose `post_parent` is not the product (shared library images)
 *     are skipped so we never rename something used elsewhere.
 *   - Each attachment stores `_cmc_img_original_filename` on first rename,
 *     keyed to the original basename — so revert is possible and repeated
 *     runs become no-ops for already-renamed attachments.
 *   - Within a batch we reserve filenames in a local map to prevent
 *     collisions between two products with the same slug (unlikely but
 *     cheap to guard against).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Image_Renamer {

    public const META_ORIGINAL    = '_cmc_img_original_filename';
    public const META_ORIGINAL_ALT = '_cmc_img_original_alt';
    public const META_RENAMED_AT  = '_cmc_img_renamed_at';
    public const META_PRODUCT_SEQ = '_cmc_img_product_seq';
    public const META_BASE_SLUG   = '_cmc_img_base_slug';
    public const OPT_SEQ_COUNTER  = 'cmc_img_product_seq_counter';
    public const CLASS_VERSION    = '0.9.10-disk-check-precedes-renamed-flag';

    /**
     * Wire runtime self-healing filters. Called once from CMC_Plugin::register_hooks.
     */
    public static function init(): void {
        // Auto-fix attachment metadata after import. Runs in BOTH admin and
        // front-end contexts because Woo POD Master V2 (and similar import
        // tools) often run from admin-ajax.php while WP-CLI background jobs
        // hit the front-end bootstrap. Priority 9999 ensures we run AFTER
        // the importer's own metadata-generation pass — we only intervene
        // when the metadata it produced is empty / zeroed.
        add_action( 'add_attachment', [ self::class, 'auto_fix_attachment_metadata' ], 9999, 1 );

        // Front-end only — the admin media library uses these URLs to LOAD
        // thumbnails, but rewriting there could mask underlying corruption
        // and break the "Edit Image" flow.
        if ( is_admin() ) {
            return;
        }

        // 1) wp_get_attachment_image_src('full') is the source of truth for
        //    WC's gallery <a href> AND PhotoSwipe's full-size load. When the
        //    URL it resolves to via `_wp_attached_file` no longer exists on
        //    disk (post-rename DB drift), redirect to the largest available
        //    size variant — and synchronously repair the postmeta so the
        //    next request avoids the filter altogether.
        add_filter( 'wp_get_attachment_image_src', [ self::class, 'self_heal_image_src' ], 5, 4 );

        // 2) Backstop: rewrite any dead URLs that survive in the final
        //    rendered gallery HTML. Catches PhotoSwipe captions, theme
        //    overrides, and any hard-coded data-* attribute whose URL is
        //    not derived from wp_get_attachment_image_src.
        add_filter( 'woocommerce_single_product_image_thumbnail_html', [ self::class, 'self_heal_gallery_html' ], 99, 2 );
    }

    /**
     * On every freshly created attachment, sanity-check its metadata and
     * re-generate it from the file on disk if the importer left it empty
     * or zeroed. Without this guard, Woo POD Master V2 imports leave
     * `_wp_attachment_metadata` as either an empty array or a row with
     * `width=0, height=0, sizes=[]` — WordPress then renders
     * `<img width="0" height="0">` and the browser flags the image as
     * "Could not load image" in DevTools even though the file is on disk
     * and serves HTTP 200. Regenerating dimensions + size variants
     * matches what `wp_generate_attachment_metadata()` does (the same
     * function the Regenerate Thumbnails plugin uses) so the fix is
     * exactly equivalent to running that plugin per-attachment.
     *
     * Guard rails:
     *   - Skip non-image mime types (PDFs / videos generate metadata too,
     *     but with completely different shape — leave them alone).
     *   - Skip when the on-disk file is missing — re-generation would
     *     fail and the importer probably hasn't finished writing yet.
     *   - Skip when metadata already has positive width / height AND
     *     a populated `sizes` array — it's intact.
     *   - Always swallow regen errors silently; this is best-effort
     *     hygiene, not a hard requirement.
     *
     * @param int $attachment_id  Newly inserted attachment post ID.
     */
    public static function auto_fix_attachment_metadata( $attachment_id ): void {
        $aid = (int) $attachment_id;
        if ( $aid <= 0 ) {
            return;
        }
        $mime = (string) get_post_mime_type( $aid );
        if ( strpos( $mime, 'image/' ) !== 0 ) {
            return;
        }

        $file = get_attached_file( $aid );
        if ( ! is_string( $file ) || $file === '' || ! self::path_really_exists( $file ) ) {
            return;
        }

        $meta = wp_get_attachment_metadata( $aid );
        if ( self::metadata_looks_intact( is_array( $meta ) ? $meta : [] ) ) {
            return;
        }

        // wp_generate_attachment_metadata() needs the admin image-editing
        // helpers; load on demand for the front-end / Woo POD AJAX path.
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        try {
            $new_meta = wp_generate_attachment_metadata( $aid, $file );
        } catch ( \Throwable $t ) {
            return;
        }

        // Save regen result if it has at least file + non-zero dimensions.
        // sizes[] can legitimately be empty when the source is smaller than
        // every registered intermediate size (Woo POD often imports the
        // 300x300 thumbnail as the original) — refusing to save in that
        // case leaves meta['file'] without its YYYY/MM prefix and srcset
        // URLs keep 404ing.
        if ( is_array( $new_meta )
             && ! empty( $new_meta['file'] )
             && (int) ( $new_meta['width']  ?? 0 ) > 0
             && (int) ( $new_meta['height'] ?? 0 ) > 0 ) {
            wp_update_attachment_metadata( $aid, $new_meta );
            clean_post_cache( $aid );
        }
    }

    /**
     * True when an attachment's metadata array carries a non-zero width,
     * a non-zero height, AND at least one entry in `sizes`. Used by the
     * `add_attachment` auto-fix and by `repair_metadata_paths()` to
     * decide whether a regenerate is worthwhile.
     */
    private static function metadata_looks_intact( array $meta ): bool {
        $w = (int) ( $meta['width']  ?? 0 );
        $h = (int) ( $meta['height'] ?? 0 );
        if ( $w <= 0 || $h <= 0 ) {
            return false;
        }
        if ( empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) {
            return false;
        }
        return true;
    }

    /**
     * Filter callback for `wp_get_attachment_image_src`. Detects a
     * full-size URL pointing at a missing file and redirects to the
     * largest size variant whose file actually exists on disk.
     *
     * @param array|false $image
     * @param int|string  $attachment_id
     * @param string|int[]$size
     * @param bool        $icon
     * @return array|false
     */
    public static function self_heal_image_src( $image, $attachment_id, $size, $icon ) {
        if ( ! is_array( $image ) || empty( $image[0] ) ) {
            return $image;
        }
        if ( $size !== 'full' && $size !== 'large' ) {
            return $image;
        }

        $uploads = wp_get_upload_dir();
        if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
            return $image;
        }
        $baseurl = trailingslashit( (string) $uploads['baseurl'] );
        $basedir = trailingslashit( (string) $uploads['basedir'] );

        $url = (string) $image[0];
        if ( strpos( $url, $baseurl ) !== 0 ) {
            return $image;
        }
        $rel = substr( $url, strlen( $baseurl ) );
        $abs = $basedir . $rel;

        // Live URL — nothing to heal.
        if ( self::path_really_exists( $abs ) ) {
            return $image;
        }

        // Try DB-level recovery first: when sizes['*']['file'] are NEW but
        // `_wp_attached_file` is OLD, rewrite the postmeta and re-resolve.
        $aid = (int) $attachment_id;
        if ( $aid > 0 && self::recover_attached_file_from_sizes( $aid ) ) {
            $new_relative = (string) get_post_meta( $aid, '_wp_attached_file', true );
            if ( $new_relative !== '' ) {
                $new_abs = $basedir . $new_relative;
                if ( self::path_really_exists( $new_abs ) ) {
                    $meta = wp_get_attachment_metadata( $aid );
                    $w = (int) ( $meta['width']  ?? 0 );
                    $h = (int) ( $meta['height'] ?? 0 );
                    return [ $baseurl . $new_relative, $w, $h, false ];
                }
            }
        }

        // Fallback: pick the largest size variant that exists on disk.
        $metadata = wp_get_attachment_metadata( $aid );
        if ( ! is_array( $metadata ) || empty( $metadata['sizes'] ) ) {
            return $image;
        }
        $subdir   = trim( dirname( $rel ), '/\\' );
        $subdir_r = ( $subdir === '' || $subdir === '.' ) ? '' : $subdir . '/';

        $best_url   = '';
        $best_w     = 0;
        $best_h     = 0;
        $best_area  = 0;
        foreach ( $metadata['sizes'] as $size_data ) {
            if ( ! is_array( $size_data ) || empty( $size_data['file'] ) ) {
                continue;
            }
            $candidate = $basedir . $subdir_r . (string) $size_data['file'];
            if ( ! self::path_really_exists( $candidate ) ) {
                continue;
            }
            $w    = (int) ( $size_data['width']  ?? 0 );
            $h    = (int) ( $size_data['height'] ?? 0 );
            $area = $w * $h;
            if ( $area >= $best_area ) {
                $best_area = $area;
                $best_url  = $baseurl . $subdir_r . (string) $size_data['file'];
                $best_w    = $w;
                $best_h    = $h;
            }
        }
        if ( $best_url !== '' ) {
            return [ $best_url, $best_w, $best_h, false ];
        }
        return $image;
    }

    /**
     * Filter callback for `woocommerce_single_product_image_thumbnail_html`.
     * Final cleanup pass on the rendered gallery slide — fixes any URL
     * inside src / href / data-* attribute whose disk file is missing by
     * remapping to the live URL based on the slide's `wp-image-{ID}` class.
     */
    public static function self_heal_gallery_html( string $html, int $attachment_id ): string {
        if ( $html === '' || $attachment_id <= 0 ) {
            return $html;
        }
        $uploads = wp_get_upload_dir();
        if ( empty( $uploads['baseurl'] ) || empty( $uploads['basedir'] ) ) {
            return $html;
        }
        $baseurl = trailingslashit( (string) $uploads['baseurl'] );
        $basedir = trailingslashit( (string) $uploads['basedir'] );

        // Resolve the live full URL via the (already-self-healed) src filter.
        $full = wp_get_attachment_image_src( $attachment_id, 'full' );
        if ( ! is_array( $full ) || empty( $full[0] ) ) {
            return $html;
        }
        $live_full_url      = (string) $full[0];
        $live_full_basename = basename( parse_url( $live_full_url, PHP_URL_PATH ) ?: '' );
        $live_full_stem     = pathinfo( $live_full_basename, PATHINFO_FILENAME );
        $live_full_ext      = pathinfo( $live_full_basename, PATHINFO_EXTENSION );
        $live_full_core     = preg_replace( '/-scaled$/', '', $live_full_stem );
        $live_full_dir      = trailingslashit( dirname( $live_full_url ) );

        $metadata = wp_get_attachment_metadata( $attachment_id );

        // Find every uploads URL inside the rendered slide HTML and probe
        // each one. Rewrite any dead URL using the live full URL (size
        // suffix preserved when present).
        $pattern = '#' . preg_quote( $baseurl, '#' )
                 . '[^\s"\'<>\\\\]+?\.(?:jpe?g|png|gif|webp)#i';
        if ( ! preg_match_all( $pattern, $html, $m ) ) {
            return $html;
        }

        $replacements = [];
        foreach ( array_unique( $m[0] ) as $url ) {
            $rel = substr( $url, strlen( $baseurl ) );
            $abs = $basedir . $rel;
            if ( self::path_really_exists( $abs ) ) {
                continue;
            }

            $basename = basename( $rel );
            $stem     = pathinfo( $basename, PATHINFO_FILENAME );
            $ext      = pathinfo( $basename, PATHINFO_EXTENSION );
            if ( $stem === '' || $ext === '' ) {
                continue;
            }

            // If URL has a -WxH size suffix, find a matching live size
            // variant; otherwise fall back to the live full URL.
            if ( preg_match( '/(-(\d+)x(\d+))$/', $stem, $sm ) ) {
                $size_suffix = $sm[1];
                $new_url     = $live_full_dir . $live_full_core . $size_suffix . '.' . $live_full_ext;
                $new_rel     = ltrim( substr( $new_url, strlen( $baseurl ) ), '/' );
                if ( ! self::path_really_exists( $basedir . $new_rel )
                  && is_array( $metadata ) && ! empty( $metadata['sizes'] ) ) {
                    // Live URL doesn't have this size — pick the largest
                    // available variant instead so the slide still loads.
                    $w_target = (int) $sm[2];
                    $h_target = (int) $sm[3];
                    $best     = '';
                    $best_diff = PHP_INT_MAX;
                    foreach ( $metadata['sizes'] as $size_data ) {
                        if ( empty( $size_data['file'] ) ) { continue; }
                        $cand_abs = $basedir . trailingslashit( dirname( $live_full_url === '' ? '' : substr( $live_full_url, strlen( $baseurl ) ) ) ) . $size_data['file'];
                        if ( ! self::path_really_exists( $cand_abs ) ) { continue; }
                        $diff = abs( ( (int) ( $size_data['width'] ?? 0 ) ) - $w_target )
                              + abs( ( (int) ( $size_data['height'] ?? 0 ) ) - $h_target );
                        if ( $diff < $best_diff ) {
                            $best_diff = $diff;
                            $best      = $live_full_dir . $size_data['file'];
                        }
                    }
                    if ( $best !== '' ) {
                        $new_url = $best;
                    } else {
                        $new_url = $live_full_url;
                    }
                }
            } else {
                $new_url = $live_full_url;
            }

            if ( $new_url !== '' && $new_url !== $url ) {
                $replacements[ $url ] = $new_url;
            }
        }

        if ( empty( $replacements ) ) {
            return $html;
        }
        return strtr( $html, $replacements );
    }

    /**
     * Collect every product in a category (optionally including descendants)
     * and report how many attachments each product has that are still
     * eligible for rename. Read-only.
     *
     * @return array{
     *     term: array{id:int, name:string, slug:string},
     *     include_subcats: bool,
     *     products: list<array{id:int, title:string, slug:string, images:int, already:int, shared:int}>,
     *     totals: array{products:int, images:int, already:int, shared:int}
     * }
     */
    public static function scan_category( int $term_id, bool $include_subcats ): array {
        $term = get_term( $term_id, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) {
            return [
                'term'            => [ 'id' => 0, 'name' => '', 'slug' => '' ],
                'include_subcats' => $include_subcats,
                'products'        => [],
                'totals'          => [ 'products' => 0, 'images' => 0, 'already' => 0, 'shared' => 0, 'missing' => 0 ],
                'renamer_version' => self::CLASS_VERSION,
            ];
        }

        $product_ids = self::product_ids_in_term( $term_id, $include_subcats );

        $products = [];
        $t_images = 0;
        $t_already = 0;
        $t_shared = 0;
        $t_missing = 0;

        foreach ( $product_ids as $pid ) {
            $p = get_post( $pid );
            if ( ! $p ) { continue; }

            $attachments = self::collect_product_attachments( $pid );

            $images  = 0;
            $already = 0;
            $shared  = 0;
            $missing = 0;

            foreach ( $attachments as $aid ) {
                $status = self::attachment_status( $aid, $pid );
                if ( $status === 'renamable' ) { $images++; }
                if ( $status === 'renamed'   ) { $already++; }
                if ( $status === 'shared'    ) { $shared++; }
                if ( $status === 'missing'   ) { $missing++; }
            }

            $products[] = [
                'id'      => (int) $pid,
                'title'   => (string) $p->post_title,
                'slug'    => (string) $p->post_name,
                'images'  => $images,
                'already' => $already,
                'shared'  => $shared,
                'missing' => $missing,
            ];

            $t_images  += $images;
            $t_already += $already;
            $t_shared  += $shared;
            $t_missing += $missing;
        }

        return [
            'term' => [
                'id'   => (int) $term->term_id,
                'name' => (string) $term->name,
                'slug' => (string) $term->slug,
            ],
            'include_subcats' => $include_subcats,
            'products'        => $products,
            'totals'          => [
                'products' => count( $products ),
                'images'   => $t_images,
                'already'  => $t_already,
                'shared'   => $t_shared,
                'missing'  => $t_missing,
            ],
            'renamer_version' => self::CLASS_VERSION,
        ];
    }

    /**
     * Rename every eligible attachment for one product. Images already
     * renamed or shared with other products are skipped silently.
     *
     * @return array{
     *     id:int, title:string, slug:string,
     *     renamed:int, skipped:int, errors:list<string>,
     *     log:list<array{aid:int, from:string, to:string, note:string}>
     * }
     */
    public static function rename_product( int $product_id ): array {
        $p = get_post( $product_id );
        if ( ! $p || $p->post_type !== 'product' ) {
            return [
                'id'      => $product_id,
                'title'   => '',
                'slug'    => '',
                'renamed' => 0,
                'skipped' => 0,
                'errors'  => [ 'Product not found.' ],
                'log'     => [],
            ];
        }

        // GMC-safe naming pattern (V0.9): filenames + ALT text both derive
        // from the product TITLE (post_name slug). Featured image uses the
        // bare slug; gallery images get `-2`, `-3`, … suffixes; variation
        // thumbnails get `-v1`, `-v2`, … suffixes. ALT text mirrors the
        // human-readable title in the same role pattern. The product title
        // is assumed to have already been rewritten clean of brand tokens
        // (the user's title rewriter does this on import); if any brand
        // remains, it would propagate to filename + ALT, but that is a
        // title-cleansing concern, not an image-rename concern.
        $base = self::ensure_product_base_slug( $product_id );
        if ( $base === '' ) {
            return [
                'id'      => $product_id,
                'title'   => (string) $p->post_title,
                'slug'    => '',
                'renamed' => 0,
                'skipped' => 0,
                'errors'  => [ 'Product has no usable post_name or post_title — cannot derive image base slug.' ],
                'log'     => [],
            ];
        }

        // Build a target-slug AND role map per attachment. The role drives
        // ALT-text generation (featured / gallery #N / variation #N).
        // Same image used as featured + gallery is renamed once and gets
        // the featured role (featured wins over gallery and variation).
        $featured_id   = (int) get_post_thumbnail_id( $product_id );
        $gallery_ids   = self::gallery_ids( $product_id );
        $variation_map = self::collect_variation_thumbs( $product_id );

        $targets = [];
        $roles   = [];
        if ( $featured_id > 0 ) {
            $targets[ $featured_id ] = $base;
            $roles[ $featured_id ]   = [ 'type' => 'featured', 'index' => 0 ];
        }
        $img_seq = 2;
        foreach ( $gallery_ids as $gid ) {
            if ( ! isset( $targets[ $gid ] ) ) {
                $targets[ $gid ] = $base . '-' . $img_seq;
                $roles[ $gid ]   = [ 'type' => 'gallery', 'index' => $img_seq ];
                $img_seq++;
            }
        }
        foreach ( $variation_map as $vid => $var_idx ) {
            if ( ! isset( $targets[ $vid ] ) ) {
                $targets[ $vid ] = $base . '-v' . $var_idx;
                $roles[ $vid ]   = [ 'type' => 'variation', 'index' => $var_idx ];
            }
        }

        $reserved = [];
        $renamed  = 0;
        $synced   = 0;
        $skipped  = 0;
        $errors   = [];
        $log      = [];

        foreach ( $targets as $aid => $target_slug ) {
            $aid    = (int) $aid;
            $role   = $roles[ $aid ] ?? [ 'type' => 'gallery', 'index' => 0 ];
            $status = self::attachment_status( $aid, $product_id );

            // Inconsistent-state recovery: a previous rename pass may have
            // moved the size variants on disk + updated metadata['sizes']
            // but failed to persist `_wp_attached_file`. The old random-
            // suffix file is gone, so attachment_status returns 'missing'
            // and the old logic would skip the row forever, leaving
            // `wp_get_attachment_image_src($aid, 'full')` (which feeds
            // <a href> + data-large_image) returning the dead old URL
            // while srcset shows the live new size variants.
            //
            // Detect this exact mismatch and recover by deriving the true
            // current basename from metadata['sizes'][...]['file'] and
            // writing it back to `_wp_attached_file` + metadata['file'].
            if ( $status === 'missing' && self::recover_attached_file_from_sizes( $aid ) ) {
                $status = self::attachment_status( $aid, $product_id );
            }

            // Skip only TRULY unrenamable rows: missing file, non-attachment.
            // 'shared' (post_parent != current product) used to be skipped to
            // protect cross-post references, but in cloning workflows the
            // import process sets post_parent inconsistently — featured-image
            // attachments end up parented to the wrong product (or to 0) and
            // the gallery references survive only in `_product_image_gallery`
            // postmeta. Skipping 'shared' leaves those attachments at their
            // original V0 long-form filename forever, so `<a href>` /
            // `data-large_image` / `data-o_data-large_image` keep returning
            // the dead long URL even after the user runs Rename. We now
            // proceed for 'shared' too — the rename only mutates ONE
            // attachment row + its disk file, and the post_content URL sweep
            // updates references uniformly across every post that points at
            // this attachment. Other products that legitimately share the
            // image will have their references rewritten by the same sweep.
            if ( ! in_array( $status, [ 'renamable', 'renamed', 'shared' ], true ) ) {
                $skipped++;
                $log[] = [
                    'aid'  => $aid,
                    'from' => (string) get_post_meta( $aid, '_wp_attached_file', true ),
                    'to'   => '',
                    'note' => $status,
                ];
                continue;
            }

            // Compare the CURRENT on-disk basename core (without size /
            // -scaled suffixes) to the freshly-derived TARGET slug. Equal
            // → no file move, just refresh ALT. Different → run a forced
            // rename so the filename catches up with the latest product
            // title.
            $current_relative = (string) get_post_meta( $aid, '_wp_attached_file', true );
            $current_basename = $current_relative !== '' ? basename( $current_relative ) : '';
            $current_stem     = $current_basename !== '' ? pathinfo( $current_basename, PATHINFO_FILENAME ) : '';
            $current_core     = $current_stem !== '' ? preg_replace( '/-scaled$/', '', $current_stem ) : '';

            if ( $current_core === $target_slug && $current_core !== '' ) {
                // Filename already matches the latest title — but the
                // override pass must still (a) refresh ALT, (b) sweep DB
                // for any lingering references to OLD filenames pre-V0.9
                // (they live in postmeta blobs, theme builders, options
                // rows; an in-sync product looks "skipped" otherwise but
                // its DB may still leak the original brand-laden URL).
                self::update_attachment_alt( $aid, $product_id, $role );
                $sweep_hits = self::sweep_legacy_urls( $aid, $current_relative );

                $synced++;
                $log[] = [
                    'aid'  => $aid,
                    'from' => $current_relative,
                    'to'   => '',
                    'note' => $sweep_hits > 0
                        ? sprintf( 'in sync (alt refreshed · %d legacy URL refs swept)', $sweep_hits )
                        : 'in sync (alt refreshed)',
                ];
                continue;
            }

            // File rename needed. force_exact=true when the row is
            // already in 'renamed' state — that path writes the target
            // slug verbatim AND skips the META_ORIGINAL re-write, so the
            // pre-V0.9 original filename remains the canonical revert
            // target across multiple override passes.
            $force = ( $status === 'renamed' );
            $res   = self::rename_attachment( $aid, (string) $target_slug, $reserved, $force );
            if ( is_wp_error( $res ) ) {
                $errors[] = sprintf( '#%d: %s', $aid, $res->get_error_message() );
                $log[]    = [
                    'aid'  => $aid,
                    'from' => $current_relative,
                    'to'   => '',
                    'note' => 'ERROR: ' . $res->get_error_message(),
                ];
                continue;
            }

            // Rename succeeded — refresh ALT + attachment post fields.
            self::update_attachment_alt( $aid, $product_id, $role );

            $renamed++;
            $log[] = [
                'aid'  => $aid,
                'from' => (string) $res['from'],
                'to'   => (string) $res['to'],
                'note' => ( $force ? 'override-renamed' : 'renamed' )
                       . ( $res['content_hits'] > 0
                           ? sprintf( ' · updated %d post_content refs', $res['content_hits'] )
                           : '' ),
            ];
        }

        // PRODUCT-LEVEL MASTER SWEEP: after handling every attachment
        // individually, run ONE more DB sweep that combines the
        // legacy→current URL maps of EVERY attachment in this product.
        // The per-attachment sweep replaces references to attachment X's
        // old URL only when we are processing attachment X — but a
        // product's post_content / postmeta / options can reference
        // attachment Y's old URL from inside a render that ran while we
        // were processing X. The master sweep catches those cross-
        // attachment lingerers in one pass. This is what finally clears
        // `data-large_image` / `data-o_data-large_image` URLs that point
        // to a different gallery item's old random-suffix filename.
        $master_map = [];
        foreach ( $targets as $aid_loop => $_ ) {
            $aid_loop         = (int) $aid_loop;
            $current_relative = (string) get_post_meta( $aid_loop, '_wp_attached_file', true );
            $partial          = self::build_legacy_url_map( $aid_loop, $current_relative );
            foreach ( $partial as $old => $new ) {
                if ( ! isset( $master_map[ $old ] ) ) {
                    $master_map[ $old ] = $new;
                }
            }
        }
        $master_hits = $master_map ? self::update_post_content_urls( $master_map ) : 0;

        // DEEP CONTENT SWEEP — repairs URLs that survive the master sweep.
        // The master sweep is built from attachments in `$targets` (featured
        // + gallery + variation). It MISSES URLs that live in post_content /
        // postmeta but reference attachments NOT in $targets:
        //   - Inline images inserted via the editor's "Add Media" button
        //     into the product description.
        //   - Shortcode / page-builder attributes that hard-code an image
        //     URL verbatim instead of resolving it through an attachment ID.
        //   - Cross-product references where Product B's description embeds
        //     a `<img>` for an attachment owned by Product A.
        // When Product A's rename moved the file on disk, those V0 URLs in
        // Product B's post_content become 404s. PhotoSwipe (Flatsome's
        // lightbox) reads the gallery `<a href>` to load the full image,
        // and a 404 there shows up as "The image could not be loaded".
        //
        // Strategy: scan post_content for every uploads URL, probe disk for
        // each one, and for any URL whose file is GONE find the owning
        // attachment via the `_cmc_img_original_filename` index → swap to
        // its live URL.
        $deep_hits   = self::deep_content_sweep( $product_id );
        $master_hits += $deep_hits;

        // Bust WooCommerce's per-product cache so `data-product_variations`
        // on the single-product form rebuilds with the renamed image URLs.
        // Done unconditionally because the master sweep + deep sweep can
        // mutate URLs (cross-attachment / cross-product references) even
        // on a pass where no individual attachment was renamed this round.
        self::flush_woo_caches_for_products( [ $product_id ] );

        // Mark Yoast SEO's indexable row stale so the og:image / twitter
        // image URLs rebuild from the attachment ID on the next request
        // — catches the edge case where the indexable holds an INTERMEDIATE
        // URL that the string-replace sweep cannot match (e.g. the second
        // rename pass after a title rewrite). No-op without Yoast.
        self::invalidate_yoast_indexables_for_products( [ $product_id ] );

        // After the rename / ALT-refresh / master sweep pass, ask the
        // surrounding cache layers to drop their copy of the rendered
        // single-product page. Without this the site keeps serving HTML
        // captured before the rename — `<a href>`, `data-src`,
        // `data-large_image`, and theme lazy-load `data-o_*` attributes
        // all freeze on the cached output. We trigger the well-known
        // purge hooks for the major page-cache plugins; each one
        // gracefully no-ops when its plugin is not installed.
        if ( $renamed + $synced + $skipped > 0 ) {
            self::purge_external_caches();
        }

        return [
            'id'           => $product_id,
            'title'        => (string) $p->post_title,
            'slug'         => $base,
            'renamed'      => $renamed,
            'synced'       => $synced,
            'skipped'      => $skipped,
            'master_hits'  => $master_hits,
            'errors'       => $errors,
            'log'          => $log,
        ];
    }

    /**
     * Build a `[old_url => new_url]` map covering an attachment's main
     * file plus every size variant — used by the per-attachment sweep
     * AND the product-level master sweep at end of rename_product().
     *
     * Returns an empty array when nothing needs sweeping (no original on
     * record, or current filename already matches the original).
     *
     * @return array<string,string>
     */
    private static function build_legacy_url_map( int $aid, string $current_relative ): array {
        $original_basename = (string) get_post_meta( $aid, self::META_ORIGINAL, true );
        if ( $original_basename === '' || $current_relative === '' ) {
            return [];
        }
        $current_basename = basename( $current_relative );
        if ( $original_basename === $current_basename ) {
            return [];
        }

        $uploads = wp_get_upload_dir();
        if ( ! empty( $uploads['error'] ) ) {
            return [];
        }
        $baseurl = trailingslashit( (string) $uploads['baseurl'] );
        $subdir  = trim( dirname( $current_relative ), '/\\' );
        if ( $subdir === '.' ) { $subdir = ''; }
        $subdir_rel = $subdir === '' ? '' : $subdir . '/';

        // Cores (without -scaled / extension) for prefix-replacement on
        // size variants whose names follow `{core}-WxH.ext`.
        $original_stem = pathinfo( $original_basename, PATHINFO_FILENAME );
        $original_core = preg_replace( '/-scaled$/', '', $original_stem );
        $current_stem  = pathinfo( $current_basename,  PATHINFO_FILENAME );
        $current_core  = preg_replace( '/-scaled$/', '', $current_stem );

        $url_map = [];
        $url_map[ $baseurl . $subdir_rel . $original_basename ] = $baseurl . $subdir_rel . $current_basename;

        $metadata = (array) wp_get_attachment_metadata( $aid );
        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            foreach ( $metadata['sizes'] as $info ) {
                if ( ! is_array( $info ) || empty( $info['file'] ) ) { continue; }
                $current_size_basename  = (string) $info['file'];
                $original_size_basename = self::replace_filename_prefix(
                    $current_size_basename,
                    $current_core,
                    $original_core
                );
                if ( $original_size_basename === $current_size_basename ) {
                    continue;
                }
                $url_map[ $baseurl . $subdir_rel . $original_size_basename ] = $baseurl . $subdir_rel . $current_size_basename;
            }
        }

        return $url_map;
    }

    /**
     * Sweep the DB for references to ONE attachment's pre-V0.9 (or any
     * earlier) filename and rewrite them to its current filename.
     *
     * Called from rename_product()'s "in sync" branch — when the file
     * itself does not need to move, we still want every post_content,
     * postmeta, and options reference to the ORIGINAL filename to be
     * pulled forward to the current filename.
     *
     * @return int  Total replacements made across all DB rows.
     */
    private static function sweep_legacy_urls( int $aid, string $current_relative ): int {
        $map = self::build_legacy_url_map( $aid, $current_relative );
        return $map ? self::update_post_content_urls( $map ) : 0;
    }

    /**
     * Scan ONE product's post_content + postmeta for uploads URLs whose
     * disk file is missing, locate the owning attachment by V0 basename
     * lookup (`_cmc_img_original_filename`), and rewrite the dead URL to
     * the attachment's live URL.
     *
     * Targets the PhotoSwipe-empty-popup case: inline `<img>` / `<a>`
     * embedded in the product description point at attachments that have
     * been renamed by some OTHER product's pass, so the V0 file is gone
     * from disk while the URL survives in post_content. Browsers render
     * the size-variant `<img src>` (those URLs come from postmeta the
     * renamer DOES update), but the lightbox `<a href>` taken from the
     * V0 URL embedded in post_content 404s.
     *
     * Robust to:
     *   - URLs with `-WxH` size suffix (intermediate sizes).
     *   - URLs with `-scaled` suffix.
     *   - URLs whose attachment was renamed multiple times (we always
     *     resolve to the CURRENT _wp_attached_file).
     */
    private static function deep_content_sweep( int $product_id ): int {
        global $wpdb;

        $content = (string) get_post_field( 'post_content', $product_id );
        if ( $content === '' ) {
            return 0;
        }

        $uploads = wp_get_upload_dir();
        if ( ! empty( $uploads['error'] ) ) {
            return 0;
        }
        $baseurl = trailingslashit( (string) ( $uploads['baseurl'] ?? '' ) );
        $basedir = trailingslashit( (string) ( $uploads['basedir'] ?? '' ) );
        if ( $baseurl === '/' || $basedir === '/' ) {
            return 0;
        }

        // Match every uploads URL ending in a known image extension. Stop at
        // whitespace, quote, angle bracket, or backslash so we don't gobble
        // surrounding markup.
        $pattern = '#' . preg_quote( $baseurl, '#' )
                 . '[^\s"\'<>\\\\]+?\.(?:jpe?g|png|gif|webp)#i';
        if ( ! preg_match_all( $pattern, $content, $m ) ) {
            return 0;
        }

        $url_map = [];
        $seen    = [];
        foreach ( $m[0] as $url ) {
            if ( isset( $seen[ $url ] ) ) {
                continue;
            }
            $seen[ $url ] = true;

            $rel = ltrim( substr( $url, strlen( $baseurl ) ), '/' );
            $abs = $basedir . $rel;

            // Live URL — file is on disk. Nothing to rewrite.
            if ( self::path_really_exists( $abs ) ) {
                continue;
            }

            $basename = basename( $rel );
            $stem     = pathinfo( $basename, PATHINFO_FILENAME );
            $ext      = pathinfo( $basename, PATHINFO_EXTENSION );
            if ( $stem === '' || $ext === '' ) {
                continue;
            }

            // Capture the size suffix (if any) BEFORE stripping it, so we
            // can apply the same size to the rewritten URL.
            $size_suffix = '';
            if ( preg_match( '/(-\d+x\d+)$/', $stem, $sm ) ) {
                $size_suffix = $sm[1];
            }

            // Reduce to the V0 core: drop -WxH and -scaled. Both forms are
            // candidates for the META_ORIGINAL lookup since the renamer
            // stored the V0 main filename — never a size-variant filename.
            $core = preg_replace( '/-\d+x\d+$/', '', $stem );
            $core = preg_replace( '/-scaled$/',  '', $core );
            if ( $core === '' ) {
                continue;
            }

            $candidate_basenames = array_values( array_unique( [
                $basename,                         // exact (covers no-size case)
                $core . '.' . $ext,                // V0 main file
                $core . '-scaled.' . $ext,         // V0 -scaled variant
            ] ) );
            $placeholders = implode( ',', array_fill( 0, count( $candidate_basenames ), '%s' ) );

            $params = array_merge( [ self::META_ORIGINAL ], $candidate_basenames );
            $aid    = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = %s AND meta_value IN ($placeholders)
                 LIMIT 1",
                $params
            ) );
            if ( $aid <= 0 ) {
                continue;
            }

            $current_relative = (string) get_post_meta( $aid, '_wp_attached_file', true );
            if ( $current_relative === '' ) {
                continue;
            }

            $current_basename = basename( $current_relative );
            $current_stem     = pathinfo( $current_basename, PATHINFO_FILENAME );
            $current_ext      = pathinfo( $current_basename, PATHINFO_EXTENSION );
            $current_core     = preg_replace( '/-scaled$/', '', $current_stem );
            $current_subdir   = trim( dirname( $current_relative ), '/\\' );
            $current_subdir_r = ( $current_subdir === '' || $current_subdir === '.' )
                ? ''
                : $current_subdir . '/';

            // Reconstruct the live URL: keep the original size suffix when
            // present, otherwise resolve to the current main filename.
            if ( $size_suffix !== '' ) {
                $new_basename = $current_core . $size_suffix . '.' . $current_ext;
            } else {
                $new_basename = $current_basename;
            }
            $new_url = $baseurl . $current_subdir_r . $new_basename;

            if ( $new_url !== $url ) {
                $url_map[ $url ] = $new_url;
            }
        }

        if ( empty( $url_map ) ) {
            return 0;
        }
        return self::update_post_content_urls( $url_map );
    }

    /**
     * Bust WooCommerce's per-product caches after an image rename so the
     * `<form data-product_variations="...">` JSON on the single-product
     * page rebuilds with the new image URLs.
     *
     * Why this is needed beyond the post_content / postmeta / options
     * sweep: WC builds `data-product_variations` from
     * `WC_Product_Variable::get_available_variations()`, which resolves
     * `image_src` / `full_src` / `srcset` per variation through cached
     * variation objects living in WC's `product_variation_meta` cache
     * group (keyed by the `product_<id>` cache prefix) and per-product
     * transients (`wc_var_prices_*`, `wc_product_children_*`, ...). Those
     * caches survive a `_wp_attached_file` rewrite — so a freshly-loaded
     * single-product page renders with a MIX of new (cache-miss
     * variations) and old (cache-hit variations) URLs.
     *
     * What we flush, per product:
     *   - `wc_delete_product_transients()` — clears wc_var_prices_*,
     *     wc_product_children_*, related-products transients, AND bumps
     *     `WC_Cache_Helper::invalidate_cache_group( 'product_<id>' )`
     *     internally so every cached variation_attributes /
     *     product_variation_meta entry stales out.
     *   - `clean_post_cache( $product_id )` — drops `posts` /
     *     `post_meta` cache for the parent product so WC re-reads
     *     `_thumbnail_id` / `_product_image_gallery` on the next request.
     *   - For each variation child: `clean_post_cache( $variation_id )`
     *     + `wp_cache_delete( $variation_id, 'attachment_url' )` so the
     *     variation's image_src builds from the up-to-date attachment
     *     URL, not a stale `wp_get_attachment_url()` cache hit.
     *
     * No-ops cleanly when WooCommerce is not installed (e.g. on a
     * non-Woo WP install that still uses this plugin for page cloning).
     *
     * @param array<int> $product_ids  Product IDs to flush. Duplicates / non-products are filtered.
     * @return list<int>               The product IDs that were actually flushed.
     */
    public static function flush_woo_caches_for_products( array $product_ids ): array {
        $product_ids = array_values( array_unique( array_filter(
            array_map( 'intval', $product_ids ),
            static fn( int $id ): bool => $id > 0
        ) ) );
        if ( empty( $product_ids ) ) { return []; }

        $flushed = [];
        foreach ( $product_ids as $pid ) {
            // Guard: only flush rows that are really products. Avoids
            // touching attachment / page IDs that may slip in from a
            // post_content sweep.
            $ptype = get_post_type( $pid );
            if ( $ptype !== 'product' ) { continue; }

            // (1) WC's own bulk transient + cache-group cleaner. Internally
            //     handles wc_var_prices_*, wc_product_children_*, related,
            //     attribute counts, and the product cache-group prefix bump.
            if ( function_exists( 'wc_delete_product_transients' ) ) {
                wc_delete_product_transients( $pid );
            }

            // (2) Defensive belt + suspenders for sites where
            //     wc_delete_product_transients() is hooked / overridden:
            //     bump the product_<id> cache group prefix directly.
            if ( class_exists( '\\WC_Cache_Helper' )
                 && is_callable( [ '\\WC_Cache_Helper', 'invalidate_cache_group' ] ) ) {
                \WC_Cache_Helper::invalidate_cache_group( 'product_' . $pid );
            }

            // (3) Drop the parent product's post + post_meta cache.
            clean_post_cache( $pid );

            // (4) For each variation child: drop post cache + attachment_url
            //     cache so image_src / full_src rebuild from current state.
            $variation_ids = get_posts( [
                'post_type'      => 'product_variation',
                'post_parent'    => $pid,
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'suppress_filters' => true,
            ] );
            foreach ( (array) $variation_ids as $vid ) {
                $vid = (int) $vid;
                if ( $vid <= 0 ) { continue; }
                clean_post_cache( $vid );
                wp_cache_delete( $vid, 'post_meta' );
                wp_cache_delete( $vid, 'attachment_url' );
            }

            $flushed[] = $pid;
        }

        return $flushed;
    }

    /**
     * Force Yoast SEO to rebuild the open-graph + twitter image fields on
     * each product's indexable row at the next page render. Used after a
     * rename / revert so the `<meta property="og:image">` tag re-resolves
     * from the attachment ID (which now points at the new filename) instead
     * of replaying a stale URL string cached in the indexable table.
     *
     * Why this is needed in addition to update_yoast_indexable_urls():
     *   - The string-replace sweep only catches indexable rows whose stored
     *     og:image URL matches the URL build from META_ORIGINAL → current
     *     basename. After a SECOND rename pass (or a title rewrite that
     *     changes the slug), Yoast's row may be cached at an INTERMEDIATE
     *     URL that does not appear in url_map → string sweep misses it and
     *     the og:image tag keeps rendering the intermediate URL forever.
     *   - Yoast's Indexable_Builder is keyed off the `version` column —
     *     setting it to 0 marks the row as stale and triggers a rebuild
     *     on the next request that asks for this object's indexable.
     *     Rebuild walks back to attachment ID → resolves URL fresh.
     *
     * What we touch (all nullable / safe to clear): open_graph_image,
     * open_graph_image_id, open_graph_image_source, open_graph_image_meta,
     * twitter_image, twitter_image_source, version. Other Yoast fields
     * the user may have customised (description, focus keyword, canonical,
     * robots flags) are left untouched — Yoast's per-field rebuild logic
     * keeps them on the next pass.
     *
     * No-op when Yoast is not installed (table missing) or no IDs given.
     *
     * @param array<int> $product_ids
     * @return list<int>  IDs whose indexable row was actually invalidated.
     */
    public static function invalidate_yoast_indexables_for_products( array $product_ids ): array {
        global $wpdb;
        $product_ids = array_values( array_unique( array_filter(
            array_map( 'intval', $product_ids ),
            static fn( int $id ): bool => $id > 0
        ) ) );
        if ( empty( $product_ids ) ) { return []; }

        $table  = $wpdb->prefix . 'yoast_indexable';
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $wpdb->esc_like( $table )
        ) );
        if ( ! $exists ) { return []; }

        $invalidated = [];
        foreach ( $product_ids as $pid ) {
            $rows_affected = $wpdb->query( $wpdb->prepare(
                "UPDATE {$table}
                    SET open_graph_image        = NULL,
                        open_graph_image_id     = NULL,
                        open_graph_image_source = NULL,
                        open_graph_image_meta   = NULL,
                        twitter_image           = NULL,
                        twitter_image_source    = NULL,
                        version                 = 0
                  WHERE object_id   = %d
                    AND object_type = 'post'",
                $pid
            ) );
            if ( (int) $rows_affected > 0 ) {
                $invalidated[] = $pid;
                clean_post_cache( $pid );
            }
        }

        return $invalidated;
    }

    /**
     * Migrate every Yoast SEO indexable URL column from the old site host
     * to the current `home_url()` host. Solves the "schema-graph still
     * shows the source-site domain after a clone" problem: Yoast caches
     * each post / term / homepage permalink in the `wp_yoast_indexable`
     * table when the post is first viewed; that cache survives a DB
     * clone, so even though `home_url()` now reports the new domain,
     * the JSON-LD <script class="yoast-schema-graph"> emits @id /
     * url / breadcrumb entries pointing back at the source site.
     *
     * Strategy:
     *   1. Auto-detect the OLD host from the indexable table — pick the
     *      most common host across `permalink` rows that does NOT match
     *      the current site host. Avoids needing the operator to type
     *      the old domain.
     *   2. Build replacements for every common encoding the URL might
     *      appear in (raw, JSON-escaped slashes, URL-encoded slashes).
     *   3. REPLACE() across every URL-shaped column: `permalink`,
     *      `canonical`, `open_graph_url`, `twitter_image`,
     *      `open_graph_image`, `open_graph_image_source`,
     *      `open_graph_image_meta`, `twitter_image_source`.
     *   4. Bump `version = 0` on every touched row so Yoast's
     *      Indexable_Builder lazily rebuilds the rest of each row from
     *      the post's current state on the next render.
     *
     * No-ops when:
     *   - Yoast SEO is not installed (table missing).
     *   - No indexable row's host differs from the current host.
     *
     * @return array{detected_old_host:string, current_host:string, rows_updated:int}
     */
    public static function migrate_yoast_indexable_domain(): array {
        global $wpdb;

        $report = [
            'detected_old_host' => '',
            'current_host'      => '',
            'rows_updated'      => 0,
        ];

        $current_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        if ( $current_host === '' ) { return $report; }
        $report['current_host'] = $current_host;

        $table  = $wpdb->prefix . 'yoast_indexable';
        $exists = $wpdb->get_var( $wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $wpdb->esc_like( $table )
        ) );
        if ( ! $exists ) { return $report; }

        // (1) Auto-detect the dominant non-current host. We sample
        // permalinks across the table — sampling a few hundred rows is
        // enough to surface the source-site host even on stores with
        // tens of thousands of indexables. Cap the LIKE prefilter to
        // protect against pathological values in `permalink`.
        $rows = (array) $wpdb->get_col(
            "SELECT permalink FROM {$table}
              WHERE permalink <> ''
                AND permalink IS NOT NULL
              LIMIT 500"
        );
        $host_counts = [];
        foreach ( $rows as $perm ) {
            $h = (string) wp_parse_url( (string) $perm, PHP_URL_HOST );
            if ( $h === '' || $h === $current_host ) { continue; }
            $host_counts[ $h ] = ( $host_counts[ $h ] ?? 0 ) + 1;
        }
        if ( empty( $host_counts ) ) { return $report; } // already in sync
        arsort( $host_counts );
        $old_host = (string) array_key_first( $host_counts );
        if ( $old_host === '' || $old_host === $current_host ) { return $report; }
        $report['detected_old_host'] = $old_host;

        // (2) Build replacement variants. Yoast stores raw URLs in most
        // columns but JSON-escapes slashes inside `open_graph_image_meta`
        // (a wp_json_encode() blob). The URL-encoded form catches edge
        // cases like canonical entries that were sanitised through
        // esc_url().
        $variants = [
            [ 'https://' . $old_host, 'https://' . $current_host ],
            [ 'http://'  . $old_host, 'https://' . $current_host ],
            [ 'https:\/\/' . $old_host, 'https:\/\/' . $current_host ],
            [ 'http:\/\/'  . $old_host, 'https:\/\/' . $current_host ],
            [ 'https%3A%2F%2F' . $old_host, 'https%3A%2F%2F' . $current_host ],
        ];

        $columns = [
            'permalink',
            'canonical',
            'open_graph_url',
            'open_graph_image',
            'open_graph_image_source',
            'open_graph_image_meta',
            'twitter_image',
            'twitter_image_source',
        ];

        $touched_ids = [];
        foreach ( $variants as [ $needle, $replacement ] ) {
            foreach ( $columns as $col ) {
                // Probe column existence so the migration survives an
                // older Yoast install that hasn't run all migrations.
                $col_exists = $wpdb->get_var( $wpdb->prepare(
                    "SHOW COLUMNS FROM {$table} LIKE %s",
                    $col
                ) );
                if ( ! $col_exists ) { continue; }

                $ids = (array) $wpdb->get_col( $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE {$col} LIKE %s",
                    '%' . $wpdb->esc_like( $needle ) . '%'
                ) );
                foreach ( $ids as $iid ) {
                    $iid = (int) $iid;
                    if ( $iid > 0 ) { $touched_ids[ $iid ] = true; }
                }

                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$table}
                        SET {$col} = REPLACE({$col}, %s, %s)
                      WHERE {$col} LIKE %s",
                    $needle,
                    $replacement,
                    '%' . $wpdb->esc_like( $needle ) . '%'
                ) );
                $report['rows_updated'] += (int) $wpdb->rows_affected;
            }
        }

        // (4) Force Yoast to rebuild the dependent fields (schema graph
        // pieces that aren't URL-shaped but reference the permalink) by
        // bumping `version = 0` on every touched row.
        if ( ! empty( $touched_ids ) ) {
            $col_exists = $wpdb->get_var( "SHOW COLUMNS FROM {$table} LIKE 'version'" );
            if ( $col_exists ) {
                $ids_csv = implode( ',', array_keys( $touched_ids ) );
                $wpdb->query( "UPDATE {$table} SET version = 0 WHERE id IN ({$ids_csv})" );
            }
            // Refresh the permalink_hash column so Yoast's lookup index
            // doesn't 404 the new permalinks. Hash uses md5(strlen . url).
            $col_exists = $wpdb->get_var( "SHOW COLUMNS FROM {$table} LIKE 'permalink_hash'" );
            if ( $col_exists ) {
                $rows = (array) $wpdb->get_results(
                    "SELECT id, permalink FROM {$table} WHERE id IN ({$ids_csv})"
                );
                foreach ( $rows as $row ) {
                    $perm = (string) $row->permalink;
                    if ( $perm === '' ) { continue; }
                    $hash = strlen( $perm ) . ':' . md5( $perm );
                    $wpdb->update(
                        $table,
                        [ 'permalink_hash' => $hash ],
                        [ 'id' => (int) $row->id ]
                    );
                }
            }
        }

        // Drop Yoast transients + object cache so the next render
        // picks up the rewritten rows instead of replaying a stale copy.
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'yoast-seo' );
        } elseif ( function_exists( 'wp_cache_flush' ) ) {
            // No group-flush available — best-effort nuke. The caller
            // already runs purge_external_caches() in most contexts.
            wp_cache_flush();
        }
        delete_transient( 'wpseo_total_unindexed' );
        delete_transient( 'wpseo_unindexed_post_link_count' );
        delete_transient( 'wpseo_unindexed_term_link_count' );

        return $report;
    }

    /**
     * Best-effort cache eviction across the popular WordPress page-cache,
     * image-optimisation, and object-cache layers. Each block is guarded
     * so the call no-ops cleanly when the corresponding plugin / drop-in
     * is not installed.
     *
     * @return list<string>  Human-readable list of layers a hook fired against.
     */
    public static function purge_external_caches(): array {
        $fired = [];

        // Generic WP object cache (covers Redis / Memcached / wp_cache_flush).
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
            $fired[] = 'WP object cache';
        }

        // LiteSpeed Cache — page cache.
        if ( class_exists( '\\LiteSpeed\\Purge' ) && method_exists( '\\LiteSpeed\\Purge', 'purge_all' ) ) {
            \LiteSpeed\Purge::purge_all();
            $fired[] = 'LiteSpeed page cache (class)';
        }
        do_action( 'litespeed_purge_all' );

        // LiteSpeed Image Optimizer — clears the optimised-image cache and
        // the lazy-load `data-o_*` snapshot cache that bakes itself into
        // rendered HTML. Without this, even after page-cache purge, the
        // FIRST render rebuilds `data-o_data-src` from LSIO's CACHED
        // optimised image map (which still points at OLD URLs). We try
        // every known LSIO surface — action names, the Purge::add API,
        // and direct postmeta cleanup — because LSIO renames its own
        // hooks across versions.
        do_action( 'litespeed_purge_img' );
        do_action( 'litespeed_image_optimize_clear' );
        do_action( 'litespeed_image_optimize_destroy' );
        if ( class_exists( '\\LiteSpeed\\Purge' ) && method_exists( '\\LiteSpeed\\Purge', 'add' ) ) {
            // Tag-based purge — covers img_optm bucket on modern LSCache.
            try { \LiteSpeed\Purge::add( 'img_optm' ); } catch ( \Throwable $t ) {}
            try { \LiteSpeed\Purge::add( 'esi' ); } catch ( \Throwable $t ) {}
            try { \LiteSpeed\Purge::add( 'pages' ); } catch ( \Throwable $t ) {}
            $fired[] = 'LiteSpeed Purge tags (img_optm, esi, pages)';
        }
        if ( class_exists( '\\LiteSpeed\\Img_Optm' ) ) {
            $fired[] = 'LiteSpeed Image Optimizer';
        }
        // Last-resort: drop LSIO postmeta keys that remember the OLD
        // optimised file paths. These are what override our renamed URLs
        // via the `wp_get_attachment_url` filter even after page cache is
        // gone.
        global $wpdb;
        $deleted_lsio_meta = (int) $wpdb->query(
            "DELETE FROM {$wpdb->postmeta}
             WHERE meta_key LIKE 'litespeed-optimize-data%'
                OR meta_key LIKE 'litespeed-optm-%'
                OR meta_key LIKE 'litespeed_optm_%'"
        );
        if ( $deleted_lsio_meta > 0 ) {
            $fired[] = sprintf( 'LSIO postmeta rows: %d deleted', $deleted_lsio_meta );
        }

        // WP Rocket.
        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
            $fired[] = 'WP Rocket';
        }
        if ( function_exists( 'rocket_clean_minify' ) ) {
            rocket_clean_minify();
        }

        // W3 Total Cache.
        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
            $fired[] = 'W3 Total Cache';
        }

        // WP Super Cache.
        if ( function_exists( 'wp_cache_clean_cache' ) ) {
            global $file_prefix;
            @wp_cache_clean_cache( $file_prefix, true );
            $fired[] = 'WP Super Cache';
        }

        // Cloudflare WP plugin (official).
        do_action( 'cloudflare_purge_everything' );

        // Hyper Cache, Cache Enabler, SG Optimizer, Nginx Helper.
        do_action( 'hyper_cache_clean_all' );
        do_action( 'cache_enabler_complete_cache_cleared' );
        do_action( 'sg_cachepress_purge_cache' );
        do_action( 'rt_nginx_helper_purge_all' );

        // Generic WP "everything changed" signal so any third-party cache
        // listening for it clears on the next request.
        do_action( 'cmc_cloner_caches_purged' );

        return $fired;
    }

    /**
     * Resolve the per-product image-base slug from the CURRENT product
     * title (V0.9.2: always re-derive, no cache short-circuit).
     *
     * Pattern: the slug comes from `post_name` (already a clean dedup-
     * suffixed slug from WordPress) or `sanitize_title( post_title )` as
     * fallback. Each call reads the current title — so if the user
     * rewrites the product title and clicks "Rename images" again, the
     * base slug refreshes to match the new title, and the rename loop
     * detects the diff and overrides the filename.
     *
     * The slug is still mirrored into `_cmc_img_base_slug` post-meta for
     * downstream reference / debugging, but the meta is treated as a
     * cache write only — it never short-circuits this method.
     *
     * Filename length cap: 80 chars on the base — leaves headroom for
     * size suffixes (`-1024x1024-scaled`) before hitting common 255-byte
     * filesystem limits.
     *
     * Returns '' only when both post_name and post_title are empty.
     */
    private static function ensure_product_base_slug( int $product_id ): string {
        $p = get_post( $product_id );
        if ( ! $p ) {
            return '';
        }

        $slug = (string) $p->post_name;
        if ( $slug === '' ) {
            $slug = sanitize_title( (string) $p->post_title );
        }
        if ( $slug === '' ) {
            return '';
        }

        // Cap base length so size-suffixed variants stay within typical
        // 255-byte filesystem limits.
        if ( strlen( $slug ) > 80 ) {
            $slug = rtrim( substr( $slug, 0, 80 ), '-' );
        }

        update_post_meta( $product_id, self::META_BASE_SLUG, $slug );
        return $slug;
    }

    /**
     * Rewrite an attachment's ALT text + post fields to mirror the product
     * title in a role-aware pattern. Pulled out of rename_attachment so the
     * batch can also "refresh ALT only" on attachments that were renamed
     * earlier (e.g. the user rewrote the product title afterwards).
     *
     * Pattern:
     *   - featured  → "{Product Title}"
     *   - gallery N → "{Product Title} - Image N"   (N starts at 2)
     *   - variation N → "{Product Title} - Variation N"  (N starts at 1)
     *
     * Also clears `post_excerpt` (caption) on the attachment, which often
     * carries brand-laden text from the original CSV import.
     *
     * @param array{type:string,index:int} $role
     */
    private static function update_attachment_alt( int $aid, int $product_id, array $role ): void {
        $product = get_post( $product_id );
        if ( ! $product ) {
            return;
        }

        $title = trim( (string) $product->post_title );
        if ( $title === '' ) {
            return;
        }

        $type  = (string) ( $role['type'] ?? 'gallery' );
        $index = (int) ( $role['index'] ?? 0 );

        switch ( $type ) {
            case 'featured':
                $alt = $title;
                break;
            case 'variation':
                $alt = sprintf( '%s - Variation %d', $title, max( 1, $index ) );
                break;
            case 'gallery':
            default:
                $alt = $index >= 2
                    ? sprintf( '%s - Image %d', $title, $index )
                    : $title;
                break;
        }

        // Preserve original ALT for revert support, only on first rewrite.
        if ( get_post_meta( $aid, self::META_ORIGINAL_ALT, true ) === '' ) {
            $original_alt = (string) get_post_meta( $aid, '_wp_attachment_image_alt', true );
            if ( $original_alt !== '' ) {
                update_post_meta( $aid, self::META_ORIGINAL_ALT, $original_alt );
            }
        }

        update_post_meta( $aid, '_wp_attachment_image_alt', $alt );

        // Refresh the attachment post itself: post_title becomes the same
        // human-readable ALT (overrides the slug-style title set by
        // rename_attachment); post_excerpt (caption) is cleared so any
        // brand-heavy import caption no longer leaks into product gallery
        // captions.
        wp_update_post( [
            'ID'           => $aid,
            'post_title'   => $alt,
            'post_excerpt' => '',
        ] );

        if ( function_exists( 'clean_attachment_cache' ) ) {
            clean_attachment_cache( $aid );
        }
        wp_cache_delete( $aid, 'posts' );
        wp_cache_delete( $aid, 'post_meta' );
    }

    /**
     * Parse `_product_image_gallery` (CSV of attachment IDs) into a clean
     * de-duplicated integer list, preserving the user's gallery order.
     */
    private static function gallery_ids( int $product_id ): array {
        $raw = (string) get_post_meta( $product_id, '_product_image_gallery', true );
        if ( $raw === '' ) {
            return [];
        }
        $out = [];
        foreach ( explode( ',', $raw ) as $g ) {
            $id = (int) trim( $g );
            if ( $id > 0 && ! in_array( $id, $out, true ) ) {
                $out[] = $id;
            }
        }
        return $out;
    }

    /**
     * Map every variation thumbnail to a 1-based variation index. Returns
     * `[ aid => idx ]` keyed by attachment ID. Variations that share the
     * same thumbnail collapse to the first index (the file is renamed
     * once and all sharing variations follow the same file via their
     * unchanged thumbnail-id reference).
     */
    private static function collect_variation_thumbs( int $product_id ): array {
        $vids = get_posts( [
            'post_type'      => 'product_variation',
            'post_parent'    => $product_id,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ] );

        $map = [];
        $idx = 0;
        foreach ( (array) $vids as $vid ) {
            $idx++;
            $thumb = (int) get_post_thumbnail_id( (int) $vid );
            if ( $thumb > 0 && ! isset( $map[ $thumb ] ) ) {
                $map[ $thumb ] = $idx;
            }
        }
        return $map;
    }

    /**
     * Revert all images for a product back to their saved original
     * filenames. Attachments without a stored original are left alone.
     *
     * @return array{
     *     id:int, title:string, reverted:int, skipped:int,
     *     errors:list<string>, log:list<array{aid:int, from:string, to:string, note:string}>
     * }
     */
    public static function revert_product( int $product_id ): array {
        $p = get_post( $product_id );
        if ( ! $p || $p->post_type !== 'product' ) {
            return [
                'id'       => $product_id,
                'title'    => '',
                'reverted' => 0,
                'skipped'  => 0,
                'errors'   => [ 'Product not found.' ],
                'log'      => [],
            ];
        }

        $attachments = self::collect_product_attachments( $product_id );
        $reserved    = [];
        $reverted    = 0;
        $skipped     = 0;
        $errors      = [];
        $log         = [];

        foreach ( $attachments as $aid ) {
            $original = (string) get_post_meta( $aid, self::META_ORIGINAL, true );
            if ( $original === '' ) {
                $skipped++;
                continue;
            }

            $target_slug = pathinfo( $original, PATHINFO_FILENAME );
            $res         = self::rename_attachment( $aid, $target_slug, $reserved, true );
            if ( is_wp_error( $res ) ) {
                $errors[] = sprintf( '#%d: %s', $aid, $res->get_error_message() );
                $log[]    = [
                    'aid'  => $aid,
                    'from' => (string) get_post_meta( $aid, '_wp_attached_file', true ),
                    'to'   => '',
                    'note' => 'ERROR: ' . $res->get_error_message(),
                ];
                continue;
            }

            delete_post_meta( $aid, self::META_ORIGINAL );
            delete_post_meta( $aid, self::META_RENAMED_AT );

            $reverted++;
            $log[] = [
                'aid'  => $aid,
                'from' => (string) $res['from'],
                'to'   => (string) $res['to'],
                'note' => 'reverted',
            ];
        }

        // Bust WC's per-product caches so `data-product_variations` JSON
        // on the single-product page rebuilds with the reverted (original)
        // URLs — same reasoning as the rename path. Skipped when nothing
        // was actually reverted to keep the no-op case cheap.
        if ( $reverted > 0 ) {
            self::flush_woo_caches_for_products( [ $product_id ] );
            self::invalidate_yoast_indexables_for_products( [ $product_id ] );
        }

        return [
            'id'       => $product_id,
            'title'    => (string) $p->post_title,
            'reverted' => $reverted,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'log'      => $log,
        ];
    }

    /**
     * @return list<int>
     */
    private static function product_ids_in_term( int $term_id, bool $include_subcats ): array {
        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return [];
        }
        $q = new WP_Query( [
            'post_type'      => 'product',
            'post_status'    => [ 'publish', 'draft', 'private', 'pending' ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'tax_query'      => [
                [
                    'taxonomy'         => 'product_cat',
                    'field'            => 'term_id',
                    'terms'            => $term_id,
                    'include_children' => $include_subcats,
                ],
            ],
        ] );
        return array_map( 'intval', (array) $q->posts );
    }

    /**
     * Collect every attachment associated with a product — the featured
     * image, WooCommerce gallery, and per-variation featured images.
     *
     * @return list<int>
     */
    private static function collect_product_attachments( int $product_id ): array {
        $ids = [];

        $thumb = (int) get_post_thumbnail_id( $product_id );
        if ( $thumb > 0 ) {
            $ids[ $thumb ] = true;
        }

        $gallery = (string) get_post_meta( $product_id, '_product_image_gallery', true );
        if ( $gallery !== '' ) {
            foreach ( explode( ',', $gallery ) as $g ) {
                $g = (int) trim( $g );
                if ( $g > 0 ) { $ids[ $g ] = true; }
            }
        }

        $variation_ids = get_posts( [
            'post_type'      => 'product_variation',
            'post_parent'    => $product_id,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ] );
        foreach ( (array) $variation_ids as $vid ) {
            $vthumb = (int) get_post_thumbnail_id( (int) $vid );
            if ( $vthumb > 0 ) {
                $ids[ $vthumb ] = true;
            }
        }

        return array_keys( $ids );
    }

    /**
     * Classify an attachment: is it renamable, already renamed, shared with
     * other posts, or not an image at all?
     *
     * @return 'renamable'|'renamed'|'shared'|'missing'|'skip'
     */
    private static function attachment_status( int $aid, int $owner_product_id ): string {
        $att = get_post( $aid );
        if ( ! $att || $att->post_type !== 'attachment' ) {
            return 'missing';
        }

        // Disk-existence check FIRST — must run before the META_ORIGINAL /
        // shared classification. Earlier versions returned 'renamed' as soon
        // as META_ORIGINAL was set, skipping the disk probe. That blew up on
        // freshly-cloned sites whose database carried META_ORIGINAL from a
        // prior rename run while the V0 files at `_wp_attached_file` paths
        // never made it to the new disk: status='renamed' → override branch
        // → rename(V0 → target) → ENOENT. By probing the disk first, those
        // attachments now report 'missing' and the recovery hook in
        // rename_product() can attempt to repair `_wp_attached_file` from
        // metadata['sizes'] before classification runs again.
        $file = (string) get_post_meta( $aid, '_wp_attached_file', true );
        if ( $file === '' ) {
            return 'missing';
        }
        $uploads = wp_get_upload_dir();
        $raw_path = ! empty( $uploads['basedir'] )
            ? trailingslashit( (string) $uploads['basedir'] ) . $file
            : '';
        if ( $raw_path === '' || ! self::path_really_exists( $raw_path ) ) {
            return 'missing';
        }

        if ( get_post_meta( $aid, self::META_ORIGINAL, true ) !== '' ) {
            return 'renamed';
        }

        // Attachment rows shared across posts: WordPress uses post_parent
        // to track the uploader/owner. If the parent is some *other* post,
        // we don't touch the file.
        $parent = (int) $att->post_parent;
        if ( $parent !== 0 && $parent !== $owner_product_id ) {
            // Allow when parent is a variation of this product.
            $parent_post = get_post( $parent );
            if ( ! $parent_post
                || $parent_post->post_type !== 'product_variation'
                || (int) $parent_post->post_parent !== $owner_product_id ) {
                return 'shared';
            }
        }

        return 'renamable';
    }

    /**
     * Hardened existence check: on LiteSpeed LSAPI some stat-cache layers
     * return stale TRUE even after `clearstatcache()`. Probing with
     * `filesize()` forces a real `stat()` syscall that cannot be faked by
     * userspace caches, and we also require `is_file()` so a stale
     * directory entry cannot pass.
     */
    private static function path_really_exists( string $path ): bool {
        if ( $path === '' ) {
            return false;
        }
        clearstatcache( true, $path );
        if ( ! file_exists( $path ) ) {
            return false;
        }
        if ( ! is_file( $path ) ) {
            return false;
        }
        $size = @filesize( $path );
        return $size !== false;
    }

    /**
     * Recover an inconsistent `_wp_attached_file` postmeta when the actual
     * full-size file on disk has already been renamed but the postmeta still
     * points at the dead old basename.
     *
     * Symptom (observed in the wild):
     *   - <img src> uses NEW filename via the size-variant lookup
     *     (`_wp_attachment_metadata['sizes'][...]['file']`).
     *   - <a href> / data-large_image use OLD filename via `wp_get_attachment_image_src('full')`,
     *     which resolves through `_wp_attached_file`.
     *   - Old basename returns 404 because the disk file was moved during a
     *     prior rename, but the full-size pointer never caught up.
     *
     * Recovery strategy:
     *   1. Read `_wp_attachment_metadata`.
     *   2. Pull a sample size variant filename (e.g. "foo-560x559.jpg").
     *   3. Strip the trailing "-WxH" dimension suffix to derive the true core.
     *   4. Build candidate full-size paths in the same subdir: "{core}.{ext}"
     *      and "{core}-scaled.{ext}".
     *   5. If a candidate exists on disk via path_really_exists(), write the
     *      relative path back to `_wp_attached_file` AND `metadata['file']`,
     *      flush attachment + post caches, return true.
     *
     * Idempotent: if the postmeta already matches a real file, returns false
     * fast (the regular `attachment_status` flow will handle it).
     */
    private static function recover_attached_file_from_sizes( int $aid ): bool {
        $metadata = get_post_meta( $aid, '_wp_attachment_metadata', true );
        if ( ! is_array( $metadata ) || empty( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
            return false;
        }

        $uploads = wp_get_upload_dir();
        $basedir = ! empty( $uploads['basedir'] ) ? trailingslashit( (string) $uploads['basedir'] ) : '';
        if ( $basedir === '' ) {
            return false;
        }

        // Determine the subdirectory (e.g. "2024/03") from the existing file
        // pointer if any, otherwise fall back to metadata['file']'s dirname.
        $current_relative = (string) get_post_meta( $aid, '_wp_attached_file', true );
        $subdir_rel       = '';
        if ( $current_relative !== '' ) {
            $dn = dirname( $current_relative );
            if ( $dn !== '' && $dn !== '.' ) {
                $subdir_rel = trailingslashit( $dn );
            }
        }
        if ( $subdir_rel === '' && ! empty( $metadata['file'] ) ) {
            $dn = dirname( (string) $metadata['file'] );
            if ( $dn !== '' && $dn !== '.' ) {
                $subdir_rel = trailingslashit( $dn );
            }
        }

        // Pick a sample size to reverse-engineer the true core. The first
        // size with a non-empty 'file' is enough; all sizes share the same
        // core by WordPress convention.
        $sample_basename = '';
        foreach ( $metadata['sizes'] as $size_data ) {
            if ( is_array( $size_data ) && ! empty( $size_data['file'] ) ) {
                $sample_basename = (string) $size_data['file'];
                break;
            }
        }
        if ( $sample_basename === '' ) {
            return false;
        }

        $sample_ext  = pathinfo( $sample_basename, PATHINFO_EXTENSION );
        $sample_stem = pathinfo( $sample_basename, PATHINFO_FILENAME );
        if ( $sample_ext === '' || $sample_stem === '' ) {
            return false;
        }

        // Strip the trailing "-WxH" dimension suffix → true core.
        $true_core = preg_replace( '/-\d+x\d+$/', '', $sample_stem );
        if ( $true_core === '' || $true_core === $sample_stem ) {
            // No dimension suffix to strip — sample wasn't an intermediate
            // size, can't reliably derive the core.
            return false;
        }

        // If the existing pointer's core already matches the true core, no
        // recovery needed (or recovery already done in a prior pass).
        if ( $current_relative !== '' ) {
            $current_basename = basename( $current_relative );
            $current_stem     = pathinfo( $current_basename, PATHINFO_FILENAME );
            $current_core     = preg_replace( '/-scaled$/', '', $current_stem );
            if ( $current_core === $true_core ) {
                return false;
            }
        }

        // Build candidate full-size paths and probe disk.
        $candidates = [
            $true_core . '.' . $sample_ext,
            $true_core . '-scaled.' . $sample_ext,
        ];

        $recovered_relative = '';
        foreach ( $candidates as $candidate_basename ) {
            $candidate_relative = $subdir_rel . $candidate_basename;
            $candidate_abs      = $basedir . $candidate_relative;
            if ( self::path_really_exists( $candidate_abs ) ) {
                $recovered_relative = $candidate_relative;
                break;
            }
        }

        if ( $recovered_relative === '' ) {
            return false;
        }

        // Write the recovered pointer in both places WordPress reads.
        update_post_meta( $aid, '_wp_attached_file', $recovered_relative );
        $metadata['file'] = $recovered_relative;
        update_post_meta( $aid, '_wp_attachment_metadata', $metadata );

        // Bust every cache layer that could keep the dead pointer alive.
        clean_attachment_cache( $aid );
        clean_post_cache( $aid );
        wp_cache_delete( $aid, 'posts' );
        wp_cache_delete( $aid, 'post_meta' );

        return true;
    }

    /**
     * Core rename routine — the atomic 5-place update for a single
     * attachment. Returns the from/to basenames and how many post_content
     * rows had their URL references updated.
     *
     * @param array<string,true> $reserved  in/out: basenames already claimed in this batch
     * @param bool               $force_exact  true when reverting — use $target verbatim, do not append "-N"
     *
     * @return array{from:string, to:string, content_hits:int}|WP_Error
     */
    private static function rename_attachment( int $aid, string $target_slug, array &$reserved, bool $force_exact = false ) {
        $old_relative = (string) get_post_meta( $aid, '_wp_attached_file', true );
        if ( $old_relative === '' ) {
            return new WP_Error( 'cmc_no_file', 'Attachment has no attached file.' );
        }

        $uploads = wp_get_upload_dir();
        if ( ! empty( $uploads['error'] ) ) {
            return new WP_Error( 'cmc_uploads_error', (string) $uploads['error'] );
        }

        $basedir = trailingslashit( (string) $uploads['basedir'] );
        $baseurl = trailingslashit( (string) $uploads['baseurl'] );

        $old_full  = $basedir . $old_relative;
        $subdir    = trim( dirname( $old_relative ), '/\\' );
        if ( $subdir === '.' ) { $subdir = ''; }
        $subdir_rel = $subdir === '' ? '' : $subdir . '/';
        $subdir_abs = $subdir === '' ? $basedir : $basedir . $subdir . '/';

        // Stat cache from earlier calls in the same request can poison this
        // check on some hosting stacks (LiteSpeed, opcache+realpath cache) —
        // the userspace `file_exists()` cache can return a stale TRUE. Probe
        // via `filesize()` which forces a real `stat()` syscall.
        if ( ! self::path_really_exists( $old_full ) ) {
            // Help the user diagnose: search the uploads dir for a file with
            // this basename so we can report "DB says X but file actually at Y".
            $real_path = self::locate_file_by_basename( $basedir, basename( $old_full ) );
            $hint      = $real_path
                ? sprintf( 'file actually exists at: %s', $real_path )
                : 'file not found anywhere under uploads — it was deleted';
            return new WP_Error(
                'cmc_file_missing',
                sprintf(
                    '[v6] File missing at DB path: %s — %s',
                    $old_full,
                    $hint
                )
            );
        }

        $old_basename = basename( $old_full );                  // "brandy-scaled.jpg"
        $ext          = pathinfo( $old_basename, PATHINFO_EXTENSION );
        $old_stem     = pathinfo( $old_basename, PATHINFO_FILENAME ); // "brandy-scaled" or "brandy"
        $had_scaled   = (bool) preg_match( '/-scaled$/', $old_stem );
        $old_core     = $had_scaled ? preg_replace( '/-scaled$/', '', $old_stem ) : $old_stem;

        // Resolve a collision-free target core name.
        $target = self::reserve_basename( $subdir_abs, $target_slug, $ext, $reserved, $old_core, $force_exact );
        if ( is_wp_error( $target ) ) {
            return $target;
        }
        $new_core  = $target;
        $new_stem  = $had_scaled ? $new_core . '-scaled' : $new_core;
        $new_basename = $new_stem . ( $ext !== '' ? '.' . $ext : '' );

        // No-op: already the target name (and already-renamed status would
        // have been caught earlier, but this guards the revert path too).
        if ( $new_basename === $old_basename ) {
            return [ 'from' => $old_basename, 'to' => $new_basename, 'content_hits' => 0 ];
        }

        // Build the file-move plan: main file + every size variant.
        $metadata  = (array) wp_get_attachment_metadata( $aid );
        $old_main_rel = $old_relative; // e.g. "2025/04/brandy-scaled.jpg"
        $new_main_rel = $subdir_rel . $new_basename;

        $moves = [];
        $moves[] = [ $old_full, $basedir . $new_main_rel ];

        $size_renames = [];
        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            foreach ( $metadata['sizes'] as $size => $info ) {
                if ( ! is_array( $info ) || empty( $info['file'] ) ) { continue; }
                $old_size_basename = (string) $info['file'];
                // Size filenames are built from the CORE (pre-scaled) name:
                //   brandy-150x150.jpg  (not brandy-scaled-150x150.jpg)
                // Use a prefix-match that tolerates both forms.
                $new_size_basename = self::replace_filename_prefix( $old_size_basename, $old_core, $new_core );
                if ( $new_size_basename === $old_size_basename ) {
                    continue;
                }
                $old_size_full = $subdir_abs . $old_size_basename;
                $new_size_full = $subdir_abs . $new_size_basename;
                // Use path_really_exists (clearstatcache + filesize syscall)
                // instead of plain file_exists — on LiteSpeed LSAPI the
                // userspace stat cache can return stale TRUE for variants
                // that no longer exist on disk, making rename() ENOENT at
                // call time and rolling back the entire move set.
                if ( ! self::path_really_exists( $old_size_full ) ) {
                    // Missing size variant on disk is non-fatal — skip the
                    // rename move BUT still update the metadata entry to
                    // the new name so srcset URLs match the post-rename
                    // file set (the missing variant just stays missing).
                    $size_renames[ $size ] = [
                        'old' => $old_size_basename,
                        'new' => $new_size_basename,
                    ];
                    continue;
                }
                $moves[]                     = [ $old_size_full, $new_size_full ];
                $size_renames[ $size ]       = [
                    'old' => $old_size_basename,
                    'new' => $new_size_basename,
                ];
            }
        }

        // Also move the unscaled "original_image" file WordPress stores when
        // it auto-scales big uploads (>2560px). It lives alongside the main
        // file at the same core name but WITHOUT "-scaled".
        if ( $had_scaled && ! empty( $metadata['original_image'] ) ) {
            $old_orig = (string) $metadata['original_image']; // "brandy.jpg"
            $new_orig = self::replace_filename_prefix( $old_orig, $old_core, $new_core );
            if ( $new_orig !== $old_orig ) {
                $old_orig_full = $subdir_abs . $old_orig;
                $new_orig_full = $subdir_abs . $new_orig;
                if ( file_exists( $old_orig_full ) ) {
                    $moves[]                  = [ $old_orig_full, $new_orig_full ];
                    $metadata['original_image'] = $new_orig;
                }
            }
        }

        // Reject if any destination exists (extra belt-and-braces after reserve_basename).
        foreach ( $moves as $pair ) {
            if ( $pair[0] !== $pair[1] && file_exists( $pair[1] ) ) {
                return new WP_Error( 'cmc_dest_exists', sprintf( 'Destination already exists: %s', basename( $pair[1] ) ) );
            }
        }

        // Pre-flight: make sure the directory is writable and every source is
        // writable. rename() needs write perms on the *directory* (to modify
        // the entry) and some platforms also require write on the source file.
        if ( ! is_writable( $subdir_abs ) ) {
            return new WP_Error( 'cmc_dir_not_writable', sprintf( 'Uploads directory is not writable: %s', $subdir_abs ) );
        }
        foreach ( $moves as $pair ) {
            if ( ! is_writable( $pair[0] ) ) {
                return new WP_Error( 'cmc_src_not_writable', sprintf(
                    'Source file is not writable (check permissions/owner): %s',
                    basename( $pair[0] )
                ) );
            }
        }

        // Perform moves; rollback on first failure. Capture the real PHP
        // error via error_get_last() so we can surface the OS-level reason
        // (EACCES, ENOSPC, filename too long, cross-device link, …).
        //
        // The first move ($moves[0]) is the MAIN file — its failure is fatal
        // and triggers a full rollback. Subsequent moves are size variants;
        // if a size variant's source is missing at rename time (stat cache
        // poisoning, file deleted post-pre-flight, etc.) we now skip it
        // gracefully instead of rolling back the main file rename, since
        // the size variant is regenerable via WP's image-edit "regenerate
        // thumbnails" flow but the main rename is the user's actual goal.
        $done = [];
        foreach ( $moves as $idx => $pair ) {
            clearstatcache( true, $pair[0] );
            clearstatcache( true, $pair[1] );
            $src_exists        = self::path_really_exists( $pair[0] );
            $dst_parent_exists = is_dir( dirname( $pair[1] ) );

            // Source disappeared between pre-flight and now (stat cache /
            // concurrent process). For size variants this is non-fatal —
            // continue the loop. For the main file (idx 0) it IS fatal.
            if ( ! $src_exists ) {
                if ( $idx === 0 ) {
                    foreach ( array_reverse( $done ) as $back ) {
                        @rename( $back[1], $back[0] );
                    }
                    return new WP_Error( 'cmc_rename_failed', sprintf(
                        '[v6] main file missing on disk | from=%s | to=%s (parent_exists=%s)',
                        $pair[0],
                        $pair[1],
                        $dst_parent_exists ? 'yes' : 'NO'
                    ) );
                }
                error_log( sprintf(
                    '[CMC image-rename] skipping missing size variant: %s',
                    $pair[0]
                ) );
                continue;
            }

            // Clear the last error so error_get_last() reflects *this* call.
            error_clear_last();
            if ( ! rename( $pair[0], $pair[1] ) ) {
                $err     = error_get_last();
                $err_msg = $err && ! empty( $err['message'] ) ? $err['message'] : 'unknown error';
                // error_get_last() messages often start with "rename(a,b): " — trim that.
                $err_msg = preg_replace( '/^rename\([^)]*\):\s*/', '', (string) $err_msg );

                // Size variants: log + skip instead of failing the whole batch.
                if ( $idx > 0 ) {
                    error_log( sprintf(
                        '[CMC image-rename] size variant rename failed (non-fatal): %s -> %s | %s',
                        $pair[0], $pair[1], $err_msg
                    ) );
                    continue;
                }

                // Main file: fatal — rollback every successful move so far.
                foreach ( array_reverse( $done ) as $back ) {
                    @rename( $back[1], $back[0] );
                }
                error_log( sprintf(
                    '[CMC image-rename] rename(%s, %s) failed: %s (src_exists=%s, dst_parent_exists=%s)',
                    $pair[0], $pair[1], $err_msg,
                    $src_exists ? 'yes' : 'NO',
                    $dst_parent_exists ? 'yes' : 'NO'
                ) );
                return new WP_Error( 'cmc_rename_failed', sprintf(
                    '[v7] rename failed reason=%s | from=%s (exists=%s) | to=%s (parent_exists=%s)',
                    $err_msg,
                    $pair[0],
                    $src_exists ? 'yes' : 'NO',
                    $pair[1],
                    $dst_parent_exists ? 'yes' : 'NO'
                ) );
            }
            $done[] = $pair;
        }

        // Update metadata. `file` MUST carry the subdir/basename — WordPress
        // builds srcset URLs from dirname($metadata['file']), and a stripped
        // key yields "/uploads/name-300x300.jpg" (no YYYY/MM) which 404s even
        // though `_wp_attached_file` still has the right path. Set it
        // unconditionally so a filter that dropped the key earlier can't
        // leave `src` and `srcset` out of sync.
        $metadata['file'] = $new_main_rel;
        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) && $size_renames ) {
            foreach ( $size_renames as $size => $pair ) {
                if ( isset( $metadata['sizes'][ $size ] ) ) {
                    $metadata['sizes'][ $size ]['file'] = $pair['new'];
                }
            }
        }
        wp_update_attachment_metadata( $aid, $metadata );
        update_post_meta( $aid, '_wp_attached_file', $new_main_rel );

        // Update wp_posts row for the attachment.
        //
        // GOTCHA: wp_update_post() silently DROPS any 'guid' field for
        // existing posts — WP treats guid as immutable post-insert. That
        // matters because the "Featured Image From URL" / "show-link-image"
        // plugin (and similar FIFU-style plugins) install a filter on
        // wp_get_attachment_url that ALWAYS returns $post->guid in place
        // of the _wp_attached_file-derived URL. If guid stays at the old
        // upload URL, full-size requests (CTX Feed, GMC feed, etc.) keep
        // serving the pre-rename filename even though every other postmeta
        // field is correct — producing storefront-vs-feed URL divergence.
        //
        // So: update post_title + post_name via wp_update_post (which fires
        // the right hooks), then patch guid DIRECTLY via $wpdb->update.
        $new_guid = $baseurl . $new_main_rel;
        wp_update_post( [
            'ID'         => $aid,
            'post_title' => pathinfo( $new_basename, PATHINFO_FILENAME ),
            'post_name'  => sanitize_title( pathinfo( $new_basename, PATHINFO_FILENAME ) ),
        ] );
        global $wpdb;
        $wpdb->update(
            $wpdb->posts,
            [ 'guid' => $new_guid ],
            [ 'ID'   => $aid ]
        );

        // Aggressively flush every cache layer that holds attachment URL
        // resolution, so wp_get_attachment_image_src($aid, 'full') returns
        // the new URL on the very next page load. Without this the WC
        // single-product gallery `<a href>` / `data-src` / `data-large_image`
        // keep returning the pre-rename URL even though the DB is correct.
        if ( function_exists( 'clean_attachment_cache' ) ) {
            clean_attachment_cache( $aid );
        }
        wp_cache_delete( $aid, 'posts' );
        wp_cache_delete( $aid, 'post_meta' );
        clean_post_cache( $aid );

        // Record / update the original-filename marker for revert.
        if ( ! $force_exact ) {
            if ( get_post_meta( $aid, self::META_ORIGINAL, true ) === '' ) {
                update_post_meta( $aid, self::META_ORIGINAL, $old_basename );
            }
            update_post_meta( $aid, self::META_RENAMED_AT, time() );
        }

        // Update every post_content that referenced the old URL(s).
        $old_main_url = $baseurl . $old_main_rel;
        $new_main_url = $baseurl . $new_main_rel;

        $url_map = [ $old_main_url => $new_main_url ];
        foreach ( $size_renames as $pair ) {
            $url_map[ $baseurl . $subdir_rel . $pair['old'] ] = $baseurl . $subdir_rel . $pair['new'];
        }
        $content_hits = self::update_post_content_urls( $url_map );

        // Reserve the new basename for the rest of this batch.
        $reserved[ strtolower( $new_core ) ] = true;

        return [
            'from'         => $old_basename,
            'to'           => $new_basename,
            'content_hits' => $content_hits,
        ];
    }

    /**
     * Pick a collision-free core filename.
     *  - If $force_exact is true (revert path), the target must be
     *    available as-is or the current attachment already holds it.
     *  - Otherwise we try $slug, then $slug-2, $slug-3, … until we find
     *    a name that isn't on disk and isn't reserved by this batch.
     *
     * @param array<string,true> $reserved
     *
     * @return string|WP_Error   the core filename (no extension, no "-scaled")
     */
    private static function reserve_basename(
        string $subdir_abs,
        string $slug,
        string $ext,
        array &$reserved,
        string $current_core,
        bool $force_exact
    ) {
        $slug = sanitize_file_name( $slug );
        $slug = preg_replace( '/-scaled$/', '', $slug );
        if ( $slug === '' ) {
            return new WP_Error( 'cmc_bad_slug', 'Target slug is empty after sanitizing.' );
        }

        if ( $force_exact ) {
            if ( strtolower( $slug ) === strtolower( $current_core )
                 || ( ! self::core_name_exists_on_disk( $subdir_abs, $slug, $ext ) && empty( $reserved[ strtolower( $slug ) ] ) ) ) {
                return $slug;
            }
            return new WP_Error( 'cmc_revert_collision', sprintf( 'Cannot revert: "%s" is taken.', $slug ) );
        }

        $candidate = $slug;
        $i         = 2;
        while ( true ) {
            $lower = strtolower( $candidate );
            if ( $lower === strtolower( $current_core ) ) {
                return $candidate; // same name we already own, keep it
            }
            if ( ! self::core_name_exists_on_disk( $subdir_abs, $candidate, $ext )
                 && empty( $reserved[ $lower ] ) ) {
                return $candidate;
            }
            $candidate = $slug . '-' . $i;
            $i++;
            if ( $i > 500 ) {
                return new WP_Error( 'cmc_collision', 'Could not find a free filename after 500 attempts.' );
            }
        }
    }

    /**
     * Search the uploads tree (YYYY/MM/ and top-level) for a file with the
     * given basename. Used only when the DB-recorded path for an attachment
     * does not exist on disk — tells the user where the file actually lives.
     *
     * Returns the full path if found (first match), empty string otherwise.
     */
    private static function locate_file_by_basename( string $basedir, string $basename ): string {
        if ( $basename === '' ) {
            return '';
        }
        // Escape glob metacharacters in the basename. Our basenames from WP
        // don't normally contain *, ?, [, {, ] — but be defensive.
        $escaped = addcslashes( $basename, '*?[]{}' );

        // WordPress puts files in YYYY/MM/ by default, so check that first.
        $matches = glob( $basedir . '[0-9][0-9][0-9][0-9]/[0-9][0-9]/' . $escaped );
        if ( is_array( $matches ) && $matches ) {
            return $matches[0];
        }
        // Fall back to a top-level check and one-level-deep (custom org).
        $matches = glob( $basedir . $escaped );
        if ( is_array( $matches ) && $matches ) {
            return $matches[0];
        }
        $matches = glob( $basedir . '*/' . $escaped );
        if ( is_array( $matches ) && $matches ) {
            return $matches[0];
        }
        return '';
    }

    /**
     * True if a file in $subdir_abs already uses $core as its exact stem —
     * either the main file, its "-scaled" sibling, or a size variant
     * ({core}-{W}x{H}.{ext}). The glob uses a digit-anchored suffix so a
     * sibling whose stem merely *starts with* $core (e.g. "$core-abcde")
     * does not count as a collision.
     */
    private static function core_name_exists_on_disk( string $subdir_abs, string $core, string $ext ): bool {
        $ext_part = $ext !== '' ? '.' . $ext : '';
        if ( file_exists( $subdir_abs . $core . $ext_part ) ) {
            return true;
        }
        if ( file_exists( $subdir_abs . $core . '-scaled' . $ext_part ) ) {
            return true;
        }
        // Size variants: {core}-{W}x{H}.{ext}. Anchor both dimension segments
        // to a leading digit so "{core}-wnxjt-150x150.jpg" is NOT matched.
        $matches = glob( $subdir_abs . $core . '-[0-9]*x[0-9]*' . $ext_part );
        return is_array( $matches ) && ! empty( $matches );
    }

    /**
     * Swap the leading "$old_core" token inside a basename for "$new_core".
     * Works for both the bare "brandy.jpg" and size-variant
     * "brandy-150x150.jpg" / "brandy-scaled.jpg" forms, and is anchored to
     * the start of the string so we never mangle the middle of a name.
     */
    private static function replace_filename_prefix( string $basename, string $old_core, string $new_core ): string {
        if ( $basename === $old_core ) {
            return $new_core;
        }
        if ( strpos( $basename, $old_core ) === 0 ) {
            $rest = substr( $basename, strlen( $old_core ) );
            if ( $rest === '' || $rest[0] === '.' || $rest[0] === '-' ) {
                return $new_core . $rest;
            }
        }
        return $basename;
    }

    /**
     * Scan a batch of image attachments and optionally repair
     * `_wp_attachment_metadata` in TWO categories:
     *
     *   (1) Path mismatch: `_wp_attachment_metadata['file']` lost its
     *       YYYY/MM prefix relative to `_wp_attached_file`. WordPress
     *       builds `srcset` URLs from `dirname(meta['file'])`, so a path
     *       diverging from `_wp_attached_file` lands srcset URLs in the
     *       wrong subdirectory and every responsive image 404s — even
     *       though `<img src>` (built from `_wp_attached_file`) loads
     *       fine. The browser picks a srcset candidate first, so the
     *       product card goes blank. Cause: import tools, CDN offloaders,
     *       or thumbnail regenerators that rewrite metadata.
     *
     *   (2) Incomplete metadata: width / height / sizes missing or
     *       zeroed out. Cause: Woo POD Master V2 (and similar import
     *       tools) create the attachment row before the JPG finishes
     *       writing — `wp_generate_attachment_metadata()` runs against
     *       a half-written file and bails with `width=0, height=0,
     *       sizes=[]`. WordPress then renders `<img width="0" height="0">`
     *       and the browser shows "Could not load image" in DevTools
     *       even though the file is on disk and serves HTTP 200. Fix:
     *       call `wp_generate_attachment_metadata()` again on the now-
     *       complete file. This is exactly what the Regenerate Thumbnails
     *       plugin does.
     *
     * Batched to survive shared-hosting timeouts: the JOIN pulls both
     * meta keys in one trip, and the caller paginates via $offset until
     * `done` comes back true. No autoload, no WP_Query, no get_post_meta
     * per row — so ~500 attachments/call stays well under 30s even on
     * LiteSpeed LSAPI.
     *
     * @param bool $apply   false = report only; true = write the fix.
     * @param int  $offset  0-based attachment cursor.
     * @param int  $limit   batch size (max 1000).
     *
     * @return array{checked:int, mismatched:int, fixed:int,
     *               incomplete:int, regenerated:int, total:int,
     *               next_offset:int, done:bool,
     *               samples:list<array{aid:int, attached:string, meta:string, reason:string}>}
     */
    public static function repair_metadata_paths( bool $apply, int $offset = 0, int $limit = 30, int $term_id = 0, bool $include_subcats = true ): array {
        if ( $offset < 0 )  { $offset = 0; }
        if ( $limit  < 1 )  { $limit  = 1; }
        if ( $limit  > 200 ) { $limit = 200; }

        // Raise time + memory before touching the image editor — Regenerate
        // Thumbnails does the same. Default LSAPI 30s / 64MB easily trips
        // when the editor has to re-crop a 1500×1500 JPG into every
        // registered intermediate size, and the symptom is a SILENT failure
        // (wp_generate_attachment_metadata returns []). That was why our
        // earlier passes only saved 2 of 17 — the rest hit memory/time
        // ceilings mid-batch.
        @set_time_limit( 120 );
        if ( function_exists( 'wp_raise_memory_limit' ) ) {
            wp_raise_memory_limit( 'image' );
        }
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // Scope:
        //   - $term_id > 0 → restrict to product images of products that
        //     belong to that product_cat (with optional sub-category
        //     descent). Lets the user repair only the category they're
        //     working on instead of the entire site, which on a 5000-image
        //     store cuts the run from minutes to seconds.
        //   - $term_id == 0 → fall back to "every product image on the
        //     site" (legacy / belt-and-suspenders pass).
        if ( $term_id > 0 ) {
            $product_aids = self::collect_product_image_attachment_ids_in_term( $term_id, $include_subcats );
        } else {
            $product_aids = self::collect_all_product_image_attachment_ids();
        }
        $total = count( $product_aids );
        $batch = array_slice( $product_aids, $offset, $limit );

        $uploads = wp_get_upload_dir();
        $basedir = isset( $uploads['basedir'] ) ? trailingslashit( (string) $uploads['basedir'] ) : '';

        $checked     = 0;
        $regenerated = 0;
        $fixed       = 0;
        $skipped     = 0;
        $samples     = [];

        foreach ( $batch as $aid ) {
            $aid = (int) $aid;
            if ( $aid <= 0 ) { continue; }

            $attached = (string) get_post_meta( $aid, '_wp_attached_file', true );
            if ( $attached === '' ) { $skipped++; continue; }

            $abs_file = $basedir !== '' ? $basedir . $attached : '';
            if ( $abs_file === '' || ! self::path_really_exists( $abs_file ) ) {
                $skipped++;
                continue;
            }
            $checked++;

            // Scan-only pass: count without writing.
            if ( ! $apply ) {
                continue;
            }

            // Brute-force regen — like Regenerate Thumbnails. We do NOT
            // gate on detecting "incomplete" because the prior detection
            // logic was over-cautious and missed the very cases user
            // imported via Woo POD.
            try {
                $new_meta = wp_generate_attachment_metadata( $aid, $abs_file );
            } catch ( \Throwable $t ) {
                $new_meta = false;
            }

            $regen_ok = is_array( $new_meta )
                && ! empty( $new_meta['file'] )
                && (int) ( $new_meta['width']  ?? 0 ) > 0
                && (int) ( $new_meta['height'] ?? 0 ) > 0;

            if ( $regen_ok ) {
                wp_update_attachment_metadata( $aid, $new_meta );
                clean_post_cache( $aid );
                $regenerated++;
                continue;
            }

            // Fallback: editor failed (memory pressure, format quirk,
            // unreadable file). At least anchor `meta['file']` to
            // `$attached` so the dirname of every srcset URL gains the
            // YYYY/MM prefix — that recovers display even when no
            // intermediate sizes can be generated.
            $existing = wp_get_attachment_metadata( $aid );
            if ( ! is_array( $existing ) ) { $existing = []; }
            $existing['file'] = $attached;

            // Lift any partial dimensions the editor gave us.
            if ( is_array( $new_meta ) ) {
                if ( ! empty( $new_meta['width'] ) ) {
                    $existing['width']  = (int) $new_meta['width'];
                }
                if ( ! empty( $new_meta['height'] ) ) {
                    $existing['height'] = (int) $new_meta['height'];
                }
                if ( ! empty( $new_meta['sizes'] ) && is_array( $new_meta['sizes'] ) ) {
                    $existing['sizes'] = $new_meta['sizes'];
                }
            }

            // Last-ditch dimensions from filename `-WIDTHxHEIGHT.ext`
            // (Woo POD often imports the size variant AS the original).
            if ( ( empty( $existing['width'] ) || empty( $existing['height'] ) )
                 && preg_match( '/-(\d+)x(\d+)\.[A-Za-z0-9]+$/', $attached, $dim_match ) ) {
                if ( empty( $existing['width'] ) )  {
                    $existing['width']  = (int) $dim_match[1];
                }
                if ( empty( $existing['height'] ) ) {
                    $existing['height'] = (int) $dim_match[2];
                }
            }
            if ( ! isset( $existing['sizes'] ) || ! is_array( $existing['sizes'] ) ) {
                $existing['sizes'] = [];
            }

            wp_update_attachment_metadata( $aid, $existing );
            clean_post_cache( $aid );
            $fixed++;

            if ( count( $samples ) < 5 ) {
                $samples[] = [
                    'aid'      => $aid,
                    'attached' => $attached,
                    'meta'     => '',
                    'reason'   => 'editor failed — fallback patched meta[file] only',
                ];
            }
        }

        $next_offset = $offset + count( $batch );
        $done        = $next_offset >= $total;

        // Final-batch cache purge: after the LAST batch in an apply
        // run, ALWAYS evict every page / object / image-optimiser cache.
        // We deliberately do NOT gate on (fixed || regenerated) — those
        // counters are batch-local and might be 0 in the last batch even
        // when earlier batches wrote rows. Without this purge, LiteSpeed
        // (or any other page cache) keeps serving HTML rendered with the
        // OLD broken srcset and the user thinks the repair did nothing —
        // the fix is in the DB but the rendered page is frozen. Purging
        // an unchanged cache is cheap and idempotent, so always doing it
        // on the last batch of an apply run is the safe call.
        $caches_purged = false;
        if ( $apply && $done ) {
            self::purge_external_caches();
            $caches_purged = true;
        }

        return [
            'checked'       => $checked,
            'mismatched'    => 0,                       // legacy field — brute-force mode doesn't classify
            'fixed'         => $fixed,                  // fallback patches (editor failed)
            'incomplete'    => $checked,                // every product image is a regen candidate
            'regenerated'   => $regenerated,            // full regen succeeded
            'skipped'       => $skipped,                // file missing on disk
            'total'         => $total,
            'next_offset'   => $next_offset,
            'done'          => $done,
            'caches_purged' => $caches_purged,
            'samples'       => $samples,
        ];
    }

    /**
     * Collect every product-image attachment ID for products that live
     * in the given product_cat term (and, optionally, its descendants).
     * Featured image of each product + its variations + every gallery
     * entry. Validated against `post_mime_type LIKE 'image/%'`, deduped,
     * ordered by ID for stable batch pagination.
     *
     * This is the per-category equivalent of
     * `collect_all_product_image_attachment_ids()` — used by the
     * Repair button when the user has picked a category, so the regen
     * sweep only touches product images relevant to that category
     * instead of the whole store.
     *
     * @return list<int>
     */
    private static function collect_product_image_attachment_ids_in_term( int $term_id, bool $include_subcats ): array {
        $product_ids = self::product_ids_in_term( $term_id, $include_subcats );
        if ( empty( $product_ids ) ) {
            return [];
        }

        global $wpdb;
        $product_ids   = array_map( 'intval', $product_ids );
        $product_phs   = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

        // Featured image of each product in the term.
        $sql_thumb = "SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
                       WHERE meta_key = '_thumbnail_id'
                         AND post_id IN ($product_phs)
                         AND meta_value REGEXP '^[0-9]+$'
                         AND meta_value <> '0'";
        $featured_product = (array) $wpdb->get_col( $wpdb->prepare( $sql_thumb, ...$product_ids ) );

        // Featured image of every variation that lives under any of those
        // products (product_variation rows have post_parent = product_id).
        $sql_var_thumb = "SELECT DISTINCT pm.meta_value
                          FROM {$wpdb->postmeta} pm
                          INNER JOIN {$wpdb->posts} v ON v.ID = pm.post_id
                          WHERE pm.meta_key = '_thumbnail_id'
                            AND v.post_type = 'product_variation'
                            AND v.post_parent IN ($product_phs)
                            AND pm.meta_value REGEXP '^[0-9]+$'
                            AND pm.meta_value <> '0'";
        $featured_var = (array) $wpdb->get_col( $wpdb->prepare( $sql_var_thumb, ...$product_ids ) );

        // Gallery image lists (CSV).
        $sql_gallery = "SELECT meta_value FROM {$wpdb->postmeta}
                        WHERE meta_key = '_product_image_gallery'
                          AND post_id IN ($product_phs)
                          AND meta_value <> ''";
        $gallery_blobs = (array) $wpdb->get_col( $wpdb->prepare( $sql_gallery, ...$product_ids ) );

        $all = array_merge( array_map( 'intval', $featured_product ), array_map( 'intval', $featured_var ) );
        foreach ( $gallery_blobs as $blob ) {
            foreach ( explode( ',', (string) $blob ) as $id ) {
                $id = (int) trim( $id );
                if ( $id > 0 ) { $all[] = $id; }
            }
        }
        if ( empty( $all ) ) {
            return [];
        }
        $all = array_values( array_unique( array_filter( $all, static fn( $i ) => $i > 0 ) ) );

        // Validate: only attachments with an image mime.
        $a_phs = implode( ',', array_fill( 0, count( $all ), '%d' ) );
        $valid = (array) $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND post_mime_type LIKE 'image/%%'
               AND ID IN ($a_phs)
             ORDER BY ID ASC",
            ...$all
        ) );

        return array_map( 'intval', $valid );
    }

    /**
     * Collect every attachment ID referenced by a WooCommerce product or
     * product variation as featured image or in the gallery. Result is
     * deduplicated, validated against `wp_posts.post_mime_type LIKE
     * 'image/%'`, and ordered by ID for stable batch pagination.
     *
     * Used by `repair_metadata_paths()` to scope the brute-force
     * regenerate-thumbnails pass to product imagery only — site title /
     * blog / theme demo images are left untouched, which keeps the run
     * an order of magnitude faster than the Regenerate Thumbnails
     * plugin's "all images" sweep.
     *
     * @return list<int>
     */
    private static function collect_all_product_image_attachment_ids(): array {
        global $wpdb;

        // Featured images of products + variations.
        $featured = (array) $wpdb->get_col(
            "SELECT DISTINCT pm.meta_value
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_thumbnail_id'
               AND p.post_type IN ('product', 'product_variation')
               AND pm.meta_value REGEXP '^[0-9]+$'
               AND pm.meta_value <> '0'"
        );

        // Gallery image lists (comma-separated attachment IDs).
        $gallery_blobs = (array) $wpdb->get_col(
            "SELECT DISTINCT pm.meta_value
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_product_image_gallery'
               AND p.post_type = 'product'
               AND pm.meta_value <> ''"
        );

        $all = array_map( 'intval', $featured );
        foreach ( $gallery_blobs as $blob ) {
            foreach ( explode( ',', (string) $blob ) as $id ) {
                $id = (int) trim( $id );
                if ( $id > 0 ) { $all[] = $id; }
            }
        }
        if ( empty( $all ) ) {
            return [];
        }
        $all = array_values( array_unique( $all ) );

        // Validate: only attachments whose mime is image/*. IN-clause is
        // bounded by the unique product-attachment count (rarely > a few
        // thousand) so it fits comfortably in MySQL's max_allowed_packet.
        $placeholders = implode( ',', array_fill( 0, count( $all ), '%d' ) );
        $valid = (array) $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND post_mime_type LIKE 'image/%%'
               AND ID IN ($placeholders)
             ORDER BY ID ASC",
            ...$all
        ) );

        return array_map( 'intval', $valid );
    }

    /**
     * Forensic dump for a single attachment — compares what the DB
     * stores against what WordPress actually renders. Pinpoints
     * whether the srcset breakage is DB state (metadata file key
     * lost its YYYY/MM) or a runtime filter (image CDN / webp
     * optimizer / LiteSpeed rewriting URLs at output time).
     *
     * Reads `_wp_attached_file` and `_wp_attachment_metadata` straight
     * from postmeta (unfiltered) AND via `wp_get_attachment_metadata()`
     * (filtered). If the two disagree, a filter is mutating metadata
     * on the way out.
     *
     * @return array{
     *     aid:int, post_type:string, mime:string,
     *     raw_attached:string, raw_meta_file:string, raw_meta_sizes_keys:list<string>,
     *     filtered_meta_file:string, filtered_meta_sizes_keys:list<string>,
     *     rendered_src:string, rendered_srcset:string,
     *     rendered_full_url:string,
     *     base_url:string, base_dir:string,
     *     diagnosis:string
     * }
     */
    public static function diagnose_attachment( int $aid ): array {
        global $wpdb;

        $post = get_post( $aid );
        if ( ! $post || $post->post_type !== 'attachment' ) {
            return [
                'aid'       => $aid,
                'diagnosis' => 'Not an attachment.',
            ];
        }

        $raw_attached = (string) $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta}
             WHERE post_id = %d AND meta_key = '_wp_attached_file' LIMIT 1",
            $aid
        ) );
        $raw_meta_blob = (string) $wpdb->get_var( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta}
             WHERE post_id = %d AND meta_key = '_wp_attachment_metadata' LIMIT 1",
            $aid
        ) );
        $raw_meta = $raw_meta_blob !== '' ? @maybe_unserialize( $raw_meta_blob ) : [];
        if ( ! is_array( $raw_meta ) ) { $raw_meta = []; }

        $filtered_meta = wp_get_attachment_metadata( $aid );
        if ( ! is_array( $filtered_meta ) ) { $filtered_meta = []; }

        $uploads = wp_get_upload_dir();

        // What the theme/product-card would actually render at the
        // WooCommerce thumbnail size — src + srcset — going through
        // every live filter the stack has registered.
        $thumb_size = 'woocommerce_thumbnail';
        $html       = wp_get_attachment_image( $aid, $thumb_size );

        $src     = '';
        $srcset  = '';
        if ( preg_match( '/\ssrc="([^"]+)"/', $html, $m ) )    { $src    = $m[1]; }
        if ( preg_match( '/\ssrcset="([^"]+)"/', $html, $m ) ) { $srcset = $m[1]; }

        $full_url = (string) wp_get_attachment_url( $aid );

        // Compare raw vs filtered `file` keys to localise the bug.
        $raw_file      = isset( $raw_meta['file'] )      ? (string) $raw_meta['file']      : '';
        $filtered_file = isset( $filtered_meta['file'] ) ? (string) $filtered_meta['file'] : '';

        // Disk-reality probes: the renamer ALWAYS rewrites local files
        // via PHP rename() on the raw basedir + _wp_attached_file path.
        // If PHP cannot stat / read / write that path — even though the
        // web server can serve the URL (via different user, open_basedir
        // exception, mod_xsendfile, or symlink) — rename will ENOENT.
        $raw_path     = $raw_attached !== '' ? trailingslashit( (string) $uploads['basedir'] ) . $raw_attached : '';
        $disk_present = $raw_path !== '' && self::path_really_exists( $raw_path );
        $disk_probes  = [];
        if ( $raw_path !== '' ) {
            clearstatcache( true, $raw_path );
            $disk_probes['raw_path']             = $raw_path;
            $disk_probes['file_exists']          = file_exists( $raw_path ) ? 'yes' : 'NO';
            $disk_probes['is_file']              = @is_file( $raw_path ) ? 'yes' : 'NO';
            $disk_probes['is_readable']          = @is_readable( $raw_path ) ? 'yes' : 'NO';
            $disk_probes['is_writable']          = @is_writable( $raw_path ) ? 'yes' : 'NO';
            $disk_probes['filesize_bytes']       = @filesize( $raw_path );
            $disk_probes['realpath']             = @realpath( $raw_path );
            $disk_probes['parent_exists']        = @is_dir( dirname( $raw_path ) ) ? 'yes' : 'NO';
            $disk_probes['parent_writable']      = @is_writable( dirname( $raw_path ) ) ? 'yes' : 'NO';

            $owner = @fileowner( $raw_path );
            $group = @filegroup( $raw_path );
            $disk_probes['file_owner_uid']       = $owner !== false ? $owner : 'unreadable';
            $disk_probes['file_group_gid']       = $group !== false ? $group : 'unreadable';
            $disk_probes['file_perms_octal']     = @fileperms( $raw_path ) !== false ? substr( sprintf( '%o', @fileperms( $raw_path ) ), -4 ) : 'unreadable';

            // Search the parent dir for the basename — proves whether PHP
            // can SEE the file under a slightly different name (URL-encoded
            // chars, NFC vs NFD unicode normalisation, leading/trailing
            // whitespace) versus truly cannot see it at all.
            $parent_listing_sample = [];
            if ( @is_dir( dirname( $raw_path ) ) && function_exists( 'glob' ) ) {
                $needle = basename( $raw_path );
                $stem   = pathinfo( $needle, PATHINFO_FILENAME );
                $hits   = @glob( dirname( $raw_path ) . '/' . substr( $stem, 0, max( 8, (int) ( strlen( $stem ) / 3 ) ) ) . '*' ) ?: [];
                foreach ( array_slice( $hits, 0, 5 ) as $h ) {
                    $parent_listing_sample[] = basename( $h );
                }
            }
            $disk_probes['parent_glob_sample']   = $parent_listing_sample;
        }

        // PHP context — pinpoints user / open_basedir / chroot mismatch
        // when the web server serves the URL but PHP cannot stat the file.
        $php_context = [
            'class_version'          => self::CLASS_VERSION,
            'php_user_uid'           => function_exists( 'posix_geteuid' ) ? @posix_geteuid() : 'posix-unavailable',
            'php_user_gid'           => function_exists( 'posix_getegid' ) ? @posix_getegid() : 'posix-unavailable',
            'open_basedir'           => (string) ini_get( 'open_basedir' ),
            'safe_mode'              => (string) ini_get( 'safe_mode' ),
            'php_sapi'               => (string) PHP_SAPI,
        ];

        $diag = [];
        if ( $raw_attached === '' ) {
            $diag[] = 'Missing _wp_attached_file in DB.';
        }
        if ( $raw_file === '' ) {
            $diag[] = 'Raw _wp_attachment_metadata[file] is empty — srcset will drop the YYYY/MM dirname.';
        } elseif ( dirname( $raw_file ) === '.' ) {
            $diag[] = 'Raw _wp_attachment_metadata[file] has no subdir (' . $raw_file . ') — DB-level breakage, repair_metadata_paths() will fix.';
        } elseif ( $raw_file !== $raw_attached ) {
            $diag[] = 'Raw meta[file] (' . $raw_file . ') differs from _wp_attached_file (' . $raw_attached . ').';
        }
        if ( $raw_file !== $filtered_file ) {
            $diag[] = 'wp_get_attachment_metadata filter is mutating [file]: raw="' . $raw_file . '" vs filtered="' . $filtered_file . '". A plugin hooks wp_get_attachment_metadata.';
        }
        if ( $srcset !== '' && strpos( $srcset, (string) $uploads['baseurl'] . '/' ) === 0 ) {
            $tail = substr( $srcset, strlen( (string) $uploads['baseurl'] ) + 1 );
            $first = strtok( $tail, ' ' );
            if ( $first !== false && strpos( $first, '/' ) === false ) {
                $diag[] = 'srcset URLs have no subdir — runtime-level breakage. Suspect: image CDN / WebP converter / LiteSpeed image optimization rewriting URLs via wp_calculate_image_srcset filter.';
            }
        }

        // Most useful diagnosis line for the rename ENOENT case.
        if ( $raw_attached !== '' && ! $disk_present ) {
            $reason = 'PHP cannot see the local file — rename() will ENOENT.';
            $obasedir = (string) ini_get( 'open_basedir' );
            if ( $obasedir !== '' && strpos( $raw_path, dirname( $obasedir ) ) === false ) {
                $reason .= ' open_basedir restriction may exclude this path: "' . $obasedir . '".';
            }
            if ( ( $disk_probes['file_exists'] ?? 'NO' ) === 'NO'
              && ! empty( $disk_probes['parent_glob_sample'] ) ) {
                $reason .= ' Parent directory IS accessible and contains other files (' . count( $disk_probes['parent_glob_sample'] ) . ' near matches), so the specific filename is genuinely missing on disk — likely an orphan attachment row from an interrupted import. Recovery: delete the orphan attachment, OR re-upload the missing image, OR run `wp media regenerate --only-missing`.';
            } elseif ( ( $disk_probes['parent_exists'] ?? 'NO' ) === 'NO' ) {
                $reason .= ' Even the parent YYYY/MM directory is not visible to PHP — strongly suggests open_basedir / chroot mismatch.';
            }
            $diag[] = $reason;
        }

        if ( empty( $diag ) ) {
            $diag[] = 'Looks consistent. If the browser still shows a broken image, purge page cache and CDN and hard-reload.';
        }

        return [
            'aid'                      => $aid,
            'class_version'            => self::CLASS_VERSION,
            'post_type'                => (string) $post->post_type,
            'mime'                     => (string) $post->post_mime_type,
            'raw_attached'             => $raw_attached,
            'raw_meta_file'            => $raw_file,
            'raw_meta_sizes_keys'      => isset( $raw_meta['sizes'] ) && is_array( $raw_meta['sizes'] )
                                          ? array_keys( $raw_meta['sizes'] ) : [],
            'filtered_meta_file'       => $filtered_file,
            'filtered_meta_sizes_keys' => isset( $filtered_meta['sizes'] ) && is_array( $filtered_meta['sizes'] )
                                          ? array_keys( $filtered_meta['sizes'] ) : [],
            'rendered_src'             => $src,
            'rendered_srcset'          => $srcset,
            'rendered_full_url'        => $full_url,
            'base_url'                 => (string) $uploads['baseurl'],
            'base_dir'                 => (string) $uploads['basedir'],
            'disk_present'             => $disk_present ? 'yes' : 'NO',
            'disk_probes'              => $disk_probes,
            'php_context'              => $php_context,
            'diagnosis'                => implode( ' | ', $diag ),
        ];
    }

    /**
     * Best-effort lookup: given a product slug or attachment ID, find
     * the thumbnail attachment ID to feed into `diagnose_attachment()`.
     * Falls back to scanning `_wp_attached_file` for a filename match
     * when neither yields a hit.
     */
    /**
     * Walk attachments in a paged sweep and rewrite `guid` whenever it
     * drifted from the `_wp_attached_file`-derived URL. Backfill for the
     * pre-fix renames where wp_update_post() silently dropped guid
     * updates (WP treats guid as immutable post-insert).
     *
     * Side effect this fixes: the "Featured Image From URL" /
     * "show-link-image" plugin (and any FIFU-style filter on
     * wp_get_attachment_url) returns `$post->guid` verbatim — so a stale
     * guid keeps serving pre-rename filenames to CTX Feed / GMC even
     * when every other postmeta is correct.
     *
     * @return array{ checked:int, updated:int, total:int, next_offset:int, done:bool }
     */
    public static function heal_stale_guids_batch( int $offset, int $limit ): array {
        global $wpdb;

        $total = (int) $wpdb->get_var( "
            SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
        " );

        $aids = $wpdb->get_col( $wpdb->prepare( "
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            ORDER BY ID ASC
            LIMIT %d OFFSET %d
        ", $limit, $offset ) );

        $uploads = wp_get_upload_dir();
        $baseurl = isset( $uploads['baseurl'] ) ? trailingslashit( (string) $uploads['baseurl'] ) : '';

        $checked = 0;
        $updated = 0;

        if ( $baseurl !== '' ) {
            foreach ( (array) $aids as $aid ) {
                $aid = (int) $aid;
                $checked++;

                $file = (string) get_post_meta( $aid, '_wp_attached_file', true );
                if ( $file === '' ) { continue; }

                // Expected guid: same shape WP would generate at upload
                // time for a non-external attachment.
                $expected_guid = $baseurl . ltrim( $file, '/' );
                $actual_guid   = (string) get_post_field( 'guid', $aid );

                if ( $actual_guid === $expected_guid ) { continue; }

                // Only heal guids that point INSIDE the uploads dir —
                // never rewrite genuinely external URLs (FIFU stores
                // remote URLs as guid; those must stay untouched).
                if ( strpos( $actual_guid, $baseurl ) !== 0 ) { continue; }

                $wpdb->update(
                    $wpdb->posts,
                    [ 'guid' => $expected_guid ],
                    [ 'ID'   => $aid ]
                );
                clean_post_cache( $aid );
                $updated++;
            }
        }

        $next_offset = $offset + count( (array) $aids );
        $done        = $next_offset >= $total || count( (array) $aids ) === 0;

        return [
            'checked'     => $checked,
            'updated'     => $updated,
            'total'       => $total,
            'next_offset' => $next_offset,
            'done'        => $done,
        ];
    }

    public static function resolve_attachment_id( string $needle ): int {
        $needle = trim( $needle );
        if ( $needle === '' ) { return 0; }

        if ( ctype_digit( $needle ) ) {
            $aid = (int) $needle;
            $post = get_post( $aid );
            if ( $post && $post->post_type === 'attachment' ) {
                return $aid;
            }
            // Also try as product id — return its featured image.
            if ( $post && $post->post_type === 'product' ) {
                $tid = (int) get_post_thumbnail_id( $aid );
                if ( $tid > 0 ) { return $tid; }
            }
        }

        // Try product slug.
        $product = get_page_by_path( $needle, OBJECT, 'product' );
        if ( $product ) {
            $tid = (int) get_post_thumbnail_id( $product->ID );
            if ( $tid > 0 ) { return $tid; }
        }

        // Fallback chain — find the attachment even AFTER it has been
        // renamed. We probe (in order):
        //   1. _wp_attached_file (current filename)
        //   2. _cmc_img_original_filename (pre-rename original — set by
        //      this renamer on first rename, so a search for "honiway"
        //      still resolves after the file was renamed to "wall-clock-")
        //   3. wp_posts.guid (the original upload URL — often retains the
        //      old basename even after _wp_attached_file is rewritten)
        global $wpdb;
        $like = '%' . $wpdb->esc_like( $needle ) . '%';

        $aid = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s
             ORDER BY post_id DESC LIMIT 1",
            $like
        ) );
        if ( $aid > 0 ) { return $aid; }

        $aid = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = %s AND meta_value LIKE %s
             ORDER BY post_id DESC LIMIT 1",
            self::META_ORIGINAL,
            $like
        ) );
        if ( $aid > 0 ) { return $aid; }

        $aid = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'attachment' AND guid LIKE %s
             ORDER BY ID DESC LIMIT 1",
            $like
        ) );
        return $aid;
    }

    /**
     * Rewrite every post whose post_content references an old URL. We
     * scope with LIKE on the old URL so we don't stream the entire
     * posts table into memory.
     */
    private static function update_post_content_urls( array $url_map ): int {
        global $wpdb;
        if ( empty( $url_map ) ) { return 0; }

        $changed = 0;

        foreach ( $url_map as $old => $new ) {
            if ( $old === $new ) { continue; }

            $ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s",
                '%' . $wpdb->esc_like( $old ) . '%'
            ) );

            foreach ( (array) $ids as $pid ) {
                $pid = (int) $pid;
                $p   = get_post( $pid );
                if ( ! $p ) { continue; }
                $new_content = str_replace( $old, $new, (string) $p->post_content, $count );
                if ( $count > 0 ) {
                    $wpdb->update(
                        $wpdb->posts,
                        [ 'post_content' => $new_content ],
                        [ 'ID' => $pid ]
                    );
                    clean_post_cache( $pid );
                    $changed += (int) $count;
                }
            }
        }

        // Also sweep postmeta + options for the same URL set. WooCommerce
        // single-product gallery renders <a href> / data-src / data-large_image
        // by resolving the FULL attachment URL through cached postmeta;
        // theme builders (Flatsome, Elementor, Divi, ACF) embed image URLs
        // verbatim inside serialized postmeta blobs and theme-options rows.
        // Those embedded copies survive `update_post_meta('_wp_attached_file')`
        // because they point to the OLD path, not the meta key we updated.
        $changed += self::update_postmeta_urls( $url_map );
        $changed += self::update_options_urls( $url_map );
        $changed += self::update_yoast_indexable_urls( $url_map );

        return $changed;
    }

    /**
     * Scan postmeta for verbatim image URLs and replace them with the new
     * counterparts. Handles both plain-string meta values and PHP-serialized
     * blobs (Elementor `_elementor_data`, Flatsome page-builder JSON, ACF
     * gallery arrays, etc.) by recursing through the unserialized tree
     * before re-serialising — straight str_replace on a serialized blob
     * would break the embedded length prefixes (`s:42:"..."` becomes
     * mismatched after replacement).
     */
    private static function update_postmeta_urls( array $url_map ): int {
        global $wpdb;
        if ( empty( $url_map ) ) { return 0; }

        $changed = 0;

        foreach ( $url_map as $old => $new ) {
            if ( $old === $new ) { continue; }

            $rows = (array) $wpdb->get_results( $wpdb->prepare(
                "SELECT meta_id, post_id, meta_value
                 FROM {$wpdb->postmeta}
                 WHERE meta_value LIKE %s",
                '%' . $wpdb->esc_like( $old ) . '%'
            ) );

            foreach ( $rows as $row ) {
                $orig    = (string) $row->meta_value;
                $updated = self::replace_in_maybe_serialized( $orig, $old, $new );
                if ( $updated !== $orig ) {
                    $wpdb->update(
                        $wpdb->postmeta,
                        [ 'meta_value' => $updated ],
                        [ 'meta_id' => (int) $row->meta_id ]
                    );
                    wp_cache_delete( (int) $row->post_id, 'post_meta' );
                    clean_post_cache( (int) $row->post_id );
                    $changed++;
                }
            }
        }

        return $changed;
    }

    /**
     * Scan options for verbatim image URLs (theme options, widget data,
     * Customizer presets, sidebars_widgets) and replace them. Same
     * unserialize-safe handling as postmeta.
     */
    private static function update_options_urls( array $url_map ): int {
        global $wpdb;
        if ( empty( $url_map ) ) { return 0; }

        $changed = 0;

        foreach ( $url_map as $old => $new ) {
            if ( $old === $new ) { continue; }

            $rows = (array) $wpdb->get_results( $wpdb->prepare(
                "SELECT option_id, option_name, option_value
                 FROM {$wpdb->options}
                 WHERE option_value LIKE %s",
                '%' . $wpdb->esc_like( $old ) . '%'
            ) );

            foreach ( $rows as $row ) {
                $orig    = (string) $row->option_value;
                $updated = self::replace_in_maybe_serialized( $orig, $old, $new );
                if ( $updated !== $orig ) {
                    $wpdb->update(
                        $wpdb->options,
                        [ 'option_value' => $updated ],
                        [ 'option_id' => (int) $row->option_id ]
                    );
                    wp_cache_delete( (string) $row->option_name, 'options' );
                    $changed++;
                }
            }
        }

        return $changed;
    }

    /**
     * Sweep Yoast SEO's custom `wp_yoast_indexable` table for image URLs
     * and replace them. Yoast stores the og:image / twitter:image URL
     * verbatim in dedicated columns of this table, separate from postmeta
     * — so the postmeta sweep above never touches them and the rendered
     * <meta property="og:image" content="..."> tag keeps pointing at the
     * old filename even after every other location has been updated.
     *
     * Columns swept (text columns that hold image URLs):
     *   - open_graph_image           : final og:image URL
     *   - open_graph_image_source    : source-of-truth URL
     *   - open_graph_image_meta      : JSON blob (slashes are escaped)
     *   - twitter_image              : final twitter:image URL
     *   - twitter_image_source       : source-of-truth URL
     *
     * Both the plain URL and the JSON-escaped variant (with backslash-
     * escaped slashes, e.g. `https:\/\/host\/path`) are replaced because
     * the *_meta column is a JSON blob produced by wp_json_encode(), which
     * by default escapes slashes.
     *
     * No-op when Yoast SEO is not installed (the indexable table is
     * absent), so the sweep is safe to run unconditionally.
     */
    private static function update_yoast_indexable_urls( array $url_map ): int {
        global $wpdb;
        if ( empty( $url_map ) ) { return 0; }

        $table = $wpdb->prefix . 'yoast_indexable';
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $wpdb->esc_like( $table )
        ) );
        if ( ! $exists ) { return 0; }

        // Hardcoded allowlist — never derived from input — so it is safe
        // to interpolate directly into the SQL.
        $columns = [
            'open_graph_image',
            'open_graph_image_source',
            'open_graph_image_meta',
            'twitter_image',
            'twitter_image_source',
        ];

        $changed         = 0;
        $touched_post_ids = [];

        foreach ( $url_map as $old => $new ) {
            if ( $old === $new || $old === '' ) { continue; }

            // Slash-escaped variant for the JSON meta column.
            $old_json = str_replace( '/', '\\/', $old );
            $new_json = str_replace( '/', '\\/', $new );
            $variants = [ [ $old, $new ] ];
            if ( $old_json !== $old ) {
                $variants[] = [ $old_json, $new_json ];
            }

            foreach ( $columns as $col ) {
                foreach ( $variants as [ $needle, $replacement ] ) {
                    // Capture object_ids so we can clean Yoast's per-post
                    // object cache after the update.
                    $ids = $wpdb->get_col( $wpdb->prepare(
                        "SELECT object_id FROM {$table} WHERE {$col} LIKE %s",
                        '%' . $wpdb->esc_like( $needle ) . '%'
                    ) );
                    foreach ( (array) $ids as $oid ) {
                        $oid = (int) $oid;
                        if ( $oid > 0 ) {
                            $touched_post_ids[ $oid ] = true;
                        }
                    }

                    $wpdb->query( $wpdb->prepare(
                        "UPDATE {$table}
                         SET {$col} = REPLACE({$col}, %s, %s)
                         WHERE {$col} LIKE %s",
                        $needle,
                        $replacement,
                        '%' . $wpdb->esc_like( $needle ) . '%'
                    ) );
                    $changed += (int) $wpdb->rows_affected;
                }
            }
        }

        // Bust the per-post indexable cache so Yoast's open-graph image
        // presenter re-reads the updated row instead of replaying the
        // old URL from object cache during the same admin request.
        if ( $touched_post_ids ) {
            foreach ( array_keys( $touched_post_ids ) as $pid ) {
                wp_cache_delete( (int) $pid, 'post_meta' );
                clean_post_cache( (int) $pid );
            }
        }

        return $changed;
    }

    /**
     * String-replace inside a value that may be a PHP-serialized blob
     * (postmeta / option_value). For raw strings we just str_replace; for
     * serialized data we recurse into the structure, replace at every
     * string leaf, then re-serialise so the length prefixes stay valid.
     *
     * @param string $value  Raw DB value (string or PHP-serialized blob).
     * @param string $old    URL to find.
     * @param string $new    URL to substitute.
     * @return string        Updated value (still as a DB-ready string).
     */
    private static function replace_in_maybe_serialized( string $value, string $old, string $new ): string {
        if ( $value === '' ) {
            return $value;
        }
        // Quick reject: nothing to replace.
        if ( strpos( $value, $old ) === false ) {
            return $value;
        }
        // Try unserialize; suppress notices because invalid serialized
        // payloads are common (just plain strings) and we treat them as
        // such.
        $unserialized = @unserialize( $value );
        if ( $unserialized === false && $value !== 'b:0;' ) {
            // Plain string field.
            return str_replace( $old, $new, $value );
        }
        // Serialized payload — walk the tree.
        $walked = self::deep_str_replace( $unserialized, $old, $new );
        return serialize( $walked );
    }

    /**
     * Recursive str_replace over arrays + objects. Used by the
     * unserialize-safe URL replacer.
     */
    private static function deep_str_replace( $data, string $old, string $new ) {
        if ( is_string( $data ) ) {
            return str_replace( $old, $new, $data );
        }
        if ( is_array( $data ) ) {
            $out = [];
            foreach ( $data as $k => $v ) {
                $out[ $k ] = self::deep_str_replace( $v, $old, $new );
            }
            return $out;
        }
        if ( is_object( $data ) ) {
            $obj = clone $data;
            foreach ( get_object_vars( $obj ) as $k => $v ) {
                $obj->$k = self::deep_str_replace( $v, $old, $new );
            }
            return $obj;
        }
        return $data;
    }
}
