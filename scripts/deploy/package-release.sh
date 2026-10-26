#!/usr/bin/env bash
set -Eeuo pipefail
archive=${1:-helal-release.tar.gz}
[[ -f public/build/manifest.json && -f vendor/autoload.php ]] || { echo "Build dependencies and assets first." >&2; exit 1; }
items=(app bootstrap config database lang public resources routes scripts/deploy vendor artisan composer.json composer.lock modules_statuses.json)
[[ ! -d Modules ]] || items+=(Modules)
# Curated tree excludes local secrets, uploads, DB files, CI keys and test-only endpoints.
tar --exclude='.env' --exclude='.env.*' --exclude='*.sqlite*' --exclude='*.sql' --exclude='bootstrap/cache/*.php' --exclude='public/storage' --exclude='public/hot' --exclude='.DS_Store' --exclude='public/assets/hero-pregnancy-ultrasound.mp4' --exclude='public/assets/service-fertility*' -czf "$archive" "${items[@]}"
sha256sum "$archive" > "$archive.sha256"
