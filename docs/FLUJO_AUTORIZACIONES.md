# 🔄 FLUJO DE AUTORIZACIONES - Sistema de Requisiciones

## 📋 Resumen Ejecutivo

El sistema maneja un **flujo secuencial de autorizaciones** con múltiples niveles que una requisición debe pasar antes de ser completamente autorizada.

---

## 🎯 Niveles del Flujo de Autorización

### Nivel 1: 📝 REVISIÓN INICIAL
**Estado:** `pendiente_revision`
- **Quién:** Revisor designado (configurado en BD)
- **Qué verifica:** Validez, completitud de datos, coherencia
- **Puede:** Aprobar o Rechazar
- **Si aprueba →** Pasa al siguiente nivel

### Nivel 2: 💳 AUTORIZACIÓN ESPECIAL - FORMA DE PAGO (Opcional)
**Estado:** `pendiente_autorizacion_pago`
- **Cuándo:** Solo si la forma de pago requiere autorización especial
- **Ejemplo:** Tarjeta de Crédito (Lic. Milton)
- **Quién:** Autorizador específico configurado en `autorizadores_metodos_pago`
- **Si aprueba →** Pasa al siguiente nivel

### Nivel 3: 🧾 AUTORIZACIÓN ESPECIAL - CUENTA CONTABLE (Opcional)
**Estado:** `pendiente_autorizacion_cuenta`
- **Cuándo:** Solo si alguna cuenta contable usada requiere autorización especial
- **Ejemplo:** Cuenta "Donaciones por aplicar" requiere autorización especial
- **Quién:** Autorizador específico configurado en `autorizadores_cuentas_contables`
- **Opción especial:** Puede tener flag `ignorar_centro_costo = 1`
  - Si está activo → Salta directo a AUTORIZADO (no pasa por centros de costo)
  - Si no → Continúa al siguiente nivel

### Nivel 4: 🏢 AUTORIZACIÓN POR CENTROS DE COSTO
**Estado:** `pendiente_autorizacion_centros`
- **Cuándo:** Siempre (a menos que cuenta contable tenga `ignorar_centro_costo = 1`)
- **Cómo funciona:**
  - Se crean autorizaciones individuales para CADA centro de costo involucrado
  - Cada centro de costo tiene su propio autorizador asignado
  - TODOS los centros deben ser autorizados para completar la requisición

### Nivel 5: ✅ AUTORIZADO
**Estado:** `autorizado`
- **Cuándo:** Todos los niveles anteriores fueron aprobados
- **Resultado:** Requisición completamente autorizada

---

## 🔀 Diagrama del Flujo

```
[CREAR REQUISICIÓN]
        ↓
[REVISIÓN INICIAL]
    ↙         ↘
RECHAZAR    APROBAR
  (FIN)         ↓
        ¿Requiere Autorización
         de Forma de Pago?
            ↙         ↘
          NO          SÍ → [AUTH. FORMA DE PAGO]
            ↓                   ↙         ↘
    ¿Requiere                RECHAZAR   APROBAR
     Autorización               (FIN)       ↓
     de Cuenta?           ¿Requiere Autorización
         ↙      ↘          de Cuenta?
       NO        SÍ                ↙      ↘
        ↓         ↓               NO       SÍ
        ↓    [AUTH. CUENTA                 ↓
        ↓     CONTABLE]              [AUTH. CUENTA
        ↓     ↙      ↘               CONTABLE]
        ↓  RECHAZAR  APROBAR          ↙      ↘
        ↓   (FIN)      ↓          RECHAZAR  APROBAR
        ↓              ↓           (FIN)      ↓
        ↓     ¿Ignora Centros?              ↓
        ↓      ↙          ↘                 ↓
        ↓    SÍ → [AUTORIZADO]   ¿Ignora Centros?
        ↓              (FIN)        ↙          ↘
        ↓                        NO            SÍ
        ↓                         ↓             ↓
        └─────────────→ [AUTH. CENTROS]   [AUTORIZADO]
                       DE COSTO              (FIN)
                    (Uno por cada
                      centro)
                    ↙         ↘
              RECHAZAR     TODOS
               (FIN)      APRUEBAN
                             ↓
                      [AUTORIZADO]
                          (FIN)
```

---

## 🏗️ Cómo se Crean las Autorizaciones por Centro de Costo

**Función:** `AutorizacionFlujo::crearAutorizacionesCentrosCosto()`

### Proceso:

1. **Obtener distribuciones de gasto** de la requisición
   - Cada línea de distribución tiene: cuenta contable, centro costo, porcentaje

2. **Agrupar por centro de costo**
   - Sumar los porcentajes de todas las líneas del mismo centro
   - Ejemplo: Si Ventas aparece 3 veces, suma sus porcentajes

3. **Para cada centro de costo:**
   - **Buscar autorizador** en tabla `autorizador_centro_costo`
   - **Filtros:**
     - `centro_costo_id` = ID del centro
     - `es_principal = 1` (autorizador principal)
     - `activo = 1`
   - **Crear registro** en `autorizacion_centro_costo`:
     ```php
     {
       autorizacion_flujo_id: ID del flujo,
       centro_costo_id: ID del centro,
       autorizador_email: email del autorizador encontrado,
       porcentaje: % total para este centro,
       estado: 'pendiente'
     }
     ```

4. **Si NO se encuentra autorizador:**
   - Busca en tabla `persona_autorizada` (vista)
   - Busca por `centro_costo_id` y `activo = 1`

5. **Si aún no hay autorizador:**
   - Usa autorizador por defecto: `"administracion@iga.edu"`

---

## 🎭 Determinación de Autorizadores

### Para Forma de Pago:
```sql
SELECT autorizador_email 
FROM autorizadores_metodos_pago
WHERE metodo_pago = ? 
  AND activo = 1
LIMIT 1
```

### Para Cuenta Contable:
```sql
SELECT autorizador_email, ignorar_centro_costo
FROM autorizadores_cuentas_contables
WHERE cuenta_contable_id = ? 
  AND activo = 1
LIMIT 1
```

### Para Centro de Costo:
```sql
-- Opción 1: Tabla principal
SELECT autorizador_email
FROM autorizador_centro_costo acc
INNER JOIN autorizadores a ON acc.autorizador_id = a.id
WHERE acc.centro_costo_id = ?
  AND acc.es_principal = 1
  AND acc.activo = 1
LIMIT 1

-- Opción 2: Vista (fallback)
SELECT email
FROM persona_autorizada
WHERE centro_costo_id = ?
  AND activo = 1
LIMIT 1
```

---

## 📊 Tablas Clave del Sistema

| Tabla | Propósito |
|-------|-----------|
| `autorizacion_flujo` | **Flujo principal** - Un registro por requisición |
| `autorizacion_centro_costo` | **Autorizaciones individuales** - Una por cada centro |
| `autorizadores_metodos_pago` | Config: Quién autoriza cada método de pago |
| `autorizadores_cuentas_contables` | Config: Quién autoriza cada cuenta |
| `autorizador_centro_costo` | Config: Quién autoriza cada centro |
| `autorizador_cuenta_exclusiones` | **NUEVA:** Centros excluidos por cuenta |

---

## 🆕 Nueva Funcionalidad: Exclusiones de Centros de Costo

### Problema a Resolver:
Una cuenta contable especial (ej: "Donaciones") no debe requerir autorización del centro de costo en ciertos casos específicos.

### Solución Implementada:

1. **Tabla `autorizador_cuenta_exclusiones`:**
   ```sql
   - autorizador_cuenta_id (referencia a config de cuenta)
   - centro_costo_id (centro a excluir)
   - motivo (razón de la exclusión)
   ```

2. **Lógica (pendiente de implementar en código):**
   ```
   AL CREAR AUTORIZACIONES POR CENTRO:
   
   Para cada centro de costo en la distribución:
     SI existe exclusión para (cuenta_contable_id, centro_costo_id):
       → NO crear autorización para ese centro
       → Continuar con el siguiente
     SINO:
       → Crear autorización normal
   ```

3. **Ejemplo de Uso:**
   - Cuenta: "Donaciones por aplicar" (ID 336)
   - Autorizador: bgutierrez@sp.iga.edu
   - Centros excluidos: 
     - Centro "Marketing" (ID 15)
     - Centro "Ventas Z4" (ID 22)
   
   **Resultado:**
   - Si la requisición usa cuenta "Donaciones" en centro "Marketing"
     → NO se crea autorización para ese centro
     → bgutierrez autoriza solo la cuenta contable
   - Si usa "Donaciones" en centro "Finanzas"
     → SÍ se crea autorización para "Finanzas"

---

## 📈 Estados del Flujo

| Estado | Descripción |
|--------|-------------|
| `pendiente_revision` | Esperando revisión inicial |
| `rechazado_revision` | Rechazado en revisión |
| `pendiente_autorizacion_pago` | Esperando auth. forma de pago |
| `pendiente_autorizacion_cuenta` | Esperando auth. cuenta contable |
| `pendiente_autorizacion_centros` | Esperando auth. de centros |
| `rechazado_autorizacion` | Rechazado por autorizador |
| `autorizado` | Completamente autorizado ✅ |

---

## ⚠️ Puntos Importantes

1. **Secuencial:** Cada nivel debe completarse antes del siguiente
2. **Opcional:** Niveles de forma de pago y cuenta solo si aplican
3. **Paralelo:** Autorizaciones de centros se crean todas juntas pero cada autorizador actúa independientemente
4. **Threshold:** Si TODOS los centros aprueban → Flujo pasa a `autorizado`
5. **Rechazos:** Un rechazo en cualquier nivel termina el flujo

---

*Documento generado el: {{DATE}}*
*Versión del sistema: 2.0*



