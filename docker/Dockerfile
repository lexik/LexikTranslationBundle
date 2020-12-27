FROM php:5.6.3-fpm

RUN php -r "copy('https://getcomposer.org/installer', '/opt/composer-setup.php');"
RUN php -r "if (hash_file('sha384', '/opt/composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('/opt/composer-setup.php'); } echo PHP_EOL;"
RUN php /opt/composer-setup.php
RUN php -r "unlink('/opt/composer-setup.php');"
RUN cp /var/www/html/composer.phar /usr/local/bin/composer

WORKDIR /var/www

RUN apt-get update && apt-get install -y --force-yes vim libssl-dev mysql-client
RUN docker-php-ext-install mbstring

# fix installation of openssl:
RUN cp /usr/src/php/ext/openssl/config0.m4 /usr/src/php/ext/openssl/config.m4

RUN docker-php-ext-install openssl

# remove redundant loading of openssl:
RUN rm /usr/local/etc/php/conf.d/ext-openssl.ini

RUN pecl install mongo

RUN docker-php-ext-install zip pdo_mysql mysqli

COPY ./php/php.ini /usr/local/etc/php/php.ini

