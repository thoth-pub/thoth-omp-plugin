#!/bin/sh
# Consume BuildKit's local metadata, never resolve the mutable registry tag again.
set -eu

digest=$(sed -n 's/^[[:space:]]*"containerimage\.digest":[[:space:]]*"\([^"]*\)"[,[:space:]]*$/\1/p' "$1")
case "$digest" in
    sha256:*) hash=${digest#sha256:} ;;
    *) echo 'BuildKit manifest digest is missing or invalid' >&2; exit 1 ;;
esac
case "$hash" in
    *[!0-9a-f]*) echo 'BuildKit manifest digest is invalid' >&2; exit 1 ;;
esac
if [ "${#hash}" -ne 64 ]; then
    echo 'BuildKit manifest digest is invalid' >&2
    exit 1
fi

printf 'THOTH_ENVIRONMENT_IMAGE=%s@%s\n' "$2" "$digest" > "$3"
