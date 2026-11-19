FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libsqlite3-dev \
    && docker-php-ext-install zip pdo pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN mkdir -p database && touch database/database.sqlite
RUN php artisan key:generate --force || true
RUN php artisan migrate --force || true
RUN php artisan storage:link || true

EXPOSE 10000
CMD php artisan serve --host=0.0.0.0 --port=10000
