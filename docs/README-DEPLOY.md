# 🚀 GUÍA DE DESPLIEGUE A PRODUCCIÓN

Este directorio contiene todos los scripts y archivos necesarios para preparar la aplicación para producción.

## 📋 CHECKLIST DE DESPLIEGUE

### ✅ PASO 1: Ejecutar Script de Limpieza
```bash
# Ejecutar en Windows
.\deploy\cleanup-for-production.bat

# O manualmente eliminar archivos listados en el script
```

**¿Qué hace?**
- ✅ Elimina archivos de debug y desarrollo
- ✅ Limpia logs de desarrollo  
- ✅ Crea backups de configuraciones
- ✅ Lista cambios manuales necesarios

### ✅ PASO 2: Remover Logs de Debug del Código
```bash
# Ejecutar script PHP para comentar logs automáticamente
cd C:\xampp\htdocs\requi
php deploy/remove-debug-logs.php

# O revisar manualmente usando:
# deploy/debug-logs-to-remove.txt
```

**¿Qué hace?**
- ✅ Comenta automáticamente error_log con "DEBUG"
- ✅ Mantiene indentación del código
- ✅ Crea backups de archivos modificados

### ✅ PASO 3: Configurar Base de Datos
```bash
# Reemplazar config/database.php con:
cp deploy/production-config-database.php config/database.php
```

**Cambios principales:**
- ❌ `'database' => 'bd_prueba'` 
- ✅ `'database' => getenv('DB_DATABASE')`
- ✅ Usuario/contraseña desde variables de entorno
- ✅ Configuraciones de seguridad habilitadas

### ✅ PASO 4: Configurar .htaccess
```bash
# Reemplazar public/.htaccess con:
cp deploy/production-htaccess public/.htaccess
```

**Cambios principales:**
- ❌ `RewriteBase /requi` (eliminado)
- ✅ Headers de seguridad agregados
- ✅ Configuración de cache
- ✅ Protección de archivos sensibles

### ✅ PASO 5: Crear Variables de Entorno
```bash
# Crear .env en el servidor con:
cp deploy/env-production.template .env
# Luego editar .env con valores reales
```

**Variables críticas:**
```env
DB_HOST=servidor_produccion
DB_DATABASE=bd_produccion
DB_USERNAME=usuario_prod
DB_PASSWORD=contraseña_segura
APP_ENV=production
APP_DEBUG=false
```

## 🗂️ ARCHIVOS EN ESTE DIRECTORIO

| Archivo | Descripción |
|---------|-------------|
| `cleanup-for-production.bat` | 🧹 Script principal de limpieza |
| `remove-debug-logs.php` | 🔧 Remueve logs de debug automáticamente |
| `debug-logs-to-remove.txt` | 📝 Lista de logs a comentar manualmente |
| `env-production.template` | ⚙️ Plantilla de .env para producción |
| `production-config-database.php` | 🗄️ Configuración de BD para producción |
| `production-htaccess` | 🔒 .htaccess optimizado para producción |
| `README-DEPLOY.md` | 📖 Esta guía |

## 🚫 ARCHIVOS QUE NO DEBEN IR A PRODUCCIÓN

### 📁 Archivos de desarrollo:
```
❌ analizar_implementacion_rechazos.php
❌ crear_requisicion_especial.php
❌ debug_*.php
❌ verificar_*.php
❌ scripts/debug_*.php
❌ public/test.php
❌ public/logs.php
❌ server.log
❌ deploy-switch.bat
❌ nul
```

### 📁 Directorios opcionales:
```
❌ deploy/ (este directorio - opcional)
❌ scripts/ (si solo contiene debug)
❌ storage/logs/*.txt (limpiar)
❌ servidor-config/ (solo referencia)
```

## ⚠️ VERIFICACIONES POST-DEPLOY

### 1. **Verificar Conexión DB**
```sql
-- Verificar que se conecta a la BD correcta
SELECT DATABASE();
-- Debería mostrar la BD de producción, NO 'bd_prueba'
```

### 2. **Verificar Logs**
```bash
# No debería mostrar logs de DEBUG
tail -f storage/logs/app.log | grep -i debug
# Si muestra algo, hay logs que no se comentaron
```

### 3. **Verificar URLs**
```bash
# Probar que las URLs funcionen sin /requi
https://tudominio.com/dashboard      # ✅ Debería funcionar  
https://tudominio.com/autorizaciones # ✅ Debería funcionar
```

### 4. **Verificar Configuración**
```php
<?php
// Script temporal para verificar configuración
echo "Database: " . getenv('DB_DATABASE') . "\n";
echo "Environment: " . (getenv('APP_ENV') ?: 'development') . "\n";
echo "Debug: " . (getenv('APP_DEBUG') ?: 'true') . "\n";
```

## 🔄 ROLLBACK (Si algo sale mal)

### Restaurar archivos:
```bash
# Los backups están en:
backup-dev/                    # Archivos eliminados
deploy/backup-debug-removal/   # Archivos antes de comentar logs

# Restaurar configuración:
cp backup-dev/database.php.backup config/database.php
cp backup-dev/htaccess.backup public/.htaccess
```

## 📞 SOPORTE

Si encuentras problemas:

1. **Verificar logs de error** del servidor web
2. **Revisar configuración** de base de datos
3. **Comprobar permisos** de archivos y directorios
4. **Verificar que PHP** tenga extensiones necesarias

---

## 🎯 RESUMEN EJECUTIVO

**Orden de ejecución recomendado:**

1. `cleanup-for-production.bat` ← Limpiar archivos
2. `remove-debug-logs.php` ← Comentar logs  
3. Reemplazar `config/database.php` ← Configurar BD
4. Reemplazar `public/.htaccess` ← Configurar Apache
5. Crear `.env` en servidor ← Variables de entorno
6. **Probar aplicación** ← Verificar funcionamiento
7. **Monitorear logs** ← Verificar que no hay errores

**Tiempo estimado:** 15-30 minutos  
**Nivel de dificultad:** Medio  
**Requiere acceso:** Servidor, BD, archivos de configuración