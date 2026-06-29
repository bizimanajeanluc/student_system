FROM php:8.2-apache

RUN a2enmod rewrite

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install MySQL server
RUN apt-get update && apt-get install -y --no-install-recommends \
    mariadb-server \
    && rm -rf /var/lib/apt/lists/*

# Copy app code
COPY ./student-system/ /var/www/html/

# Copy DB schema and entrypoint
COPY ./db.sql /docker-entrypoint-initdb.d/db.sql
COPY ./docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/assets/uploads /var/www/html/assets/submissions \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
