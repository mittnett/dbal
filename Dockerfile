FROM php:8.0-cli

RUN useradd -u 911 -U -d /config -s /bin/false abc && \
		usermod -G users abc && \
		mkdir -p /app /config

COPY bin/docker-entrypoint.sh /usr/bin/local/docker-entrypoint

ENTRYPOINT ["/usr/bin/local/docker-entrypoint"]

