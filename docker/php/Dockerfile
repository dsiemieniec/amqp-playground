FROM php:8.1-fpm

RUN apt-get update \
    && apt-get install -y zlib1g-dev g++ git libicu-dev zip libzip-dev libcurl4-openssl-dev pkg-config libssl-dev procps librabbitmq-dev libssh-dev supervisor \
    && docker-php-ext-install intl opcache pdo pdo_mysql sockets bcmath sockets pcntl \
    && pecl install apcu \
    && pecl install amqp \
    && docker-php-ext-enable apcu \
    && docker-php-ext-enable amqp \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

WORKDIR /var/www/project

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN service supervisor start
