#!/usr/bin/env bash
. /home/hub/.bash_ddev_lanes
# itest.sh <lane> [phpunit args] <tests>. Exit: phpunit's; 3 refused; 4 indexes left; 5 password unmasked
set -u
MASK="s/(--[a-z-]*password[= ]+)('[^']*'|\\\\?\"[^\"]*\"|[^ ,\"'\\\\]+)/\\1****/g"
say() { echo "itest: $*" | sed -E "$MASK"; }
no() { say "$*: refused"; rc=3; exit 3; }
[ $# -ge 2 ] || no "usage: itest.sh <lane> [phpunit args] <tests>"
L=${1%/}; shift; N=${L##*/}; P=$N-itest; S=http://opensearch:9200; rc=0; parked=0
C=$L/dev/tests/integration/etc/install-config-mysql.php; M=$L/generated/metadata
[[ $N =~ ^[a-z0-9-]+$ ]] && [ "$(awk '/^name:/{print $2; exit}' "$L/.ddev/config.yaml")" = "$N" ] \
    || no "$N is not this lane's DDEV name"
grep -qF "'opensearch-index-prefix' => '$P'," "$C" && grep -qF "'opensearch-host' => 'opensearch'," "$C" \
    || no "$C does not set prefix $P on host opensearch"
H="Authorization: Bearer $(cat ~/data/admin-token)"
W=$(curl -sS -f -m 30 -H "$H" http://127.0.0.1:8080/api/worktrees) || no "the hub worktree list failed"
O=$(jq --arg p "$P" '[.[] | select(.agent_name == $p or .ddev_project_name == $p)] | length' <<<"$W") \
    && [ "$O" = 0 ] || no "a live lane or shop owns $P, or the hub list is unreadable"
[ ! -e "$M.off" ] || no "$M.off is left from an earlier run: move it back first"
exec 9>"$L/var/itest.lock" && flock -n 9 || no "another run holds $L/var/itest.lock"
own() { local o; o=$(ddev exec -p "$N" --raw curl -sS -f -m 30 "$S/_cat/indices/${P}_*?h=index") || return 1
    tr -d '\r' <<<"$o" | grep -E "^${P}_[a-z0-9_.-]+$" || true; }
drop() { local i; for i in $(own); do ddev exec -p "$N" --raw curl -sS -f -m 30 -o /dev/null -X DELETE "$S/$i" \
    && say "deleted $i" || say "could not delete $i"; done; }
end() {
    drop; local left f
    if ! left=$(own); then say "LEAK: cannot count ${P}_*"; rc=4; elif [ -n "$left" ]; then say "LEAK:" $left; rc=4; fi
    if [ "$parked" = 1 ]; then if [ -e "$M" ]; then say "$M came back: $M.off kept"; else mv "$M.off" "$M"; fi; fi
    for f in "$L"/var/log/*.log; do [ -f "$f" ] && sed -i -E "$MASK" "$f"; done
    if grep -l -E -e '--[a-z-]*password[= ]+[^*]' "$L"/var/log/*.log 2>/dev/null; then
        say "a password is left unmasked in the files above"; rc=5; fi
    say "exit=$rc"; exit "$rc"; }
own > /dev/null || no "cannot list ${P}_* at $S"
trap end EXIT; trap 'rc=130; exit' INT; trap 'rc=143; exit' TERM
drop
if [ -e "$M" ]; then mv "$M" "$M.off" || no "cannot park $M"; parked=1; fi
ddev exec -p "$N" -d /var/www/html/dev/tests/integration --raw ../../../vendor/bin/phpunit -c phpunit.xml \
    --do-not-cache-result "$@" 2>&1 | sed -u -E "$MASK"
rc=${PIPESTATUS[0]}; say "phpunit exit=$rc"
