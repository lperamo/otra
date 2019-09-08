#!/usr/bin/env sh
# shellcheck source=./src/lib.sh
. "${CORE_DIR}"/console/shellCompletions/shellCompletions.sh

for (( index=0; index < ${#OTRA_COMMANDS_DESCRIPTIONS[@]}; index+=1 )); do
  compadd -S "" -X "$(echo -e "${OTRA_COMMANDS_DESCRIPTIONS[@]:((index)):1}")" "${OTRA_COMMANDS[@]:((index)):1}"
done
