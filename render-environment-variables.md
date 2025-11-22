# Variables de Entorno para Render.com - VASIR

## 📋 Configuración de Variables de Entorno

Estas son todas las variables de entorno requeridas para el despliegue de VASIR en Render.com:

### 🚀 Configuración de la Aplicación
| Key | Value |
|-----|-------|
| `APP_NAME` | `VASIR` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://vasir.onrender.com` |
| `APP_KEY` | `base64:PGVBh/ldiBbrj13hIdqJ8EVeLM1ntzNxJIZ7b9R3uMg=` |
| `APP_LOCALE` | `es` |
| `APP_FALLBACK_LOCALE` | `es` |
| `APP_FAKER_LOCALE` | `es_ES` |
| `BCRYPT_ROUNDS` | `10` |

### 🗄️ Configuración de Base de Datos (Railway MySQL)
| Key | Value |
|-----|-------|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `caboose.proxy.rlwy.net` |
| `DB_PORT` | `18882` |
| `DB_DATABASE` | `railway` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | `kkOYbrIPcsfEAXqLxfacRgNzEkUVUsrW` |
| `DB_CHARSET` | `utf8mb4` |
| `DB_COLLATION` | `utf8mb4_unicode_ci` |
| `DB_FOREIGN_KEYS` | `true` |

### 📧 Configuración de Email (Gmail SMTP)
| Key | Value |
|-----|-------|
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `smtp.gmail.com` |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | `vasirtours2025@gmail.com` |
| `MAIL_PASSWORD` | `cqmemoagpnrxeigx` |
| `MAIL_ENCRYPTION` | `tls` |
| `MAIL_FROM_ADDRESS` | `vasirtours2025@gmail.com` |
| `MAIL_FROM_NAME` | `VASIR` |
| `MAIL_TIMEOUT` | `120` |

### 🔐 Configuración de Administrador
| Key | Value |
|-----|-------|
| `ADMIN_NAME` | `Dalia María Hernández` |
| `ADMIN_EMAIL` | `vasirtours2025@gmail.com` |
| `ADMIN_PASSWORD` | `Admin123` |

### 💳 Configuración de Wompi El Salvador (Producción)
| Key | Value |
|-----|-------|
| `WOMPI_BASE_URL` | `https://api.wompi.sv` |
| `WOMPI_AUTH_URL` | `https://id.wompi.sv/connect/token` |
| `WOMPI_CLIENT_ID` | `26fcac1d-509e-40fe-a38c-2803b9832e40` |
| `WOMPI_CLIENT_SECRET` | `dbf1c5bb-5cfe-4d58-a616-cedc93e9c399` |
| `WOMPI_AUDIENCE` | `wompi_api` |
| `WOMPI_SANDBOX` | `false` |

### 🔄 Configuración de Cache y Sesiones
| Key | Value |
|-----|-------|
| `CACHE_STORE` | `database` |
| `SESSION_DRIVER` | `database` |
| `SESSION_LIFETIME` | `120` |
| `SESSION_ENCRYPT` | `false` |
| `SESSION_HTTP_ONLY` | `true` |
| `SESSION_SAME_SITE` | `lax` |
| `SESSION_SECURE_COOKIE` | `true` |
| `QUEUE_CONNECTION` | `database` |

### 🛡️ Configuración de Sanctum
| Key | Value |
|-----|-------|
| `SANCTUM_STATEFUL_DOMAINS` | `vasir.onrender.com` |

### 📁 Configuración de Archivos y Logs
| Key | Value |
|-----|-------|
| `FILESYSTEM_DISK` | `local` |
| `LOG_CHANNEL` | `stack` |
| `LOG_LEVEL` | `error` |

### ⚡ Configuración de PHP
| Key | Value |
|-----|-------|
| `PHP_CLI_SERVER_WORKERS` | `1` |

---

## 🚀 Instrucciones de Configuración en Render

1. **Acceder al Dashboard de Render:**
   - Ve a [https://dashboard.render.com](https://dashboard.render.com)
   - Selecciona tu servicio `vasir`

2. **Configurar Variables de Entorno:**
   - Ve a la pestaña "Environment"
   - Agrega cada variable con su correspondiente valor
   - Guarda los cambios

3. **Redeploy Automático:**
   - Render redesplegará automáticamente la aplicación
   - Verifica que el despliegue sea exitoso

## ⚠️ Notas Importantes

- **Variables Sensibles:** `DB_PASSWORD`, `WOMPI_CLIENT_SECRET`, `MAIL_PASSWORD`, `ADMIN_PASSWORD` y `APP_KEY` son confidenciales
- **Producción:** `WOMPI_SANDBOX=false` significa que usas el entorno real de Wompi El Salvador
- **SSL/HTTPS:** `SESSION_SECURE_COOKIE=true` requiere HTTPS (Render lo proporciona automáticamente)
- **Base de Datos:** La configuración apunta a Railway MySQL como base de datos externa

## 🔧 Variables Críticas para el Sistema de Pagos

Las siguientes variables son **CRÍTICAS** para el funcionamiento del sistema de pagos con Wompi:

- `WOMPI_BASE_URL`
- `WOMPI_AUTH_URL` 
- `WOMPI_CLIENT_ID`
- `WOMPI_CLIENT_SECRET`
- `WOMPI_AUDIENCE`
- `WOMPI_SANDBOX`

**¡Asegúrate de configurarlas correctamente para que los webhooks funcionen!**
