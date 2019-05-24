FROM wordpress:php7.2

# install the PHP extensions we need
RUN set -ex; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libmemcached-dev \
        libz-dev \
        libxml2-dev \
        libxslt-dev \
        unixodbc-dev \
        freetds-dev \
        zlib1g-dev \
        libmcrypt-dev \
    ; \
    \
    pecl install memcached; \
    pecl install mcrypt-1.0.1; \
    docker-php-ext-enable memcached; \
    docker-php-ext-enable mcrypt; \
    docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr; \
    docker-php-ext-configure pdo_dblib --with-libdir=/lib/x86_64-linux-gnu; \
    docker-php-ext-install xsl pdo_odbc soap pdo_dblib zip; \
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
        unixodbc \
    ; \
    rm -rf /var/lib/apt/lists/*

COPY mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf

RUN a2enmod headers

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
