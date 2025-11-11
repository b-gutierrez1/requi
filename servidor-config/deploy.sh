#!/bin/bash
# ===================================
# SCRIPT DE DESPLIEGUE AL SERVIDOR
# ===================================

echo "🚀 Iniciando despliegue del Sistema de Requisiciones..."

# Variables de configuración
PROJECT_NAME="requi-mvc"
SERVER_USER="tu_usuario"
SERVER_HOST="tu_servidor.com"
SERVER_PATH="/var/www/requi-mvc"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para logging
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

# 1. Verificar archivos requeridos
log "Verificando archivos necesarios..."

if [[ ! -f ".env.production" ]]; then
    error "No se encontró .env.production. Créalo basado en .env.production de ejemplo."
    exit 1
fi

if [[ ! -f "composer.json" ]]; then
    error "No se encontró composer.json"
    exit 1
fi

# 2. Instalar/actualizar dependencias
log "Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader

# 3. Verificar permisos de directorios críticos
log "Verificando estructura de directorios..."

directories=("storage/logs" "storage/uploads" "public/uploads")
for dir in "${directories[@]}"; do
    if [[ ! -d "$dir" ]]; then
        mkdir -p "$dir"
        log "Creado directorio: $dir"
    fi
done

# 4. Crear archivo de configuración para servidor
log "Preparando archivos de configuración..."

# Crear .env para producción (copia el ejemplo)
if [[ -f ".env.production" ]]; then
    cp ".env.production" ".env"
    log "Archivo .env configurado para producción"
fi

# 5. Optimizar para producción
log "Optimizando archivos para producción..."

# Minificar CSS (si tienes herramientas disponibles)
# cssmin public/css/app.css > public/css/app.min.css

# Minificar JS (si tienes herramientas disponibles)
# jsmin public/js/app.js > public/js/app.min.js

# 6. Configurar permisos correctos
log "Configurando permisos..."

# Para directorios
find storage -type d -exec chmod 755 {} \;
find public/uploads -type d -exec chmod 755 {} \;

# Para archivos
find storage -type f -exec chmod 644 {} \;
find public -type f -exec chmod 644 {} \;

# Hacer ejecutable el script
chmod +x public/index.php

# 7. Subir al servidor (usando rsync)
if [[ "$1" == "deploy" ]]; then
    log "Subiendo archivos al servidor..."
    
    rsync -avz --exclude='node_modules' \
               --exclude='.git' \
               --exclude='*.log' \
               --exclude='.env.example' \
               --exclude='servidor-config' \
               ./ $SERVER_USER@$SERVER_HOST:$SERVER_PATH/
    
    # Ejecutar comandos en el servidor
    ssh $SERVER_USER@$SERVER_HOST << 'EOF'
        cd /var/www/requi-mvc
        
        # Configurar permisos en el servidor
        sudo chown -R www-data:www-data storage public/uploads
        sudo chmod -R 755 storage public/uploads
        
        # Reiniciar servicios
        sudo systemctl reload nginx
        sudo systemctl reload php8.1-fpm
        
        echo "✅ Despliegue completado en el servidor"
EOF

    log "🎉 Despliegue completado exitosamente!"
else
    log "📦 Archivos preparados para despliegue"
    log "💡 Para subir al servidor ejecuta: ./deploy.sh deploy"
fi

# 8. Instrucciones finales
echo ""
log "📋 PASOS FINALES EN EL SERVIDOR:"
echo "1. Configurar base de datos en .env"
echo "2. Importar esquema de base de datos"
echo "3. Configurar permisos web server (www-data)"
echo "4. Configurar certificado SSL"
echo "5. Verificar que el sistema funciona"

echo ""
log "✅ Script completado"