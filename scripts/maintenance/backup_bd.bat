@echo off
setlocal

:: ============================================================
:: Backup de bd_prueba → restaurable como bd_requisicion
:: Uso: doble clic o ejecutar desde terminal
:: ============================================================

set MYSQL_BIN=C:\xampp\mysql\bin
set DB_HOST=localhost
set DB_PORT=3306
set DB_USER=root
set DB_PASS=
set DB_SOURCE=bd_prueba
set DB_TARGET=bd_requisicion

:: Nombre del archivo con fecha/hora
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set DATETIME=%%I
set TIMESTAMP=%DATETIME:~0,8%_%DATETIME:~8,6%
set BACKUP_FILE=%~dp0backup_%TIMESTAMP%.sql

echo.
echo ============================================================
echo  Backup: %DB_SOURCE% → %DB_TARGET%
echo  Archivo: %BACKUP_FILE%
echo ============================================================
echo.

:: Crear encabezado con la BD destino
(
    echo -- ============================================================
    echo -- Backup de: %DB_SOURCE%
    echo -- Fecha: %TIMESTAMP%
    echo -- Restaurar como: %DB_TARGET%
    echo -- ============================================================
    echo.
    echo CREATE DATABASE IF NOT EXISTS `%DB_TARGET%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    echo USE `%DB_TARGET%`;
    echo.
) > "%BACKUP_FILE%"

:: Ejecutar mysqldump a archivo temporal
set TEMP_FILE=%~dp0backup_temp_%TIMESTAMP%.sql

"%MYSQL_BIN%\mysqldump.exe" ^
    --host=%DB_HOST% ^
    --port=%DB_PORT% ^
    --user=%DB_USER% ^
    --no-tablespaces ^
    --routines ^
    --triggers ^
    --single-transaction ^
    %DB_SOURCE% > "%TEMP_FILE%"

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Fallo el backup. Verifica que XAMPP este corriendo.
    del "%TEMP_FILE%" 2>nul
    del "%BACKUP_FILE%" 2>nul
    goto :end
)

:: Reemplazar collations de MariaDB incompatibles con MySQL
:: utf8mb4_uca1400_ai_ci → utf8mb4_unicode_ci
:: utf8mb4_uca1400_as_cs → utf8mb4_unicode_ci
echo [INFO] Normalizando collations para compatibilidad MySQL/MariaDB...
powershell -Command "(Get-Content '%TEMP_FILE%') -replace 'utf8mb4_uca1400_ai_ci','utf8mb4_unicode_ci' -replace 'utf8mb4_uca1400_as_cs','utf8mb4_unicode_ci' -replace 'utf8mb3_uca1400_ai_ci','utf8_unicode_ci' | Add-Content '%BACKUP_FILE%'"

del "%TEMP_FILE%" 2>nul

echo [OK] Backup completado exitosamente.
echo [OK] Archivo: %BACKUP_FILE%
echo.
echo Para restaurar como "%DB_TARGET%" ejecuta:
echo   %MYSQL_BIN%\mysql.exe -u %DB_USER% "%DB_TARGET%" ^< "%BACKUP_FILE%"
echo.
echo O desde MySQL Workbench / phpMyAdmin importa el archivo SQL.

:end
echo.
pause
endlocal
