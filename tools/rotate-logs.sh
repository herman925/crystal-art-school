#!/bin/bash
set -eu

if [ "$#" -ne 2 ]; then
    printf '%s\n' 'Usage: rotate-logs.sh EXPLICIT_PRIVATE_LOG_DIRECTORY VERIFIED_RUN_SPACE_BUDGET_BYTES' >&2
    exit 2
fi

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
export LC_ALL=C
exec /opt/alt/python311/bin/python3 -I "$SCRIPT_DIR/log-retention.py" \
    --directory "$1" --space-budget "$2"
