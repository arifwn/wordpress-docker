FROM wordpress:php8.0

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
    pecl install redis; \
    pecl install mcrypt-1.0.4; \
    docker-php-ext-enable memcached; \
    docker-php-ext-enable mcrypt; \
    docker-php-ext-enable redis; \
    docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr; \
    docker-php-ext-configure pdo_dblib --with-libdir=/lib/x86_64-linux-gnu; \
    docker-php-ext-configure gd --with-webp --with-jpeg \
    --with-xpm --with-freetype; \
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
        gnupg \
        less \
        mariadb-client \
        apt-transport-https xfonts-base xfonts-75dpi libsasl2-dev wget fontconfig libxrender1 \
    ; \
    wget --quiet https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.5/wkhtmltox_0.12.5-1.stretch_amd64.deb -P /tmp/; \
    dpkg -i /tmp/wkhtmltox_0.12.5-1.stretch_amd64.deb; \
    rm /tmp/wkhtmltox_0.12.5-1.stretch_amd64.deb \
    rm -rf /var/lib/apt/lists/*

# https://make.wordpress.org/cli/2018/05/31/gpg-signature-change/
# pub   rsa2048 2018-05-31 [SC]
#       63AF 7AA1 5067 C056 16FD  DD88 A3A2 E8F2 26F0 BC06
# uid           [ unknown] WP-CLI Releases <releases@wp-cli.org>
# sub   rsa2048 2018-05-31 [E]
ENV WORDPRESS_CLI_GPG_KEY 63AF7AA15067C05616FDDD88A3A2E8F226F0BC06

ENV WORDPRESS_CLI_VERSION 2.4.0
ENV WORDPRESS_CLI_SHA512 4049c7e45e14276a70a41c3b0864be7a6a8cfa8ea65ebac8b184a4f503a91baa1a0d29260d03248bc74aef70729824330fb6b396336172a624332e16f64e37ef

RUN set -ex; \
    curl -o /usr/local/bin/wp.gpg -fSL "https://github.com/wp-cli/wp-cli/releases/download/v${WORDPRESS_CLI_VERSION}/wp-cli-${WORDPRESS_CLI_VERSION}.phar.gpg"; \
    \
    export GNUPGHOME="$(mktemp -d)"; \
    gpg --batch --keyserver ha.pool.sks-keyservers.net --recv-keys "$WORDPRESS_CLI_GPG_KEY"; \
    gpg --batch --decrypt --output /usr/local/bin/wp /usr/local/bin/wp.gpg; \
    command -v gpgconf && gpgconf --kill all || :; \
    rm -rf "$GNUPGHOME" /usr/local/bin/wp.gpg; \
    \
    echo "$WORDPRESS_CLI_SHA512 */usr/local/bin/wp" | sha512sum -c -; \
    chmod +x /usr/local/bin/wp; \
    \
    wp --allow-root --version

COPY mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY init-with-newrelic.sh /usr/local/bin/

RUN a2enmod headers

ENTRYPOINT ["init-with-newrelic.sh"]
CMD ["apache2-foreground"]
