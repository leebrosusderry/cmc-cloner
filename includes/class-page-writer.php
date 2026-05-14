<?php
/**
 * Page Writer — updates a page's post_content with generated content,
 * backs up the original on first clone, and exposes a Revert helper.
 *
 * Only post_content is touched. Title, slug, excerpt, featured image,
 * parent, template, and menu order are left untouched.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Page_Writer {

    /**
     * @return true|WP_Error
     */
    public static function update(
        int $page_id,
        string $new_content,
        string $template_slug,
        string $skeleton_slug,
        int $style_seed
    ) {
        $p = get_post( $page_id );
        if ( ! $p || $p->post_type !== 'page' ) {
            return new WP_Error( 'cmc_not_page', 'The selected post is not a page.' );
        }

        $has_backup = get_post_meta( $page_id, '_cmc_original_content', true ) !== '';
        if ( ! $has_backup ) {
            update_post_meta( $page_id, '_cmc_original_content', $p->post_content );
        }

        $result = wp_update_post( [
            'ID'           => $page_id,
            'post_content' => $new_content,
        ], true );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        update_post_meta( $page_id, '_cmc_cloned',        1 );
        update_post_meta( $page_id, '_cmc_template',      $template_slug );
        update_post_meta( $page_id, '_cmc_skeleton',      $skeleton_slug );
        update_post_meta( $page_id, '_cmc_style_seed',    $style_seed );
        update_post_meta( $page_id, '_cmc_generated_at',  time() );

        /**
         * Fires after a CMC page has been successfully cloned/updated.
         * Listeners (e.g. CMC_Size_Guide) can perform side-effects such
         * as attaching niche-specific pages to the footer menu.
         *
         * @param int    $page_id        WP page ID just updated.
         * @param string $template_slug  Template that was applied.
         * @param string $skeleton_slug  Skeleton that was used.
         */
        do_action( 'cmc_cloner_page_updated', $page_id, $template_slug, $skeleton_slug );

        return true;
    }

    /**
     * @return true|WP_Error
     */
    public static function revert( int $page_id ) {
        $original = get_post_meta( $page_id, '_cmc_original_content', true );
        if ( ! is_string( $original ) || $original === '' ) {
            return new WP_Error( 'cmc_no_backup', 'No original content backup found for this page.' );
        }

        $p = get_post( $page_id );
        if ( ! $p || $p->post_type !== 'page' ) {
            return new WP_Error( 'cmc_not_page', 'The selected post is not a page.' );
        }

        $result = wp_update_post( [
            'ID'           => $page_id,
            'post_content' => $original,
        ], true );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        delete_post_meta( $page_id, '_cmc_original_content' );
        delete_post_meta( $page_id, '_cmc_cloned' );
        delete_post_meta( $page_id, '_cmc_template' );
        delete_post_meta( $page_id, '_cmc_skeleton' );
        delete_post_meta( $page_id, '_cmc_style_seed' );
        delete_post_meta( $page_id, '_cmc_generated_at' );

        return true;
    }
}
