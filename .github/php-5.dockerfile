ARG PHP_DOCKER_VERSION
FROM php:${PHP_DOCKER_VERSION}-cli

RUN sed -i s/httpredir.debian.org/archive.debian.org/g /etc/apt/sources.list
RUN sed -i s/deb.debian.org/archive.debian.org/g /etc/apt/sources.list

RUN sed -i 's|security.debian.org|archive.debian.org/debian-security/|g' /etc/apt/sources.list

RUN sed -i '/stretch-updates/d' /etc/apt/sources.list

RUN apt update --allow-unauthenticated

RUN apt install --allow-unauthenticated -y --force-yes unzip ca-certificates

RUN docker-php-ext-install pcntl > /dev/null

RUN curl -o /usr/bin/composer https://getcomposer.org/composer-1.phar && chmod +x /usr/bin/composer
