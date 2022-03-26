#!/usr/bin/env sh

set -e

cd ./containers/php-cli \
	&& podman build -t hbdbal:latest .
