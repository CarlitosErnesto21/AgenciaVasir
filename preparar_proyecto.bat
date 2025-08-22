@echo off
setlocal enabledelayedexpansion

REM Script para preparar el proyecto antes de configurar backups
REM Ejecutar cuando el diagnostico dice que el backup no funciona

color 0B
title Preparar Proyecto VASIR
echo.
echo ===============================================
echo    PREPARAR PROYECTO PARA BACKUPS
echo ===============================================
echo.
echo Este script va a instalar dependencias y
echo configurar el proyecto para que funcionen
echo los backups.
echo.
echo Presiona ENTER para comenzar...
pause >nul
cls

echo.
echo 📦 PASO 1: Instalando dependencias...
echo    (Esto puede tardar varios minutos)
echo.

REM Verificar si composer existe
composer --version >nul 2>&1
if %errorLevel% neq 0 (
    color 0C
    echo ❌ COMPOSER NO ESTA INSTALADO
    echo.
    echo NECESITAS INSTALAR COMPOSER:
    echo 1. Ve a: https://getcomposer.org/download/
    echo 2. Descarga e instala Composer
    echo 3. Reinicia la computadora
    echo 4. Vuelve a ejecutar este script
    echo.
    goto :final
)

echo ✅ Composer encontrado
echo.

REM Ejecutar composer install
echo 🔄 Ejecutando composer install...
composer install
if %errorLevel% neq 0 (
    color 0C
    echo.
    echo ❌ ERROR EN COMPOSER INSTALL
    echo.
    echo POSIBLES SOLUCIONES:
    echo 1. Verificar conexión a internet
    echo 2. Ejecutar: composer update
    echo 3. Pedir ayuda en el grupo
    echo.
    goto :final
)

echo ✅ Dependencias instaladas correctamente
echo.

echo 📄 PASO 2: Verificando archivo .env...
echo.

if not exist ".env" (
    if exist ".env.example" (
        echo 🔄 Creando archivo .env desde .env.example...
        copy ".env.example" ".env" >nul
        echo ✅ Archivo .env creado
    ) else (
        color 0E
        echo ⚠️ NO EXISTE .env NI .env.example
        echo.
        echo NECESITAS:
        echo 1. Crear archivo .env
        echo 2. Configurar base de datos
        echo 3. Pedir ayuda en el grupo
        echo.
        goto :final
    )
) else (
    echo ✅ Archivo .env existe
)

echo.
echo 🔑 PASO 3: Generando clave de aplicación...
echo.

php artisan key:generate --force
if %errorLevel% neq 0 (
    color 0C
    echo ❌ ERROR GENERANDO CLAVE
    goto :final
)

echo ✅ Clave generada correctamente
echo.

echo 🗃️ PASO 4: Ejecutando migraciones...
echo.

php artisan migrate --force
if %errorLevel% neq 0 (
    color 0E
    echo ⚠️ ERROR EN MIGRACIONES
    echo.
    echo POSIBLES CAUSAS:
    echo 1. Base de datos no configurada en .env
    echo 2. Base de datos no existe
    echo 3. Problemas de conexión
    echo.
    echo QUE HACER:
    echo 1. Verificar configuración en .env
    echo 2. Crear la base de datos
    echo 3. Volver a ejecutar este script
    echo.
    goto :final
)

echo ✅ Migraciones ejecutadas correctamente
echo.

echo 🧪 PASO 5: Probando backup nuevamente...
echo.

php artisan backup:auto >nul 2>&1
if %errorLevel% neq 0 (
    color 0C
    echo ❌ BACKUP AÚN NO FUNCIONA
    echo.
    echo NECESITAS AYUDA MANUAL:
    echo 1. Tomar captura de este error
    echo 2. Enviar al grupo
    echo 3. Mencionar que ya ejecutaste este script
    echo.
    echo Vamos a ver el error exacto:
    echo.
    php artisan backup:auto
    goto :final
)

color 0A
echo.
echo ✅✅✅ ¡PROYECTO PREPARADO CORRECTAMENTE! ✅✅✅
echo.
echo 🎉 El sistema de backup ya funciona
echo.
echo SIGUIENTE PASO:
echo 1. Ejecutar: configurar_backup_automatico.bat
echo 2. Como administrador
echo 3. Seguir las instrucciones
echo.

:final
echo.
echo Presiona ENTER para cerrar...
pause >nul
