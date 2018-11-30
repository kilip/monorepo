#!/usr/bin/env bash

source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/../bash/common.lib.sh"

BUILD_TYPE="nightly"
BUILD_DIR="${TRAVIS_BUILD_DIR:-$PWD}"
SF_PATH=/home/frs/project/monorepo/${BUILD_TYPE}
EXIT=0
MESSAGE="Deploy success!"
RELEASE_PATH=${BUILD_DIR}/build/${BUILD_TYPE}


print_header "deploy" ${BUILD_TYPE}

# exec command only when $EXIT=0
function runNoError()
{
    if [[ 0 != ${EXIT} ]]; then
        print_header "skipped" "$1"
    else
        print_header "execute" "$1"
        eval "$1"
    fi;
}


mkdir -pv ${RELEASE_PATH} || print_success "directory ${RELEASE_PATH} exists"
runNoError "./bin/monorepo compile --ansi -vvv ${RELEASE_PATH}" || EXIT="Compile Failed!"
runNoError "rsync -r -v --delete-after --quiet ${RELEASE_PATH} ${SF_USER}@${SF_HOST}:${SF_PATH}" || EXIT="rsync failed"

code=0
if [[ 0 != ${EXIT} ]]; then
    print_error "Deploy failed!"
    code=1
else
    print_success "Deploy success!"
fi;

exit ${code}