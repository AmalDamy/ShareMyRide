# Use the official PHP image with Apache
FROM php:8.2-apache

# Install mysqli extension (required for your app)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy ALL your project files into the Apache web root
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html/

# Expose port (Cloud Run requires the container to listen on $PORT)
ENV PORT 8080
RUN sed -i "s/80/\${PORT}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
