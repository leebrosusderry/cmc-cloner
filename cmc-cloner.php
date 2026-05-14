<?php
/**
 * Plugin Name: CMC Cloner
 * Plugin URI:  https://example.com/cmc-cloner
 * Description: AI-powered page cloner for Flatsome-based sites. Rewrites Refund / Return / Contact / About / Privacy and other policy pages to be GMC-compliant with per-site style variation.
 * Version:     0.9.12
 * Author:      CMC
 * Text Domain: cmc-cloner
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CMC_CLONER_VERSION',  '0.9.12' );
define( 'CMC_CLONER_FILE',     __FILE__ );
define( 'CMC_CLONER_DIR',      plugin_dir_path( __FILE__ ) );
define( 'CMC_CLONER_URL',      plugin_dir_url( __FILE__ ) );
define( 'CMC_CLONER_BASENAME', plugin_basename( __FILE__ ) );

/**
 * GitHub repository for self-updates, in "owner/repo" form. Empty
 * disables the updater silently — useful for local dev installs and
 * for cloned sites that should pin to the source-built version.
 *
 * When set, the plugin polls https://api.github.com/repos/<owner/repo>/releases/latest
 * every ~12h and surfaces a standard WP "Update available" notice when
 * a newer release is published. See `includes/class-updater.php` and
 * `.github/workflows/release.yml` for the release-build pipeline.
 */
if ( ! defined( 'CMC_CLONER_GITHUB_REPO' ) ) {
    define( 'CMC_CLONER_GITHUB_REPO', 'leebrosusderry/cmc-cloner' ); // e.g. 'your-username/cmc-cloner'
}
require_once CMC_CLONER_DIR . 'includes/class-plugin.php';

CMC_Plugin::instance();
