ARG SWOOLE_DOCKER_VERSION
FROM php:${SWOOLE_DOCKER_VERSION}-cli

RUN curl -o /usr/bin/composer https://getcomposer.org/composer-1.phar && chmod +x /usr/bin/composer
