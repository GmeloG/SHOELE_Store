#!/bin/bash
set -e

# Garantir estrutura e permissões da pasta de uploads
# (o named volume é gerido pelo Docker, por isso este mkdir funciona sempre)
mkdir -p /var/www/html/uploads/produtos
chown -R www-data:www-data /var/www/html/uploads
chmod -R 775 /var/www/html/uploads

# Em Linux, dar permissões de leitura ao Apache nos ficheiros do bind mount
find /var/www/html -not -path "*/uploads/*" -not -path "*/.git/*" -type f -exec chmod a+r {} \;
find /var/www/html -not -path "*/uploads/*" -not -path "*/.git/*" -type d -exec chmod a+rx {} \;

exec apache2-foreground
