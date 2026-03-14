#!/bin/bash

cd ..
zip -r sc-localization.zip sc-localization -x "sc-localization/.git*" -x "sc-localization/.idea*" -x "sc-localization/build.sh"
