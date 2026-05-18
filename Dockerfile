FROM php:8.3-fpm-alpine

RUN apk add --no-cache bash sqlite \
    && apk add --no-cache --virtual .build-deps sqlite-dev \
    && docker-php-ext-install pdo_sqlite \
    && apk del .build-deps

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/cuentasclaras-entrypoint
RUN chmod +x /usr/local/bin/cuentasclaras-entrypoint

EXPOSE 9000

ENTRYPOINT ["cuentasclaras-entrypoint"]
CMD ["php-fpm"]
