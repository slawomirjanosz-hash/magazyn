
FROM php:8.2-cli

# Wymuszenie rebuildu warstwy Node.js
ENV NODE_VERSION_DEBUG=1

WORKDIR /app

COPY . /app

RUN mkdir -p /app/bootstrap/cache && chmod -R 777 /app/bootstrap/cache

RUN apt-get update \
    # Instalacja Node.js i npm na samym początku
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && echo "NODEJS INSTALLED" \
    && echo "Node version: $(node -v)" \
    && echo "NPM version: $(npm -v)" \
    # Dalej reszta zależności
    && apt-get install -y git unzip libpng-dev libonig-dev libxml2-dev libjpeg-dev libzip-dev zip curl \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install gd zip \
    # --- force rebuild ---
    && mkdir -p bootstrap/cache \
    && chmod -R 777 bootstrap/cache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader \
    && npm install \
    && npm run build \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache
    

# Railway rebuild trigger

RUN php -m && php -i

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]


# force rebuild
# kolejny komentarz na koniec by wymusić rebuild

