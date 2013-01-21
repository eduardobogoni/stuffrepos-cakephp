#!/usr/bin/env bash

set -u
set -e

source `dirname "$0"`"/script-lib.sh"

checkParameterCount $# 1 "<APP_ROOT_DIRECTORY>"

[ -z "$1" ] && exitWithError "Usage: $0 APP_ROOT_DIRECTORY"

APP_ROOT_DIRECTORY=$1
APP_ROOT_DIRECTORY=${APP_ROOT_DIRECTORY%/}
APP_ROOT_DIRECTORY="`readlink -f "$APP_ROOT_DIRECTORY"`"

mkdir -p "$APP_ROOT_DIRECTORY"

if [ ! -f "$APP_ROOT_DIRECTORY/.git" ]; then
    (cd "$APP_ROOT_DIRECTORY"; git init)
fi

if [ ! -e "$APP_ROOT_DIRECTORY/stuffrepos-cakephp" ]; then
    (cd "$APP_ROOT_DIRECTORY"; git submodule add 'https://code.google.com/p/stuffrepos-cakephp' stuffrepos-cakephp)
fi

(cd "$APP_ROOT_DIRECTORY"; git submodule update --init --recursive)

$APP_ROOT_DIRECTORY"/stuffrepos-cakephp/scripts/create-app-directory.sh" "$APP_ROOT_DIRECTORY/app"