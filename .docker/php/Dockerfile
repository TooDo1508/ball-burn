FROM php:8.4-fpm-bookworm

ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG INSTALL_XDEBUG=0

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        tesseract-ocr \
        tesseract-ocr-eng \
        tesseract-ocr-vie \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        zip \
    && if [ "$INSTALL_XDEBUG" = "1" ]; then \
        pecl install xdebug-3.5.3 \
        && docker-php-ext-enable xdebug; \
    fi \
    && groupmod --gid "$WWWGROUP" www-data \
    && usermod --uid "$WWWUSER" --gid "$WWWGROUP" www-data \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/99-local.ini
COPY xdebug.ini /tmp/xdebug.ini

RUN if [ "$INSTALL_XDEBUG" = "1" ]; then \
        cp /tmp/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini; \
    fi \
    && rm /tmp/xdebug.ini

WORKDIR /var/www/html
