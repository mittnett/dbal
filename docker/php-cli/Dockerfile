FROM composer:2 AS composer_builder

FROM php:8.0-cli

RUN apt-get update && apt-get install -yqq bash \
    && useradd -u 911 -U -d /config -s /bin/bash abc \
    && usermod -G users abc \
    && mkdir -p /app /config

COPY --from=composer_builder /usr/bin/composer /usr/local/bin/composer
COPY docker-entrypoint.sh /usr/bin/local/docker-entrypoint

ENTRYPOINT ["/usr/bin/local/docker-entrypoint"]
