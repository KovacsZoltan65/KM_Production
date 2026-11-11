#!/usr/bin/env bash
# Only fixed, read-only CI probes are permitted here. Never enable xtrace.
set +x
set -euo pipefail

action=${1:-}
case "$action" in
    probe) query='SELECT 1' ;;
    settings) query='SELECT VERSION(), @@character_set_database, @@collation_database, @@SESSION.sql_mode, @@SESSION.time_zone' ;;
    *) printf '%s\n' 'Usage: bash scripts/ci-mysql-client.sh <probe|settings>' >&2; exit 64 ;;
esac

# Validate the exact option-file target as well, even if Laravel has cached config.
case "${TEST_MYSQL_HOST:-127.0.0.1}" in
    127.0.0.1|localhost|mysql) ;;
    *) printf '%s\n' 'MySQL test environment guard failed.' >&2; exit 1 ;;
esac
if [[ "${TEST_MYSQL_USERNAME:-km_testing}" != km_testing ]] ||
    [[ ! "${TEST_MYSQL_DATABASE:-km_production_testing}" =~ ^km_production_(test|testing)(_[a-z0-9]+)?$ ]] ||
    [[ ! "${TEST_MYSQL_PORT:-33060}" =~ ^[0-9]{1,5}$ ]]; then
    printf '%s\n' 'MySQL test environment guard failed.' >&2
    exit 1
fi

# Reuse the project guard before opening a connection or writing credentials.
if ! php scripts/backend-test-environment.php mysql guard >/dev/null 2>&1; then
    printf '%s\n' 'MySQL test environment guard failed.' >&2
    exit 1
fi

umask 077
directory=$(mktemp -d "${RUNNER_TEMP:-${TMPDIR:-/tmp}}/km-ci-mysql.XXXXXXXXXX")
config="$directory/client.cnf"
result="$directory/result.txt"
cleanup() {
    rm -f -- "$config" "$result"
    rmdir -- "$directory"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

# MySQL option-file syntax: double-quote all values and escape control characters.
# printf is a shell builtin, so credentials never become process arguments.
option() {
    local value=$2
    value=${value//\\/\\\\}
    value=${value//\"/\\\"}
    value=${value//$'\b'/\\b}
    value=${value//$'\t'/\\t}
    value=${value//$'\n'/\\n}
    value=${value//$'\r'/\\r}
    printf '%s="%s"\n' "$1" "$value"
}

{
    printf '%s\n' '[client]' 'protocol=TCP' 'connect-timeout=10'
    option host "${TEST_MYSQL_HOST:-127.0.0.1}"
    option port "${TEST_MYSQL_PORT:-33060}"
    option database "${TEST_MYSQL_DATABASE:-km_production_testing}"
    option user "${TEST_MYSQL_USERNAME:-km_testing}"
    option password "${TEST_MYSQL_PASSWORD:-testing_only}"
} > "$config"
chmod 600 "$config"

# Ignore ambient password/login files. No raw client failure output is published.
unset MYSQL_PWD
if mysql --defaults-file="$config" --no-login-paths --batch --skip-column-names \
    --execute="$query" > "$result" 2>/dev/null; then
    cat -- "$result"
else
    printf '%s\n' 'MySQL test connection/query failed; raw diagnostics withheld.' >&2
    exit 1
fi
