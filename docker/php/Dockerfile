FROM php:8.3-apache

RUN apt update \
    && apt install -y zlib1g-dev g++ git libicu-dev libpq-dev zip libzip-dev libssl-dev librabbitmq-dev zip libxslt-dev \
    && docker-php-ext-install intl pdo opcache pdo_pgsql xsl \
    && pecl install apcu redis amqp pcov xdebug\
    && docker-php-ext-enable apcu \
    && docker-php-ext-enable redis \
    && docker-php-ext-enable pcov \
    && docker-php-ext-enable amqp \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip \
    && a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/symfony_docker/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf


COPY config/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY config/memory.ini /usr/local/etc/php/conf.d/memory.ini
COPY config/timezone.ini /usr/local/etc/php/conf.d/timezone.ini

WORKDIR /var/www/symfony_docker

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony
