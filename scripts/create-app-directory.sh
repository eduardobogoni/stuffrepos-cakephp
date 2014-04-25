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
php "$STUFFREPOS_DIRECTORY/scripts/gen-security.php" >> "$APP_PATH/Config/core.php"
cp "$APP_PATH/Config/database.php.default" "$APP_PATH/Config/database.php"
cp "$APP_PATH/Config/email.php.default" "$APP_PATH/Config/email.php"

rm -rf "$APP_PATH/Config/Schema"
rm -rf "$APP_PATH/Config/acl.ini.php"
rm -rf "$APP_PATH/Config/acl.php"
rm -rf "$APP_PATH/webroot/css/cake.generic.css"
rm -rf "$APP_PATH/View/Errors"
rm -rf "$APP_PATH/View/Emails"
rm -rf "$APP_PATH/View/Layouts/Emails"
rm -rf "$APP_PATH/View/Layouts/js"
rm -rf "$APP_PATH/View/Layouts/rss"
rm -rf "$APP_PATH/View/Layouts/xml"
rm -rf "$APP_PATH/View/Layouts/ajax.ctp"
rm -rf "$APP_PATH/View/Layouts/error.ctp"
rm -rf "$APP_PATH/View/Layouts/flash.ctp"
find "$APP_PATH" -name empty -type f -delete
find "$APP_PATH" -type d -empty -delete
tree "$APP_PATH"


