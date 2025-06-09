FROM php:8.2-apache
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    supervisor \
    && docker-php-ext-install pdo pdo_mysql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory.ini && \
    echo "post_max_size = 50M" > /usr/local/etc/php/conf.d/post.ini && \
    echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/post.ini
WORKDIR /var/www/html
COPY . .
RUN composer install --optimize-autoloader --no-dev
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache
RUN if [ ! -f ".env" ]; then cp .env.example .env && php artisan key:generate; fi
COPY docker/supervisor.conf /etc/supervisor/conf.d/laravel-worker.conf
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
EXPOSE 80
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
