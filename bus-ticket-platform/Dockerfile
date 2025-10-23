FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libsqlite3-dev tzdata zip unzip && \
    docker-php-ext-install pdo pdo_sqlite && \
    a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

COPY ./app /var/www/html/app
COPY ./public /var/www/html/public
COPY ./storage /var/www/html/storage

RUN { \
      echo "display_errors=On"; \
      echo "error_reporting=E_ALL"; \
      echo "date.timezone=Europe/Istanbul"; \
      echo "session.save_path=/var/www/html/storage/sessions"; \
   } > /usr/local/etc/php/conf.d/app.ini

RUN chown -R www-data:www-data /var/www/html/storage

WORKDIR /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]
