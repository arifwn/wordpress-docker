FROM wordpress:cli

USER root

RUN apk add --no-cache \
        bash \
        vim \
        curl \
        zip

ENTRYPOINT ["docker-entrypoint.sh"]
USER 33:33
CMD ["wp", "shell"]
