##################################
### Set the Pytest environment ###
##################################
FROM php:apache

# Set the working directory
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
        libzip-dev \
        && docker-php-ext-configure zip \
        && docker-php-ext-install zip \
        && pecl install mailparse \
        && docker-php-ext-enable mailparse

