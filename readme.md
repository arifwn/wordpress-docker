Wordpress Docker Image
======================

Includes commonly used php extensions.

Build
-----

- `docker build -t arifwn/wordpress:7.4-apache ./wordpress-php7.4`
- `docker build -t arifwn/wordpress:8.0-apache ./wordpress-php8.0`
- `docker build -t arifwn/wordpress:8.0-apache-slim ./wordpress-php8.0-slim`
- `docker build -t arifwn/wordpress:8.1-apache ./wordpress-php8.1`
- `docker build -t arifwn/wordpress:8.1-apache-slim ./wordpress-php8.1-slim`
- `docker build -t arifwn/wordpress:cli ./wp-cli`
- `docker push arifwn/wordpress`
- `docker build -t arifwn/php:8.0-apache ./php8.0`
- `docker build -t arifwn/php:8.1-apache ./php8.1`
- `docker push arifwn/php`

Run
---
- cli from kubectl
    - `kubectl --kubeconfig=./kube_config_cluster.yml exec <pod name> -- /bin/bash`
- cli from docker
    - `docker exec -i container_name su - www-data -s /bin/sh -c "cd /var/www/html; /bin/bash"`
- run wp as www-data:
    - `su www-data -s /bin/sh -c "wp shell"`
- run wp-cli image:
    sudo docker run -it --rm \
    --volumes-from container_id \
    --network container:container_id \
    arifwn/wordpress:cli /bin/bash
