# ---------- Base PHP FPM Image ----------
FROM php:8.4-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    bash \
    icu-dev \
    icu-libs \
    libzip-dev \
    zlib-dev \
    oniguruma-dev \
    git \
    curl \
    unzip \
    libpng-dev \
    libwebp-dev \
    libjpeg-turbo-dev \
    freetype-dev

# Install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

RUN docker-php-ext-install \
    intl \
    pdo \
    pdo_mysql \
    opcache \
    gd \
    zip \
    mbstring

# Install Redis extension (requires build tools for phpize)
RUN apk add --no-cache --virtual .build-deps \
        autoconf \
        make \
        g++ \
        linux-headers \
        openssl-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Workdir
WORKDIR /var/www/html

# Copy app
COPY . .

# Set correct permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

EXPOSE 8000

CMD ["php-fpm"]
