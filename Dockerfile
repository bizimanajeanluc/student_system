FROM php:8.2-apache

RUN a2enmod rewrite

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY ./student-system/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html/assets/uploads /var/www/html/assets/submissions

EXPOSE 80

CMD ["apache2-foreground"]
