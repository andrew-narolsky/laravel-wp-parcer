FROM nginx:stable-alpine-perl

ARG CONF_FILE=nginx.conf
ADD ${CONF_FILE} /etc/nginx/nginx.conf

RUN apk update \
    && apk upgrade \
    && apk add --no-cache openssl bash

RUN set -x ; \
    addgroup -g 82 -S www-data ; \
    adduser -u 82 -D -S -G www-data www-data && exit 0 ; exit 1

CMD nginx

EXPOSE 80 443