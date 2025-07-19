# Usa la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instala extensiones necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    libpq-dev \
 && docker-php-ext-install zip pdo pdo_pgsql pgsql

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia archivos del proyecto
COPY . /var/www/html/

# Cambia la raíz del documento a /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Establece directorio de trabajo
WORKDIR /var/www/html

# Instala dependencias
RUN composer install --no-dev --optimize-autoloader

# Cache opcional
# RUN php artisan config:cache
# RUN php artisan route:cache

# Expone el puerto 80
EXPOSE 80

# Inicia Apache
CMD ["apache2-foreground"]
