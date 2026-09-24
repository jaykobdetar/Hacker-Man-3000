# syntax=docker/dockerfile:1
# Hacker Experience Legacy: PHP 8.4 + Apache image, also used for the cron container.

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --ignore-platform-reqs

FROM php:8.4-apache

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype-dev \
        locales cron mariadb-client \
        python3 python3-pymysql; \
    docker-php-ext-configure gd --with-jpeg --with-freetype; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql gd gettext; \
    sed -i -E 's/^# *(en_US.UTF-8|pt_BR.UTF-8)/\1/' /etc/locale.gen; \
    locale-gen; \
    rm -rf /var/lib/apt/lists/*; \
    a2enmod rewrite headers; \
    sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/game.ini "$PHP_INI_DIR/conf.d/zz-game.ini"
COPY docker/apache/security.conf /etc/apache2/conf-enabled/zz-security.conf

WORKDIR /var/www/html
COPY --chown=root:root . .
COPY --from=vendor /app/vendor ./vendor

# Only generated pages, uploads and the query counter are writable by the web server.
RUN set -eux; \
    mkdir -p html/profile html/ranking html/fame images/profile/thumbnail images/profile/x60 images/clan; \
    chown -R www-data:www-data html images/profile images/clan status/queries.txt; \
    rm -rf docker/db

COPY docker/cron/entrypoint.sh /usr/local/bin/game-cron
RUN chmod +x /usr/local/bin/game-cron

ENV PYTHON_BIN=python3
