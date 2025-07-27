# Usamos imagen oficial PHP 8.1 con Apache
FROM php:8.1-apache

# Instalar dependencias del sistema y extensiones PHP necesarias para Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
    && docker-php-ext-install pdo pdo_mysql zip mbstring exif pcntl bcmath gd

# Habilitar mod_rewrite para Apache (necesario para Laravel)
RUN a2enmod rewrite

# Instalar Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar el código fuente al contenedor
COPY . /var/www/html/

# Establecer permisos para storage y cache (Laravel necesita permisos de escritura)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Instalar dependencias PHP con Composer (opcional, si quieres instalar al construir la imagen)
RUN composer install --no-dev --optimize-autoloader

# Exponer el puerto 80 (Apache)
EXPOSE 80

# Comando para arrancar Apache en primer plano
CMD ["apache2-foreground"]
