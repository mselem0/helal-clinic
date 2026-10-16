#!/usr/bin/env bash
set -Eeuo pipefail
: "${DEPLOY_HOST:?}" "${DEPLOY_USER:?}" "${DEPLOY_PORT:?}" "${DEPLOY_PATH:?}" "${DEPLOY_SSH_KEY:?}" "${DEPLOY_KNOWN_HOSTS:?}" "${GITHUB_SHA:?}"
[[ "$DEPLOY_HOST" =~ ^[a-zA-Z0-9.-]+$ && "$DEPLOY_USER" =~ ^[a-zA-Z0-9_-]+$ && "$DEPLOY_PORT" =~ ^[0-9]{1,5}$ ]] || exit 1
[[ "$DEPLOY_PATH" == "/home/$DEPLOY_USER/apps/helal" && "$GITHUB_SHA" =~ ^[0-9a-f]{40}$ ]] || exit 1
keydir=$(mktemp -d)
trap 'rm -rf "$keydir"' EXIT
chmod 700 "$keydir"
printf '%s\n' "$DEPLOY_SSH_KEY" > "$keydir/key"
printf '%s\n' "$DEPLOY_KNOWN_HOSTS" > "$keydir/known_hosts"
chmod 600 "$keydir/key" "$keydir/known_hosts"
ssh-keygen -F "[$DEPLOY_HOST]:$DEPLOY_PORT" -f "$keydir/known_hosts" >/dev/null
options=(-i "$keydir/key" -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile="$keydir/known_hosts" -o ConnectTimeout=20)
target="$DEPLOY_USER@$DEPLOY_HOST"
ssh "${options[@]}" -p "$DEPLOY_PORT" "$target" "test -d '$DEPLOY_PATH/incoming' && test -f '$DEPLOY_PATH/shared/.env'"
scp "${options[@]}" -P "$DEPLOY_PORT" helal-release.tar.gz "$target:$DEPLOY_PATH/incoming/$GITHUB_SHA.tar.gz"
scp "${options[@]}" -P "$DEPLOY_PORT" scripts/deploy/deploy-release.sh "$target:$DEPLOY_PATH/incoming/deploy-$GITHUB_SHA.sh"
digest=$(sha256sum helal-release.tar.gz | cut -d' ' -f1)
ssh "${options[@]}" -p "$DEPLOY_PORT" "$target" "bash '$DEPLOY_PATH/incoming/deploy-$GITHUB_SHA.sh' '$DEPLOY_PATH' '$GITHUB_SHA' '$digest' '${INITIALIZE_DEPLOY:-false}'"
