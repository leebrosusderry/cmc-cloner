<?php
/**
 * Page Reader — lists WordPress pages and returns their raw post_content.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Page_Reader {

    public static function list_pages(): array {
        $pages = get_pages( [
            'sort_column' => 'post_title',
            'sort_order'  => 'ASC',
            'post_status' => [ 'publish', 'draft', 'private' ],
            'number'      => 0,
        ] );

        $out = [];
        foreach ( (array) $pages as $p ) {
            $out[] = [
                'id'       => (int) $p->ID,
                'title'    => $p->post_title !== '' ? $p->post_title : '(no title)',
                'slug'     => (string) $p->post_name,
                'status'   => (string) $p->post_status,
                'cloned'   => (bool) get_post_meta( $p->ID, '_cmc_cloned', true ),
                'template' => (string) get_post_meta( $p->ID, '_cmc_template', true ),
            ];
        }
        return $out;
    }

    public static function get_page( int $page_id ): ?array {
        $p = get_post( $page_id );
        if ( ! $p || $p->post_type !== 'page' ) {
            return null;
        }
        $generated_at = (int) get_post_meta( $p->ID, '_cmc_generated_at', true );
        return [
            'id'                 => (int) $p->ID,
            'title'              => (string) $p->post_title,
            'content'            => (string) $p->post_content,
            'status'             => (string) $p->post_status,
            'cloned'             => (bool) get_post_meta( $p->ID, '_cmc_cloned', true ),
            'template'           => (string) get_post_meta( $p->ID, '_cmc_template', true ),
            'skeleton'           => (string) get_post_meta( $p->ID, '_cmc_skeleton', true ),
            'style_seed'         => (int) get_post_meta( $p->ID, '_cmc_style_seed', true ),
            'generated_at'       => $generated_at,
            'generated_at_human' => $generated_at > 0
                ? sprintf( '%s ago', human_time_diff( $generated_at, current_time( 'timestamp' ) ) )
                : '',
            'has_backup'         => get_post_meta( $p->ID, '_cmc_original_content', true ) !== '',
            'edit_url'           => (string) get_edit_post_link( $p->ID, 'raw' ),
            'view_url'           => (string) get_permalink( $p->ID ),
        ];
    }
}
