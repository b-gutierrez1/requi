# 🚀 GUÍA COMPLETA DE DESPLIEGUE AL SERVIDOR

## 📋 PRE-REQUISITOS

### En tu Servidor necesitas:
- **PHP 8.1+** con extensiones: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `json`, `curl`
- **MySQL/MariaDB 8.0+**
- **Apache 2.4+** o **Nginx 1.18+**
- **Composer** (para manejar dependencias PHP)

## 🔧 PASO 1: PREPARAR ARCHIVOS LOCALES

### 1.1 Configura el archivo `.env` para producción:
```bash
# Copia el archivo de ejemplo
cp .env.production .env

# Edita las variables según tu servidor:
nano .env
```

**Variables importantes a cambiar:**
- `APP_URL=https://tudominio.com`
- `APP_BASE_PATH=/` (si está en el root) o `/requi-mvc` (si está en subdirectorio)
- `DB_HOST=localhost` (o IP de tu BD)
- `DB_DATABASE=nombre_bd_produccion`
- `DB_USERNAME=usuario_bd_seguro`
- `DB_PASSWORD=password_muy_seguro`

### 1.2 Optimiza los archivos:
```bash
# Instala dependencias de producción
composer install --no-dev --optimize-autoloader

# Verifica que no hay archivos de desarrollo
rm -f debug*.php test*.php
```

## 📁 PASO 2: SUBIR AL SERVIDOR

### Opción A: Via FTP/SFTP
1. Comprimir archivos (excluyendo `.git`, `node_modules`, `*.log`)
2. Subir al directorio del servidor (ej: `/var/www/html/requi-mvc`)
3. Descomprimir en el servidor

### Opción B: Via Git (Recomendado)
```bash
# En el servidor:
cd /var/www/html
git clone https://tu-repositorio.git requi-mvc
cd requi-mvc
composer install --no-dev --optimize-autoloader
```

### Opción C: Via script automatizado
```bash
# Usa el script incluido:
chmod +x servidor-config/deploy.sh
./servidor-config/deploy.sh deploy
```

## ⚙️ PASO 3: CONFIGURAR EL SERVIDOR WEB

### Para Apache:
```bash
# Copia el archivo de configuración:
sudo cp servidor-config/.htaccess /var/www/html/requi-mvc/

# Habilita mod_rewrite:
sudo a2enmod rewrite

# Configura el VirtualHost:
sudo nano /etc/apache2/sites-available/requi-mvc.conf
```

**Contenido del VirtualHost:**
```apache
<VirtualHost *:80>
    ServerName tudominio.com
    DocumentRoot /var/www/html/requi-mvc/public
    
    <Directory /var/www/html/requi-mvc/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Proteger directorios sensibles
    <Directory /var/www/html/requi-mvc/storage>
        Require all denied
    </Directory>
    
    <Directory /var/www/html/requi-mvc/app>
        Require all denied
    </Directory>
</VirtualHost>
```

```bash
# Activar el sitio:
sudo a2ensite requi-mvc
sudo systemctl reload apache2
```

### Para Nginx:
```bash
# Copia la configuración:
sudo cp servidor-config/nginx.conf /etc/nginx/sites-available/requi-mvc

# Activar el sitio:
sudo ln -s /etc/nginx/sites-available/requi-mvc /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 🗄️ PASO 4: CONFIGURAR BASE DE DATOS

### 4.1 Crear la base de datos:
```sql
CREATE DATABASE requi_mvc_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'requi_user'@'localhost' IDENTIFIED BY 'password_muy_seguro';
GRANT ALL PRIVILEGES ON requi_mvc_prod.* TO 'requi_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4.2 Importar el esquema:
```bash
# Si tienes un dump de la BD:
mysql -u requi_user -p requi_mvc_prod < database/schema.sql

# O ejecuta las migraciones si las tienes:
php scripts/run_migrations.php
```

## 🔒 PASO 5: CONFIGURAR PERMISOS

```bash
# Configurar propietario correcto:
sudo chown -R www-data:www-data /var/www/html/requi-mvc

# Permisos para directorios:
sudo find /var/www/html/requi-mvc -type d -exec chmod 755 {} \;

# Permisos para archivos:
sudo find /var/www/html/requi-mvc -type f -exec chmod 644 {} \;

# Permisos especiales para storage y uploads:
sudo chmod -R 775 /var/www/html/requi-mvc/storage
sudo chmod -R 775 /var/www/html/requi-mvc/public/uploads

# Hacer ejecutable el index:
sudo chmod 755 /var/www/html/requi-mvc/public/index.php
```

## 🔐 PASO 6: CONFIGURAR HTTPS (Recomendado)

### Con Let's Encrypt (Certbot):
```bash
# Instalar certbot:
sudo apt install certbot python3-certbot-apache

# Obtener certificado:
sudo certbot --apache -d tudominio.com -d www.tudominio.com

# Verificar renovación automática:
sudo systemctl status certbot.timer
```

## ✅ PASO 7: VERIFICACIONES FINALES

### 7.1 Verifica que el sistema funciona:
1. Visita: `https://tudominio.com`
2. Prueba login
3. Verifica que CSS/JS cargan correctamente
4. Prueba crear una requisición

### 7.2 Verifica logs:
```bash
# Logs de la aplicación:
tail -f /var/www/html/requi-mvc/storage/logs/app.log

# Logs del servidor web:
sudo tail -f /var/log/apache2/error.log
# o para Nginx:
sudo tail -f /var/log/nginx/error.log
```

### 7.3 Configurar monitoreo básico:
```bash
# Crear script de health check:
echo '#!/bin/bash
curl -f https://tudominio.com/api/health || echo "❌ Sistema no responde"
' > /usr/local/bin/check-requi-health

chmod +x /usr/local/bin/check-requi-health

# Agregar a crontab para verificar cada 5 minutos:
echo "*/5 * * * * /usr/local/bin/check-requi-health" | sudo crontab -
```

## 🔧 TROUBLESHOOTING COMÚN

### Error: "500 Internal Server Error"
```bash
# Verificar logs:
sudo tail -f /var/log/apache2/error.log

# Verificar permisos:
sudo chown -R www-data:www-data /var/www/html/requi-mvc/storage

# Verificar .env:
cat /var/www/html/requi-mvc/.env
```

### Error: CSS/JS no cargan
- Verifica `APP_URL` en `.env`
- Verifica `APP_BASE_PATH` en `.env`
- Verifica que los archivos existen en `public/css/` y `public/js/`

### Error: Base de datos
```bash
# Probar conexión:
mysql -h localhost -u requi_user -p requi_mvc_prod

# Verificar variables en .env:
grep DB_ .env
```

### Error: Permisos
```bash
# Resetear permisos:
sudo chown -R www-data:www-data /var/www/html/requi-mvc
sudo chmod -R 755 /var/www/html/requi-mvc
sudo chmod -R 775 /var/www/html/requi-mvc/storage
```

## 📊 MANTENIMIENTO

### Backups automatizados:
```bash
# Script de backup diario:
echo '#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u requi_user -p requi_mvc_prod > /backups/requi_${DATE}.sql
find /backups -name "requi_*.sql" -mtime +7 -delete
' > /usr/local/bin/backup-requi

chmod +x /usr/local/bin/backup-requi

# Programar backup diario a las 2:00 AM:
echo "0 2 * * * /usr/local/bin/backup-requi" | sudo crontab -
```

### Actualizaciones:
```bash
# Para actualizar el sistema:
cd /var/www/html/requi-mvc
git pull origin main
composer install --no-dev --optimize-autoloader
sudo systemctl reload apache2
```

## 📞 SOPORTE

Si tienes problemas:
1. Revisa los logs del sistema
2. Verifica la configuración paso a paso
3. Asegúrate que todos los servicios estén funcionando
4. Contacta al administrador del sistema

---

🎉 **¡Sistema listo para producción!**