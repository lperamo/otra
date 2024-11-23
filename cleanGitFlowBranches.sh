#!/bin/bash

# Récupérez toutes les branches locales
local_branches=$(git branch | tr -d ' *')

# Parcourez chaque entrée "gitflow.branch" dans .git/config
while IFS= read -r branch_entry; do
    # Extraire le type et le nom de la branche
    branch_info=$(echo "$branch_entry" | sed -n 's/gitflow.branch.\(.*\).base/\1/p')
    branch_type=$(echo "$branch_info" | cut -d'/' -f1)
    branch_name=$(echo "$branch_info" | cut -d'/' -f2-)
    if [[ -n "$branch_name" ]] && ! echo "$local_branches" | grep -q "^$branch_type/$branch_name$"; then
        # Supprimez l'entrée de branche du fichier .git/config
        git config --remove-section "gitflow.branch.$branch_type/$branch_name"
    fi
done < <(git config --list | grep -E '^gitflow\.branch\.(feature|bugfix|release|hotfix|support)/.*\.base' | cut -d'=' -f1)


