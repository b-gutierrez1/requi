@echo off
REM ===================================================================
REM Aplica la migracion de renombre a la terminologia oficial.
REM Doble clic para ejecutar. Requiere que el servicio MariaDB este
REM corriendo (es el de "Program Files", no el de XAMPP).
REM ===================================================================

setlocal
set MYSQL="C:\Program Files\MariaDB 11.7\bin\mysql.exe"
set SCRIPT=%~dp0migrations\2026-08-05_renombre_terminologia.sql

echo.
echo ============================================================
echo  Renombre a terminologia oficial - base bd_prueba
echo ============================================================
echo.

if not exist %MYSQL% (
    echo ERROR: no se encontro el cliente de MariaDB en:
    echo   %MYSQL%
    goto fin
)

if not exist "%SCRIPT%" (
    echo ERROR: no se encontro el script de migracion en:
    echo   %SCRIPT%
    goto fin
)

echo Aplicando migracion...
echo.

%MYSQL% -u root --default-character-set=utf8mb4 bd_prueba < "%SCRIPT%"

if errorlevel 1 (
    echo.
    echo ------------------------------------------------------------
    echo  FALLO: revisa el mensaje de error de arriba.
    echo  La base quedo sin cambios o a medias: avisa antes de seguir.
    echo ------------------------------------------------------------
) else (
    echo.
    echo ------------------------------------------------------------
    echo  LISTO: migracion aplicada sin errores.
    echo ------------------------------------------------------------
    echo.
    echo Comprobacion rapida:
    %MYSQL% -u root --default-character-set=utf8mb4 bd_prueba -e "SELECT 'unidad_de_negocio (debe tener 28 detalles)' AS tabla, COUNT(*) AS filas FROM unidad_de_negocio UNION ALL SELECT 'centro_de_costo (debe tener 10 grupos)', COUNT(*) FROM centro_de_costo;"
)

:fin
echo.
echo Presiona una tecla para cerrar...
pause > nul
endlocal
