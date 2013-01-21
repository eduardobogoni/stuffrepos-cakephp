#!/bin/bash

PRG="$0"

while [ -h "$PRG" ] ; do
  ls=`ls -ld "$PRG"`
  link=`expr "$ls" : '.*-> \(.*\)$'`
  if expr "$link" : '/.*' > /dev/null; then
    PRG="$link"
  else
    PRG=`dirname "$PRG"`/"$link"
  fi
done

SCRIPT_DIRECTORY="`dirname "$PRG"`"
SCRIPT_DIRECTORY="`readlink -f "$SCRIPT_DIRECTORY"`"

STUFFREPOS_DIRECTORY="`dirname "$SCRIPT_DIRECTORY"`"
STUFFREPOS_DIRECTORY="`readlink -f "$STUFFREPOS_DIRECTORY"`"

function message {    
    echo $* 1>&2    
}

function checkEmptyDirectory {    
    if [ -e "$1" ]; then
        [ ! -d "$1" ] && exitWithError "\"$1\" existe e não é um diretório."
        EMPTY_DIR=`ls -A "$1"`    
        [ -z "$EMPTY_DIR" ] || exitWithError "\"$1\" não é um diretório vazio."    
    fi    
}

function checkParameterCount {    
    if [ $1 -lt $2 ]; then 
        exitWithError "Usage: $0 " $3
    fi
}

function exitWithError {    
    message $*
    exit 1
}