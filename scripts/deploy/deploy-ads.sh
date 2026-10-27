#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
revision=${1:?Revision required}
digest=${2:?Digest required}
[[ "$revision" =~ ^[0-9a-f]{40}$ && "$digest" =~ ^[0-9a-f]{64}$ ]] || exit 1
base=/home/drmonwhs/apps/helal-ads
php=/opt/alt/php83/usr/bin/php
archive="$base/incoming/$revision.tar.gz"
release="$base/releases/$revision"
exec 9>"$base/ads-deploy.lock"
flock -n 9 || exit 1
printf '%s  %s\n' "$digest" "$archive" | sha256sum -c -
test -f "$base/.env" && test -d "$base/storage" && test ! -e "$release"
release_owned=false
activated=false
cleanup_partial_release() {
    result=$?
    trap - EXIT
    if [[ $result -ne 0 && "$release_owned" == true && "$activated" == false && "$(readlink -f "$base/current" || true)" != "$release" ]]; then
        rm -rf -- "$release"
    fi
    exit "$result"
}
trap cleanup_partial_release EXIT
mkdir "$release"
release_owned=true
tar --exclude=vendor -xzf "$archive" -C "$release"
# Reuse immutable dependencies only when the lock file is identical; never edit shared inodes.
if cmp -s "$release/composer.lock" "$base/composer.lock"; then
    cp -al "$base/vendor" "$release/vendor"
else
    tar -xzf "$archive" -C "$release" vendor
fi
ln -s "$base/.env" "$release/.env"
ln -s "$base/storage" "$release/storage"
chmod 755 "$release"
chmod -R u+rwX,go+rX "$release/public"
cd "$release"
"$php" -r 'require "vendor/autoload.php"; $e=Dotenv\Dotenv::parse(file_get_contents(".env")); if (($e["APP_URL"]??"")!=="https://ads.drmohamedhelal.com" || ($e["DB_DATABASE"]??"")!=="drmonwhs_ads") exit(1);'
"$php" scripts/deploy/backup-database.php "$base/setup-backups"
"$php" artisan migrate --force
"$php" artisan config:cache
"$php" artisan view:cache
old=$(readlink "$base/current" || true)
ln -s "$release" "$base/.next-$revision"
mv -Tf "$base/.next-$revision" "$base/current"
activated=true
if ! curl --fail --silent --show-error --max-time 30 https://ads.drmohamedhelal.com/ -o "$base/incoming/check-$revision.html" || ! grep -q 'GTM-5BFT64CX' "$base/incoming/check-$revision.html" || ! curl --fail --silent --show-error --max-time 30 -I https://ads.drmohamedhelal.com/ | grep -qi 'x-robots-tag:.*noindex'; then
    if [[ -n "$old" ]]; then ln -s "$old" "$base/.rollback-$revision"; mv -Tf "$base/.rollback-$revision" "$base/current"; fi
    exit 1
fi
printf '%s\n' "$revision" > "$base/deployed-revision"
rm -f "$archive" "$base/incoming/check-$revision.html"
echo "Ads release deployed and verified."
