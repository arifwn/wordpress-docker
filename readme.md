Wordpress Docker Image
======================

Includes commonly used php extensions.

Build
-----

- `docker build -t arifwn/wordpress:7.4-apache ./wordpress-php7.4`
- `docker push arifwn/wordpress`
- `docker build -t arifwn/php:7.4-apache ./php7.4`
- `docker push arifwn/php`

Run
---
- cli from kubectl
    - `kubectl --kubeconfig=./kube_config_cluster.yml exec <pod name> -- /bin/bash`
- run wp as www-data:
    - `su www-data -s /bin/sh -c "wp shell"`

