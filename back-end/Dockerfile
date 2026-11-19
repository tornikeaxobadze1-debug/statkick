FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libsqlite3-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install zip pdo pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN cp -n .env.example .env || true
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build
RUN mkdir -p database && touch database/database.sqlite
RUN php artisan key:generate --force
RUN php artisan migrate --force || true
RUN php artisan storage:link || true

EXPOSE 10000
CMD php artisan serve --host=0.0.0.0 --port=10000
