FROM php:8.5-apache

# Don't advertise versions, list directories, or serve internal files.
RUN sed -i 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf \
 && printf '%s\n' \
      '<Directory /var/www/html>' \
      '    Options -Indexes' \
      '    <Files "common.php">' \
      '        Require all denied' \
      '    </Files>' \
      '</Directory>' \
      '<Directory /var/www/html/templates>' \
      '    Require all denied' \
      '</Directory>' \
      > /etc/apache2/conf-available/simple-ots.conf \
 && a2enconf simple-ots \
 && echo 'expose_php = Off' > /usr/local/etc/php/conf.d/simple-ots.ini

COPY html /var/www/html

RUN mkdir -p /var/www/db && chown -R www-data:www-data /var/www/db

# Fix ownership at start-up too, so a bind-mounted ./data directory owned by root stays writable.
CMD ["sh", "-c", "chown -R www-data:www-data /var/www/db && exec apache2-foreground"]
