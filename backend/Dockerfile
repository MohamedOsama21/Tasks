FROM php:8.2-fpm
RUN pecl install redis \
 && docker-php-ext-install mysqli \
 && docker-php-ext-enable redis
COPY php-session.ini /usr/local/etc/php/conf.d/session.ini
WORKDIR /var/www/html
COPY --chown=www-data:www-data src/ .
