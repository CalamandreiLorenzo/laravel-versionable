FROM composer:latest

WORKDIR /app
RUN chown 1000:1000 /app
USER 1000
VOLUME /app