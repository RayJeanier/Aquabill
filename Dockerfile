# AquaBill on Render: PHP 8 + Apache with the PostgreSQL driver.
FROM php:8.3-apache

# PostgreSQL driver for PDO (Supabase)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Philippine time for dates shown in the app
RUN echo "date.timezone = Asia/Manila" > /usr/local/etc/php/conf.d/timezone.ini

# Hide PHP errors from visitors (they still go to Render's logs)
RUN { \
      echo "display_errors = Off"; \
      echo "log_errors = On"; \
      echo "error_log = /dev/stderr"; \
    } > /usr/local/etc/php/conf.d/production.ini

# Render tells the app which port to use via $PORT (default 10000)
ENV PORT=10000
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf

# Let PHP see the DB_* environment variables set in Render
RUN echo "PassEnv DB_HOST DB_PORT DB_NAME DB_USER DB_PASS" > /etc/apache2/conf-enabled/passenv.conf

COPY . /var/www/html/

# The app writes config/pricing.json from the Pricing page
RUN chown -R www-data:www-data /var/www/html/config

EXPOSE 10000
