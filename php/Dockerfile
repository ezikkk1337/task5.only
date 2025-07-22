FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo_mysql mysqli gd mbstring zip soap dom


RUN echo "memory_limit = 1024M" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "post_max_size = 256M" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "upload_max_filesize = 256M" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "max_input_vars = 10000" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "short_open_tag = On" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/bitrix.ini

RUN echo "opcache.enable=0" >> /usr/local/etc/php/conf.d/bitrix.ini

RUN echo "date.timezone = Europe/Moscow" >> /usr/local/etc/php/conf.d/bitrix.ini

RUN echo "user_ini.filename = .user.ini" >> /usr/local/etc/php/conf.d/bitrix.ini \
    && echo "user_ini.cache_ttl = 300" >> /usr/local/etc/php/conf.d/bitrix.ini


RUN groupadd -g 1000 bitrix \
    && useradd -u 1000 -g bitrix -m bitrix

WORKDIR /var/www/html

ENV TZ=Europe/Moscow

RUN chown -R bitrix:bitrix /var/www/html \
    && chmod -R 755 /var/www/html

USER bitrix
