# Manual de Integración — SAP Business One Service Layer

**Sistema:** Requisiciones IGA  
**SAP:** Business One (HANA) vía Service Layer  
**Versión:** 1.0  
**Fecha:** Mayo 2026

---

## Índice

1. [Requisitos previos](#1-requisitos-previos)
2. [Resumen del flujo](#2-resumen-del-flujo)
3. [Fase 1 — Preparación de base de datos](#3-fase-1--preparación-de-base-de-datos)
4. [Fase 2 — Configuración del servidor](#4-fase-2--configuración-del-servidor)
5. [Fase 3 — Código a crear](#5-fase-3--código-a-crear)
6. [Fase 4 — Datos a llenar (trabajo administrativo)](#6-fase-4--datos-a-llenar-trabajo-administrativo)
7. [Fase 5 — Activar la integración](#7-fase-5--activar-la-integración)
8. [Pruebas](#8-pruebas)
9. [Qué pasa si SAP falla](#9-qué-pasa-si-sap-falla)
10. [Checklist final antes de activar](#10-checklist-final-antes-de-activar)

---

## 1. Requisitos previos

Antes de empezar, confirmar que se tiene lo siguiente:

### Del lado de SAP
- [ ] SAP Business One HANA instalado y funcionando
- [ ] Service Layer habilitado y accesible desde el servidor web
- [ ] URL del Service Layer (formato: `https://servidor-sap:50000/b1s/v1`)
- [ ] Usuario de servicio creado en SAP (solo para esta integración)
- [ ] Contraseña del usuario de servicio
- [ ] Nombre de la empresa (CompanyDB) en SAP
- [ ] Confirmación de que los **proveedores** ya están registrados en SAP con su CardCode
- [ ] Certificado SSL del servidor SAP (si usa HTTPS con certificado propio)

### Del lado del sistema de requisiciones
- [ ] Acceso SSH al servidor Linux (`/var/www/ieadmon/requi`)
- [ ] Acceso a MySQL de producción
- [ ] Lista de centros de costo con su código en SAP
- [ ] Lista de cuentas contables con su código en SAP
- [ ] Lista de proveedores con NIT → CardCode SAP

### Verificar conectividad
Desde el servidor Linux, ejecutar:
```bash
curl -k https://servidor-sap:50000/b1s/v1 -I
# Debe responder HTTP/1.1 200 o 401 (no connection refused)
```

---

## 2. Resumen del flujo

```
Usuario crea requisición
        ↓
Pasa por revisión y autorizaciones
        ↓
Estado llega a "autorizado"
        ↓
Sistema llama automáticamente a SAP Service Layer
        ↓
SAP crea la Orden de Compra
        ↓
SAP retorna DocEntry + DocNumber
        ↓
Sistema guarda el número de PO en la requisición
        ↓
Usuario puede ver el número de PO de SAP en la requisición
```

---

## 3. Fase 1 — Preparación de base de datos

Ejecutar estos SQLs en producción. **Hacerlo en orden.**

### 3.1 Agregar códigos SAP a centros de costo

```sql
ALTER TABLE centro_de_costo
ADD COLUMN sap_codigo VARCHAR(20) NULL
    COMMENT 'CostingCode del centro de costo en SAP B1';

-- Verificar
DESCRIBE centro_de_costo;
```

### 3.2 Agregar códigos SAP a cuentas contables

```sql
ALTER TABLE cuenta_contable
ADD COLUMN sap_codigo VARCHAR(20) NULL
    COMMENT 'AccountCode de la cuenta en SAP B1';

-- Verificar
DESCRIBE cuenta_contable;
```

### 3.3 Agregar campos SAP a requisiciones

```sql
ALTER TABLE requisiciones
ADD COLUMN sap_proveedor_codigo VARCHAR(20) NULL
    COMMENT 'CardCode del proveedor en SAP B1',
ADD COLUMN sap_doc_entry INT NULL
    COMMENT 'DocEntry de la PO creada en SAP',
ADD COLUMN sap_doc_number INT NULL
    COMMENT 'DocNum de la PO creada en SAP',
ADD COLUMN sap_sync_estado VARCHAR(20) DEFAULT 'pendiente'
    COMMENT 'pendiente / sincronizado / error / no_aplica',
ADD COLUMN sap_sync_fecha TIMESTAMP NULL
    COMMENT 'Fecha y hora de sincronización exitosa',
ADD COLUMN sap_sync_error TEXT NULL
    COMMENT 'Mensaje de error si la sincronización falló';

-- Índice para consultas de sincronización pendiente
CREATE INDEX idx_sap_sync ON requisiciones(sap_sync_estado, sap_sync_fecha);

-- Verificar
DESCRIBE requisiciones;
```

### 3.4 Crear tabla de mapeo de proveedores

```sql
CREATE TABLE sap_proveedores_mapeo (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    nit         VARCHAR(20)  NOT NULL UNIQUE COMMENT 'NIT del proveedor local',
    sap_card_code VARCHAR(20) NOT NULL        COMMENT 'CardCode en SAP B1',
    nombre      VARCHAR(255) NOT NULL,
    activo      TINYINT(1)   DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_card_code (sap_card_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.5 Crear tabla de log de sincronización

```sql
CREATE TABLE sap_sync_log (
    id              INT PRIMARY KEY AUTO_INCREMENT,
    requisicion_id  INT          NOT NULL,
    operacion       VARCHAR(50)  NOT NULL COMMENT 'crear_po / reintento / error',
    estado          VARCHAR(20)  NOT NULL COMMENT 'exitoso / error',
    request_json    LONGTEXT     NULL COMMENT 'JSON enviado a SAP',
    response_json   LONGTEXT     NULL COMMENT 'Respuesta de SAP',
    error_mensaje   TEXT         NULL,
    fecha           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requisicion_id) REFERENCES requisiciones(id),
    INDEX idx_req (requisicion_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Fase 2 — Configuración del servidor

### 4.1 Agregar credenciales SAP al .env

```ini
# SAP Business One Service Layer
SAP_BASE_URL=https://servidor-sap:50000/b1s/v1
SAP_COMPANY_DB=nombre_empresa_sap
SAP_USERNAME=usuario_servicio
SAP_PASSWORD=contraseña_servicio
SAP_SSL_VERIFY=false
# SAP_SSL_VERIFY=true si el certificado es válido/firmado por CA reconocida
```

### 4.2 Crear archivo de configuración SAP

Crear el archivo `config/sap.php`:

```php
<?php
return [
    'base_url'   => getenv('SAP_BASE_URL')   ?: '',
    'company_db' => getenv('SAP_COMPANY_DB') ?: '',
    'username'   => getenv('SAP_USERNAME')   ?: '',
    'password'   => getenv('SAP_PASSWORD')   ?: '',
    'ssl_verify' => getenv('SAP_SSL_VERIFY') === 'true',
    'timeout'    => 30,
];
```

---

## 5. Fase 3 — Código a crear

### 5.1 SapService.php

Crear `app/Services/SapService.php`. Este servicio se encarga de:
- Autenticarse con SAP Service Layer
- Construir el JSON de la Orden de Compra
- Enviar la petición
- Guardar el resultado

**Estructura del JSON que se envía a SAP:**

```json
{
  "CardCode": "P001",
  "DocDate": "2026-05-12",
  "DocDueDate": "2026-05-20",
  "DocCurrency": "GTQ",
  "Comments": "Requisición #33 - Alquiler vehículo",
  "DocumentLines": [
    {
      "AccountCode": "5001-001-00",
      "Quantity": 1,
      "UnitPrice": 2784.00,
      "LineTotal": 2784.00,
      "CostingCode": "CC-ADMIN",
      "OcrCode2": "PRY-001"
    }
  ]
}
```

**Mapeo de campos:**

| Campo SAP | Origen en Requisición |
|---|---|
| `CardCode` | `sap_proveedores_mapeo.sap_card_code` (buscar por `proveedor_nit`) |
| `DocDate` | `requisiciones.fecha_solicitud` |
| `DocDueDate` | `requisiciones.fecha_limite` |
| `DocCurrency` | `requisiciones.moneda` |
| `Comments` | `"Requisición #" + numero_requisicion + " - " + observaciones` |
| `AccountCode` (línea) | `cuenta_contable.sap_codigo` |
| `Quantity` (línea) | `1` (una línea por distribución) |
| `UnitPrice` (línea) | `distribucion_gasto.cantidad` |
| `LineTotal` (línea) | `distribucion_gasto.cantidad` |
| `CostingCode` (línea) | `centro_de_costo.sap_codigo` |

### 5.2 Punto de activación en AutorizacionService.php

En `app/Services/AutorizacionService.php`, dentro del método `verificarYCompletarFlujo()`,
buscar el bloque donde se detecta el cambio a `ESTADO_AUTORIZADO` (~línea 723):

```php
if ($nuevoEstado === AutorizacionFlujo::ESTADO_AUTORIZADO) {

    // ← AGREGAR ESTO
    try {
        $sapService = new \App\Services\SapService();
        $sapService->crearOrdenCompra($ordenId);
    } catch (\Exception $e) {
        error_log("SAP sync error req #{$ordenId}: " . $e->getMessage());
        // No bloqueamos el flujo si SAP falla — se puede reintentar
    }

    $this->notificacionService->notificarAutorizacionCompleta($ordenId);
}
```

> **Importante:** El bloque `try/catch` es intencional. Si SAP falla, la requisición
> sigue marcada como autorizada. El error queda en `sap_sync_log` y se puede
> reintentar manualmente.

### 5.3 Modelos nuevos a crear

**`app/Models/SapProveedorMapeo.php`**
- Tabla: `sap_proveedores_mapeo`
- Métodos: `buscarPorNit($nit)`, `buscarPorCardCode($code)`

**`app/Models/SapSyncLog.php`**
- Tabla: `sap_sync_log`
- Método: `registrar($requisicionId, $operacion, $estado, $request, $response, $error)`

---

## 6. Fase 4 — Datos a llenar (trabajo administrativo)

Esta fase no requiere programación. Es trabajo de cruzar datos entre el sistema y SAP.

### 6.1 Centros de costo

Alguien del área debe llenar la columna `sap_codigo` en cada centro de costo.

**Cómo ver los centros actuales:**
```sql
SELECT id, nombre, sap_codigo FROM centro_de_costo WHERE activo = 1 ORDER BY nombre;
```

**Cómo actualizar:**
```sql
UPDATE centro_de_costo SET sap_codigo = 'CC-ADMIN' WHERE id = 1;
UPDATE centro_de_costo SET sap_codigo = 'CC-ACAD'  WHERE id = 2;
-- etc.
```

> El código SAP (`CostingCode`) se encuentra en SAP B1 en:
> **Módulos → Finanzas → Centros de Costo**

### 6.2 Cuentas contables

```sql
SELECT id, codigo, descripcion, sap_codigo FROM cuenta_contable WHERE activo = 1;
```

```sql
UPDATE cuenta_contable SET sap_codigo = '5001-001-00' WHERE id = 336;
-- etc.
```

> El código SAP (`AccountCode`) se encuentra en SAP B1 en:
> **Módulos → Finanzas → Plan de Cuentas**

### 6.3 Proveedores (tabla de mapeo)

Llenar la tabla `sap_proveedores_mapeo` con NIT → CardCode de SAP:

```sql
INSERT INTO sap_proveedores_mapeo (nit, sap_card_code, nombre) VALUES
('1234567-8', 'P001', 'RENTAAUTOS, S.A.'),
('9876543-2', 'P002', 'Comercial El Éxito, S.A.'),
-- agregar todos los proveedores que tienen requisiciones
```

> El `CardCode` del proveedor se encuentra en SAP B1 en:
> **Socios de Negocios → Proveedores**

### 6.4 Verificar que todo está llenado antes de activar

```sql
-- Centros de costo sin código SAP
SELECT nombre FROM centro_de_costo WHERE activo = 1 AND sap_codigo IS NULL;

-- Cuentas contables sin código SAP
SELECT codigo, descripcion FROM cuenta_contable WHERE activo = 1 AND sap_codigo IS NULL;

-- Proveedores en requisiciones aprobadas sin mapeo SAP
SELECT DISTINCT r.proveedor_nombre, r.proveedor_nit
FROM requisiciones r
WHERE r.proveedor_nit NOT IN (SELECT nit FROM sap_proveedores_mapeo)
ORDER BY r.proveedor_nombre;
```

Los tres queries deben devolver **cero resultados** antes de activar.

---

## 7. Fase 5 — Activar la integración

1. Subir los archivos nuevos al servidor:
   - `app/Services/SapService.php`
   - `app/Models/SapProveedorMapeo.php`
   - `app/Models/SapSyncLog.php`
   - `config/sap.php`

2. Actualizar `.env` con las credenciales SAP

3. Actualizar `app/Services/AutorizacionService.php` con el hook

4. Verificar conectividad final:
```bash
curl -k -X POST https://servidor-sap:50000/b1s/v1/Login \
  -H "Content-Type: application/json" \
  -d '{"CompanyDB":"EMPRESA","UserName":"usuario","Password":"contraseña"}'
# Debe retornar SessionId
```

---

## 8. Pruebas

### Prueba 1 — Conexión a SAP
- Crear una requisición de prueba
- Completar todas las autorizaciones
- Verificar en `sap_sync_log` que el estado sea `exitoso`
- Verificar en SAP que la PO aparece en **Compras → Órdenes de Compra**

### Prueba 2 — Proveedor sin mapeo
- Crear requisición con proveedor que NO está en `sap_proveedores_mapeo`
- El sistema debe marcar `sap_sync_estado = 'error'` en la requisición
- Debe quedar log en `sap_sync_log` con el error

### Prueba 3 — SAP caído
- Desactivar temporalmente la URL de SAP en `.env`
- Aprobar una requisición
- La requisición debe quedar `autorizado` normalmente
- `sap_sync_estado` debe quedar `error`
- Al restaurar SAP, se puede reintentar manualmente

---

## 9. Qué pasa si SAP falla

El diseño es **no bloqueante**: si SAP falla, la requisición sigue marcada como
autorizada. El error queda registrado y se puede reintentar.

### Ver requisiciones con error de sync

```sql
SELECT id, numero_requisicion, proveedor_nombre, sap_sync_error, sap_sync_fecha
FROM requisiciones
WHERE sap_sync_estado = 'error'
ORDER BY updated_at DESC;
```

### Reintentar manualmente (cuando SAP esté disponible)

```sql
-- Marcar para reintento
UPDATE requisiciones SET sap_sync_estado = 'pendiente' WHERE id = ?;
```

Luego ejecutar el proceso de sincronización para esa requisición.

---

## 10. Checklist final antes de activar

### Técnico (desarrollador)
- [ ] ALTERs de tabla ejecutados en producción
- [ ] Tablas `sap_proveedores_mapeo` y `sap_sync_log` creadas
- [ ] `config/sap.php` creado
- [ ] `.env` actualizado con credenciales SAP
- [ ] `SapService.php` creado y probado
- [ ] Hook insertado en `AutorizacionService.php`
- [ ] Conectividad verificada desde servidor → SAP

### Datos (administración)
- [ ] Todos los centros de costo tienen `sap_codigo`
- [ ] Todas las cuentas contables activas tienen `sap_codigo`
- [ ] Todos los proveedores frecuentes están en `sap_proveedores_mapeo`
- [ ] Query de verificación devuelve cero resultados

### Pruebas
- [ ] Prueba con requisición real completada en ambiente de prueba SAP
- [ ] PO aparece correctamente en SAP con montos, centro de costo y cuenta contable
- [ ] Número de PO SAP se guarda en la requisición
- [ ] Escenario de error manejado correctamente

---

*Documento generado para el proyecto Sistema de Requisiciones IGA — Mayo 2026*
