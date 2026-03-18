FROM php:8.4-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first (for better Docker layer caching)
COPY composer.json composer.lock ./

# Install PHP dependencies (without scripts since artisan doesn't exist yet)
RUN composer install --no-scripts --no-interaction

# Copy supervisor configuration
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy rest of application files
COPY . .

# Copy .env.example to .env
RUN cp .env.example .env

# Run composer scripts now that all files are in place
RUN composer dump-autoload --optimize && php artisan package:discover --ansi

# Create supervisor log directory
RUN mkdir -p /var/log/supervisor

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/storage

# Create startup script
RUN echo '#!/bin/bash\n\
# Create Laravel storage directories if they do not exist\n\
mkdir -p storage/framework/{sessions,views,cache}\n\
mkdir -p storage/logs\n\
mkdir -p bootstrap/cache\n\
# Set permissions\n\
chown -R www-data:www-data storage bootstrap/cache\n\
chmod -R 777 storage bootstrap/cache\n\
# Generate application key if not set\n\
php artisan key:generate --force\n\
# Cache configuration\n\
php artisan config:cache\n\
# Run migrations\n\
php artisan migrate --force\n\
# Start supervisor (artisan serve, scheduler, and queue)\n\
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /start.sh && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
