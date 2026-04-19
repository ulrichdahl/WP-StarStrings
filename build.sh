#!/bin/bash

PLUGIN_NAME="$(basename "$(dirname "$(realpath "$0")")")"
ls -1 languages/*.po |
while read F; do
  msgfmt -o "${F:0:-2}mo" "$F"
done
cd ..
if [ -n "$1" ]; then
  zip -r ~/"${PLUGIN_NAME}-v$1.zip" ${PLUGIN_NAME} -x "${PLUGIN_NAME}/.git*" -x "${PLUGIN_NAME}/.idea*" -x "${PLUGIN_NAME}/build.sh"
fi
