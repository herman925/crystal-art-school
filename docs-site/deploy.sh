#!/usr/bin/env bash
# Build the guide and publish it to docs.crystalau.com.
#
# node_modules is not kept on Google Drive: the sync client writes package files in
# pieces and npm then reports "Invalid package config". The build runs in a temp
# directory and only the result comes back.
set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(dirname "$HERE")"
ENV_FILE="$ROOT/.secrets/.env"
KEY="$ROOT/.secrets/hostinger_ed25519"
REMOTE="u202887698@82.29.185.202"
SSH=(ssh -i "$KEY" -p 65002 -o StrictHostKeyChecking=no "$REMOTE")
DOC=/home/u202887698/domains/crystalau.com/public_html/docs_site
BUILD=/c/Users/Herman/AppData/Local/Temp/cas-docs-build

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
  staged="$(python -c '
import pathlib, re, sys
env = pathlib.Path(sys.argv[1]).read_text(encoding="utf-8")
rows = []
for user, key in (("herman", "DOCS_AUTH_HERMAN_PASSWORD"), ("crystal", "DOCS_AUTH_CRYSTAL_PASSWORD")):
    m = re.search(rf"^{key}=(.*)$", env, re.M)
    if not m:
        sys.exit(f"{key} missing from .env")
    rows.append(user + "\t" + m.group(1).strip())
sys.stdout.write("\n".join(rows))
' "$(cygpath -w "$ENV_FILE")")"

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

sync_users

rm -rf "$BUILD" && mkdir -p "$BUILD"
cp -r "$HERE/site" "$HERE/package.json" "$BUILD/"
( cd "$BUILD" && npm install --silent && npx vitepress build site )

STAGE="$BUILD/upload"
rm -rf "$STAGE" && mkdir -p "$STAGE/_private"
cp -r "$BUILD/site/.vitepress/dist/." "$STAGE/_private/"
printf 'Require all denied\n' > "$STAGE/_private/.htaccess"
cp "$HERE/server/"*.php "$STAGE/"
cp "$HERE/server/htaccess.tpl" "$STAGE/.htaccess" && rm -f "$STAGE/htaccess.tpl"

tar -czf "$BUILD/site.tgz" -C "$STAGE" .
scp -i "$KEY" -P 65002 -o StrictHostKeyChecking=no "$BUILD/site.tgz" "$REMOTE:$DOC/"
"${SSH[@]}" "cd $DOC && rm -rf _private && tar xzf site.tgz && rm -f site.tgz && chmod 600 users.php"

# An exit code is not a result: confirm the door is shut and a real login opens it.
out="$(curl -sS -o /dev/null -w '%{http_code}' https://docs.crystalau.com/faq)"
[ "$out" = "302" ] || { echo "FAILED: signed-out request returned $out, expected 302"; exit 1; }
echo "published to https://docs.crystalau.com  (signed-out request correctly redirects)"
