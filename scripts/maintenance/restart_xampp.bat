@echo off
echo Reiniciando servicios de XAMPP...

echo Deteniendo Apache...
net stop Apache2.4 2>nul
taskkill /f /im httpd.exe 2>nul

echo Deteniendo MySQL...
net stop mysql 2>nul
taskkill /f /im mysqld.exe 2>nul

echo Esperando 3 segundos...
timeout /t 3 /nobreak >nul

echo Iniciando MySQL...
net start mysql

echo Iniciando Apache...
net start Apache2.4

echo.
echo Servicios reiniciados. Presiona cualquier tecla para cerrar.
pause >nul