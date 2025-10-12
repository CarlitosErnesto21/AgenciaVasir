# 🚀 GUÍA PASO A PASO - IMPLEMENTACIÓN AGENCIA VASIR

## 📋 RESUMEN DE IMPLEMENTACIÓN

Este documento detalla los pasos exactos para replicar la implementación del sistema de gestión de la Agencia de Turismo VASIR, desde la configuración inicial hasta el deployment en producción.

---

## 🎯 FASE 1: PREPARACIÓN DEL ENTORNO

### 1.1 Requisitos del Sistema
```bash
# Requisitos mínimos
PHP: 8.2+
Node.js: 18+
NPM: 9+
Composer: 2.5+
MySQL: 8.0+
```

### 1.2 Creación del Proyecto Base
```bash
# 1. Crear proyecto Laravel
composer create-project laravel/laravel AgenciaVasir "^12.0"
cd AgenciaVasir

# 2. Configurar permisos (Linux/Mac)
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# 3. Generar key de aplicación
php artisan key:generate
```

### 1.3 Configuración de Base de Datos
```env
# .env - Configuración principal
APP_NAME=VASIR
APP_ENV=production
APP_KEY=base64:TU_KEY_AQUI
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=mysql
DB_HOST=tu-railway-host.com
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=tu-password

# Configuración adicional
FILESYSTEM_DISK=public
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

---

## 🎯 FASE 2: INSTALACIÓN DE DEPENDENCIAS

### 2.1 Dependencias Backend (Composer)
```bash
# Paquetes principales
composer require inertiajs/inertia-laravel "^2.0"
composer require laravel/sanctum "^4.0"
composer require spatie/laravel-permission "^6.19"
composer require barryvdh/laravel-dompdf "^3.1"
composer require tightenco/ziggy "^2.5"

# Paquetes de desarrollo
composer require --dev laravel/breeze "^2.3"
composer require --dev laravel/pail "^1.2"
composer require --dev laravel/pint "^1.13"
composer require --dev fakerphp/faker "^1.23"
```

### 2.2 Dependencias Frontend (NPM)
```bash
# Framework y bridge
npm install @inertiajs/vue3@^2.0.0
npm install vue@^3.5.14
npm install @vitejs/plugin-vue@^5.2.4
npm install laravel-vite-plugin@^1.2.0

# UI y estilos
npm install primevue@^4.3.4 primeicons@^7.0.0
npm install @primeuix/themes@^1.1.1
npm install tailwindcss@^3.2.1
npm install @tailwindcss/forms@^0.5.3
npm install autoprefixer@^10.4.12
npm install postcss@^8.4.31

# Funcionalidades adicionales
npm install @fortawesome/vue-fontawesome@^3.0.8
npm install @fortawesome/free-solid-svg-icons@^6.7.2
npm install pinia@^3.0.3
npm install axios@^1.8.2
npm install chart.js@^4.4.9
npm install vue-tel-input@^9.5.0
npm install ziggy-js@^2.5.3

# Herramientas de desarrollo
npm install vite@^6.2.4
npm install concurrently@^9.0.1
```

---

## 🎯 FASE 3: CONFIGURACIÓN INICIAL

### 3.1 Configuración Inertia.js
```bash
# 1. Instalar Inertia
php artisan inertia:install vue

# 2. Publicar middleware
php artisan inertia:middleware
```

```php
// app/Http/Kernel.php - Agregar middleware
'web' => [
    // ... otros middlewares
    \App\Http\Middleware\HandleInertiaRequests::class,
],
```

### 3.2 Configuración Sanctum
```bash
# 1. Publicar configuración
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 2. Ejecutar migración
php artisan migrate
```

### 3.3 Configuración Spatie Permission
```bash
# 1. Publicar migración
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# 2. Ejecutar migración
php artisan migrate
```

### 3.4 Configuración Laravel Breeze
```bash
# 1. Instalar Breeze con Inertia
php artisan breeze:install vue --ssr

# 2. Instalar dependencias y build
npm install && npm run build
```

---

## 🎯 FASE 4: ESTRUCTURA DE BASE DE DATOS

### 4.1 Crear Migraciones Principales
```bash
# Tablas del sistema
php artisan make:migration create_clientes_table
php artisan make:migration create_empleados_table
php artisan make:migration create_tours_table
php artisan make:migration create_productos_table
php artisan make:migration create_hoteles_table
php artisan make:migration create_aerolineas_table
php artisan make:migration create_reservas_table
php artisan make:migration create_ventas_table
php artisan make:migration create_inventarios_table
php artisan make:migration create_paquetes_table
php artisan make:migration create_site_settings_table
php artisan make:migration create_company_values_table

# Tablas de relación
php artisan make:migration create_detalles_reservas_tours_table
php artisan make:migration create_detalles_reservas_hoteles_table
php artisan make:migration create_detalles_reservas_aerolineas_table
php artisan make:migration create_detalles_ventas_table

# Tablas de categorización
php artisan make:migration create_categorias_productos_table
php artisan make:migration create_categorias_hoteles_table
php artisan make:migration create_tipos_documentos_table
php artisan make:migration create_metodos_pagos_table
php artisan make:migration create_paises_table
php artisan make:migration create_provincias_table
php artisan make:migration create_transportes_table
```

### 4.2 Ejemplo de Migración (Tours)
```php
// database/migrations/xxxx_create_tours_table.php
public function up()
{
    Schema::create('tours', function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->text('descripcion');
        $table->decimal('precio', 10, 2);
        $table->integer('cupo_max');
        $table->date('fecha_inicio');
        $table->date('fecha_fin');
        $table->string('imagen')->nullable();
        $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
        $table->enum('tipo', ['NACIONAL', 'INTERNACIONAL'])->default('NACIONAL');
        $table->foreignId('pais_id')->constrained('paises');
        $table->foreignId('provincia_id')->constrained('provincias');
        $table->timestamps();
    });
}
```

### 4.3 Crear Seeders
```bash
# Crear seeders principales
php artisan make:seeder AdminUserSeeder
php artisan make:seeder TipoDocumentoSeeder
php artisan make:seeder CategoriaProductoSeeder
php artisan make:seeder SiteSettingsSeeder
php artisan make:seeder ControlSeeder
```

---

## 🎯 FASE 5: DESARROLLO DE MODELOS

### 5.1 Crear Modelos Eloquent
```bash
# Modelos principales
php artisan make:model Tour
php artisan make:model Producto
php artisan make:model Hotel
php artisan make:model Aerolinea
php artisan make:model Reserva
php artisan make:model Cliente
php artisan make:model Empleado
php artisan make:model Venta
php artisan make:model Inventario
php artisan make:model Paquete
php artisan make:model SiteSetting
php artisan make:model CompanyValue
```

### 5.2 Ejemplo de Modelo (Tour)
```php
// app/Models/Tour.php
class Tour extends Model
{
    protected $fillable = [
        'nombre', 'descripcion', 'precio', 'cupo_max',
        'fecha_inicio', 'fecha_fin', 'imagen', 'estado',
        'tipo', 'pais_id', 'provincia_id'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'precio' => 'decimal:2'
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }
}
```

---

## 🎯 FASE 6: DESARROLLO DE CONTROLADORES

### 6.1 Crear Controladores Principales
```bash
php artisan make:controller TourController --resource
php artisan make:controller ProductoController --resource
php artisan make:controller HotelController --resource
php artisan make:controller AerolineaController --resource
php artisan make:controller ReservaController --resource
php artisan make:controller ClienteController --resource
php artisan make:controller VentaController --resource
php artisan make:controller SettingsController
php artisan make:controller PaqueteController --resource
php artisan make:controller InformePDFController
php artisan make:controller BackupController
```

### 6.2 Ejemplo de Controlador (TourController)
```php
// app/Http/Controllers/TourController.php
use Illuminate\Support\Facades\Storage;

class TourController extends Controller
{
    public function index()
    {
        $tours = Tour::with(['pais', 'provincia'])
            ->get()
            ->map(function ($tour) {
                $cuposReservados = $tour->reservas()
                    ->where('estado', 'CONFIRMADA')
                    ->sum('cupos_reservados');
                
                $tour->cupos_disponibles = max(0, $tour->cupo_max - $cuposReservados);
                return $tour;
            });

        return response()->json($tours);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'precio' => 'required|numeric|min:0',
            'cupo_max' => 'required|integer|min:1',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'tipo' => 'required|in:NACIONAL,INTERNACIONAL',
            'pais_id' => 'required|exists:paises,id',
            'provincia_id' => 'required|exists:provincias,id'
        ]);

        try {
            $tour = new Tour($validated);

            if ($request->hasFile('imagen')) {
                $imageName = $request->file('imagen')->store('tours', 'public');
                $tour->imagen = $imageName;
            }

            $tour->save();

            return response()->json([
                'message' => 'Tour creado exitosamente',
                'tour' => $tour
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creando tour: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}
```

---

## 🎯 FASE 7: DESARROLLO FRONTEND

### 7.1 Configuración Vite
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
```

### 7.2 Configuración Principal (app.js)
```javascript
// resources/js/app.js
import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';

// PrimeVue
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';

// FontAwesome
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { fas } from '@fortawesome/free-solid-svg-icons';
library.add(fas);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(pinia)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.dark-mode'
                    }
                }
            })
            .use(ToastService)
            .component('FontAwesomeIcon', FontAwesomeIcon)
            .component('Toast', Toast)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
```

### 7.3 Estructura de Componentes Vue
```bash
# Crear estructura de directorios
mkdir -p resources/js/Components/DashboardViews
mkdir -p resources/js/Layouts
mkdir -p resources/js/Pages/Auth
mkdir -p resources/js/Pages/Catalogos
mkdir -p resources/js/Pages/VistasClientes
mkdir -p resources/js/Pages/Configuracion
mkdir -p resources/js/stores
```

### 7.4 Store Pinia (Carrito)
```javascript
// resources/js/stores/carrito.js
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export const useCarritoStore = defineStore('carrito', () => {
    const items = ref([]);
    const isVisible = ref(false);
    const page = usePage();

    const itemCount = computed(() => {
        return items.value.reduce((total, item) => total + item.cantidad, 0);
    });

    const totalPrice = computed(() => {
        return items.value.reduce((total, item) => {
            return total + (item.precio * item.cantidad);
        }, 0);
    });

    const agregarItem = (producto) => {
        const existingItem = items.value.find(item => item.id === producto.id);
        
        if (existingItem) {
            existingItem.cantidad += 1;
        } else {
            items.value.push({
                id: producto.id,
                nombre: producto.nombre,
                precio: producto.precio,
                imagen: producto.imagen,
                cantidad: 1
            });
        }
        
        guardarEnStorage();
    };

    const guardarEnStorage = () => {
        localStorage.setItem('carrito_agencia_vasir', JSON.stringify(items.value));
    };

    return {
        items,
        isVisible,
        itemCount,
        totalPrice,
        agregarItem,
        guardarEnStorage
    };
});
```

---

## 🎯 FASE 8: SISTEMA DE STORAGE

### 8.1 Configuración Laravel Storage
```php
// config/filesystems.php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
        'throw' => false,
    ],
],
```

### 8.2 Script de Setup Storage
```php
// setup-storage.php
<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

echo "🔧 Configurando sistema de almacenamiento...\n";

$directories = [
    storage_path('app/public'),
    storage_path('app/public/tours'),
    storage_path('app/public/productos'),
    storage_path('app/public/hoteles'),
    storage_path('app/public/aerolinea'),
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0775, true);
        echo "✅ Directorio creado: $dir\n";
    }
    
    chmod($dir, 0775);
    chown($dir, 'www-data');
    chgrp($dir, 'www-data');
}

$link = public_path('storage');
$target = storage_path('app/public');

if (!file_exists($link)) {
    symlink($target, $link);
    echo "✅ Enlace simbólico creado: $link -> $target\n";
}

echo "✅ Sistema de almacenamiento configurado exitosamente!\n";
```

### 8.3 Componente ImageWithFallback
```vue
<!-- resources/js/Components/ImageWithFallback.vue -->
<template>
  <div class="image-container">
    <img
      :src="imageSrc"
      :alt="alt"
      @error="handleImageError"
      @load="handleImageLoad"
      :class="imageClass"
      v-show="!hasError && !isLoading"
    />
    
    <div v-if="hasError" :class="fallbackClass">
      <canvas
        ref="fallbackCanvas"
        :width="width"
        :height="height"
        :class="imageClass"
      />
    </div>
    
    <div v-if="isLoading" :class="loadingClass">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  src: String,
  alt: String,
  fallbackText: String,
  width: { type: Number, default: 300 },
  height: { type: Number, default: 200 },
  imageClass: { type: String, default: 'w-full h-48 object-cover rounded-lg' },
  fallbackClass: { type: String, default: 'w-full h-48 flex items-center justify-center bg-gray-200 rounded-lg' },
  loadingClass: { type: String, default: 'w-full h-48 flex items-center justify-center bg-gray-100 rounded-lg' }
});

const hasError = ref(false);
const isLoading = ref(true);
const fallbackCanvas = ref(null);

const imageSrc = computed(() => {
  if (!props.src) return '';
  
  if (props.src.startsWith('http') || props.src.startsWith('data:')) {
    return props.src;
  }
  
  return `/storage/${props.src}`;
});

const handleImageError = () => {
  hasError.value = true;
  isLoading.value = false;
  generateFallbackImage();
};

const handleImageLoad = () => {
  isLoading.value = false;
  hasError.value = false;
};

const generateFallbackImage = () => {
  if (!fallbackCanvas.value) return;
  
  const canvas = fallbackCanvas.value;
  const ctx = canvas.getContext('2d');
  
  // Fondo degradado
  const gradient = ctx.createLinearGradient(0, 0, props.width, props.height);
  gradient.addColorStop(0, '#ef4444');
  gradient.addColorStop(1, '#dc2626');
  
  ctx.fillStyle = gradient;
  ctx.fillRect(0, 0, props.width, props.height);
  
  // Texto
  ctx.fillStyle = 'white';
  ctx.font = 'bold 14px Arial, sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  
  const text = props.fallbackText || props.alt || 'Imagen no disponible';
  ctx.fillText(text, props.width / 2, props.height / 2);
};

onMounted(() => {
  if (!props.src) {
    hasError.value = true;
    isLoading.value = false;
    generateFallbackImage();
  }
});
</script>
```

---

## 🎯 FASE 9: DEPLOYMENT EN RENDER

### 9.1 Dockerfile para Render
```dockerfile
# Dockerfile.render
FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath xml

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer puerto
EXPOSE 80

# Script de inicio
CMD ["./render-start.sh"]
```

### 9.2 Script de Inicio para Render
```bash
#!/bin/bash
# render-start.sh

set -e

echo "🚀 Iniciando aplicación Laravel en Render..."

# Esperar a que la base de datos esté disponible
echo "⏳ Esperando conexión a la base de datos..."
until php artisan db:show --quiet 2>/dev/null; do
    echo "⏳ Base de datos no disponible, esperando..."
    sleep 2
done

echo "✅ Base de datos conectada!"

# Ejecutar migraciones
echo "🔄 Ejecutando migraciones..."
php artisan migrate --force

# Ejecutar seeders
echo "🌱 Ejecutando seeders..."
php artisan db:seed --force

# Configurar sistema de almacenamiento
echo "📁 Configurando almacenamiento..."
php setup-storage.php

# Optimizar aplicación
echo "🧹 Optimizando aplicación..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Crear enlace simbólico para storage
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

echo "✅ Aplicación lista!"

# Iniciar Apache
exec apache2-foreground
```

### 9.3 Variables de Entorno para Render
```env
# Render Environment Variables
APP_NAME=VASIR
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vasir-agency-app.onrender.com

DB_CONNECTION=mysql
DB_HOST=roundhouse.proxy.rlwy.net
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=TU_PASSWORD_RAILWAY

FILESYSTEM_DISK=public
SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=database

ADMIN_NAME="Carlos Ernesto Arteaga Rosales"
ADMIN_EMAIL="ernesto.rosales354@gmail.com"
ADMIN_PASSWORD="Carlitos123"
```

---

## 🎯 FASE 10: TESTING Y OPTIMIZACIÓN

### 10.1 Tests Básicos
```bash
# Crear tests
php artisan make:test TourTest
php artisan make:test ProductoTest
php artisan make:test ReservaTest

# Ejecutar tests
php artisan test
```

### 10.2 Optimización de Performance
```bash
# Cache de configuración
php artisan config:cache

# Cache de rutas
php artisan route:cache

# Cache de vistas
php artisan view:cache

# Optimización de autoloader
composer dump-autoload --optimize

# Build de producción frontend
npm run build
```

### 10.3 Limpieza de Logs de Debug
```bash
# Remover console.log y logs innecesarios
find resources/js -name "*.vue" -exec sed -i '/console\.log/d' {} \;
find app/Http/Controllers -name "*.php" -exec sed -i '/Log::info.*debug/d' {} \;
```

---

## ✅ CHECKLIST FINAL DE DEPLOYMENT

### Pre-Deployment
- [ ] ✅ Todas las migraciones ejecutadas
- [ ] ✅ Seeders configurados
- [ ] ✅ Storage system configurado
- [ ] ✅ Variables de entorno configuradas
- [ ] ✅ Assets compilados (npm run build)
- [ ] ✅ Logs de debug eliminados
- [ ] ✅ Tests pasando

### Post-Deployment
- [ ] ✅ Base de datos conectada
- [ ] ✅ Storage symlinks funcionando
- [ ] ✅ Imágenes cargando correctamente
- [ ] ✅ Autenticación funcionando
- [ ] ✅ APIs respondiendo
- [ ] ✅ SSL certificate activo
- [ ] ✅ Domain configurado

### Verificación Final
- [ ] ✅ Homepage carga correctamente
- [ ] ✅ Panel admin accesible
- [ ] ✅ Sistema de reservas funcional
- [ ] ✅ Tienda virtual operativa
- [ ] ✅ Reportes generándose
- [ ] ✅ Email notifications working

---

## 📊 MÉTRICAS DE ÉXITO

### Performance Targets
- **Tiempo de carga**: < 3 segundos
- **First Contentful Paint**: < 2 segundos
- **Time to Interactive**: < 4 segundos
- **Cumulative Layout Shift**: < 0.1

### Funcionalidad
- **Uptime**: 99.9%
- **Error Rate**: < 0.1%  
- **API Response Time**: < 500ms
- **Database Query Time**: < 100ms

---

*Guía de Implementación - Agencia VASIR v1.0*
