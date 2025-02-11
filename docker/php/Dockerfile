FROM php:8.3.16-fpm AS php-fpm

# Ensure the commands run as root
USER root

RUN apt-get update \
    && apt-get install -y \
            sudo \
            zlib1g-dev \
            libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql

RUN docker-php-ext-install zip

# Uninstall and then reinstall apcu to avoid issues wit --build
RUN pecl uninstall apcu || true
RUN pecl install apcu-5.1.22
RUN docker-php-ext-enable apcu

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /usr/src/app

ARG APP_FOLDER

RUN mkdir /.composer || true && chown 1000 /.composer

COPY apps/csv_db /usr/src/app

RUN mkdir -p /usr/src/app/var/cache /usr/src/app/var/log
RUN chmod -R a+w /usr/src/app/var/cache /usr/src/app/var/log

COPY . /usr/src/app

RUN chown -R 1000:1000 /usr/src/app
USER 1000:1000

RUN composer install && composer clear-cache
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

RUN PATH=$PATH:/usr/src/apps/vendor/bin:bin
