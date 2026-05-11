ARG PHP_FPM_VERSION=8.3
FROM php:${PHP_FPM_VERSION}-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpng-dev \
    libwebp-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd intl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

RUN echo 'max_execution_time = 300' > /usr/local/etc/php/conf.d/zz-timeouts.ini \
    && echo 'max_input_time = 300' >> /usr/local/etc/php/conf.d/zz-timeouts.ini \
    && echo 'upload_max_filesize = 128M' >> /usr/local/etc/php/conf.d/zz-timeouts.ini \
    && echo 'post_max_size = 128M' >> /usr/local/etc/php/conf.d/zz-timeouts.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
