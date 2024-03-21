FROM php:8.3-apache

COPY html /var/www/html

RUN touch /var/www/db.sqlite && chown -R www-data:www-data /var/www/
