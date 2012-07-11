#!/usr/bin/env bash

function exitWithError {    
    echo "${1:-"Unknown Error"}" 1>&2
    exit 1
}

[ -z "$1" ] && exitWithError "Usage: $0 APP_ROOT_DIRECTORY"

APP_ROOT_DIRECTORY=$1
APP_ROOT_DIRECTORY=${APP_ROOT_DIRECTORY%/}

#
# Create app directory.
# 
mkdir -p "$APP_ROOT_DIRECTORY" || exitWithError "Fail to create directory \"$APP_ROOT_DIRECTORY\"."

#
# Check if directory is empty.
#
EMPTY_DIR=`ls -A "$APP_ROOT_DIRECTORY"`
[ -z "$EMPTY_DIR" ] || exitWithError "\"$APP_ROOT_DIRECTORY\" is not a empty directory."

#
# Creates a git working directory, configure and update submodules.
#
(cd "$APP_ROOT_DIRECTORY"; git init)
(cd "$APP_ROOT_DIRECTORY"; git submodule add 'https://code.google.com/p/stuffrepos-cakephp' stuffrepos-cakephp)
(cd "$APP_ROOT_DIRECTORY"; git submodule update --init --recursive)

#
# Builds the appĺication skeleton.
#
mkdir -p "$APP_ROOT_DIRECTORY/stuffrepos-cakephp/app"
(cd "$APP_ROOT_DIRECTORY/stuffrepos-cakephp/cakephp/app"; cp . "$APP_ROOT_DIRECTORY/app" -R)
(cd "$APP_ROOT_DIRECTORY/stuffrepos-cakephp/app"; cp . "$APP_ROOT_DIRECTORY/app" -vR)
echo `find "$APP_ROOT_DIRECTORY/app/tmp" -type d -exec chmod og+wt '{}' \;`
