# 🚀 Guía de Despliegue a Producción - Sistema de Reservas Wompi

## 1. 📋 Pre-requisitos del Servidor

### Requisitos Mínimos:
- **PHP**: 8.1 o superior
- **MySQL/PostgreSQL**: 8.0+ / 13+
- **Composer**: Última versión
- **Node.js**: 18+ (para assets)
- **Supervisor** (recomendado para queue)
- **Nginx/Apache** con SSL habilitado

### Variables de Entorno Críticas:
```bash
# .env de producción
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=tu_bd_produccion
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password_seguro

# Wompi Configuración
WOMPI_PUBLIC_KEY=tu_public_key_produccion
WOMPI_PRIVATE_KEY=tu_private_key_produccion
WOMPI_EVENTS_SECRET=tu_events_secret_produccion
WOMPI_ENVIRONMENT=production
WOMPI_BASE_URL=https://production.wompi.co

# URLs importantes
WOMPI_WEBHOOK_URL=https://tu-dominio.com/api/webhooks/wompi
WOMPI_REDIRECT_URL=https://tu-dominio.com/payment/success
```

## 2. 🔧 Configuración en Wompi Dashboard

### Paso 1: Configurar Webhook
1. Ir a: https://comercios.wompi.co/
2. Navegar a: **Configuración** → **Webhooks**
3. Agregar nuevo webhook:
   - **URL**: `https://tu-dominio.com/api/webhooks/wompi`
   - **Eventos**: Marcar todos los relacionados con pagos
   - **Método**: POST
   - **Formato**: JSON

### Paso 2: Obtener Credenciales de Producción
1. En dashboard de Wompi → **Configuración** → **Credenciales**
2. Copiar:
   - Public Key (producción)
   - Private Key (producción) 
   - Events Secret (para webhook)

### Paso 3: Configurar URLs de Redirección
1. **URL de éxito**: `https://tu-dominio.com/payment/success`
2. **URL de error**: `https://tu-dominio.com/payment/error`
3. **URL de webhook**: `https://tu-dominio.com/api/webhooks/wompi`

## 3. 🏗️ Despliegue del Código

### Paso 1: Clonar y Configurar
```bash
# En tu servidor
cd /var/www/html
git clone https://github.com/CarlitosErnesto21/AgenciaVasir.git
cd AgenciaVasir

# Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Paso 2: Configuración del Entorno
```bash
# Copiar archivo de entorno
cp .env.example .env

# Editar variables (usar las de arriba)
nano .env

# Generar clave de aplicación
php artisan key:generate

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Paso 3: Base de Datos
```bash
# Ejecutar migraciones
php artisan migrate --force

# Ejecutar seeders si es necesario
php artisan db:seed --class=ProductoSeeder
```

## 4. ⏰ Configurar Cron Jobs (CRÍTICO)

### En el servidor (crontab):
```bash
# Editar crontab del usuario web
sudo crontab -u www-data -e

# Agregar esta línea:
* * * * * cd /var/www/html/AgenciaVasir && php artisan schedule:run >> /dev/null 2>&1

# Verificar que se guardó
sudo crontab -u www-data -l
```

### Verificar que funciona:
```bash
# Listar tareas programadas
php artisan schedule:list

# Ejecutar manualmente una vez
php artisan schedule:run
```

## 5. 🔄 Configurar Queue Worker (Recomendado)

### Instalar Supervisor:
```bash
sudo apt update
sudo apt install supervisor
```

### Crear configuración:
```bash
# Crear archivo de configuración
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/AgenciaVasir/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/AgenciaVasir/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Recargar supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## 6. 🌐 Configurar Servidor Web

### Para Nginx:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tu-dominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name tu-dominio.com;
    root /var/www/html/AgenciaVasir/public;

    # SSL configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    # Increase max body size for file uploads
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 7. 🔒 Seguridad

### SSL Certificate:
```bash
# Usando Let's Encrypt (recomendado)
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d tu-dominio.com
```

### Firewall:
```bash
# Configurar UFW
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp  
sudo ufw allow 443/tcp
sudo ufw enable
```

### Permisos de Archivos:
```bash
# Asegurar permisos correctos
sudo chown -R www-data:www-data /var/www/html/AgenciaVasir
sudo find /var/www/html/AgenciaVasir -type f -exec chmod 644 {} \;
sudo find /var/www/html/AgenciaVasir -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/html/AgenciaVasir/storage
sudo chmod -R 775 /var/www/html/AgenciaVasir/bootstrap/cache
```

## 8. 📊 Monitoreo y Logs

### Configurar Log Rotation:
```bash
# Crear configuración de logrotate
sudo nano /etc/logrotate.d/laravel
```

```bash
/var/www/html/AgenciaVasir/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    postrotate
        /usr/bin/supervisorctl restart laravel-worker:* > /dev/null 2>&1 || true
    endscript
}
```

### Comandos de Monitoreo:
```bash
# Ver logs en tiempo real
tail -f /var/www/html/AgenciaVasir/storage/logs/laravel.log

# Ver logs específicos de reservas
grep "Reserva" /var/www/html/AgenciaVasir/storage/logs/laravel.log

# Ver estadísticas del sistema
php artisan tinker --execute="echo json_encode(App\Models\StockReservation::obtenerEstadisticas(), JSON_PRETTY_PRINT);"
```

## 9. 🧪 Pruebas Post-Despliegue

### Checklist de Verificación:
```bash
# 1. Verificar que el sitio carga
curl -I https://tu-dominio.com

# 2. Probar webhook endpoint
curl -X POST https://tu-dominio.com/api/webhooks/wompi \
  -H "Content-Type: application/json" \
  -d '{"test": true}'

# 3. Verificar cron jobs
php artisan schedule:list

# 4. Verificar sistema de reservas
php artisan tinker --execute="App\Models\StockReservation::obtenerEstadisticas()"

# 5. Crear venta de prueba (desde frontend)
# Ir a: https://tu-dominio.com
```

## 10. 🚨 Backup y Mantenimiento

### Backup Automático:
```bash
# Crear script de backup
sudo nano /usr/local/bin/laravel-backup.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
APP_PATH="/var/www/html/AgenciaVasir"
BACKUP_PATH="/backup/laravel"

# Crear directorio si no existe
mkdir -p $BACKUP_PATH

# Backup de la base de datos
mysqldump -u usuario -ppassword nombre_bd > $BACKUP_PATH/db_backup_$DATE.sql

# Backup de archivos de storage
tar -czf $BACKUP_PATH/storage_backup_$DATE.tar.gz -C $APP_PATH storage

# Limpiar backups antiguos (mantener 7 días)
find $BACKUP_PATH -name "*.sql" -mtime +7 -delete
find $BACKUP_PATH -name "*.tar.gz" -mtime +7 -delete
```

```bash
# Hacer ejecutable y agregar al cron
sudo chmod +x /usr/local/bin/laravel-backup.sh
sudo crontab -e

# Agregar: backup diario a las 2 AM
0 2 * * * /usr/local/bin/laravel-backup.sh
```

## 11. 📞 Contactos de Emergencia

### URLs Importantes:
- **Dashboard Wompi**: https://comercios.wompi.co/
- **Documentación API**: https://docs.wompi.co/
- **Estado del servicio**: https://status.wompi.co/

### Comandos de Emergencia:
```bash
# Reiniciar workers si hay problemas
sudo supervisorctl restart laravel-worker:*

# Limpiar cache si hay problemas
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ver logs de errores recientes
tail -100 /var/www/html/AgenciaVasir/storage/logs/laravel.log | grep ERROR
```

¡Tu sistema estará listo para producción siguiendo esta guía! 🚀
