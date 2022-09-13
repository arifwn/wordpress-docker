#!/bin/bash
set -e

if [ -z "${GWD_SKIP_WP_PATCH}" ]; then
    echo 'patching wordpress in 3 minutes. define GWD_SKIP_WP_PATCH environment variable to skip this'
    sleep 3m

    mv /var/www/html/xmlrpc.php /tmp/
    touch /var/www/html/xmlrpc.php
    chown "33:33" /var/www/html/xmlrpc.php

    echo 'wordpress patch completed'
fi
