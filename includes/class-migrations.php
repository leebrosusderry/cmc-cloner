<?php
/**
 * CMC Cloner — Schema migration framework.
 *
 * Why this exists:
 *   - Auto-update means cloned sites jump from version X to Y in one
 *     click. If Y assumes a different option shape, postmeta key, or
 *     DB column than X, the site explodes the moment new code reads
 *     legacy data.
 *   - WP's built-in plugin update flow runs the activation hook but
 *     does NOT track which schema version the data is at. We track it
 *     ourselves in the `cmc_db_version` option and run any missing
 *     migrations on `plugins_loaded`.
 *
 * How to add a migration when you change data shape:
 *   1. Bump CMC_CLONER_VERSION in cmc-cloner.php (e.g. 0.9.11).
 *   2. Add an entry to MIGRATIONS below:
 *          '0.9.11' => 'migrate_to_0_9_11',
 *      keyed by the version this migration lands in.
 *   3. Implement the static method below. Make it idempotent — it may
 *      run on a site whose data is already in the new shape (e.g. if a
 *      previous attempt crashed midway). Default to "no-op when already
 *      correct".
 *   4. After your release, every site that updates past 0.9.11 will
 *      run `migrate_to_0_9_11` exactly once.
 *
 * Idempotency contract for every migration:
 *   - Read current shape, decide if rewrite is needed.
 *   - If already in new shape → return without touching anything.
 *   - Never assume data starts in the OLD shape.
 *
 * Failure handling:
 *   - A migration that throws an exception or returns false (we don't
 *     enforce return values yet, just exceptions) leaves the version
 *     pointer at the LAST successful migration. Next page load will
 *     retry the failed step. Add a `try { ... } catch` if a partial
 *     write would corrupt data — preferred pattern is "write to new
 *     key, swap-and-delete old key" so a crash mid-migration just
 *     leaves both keys present.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CMC_Migrations {

    /** Option storing the schema version this site has been migrated TO. */
    private const VERSION_OPTION = 'cmc_db_version';

    /**
     * Version → static method name. Keys MUST be PHP-comparable version
     * strings (version_compare). Order doesn't matter — we sort here.
     *
     * @var array<string, string>
     */
    private const MIGRATIONS = [
        // Placeholder: no migrations yet. The first real one lives at
        // the version that actually changes a data shape.
        //
        // Example:
        //   '0.9.11' => 'migrate_to_0_9_11',
    ];

    /**
     * Run every migration whose version is > the recorded `cmc_db_version`
     * AND <= the current `CMC_CLONER_VERSION`. Called on every load — cheap
     * when nothing pending (1 option read).
     */
    public static function run_pending(): void {
        $current_version = (string) get_option( self::VERSION_OPTION, '0.0.0' );
        $code_version    = (string) CMC_CLONER_VERSION;

        // Fast path: already up to date.
        if ( version_compare( $current_version, $code_version, '>=' ) ) {
            return;
        }

        $migrations = self::MIGRATIONS;
        if ( empty( $migrations ) ) {
            // No registered migrations — just sync the marker so we
            // don't keep checking on every load.
            update_option( self::VERSION_OPTION, $code_version, false );
            return;
        }

        // Sort migration keys ascending by version so they apply in order.
        uksort( $migrations, 'version_compare' );

        foreach ( $migrations as $target_version => $method ) {
            // Skip migrations already applied (target <= current).
            if ( version_compare( $target_version, $current_version, '<=' ) ) {
                continue;
            }
            // Skip migrations newer than the code version — they belong
            // to a future release that isn't installed yet. (Can happen
            // briefly if a release with a new migration is rolled back.)
            if ( version_compare( $target_version, $code_version, '>' ) ) {
                continue;
            }

            // Run. Each migration is wrapped so one failure doesn't
            // mask the version-pointer bump for OTHER successful steps.
            try {
                if ( method_exists( self::class, $method ) ) {
                    self::{$method}();
                }
                update_option( self::VERSION_OPTION, $target_version, false );
                $current_version = $target_version;
            } catch ( \Throwable $e ) {
                // Leave the version pointer where it is so the same step
                // retries on next load. Log for diagnosis.
                if ( function_exists( 'error_log' ) ) {
                    error_log( '[CMC_Migrations] ' . $method . ' failed: ' . $e->getMessage() );
                }
                return; // Stop the chain — don't run later migrations on a half-migrated DB.
            }
        }

        // Final sync: align the marker with code version so a future
        // release with no migrations still bumps the pointer.
        update_option( self::VERSION_OPTION, $code_version, false );
    }

    /**
     * Called from the plugin activation hook on a FRESH install (no
     * previous `cmc_db_version` option). Stamps the marker at the
     * current code version so the on-load migration loop doesn't
     * re-run historical migrations against a virgin DB.
     */
    public static function mark_baseline_on_fresh_install(): void {
        if ( get_option( self::VERSION_OPTION, null ) === null ) {
            update_option( self::VERSION_OPTION, (string) CMC_CLONER_VERSION, false );
        }
    }

    /**
     * Diagnostics: returns the recorded schema version, the code version,
     * and the list of migrations that would run if `run_pending()` were
     * called right now. Useful for an admin-side debug pane.
     *
     * @return array{ recorded:string, code:string, pending:list<string> }
     */
    public static function status(): array {
        $recorded = (string) get_option( self::VERSION_OPTION, '0.0.0' );
        $code     = (string) CMC_CLONER_VERSION;

        $pending = [];
        $migrations = self::MIGRATIONS;
        uksort( $migrations, 'version_compare' );
        foreach ( $migrations as $v => $_method ) {
            if ( version_compare( $v, $recorded, '>' ) && version_compare( $v, $code, '<=' ) ) {
                $pending[] = $v;
            }
        }

        return [
            'recorded' => $recorded,
            'code'     => $code,
            'pending'  => $pending,
        ];
    }

    // ---------------------------------------------------------------
    // Migration implementations. Add one method per `MIGRATIONS` entry.
    // ---------------------------------------------------------------
    //
    // Pattern (copy-paste when adding a real migration):
    //
    // private static function migrate_to_0_9_11(): void {
    //     // 1. Read old data.
    //     $old = get_option( 'cmc_old_key', null );
    //     if ( $old === null ) {
    //         return; // Already migrated OR never had old data.
    //     }
    //     // 2. Transform.
    //     $new = [ 'value' => $old, 'migrated_at' => time() ];
    //     // 3. Write new key, delete old.
    //     update_option( 'cmc_new_key', $new, false );
    //     delete_option( 'cmc_old_key' );
    // }
}
