FROM php:8.4-apache

COPY html /var/www/html

RUN mkdir /var/www/db && touch /var/www/db/db.sqlite && chown -R www-data:www-data /var/www/
