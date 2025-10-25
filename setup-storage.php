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
    storage_path('app/public/paquetes'),
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0775, true);
        echo "✅ Directorio creado: $dir\n";
    } else {
        echo "📁 Directorio existe: $dir\n";
    }
    // Asegurar permisos correctos
    chmod($dir, 0775);
    // Cambiar propietario a www-data
    $output = shell_exec("chown -R www-data:www-data $dir 2>&1");
    echo "🔧 Permisos 775 y propietario www-data establecidos para: $dir\n";
}

// Crear el enlace simbólico
$link = public_path('storage');
$target = storage_path('app/public');

if (is_link($link)) {
    echo "🔗 Enlace simbólico existe: $link -> " . readlink($link) . "\n";
} else {
    echo "🔧 Creando enlace simbólico...\n";
    // Crear el enlace simbólico manualmente si no existe
    if (symlink($target, $link)) {
        echo "✅ Enlace simbólico creado: $link -> $target\n";
    } else {
        echo "❌ Error creando enlace simbólico. Intentando con Artisan...\n";
        $output = shell_exec('php artisan storage:link 2>&1');
        echo "Artisan output: " . $output . "\n";
    }
}

// Verificar permisos de escritura en cada directorio
foreach ($directories as $dir) {
    // Mostrar información detallada de permisos
    $permissions = substr(sprintf('%o', fileperms($dir)), -4);
    $owner = posix_getpwuid(fileowner($dir))['name'] ?? 'unknown';
    $group = posix_getgrgid(filegroup($dir))['name'] ?? 'unknown';
    echo "📋 Info directorio $dir: permisos=$permissions, propietario=$owner:$group\n";

    $testFile = $dir . '/.test';
    if (file_put_contents($testFile, 'test') !== false) {
        unlink($testFile);
        echo "✅ Permisos de escritura OK en: $dir\n";
    } else {
        echo "❌ Permisos de escritura FALLÓ en: $dir\n";
        echo "🔧 Intentando arreglar permisos con más fuerza...\n";
        // Intentar arreglar permisos con más fuerza
        shell_exec("chmod -R 775 $dir");
        shell_exec("chown -R www-data:www-data $dir 2>/dev/null || true");

        // Verificar nuevamente después del arreglo
        if (file_put_contents($testFile, 'test') !== false) {
            unlink($testFile);
            echo "✅ Permisos arreglados correctamente para: $dir\n";
        } else {
            echo "❌ No se pudieron arreglar permisos para: $dir\n";
        }
    }
}

echo "🎉 Configuración de almacenamiento completada!\n";
