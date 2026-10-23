FROM node:24.22.1-bookworm-slim AS frontend
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates \
    && update-ca-certificates \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY package*.json ./
RUN node --version && npm ci
COPY resources ./resources
COPY tsconfig.json vite.config.ts ./
RUN npm run build

FROM php:8.4.27-apache-bookworm AS backend
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates libpq-dev libsqlite3-dev libonig-dev libicu-dev libzip-dev unzip \
    && update-ca-certificates \
    && docker-php-ext-install pdo_pgsql pdo_sqlite mbstring intl zip opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --from=frontend /app/public/build ./public/build
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/showcase.ini
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf
EXPOSE 10000
CMD ["sh", "deploy/start.sh"]
