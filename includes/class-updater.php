<?php
/**
 * CMC Cloner — Self-updater.
 *
 * Polls the GitHub Releases API of `CMC_CLONER_GITHUB_REPO` for new
 * versions and feeds results into WP's built-in plugin-update flow.
 * Once a new tag is published, WP shows the standard "Update available"
 * notice; clicking Update downloads the release ZIP, unzips, replaces
 * the plugin folder, and runs activation hooks — exactly the same path
 * as an update from wordpress.org.
 *
 * Why no third-party update-checker lib:
 *   - We need exactly one source (GitHub Releases) and one auth mode
 *     (anonymous public repo). PUC ships ~30 files of multi-vendor
 *     scaffolding we'd never exercise.
 *   - This file is ~150 lines and is the entire critical path of
 *     "decide whether to upgrade the plugin". Easier to audit + patch
 *     than a vendored library.
 *
 * Configuration:
 *   - Set `CMC_CLONER_GITHUB_REPO` in the main plugin file to
 *     "owner/repo" (e.g. "myorg/cmc-cloner"). Empty string disables
 *     the updater silently — useful for local-only dev installs.
 *   - The GitHub Release for each version MUST have a ZIP attached as
 *     an asset (built by the workflow in .github/workflows/release.yml).
 *     The default GitHub "Source code (zip)" link does NOT work: it
 *     unpacks into "owner-repo-<sha>/" instead of "cmc-cloner/", and
 *     contains dev files (.git, node_modules, etc.).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Updater {

    /** Transient name + TTL for caching the latest-release fetch.
     *  GitHub's anonymous rate limit is 60 req/h per IP, shared across
     *  every plugin on the host — so we cache aggressively. WP's own
     *  update transient already throttles checks to ~12h. */
    private const CACHE_KEY = 'cmc_cloner_latest_release';
    private const CACHE_TTL = 6 * HOUR_IN_SECONDS;

    public static function init(): void {
        if ( self::repo() === '' ) {
            return;
        }
        add_filter( 'pre_set_site_transient_update_plugins', [ self::class, 'inject_update' ] );
        add_filter( 'plugins_api',                           [ self::class, 'plugin_info' ], 10, 3 );
        add_filter( 'upgrader_source_selection',             [ self::class, 'fix_source_folder' ], 10, 4 );
        add_action( 'upgrader_process_complete',             [ self::class, 'flush_cache_after_update' ], 10, 2 );
    }

    /**
     * Hook: pre_set_site_transient_update_plugins.
     *
     * WP runs this filter while building the "which plugins need updating?"
     * transient (about every 12h, plus on every Plugins / Updates page
     * load). We compare the installed CMC_CLONER_VERSION against the
     * latest GitHub tag and, if newer, hand WP an update-info object so
     * the standard "Update available" notice + Update button appear.
     */
    public static function inject_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            return $transient;
        }
        $latest = self::fetch_latest_release();
        if ( ! $latest ) {
            return $transient;
        }
        if ( version_compare( $latest['version'], CMC_CLONER_VERSION, '<=' ) ) {
            // Installed >= released. Mark as "no update" so WP doesn't
            // keep nagging if the user previously dismissed an update.
            if ( isset( $transient->response[ CMC_CLONER_BASENAME ] ) ) {
                unset( $transient->response[ CMC_CLONER_BASENAME ] );
            }
            $transient->no_update[ CMC_CLONER_BASENAME ] = self::build_info_object( $latest, true );
            return $transient;
        }
        $transient->response[ CMC_CLONER_BASENAME ] = self::build_info_object( $latest, false );
        return $transient;
    }

    /**
     * Hook: plugins_api — drives the "View version X.Y.Z details" modal
     * on the Plugins screen so the user can read the changelog before
     * clicking Update.
     */
    public static function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }
        if ( ! isset( $args->slug ) || $args->slug !== self::slug() ) {
            return $result;
        }
        $latest = self::fetch_latest_release();
        if ( ! $latest ) {
            return $result;
        }
        return (object) [
            'name'          => 'CMC Cloner',
            'slug'          => self::slug(),
            'version'       => $latest['version'],
            'author'        => '<a href="https://github.com/' . esc_attr( self::repo() ) . '">CMC</a>',
            'homepage'      => 'https://github.com/' . self::repo(),
            'download_link' => $latest['zip_url'],
            'requires'      => '6.0',
            'requires_php'  => '8.0',
            'tested'        => get_bloginfo( 'version' ),
            'last_updated'  => $latest['published_at'],
            'sections'      => [
                'description' => 'AI-powered page cloner for Flatsome-based sites. Rewrites Refund / Return / Contact / About / Privacy and other policy pages to be GMC-compliant with per-site style variation.',
                'changelog'   => self::changelog_html( $latest['changelog'] ),
            ],
        ];
    }

    /**
     * Hook: upgrader_source_selection.
     *
     * GitHub's release-asset ZIPs (when built via our workflow) unpack
     * to "cmc-cloner/" cleanly, but `actions/upload-release-asset` or
     * the legacy `zipball_url` fallback produce "<owner>-<repo>-<sha>/".
     * If the unzipped folder name doesn't match the plugin slug, WP
     * installs into a brand-new directory and the old plugin is left
     * orphaned + DEACTIVATED. We rename the unzipped folder back to the
     * expected slug here, before WP moves it into wp-content/plugins.
     */
    public static function fix_source_folder( $source, $remote_source, $upgrader, $hook_extra ) {
        // Only act on our own plugin upgrades.
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== CMC_CLONER_BASENAME ) {
            return $source;
        }
        if ( ! is_string( $source ) || ! is_string( $remote_source ) ) {
            return $source;
        }
        $expected = self::slug();
        $current  = basename( untrailingslashit( $source ) );
        if ( $current === $expected ) {
            return $source;
        }
        $new_source = trailingslashit( $remote_source ) . $expected . '/';
        if ( $new_source === $source ) {
            return $source;
        }
        // Defensive: if a leftover directory with the target name exists,
        // remove it so rename() doesn't fail.
        if ( @is_dir( $new_source ) ) {
            @rename( $new_source, $new_source . '.bak-' . time() );
        }
        if ( @rename( $source, $new_source ) ) {
            return $new_source;
        }
        return $source;
    }

    /**
     * Hook: upgrader_process_complete.
     *
     * After ANY plugin update finishes (not just ours), drop our cache.
     * Prevents WP from immediately re-suggesting the same upgrade
     * because our 6h transient still says "new version available".
     */
    public static function flush_cache_after_update( $upgrader, $extra ): void {
        if ( ! is_array( $extra ) ) {
            return;
        }
        if ( ( $extra['action'] ?? '' ) !== 'update' ) {
            return;
        }
        if ( ( $extra['type'] ?? '' ) !== 'plugin' ) {
            return;
        }
        self::flush_cache();
    }

    /**
     * Force-clear the latest-release cache so the next plugins-update
     * transient build re-fetches from GitHub. Exposed for use by admin
     * actions / debug tools.
     */
    public static function flush_cache(): void {
        delete_site_transient( self::CACHE_KEY );
    }

    // ---------- internals ----------

    /** Plugin folder slug (e.g. "cmc-cloner"). */
    private static function slug(): string {
        return dirname( CMC_CLONER_BASENAME );
    }

    /** GitHub "owner/repo" or empty string if not configured. */
    private static function repo(): string {
        return defined( 'CMC_CLONER_GITHUB_REPO' ) ? trim( (string) CMC_CLONER_GITHUB_REPO ) : '';
    }

    /**
     * Shape the data WP expects in the update transient. Used for both
     * the "response" (update-available) and "no_update" branches.
     *
     * @param array{version:string, zip_url:string, changelog:string, published_at:string} $latest
     */
    private static function build_info_object( array $latest, bool $no_update ): object {
        return (object) [
            'id'           => 'cmc-cloner/' . CMC_CLONER_BASENAME,
            'slug'         => self::slug(),
            'plugin'       => CMC_CLONER_BASENAME,
            'new_version'  => $latest['version'],
            'url'          => 'https://github.com/' . self::repo(),
            'package'      => $no_update ? '' : $latest['zip_url'],
            'icons'        => [],
            'banners'      => [],
            'banners_rtl'  => [],
            'tested'       => get_bloginfo( 'version' ),
            'requires_php' => '8.0',
            'compatibility'=> new stdClass(),
        ];
    }

    /**
     * Fetch latest-release JSON from the GitHub API. Cached aggressively
     * (6h) because the unauthenticated rate limit is 60 req/h per IP.
     *
     * @return array{version:string, zip_url:string, changelog:string, published_at:string}|null
     */
    private static function fetch_latest_release(): ?array {
        $cached = get_site_transient( self::CACHE_KEY );
        if ( is_array( $cached ) && isset( $cached['version'], $cached['zip_url'] ) ) {
            return $cached;
        }

        $api_url = 'https://api.github.com/repos/' . self::repo() . '/releases/latest';
        $res     = wp_remote_get( $api_url, [
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'CMC-Cloner-Updater/' . CMC_CLONER_VERSION,
            ],
        ] );
        if ( is_wp_error( $res ) ) {
            // Cache a negative result for a short window so a network
            // hiccup doesn't hammer GitHub on every transient rebuild.
            set_site_transient( self::CACHE_KEY, [ 'version' => '0.0.0', 'zip_url' => '', 'changelog' => '', 'published_at' => '' ], 15 * MINUTE_IN_SECONDS );
            return null;
        }
        $code = (int) wp_remote_retrieve_response_code( $res );
        if ( $code !== 200 ) {
            // 404 = no releases yet, 403 = rate-limited, etc. Negative-cache
            // briefly to avoid spamming.
            set_site_transient( self::CACHE_KEY, [ 'version' => '0.0.0', 'zip_url' => '', 'changelog' => '', 'published_at' => '' ], 15 * MINUTE_IN_SECONDS );
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
            return null;
        }

        // Tag conventions: "v1.2.3" or bare "1.2.3". Strip a leading v
        // so version_compare works correctly.
        $version = ltrim( (string) $body['tag_name'], 'vV' );

        // Prefer a release ASSET (a properly built zip with a clean
        // top-level folder), fall back to the source zipball if no
        // asset is attached. The fallback works thanks to
        // fix_source_folder() above renaming the unzipped dir.
        $zip_url = '';
        if ( ! empty( $body['assets'] ) && is_array( $body['assets'] ) ) {
            foreach ( $body['assets'] as $asset ) {
                $name = (string) ( $asset['name'] ?? '' );
                $url  = (string) ( $asset['browser_download_url'] ?? '' );
                if ( $url !== '' && substr( strtolower( $name ), -4 ) === '.zip' ) {
                    $zip_url = $url;
                    break;
                }
            }
        }
        if ( $zip_url === '' ) {
            $zip_url = (string) ( $body['zipball_url'] ?? '' );
        }
        if ( $zip_url === '' ) {
            return null;
        }

        $payload = [
            'version'      => $version,
            'zip_url'      => $zip_url,
            'changelog'    => (string) ( $body['body'] ?? '' ),
            'published_at' => (string) ( $body['published_at'] ?? '' ),
        ];
        set_site_transient( self::CACHE_KEY, $payload, self::CACHE_TTL );
        return $payload;
    }

    /**
     * Convert a release body (typically Markdown) into HTML acceptable
     * for the plugin-info modal. WP's modal whitelists a handful of
     * tags via wp_kses_post() so we keep this minimal.
     */
    private static function changelog_html( string $markdown ): string {
        if ( $markdown === '' ) {
            return '<p>No changelog provided.</p>';
        }
        // Crude markdown → HTML: paragraphs + line breaks. Anything richer
        // (lists, code fences) shows up as raw markdown text, which is
        // acceptable for a developer-only changelog view.
        $escaped = esc_html( trim( $markdown ) );
        return '<pre style="white-space:pre-wrap;font-family:inherit;font-size:13px;">' . $escaped . '</pre>';
    }
}
