FROM php:8.2-apache

# Enable Apache mods
RUN a2enmod rewrite headers expires deflate

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    docker-php-ext-enable mysqli

# Enable GD for avatar processing
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libwebp-dev && \
    docker-php-ext-install gd && \
    docker-php-ext-enable gd

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/uploads /var/www/html/logs

# Apache config: serve from /var/www/html
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHP config
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/aerobook.ini && \
    echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/aerobook.ini && \
    echo "max_execution_time = 60" >> /usr/local/etc/php/conf.d/aerobook.ini && \
    echo "display_errors = Off" >> /usr/local/etc/php/conf.d/aerobook.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/aerobook.ini

EXPOSE 80

CMD ["apache2-foreground"]
