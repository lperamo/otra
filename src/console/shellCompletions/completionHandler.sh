#!/usr/bin/env bash
autocomplete()
{
  if [[ -n ${BASH+x} ]]; then
    # shellcheck source=./shellCompletions.sh
    . "${CURRENT_DIR}"/shellCompletions.sh

    typeset SUGGESTIONS=""
  else
    # shellcheck source=./shellCompletions.sh
    . "${CORE_DIR}"/console/shellCompletions/shellCompletions.sh
  fi

  if [ -n "${ZSH_VERSION+x}" ]; then
    COMP_WORDS=(${words})
    COMP_CWORD=$((CURRENT - 1))
  fi

  if [[ "${COMP_WORDS[@]:1:1}" > 1 ]]; then # complete mode (list with descriptions)
    for (( index=0; index < ${#OTRA_COMMANDS_DESCRIPTIONS[@]}; index+=1 )); do
      typeset SUGGESTION="${OTRA_COMMANDS[@]:((index)):1}"

      if [[ -n ${BASH+x} ]]; then
        SUGGESTIONS+=" ${SUGGESTION}"
      else
        compadd -S "" -X "$(echo -e "${OTRA_COMMANDS_DESCRIPTIONS[@]:((index)):1}")" "${SUGGESTION}"
      fi
    done
  else # simple mode (simple list)
    for (( index=0; index < ${#OTRA_COMMANDS_DESCRIPTIONS[@]}; index+=1 )); do
      typeset SUGGESTION="$(echo -e ${OTRA_COMMANDS[@]:((index)):1})"

      if [[ -n ${BASH+x} ]]; then
        SUGGESTIONS+=" ${SUGGESTION}"
      else
        compadd -S "" ${SUGGESTION}
      fi
    done
  fi

  if [ -n "${BASH+x}" ]; then
    complete -W "${SUGGESTIONS}" otra
  fi
}

autocomplete
