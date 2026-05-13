<?php
/**
 * Products Eraser — wipes every WooCommerce product plus its variations and
 * every image referenced by them, so a freshly-cloned site starts from a
 * clean catalogue.
 *
 * Scope (intentional):
 *   - products         — post_type=product, any status (includes trash)
 *   - variations       — post_type=product_variation
 *   - attachments      — collected from THREE sources before deleting the
 *                        parent post (because removing the post wipes its
 *                        postmeta, after which the link is unrecoverable):
 *                         1) post_parent IN (product, variations)
 *                         2) `_thumbnail_id` postmeta of product + variations
 *                         3) `_product_image_gallery` CSV postmeta on product
 *                        Woo POD Master and most third-party importers create
 *                        attachments with `post_parent = 0` and link via
 *                        postmeta only — without sources 2+3, those images
 *                        leaked into the Media library forever.
 *
 * Deliberately NOT touched:
 *   - categories (product_cat), tags (product_tag), other product taxonomies
 *   - orders, customers, reviews/comments
 *   - shared media that aren't referenced by any product (post_parent=0 with
 *     no `_thumbnail_id` / gallery hit — typically pages, blog posts, or
 *     theme demo images)
 *
 * All deletions are FORCE deletes (bypass trash) — `wp_delete_post($id, true)`
 * / `wp_delete_attachment($id, true)` — so the operation is not recoverable.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Products_Eraser {

    /**
     * Count what would be deleted. Read-only.
     *
     * `orphan_variations` are variation rows whose `post_parent` is either
     * 0 or points at a wp_posts row that no longer exists — typically left
     * over after a prior wipe that only processed products and skipped the
     * children. They surface here so a follow-up Scan accurately reflects
     * what the next Delete pass will clean up.
     *
     * @return array{products:int, variations:int, orphan_variations:int, attachments:int}
     */
    public static function scan(): array {
        global $wpdb;

        $products = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product'"
        );

        $variations = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product_variation'"
        );

        // A variation is "orphan" if its parent is gone OR the parent is
        // no longer a product post type. The latter catches importers
        // that leave parents in `auto-draft` / `trash` / custom post
        // types so a literal `NOT EXISTS` check would miss them.
        $orphan_variations = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} v
             WHERE v.post_type = 'product_variation'
               AND ( v.post_parent = 0
                     OR NOT EXISTS (
                         SELECT 1 FROM {$wpdb->posts} p
                         WHERE p.ID = v.post_parent
                           AND p.post_type = 'product'
                     ) )"
        );

        // Count UNIQUE attachment IDs across all three reference sources
        // so the scan number matches what `delete_product()` will actually
        // remove. Building a set in PHP is cheaper than a triple UNION
        // (postmeta gallery values are CSV blobs that need parsing).
        $aid_set = [];

        // Source 1: post_parent.
        $by_parent = (array) $wpdb->get_col(
            "SELECT a.ID FROM {$wpdb->posts} a
             INNER JOIN {$wpdb->posts} p ON p.ID = a.post_parent
             WHERE a.post_type = 'attachment'
               AND p.post_type IN ('product','product_variation')"
        );
        foreach ( $by_parent as $aid ) {
            $aid_set[ (int) $aid ] = true;
        }

        // Source 2: _thumbnail_id of product + variation posts.
        $thumbs = (array) $wpdb->get_col(
            "SELECT pm.meta_value FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_thumbnail_id'
               AND p.post_type IN ('product','product_variation')
               AND pm.meta_value REGEXP '^[0-9]+$'
               AND pm.meta_value <> '0'"
        );
        foreach ( $thumbs as $aid ) {
            $aid_set[ (int) $aid ] = true;
        }

        // Source 3: _product_image_gallery CSV (products only — variations
        // don't have a gallery, just `_thumbnail_id`).
        $galleries = (array) $wpdb->get_col(
            "SELECT pm.meta_value FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_product_image_gallery'
               AND p.post_type = 'product'
               AND pm.meta_value <> ''"
        );
        foreach ( $galleries as $csv ) {
            foreach ( explode( ',', (string) $csv ) as $token ) {
                $token = (int) trim( $token );
                if ( $token > 0 ) {
                    $aid_set[ $token ] = true;
                }
            }
        }

        // Filter the set: only IDs that are actually image attachments
        // (defensive — postmeta values are unsanitised input that could
        // technically point at a non-attachment row).
        $attachments = 0;
        if ( ! empty( $aid_set ) ) {
            $ids_csv  = implode( ',', array_keys( $aid_set ) );
            $attachments = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                 WHERE post_type = 'attachment'
                   AND ID IN ($ids_csv)"
            );
        }

        return [
            'products'          => $products,
            'variations'        => $variations,
            'orphan_variations' => $orphan_variations,
            'attachments'       => $attachments,
        ];
    }

    /**
     * Total number of records the eraser will still process — used by
     * the AJAX handler to compute `done`. Products are drained first,
     * orphan variations second.
     */
    public static function remaining_deletable(): int {
        $s = self::scan();
        return (int) $s['products'] + (int) $s['orphan_variations'];
    }

    /**
     * Return the next N IDs to process. Two-phase:
     *
     *   1. Products (`post_type=product`).
     *   2. Once products are exhausted, orphan variations
     *      (`product_variation` rows whose parent no longer exists).
     *
     * Caller routes each ID through `delete_one()` which dispatches to
     * the right delete path. Batch size is small so each HTTP round-trip
     * stays inside PHP max_execution_time.
     *
     * @return list<int>
     */
    public static function next_batch( int $limit ): array {
        global $wpdb;
        $limit = max( 1, min( 50, $limit ) );

        // Phase 1: products.
        $rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'product'
             ORDER BY ID ASC
             LIMIT %d",
            $limit
        ) );
        if ( ! empty( $rows ) ) {
            return array_map( 'intval', (array) $rows );
        }

        // Phase 2: orphan variations — same definition as scan():
        // parent missing, parent_id = 0, OR parent is no longer a
        // `product` post type (e.g. drafted / trashed / re-typed).
        $rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT v.ID FROM {$wpdb->posts} v
             WHERE v.post_type = 'product_variation'
               AND ( v.post_parent = 0
                     OR NOT EXISTS (
                         SELECT 1 FROM {$wpdb->posts} p
                         WHERE p.ID = v.post_parent
                           AND p.post_type = 'product'
                     ) )
             ORDER BY v.ID ASC
             LIMIT %d",
            $limit
        ) );

        return array_map( 'intval', (array) $rows );
    }

    /**
     * Route an ID returned by `next_batch()` to the right delete method
     * based on its actual post_type.
     *
     * @return array{product:int, variations:int, attachments:int, errors:list<string>}
     */
    public static function delete_one( int $id ): array {
        $type = get_post_type( $id );
        if ( $type === 'product' ) {
            return self::delete_product( $id );
        }
        if ( $type === 'product_variation' ) {
            return self::delete_orphan_variation( $id );
        }
        return [
            'product'     => 0,
            'variations'  => 0,
            'attachments' => 0,
            'errors'      => [ '#' . $id . ' is not a product / variation (got: ' . ( $type ?: 'missing' ) . ').' ],
        ];
    }

    /**
     * Force-delete a single orphan variation plus the attachments it
     * still references via `_thumbnail_id` or `post_parent`. Used by
     * `delete_one()` for the phase-2 sweep after every parent product
     * has already been wiped.
     *
     * @return array{product:int, variations:int, attachments:int, errors:list<string>}
     */
    public static function delete_orphan_variation( int $variation_id ): array {
        $stats = [
            'product'     => 0,
            'variations'  => 0,
            'attachments' => 0,
            'errors'      => [],
        ];

        $post = get_post( $variation_id );
        if ( ! $post || $post->post_type !== 'product_variation' ) {
            $stats['errors'][] = 'Variation #' . $variation_id . ' not found.';
            return $stats;
        }

        $set = [];

        $thumb = (int) get_post_meta( $variation_id, '_thumbnail_id', true );
        if ( $thumb > 0 ) { $set[ $thumb ] = true; }

        foreach ( self::attachments_for_parents( [ $variation_id ] ) as $aid ) {
            if ( $aid > 0 ) { $set[ $aid ] = true; }
        }

        foreach ( array_keys( $set ) as $aid ) {
            if ( get_post_type( $aid ) !== 'attachment' ) { continue; }
            if ( wp_delete_attachment( $aid, true ) ) {
                $stats['attachments']++;
            } else {
                $stats['errors'][] = 'Failed to delete attachment #' . $aid;
            }
        }

        if ( wp_delete_post( $variation_id, true ) ) {
            $stats['variations'] = 1;
        } else {
            $stats['errors'][] = 'Failed to delete orphan variation #' . $variation_id;
        }

        return $stats;
    }

    /**
     * Force-delete one product plus its variations and directly-attached
     * media. Safe to call repeatedly — if the product was already gone,
     * counts come back as zero.
     *
     * @return array{product:int, variations:int, attachments:int, errors:list<string>}
     */
    public static function delete_product( int $product_id ): array {
        $stats = [
            'product'     => 0,
            'variations'  => 0,
            'attachments' => 0,
            'errors'      => [],
        ];

        $post = get_post( $product_id );
        if ( ! $post || $post->post_type !== 'product' ) {
            $stats['errors'][] = 'Product #' . $product_id . ' not found.';
            return $stats;
        }

        $variation_ids = get_children( [
            'post_parent' => $product_id,
            'post_type'   => 'product_variation',
            'numberposts' => -1,
            'fields'      => 'ids',
            'post_status' => 'any',
        ] );
        $variation_ids = array_map( 'intval', (array) $variation_ids );

        $parent_ids = array_merge( [ $product_id ], $variation_ids );

        // Collect attachments from ALL THREE sources BEFORE we delete
        // any post — wp_delete_post() wipes postmeta atomically, so once
        // the product is gone we can't recover `_thumbnail_id` /
        // `_product_image_gallery` references and any orphaned image
        // (Woo POD's typical post_parent=0 case) leaks into Media Library
        // forever.
        $attachment_ids = self::collect_all_referenced_attachments( $product_id, $variation_ids, $parent_ids );

        foreach ( $attachment_ids as $aid ) {
            // Defensive: only delete if the row is actually an attachment
            // (postmeta values are unsanitised — a bad meta_value pointing
            // at a non-attachment ID must not nuke an unrelated post).
            if ( get_post_type( $aid ) !== 'attachment' ) {
                continue;
            }
            if ( wp_delete_attachment( $aid, true ) ) {
                $stats['attachments']++;
            } else {
                $stats['errors'][] = 'Failed to delete attachment #' . $aid;
            }
        }

        foreach ( $variation_ids as $vid ) {
            if ( wp_delete_post( $vid, true ) ) {
                $stats['variations']++;
            } else {
                $stats['errors'][] = 'Failed to delete variation #' . $vid;
            }
        }

        if ( wp_delete_post( $product_id, true ) ) {
            $stats['product'] = 1;
        } else {
            $stats['errors'][] = 'Failed to delete product #' . $product_id;
        }

        return $stats;
    }

    /**
     * Resolve every attachment ID a product (or its variations) refers to,
     * across the three places WooCommerce / Woo POD / theme builders may
     * have stored the link:
     *
     *   1. `wp_posts.post_parent` IN (product_id, variation_ids).
     *      Catches the standard upload flow where Media Library is filtered
     *      to "Uploaded to this product".
     *   2. `_thumbnail_id` postmeta on product + each variation.
     *      Catches Woo POD imports that create attachments with
     *      `post_parent = 0` and link only via featured-image meta.
     *   3. `_product_image_gallery` postmeta on product (CSV).
     *      Catches gallery items that are likewise `post_parent = 0`.
     *
     * Returns a deduplicated, integer-cast list. Caller is still responsible
     * for the `get_post_type() === 'attachment'` check before deleting.
     *
     * @param list<int> $variation_ids
     * @param list<int> $parent_ids       product_id + variation_ids merged
     * @return list<int>
     */
    private static function collect_all_referenced_attachments( int $product_id, array $variation_ids, array $parent_ids ): array {
        $set = [];

        // Source 1: post_parent.
        foreach ( self::attachments_for_parents( $parent_ids ) as $aid ) {
            if ( $aid > 0 ) { $set[ $aid ] = true; }
        }

        // Source 2: _thumbnail_id of product + every variation.
        foreach ( $parent_ids as $pid ) {
            $thumb = (int) get_post_meta( $pid, '_thumbnail_id', true );
            if ( $thumb > 0 ) { $set[ $thumb ] = true; }
        }

        // Source 3: _product_image_gallery on the product itself.
        $gallery = (string) get_post_meta( $product_id, '_product_image_gallery', true );
        if ( $gallery !== '' ) {
            foreach ( explode( ',', $gallery ) as $token ) {
                $token = (int) trim( $token );
                if ( $token > 0 ) { $set[ $token ] = true; }
            }
        }

        return array_keys( $set );
    }

    /**
     * @param list<int> $parent_ids
     * @return list<int>
     */
    private static function attachments_for_parents( array $parent_ids ): array {
        if ( ! $parent_ids ) {
            return [];
        }
        global $wpdb;
        $in = implode( ',', array_map( 'intval', $parent_ids ) );
        $rows = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND post_parent IN ($in)"
        );
        return array_map( 'intval', (array) $rows );
    }
}
