# Usa la imagen oficial de PHP con Apache y extensiones necesarias
FROM php:8.2-apache

# Instala dependencias del sistema y extensiones de PHP necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    libpq-dev \
 && docker-php-ext-install zip pdo pdo_pgsql pgsql

# Instala Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia los archivos del proyecto al directorio raíz del servidor Apache
COPY . /var/www/html/

# Establece permisos para almacenamiento y cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Establece directorio de trabajo
WORKDIR /var/www/html

# Ejecuta composer install para instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Cachea configuración y rutas para producción (opcional)
#RUN php artisan config:cache
#RUN php artisan route:cache

# Expone el puerto 80 para el tráfico web
EXPOSE 80

# Comando para arrancar Apache en primer plano
CMD ["apache2-foreground"]
