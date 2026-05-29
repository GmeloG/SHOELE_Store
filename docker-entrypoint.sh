#!/bin/bash

# Em Linux, o bind mount herda as permissões do host.
# O root no container (sudo Docker) consegue fazer chmod mesmo em dirs com 700.
chmod -R a+rX /var/www/html || true

# Garantir estrutura e permissões da pasta de uploads por cima do chmod anterior
mkdir -p /var/www/html/uploads/produtos
chown -R www-data:www-data /var/www/html/uploads
chmod -R 775 /var/www/html/uploads

exec apache2-foreground
