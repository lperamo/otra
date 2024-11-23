#!/bin/bash
git diff --raw | while read -r perm _ _ _ _ file
do
    old_mode=$(echo "$perm" | cut -c 4-6)
    if [[ -n $old_mode && -n $file ]]; then
        chmod "$old_mode" "$file"
    fi
done

