# Plan de Migración - Sistema de Requisiciones v3.0

## 🎯 **Objetivo**
Migrar del esquema actual confuso y disperso a un esquema limpio, normalizado y mantenible.

## 🔍 **Problemas identificados del esquema actual**
1. **`orden_compra`** sin columna `estado`
2. **`autorizacion_centro_costo`** sin `orden_compra_id` directo
3. **Estados dispersos** entre múltiples tablas
4. **Relaciones rotas** y consultas complejas
5. **Lógica de negocio mezclada** con estructura de datos

## 📋 **Plan de Ejecución**

### **FASE 1: Preparación (5 min)**
```bash
# 1. Crear backup de la BD actual
mysqldump -u root -p bd_prueba > backup_antes_migracion_$(date +%Y%m%d_%H%M%S).sql

# 2. Crear BD de prueba para testing
mysql -u root -p -e "CREATE DATABASE bd_prueba_v3;"
```

### **FASE 2: Crear nuevo esquema (10 min)**
```bash
# Ejecutar el nuevo esquema
mysql -u root -p bd_prueba_v3 < nuevo_esquema.sql
```

### **FASE 3: Migrar datos (15 min)**
```bash
# Ejecutar migración de datos
mysql -u root -p bd_prueba_v3 < migracion_datos.sql
```

### **FASE 4: Validación (10 min)**
```bash
# Ejecutar script de validación
php validar_migracion.php
```

### **FASE 5: Cambiar en producción (5 min)**
```bash
# Renombrar BDs
mysql -u root -p -e "
    RENAME TABLE bd_prueba.* TO bd_prueba_old.*;
    RENAME TABLE bd_prueba_v3.* TO bd_prueba.*;
"
```

## 🧪 **Scripts de Testing**

### **Script de Validación**
```php
<?php
// validar_migracion.php

// 1. Verificar que todos los datos se migraron
$conteos = [
    'requisiciones' => 'SELECT COUNT(*) FROM requisiciones',
    'autorizaciones' => 'SELECT COUNT(*) FROM autorizaciones', 
    'items' => 'SELECT COUNT(*) FROM requisicion_items'
];

foreach ($conteos as $tabla => $sql) {
    $count = $pdo->query($sql)->fetchColumn();
    echo "$tabla: $count registros\n";
}

// 2. Verificar integridad referencial
$integridad = [
    'items sin requisicion' => 'SELECT COUNT(*) FROM requisicion_items ri LEFT JOIN requisiciones r ON ri.requisicion_id = r.id WHERE r.id IS NULL',
    'autorizaciones sin requisicion' => 'SELECT COUNT(*) FROM autorizaciones a LEFT JOIN requisiciones r ON a.requisicion_id = r.id WHERE r.id IS NULL'
];

foreach ($integridad as $test => $sql) {
    $errores = $pdo->query($sql)->fetchColumn();
    echo "$test: $errores errores\n";
}

// 3. Verificar lógica de negocio
echo "Testing autorización requisición 2...\n";
$auth = new RequisicionServiceNuevo();
$permisos = $auth->puedeAutorizar(2, 'bgutierrez@sp.iga.edu');
echo $permisos['puede_autorizar'] ? '✅ PUEDE AUTORIZAR' : '❌ NO PUEDE AUTORIZAR: ' . $permisos['motivo_rechazo'];
?>
```

## 📊 **Mapeo de Datos**

### **Tabla: orden_compra → requisiciones**
```sql
id → id
CONCAT('REQ-', LPAD(id, 6, '0')) → numero_requisicion
'borrador' → estado (default)
usuario_id → usuario_id
nombre_razon_social → proveedor_nombre
fecha → fecha_solicitud
monto_total → monto_total
```

### **Tabla: autorizacion_centro_costo → autorizaciones**
```sql
id → id
af.orden_compra_id → requisicion_id
'centro_costo' → tipo
centro_costo_id → centro_costo_id
autorizador_email → autorizador_email
estado → estado
```

### **Tabla: distribucion_gasto → distribucion_centros**
```sql
orden_compra_id → requisicion_id
centro_costo_id → centro_costo_id
porcentaje → porcentaje
monto → monto
```

## ⚠️ **Consideraciones Importantes**

### **1. Compatibilidad hacia atrás**
- Los modelos antiguos seguirán funcionando temporalmente
- Los nuevos servicios usarán el esquema v3.0
- Migración gradual de funcionalidades

### **2. Testing**
- Probar especialmente el flujo de autorización
- Verificar que el problema del "botón de autorización 2" se resuelva
- Validar todos los estados y transiciones

### **3. Rollback plan**
Si algo sale mal:
```bash
# Volver al esquema anterior
mysql -u root -p -e "
    DROP DATABASE bd_prueba;
    RENAME DATABASE bd_prueba_old TO bd_prueba;
"
```

## 🎉 **Beneficios esperados**

1. **✅ Problema del botón de autorización resuelto** - Relaciones claras y directas
2. **✅ Estados consistentes** - Una sola fuente de verdad
3. **✅ Consultas más simples** - JOINs directos sin tablas intermedias confusas
4. **✅ Código más mantenible** - Lógica de negocio limpia en servicios
5. **✅ Mejor rendimiento** - Índices optimizados y consultas eficientes

## 🚀 **Pasos siguientes**

1. **Ejecutar la migración** en ambiente de desarrollo
2. **Probar exhaustivamente** el flujo completo
3. **Actualizar controladores** para usar nuevos servicios
4. **Migrar vistas** para mostrar datos del nuevo esquema
5. **Documentar** los cambios para el equipo

¿Quieres que ejecutemos esta migración paso a paso?