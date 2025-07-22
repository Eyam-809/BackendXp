# Usa la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instala extensiones del sistema y PHP necesarias para Laravel y MySQL
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    default-mysql-client \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libmysqlclient-dev \
 && docker-php-ext-install pdo pdo_mysql zip

# Instala Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia el contenido del proyecto al contenedor
COPY . /var/www/html/

# Cambia la raíz del documento a /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Establece permisos necesarios
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Define el directorio de trabajo
WORKDIR /var/www/html

# Instala las dependencias de Composer para producción
RUN composer install --no-dev --optimize-autoloader

# (Opcional) Cache de configuración y rutas para mejorar el rendimiento
# RUN php artisan config:cache
# RUN php artisan route:cache

# Expone el puerto 80 para Apache
EXPOSE 80

# Comando por defecto para iniciar Apache
CMD ["apache2-foreground"]
