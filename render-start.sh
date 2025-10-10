#!/bin/bash
# Render deployment script

echo "📦 Iniciando proceso de despliegue..."

# Instalar dependencias de PHP
echo "🔧 Instalando dependencias de PHP..."
composer install --no-dev --optimize-autoloader

# Instalar dependencias de Node.js
echo "🔧 Instalando dependencias de Node.js..."
npm ci

# Compilar assets
echo "🏗️ Compilando assets..."
npm run build

# Ejecutar migraciones
echo "🗄️ Ejecutando migraciones..."
php artisan migrate --force

# Limpiar y optimizar caché
echo "🧹 Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar sistema de almacenamiento
echo "📁 Configurando almacenamiento..."
php setup-storage.php

# Crear enlace simbólico de storage
echo "🔗 Creando enlace de storage..."
php artisan storage:link

echo "🎉 Despliegue completado exitosamente!"
