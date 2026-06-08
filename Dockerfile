FROM php:8.3-fpm-alpine

RUN apk add --no-cache bash nginx sqlite \
    && apk add --no-cache --virtual .build-deps sqlite-dev \
    && docker-php-ext-install pdo_sqlite \
    && apk del .build-deps

WORKDIR /var/www/html

COPY . /var/www/html
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/entrypoint.sh /usr/local/bin/cuentasclaras-entrypoint
RUN chmod +x /usr/local/bin/cuentasclaras-entrypoint

EXPOSE 8080

ENTRYPOINT ["cuentasclaras-entrypoint"]
CMD ["serve"]
