# syntax=docker/dockerfile:1.7

# ---- stage 1: composer ----
FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# ---- stage 2: runtime ----
FROM php:8.5-fpm-alpine AS runtime
# opcache and mbstring are already bundled in this base image; only pdo_mysql,
# intl and redis need to be added.
RUN set -eux; \
    apk add --no-cache icu-libs; \
    apk add --no-cache --virtual .build icu-dev linux-headers $PHPIZE_DEPS; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql intl; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del .build

COPY deploy/docker/php.ini       /usr/local/etc/php/conf.d/zz-app.ini
COPY deploy/docker/php-fpm.conf  /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /var/www/html
COPY --from=deps --chown=www-data:www-data /app /var/www/html
USER www-data
EXPOSE 9000
HEALTHCHECK --interval=10s --timeout=2s CMD php -r "exit(extension_loaded('Zend OPcache')?0:1);"
