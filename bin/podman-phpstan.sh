#!/usr/bin/env sh

set -e

bin/podman-exec.sh vendor/bin/phpstan analyse

