#!/bin/sh

#VARIABLES
TMPPATH="$1"

BASE="/usr/local/xmlnuke"

mkdir -p $BASE

cp -R $TMPPATH/xmlnuke-common $BASE
