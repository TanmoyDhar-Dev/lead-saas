# syntax=docker/dockerfile:1.7

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

FROM php:8.3-fpm-alpine AS php-base

RUN apk add --no-cache \
        fcgi \
        freetype \
        freetype-dev \
        git \
        icu-dev \
        libjpeg-turbo \
        libjpeg-turbo-dev \
        libpng \
        libpng-dev \
        libzip \
        libzip-dev \
        oniguruma-dev \
        linux-headers \
        libpq \
        postgresql-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        postgresql-dev \
        $PHPIZE_DEPS \
    && rm -rf /tmp/pear /var/cache/apk/*

COPY docker/php/conf.d/ /usr/local/etc/php/conf.d/
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

FROM php-base AS app

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative \
    && mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]

FROM nginx:1.27-alpine AS nginx

RUN apk add --no-cache curl

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public

HEALTHCHECK --interval=15s --timeout=5s --retries=10 --start-period=30s \
    CMD curl -fsS http://127.0.0.1/up > /dev/null || exit 1

EXPOSE 80

FROM app AS cli

# Reusable CLI image for queue workers and the scheduler.
