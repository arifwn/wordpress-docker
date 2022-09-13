FROM wordpress:php8.1

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
        libmagickwand-dev \
        ghostscript \
    ; \
    \
#    pecl install imagick; \
    mkdir -p /usr/src/php/ext/imagick; \
    curl -fsSL https://github.com/Imagick/imagick/archive/06116aa24b76edaf6b1693198f79e6c295eda8a9.tar.gz | tar xvz -C "/usr/src/php/ext/imagick" --strip 1; \
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
    docker-php-ext-install gd imagick xsl pdo_mysql pdo_odbc soap pdo_dblib zip; \
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
        ghostscript\
        apt-transport-https xfonts-base xfonts-75dpi libsasl2-dev wget fontconfig libxrender1 libxext6 \
    ; \
    wget --quiet https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6-1/wkhtmltox_0.12.6-1.buster_amd64.deb -P /tmp/; \
    dpkg -i /tmp/wkhtmltox_0.12.6-1.buster_amd64.deb; \
    rm /tmp/wkhtmltox_0.12.6-1.buster_amd64.deb \
    rm -rf /var/lib/apt/lists/*

# https://make.wordpress.org/cli/2018/05/31/gpg-signature-change/
# pub   rsa2048 2018-05-31 [SC]
#       63AF 7AA1 5067 C056 16FD  DD88 A3A2 E8F2 26F0 BC06
# uid           [ unknown] WP-CLI Releases <releases@wp-cli.org>
# sub   rsa2048 2018-05-31 [E]
ENV WORDPRESS_CLI_GPG_KEY 63AF7AA15067C05616FDDD88A3A2E8F226F0BC06

ENV WORDPRESS_CLI_VERSION 2.6.0
ENV WORDPRESS_CLI_SHA512 d73f9161a1f03b8ecaac7b196b6051fe847b3c402b9c92b1f6f3acbe5b1cf91f7260c0e499b8947bab75920ecec918b39533ca65fa5a1fd3eb6ce7b8e2c58e7d

RUN set -ex; \
    curl -o /usr/local/bin/wp.gpg -fL "https://github.com/wp-cli/wp-cli/releases/download/v${WORDPRESS_CLI_VERSION}/wp-cli-${WORDPRESS_CLI_VERSION}.phar.gpg"; \
    \
    GNUPGHOME="$(mktemp -d)"; export GNUPGHOME; \
    gpg --batch --keyserver keyserver.ubuntu.com --recv-keys "$WORDPRESS_CLI_GPG_KEY"; \
    gpg --batch --decrypt --output /usr/local/bin/wp /usr/local/bin/wp.gpg; \
    gpgconf --kill all; \
    rm -rf "$GNUPGHOME" /usr/local/bin/wp.gpg; unset GNUPGHOME; \
    \
    echo "$WORDPRESS_CLI_SHA512 */usr/local/bin/wp" | sha512sum -c -; \
    chmod +x /usr/local/bin/wp; \
    wp --allow-root --version

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
        echo 'opcache.memory_consumption=512'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.revalidate_freq=10'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.jit_buffer_size=512M'; \
        echo 'opcache.jit=function'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY init-with-newrelic.sh /usr/local/bin/
COPY imagick-policy.xml /etc/ImageMagick-6/policy.xml
COPY shell.sh /usr/local/bin/
COPY install-gwd-sso.sh /usr/local/bin/
COPY patch-wordpress.sh /usr/local/bin/
ADD mu-plugins /var/mu-plugins

RUN a2enmod rewrite expires headers

ENTRYPOINT ["init-with-newrelic.sh"]
CMD ["apache2-foreground"]
