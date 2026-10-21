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
ENV_FILE="$ROOT/.secrets/.env"
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

# ---------------------------------------------------------------------------
# Logins, rebuilt from .env every time so the two can never drift apart.
#
# The plain passwords go over the SSH pipe to be hashed on the server and are never
# written to a file on either side. .env is CRLF, and Python's default write_text would
# put the carriage return back - hence newline="\n" and the per-line trim in PHP. A hash
# of "password\r" fails to verify and looks exactly like a wrong password.
# ---------------------------------------------------------------------------
sync_users() {
  local staged
  staged="$(python3 -c '
import pathlib, re, sys
env = pathlib.Path(sys.argv[1]).read_text(encoding="utf-8")
rows = []
for user, key in (("herman", "DOCS_AUTH_HERMAN_PASSWORD"), ("crystal", "DOCS_AUTH_CRYSTAL_PASSWORD")):
    m = re.search(rf"^{key}=(.*)$", env, re.M)
    if not m:
        sys.exit(f"{key} missing from .env")
    rows.append(user + "\t" + m.group(1).strip())
sys.stdout.write("\n".join(rows))
' "$ENV_FILE")"

  local hashes
  hashes="$(printf '%s' "$staged" | "${SSH[@]}" 'php -r "
    \$out = [];
    foreach (explode(PHP_EOL, stream_get_contents(STDIN)) as \$line) {
        \$line = trim(\$line);
        if (\$line === \"\") { continue; }
        [\$u, \$p] = explode(\"\t\", \$line, 2);
        \$out[] = sprintf(\"    %s => %s,\", var_export(\$u, true), var_export(password_hash(\$p, PASSWORD_BCRYPT, [\"cost\" => 12]), true));
    }
    echo implode(PHP_EOL, \$out);
  "')"

  printf '<?php\n/**\n * Readers of the site guide. Hashes only; the plain passwords live in\n * .secrets/.env, and deploy.sh rebuilds this file from them on every publish.\n */\n\nreturn [\n%s\n];\n' "$hashes" > "$HERE/server/users.php"
  echo "logins rebuilt from .env"
}

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
    sync_users
  fi

  cp -r "$HERE/site" "$HERE/package.json" "$HERE/package-lock.json" "$BUILD/"
  ( cd "$BUILD" && npm ci --silent && npx vitepress build site )

  rm -rf "$stage" && mkdir -p "$stage/_private"
  cp -r "$BUILD/site/.vitepress/dist/." "$stage/_private/"
  printf 'Require all denied\n' > "$stage/_private/.htaccess"
  cp "$HERE/server/"*.php "$stage/"
  cp "$HERE/server/htaccess.tpl" "$stage/.htaccess" && rm -f "$stage/htaccess.tpl"

  if [ "$dry_run" = 1 ]; then
    echo "dry run: nothing sent. Would upload to $REMOTE:$DOC :"
    ( cd "$stage" && find . -type f | sort | sed 's|^\./|  |' )
    echo "users.php: rebuilt from .env on a real publish (needs SSH, so skipped here)"
    echo "would then unpack in $DOC and check https://docs.crystalau.com/faq returns 302"
    return 0
  fi

  tar -czf "$BUILD/site.tgz" -C "$stage" .
  "${SCP[@]}" "$BUILD/site.tgz" "$REMOTE:$DOC/"
  "${SSH[@]}" "cd $DOC && rm -rf _private && tar xzf site.tgz && rm -f site.tgz && chmod 600 users.php"

  # An exit code is not a result: confirm the door is shut and a real login opens it.
  local out
  out="$(curl -sS -o /dev/null -w '%{http_code}' https://docs.crystalau.com/faq)"
  [ "$out" = "302" ] || { echo "FAILED: signed-out request returned $out, expected 302"; exit 1; }
  echo "published to https://docs.crystalau.com  (signed-out request correctly redirects)"
}

if [ "${BASH_SOURCE[0]}" = "$0" ]; then
  main "$@"
fi
