FROM php:7.2

ARG WWWGROUP
ARG WWWUSER

ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
    && apt-get install -y git zip unzip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN groupadd --force -g $WWWGROUP dev
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u $WWWUSER dev
RUN chown -R $WWWUSER:$WWWGROUP /var/www/html

WORKDIR /var/www/html

COPY php.ini /etc/php/7.2/cli/conf.d/99-dev.ini
