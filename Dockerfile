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

# ─────────────────────────────────────────────────────────────
# All-in-one mode: bundle MariaDB inside the container so the
# app runs WITHOUT an external database (see docker/entrypoint.sh).
# Override DB_HOST/DB_USER/DB_PASS/DB_NAME env vars to use an
# external MySQL instead — the entrypoint skips the bundled DB.
# ─────────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends mariadb-server mariadb-client && \
    rm -rf /var/lib/apt/lists/* && \
    rm -rf /var/lib/mysql/*

# Lean MariaDB config tuned for small free-tier instances (512 MB RAM)
COPY docker/mariadb.cnf /etc/mysql/mariadb.conf.d/99-aerobook.cnf

# Entrypoint: boots MariaDB, seeds schema, starts Apache
COPY docker/entrypoint.sh /usr/local/bin/aerobook-entrypoint.sh
RUN chmod +x /usr/local/bin/aerobook-entrypoint.sh

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

# Boot MariaDB (if DB_HOST is localhost/127.0.0.1) + Apache
CMD ["aerobook-entrypoint.sh"]
