#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
base=${1:?Application path required}
revision=${2:?Commit SHA required}
digest=${3:?Archive digest required}
initialize=${4:-false}
[[ "$base" =~ ^/home/[a-zA-Z0-9_-]+/apps/helal$ ]] || { echo "Unexpected deployment path" >&2; exit 1; }
[[ "$revision" =~ ^[0-9a-f]{40}$ && "$digest" =~ ^[0-9a-f]{64}$ ]] || exit 1
[[ "$initialize" == true || "$initialize" == false ]] || exit 1
php_bin=${HELAL_PHP_BIN:-/opt/alt/php83/usr/bin/php}
archive="$base/incoming/$revision.tar.gz"
release="$base/releases/$revision"
shared="$base/shared"
webroot="$(dirname "$(dirname "$base")")/public_html"
[[ -f "$shared/.env" && -f "$archive" ]] || { echo "Upload and production .env must be prepared first." >&2; exit 1; }
exec 9>"$base/deploy.lock"
flock -n 9 || { echo "Another deployment is active." >&2; exit 1; }
printf '%s  %s\n' "$digest" "$archive" | sha256sum -c -
[[ ! -e "$release" ]] || { echo "This revision already exists; use a new commit." >&2; exit 1; }
release_owned=false
cleanup_partial_release() {
    result=$?
    trap - EXIT
    if [[ $result -ne 0 && "$release_owned" == true && "$(readlink -f "$base/current" || true)" != "$release" ]]; then
        rm -rf -- "$release"
    fi
    exit "$result"
}
trap cleanup_partial_release EXIT
mkdir "$release"
release_owned=true
mkdir -p "$shared/backups"
tar --exclude=vendor -xzf "$archive" -C "$release"
# Reuse immutable dependency files when the lock is identical; never modify them in place.
if [[ -f "$base/current/composer.lock" ]] && cmp -s "$release/composer.lock" "$base/current/composer.lock"; then
    cp -al "$base/current/vendor" "$release/vendor"
else
    tar -xzf "$archive" -C "$release" vendor
fi

[[ -f "$release/artisan" && -f "$release/vendor/autoload.php" && -f "$release/public/build/manifest.json" ]] || exit 1
mkdir -p "$shared/storage/app/public" "$shared/storage/framework/cache/data" "$shared/storage/framework/sessions" "$shared/storage/framework/views" "$shared/storage/logs"
chmod 755 "$release" "$base" "$base/releases"
chmod 711 "$shared"
chmod -R u+rwX,go+rX "$shared/storage/app/public"
# The archive intentionally contains no .env, storage, database dumps, tests, or CI SSH key.
ln -s "$shared/.env" "$release/.env"
ln -s "$shared/storage" "$release/storage"
ln -s "$shared/storage/app/public" "$release/public/storage"
chmod -R u+rwX,go+rX "$release/public"
chmod 755 "$release/vendor" "$release/bootstrap" "$release/app" "$release/config" "$release/routes" "$release/resources" "$release/database"
old_release=$(readlink "$base/current" || true)
maintenance=false
activated=false
installed_webroot=false
finish() {
    result=$?
    trap - EXIT
    if [[ $result -ne 0 && "$activated" == true && -n "$old_release" ]]; then
        ln -s "$old_release" "$base/.rollback-$revision"
        mv -Tf "$base/.rollback-$revision" "$base/current"
        echo "Previous application release restored. Database migrations were not reversed." >&2
    fi
    if [[ $result -ne 0 && "$installed_webroot" == true && -z "$old_release" ]]; then
        "$php_bin" "$release/scripts/deploy/rollback-webroot.php" "$base" || true
        [[ ! -L "$base/current" ]] || rm "$base/current"
        echo "First publication failed; original web root restored. Database migrations were not reversed." >&2
    fi
    if [[ "$maintenance" == true ]]; then
        "$php_bin" "$base/current/artisan" up >/dev/null 2>&1 || true
    fi
    exit "$result"
}
trap finish EXIT
cd "$release"
"$php_bin" -r 'if (PHP_VERSION_ID < 80300) exit(1); require "vendor/autoload.php";'
"$php_bin" scripts/deploy/check-production.php
"$php_bin" scripts/deploy/check-webroot.php "$base" "$release"
if [[ -n "$old_release" ]]; then
    "$php_bin" "$base/current/artisan" down --retry=30
    maintenance=true
elif [[ "$initialize" != true ]]; then
    echo "First publication requires the manual initialize option." >&2
    exit 1
fi
if [[ "$initialize" == true && -e "$shared/initialized" ]]; then
    echo "Initial seeding already completed." >&2
    exit 1
fi
"$php_bin" scripts/deploy/backup-database.php "$shared/backups"
"$php_bin" artisan migrate --force
if [[ "$initialize" == true ]]; then
    [[ ! -e "$shared/initialized" ]] || { echo "Initial seeding already completed." >&2; exit 1; }
    "$php_bin" artisan db:seed --force
fi
# Seeded public media must remain readable by the web server despite the private deploy umask.
chmod -R u+rwX,go+rX "$shared/storage/app/public"
"$php_bin" artisan config:cache
"$php_bin" artisan view:cache
# The stable bridge keeps the main domain's fixed public_html document root.
if [[ ! -f "$shared/webroot.json" ]]; then
    [[ "$initialize" == true ]] || exit 1
    "$php_bin" scripts/deploy/install-webroot.php "$base" "$webroot" "$release"
    installed_webroot=true
fi
ln -s "$release" "$base/.current-$revision"
mv -Tf "$base/.current-$revision" "$base/current"
activated=true
"$php_bin" artisan up
maintenance=false
curl --fail --silent --show-error --max-time 30 --retry 2 --retry-delay 3 --output /dev/null https://drmohamedhelal.com/up
curl --fail --silent --show-error --max-time 30 --output /dev/null https://drmohamedhelal.com/
printf '%s\n' "$revision" > "$shared/initialized"
rm -f "$archive"
echo "Release activated and both HTTP checks passed."
