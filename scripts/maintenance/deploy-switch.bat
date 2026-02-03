@echo off
REM ============================================
REM Script para cambiar entre ambientes
REM Uso: deploy-switch.bat {local|server}
REM ============================================

if "%1"=="local" goto LOCAL
if "%1"=="server" goto SERVER
goto USAGE

:LOCAL
echo 🏠 Configurando para desarrollo local...
if exist "public\.htaccess.disabled" (
    move "public\.htaccess.disabled" "public\.htaccess" >nul 2>&1
    echo ✅ .htaccess activado para desarrollo local
)
if exist ".env.local" (
    copy ".env.local" ".env" >nul 2>&1
    echo ✅ .env local configurado
)
echo ✅ Listo para desarrollo local
echo.
echo URLs disponibles:
echo   http://localhost/requi/
echo   http://localhost/requi/login
goto END

:SERVER
echo 🌍 Configurando para servidor...
if exist "public\.htaccess" (
    move "public\.htaccess" "public\.htaccess.disabled" >nul 2>&1
    echo ✅ .htaccess deshabilitado para servidor
)
if exist ".env.production" (
    copy ".env.production" ".env" >nul 2>&1
    echo ✅ .env de producción configurado
)
echo ✅ Listo para servidor
echo.
echo Recuerda hacer:
echo   git add .
echo   git commit -m "Deploy: Configuración para servidor"
echo   git push origin main
goto END

:USAGE
echo.
echo Uso: %0 {local^|server}
echo.
echo   local  - Configura para desarrollo local
echo   server - Configura para servidor de producción
echo.
exit /b 1

:END