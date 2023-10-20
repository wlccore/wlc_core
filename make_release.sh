#!/bin/bash

PROJECT_NAME=$(jq .name < composer.json)
stable_branch=${1:-master}

if echo $stable_branch | egrep '^(master|FunLib_[0-9]+_[0-9]+)$' | read; then
    :
else
    echo "Stable branch must be 'master' or '${PROJECT_NAME}_M_N'"
    exit -1
fi 

echo "Making sure up-to-date $stable_branch branch is used"
git_remote=origin
if git fetch $git_remote && git checkout remotes/$git_remote/$stable_branch; then
    :
else
    echo "Failed to get the latest $stable_branch"
    exit -1
fi

prevver=$(jq .version < composer.json | sed -e 's/"//g')
nextver="$(echo $prevver | cut -f1 -d.).$(echo $prevver | cut -f2 -d.).$(( $(echo $prevver | cut -f3 -d.) + 1 ))"
ver=${2:-$nextver}
tag="v$ver"

echo
echo "Source: remotes/$git_remote/$stable_branch"
echo "Tag: $tag"
echo

read -p "Create new Test tag (yes/no): " CONT

if test "$CONT" != "yes"; then
    exit -1
fi

sed -i -e "s/\"version\": \"${prevver}\"/\"version\": \"${ver}\"/g" composer.json
sed -i -e "s/\"version\": \"${prevver}\"/\"version\": \"${ver}\"/g" package.json
sed -i -e "s/sonar\.projectVersion=${prevver}/sonar\.projectVersion=${ver}/g" sonar-project.properties
sed -i -e "s/${prevver}/${ver}/g" root/version.php
git add composer.json
git add package.json
git add root/version.php
git add sonar-project.properties
git commit -m "Updated for release ${PROJECT_NAME} $ver" && \
    git tag -a -m "Release ${PROJECT_NAME} $ver" $tag && \
    git push $git_remote HEAD:$stable_branch $tag && \
    echo "OK" || echo "FAILED"

git checkout master
git pull
