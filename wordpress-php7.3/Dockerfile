FROM wordpress:php7.3

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
        libmcrypt-dev \
        libwebp-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libxpm-dev \
        libfreetype6-dev \
    ; \
    \
    pecl install memcached; \
    pecl install mcrypt-1.0.2; \
    docker-php-ext-enable memcached; \
    docker-php-ext-enable mcrypt; \
    docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr; \
    docker-php-ext-configure pdo_dblib --with-libdir=/lib/x86_64-linux-gnu; \
    docker-php-ext-configure gd --with-gd --with-webp-dir --with-jpeg-dir \
    --with-png-dir --with-zlib-dir --with-xpm-dir --with-freetype-dir; \
    docker-php-ext-install gd xsl pdo_odbc soap pdo_dblib zip; \
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
    apt-get update; \
    apt-get install -y \
        zip \
        unzip \
        unixodbc \
        apt-transport-https xfonts-base xfonts-75dpi libsasl2-dev wget fontconfig libxrender1 \
    ; \
    wget --quiet https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.5/wkhtmltox_0.12.5-1.stretch_amd64.deb -P /tmp/; \
    dpkg -i /tmp/wkhtmltox_0.12.5-1.stretch_amd64.deb; \
    rm /tmp/wkhtmltox_0.12.5-1.stretch_amd64.deb \
    rm -rf /var/lib/apt/lists/*

COPY mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf

RUN a2enmod headers

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
