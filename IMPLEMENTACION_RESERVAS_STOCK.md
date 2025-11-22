# 🛒 GUÍA DE IMPLEMENTACIÓN: Sistema de Reservas de Stock con Wompi

## 📋 Resumen de Cambios

Hemos implementado un **Sistema de Reservas Temporales de Stock** que resuelve el problema de reducir el stock antes de confirmar el pago. Ahora el flujo es:

### ✅ Flujo Anterior (Problemático):
1. Usuario agrega productos al carrito
2. **Sistema crea venta "completada" y reduce stock inmediatamente** ❌
3. Se genera enlace de Wompi
4. Usuario puede o no pagar (stock ya reducido)

### ✅ Nuevo Flujo (Correcto):
1. Usuario agrega productos al carrito
2. **Sistema crea venta "pendiente" SIN reducir stock** ✅
3. **Se crean reservas temporales de stock (30 min)** 🔒
4. Se genera enlace de Wompi
5. **Wompi webhook confirma o rechaza el pago**:
   - ✅ **Pago aprobado**: Confirmar reservas + reducir stock + venta "completada"
   - ❌ **Pago rechazado**: Cancelar reservas + liberar stock + venta "cancelada"
6. **Limpieza automática**: Reservas expiradas se limpian cada 15 min

---

## 🚀 Pasos para Aplicar los Cambios

### 1. **Ejecutar Migraciones**
```bash
# Crear tabla de reservas de stock
php artisan migrate

# Verificar que las tablas se crearon correctamente
php artisan migrate:status
```

### 2. **Verificar Configuración del Scheduler**
Asegurate que el Laravel Scheduler esté configurado en tu cron:
```bash
# Agregar a crontab (crontab -e)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### 3. **Comandos Disponibles**
```bash
# Limpiar reservas expiradas manualmente
php artisan reservations:clean-expired

# Ver qué se limpiará sin ejecutar (dry-run)
php artisan reservations:clean-expired --dry-run

# Forzar limpieza sin confirmación
php artisan reservations:clean-expired --force
```

---

## 📊 Nuevas Características

### 🔒 **Modelo StockReservation**
- **Estados**: `activa`, `confirmada`, `expirada`, `cancelada`
- **Expiración**: 30 minutos por defecto
- **Gestión automática**: Limpieza cada 15 minutos

### 🛍️ **Estados de Venta Actualizados**
- **`pendiente`**: Venta creada, esperando confirmación de pago
- **`completada`**: Pago confirmado, stock reducido
- **`cancelada`**: Pago fallido o rechazado

### 🎯 **Webhook Mejorado**
- **Pago aprobado (`approved`)**:
  - Confirma reservas
  - Reduce stock real
  - Cambia venta a "completada"
  - Crea movimientos de inventario
- **Pago rechazado (`declined/failed/error/voided`)**:
  - Cancela reservas
  - Libera stock reservado
  - Cambia venta a "cancelada"

---

## 🔧 Métodos Útiles del Modelo StockReservation

```php
// Crear reservas para un carrito
$reservas = StockReservation::crearReservasParaCarrito($productos, $referencia, 30);

// Confirmar reservas por referencia de Wompi
$confirmadas = StockReservation::confirmarReservasPorReferencia($referencia);

// Cancelar reservas por referencia
$canceladas = StockReservation::cancelarReservasPorReferencia($referencia, 'Motivo');

// Calcular stock disponible (considerando reservas)
$disponible = StockReservation::calcularStockDisponible($productoId);

// Obtener estadísticas
$stats = StockReservation::obtenerEstadisticas();

// Limpiar reservas expiradas
$expiradas = StockReservation::limpiarReservasExpiradas();
```

---

## 🚨 Puntos Importantes

### ✅ **Ventajas del Nuevo Sistema**:
1. **Stock preciso**: No se reduce hasta confirmar el pago
2. **Prevención de sobreventa**: Las reservas evitan vender stock no disponible
3. **Limpieza automática**: Las reservas expiradas se eliminan automáticamente
4. **Trazabilidad completa**: Logs detallados de todo el proceso

### ⚠️ **Consideraciones**:
1. **Reservas temporales**: Los productos se "reservan" por 30 minutos
2. **Scheduler requerido**: Necesita cron configurado para limpieza automática
3. **Webhook crítico**: El webhook de Wompi es esencial para el funcionamiento

### 🔍 **Monitoreo**:
- Logs en `storage/logs/laravel.log`
- Logs del scheduler en `storage/logs/scheduled-stock-reservations-cleanup.log`
- Estadísticas disponibles via `StockReservation::obtenerEstadisticas()`

---

## 🧪 Testing del Sistema

### 1. **Probar Flujo Completo**:
```bash
# 1. Agregar productos al carrito (frontend)
# 2. Crear enlace de pago
# 3. Verificar que se crearon reservas:
php artisan tinker
>>> StockReservation::obtenerEstadisticas()

# 4. Simular webhook aprobado
php artisan test:webhook-manual REFERENCIA_WOMPI

# 5. Verificar que se confirmaron reservas y redujo stock
```

### 2. **Probar Expiración**:
```bash
# Forzar expiración de reservas
php artisan reservations:clean-expired --force

# Ver estadísticas después
php artisan tinker
>>> StockReservation::obtenerEstadisticas()
```

### 3. **Verificar Webhook**:
- URL del webhook: `https://tu-dominio.com/api/wompi/webhook`
- Configurar en el dashboard de Wompi
- Probar con pagos reales en sandbox

---

## 📱 Actualización del Frontend

El frontend ya maneja correctamente el nuevo flujo. Solo considera:

1. **Mensaje actualizado**: Ahora muestra que la venta es "pendiente"
2. **Estados de venta**: El frontend debe manejar el estado `pendiente`
3. **Carrito**: Se limpia después de crear el enlace (como antes)

---

## 🆘 Solución de Problemas

### **Reservas no se crean**:
- Verificar que existe stock disponible
- Revisar logs en `laravel.log`
- Comprobar que el producto existe

### **Reservas no se confirman**:
- Verificar que el webhook está funcionando
- Revisar configuración de Wompi
- Comprobar logs del webhook

### **Stock inconsistente**:
```bash
# Obtener estadísticas
php artisan tinker
>>> StockReservation::obtenerEstadisticas()

# Limpiar reservas expiradas
php artisan reservations:clean-expired --force
```

### **Scheduler no funciona**:
```bash
# Verificar que está en crontab
crontab -l

# Ejecutar manualmente
php artisan schedule:run

# Ver próximos trabajos
php artisan schedule:list
```

---

## ✨ Resultado Final

¡Ahora tu sistema de e-commerce tiene un control de stock robusto y preciso!

- ✅ **Stock se reduce solo cuando se confirma el pago**
- ✅ **No más sobreventa por pagos fallidos**
- ✅ **Limpieza automática de reservas expiradas**
- ✅ **Trazabilidad completa del proceso**
- ✅ **Compatibilidad total con Wompi webhooks**
