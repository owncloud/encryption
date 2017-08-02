#!/usr/bin/env bash

OC_PATH=../../../../
CORE_INT_TESTS_PATH=tests/integration/
OCC=${OC_PATH}occ

#Starting here the execution will be the same as if we were in core.
cd "$OC_PATH""$CORE_INT_TESTS_PATH"


./run.sh "$@"
