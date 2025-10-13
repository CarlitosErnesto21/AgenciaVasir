# 🌐 DIAGRAMA DE DESPLIEGUE COMPLETO - AGENCIA VASIR

## 🏗️ DIAGRAMA DE DESPLIEGUE (DEPLOYMENT DIAGRAM)

```mermaid
graph TB
    %% CAPA DE USUARIO
    subgraph "👥 USUARIOS / USERS"
        USER1[📱 Cliente Móvil<br/>Mobile Client]
        USER2[💻 Cliente Escritorio<br/>Desktop Client]
        USER3[👨‍💼 Administrador<br/>Administrator]
    end

    %% CAPA DE RED/INTERNET
    subgraph "🌐 INTERNET / RED"
        CDN[🚀 CDN de Render<br/>Render CDN]
        SSL[🔒 Certificado SSL<br/>SSL Certificate]
        DNS[🌍 Resolución DNS<br/>DNS Resolution]
    end

    %% CAPA DE APLICACIÓN (RENDER)
    subgraph "☁️ PLATAFORMA RENDER CLOUD"
        subgraph "🐳 Contenedor Docker - Servidor Web"
            APACHE[🌐 Apache 2.4<br/>Servidor HTTP]
            PHP[🐘 PHP 8.2-FPM<br/>Motor PHP]
            LARAVEL[⚡ Aplicación Laravel 12<br/>Laravel App]
        end
        
        subgraph "📁 Sistema de Archivos"
            STORAGE[💾 Almacenamiento Persistente<br/>Persistent Storage]
            SYMLINK[🔗 Enlaces Simbólicos<br/>Storage Symlinks]
            LOGS[📋 Registros de Aplicación<br/>Application Logs]
        end
        
        subgraph "🔧 Servicios del Sistema"
            CRON[⏰ Programador Laravel<br/>Laravel Scheduler]
            QUEUE[📬 Procesador de Colas<br/>Queue Worker]
            CACHE[🗄️ Caché de Archivos<br/>File Cache]
        end
    end

    %% CAPA DE BASE DE DATOS (RAILWAY)
    subgraph "🚄 NUBE RAILWAY CLOUD"
        subgraph "🗄️ Clúster de Base de Datos"
            MYSQL[💾 MySQL 8.0 Principal<br/>MySQL Primary]
            BACKUP[💿 Respaldos Automáticos<br/>Automated Backups]
            REPLICA[🔄 Réplica de Lectura<br/>Read Replica]
        end
        
        subgraph "🔒 Seguridad / Security"
            FIREWALL[🛡️ Firewall de BD<br/>Database Firewall]
            ENCRYPT[🔐 Cifrado de Datos<br/>Data Encryption]
        end
    end

    %% CAPA DE MONITOREO
    subgraph "📊 MONITOREO Y ANALÍTICAS"
        RENDER_LOGS[📈 Métricas de Render<br/>Render Metrics]
        RAILWAY_METRICS[📊 Analíticas Railway<br/>Railway Analytics]
        UPTIME[⏱️ Monitoreo de Tiempo<br/>Uptime Monitoring]
    end

    %% CONEXIONES CON PROTOCOLOS Y PUERTOS
    USER1 -.->|HTTPS:443| DNS
    USER2 -.->|HTTPS:443| DNS
    USER3 -.->|HTTPS:443| DNS
    
    DNS -->|SSL/TLS| SSL
    SSL -->|HTTPS:443| CDN
    CDN -->|HTTP:80| APACHE
    
    APACHE -->|FastCGI| PHP
    PHP -->|Internal| LARAVEL
    
    LARAVEL -.->|TCP:3306| MYSQL
    LARAVEL -->|File I/O| STORAGE
    LARAVEL -->|Process| QUEUE
    LARAVEL -->|Read/Write| CACHE
    
    MYSQL -->|Replication| REPLICA
    MYSQL -->|Scheduled| BACKUP
    FIREWALL -->|Filter| MYSQL
    
    APACHE -->|Logs| RENDER_LOGS
    MYSQL -->|Metrics| RAILWAY_METRICS
    
    %% CONFIGURACIÓN DE ESTILOS
    classDef user fill:#e3f2fd,stroke:#1565c0,stroke-width:2px
    classDef internet fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px
    classDef render fill:#e8f5e8,stroke:#2e7d32,stroke-width:2px
    classDef database fill:#fff3e0,stroke:#ef6c00,stroke-width:2px
    classDef monitoring fill:#fce4ec,stroke:#c2185b,stroke-width:2px
    classDef security fill:#f1f8e9,stroke:#558b2f,stroke-width:2px

    class USER1,USER2,USER3 user
    class CDN,SSL,DNS internet
    class APACHE,PHP,LARAVEL,STORAGE,SYMLINK,LOGS,CRON,QUEUE,CACHE render
    class MYSQL,BACKUP,REPLICA database
    class RENDER_LOGS,RAILWAY_METRICS,UPTIME monitoring
    class FIREWALL,ENCRYPT security
```

---

## 🏢 ESPECIFICACIONES TÉCNICAS DE DESPLIEGUE

### **☁️ NIVEL 1: FRONTEND Y SERVIDOR WEB (RENDER)**

| Componente | Especificación | Puerto | Protocolo |
|------------|----------------|--------|-----------|
| **Servidor Apache HTTP** | 2.4.x | 80, 443 | HTTP/HTTPS |
| **PHP-FPM** | 8.2 | 9000 | FastCGI |
| **Aplicación Laravel** | 12.x | Interno | PHP |
| **Recursos Estáticos** | Bundle Vite | 80 | HTTP |
| **Enlaces de Almacenamiento** | /storage → /app/public | Sistema de Archivos | - |

**Configuración del Servidor:**
```apache
# Configuración de Virtual Host de Apache
<VirtualHost *:80>
    ServerName vasir-agency-app.onrender.com
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Redireccionar HTTP a HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HOST}%1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName vasir-agency-app.onrender.com
    DocumentRoot /var/www/html/public
    
    # Configuración SSL (Administrado por Render)
    SSLEngine on
    
    # Reescritura de URLs de Laravel
    <Directory /var/www/html/public>
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [L]
    </Directory>
</VirtualHost>
```

### **🗄️ NIVEL 2: CAPA DE BASE DE DATOS (RAILWAY)**

| Componente | Especificación | Puerto | Protocolo |
|------------|----------------|--------|-----------|
| **MySQL Principal** | 8.0.x | 3306 | TCP/MySQL |
| **Pool de Conexiones** | Máx 100 conexiones | 3306 | TCP |
| **Cifrado SSL** | TLS 1.2+ | 3306 | TCP Cifrado |
| **Sistema de Respaldos** | Diario Automático | - | - |

**Configuración de Conexión:**
```env
# Configuración de Conexión a Base de Datos
DB_CONNECTION=mysql
DB_HOST=roundhouse.proxy.rlwy.net
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=[CIFRADO]

# Configuración del Pool de Conexiones
DB_POOL_MIN=5
DB_POOL_MAX=20
DB_TIMEOUT=30
```

### **📁 NIVEL 3: CAPA DE ALMACENAMIENTO (DISCO PERSISTENTE RENDER)**

| Directorio | Propósito | Permisos | Respaldo |
|------------|-----------|----------|----------|
| `/storage/app/public/tours/` | Imágenes de tours | 775 | Incluido |
| `/storage/app/public/productos/` | Imágenes de productos | 775 | Incluido |
| `/storage/app/public/hoteles/` | Imágenes de hoteles | 775 | Incluido |
| `/storage/app/public/aerolinea/` | Imágenes de aerolíneas | 775 | Incluido |
| `/storage/logs/` | Registros de aplicación | 755 | Excluido |
| `/storage/framework/cache/` | Caché de aplicación | 755 | Excluido |

---

## 🔄 FLUJO DE DESPLIEGUE DETALLADO

### **PROCESO DE DESPLIEGUE AUTOMÁTICO / AUTOMATIC DEPLOYMENT**

```mermaid
sequenceDiagram
    participant DEV as 👨‍💻 Desarrollador<br/>Developer
    participant GIT as 📚 GitHub<br/>Repositorio
    participant RENDER as ☁️ Render<br/>Servidor
    participant RAILWAY as 🚄 Railway<br/>Base de Datos
    participant USERS as 👥 Usuarios<br/>Users

    Note over DEV,USERS: 🚀 Proceso de Despliegue Automático

    DEV->>GIT: 1. git push origin main<br/>(Subir código)
    GIT->>RENDER: 2. Webhook trigger<br/>(Activar despliegue)
    
    Note over RENDER: 🐳 Proceso de Construcción
    RENDER->>RENDER: 3. Docker build<br/>(Crear contenedor)
    RENDER->>RENDER: 4. composer install<br/>(Instalar dependencias PHP)
    RENDER->>RENDER: 5. npm run build<br/>(Compilar assets)
    
    Note over RENDER: ⚙️ Proceso de Despliegue
    RENDER->>RAILWAY: 6. Test DB connection<br/>(Probar conexión BD)
    RAILWAY-->>RENDER: 7. Connection OK<br/>(Conexión exitosa)
    
    RENDER->>RENDER: 8. php artisan migrate<br/>(Ejecutar migraciones)
    RENDER->>RENDER: 9. php artisan config:cache<br/>(Cachear configuración)
    RENDER->>RENDER: 10. setup-storage.php<br/>(Configurar almacenamiento)
    
    Note over RENDER: 🌐 Puesta en Marcha
    RENDER->>RENDER: 11. Apache restart<br/>(Reiniciar servidor)
    RENDER->>USERS: 12. Service available<br/>(Servicio disponible)
    
    Note over USERS: ✅ Despliegue Sin Tiempo de Inactividad
```

### **CONFIGURACIÓN DE VARIABLES DE ENTORNO**

```bash
# Variables de Entorno de Producción
APP_NAME=VASIR
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vasir-agency-app.onrender.com

# Configuración de Base de Datos
DB_CONNECTION=mysql
DB_HOST=roundhouse.proxy.rlwy.net
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=[GENERADO_POR_RAILWAY]

# Configuración de Caché y Sesiones
CACHE_DRIVER=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Configuración de Almacenamiento
FILESYSTEM_DISK=public

# Seguridad
APP_KEY=[GENERADO_POR_LARAVEL]
BCRYPT_ROUNDS=12

# Configuración de Correo (Futuro)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
```

---

## 🛡️ SEGURIDAD Y MONITOREO

### **MEDIDAS DE SEGURIDAD IMPLEMENTADAS**

```yaml
Seguridad de Red:
  - HTTPS obligatorio (SSL/TLS 1.2+)
  - Cabeceras de seguridad (HSTS, CSP)
  - Limitación de velocidad en endpoints API
  
Seguridad de Base de Datos:
  - Conexiones cifradas (SSL)
  - Firewall de Railway activo
  - Credenciales en variables de entorno
  
Seguridad de Aplicación:
  - Protección CSRF de Laravel
  - Autenticación Sanctum
  - Validación y sanitización de entradas
  - Sistema de permisos Spatie
```

### **MONITOREO Y ALERTAS**

```yaml
Monitoreo de Render:
  - Alertas de uso de CPU (>80%)
  - Alertas de uso de memoria (>80%)
  - Monitoreo de tiempo de respuesta
  - Seguimiento de tasa de errores

Monitoreo de Railway:
  - Monitoreo de conexiones de BD
  - Análisis de rendimiento de consultas
  - Seguimiento de uso de almacenamiento
  - Verificación de respaldos

Monitoreo de Aplicación:
  - Registro de errores de Laravel
  - Métricas de rendimiento
  - Seguimiento de actividad de usuarios
  - Verificaciones de salud del sistema
```

---

## 📈 ESCALABILIDAD Y PERFORMANCE

### **CONFIGURACIÓN ACTUAL**

| Recurso | Especificación | Límite | Escalabilidad |
|---------|----------------|--------|---------------|
| **CPU** | Shared vCPU | 1 core | Vertical scaling |
| **RAM** | 512MB | 1GB burst | Vertical scaling |
| **Storage** | 1GB persistent | 10GB max | Configurable |
| **Bandwidth** | 100GB/mes | 1TB available | Pay-as-grow |
| **Database** | Railway MySQL | 1GB storage | Horizontal scaling |

### **PLAN DE ESCALAMIENTO**

```mermaid
graph LR
    subgraph "🏃‍♂️ Actual (Inicial)"
        C1[512MB RAM]
        C2[1 vCPU]
        C3[1GB Almacenamiento]
    end
    
    subgraph "🚀 Crecimiento (Pro)"
        G1[2GB RAM]
        G2[2 vCPU]
        G3[10GB Almacenamiento]
    end
    
    subgraph "🏢 Escala (Empresarial)"
        E1[8GB RAM]
        E2[4 vCPU]
        E3[100GB Almacenamiento]
        E4[Balanceador de Carga]
        E5[Múltiples Instancias]
    end
    
    C1 --> G1
    G1 --> E1
    E4 --> E5
```

---

## 🔧 CONFIGURACIÓN DE PRODUCCIÓN

### **Docker Container Specifications**

```dockerfile
# Detalles del Contenedor de Producción
FROM php:8.2-apache

# Recursos del Sistema
ENV APACHE_MEMORY_LIMIT=256M
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_MAX_EXECUTION_TIME=300
ENV PHP_UPLOAD_MAX_FILESIZE=10M

# Configuración de Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV APACHE_SERVER_NAME=vasir-agency-app.onrender.com

# Optimizaciones de Laravel
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_LEVEL=error
```

### **Optimizaciones de Rendimiento**

```php
// config/app.php - Optimizaciones de producción
'providers' => [
    // Solo proveedores de servicios esenciales en producción
    App\Providers\AppServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    // Removidos: proveedores de debug innecesarios
],

// config/cache.php
'default' => 'file', // Caché rápido basado en archivos

// config/session.php  
'driver' => 'database', // Sesiones persistentes

// config/queue.php
'default' => 'database', // Procesamiento simple de colas
```

---

## ✅ CHECKLIST DE DESPLIEGUE

### **Pre-Deployment**
- [ ] ✅ Environment variables configuradas
- [ ] ✅ Database migrations tested
- [ ] ✅ Storage directories created
- [ ] ✅ SSL certificate verified
- [ ] ✅ DNS configuration confirmed

### **During Deployment**
- [ ] ✅ Zero-downtime deployment
- [ ] ✅ Database connectivity verified
- [ ] ✅ Storage symlinks created
- [ ] ✅ Cache cleared and rebuilt
- [ ] ✅ Application optimizations applied

### **Post-Deployment**
- [ ] ✅ Health check endpoints responding
- [ ] ✅ All features functional testing
- [ ] ✅ Performance metrics baseline
- [ ] ✅ Error monitoring active
- [ ] ✅ Backup systems verified

---

*Diagrama de Despliegue Completo - Agencia VASIR v1.0*  
*Actualizado: 12 octubre 2025*
