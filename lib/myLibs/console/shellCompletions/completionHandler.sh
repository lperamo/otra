#!/usr/bin/env bash
# shellcheck source=./src/lib.sh
. "${CORE_DIR}"/console/shellCompletions/shellCompletions.sh

if [ -n "${ZSH_VERSION+x}" ]; then
  COMP_WORDS=(${words})
  COMP_CWORD=$((CURRENT - 1))
fi

if [[ "${COMP_WORDS[@]:1:1}" > 1 ]]; then
  for (( index=0; index < ${#OTRA_COMMANDS_DESCRIPTIONS[@]}; index+=1 )); do
    compadd -S "" -X "$(echo -e "${OTRA_COMMANDS_DESCRIPTIONS[@]:((index)):1}")" "${OTRA_COMMANDS[@]:((index)):1}"
  done
else
  compadd -S "" ${OTRA_COMMANDS}
fi
