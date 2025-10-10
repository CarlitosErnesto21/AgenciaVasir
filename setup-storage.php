<?php
/**
 * Script para configurar el almacenamiento en producción
 * Se ejecuta automáticamente durante el deployment en Render
 */

// Cargar Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

echo "🔧 Configurando sistema de almacenamiento...\n";

// Crear directorios de almacenamiento
$directories = [
    storage_path('app/public'),
    storage_path('app/public/tours'),
    storage_path('app/public/productos'),
    storage_path('app/public/hoteles'),
    storage_path('app/public/aerolinea'),
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Directorio creado: $dir\n";
    } else {
        echo "📁 Directorio existe: $dir\n";
    }
}

// Verificar el enlace simbólico
$link = public_path('storage');
$target = storage_path('app/public');

if (is_link($link)) {
    echo "🔗 Enlace simbólico existe: $link -> " . readlink($link) . "\n";
} else {
    echo "⚠️  Enlace simbólico no existe. Ejecutar: php artisan storage:link\n";
}

// Verificar permisos
$testFile = storage_path('app/public/.test');
if (file_put_contents($testFile, 'test') !== false) {
    unlink($testFile);
    echo "✅ Permisos de escritura: OK\n";
} else {
    echo "❌ Permisos de escritura: FALLÓ\n";
}

echo "🎉 Configuración de almacenamiento completada!\n";
