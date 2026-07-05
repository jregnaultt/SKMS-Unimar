# Stage 1: Build frontend assets
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Build PHP dependencies
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer*.json ./
# Ignore local platform requirements during build since they will be met in the final image
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize

# Stage 3: Final production image
FROM php:8.4-apache

# Install system dependencies (poppler-utils, unzip, supervisor)
RUN apt-get update && apt-get install -y --no-install-recommends \
    poppler-utils \
    unzip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Add install-php-extensions helper
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install required PHP extensions
RUN install-php-extensions \
    pdo_mysql \
    redis \
    gd \
    intl \
    zip \
    pcntl \
    opcache \
    exif

# Enable Apache modules including proxying for WebSockets (Reverb)
RUN a2enmod rewrite proxy proxy_http proxy_wstunnel

# Change Apache port to 8080 (non-privileged)
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Configure Apache Document Root to point to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Use production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy application code
WORKDIR /app
COPY --from=composer-builder /app /app
COPY --from=node-builder /app/public/build /app/public/build

# Copy apache virtualhost config, supervisor config and entrypoint script
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /app/docker/entrypoint.sh
RUN chmod +x /app/docker/entrypoint.sh

# Setup storage & cache permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Expose port for web server
EXPOSE 8080

# Start container via entrypoint script
CMD ["/bin/sh", "/app/docker/entrypoint.sh"]
