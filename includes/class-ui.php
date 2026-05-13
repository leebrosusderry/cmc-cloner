<?php
/**
 * Tiny UI helper used by the admin views to keep markup DRY.
 *
 * Currently exposes the help-tooltip trigger — a small `?` button that
 * renders inline next to a label and reveals a richer description bubble
 * on hover / click. The bubble itself is rendered by the plugin's admin.js
 * which reads the `data-cmc-help-html` attribute from the trigger.
 *
 * Why a static helper instead of inline markup: the same widget is
 * rendered ~30+ times across Settings + Site Setup. Having one source of
 * truth means a future redesign (icon swap, accessibility tweak, kbd
 * shortcut) ripples through both views from a single edit.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_UI {

    /**
     * Build the HTML for a help-tooltip trigger.
     *
     * @param string $html  Tooltip body. May contain <code>, <strong>,
     *                      <em>, <p>, <br>, <ul>, <li>. Anything outside
     *                      that whitelist is stripped via wp_kses_post().
     * @return string       Inline trigger HTML — safe to echo.
     */
    public static function tooltip( string $html ): string {
        $payload = wp_kses_post( $html );
        if ( $payload === '' ) {
            return '';
        }
        return sprintf(
            '<button type="button" class="cmc-help" aria-label="%s" aria-expanded="false" data-cmc-help-html="%s">?</button>',
            esc_attr__( 'More information', 'cmc-cloner' ),
            esc_attr( $payload )
        );
    }

    /**
     * Echo a tooltip trigger. Convenience wrapper used in template scope
     * where `<?php CMC_UI::help( '...' ); ?>` reads cleaner than
     * `<?php echo CMC_UI::tooltip( '...' ); ?>`.
     */
    public static function help( string $html ): void {
        echo self::tooltip( $html );
    }
}
