#!/bin/bash

ls -1 languages/*.po |
while read F; do
  msgfmt -o "${F:0:-2}mo" "$F"
done
cd ..
zip -r sc-localization.zip sc-localization -x "sc-localization/.git*" -x "sc-localization/.idea*" -x "sc-localization/build.sh"
