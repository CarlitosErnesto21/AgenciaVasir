# 📋 GUÍA COMPLETA DE MIGRACIÓN - Sistema de Imágenes Laravel Storage

## 🎯 PROYECTO ORIGINAL
**Ruta**: `C:\Users\ernes\Documents\modulos\proyecto_graduacion\AgenciaVasir`

---

## 📂 ARCHIVOS MODIFICADOS

### 🔧 **CONTROLADORES** (Backend - Laravel)

#### 1. `app/Http/Controllers/TourController.php`
**Cambios realizados:**
- ✅ Agregar `use Illuminate\Support\Facades\Storage;` al inicio
- ✅ Método `store()`: Cambiar `move()` por `Storage::disk('public')->store('tours', 'public')`
- ✅ Método `update()`: Cambiar `move()` por `Storage::disk('public')->store('tours', 'public')`
- ✅ Método `destroy()`: Cambiar `unlink()` por `Storage::disk('public')->delete()`

**Código a reemplazar en store():**
```php
// ANTES:
if ($request->hasFile('imagen')) {
    $imageName = time() . '.' . $request->imagen->extension();
    $request->imagen->move(public_path('images/tours'), $imageName);
    $tour->imagen = $imageName;
}

// DESPUÉS:
if ($request->hasFile('imagen')) {
    $imageName = $request->file('imagen')->store('tours', 'public');
    $tour->imagen = $imageName;
}
```

#### 2. `app/Http/Controllers/ProductoController.php`
**Cambios realizados:**
- ✅ Agregar `use Illuminate\Support\Facades\Storage;` al inicio
- ✅ Método `store()`: Cambiar `move()` por `Storage::disk('public')->store('productos', 'public')`
- ✅ Método `update()`: Cambiar `move()` por `Storage::disk('public')->store('productos', 'public')`
- ✅ Método `destroy()`: Cambiar `unlink()` por `Storage::disk('public')->delete()`

**Código a reemplazar en store():**
```php
// ANTES:
if ($file = $request->file('imagen')) {
    $destinationPath = 'images/productos/';
    $imageName = date('YmdHis') . "_" . $file->getClientOriginalName();
    $file->move($destinationPath, $imageName);
    $producto->imagen = $imageName;
}

// DESPUÉS:
if ($file = $request->file('imagen')) {
    $imageName = $file->store('productos', 'public');
    $producto->imagen = $imageName;
}
```

#### 3. `app/Http/Controllers/HotelController.php`
**Cambios realizados:**
- ✅ Agregar `use Illuminate\Support\Facades\Storage;` al inicio
- ✅ Método `store()`: Cambiar `move()` por `Storage::disk('public')->store('hoteles', 'public')`
- ✅ Método `update()`: Cambiar `move()` por `Storage::disk('public')->store('hoteles', 'public')`
- ✅ Método `destroy()`: Cambiar `unlink()` por `Storage::disk('public')->delete()`

#### 4. `app/Http/Controllers/AerolineaController.php`
**Cambios realizados:**
- ✅ Agregar `use Illuminate\Support\Facades\Storage;` al inicio
- ✅ Método `store()`: Cambiar `move()` por `Storage::disk('public')->store('aerolinea', 'public')`
- ✅ Método `update()`: Cambiar `move()` por `Storage::disk('public')->store('aerolinea', 'public')`
- ✅ Método `destroy()`: Cambiar `unlink()` por `Storage::disk('public')->delete()`

---

### 🎨 **COMPONENTES VUE** (Frontend)

#### 5. `resources/js/Components/ImageWithFallback.vue`
**Cambios realizados:**
- ✅ Componente completamente actualizado para manejar fallbacks
- ✅ Genera imágenes placeholder con Canvas cuando fallan las cargas
- ✅ Manejo robusto de errores de carga

#### 6. `resources/js/Pages/Catalogos/Productos.vue`
**Cambios realizados:**
- ✅ Cambiar todas las referencias de `/images/productos/` por `/storage/productos/`
- ✅ Implementar `ImageWithFallback` component

**Código a reemplazar:**
```vue
<!-- ANTES: -->
<img :src="`/images/productos/${producto.imagen}`" />

<!-- DESPUÉS: -->
<ImageWithFallback 
  :src="producto.imagen ? `/storage/${producto.imagen}` : null"
  :alt="producto.nombre"
  :fallback-text="producto.nombre"
/>
```

#### 7. `resources/js/Pages/Catalogos/Tours.vue`
**Cambios realizados:**
- ✅ Cambiar `/images/tours/` por `/storage/tours/`
- ✅ Implementar ImageWithFallback

#### 8. `resources/js/Pages/Catalogos/Hoteles.vue`
**Cambios realizados:**
- ✅ Cambiar `/images/hoteles/` por `/storage/hoteles/`
- ✅ Implementar ImageWithFallback

#### 9. `resources/js/Pages/Catalogos/Aerolineas.vue`
**Cambios realizados:**
- ✅ Cambiar `/images/aerolinea/` por `/storage/aerolinea/`
- ✅ Implementar ImageWithFallback

#### 10. `resources/js/Pages/ToursNacionales.vue`
**Cambios realizados:**
- ✅ Cambiar `/images/tours/` por `/storage/tours/`
- ✅ Implementar ImageWithFallback

#### 11. `resources/js/Pages/ToursInternacionales.vue`
**Cambios realizados:**
- ✅ Cambiar `/images/tours/` por `/storage/tours/`
- ✅ Implementar ImageWithFallback

#### 12. `resources/js/Pages/Tienda.vue`
**Cambios realizados:**
- ✅ Cambiar `/images/productos/` por `/storage/productos/`
- ✅ Implementar ImageWithFallback

#### 13. `resources/js/Components/CarritoCompras.vue`
**Cambios realizados:**
- ✅ Cambiar `/images/productos/` por `/storage/productos/`
- ✅ Implementar ImageWithFallback

---

### ⚙️ **ARCHIVOS DE CONFIGURACIÓN**

#### 14. `render-start.sh`
**Cambios realizados:**
- ✅ Agregar línea para ejecutar `setup-storage.php`

**Código a agregar:**
```bash
# Configurar sistema de almacenamiento
echo "📁 Configurando almacenamiento..."
php setup-storage.php
```

---

## 🆕 ARCHIVOS NUEVOS A CREAR

### 1. `setup-storage.php` (En la raíz del proyecto)
```php
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
```

---

## 🔄 PASOS DE MIGRACIÓN

### Paso 1: Backup del Proyecto Original
```bash
cd C:\Users\ernes\Documents\modulos\proyecto_graduacion\
cp -r AgenciaVasir AgenciaVasir_backup_$(date +%Y%m%d)
```

### Paso 2: Aplicar Cambios Backend
1. Modificar los 4 controladores (Tours, Productos, Hoteles, Aerolineas)
2. Agregar `use Illuminate\Support\Facades\Storage;` en cada uno
3. Cambiar métodos `store()`, `update()`, `destroy()` según los ejemplos

### Paso 3: Aplicar Cambios Frontend
1. Crear el componente `ImageWithFallback.vue`
2. Modificar todos los archivos Vue listados
3. Cambiar rutas `/images/` por `/storage/`
4. Implementar el componente ImageWithFallback

### Paso 4: Configuración de Despliegue
1. Crear `setup-storage.php` en la raíz
2. Modificar `render-start.sh` para ejecutar el script

### Paso 5: Compilar y Desplegar
```bash
npm run build
git add .
git commit -m "Migración completa a Laravel Storage"
git push origin main
```

### Paso 6: Migrar Imágenes Existentes (Opcional)
Si hay imágenes existentes en `/public/images/`, ejecutar:
```bash
php artisan storage:link
# Mover manualmente las imágenes de public/images/* a storage/app/public/*
```

---

## ✅ VERIFICACIÓN FINAL

Después de aplicar todos los cambios:

1. **Verificar que no hay errores 404** en las imágenes
2. **Confirmar que las imágenes aparecen** correctamente
3. **Probar subida de nuevas imágenes** en admin
4. **Verificar que los fallbacks** funcionan para imágenes faltantes

---

## 🚨 NOTAS IMPORTANTES

- ✅ **El sistema funciona con o sin imágenes físicas** (fallbacks automáticos)
- ✅ **Compatible con Render hosting** (filesystem ephemeral)
- ✅ **No perderás imágenes** en futuros deployments
- ✅ **Mejor rendimiento** y gestión de assets

---

**Fecha de creación**: 8 de octubre de 2025  
**Proyecto**: AgenciaVasir - Migración a Laravel Storage