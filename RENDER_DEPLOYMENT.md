# 🚀 Variables de Entorno para RENDER (vasir.onrender.com)

## Variables Críticas para Configurar en Render Dashboard

### 🔧 Configuración Básica
```
APP_NAME=VASIR
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vasir.onrender.com
APP_KEY=base64:PGVBh/ldiBbrj13hIdqJ8EVeLM1ntzNxJIZ7b9R3uMg=
```

### 🗄️ Base de Datos (Render PostgreSQL)
```
DB_CONNECTION=pgsql
DB_HOST=[TU_HOST_POSTGRESQL_RENDER]
DB_PORT=5432
DB_DATABASE=[TU_DATABASE_NAME]
DB_USERNAME=[TU_USERNAME]
DB_PASSWORD=[TU_PASSWORD_SEGURO]
```

### 💳 Wompi El Salvador - Producción
```
WOMPI_CLIENT_ID=[TU_CLIENT_ID_PRODUCCION]
WOMPI_CLIENT_SECRET=[TU_CLIENT_SECRET_PRODUCCION]
WOMPI_SANDBOX=false
WOMPI_BASE_URL=https://api.wompi.sv
WOMPI_AUTH_URL=https://id.wompi.sv/connect/token
WOMPI_AUDIENCE=wompi_api

# URLs para Render
WOMPI_WEBHOOK_URL=https://vasir.onrender.com/api/webhooks/wompi
WOMPI_SUCCESS_URL=https://vasir.onrender.com/payment/success
WOMPI_ERROR_URL=https://vasir.onrender.com/payment/error
WOMPI_PENDING_URL=https://vasir.onrender.com/payment/pending

# Events Secret (obtener de Wompi Dashboard)
WOMPI_EVENTS_SECRET=[TU_EVENTS_SECRET_PRODUCCION]
```

### 📧 Email (Mantener tu Gmail)
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=vasirtours2025@gmail.com
MAIL_PASSWORD=ppwkrfoswbialxlt
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=vasirtours2025@gmail.com
MAIL_FROM_NAME=VASIR
```

### 🔒 Sesiones y Cache
```
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### 👤 Admin Inicial
```
ADMIN_NAME=Carlos Ernesto Arteaga Rosales
ADMIN_EMAIL=ernesto.rosales354@gmail.com
ADMIN_PASSWORD=[CAMBIAR_PASSWORD_SEGURO_PRODUCCION]
```

## 📋 Pasos para Configurar en Render:

### 1. **En Render Dashboard:**
- Ve a tu servicio → **Environment**
- Agrega cada variable una por una
- **IMPORTANTE**: No usar comillas en los valores

### 2. **En Wompi Dashboard:**
- Configurar webhook: `https://vasir.onrender.com/api/webhooks/wompi`
- Obtener credenciales de producción
- Copiar el Events Secret

### 3. **Configuración de Build en Render:**
```bash
# Build Command
npm install && npm run build && composer install --no-dev --optimize-autoloader

# Start Command  
php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=$PORT
```

### 4. **Variables Específicas de Render:**
```
# Auto-generadas por Render
PORT=10000
RENDER=true

# Sanctum para dominio de producción
SANCTUM_STATEFUL_DOMAINS=vasir.onrender.com
```

## ⚠️ **IMPORTANTE - Diferencias Desarrollo vs Producción:**

| Variable | Desarrollo | Producción Render |
|----------|------------|-------------------|
| `APP_ENV` | local | production |
| `APP_DEBUG` | true | false |
| `APP_URL` | http://192.168.1.5:8080 | https://vasir.onrender.com |
| `DB_CONNECTION` | mysql | pgsql |
| `WOMPI_SANDBOX` | true | false |
| `WOMPI_WEBHOOK_URL` | http://192.168... | https://vasir.onrender.com... |

## 🔧 **Script de Deploy Automático:**

Crear archivo `render-deploy.sh`:
```bash
#!/bin/bash
echo "🚀 Iniciando deploy en Render..."

# Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Ejecutar migraciones
php artisan migrate --force

# Optimizar para producción
php artisan config:cache
php artisan route:cache  
php artisan view:cache

# Limpiar logs antiguos
php artisan log:clear

echo "✅ Deploy completado!"
```

## 📞 **Contactos para Soporte:**
- **Render Support**: https://render.com/docs
- **Wompi El Salvador**: https://wompi.sv/
- **Laravel on Render**: https://render.com/docs/deploy-laravel
