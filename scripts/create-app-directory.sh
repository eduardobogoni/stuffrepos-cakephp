#!/usr/bin/env bash

set -u
set -e

source `dirname "$0"`"/script-lib.sh"

checkParameterCount $# 1 "<APP_PATH>"

APP_PATH=$1
APP_PATH=${APP_PATH%/}
APP_PATH="`readlink -f "$APP_PATH"`"

echo "STUFFREPOS_DIRECTORY: \"$STUFFREPOS_DIRECTORY\"" 1>&2
echo "APP_PATH: \"$APP_PATH\"" 1>&2

checkEmptyDirectory $APP_PATH

#
# Builds the appĺication skeleton.
#
mkdir -p "$APP_PATH"
(cd "$STUFFREPOS_DIRECTORY/cakephp/app"; cp . "$APP_PATH/" -R)
(cd "$STUFFREPOS_DIRECTORY/app"; cp . "$APP_PATH/" -R)
echo `find "$APP_PATH/tmp" -type d -exec chmod og+wt '{}' \;`
