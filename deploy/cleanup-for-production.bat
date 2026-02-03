@echo off
REM ============================================
REM Script de Limpieza para Producción
REM Ejecutar ANTES de subir código a producción
REM ============================================

echo.
echo ============================================
echo    LIMPIEZA PARA PRODUCCION - REQUI MVC
echo ============================================
echo.

REM Crear directorio de backup
if not exist "backup-dev" mkdir backup-dev
echo [INFO] Directorio de backup creado: backup-dev

echo.
echo [PASO 1] Eliminando archivos de debug y desarrollo...
echo ----------------------------------------

REM Archivos de debug principales
if exist "analizar_implementacion_rechazos.php" (
    move "analizar_implementacion_rechazos.php" "backup-dev\"
    echo [ELIMINADO] analizar_implementacion_rechazos.php
)

if exist "crear_requisicion_especial.php" (
    move "crear_requisicion_especial.php" "backup-dev\"
    echo [ELIMINADO] crear_requisicion_especial.php
)

if exist "debug_autorizar_centro.php" (
    move "debug_autorizar_centro.php" "backup-dev\"
    echo [ELIMINADO] debug_autorizar_centro.php
)

if exist "debug_dashboard.php" (
    move "debug_dashboard.php" "backup-dev\"
    echo [ELIMINADO] debug_dashboard.php
)

if exist "debug_flujo_completo.php" (
    move "debug_flujo_completo.php" "backup-dev\"
    echo [ELIMINADO] debug_flujo_completo.php
)

if exist "debug_post_aprobacion.php" (
    move "debug_post_aprobacion.php" "backup-dev\"
    echo [ELIMINADO] debug_post_aprobacion.php
)

if exist "verificar_notificaciones_rechazos.php" (
    move "verificar_notificaciones_rechazos.php" "backup-dev\"
    echo [ELIMINADO] verificar_notificaciones_rechazos.php
)

REM Archivos en public/
if exist "public\test.php" (
    move "public\test.php" "backup-dev\"
    echo [ELIMINADO] public/test.php
)

if exist "public\logs.php" (
    move "public\logs.php" "backup-dev\"
    echo [ELIMINADO] public/logs.php
)

REM Archivos en scripts/
if exist "scripts\debug_autorizaciones.php" (
    move "scripts\debug_autorizaciones.php" "backup-dev\"
    echo [ELIMINADO] scripts/debug_autorizaciones.php
)

if exist "scripts\probar_claude.php" (
    move "scripts\probar_claude.php" "backup-dev\"
    echo [ELIMINADO] scripts/probar_claude.php
)

REM Archivos varios
if exist "server.log" (
    move "server.log" "backup-dev\"
    echo [ELIMINADO] server.log
)

if exist "nul" (
    del "nul"
    echo [ELIMINADO] nul
)

if exist "deploy-switch.bat" (
    move "deploy-switch.bat" "backup-dev\"
    echo [ELIMINADO] deploy-switch.bat
)

echo.
echo [PASO 2] Limpiando logs de desarrollo...
echo ----------------------------------------

REM Limpiar logs
if exist "storage\logs" (
    move "storage\logs\*.*" "backup-dev\" 2>nul
    echo [LIMPIADO] storage/logs/
) else (
    echo [INFO] storage/logs/ no existe
)

echo.
echo [PASO 3] Respaldando archivos de configuración...
echo ----------------------------------------

REM Backup de configuraciones
copy "config\database.php" "backup-dev\database.php.backup"
copy "public\.htaccess" "backup-dev\htaccess.backup" 2>nul

echo [BACKUP] config/database.php guardado
echo [BACKUP] public/.htaccess guardado

echo.
echo [PASO 4] Información sobre cambios manuales necesarios...
echo ----------------------------------------
echo.
echo ⚠️  CAMBIOS MANUALES REQUERIDOS:
echo.
echo 1. CONFIGURACION DE BASE DE DATOS:
echo    - Editar config/database.php
echo    - Cambiar 'bd_prueba' por BD de producción
echo    - Configurar usuario y contraseña de producción
echo.
echo 2. HTACCESS:
echo    - Editar public/.htaccess  
echo    - Eliminar línea: RewriteBase /requi
echo.
echo 3. LOGS DE DEBUG EN CÓDIGO:
echo    - Revisar y comentar error_log con "DEBUG" 
echo    - Ver lista en: deploy/debug-logs-to-remove.txt
echo.
echo 4. VARIABLES DE ENTORNO:
echo    - Crear .env para producción
echo    - Ver plantilla en: deploy/env-production.template
echo.
echo ============================================
echo    LIMPIEZA COMPLETADA
echo ============================================
echo.
echo ✅ Archivos eliminados (respaldados en backup-dev/)
echo ✅ Logs limpiados  
echo ⚠️  Revisar cambios manuales listados arriba
echo.
echo Presiona cualquier tecla para continuar...
pause >nul