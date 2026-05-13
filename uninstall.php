<?php
/**
 * Uninstall handler — only runs when the user deletes the plugin from the Plugins screen.
 *
 * Preserves post_meta backups (_cmc_original_content, _cmc_cloned, ...) so admins
 * can still revert generated pages manually after removing the plugin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'cmc_cloner_settings' );
delete_option( 'cmc_cloner_prompts' );
