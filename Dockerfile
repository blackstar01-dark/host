FROM php:8.2-apache

# Copiar todos los archivos al directorio que Apache sirve
COPY . /var/www/html/

# Instalar extensiones necesarias (PDO + SQLite + MySQL si las necesitas)
RUN docker-php-ext-install pdo pdo_mysql pdo_sqlite

# Ajustar permisos para que Apache pueda leer y escribir archivos si es necesario
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80
EXPOSE 80

# Ejecutar Apache en primer plano
CMD ["apache2-foreground"]
