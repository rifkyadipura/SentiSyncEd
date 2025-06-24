FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql mysqli zip exif pcntl gd

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . /var/www/html/

# Set permissions
RUN find /var/www/html -type d -exec chmod 755 {} \;
RUN find /var/www/html -type f -exec chmod 644 {} \;

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/docker-php.conf
RUN a2enconf docker-php

# Configure PHP
COPY php.ini /usr/local/etc/php/php.ini

# Ensure .php files are properly handled
RUN echo '<FilesMatch \.php$>\n\
    SetHandler application/x-httpd-php\n\
</FilesMatch>' > /etc/apache2/conf-available/php-handler.conf
RUN a2enconf php-handler

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
