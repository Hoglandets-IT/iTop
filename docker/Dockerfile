ARG PHP_VERSION=8.3
FROM ghcr.io/clysec/alpine-unit:${PHP_VERSION}-alpine3.22

WORKDIR /app

USER root

COPY --chown=unit:unit ./unit.json /docker-entrypoint.d/unit.json
COPY ./docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN apk update \
    && apk add --no-cache git unzip libxml2-dev openldap-dev \
    && docker-php-ext-install soap \
    && docker-php-ext-install ldap \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && rm -rf /app/*

RUN rm -rf /app

USER unit
WORKDIR /app

ARG ITOP_VERSION=3.2.2
RUN composer create-project combodo/itop:$ITOP_VERSION /app/web --repository="https://git-registry.hoglandet.se/api/packages/composer/composer" --remove-vcs \
    && chmod ug+w -R /app/web/conf /app/web/data \
    && rm -rf /app/web/.git \
    && mkdir -p /app/web/log \
    && ln -sf /dev/stdout /app/web/log/itop.log \
    && sed -i 's/GLOB_BRACE/0/' /app/web/setup/setuputils.class.inc.php
