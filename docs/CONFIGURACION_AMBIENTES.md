# 🔧 CONFIGURACIÓN PARA DIFERENTES AMBIENTES

## 📋 DIFERENCIAS ENTRE LOCAL Y SERVIDOR

### **🏠 DESARROLLO LOCAL (XAMPP)**

#### Apache:
- **Alias:** `/requi` → `C:/xampp/htdocs/requi/public`
- **AllowOverride:** `All` (permite .htaccess)
- **Configuración:** `C:\xampp\apache\conf\extra\requi-alias.conf`

#### .htaccess:
- **Existe:** `public/.htaccess` 
- **RewriteBase:** `/requi`
- **Estado:** Activo

#### .env:
```ini
APP_ENV=production
APP_URL=http://localhost/requi
AZURE_REDIRECT_URI=http://localhost/requi/auth/azure/callback
```

---

### **🌍 SERVIDOR PRODUCCIÓN**

#### Apache:
- **Alias:** `/requi` → `/var/www/ieadmon/requi/public`
- **AllowOverride:** `None` (ignora .htaccess)
- **Configuración:** Reglas en VirtualHost SSL

#### .htaccess:
- **Deshabilitado:** `mv .htaccess .htaccess.disabled`
- **Reglas:** En configuración Apache
- **Estado:** Inactivo

#### .env:
```ini
APP_ENV=production
APP_URL=https://ieadmon.iga.edu/requi
AZURE_REDIRECT_URI=https://ieadmon.iga.edu/requi/auth/azure/callback
```

---

## 🚀 PROCESO DE DESPLIEGUE

### **Paso 1: Preparar archivos localmente**
```bash
# Deshabilitar .htaccess para servidor
mv public/.htaccess public/.htaccess.disabled

# Actualizar .env para producción
cp .env.production .env
```

### **Paso 2: Subir al servidor**
```bash
# Via Git (recomendado)
git add .
git commit -m "Deploy: Configuración para servidor"
git push origin main

# En el servidor:
git pull origin main
composer install --no-dev --optimize-autoloader
```

### **Paso 3: Configuración en servidor**
```bash
# Verificar que .htaccess esté deshabilitado
ls -la public/.htaccess*

# Verificar configuración Apache
sudo apache2ctl configtest
sudo systemctl reload apache2
```

---

## 🔄 VOLVER A DESARROLLO LOCAL

### **Reactivar .htaccess local:**
```bash
mv public/.htaccess.disabled public/.htaccess

# Restaurar .env local
cp .env.local .env
```

---

## ⚙️ CONFIGURACIÓN AUTOMÁTICA

### **Script para cambiar entre ambientes:**

```bash
#!/bin/bash
# deploy-switch.sh

case "$1" in
  "local")
    echo "🏠 Configurando para desarrollo local..."
    mv public/.htaccess.disabled public/.htaccess 2>/dev/null
    cp .env.local .env 2>/dev/null
    echo "✅ Listo para desarrollo local"
    ;;
    
  "server")
    echo "🌍 Configurando para servidor..."
    mv public/.htaccess public/.htaccess.disabled 2>/dev/null
    cp .env.production .env 2>/dev/null
    echo "✅ Listo para servidor"
    ;;
    
  *)
    echo "Uso: $0 {local|server}"
    exit 1
    ;;
esac
```

### **Uso:**
```bash
chmod +x deploy-switch.sh

# Para desarrollo local
./deploy-switch.sh local

# Para servidor
./deploy-switch.sh server
```

---

## 📝 NOTAS IMPORTANTES

1. **Never commit .env** - Usar .env.example
2. **El .htaccess** debe existir para local pero estar deshabilitado en servidor
3. **Siempre probar** la configuración antes del despliegue
4. **Documentar cambios** en este archivo

---

## 🔍 TROUBLESHOOTING

### **Problema: 404 en servidor después de despliegue**
```bash
# Verificar que .htaccess esté deshabilitado
ls -la public/.htaccess*

# Debe mostrar: .htaccess.disabled
```

### **Problema: URLs mal generadas**
```bash
# Verificar APP_URL en .env
grep APP_URL .env

# Local debe ser: http://localhost/requi  
# Servidor debe ser: https://ieadmon.iga.edu/requi
```

### **Problema: Apache no recarga configuración**
```bash
# Servidor
sudo systemctl reload apache2

# Local XAMPP
# Reiniciar desde Panel de Control
```