#!/bin/bash

# Garantir estrutura e permissões da pasta de uploads
mkdir -p /var/www/html/uploads/produtos
chown -R www-data:www-data /var/www/html/uploads
chmod -R 775 /var/www/html/uploads

# Em Linux, tentar dar permissões de leitura ao Apache nos ficheiros do bind mount.
# 2>/dev/null || true garante que o script nunca falha por falta de permissão.
find /var/www/html -not -path "*/uploads/*" -not -path "*/.git/*" -type f -exec chmod a+r {} \; 2>/dev/null || true
find /var/www/html -not -path "*/uploads/*" -not -path "*/.git/*" -type d -exec chmod a+rx {} \; 2>/dev/null || true

exec apache2-foreground
