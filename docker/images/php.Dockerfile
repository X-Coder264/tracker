FROM php:8.0-fpm-alpine

ARG HOST_USER_ID
ARG HOST_GROUP_ID

ARG DEBIAN_FRONTEND=noninteractive

RUN set -xe && \
    apk add --no-cache --upgrade apk-tools zlib && \
    apk add --no-cache --update --virtual .phpize-deps $PHPIZE_DEPS && \
    apk add --no-cache \
        oniguruma-dev \
        curl-dev \
        libxml2-dev \
        git \
        openssh-client \
        zlib-dev \
        libtool \
        libzip-dev && \
    # create app user
    addgroup -S -g ${HOST_GROUP_ID} app && \
    adduser -S -s /bin/sh -DS -u ${HOST_USER_ID} -G app app && \
    # download and install composer
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/bin --filename=composer && \
    php -r "unlink('composer-setup.php');" && \
    # install php extensions
    pecl channel-update pecl.php.net && \
    pecl install xdebug apcu && \
    docker-php-ext-install mbstring curl dom mysqli pdo_mysql zip && \
    # enable php extensions
    docker-php-ext-enable xdebug apcu zip && \
    # cleanup
    apk del --purge .phpize-deps && \
    rm -rf /tmp/* && \
    rm -rf /usr/share/php7 && \
    rm -rf /var/cache/apk/*
