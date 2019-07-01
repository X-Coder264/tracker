FROM node:12.4-alpine

ENV DEBIAN_FRONTEND=noninteractive

ARG HOST_USER_ID
ARG HOST_GROUP_ID

# because of libsass, optipng and gifsicle
RUN set -xe && \
    apk add --no-cache --upgrade apk-tools && \
    echo "http://dl-cdn.alpinelinux.org/alpine/edge/community" >> /etc/apk/repositories && \
    # because of libjpeg-turbo-utils
    echo "http://dl-cdn.alpinelinux.org/alpine/edge/main" >> /etc/apk/repositories && \
    apk add --no-cache --update git python make libsass optipng gifsicle libjpeg-turbo-utils g++ libpng-dev automake autoconf nasm bash && \
    # handle user
    deluser --remove-home node && \
    addgroup -S -g ${HOST_GROUP_ID} app && \
    adduser -S -s /bin/sh -DS -u ${HOST_USER_ID} -G app app && \
    # cleanup
    rm -rf /tmp/* && \
    rm -rf /var/cache/apk/*
