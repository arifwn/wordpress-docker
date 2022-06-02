#!/bin/bash
set -e

if [ -z "${OPENID_CONNECT_CLIENT_ID}" ]; then
    echo 'No OpenID Connect client id defined. OpenID Connect client will not be installed.'
else
    echo 'installing OpenID Connect client in 1 minutes...'
    sleep 1m

    if [ ! -d "/var/www/html/wp-content/mu-plugins" ]; then
        mkdir /var/www/html/wp-content/mu-plugins
        chown "33:33" -R /var/www/html/wp-content/mu-plugins
    fi

    if [ -d "/var/www/html/wp-content/mu-plugins/gwd-openid-connect-admin-login" ]; then
        rm -rf /var/www/html/wp-content/mu-plugins/gwd-openid-connect-admin-login 
    fi

    if [ -f /var/www/html/wp-content/mu-plugins/gwd-openid-connect-admin-login.php ]; then
        rm /var/www/html/wp-content/mu-plugins/gwd-openid-connect-admin-login.php
    fi

    cp -r /var/mu-plugins/gwd-openid-connect-admin-login /var/www/html/wp-content/mu-plugins/
    cp /var/mu-plugins/gwd-openid-connect-admin-login.php /var/www/html/wp-content/mu-plugins/
    chown "33:33" -R /var/www/html/wp-content/mu-plugins
    echo 'OpenID Connect installed'
fi
