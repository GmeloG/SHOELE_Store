#!/bin/bash
set -e

# Garantir que a pasta de uploads existe com permissões corretas
# (necessário em Linux porque o volume mount sobrepõe o que foi criado no Dockerfile)
mkdir -p /var/www/html/uploads/produtos
chown -R www-data:www-data /var/www/html/uploads
chmod -R 775 /var/www/html/uploads

# Em Linux, os ficheiros montados via volume pertencem ao utilizador do host.
# Dar permissões de leitura a todos para que o Apache (www-data) consiga ler.
find /var/www/html -not -path "*/uploads/*" -type f -exec chmod a+r {} \;
find /var/www/html -not -path "*/uploads/*" -type d -exec chmod a+rx {} \;

exec apache2-foreground
