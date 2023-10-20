#!/bin/bash

trunk_rev=${1:-HEAD}
ver_base=$(date  +'%Y%m%d')
brname=stable_${ver_base}

if test -z "$brname"; then
	echo "Usage: ./make_stable_branch.sh [trunk_rev]"
	exit -1
fi

PROJECT_PATH=/wlc/core
PROJECT_NAME="WLC Core"

prodtag=^${PROJECT_PATH}/trunk@${trunk_rev}
branch=^${PROJECT_PATH}/branches/$brname
echo "Source: $prodtag"
echo "Branch: $branch"

if svn ls $branch > /dev/null 2>&1; then
	echo "ERROR: Branch already exists"
	exit -1
fi

read -p "Create new stable branch from trunk rev ${trunk_rev} (yes/no): " CONT

if test "$CONT" != "yes"; then
	exit -1
fi


svn cp $prodtag $branch -m "Stable branch $brname from ${PROJECT_NAME} trunk ${trunk_rev}"
