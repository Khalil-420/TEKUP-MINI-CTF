# Use an Apache image with PHP 8
FROM php:8-apache

# Install required system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    libmagickwand-dev \
    libtidy-dev \
    libcurl4-openssl-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    gettext \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo_mysql gd opcache tidy curl

# Enable Apache modules
RUN a2enmod rewrite

# Set recommended PHP settings
COPY php.ini /usr/local/etc/php/conf.d/zenphoto.ini

# Set up Apache configuration
COPY apache.conf /etc/apache2/sites-available/zenphoto.conf
RUN a2ensite zenphoto.conf

# Set working directory
WORKDIR /var/www/html

# Copy Zenphoto files into the container
COPY . .

# Adjust permissions
RUN chown -R www-data:www-data .

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
