FROM php:8.3-fpm-alpine

WORKDIR /var/www

RUN apk add --no-cache \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev

RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --optimize-autoloader --no-dev

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]