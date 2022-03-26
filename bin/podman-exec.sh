#!/usr/bin/env sh

set -e

podman run --rm \
	--tty \
	--interactive \
	--volume $(pwd):/srv/app \
	--workdir /srv/app \
	hbdbal:latest \
	"$@"

