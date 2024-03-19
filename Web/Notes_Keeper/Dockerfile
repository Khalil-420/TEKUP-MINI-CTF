FROM php:7.4-apache

WORKDIR /var/www/html

COPY ./challenge/. /var/www/html

RUN docker-php-ext-install pdo_mysql

EXPOSE 80
