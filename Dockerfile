FROM php:8.4-cli-bullseye

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get upgrade -y 
RUN apt-get update && apt-get upgrade -y && apt-get install --no-install-recommends -y \
    git unzip zip curl libicu-dev libonig-dev libxml2-dev \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libcurl4-openssl-dev pkg-config libssl-dev \
    default-libmysqlclient-dev default-mysql-client \
    gnupg2 lsb-release apt-transport-https ca-certificates \
    && docker-php-ext-install intl mbstring pdo pdo_mysql zip  \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --prefer-dist --no-progress

CMD ["composer", "test"]
