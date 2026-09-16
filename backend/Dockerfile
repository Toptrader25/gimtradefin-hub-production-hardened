FROM php:8.3-cli

RUN apt-get update && apt-get install -y git unzip libpq-dev libicu-dev \
    && docker-php-ext-install pdo_pgsql intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy full source FIRST so vendor is never overwritten by a later COPY.
COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN if [ -f composer.lock ]; then \
        composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts; \
    else \
        composer update --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts; \
    fi \
    && composer dump-autoload --optimize --no-dev

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
