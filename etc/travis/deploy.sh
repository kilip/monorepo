#!/usr/bin/env bash

set -e

SF_PATH=/home/frs/project/monorepo
RELEASE_PATH=${TRAVIS_BUILD_DIR}/build/release
EXIT=0

# exec command only when $EXIT=0
function doExec()
{
    if [[ 0 == ${EXIT} ]]; then
        $1 || EXIT=$2
    else
        echo "Skipping command ${1}"
    fi;
}

mkdir -pv ${RELEASE_PATH} | echo "directory ${RELEASE_PATH} exists"

doExec "./bin/monorepo compile --ansi -vvv ${TRAVIS_BUILD_DIR}/build/release" 1
doExec "rsync -r --delete-after --quiet ${TRAVIS_BUILD_DIR}/build/release ${SF_USER}:${SF_PASS}@${SF_HOST}:${SF_PATH}" 2

if [[ 0 == ${EXIT} ]]; then
    echo "Deploy success!";
elif [[ 1 == $EXIT ]]; then
    echo "Compile failed!";
elif [[ 2 == $EXIT ]]; then
    echo "Deploy Failed";
fi;

exit ${EXIT}