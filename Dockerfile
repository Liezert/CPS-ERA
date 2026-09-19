FROM php:8.5-fpm

# System dependencies & PHP extensions required by Laravel, Filament, and Medialibrary
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql zip gd intl bcmath exif \
    && rm -rf /var/lib/apt/lists/*

# Node.js (untuk compile assets Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source
COPY . .

# Install dependencies & build frontend assets
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm install \
    && npm run build \
    && npm prune --production \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Config nginx & supervisor
COPY docker/nginx.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh
RUN tr -d '\r' < /start.sh > /start.sh.tmp && mv /start.sh.tmp /start.sh && chmod +x /start.sh

EXPOSE 10000

CMD ["/start.sh"]
