#!/bin/bash

# Script de inicio para Render
set -e

echo "🚀 Iniciando aplicación Laravel en Render..."

# Esperar a que la base de datos esté disponible
echo "⏳ Esperando conexión a la base de datos..."
until php artisan db:show --quiet 2>/dev/null; do
    echo "⏳ Base de datos no disponible, esperando..."
    sleep 2
done

echo "✅ Base de datos conectada!"

# Ejecutar migraciones (fresh para recrear la base de datos completamente)
echo "🔄 Recreando base de datos con migraciones frescas..."
php artisan migrate:fresh --force --seed

# Ejecutar migraciones (solo migrate, no fresh en producción)
# echo "🔄 Ejecutando migraciones..."
# php artisan migrate --force

# Configurar sistema de almacenamiento
echo "📁 Configurando almacenamiento..."
php setup-storage.php

# Limpiar carpetas de storage públicas
echo "🗑️ Limpiando carpetas de storage públicas..."

# Mostrar contenido antes de limpiar
echo "📊 Contenido antes de limpiar:"
ls -la public/storage/hoteles/ 2>/dev/null || echo "  hoteles/ no existe o está vacía"
ls -la public/storage/productos/ 2>/dev/null || echo "  productos/ no existe o está vacía"
ls -la public/storage/tours/ 2>/dev/null || echo "  tours/ no existe o está vacía"

# Limpiar las carpetas
rm -rf public/storage/hoteles/* 2>/dev/null || echo "  hoteles/ ya estaba vacía"
rm -rf public/storage/productos/* 2>/dev/null || echo "  productos/ ya estaba vacía"
rm -rf public/storage/tours/* 2>/dev/null || echo "  tours/ ya estaba vacía"

# Verificar que se limpiaron
echo "📊 Contenido después de limpiar:"
ls -la public/storage/hoteles/ 2>/dev/null || echo "  hoteles/ no existe"
ls -la public/storage/productos/ 2>/dev/null || echo "  productos/ no existe"
ls -la public/storage/tours/ 2>/dev/null || echo "  tours/ no existe"

echo "✅ Proceso de limpieza completado!"

# Asegurar que el symlink funcione
echo "🔗 Creando enlace simbólico de storage..."
php artisan storage:link || echo "⚠️ Error creando symlink con artisan, usando script personalizado"

# Limpiar y cachear configuraciones
echo "🧹 Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Crear enlace simbólico para storage si no existe
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

echo "✅ Aplicación lista!"

# PENDIENTE PARA BORRAR LUEGO
# Iniciar worker de queue en background para emails
echo "📧 Iniciando worker de cola de emails..."
php artisan queue:work --daemon --sleep=3 --tries=3 &

# Iniciar Apache
exec apache2-foreground
