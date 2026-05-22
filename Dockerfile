FROM php:8.1-apache

# Instalar extensões PHP necessárias
RUN docker-php-ext-install pdo pdo_mysql

# Instalar extensão GD para processamento de imagens
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/*

# Ativar mod_rewrite do Apache
RUN a2enmod rewrite

# Suprimir aviso de ServerName
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Permitir .htaccess em todo o servidor
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Definir pasta de trabalho
WORKDIR /var/www/html

# Garantir permissões corretas na pasta de uploads
RUN mkdir -p /var/www/html/uploads/produtos \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
