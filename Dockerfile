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
FROM dunglas/frankenphp:1-php8.4

# Install system dependencies (poppler-utils, unzip, supervisor)
RUN apt-get update && apt-get install -y --no-install-recommends \
    poppler-utils \
    unzip \
    supervisor \
    libcap2-bin \
    && rm -rf /var/lib/apt/lists/*

# Remove file capabilities from frankenphp binary to prevent Operation not permitted error on Render
RUN setcap -r /usr/local/bin/frankenphp

# Install required PHP extensions using dunglas/frankenphp built-in extension helper
RUN install-php-extensions \
    pdo_mysql \
    redis \
    gd \
    intl \
    zip \
    pcntl \
    opcache \
    exif

# Use production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy application code
WORKDIR /app
COPY --from=composer-builder /app /app
COPY --from=node-builder /app/public/build /app/public/build

# Copy supervisor config and entrypoint script
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /app/docker/entrypoint.sh
RUN chmod +x /app/docker/entrypoint.sh

# Setup storage & cache permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Expose port for web server
EXPOSE 8080

# Start container via entrypoint script
CMD ["/bin/sh", "/app/docker/entrypoint.sh"]
