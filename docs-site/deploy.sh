#!/usr/bin/env bash
# Build the guide and publish it to docs.crystalau.com.
#
# Usage: docs-site/deploy.sh [--dry-run]
#   --dry-run  build only: no SSH, no upload. Prints what would be uploaded.
#
# node_modules is not kept on Google Drive: the sync client writes package files in
# pieces and npm then reports "Invalid package config". The build runs in a temp
# directory and only the result comes back.
#
# The server's host key is pinned. It is scanned once into a temp known_hosts file, its
# fingerprint must equal HOST_FINGERPRINT, and every SSH and scp call then uses that file
# with StrictHostKeyChecking=yes. A mismatch aborts before anything is sent.
set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(dirname "$HERE")"
KEY="$ROOT/.secrets/hostinger_ed25519"
REMOTE="u202887698@82.29.185.202"
PORT=65002
HOST_FINGERPRINT="SHA256:QM5FECXwy/Tk32qCDy82opddDNuZYWWfi31zV0hSABY"
DOC=/home/u202887698/domains/crystalau.com/public_html/docs_site

# Scan the server's ed25519 host key into a file. Fails if nothing comes back.
scan_host_key() {  # $1 = known_hosts file to write
  ssh-keyscan -p "$PORT" -t ed25519 "${REMOTE#*@}" > "$1" 2>/dev/null || true
  [ -s "$1" ] || { echo "FAILED: ssh-keyscan returned no host key" >&2; return 1; }
}

# Every key in the file must have exactly the expected fingerprint.
check_host_fingerprint() {  # $1 = known_hosts file, $2 = expected fingerprint
  local fp
  fp="$(ssh-keygen -lf "$1" | awk '{print $2}')"
  if [ -z "$fp" ] || [ "$(printf '%s\n' "$fp" | sort -u | wc -l)" != 1 ] || [ "$fp" != "$2" ]; then
    echo "FAILED: host key fingerprint is '$fp', expected '$2'. Nothing was sent." >&2
    return 1
  fi
}

# Reader credentials are provisioned separately. An ordinary guide release never rewrites users.php.

main() {
  local dry_run=0
  case "${1:-}" in
    "") ;;
    --dry-run) dry_run=1 ;;
    *) echo "usage: $0 [--dry-run]" >&2; exit 2 ;;
  esac

  local stage known
  BUILD="$(mktemp -d)"
  trap 'rm -rf "$BUILD"' EXIT
  known="$BUILD/known_hosts"
  stage="$BUILD/upload"

  SSH=(ssh -i "$KEY" -p "$PORT" -o StrictHostKeyChecking=yes -o UserKnownHostsFile="$known" "$REMOTE")
  SCP=(scp -i "$KEY" -P "$PORT" -o StrictHostKeyChecking=yes -o UserKnownHostsFile="$known")

  if [ "$dry_run" = 0 ]; then
    scan_host_key "$known"
    check_host_fingerprint "$known" "$HOST_FINGERPRINT"
    "${SSH[@]}" "test -s $DOC/users.php" || { echo "FAILED: existing guide readers are missing; nothing published" >&2; exit 1; }
  fi

  : "${DOCS_ADMIN_SOURCE:?Set DOCS_ADMIN_SOURCE to the private administrator Markdown directory}"
  : "${DOCS_SCREENSHOT_SOURCE:?Set DOCS_SCREENSHOT_SOURCE to the private backend screenshot directory}"
  cp -r "$HERE/site" "$HERE/package.json" "$HERE/package-lock.json" "$HERE/build.mjs" "$BUILD/"
  ( cd "$BUILD" && npm ci --silent && npm run build --silent )

  rm -rf "$stage" && mkdir -p "$stage/_private"
  cp -r "$BUILD/site/.vitepress/dist/." "$stage/_private/"
  printf 'Require all denied\n' > "$stage/_private/.htaccess"
  for handler in auth.php gate.php login.php logout.php; do cp "$HERE/server/$handler" "$stage/"; done
  cp "$HERE/server/htaccess.tpl" "$stage/.htaccess" && rm -f "$stage/htaccess.tpl"

  if [ "$dry_run" = 1 ]; then
    echo "dry run: nothing sent. Would upload to $REMOTE:$DOC :"
    ( cd "$stage" && find . -type f | sort | sed 's|^\./|  |' )
    echo "users.php: preserved on server; never included in the upload"
    echo "would then unpack in $DOC and check https://docs.crystalau.com/faq returns 302"
    return 0
  fi

  local release_id remote_stage backup
  release_id="$(date -u +%Y%m%dT%H%M%SZ)-$$"
  remote_stage="/home/u202887698/guide-stage-$release_id"
  backup="/home/u202887698/backups/guide-$release_id"
  "${SSH[@]}" "mkdir -m 700 '$remote_stage'"
  "${SCP[@]}" "$HERE/install-release.py" "$REMOTE:$remote_stage/install-release.py"
  "${SSH[@]}" "python3 '$remote_stage/install-release.py' snapshot --target '$DOC'" > "$BUILD/expected.json"
  tar -czf "$BUILD/site.tgz" -C "$stage" .
  "${SCP[@]}" "$BUILD/site.tgz" "$BUILD/expected.json" "$REMOTE:$remote_stage/"
  "${SSH[@]}" "python3 '$remote_stage/install-release.py' install --target '$DOC' --archive '$remote_stage/site.tgz' --expected '$remote_stage/expected.json' --backup '$backup'"

  # An exit code is not a result: confirm the door is shut and a real login opens it.
  local out
  out="$(curl -sS -o /dev/null -w '%{http_code}' https://docs.crystalau.com/faq)"
  [ "$out" = "302" ] || { echo "FAILED: signed-out request returned $out, expected 302"; exit 1; }
  echo "published to https://docs.crystalau.com  (signed-out request correctly redirects)"
}

if [ "${BASH_SOURCE[0]}" = "$0" ]; then
  main "$@"
fi
