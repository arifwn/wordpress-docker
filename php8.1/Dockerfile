FROM php:8.1-apache

# install the PHP extensions we need
RUN set -ex; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libmemcached-dev \
        libz-dev \
        libzip-dev \
        libxml2-dev \
        libxslt-dev \
        unixodbc-dev \
        freetds-dev \
        zlib1g-dev \
        # libmcrypt-dev \
        libwebp-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libxpm-dev \
        libfreetype6-dev \
    ; \
    \
    pecl install memcached; \
    pecl install redis; \
    # pecl install mcrypt-1.0.5; \
    docker-php-ext-enable memcached; \
    # docker-php-ext-enable mcrypt; \
    docker-php-ext-enable redis; \
    docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr; \
    docker-php-ext-configure pdo_dblib --with-libdir=/lib/x86_64-linux-gnu; \
    docker-php-ext-configure gd --with-webp --with-jpeg \
    --with-xpm --with-freetype; \
    docker-php-ext-install mysqli gd xsl pdo_odbc soap pdo_dblib zip opcache; \
    \
# reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
    ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { print $3 }' \
        | sort -u \
        | xargs -r dpkg-query -S \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual; \
    \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    apt-get install -y \
        zip \
        unzip \
        gnupg \
        less \
    ; \
    rm -rf /var/lib/apt/lists/*

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.jit_buffer_size=128M'; \
        echo 'opcache.jit=function'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf

RUN a2enmod rewrite expires headers

VOLUME /var/www/html

COPY docker-entrypoint.sh /usr/local/bin/
COPY init-with-newrelic.sh /usr/local/bin/

ENTRYPOINT ["init-with-newrelic.sh"]
CMD ["apache2-foreground"]
