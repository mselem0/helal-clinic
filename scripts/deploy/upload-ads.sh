#!/usr/bin/env bash
set -Eeuo pipefail
: "${DEPLOY_HOST:?}" "${DEPLOY_USER:?}" "${DEPLOY_PORT:?}" "${DEPLOY_SSH_KEY:?}" "${DEPLOY_KNOWN_HOSTS:?}" "${GITHUB_SHA:?}"
[[ "$DEPLOY_HOST" == server417.web-hosting.com && "$DEPLOY_USER" == drmonwhs && "$DEPLOY_PORT" == 21098 ]] || exit 1
keydir=$(mktemp -d)
trap 'rm -rf "$keydir"' EXIT
chmod 700 "$keydir"
printf '%s\n' "$DEPLOY_SSH_KEY" > "$keydir/key"
printf '%s\n' "$DEPLOY_KNOWN_HOSTS" > "$keydir/known_hosts"
chmod 600 "$keydir/key" "$keydir/known_hosts"
options=(-i "$keydir/key" -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile="$keydir/known_hosts" -o ConnectTimeout=20)
target="$DEPLOY_USER@$DEPLOY_HOST"
base=/home/drmonwhs/apps/helal-ads
scp "${options[@]}" -P "$DEPLOY_PORT" helal-release.tar.gz "$target:$base/incoming/$GITHUB_SHA.tar.gz"
scp "${options[@]}" -P "$DEPLOY_PORT" scripts/deploy/deploy-ads.sh "$target:$base/incoming/deploy-$GITHUB_SHA.sh"
digest=$(sha256sum helal-release.tar.gz | cut -d' ' -f1)
ssh "${options[@]}" -p "$DEPLOY_PORT" "$target" "bash '$base/incoming/deploy-$GITHUB_SHA.sh' '$GITHUB_SHA' '$digest'"
