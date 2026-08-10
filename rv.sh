#!/usr/bin/env bash
set -e

# Root Rendezvous CLI Wrapper for Linux / macOS
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "$DIR/scripts/rv.sh" "$@"
