#!/usr/bin/env bash
# Bump the plugin version, commit, tag, and push — triggering the
# .github/workflows/release.yml workflow which builds the ZIP and
# publishes a GitHub Release.
#
# Usage:
#   ./bin/release.sh 0.9.11
#   ./bin/release.sh 1.0.0
#
# What it does:
#   1. Validates the version is semver-shaped (X.Y.Z).
#   2. Refuses to run on a dirty working tree (uncommitted changes).
#   3. Refuses to run on a non-default branch unless --force is passed.
#   4. Patches `Version:` header AND `CMC_CLONER_VERSION` constant in
#      cmc-cloner.php so the release workflow's verify step is happy.
#   5. Commits the bump, tags vX.Y.Z, and pushes both branch + tag.
#
# After the push, watch the Actions tab on GitHub to confirm the release
# job succeeds. The site-side updater will see the new version within
# ~12h, or immediately if you click "Check Again" on WP Updates.

set -euo pipefail

VERSION="${1:-}"
FORCE_BRANCH="${2:-}"

if [ -z "$VERSION" ]; then
    echo "Usage: $0 <version>   (e.g. $0 0.9.11)"
    exit 1
fi

# semver-ish: digits.digits.digits, optional pre-release suffix.
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$ ]]; then
    echo "Error: '$VERSION' is not a valid semver (expected X.Y.Z or X.Y.Z-suffix)."
    exit 1
fi

# Move to the plugin root so the relative paths below work from any CWD.
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR/.."

if [ ! -f cmc-cloner.php ]; then
    echo "Error: cmc-cloner.php not found at $(pwd) — run this from the plugin root or via ./bin/release.sh."
    exit 1
fi

# Refuse to run on a dirty tree: a half-staged change could land in the
# release commit by accident.
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "Error: working tree has uncommitted changes. Commit or stash first."
    git status --short
    exit 1
fi

# Don't accidentally release from a feature branch.
CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
DEFAULT_BRANCH="$(git remote show origin 2>/dev/null | sed -n '/HEAD branch/s/.*: //p' || echo main)"
if [ -z "$DEFAULT_BRANCH" ]; then DEFAULT_BRANCH="main"; fi

if [ "$CURRENT_BRANCH" != "$DEFAULT_BRANCH" ] && [ "$FORCE_BRANCH" != "--force" ]; then
    echo "Error: you're on '$CURRENT_BRANCH' but the default branch is '$DEFAULT_BRANCH'."
    echo "       Pass --force as the second arg to release from this branch anyway."
    exit 1
fi

# Patch the version in BOTH places. Use a tmp file + mv to keep the
# operation atomic-ish (no half-written cmc-cloner.php on disk).
TMP=$(mktemp)

# 1. Plugin header line:  * Version:     0.9.10
sed -E "s/^([[:space:]]*\*[[:space:]]*Version:[[:space:]]*).+$/\1$VERSION/" cmc-cloner.php > "$TMP"
mv "$TMP" cmc-cloner.php

# 2. PHP constant:  define( 'CMC_CLONER_VERSION', '0.9.10' );
sed -E "s/(define\([[:space:]]*'CMC_CLONER_VERSION'[[:space:]]*,[[:space:]]*')[^']+(')/\1$VERSION\2/" cmc-cloner.php > "$TMP"
mv "$TMP" cmc-cloner.php

# Sanity check: both new values are present.
if ! grep -q "^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*$VERSION$" cmc-cloner.php; then
    echo "Error: failed to patch plugin Version header to $VERSION. Inspect cmc-cloner.php manually."
    exit 1
fi
if ! grep -q "'CMC_CLONER_VERSION',[[:space:]]*'$VERSION'" cmc-cloner.php; then
    echo "Error: failed to patch CMC_CLONER_VERSION constant to $VERSION. Inspect cmc-cloner.php manually."
    exit 1
fi

echo "Patched cmc-cloner.php to version $VERSION."

# Commit + tag + push.
git add cmc-cloner.php
git commit -m "Release v$VERSION"
git tag "v$VERSION"

echo
echo "Pushing branch $CURRENT_BRANCH and tag v$VERSION to origin…"
git push origin "$CURRENT_BRANCH"
git push origin "v$VERSION"

echo
echo "Done. Watch the release workflow at:"
echo "    https://github.com/${GITHUB_REPO:-OWNER/REPO}/actions"
echo
echo "Once the workflow finishes (~1 min), the GitHub Release will be live and"
echo "every installed site will see 'Update available' within ~12 hours."
