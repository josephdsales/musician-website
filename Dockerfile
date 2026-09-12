# Songbook on Render (PHP 8.2 + Apache, talks to Neon Postgres)
FROM php:8.2-apache

# PHP extensions: Postgres + MySQL drivers
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# App lives at repo root (index.php beside this Dockerfile)
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Render injects $PORT — repoint Apache at it, then serve.
CMD sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf \
 && sed -i "s/:80>/:$PORT>/" /etc/apache2/sites-available/000-default.conf \
 && apache2-foreground
