FROM php:8.2-apache

# Install system dependencies & PostgreSQL dev libraries
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for PostgreSQL & GD image processing
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Ensure upload directories exist with proper Apache permissions
RUN mkdir -p /var/www/html/assets/uploads/profiles /var/www/html/assets/uploads/covers \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/assets/uploads

EXPOSE 80
