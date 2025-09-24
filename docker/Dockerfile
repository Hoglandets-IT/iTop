ARG PHP_VERSION=8.3
FROM ghcr.io/clysec/alpine-unit:${PHP_VERSION}-alpine3.22

WORKDIR /app

USER root

COPY --chown=unit:unit ./unit.json /docker-entrypoint.d/unit.json
COPY ./docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN apk update \
    && apk add --no-cache git unzip libxml2-dev openldap-dev graphviz \
    && docker-php-ext-install soap \
    && docker-php-ext-install ldap \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && rm -rf /app/* \
    && composer create-project combodo/itop:$ITOP_VERSION /app --repository="https://git-registry.hoglandet.se/api/packages/composer/composer" --remove-vcs \
    && chown -R unit:unit /app /docker-entrypoint.d/unit.json

USER unit
WORKDIR /app

ARG ITOP_VERSION=3.2.2
RUN chmod ug+w -R /app/conf /app/data \
    && rm -rf /app/.git \
    && mkdir -p /app/log \
    && ln -sf /dev/stdout /app/log/itop.log \
    && sed -i 's/GLOB_BRACE/0/' /app/setup/setuputils.class.inc.php
