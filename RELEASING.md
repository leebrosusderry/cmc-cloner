# Auto-update setup + release workflow

This plugin self-updates via GitHub Releases. Once configured, every site running CMC Cloner shows a standard WordPress "Update available" notice within ~12 hours of you publishing a new release.

---

## One-time setup (do this once, before first release)

### 1. Create the GitHub repo and push the plugin

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/cmc-plugin/wp-content/plugins/cmc-cloner

# Sanity check — make sure no secrets are about to be committed.
git grep -i "sk-\|api[-_]key\|password\|token" || echo "Clean"

git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/cmc-cloner.git
git push -u origin main
```

The `.gitignore` already excludes `.env`, `.local.php`, `*.log`, `.DS_Store`, `.vscode/`, etc.

### 2. Point the plugin at the repo

Edit `cmc-cloner.php`, line ~30:

```php
define( 'CMC_CLONER_GITHUB_REPO', 'YOUR-USERNAME/cmc-cloner' );
```

Empty string disables the updater silently (useful for local dev). Commit + push.

### 3. Sanity check the GitHub Actions permissions

Repo Settings → Actions → General → **Workflow permissions** → set to "Read and write permissions". Required so the release workflow can create releases + upload ZIP assets.

---

## Releasing a new version

### Day-to-day coding

Code in VSCode, commit + push to `main` as often as you want. **Commits alone don't trigger updates** — only Git tags do.

```bash
# normal dev cycle, no release
git add .
git commit -m "Fix variation orphan cleanup"
git push
```

### When you want to ship an update

```bash
./bin/release.sh 0.9.11
```

The script:

1. Validates the version is semver-shaped.
2. Refuses to run on a dirty tree or non-default branch.
3. Patches the version in **two** places in `cmc-cloner.php`:
   - The `* Version:` plugin header line
   - The `define( 'CMC_CLONER_VERSION', ... )` constant
4. Commits the bump as `Release vX.Y.Z`.
5. Tags `vX.Y.Z`.
6. Pushes branch + tag.

The tag push triggers `.github/workflows/release.yml` which:

1. Re-verifies the version in the file matches the tag.
2. Builds a clean ZIP (excludes `.git/`, `.github/`, `.vscode/`, `.claude/`, `*.log`, etc.).
3. Publishes a GitHub Release with the ZIP attached as an asset.

Watch the run at `https://github.com/YOUR-USERNAME/cmc-cloner/actions`. ~1 minute end-to-end.

### Manual fallback (if the workflow breaks)

If the Actions workflow fails for any reason, you can publish the release by hand:

1. On the GitHub repo → Releases → Draft a new release.
2. Choose the tag you already pushed.
3. Build the ZIP locally (just zip the plugin folder, exclude dev files).
4. Drag the ZIP into the release-assets area.
5. Publish.

The site-side updater only requires `releases/latest` to exist with a `.zip` asset; it doesn't care whether the workflow or you uploaded it.

---

## How a site picks up the update

1. Every ~12 hours, WordPress rebuilds its plugin-update transient.
2. `CMC_Updater::inject_update()` fetches `releases/latest` from GitHub, compares to the installed `CMC_CLONER_VERSION`.
3. If newer, the site shows "Update available" on the Plugins screen.
4. Admin clicks **Update** → WP downloads the ZIP → unzips → replaces the plugin folder → runs activation hook → `CMC_Migrations::run_pending()` applies any pending schema changes.

To force a check immediately (instead of waiting up to 12h):

- WP admin → Dashboard → Updates → **Check Again**, or
- WP admin → Plugins → the page load itself flushes the cache when the transient is stale.

---

## Adding a data-shape migration

When a release changes the shape of an option / postmeta / table:

1. Bump `CMC_CLONER_VERSION` (the release script does this for you).
2. In `includes/class-migrations.php`, add an entry to `MIGRATIONS`:

   ```php
   private const MIGRATIONS = [
       '0.9.11' => 'migrate_to_0_9_11',
   ];
   ```

3. Implement the static method below the registry:

   ```php
   private static function migrate_to_0_9_11(): void {
       $old = get_option( 'cmc_old_key', null );
       if ( $old === null ) { return; } // idempotent
       update_option( 'cmc_new_key', [ 'value' => $old, 'at' => time() ], false );
       delete_option( 'cmc_old_key' );
   }
   ```

4. Test on a dev site first: upgrade an instance from 0.9.10 to 0.9.11 and verify the option is migrated.

Migrations run on every `plugins_loaded` if `cmc_db_version` < `CMC_CLONER_VERSION`. Failures don't crash the page — they log to `error_log` and retry on the next request, leaving the version pointer at the last successful step.

---

## Pre-flight checklist before every release

- [ ] Tests pass on the dev XAMPP site (manually click through Site Setup, Run All, etc.)
- [ ] No new `.env` or `*.local.php` files committed
- [ ] Tag version matches the planned release (e.g. patch vs. minor)
- [ ] If schema changed: migration added + `MIGRATIONS` registry updated
- [ ] Changelog reasoning lives in commit messages since the previous tag (release notes auto-generate from them)

---

## Troubleshooting

**"Update available" never shows on a site**

- Check `CMC_CLONER_GITHUB_REPO` is set correctly (not empty).
- Check the latest release on GitHub has a `.zip` asset attached (look at the file list under "Assets" on the release page).
- Force-flush: in WP admin go to Dashboard → Updates → Check Again.
- If still nothing, add `define( 'WP_DEBUG', true );` in `wp-config.php` and check `wp-content/debug.log` for `[CMC_Updater]` messages.

**Update downloads but install fails with "Could not create directory"**

WP couldn't write to `wp-content/plugins/`. Check directory permissions on the host. The updater can't help here — it's the same constraint as any other plugin update.

**Update installs but plugin shows as deactivated afterwards**

The unzipped folder name didn't match the expected slug, so WP installed to a new folder. The `fix_source_folder()` hook in `class-updater.php` should prevent this when the release ZIP is built by the workflow. If you uploaded the ZIP by hand, make sure its top-level entry is exactly `cmc-cloner/`.
