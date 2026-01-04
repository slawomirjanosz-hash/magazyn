
FROM php:8.2-cli

# Wymuszenie rebuildu warstwy Node.js - force rebuild 2025-01-04
ENV NODE_VERSION_DEBUG=2
ENV FORCE_REBUILD=2025-01-04-15-30

WORKDIR /app

COPY . /app

RUN mkdir -p /app/bootstrap/cache && chmod -R 777 /app/bootstrap/cache

RUN apt-get update \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs git unzip libpng-dev libonig-dev libxml2-dev libjpeg-dev libzip-dev zip curl \
    && echo "NODEJS INSTALLED" \
    && echo "Node version: $(node -v)" \
    && echo "NPM version: $(npm -v)" \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install gd zip \
    # --- force rebuild ---
    && mkdir -p bootstrap/cache \
    && chmod -R 777 bootstrap/cache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && echo "PHP MODULES:" \
    && php -m \
    && composer install --no-dev --optimize-autoloader \
    && npm install \
    && npm run build \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache
    

# Railway rebuild trigger

RUN php -m && php -i
#
# Railway rebuild trigger

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]


# force rebuild
# kolejny komentarz na koniec by wymusić rebuild

