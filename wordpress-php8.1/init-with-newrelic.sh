#!/bin/bash
set -e

if [ ! -z "$POST_INSTALL_SCRIPT" ]; then
    echo "executing post installation script: $POST_INSTALL_SCRIPT"
    "/var/www/html/$POST_INSTALL_SCRIPT"
fi

if [ "$NEWRELIC_INSTALL" = 'YES' ]; then
    if [ ! -f /usr/local/etc/php/conf.d/newrelic.ini ]; then
        echo "enabling newrelic"
        curl --output - $NEWRELIC_PHP_AGENT_URL | tar -C /tmp -zx
        
        export NR_INSTALL_USE_CP_NOT_LN=1
        export NR_INSTALL_SILENT=1
        /tmp/newrelic-php5-*/newrelic-install install
        rm -rf /tmp/newrelic-php5-* /tmp/nrinstall*
        sed -i \
            -e 's/"REPLACE_WITH_REAL_KEY"/"${NEWRELIC_LICENSE_KEY}"/' \
            -e 's/newrelic.appname = "PHP Application"/newrelic.appname = "${NEWRELIC_APP_NAME}"/' \
            -e 's/;newrelic.daemon.app_connect_timeout =.*/newrelic.daemon.app_connect_timeout=15s/' \
            -e 's/;newrelic.daemon.start_timeout =.*/newrelic.daemon.start_timeout=5s/' \
            /usr/local/etc/php/conf.d/newrelic.ini
    fi
fi

echo "<?php echo 'ready';" > /var/www/html/kubernetes-readiness-check.php
chown "33:33" /var/www/html/kubernetes-readiness-check.php

exec docker-entrypoint.sh "$@"
