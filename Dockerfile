FROM php:8.2-apache

RUN apt-get update && apt-get install -y libzip-dev zip \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

EXPOSE 80
