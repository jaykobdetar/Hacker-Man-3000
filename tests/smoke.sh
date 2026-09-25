#!/usr/bin/env bash
# End-to-end smoke test against a running game (used by CI, handy locally):
#
#   php -S 127.0.0.1:8080 scripts/dev-router.php &
#   tests/smoke.sh http://127.0.0.1:8080
#
# Needs a database loaded from game.sql with a started round (see README). Registers a player,
# logs in, visits the main pages and checks the security basics (CSRF, blocked paths, headers).
set -euo pipefail

BASE="${1:-http://127.0.0.1:8080}"
JAR="$(mktemp)"
PAGE="$(mktemp)"
trap 'rm -f "$JAR" "$PAGE"' EXIT
# letters only: the game rejects emails with many digits as spam
USER_NAME="smoke$(od -An -N5 -tx1 /dev/urandom | tr -d " \n" | tr 0-9 g-p)"
PASSWORD='Sm0ke&"<test>'
failures=0

fail() { echo "FAIL: $*"; failures=$((failures + 1)); }
ok() { echo "ok:   $*"; }

# Fetches a page into $PAGE (piping curl into `grep -q` would trip `set -o pipefail`).
fetch() {
    curl -s -b "$JAR" -c "$JAR" -o "$PAGE" "$BASE/$1"
}

token() {
    fetch "$1"
    grep -o 'name="csrf_token" value="[a-f0-9]*"' "$PAGE" | head -1 | sed 's/.*value="//; s/"$//'
}

# --- security basics ---------------------------------------------------------
headers="$(curl -s -D - -o /dev/null -c "$JAR" "$BASE/")"
for h in 'Content-Security-Policy' 'X-Frame-Options' 'X-Content-Type-Options'; do
    grep -qi "^$h:" <<<"$headers" && ok "header $h" || fail "missing header $h"
done
grep -qi '^Set-Cookie:.*HttpOnly' <<<"$headers" && ok "session cookie is HttpOnly" || fail "session cookie is not HttpOnly"

for path in .env game.sql classes/Config.class.php cron/doomUpdater.php python/gamedb.py composer.lock vendor/autoload.php; do
    code="$(curl -s -o /dev/null -w '%{http_code}' "$BASE/$path")"
    [ "$code" = 404 ] && ok "/$path is not served" || fail "/$path returned $code"
done

code="$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST \
    --data-urlencode "username=$USER_NAME" --data-urlencode "password=$PASSWORD" "$BASE/login")"
[ "$code" = 403 ] && ok "POST without CSRF token is rejected" || fail "POST without CSRF token returned $code"

# --- register and log in -----------------------------------------------------
curl -s -o /dev/null -b "$JAR" -c "$JAR" -X POST --data-urlencode "csrf_token=$(token '')" \
    --data-urlencode "username=$USER_NAME" --data-urlencode "password=$PASSWORD" \
    --data-urlencode "email=$USER_NAME@example.org" "$BASE/register"
fetch ''
grep -q 'Registration complete' "$PAGE" && ok "registration" || fail "registration"

curl -s -o /dev/null -b "$JAR" -c "$JAR" -X POST --data-urlencode "csrf_token=$(token '')" \
    --data-urlencode "username=$USER_NAME" --data-urlencode "password=$PASSWORD" "$BASE/login"
fetch index
grep -q '<title>Control Panel' "$PAGE" && ok "login" || fail "login"

# --- main pages render without PHP errors ------------------------------------
for p in index processes software internet log hardware university finances list missions \
         clan ranking fame stats mail profile settings news "mail?action=new" "hardware?opt=upgrade" \
         "ranking?show=clan" "university?opt=certification"; do
    code="$(curl -s -o "$PAGE" -w '%{http_code}' -b "$JAR" -c "$JAR" "$BASE/$p")"
    if [ "$code" -ge 400 ] || grep -qE 'Fatal error|Uncaught|Parse error|SQLSTATE' "$PAGE"; then
        fail "/$p ($code): $(grep -oE '(Fatal error|Uncaught|Parse error|SQLSTATE)[^<]{0,160}' "$PAGE" | head -1)"
    else
        ok "/$p"
    fi
done

# --- AJAX with the CSRF header -------------------------------------------------
resp="$(curl -s -b "$JAR" -c "$JAR" -H "X-CSRF-Token: $(token 'mail?action=new')" -X POST -d func=getCommon "$BASE/ajax.php")"
grep -q '"status":"OK"' <<<"$resp" && ok "ajax with CSRF header" || fail "ajax with CSRF header: $resp"

curl -s -o /dev/null -b "$JAR" -c "$JAR" "$BASE/logout"
fetch index
grep -q '<title>Control Panel' "$PAGE" && fail "logout" || ok "logout"

if [ "$failures" -gt 0 ]; then
    echo "$failures check(s) failed"
    exit 1
fi
echo "all checks passed"
