FROM php:7.4-alpine

# install the PHP extensions we need (https://make.wordpress.org/hosting/handbook/handbook/server-environment/#php-extensions)
RUN set -ex; \
    \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        imagemagick-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        libpng-dev \
        libxpm-dev \
        freetype-dev \
        libzip-dev \
        libmemcached-dev \
        libmcrypt-dev \
    ; \
    \
    docker-php-ext-configure gd --with-webp --with-jpeg \
    --with-xpm --with-freetype; \
    docker-php-ext-install -j "$(nproc)" \
        bcmath \
        exif \
        gd \
        mysqli \
        zip \
    ; \
    pecl install memcached; \
    docker-php-ext-enable memcached; \
    pecl install mcrypt-1.0.4; \
    docker-php-ext-enable mcrypt; \
    pecl install imagick-3.4.4; \
    docker-php-ext-enable imagick; \
    \
    runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
            | tr ',' '\n' \
            | sort -u \
            | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
    )"; \
    apk add --virtual .wordpress-phpexts-rundeps $runDeps; \
    apk del .build-deps

# set recommended PHP.ini settings
# excluding opcache due https://github.com/docker-library/wordpress/issues/407
# https://wordpress.org/support/article/editing-wp-config-php/#configure-error-logging
RUN { \
# https://www.php.net/manual/en/errorfunc.constants.php
# https://github.com/docker-library/wordpress/issues/420#issuecomment-517839670
        echo 'error_reporting = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_RECOVERABLE_ERROR'; \
        echo 'display_errors = Off'; \
        echo 'display_startup_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /dev/stderr'; \
        echo 'log_errors_max_len = 1024'; \
        echo 'ignore_repeated_errors = On'; \
        echo 'ignore_repeated_source = Off'; \
        echo 'html_errors = Off'; \
    } > /usr/local/etc/php/conf.d/error-logging.ini

# install wp-cli dependencies
RUN apk add --no-cache \
# bash is needed for 'wp shell': https://github.com/wp-cli/shell-command/blob/b8dafcc2a2eba5732fdee70be077675a302848e9/src/WP_CLI/REPL.php#L104
        bash \
        less \
        mysql-client

RUN set -ex; \
    mkdir -p /var/www/html; \
    chown -R www-data:www-data /var/www/html
WORKDIR /var/www/html
VOLUME /var/www/html

# https://make.wordpress.org/cli/2018/05/31/gpg-signature-change/
# pub   rsa2048 2018-05-31 [SC]
#       63AF 7AA1 5067 C056 16FD  DD88 A3A2 E8F2 26F0 BC06
# uid           [ unknown] WP-CLI Releases <releases@wp-cli.org>
# sub   rsa2048 2018-05-31 [E]
ENV WORDPRESS_CLI_GPG_KEY 63AF7AA15067C05616FDDD88A3A2E8F226F0BC06

ENV WORDPRESS_CLI_VERSION 2.6.0
ENV WORDPRESS_CLI_SHA512 d73f9161a1f03b8ecaac7b196b6051fe847b3c402b9c92b1f6f3acbe5b1cf91f7260c0e499b8947bab75920ecec918b39533ca65fa5a1fd3eb6ce7b8e2c58e7d

RUN set -ex; \
    \
    apk add --no-cache --virtual .fetch-deps \
        gnupg \
    ; \
    \
    curl -o /usr/local/bin/wp.gpg -fSL "https://github.com/wp-cli/wp-cli/releases/download/v${WORDPRESS_CLI_VERSION}/wp-cli-${WORDPRESS_CLI_VERSION}.phar.gpg"; \
    \
    export GNUPGHOME="$(mktemp -d)"; \
    gpg --batch --keyserver keyserver.ubuntu.com --recv-keys "$WORDPRESS_CLI_GPG_KEY"; \
    gpg --batch --decrypt --output /usr/local/bin/wp /usr/local/bin/wp.gpg; \
    command -v gpgconf && gpgconf --kill all || :; \
    rm -rf "$GNUPGHOME" /usr/local/bin/wp.gpg; \
    \
    echo "$WORDPRESS_CLI_SHA512 */usr/local/bin/wp" | sha512sum -c -; \
    chmod +x /usr/local/bin/wp; \
    \
    apk del .fetch-deps; \
    \
    wp --allow-root --version

COPY docker-entrypoint.sh /usr/local/bin/

USER root

RUN apk add --no-cache \
        bash \
        vim \
        curl \
        zip

ENTRYPOINT ["docker-entrypoint.sh"]
USER 33:33
CMD ["wp", "shell"]
