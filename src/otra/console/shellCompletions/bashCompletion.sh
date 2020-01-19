#!/usr/bin/env bash
typeset CURRENT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
export CURRENT_DIR
# TODO wierdly, the dynamic edition does not work for bash, we need to fix this
# shellcheck source=./shellCompletions.sh
. "${CURRENT_DIR}"/completionHandler.sh
