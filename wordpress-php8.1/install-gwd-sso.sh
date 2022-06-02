#!/bin/bash
set -e

if [ -z "${OPENID_CONNECT_CLIENT_ID}" ]; then
    echo 'skip installing openid connect client'
else
    echo 'installing openid connect client in 5 minutes...'
    sleep 5m

    if [ ! -d "/var/www/html/wp-content/mu-plugins" ]; then
        mkdir /var/www/html/wp-content/mu-plugins
        chown "33:33" -R /var/www/html/wp-content/mu-plugins
    fi

    if [ ! -d "/var/www/html/wp-content/mu-plugins/gwd-openid-connect-admin-login" ]; then
        cp -r /opt/mu-plugins/gwd-openid-connect-admin-login /var/www/html/wp-content/mu-plugins/
        chown "33:33" -R /var/www/html/wp-content/mu-plugins
    fi

    if [ ! -f /var/www/html/wp-content/mu-plugins/gwd-openid-connect-admin-login.php ]; then
        cp /opt/mu-plugins/gwd-openid-connect-admin-login.php /var/www/html/wp-content/mu-plugins/
        chown "33:33" -R /var/www/html/wp-content/mu-plugins
    fi
    echo 'done'
fi
