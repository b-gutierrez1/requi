# 📋 Guía: Ver Tareas Pendientes

## 🎯 Descripción General

El sistema de requisiciones cuenta con una interfaz completa para visualizar todas las tareas pendientes de autorización. Esta funcionalidad permite a los usuarios ver de manera centralizada todas las requisiciones que requieren su acción.

---

## 🔗 Acceso a Tareas Pendientes

### URL Principal
```
/autorizaciones
```

**Acceso desde el Dashboard:**
- Navega a la página principal del sistema
- En el dashboard encontrarás widgets con el conteo de autorizaciones pendientes
- Haz clic en "Ver Autorizaciones Pendientes" o accede directamente a `/autorizaciones`

---

## 👥 Tipos de Usuarios y sus Vistas

### 1. **Revisores** 📝
Los revisores ven requisiciones que necesitan revisión inicial antes de pasar al flujo de autorización.

**Visualizan:**
- Requisiciones con estado: `pendiente_revision`
- Botón: "Revisar"
- Color: Azul (Primary)

**Acciones disponibles:**
- ✅ Aprobar revisión
- ❌ Rechazar revisión

### 2. **Autorizadores por Centro de Costo** 🏢
Usuarios asignados como autorizadores de centros de costo específicos.

**Visualizan:**
- Autorizaciones pendientes para sus centros asignados
- Porcentaje de gasto asignado al centro
- Botón: "Autorizar"
- Color: Amarillo (Warning)

**Información mostrada:**
- Centro de costo asignado
- Porcentaje de la requisición asignado al centro
- Datos del proveedor y monto total

### 3. **Autorizadores Especiales - Forma de Pago** 💳
Usuarios que autorizan métodos de pago específicos (ej: Tarjeta de Crédito).

**Visualizan:**
- Autorizaciones pendientes de forma de pago
- Método de pago a autorizar
- Botón: "Autorizar"
- Color: Verde (Success)

**Información mostrada:**
- Forma de pago requerida
- Datos de la requisición
- Monto total

### 4. **Autorizadores Especiales - Cuenta Contable** 🧾
Usuarios que autorizan cuentas contables específicas (ej: "Donaciones por aplicar").

**Visualizan:**
- Autorizaciones pendientes de cuenta contable
- Cuenta contable a autorizar
- Botón: "Autorizar"
- Color: Azul claro (Info)

**Información mostrada:**
- Cuenta contable requerida
- Datos de la requisición
- Monto total

### 5. **Autorizadores de Respaldo** 🛡️
Usuarios configurados como respaldo de otros autorizadores.

**Visualizan:**
- Badge especial: "Autorizador de Respaldo"
- Todas las autorizaciones de los autorizadores principales que respaldan
- Pueden actuar cuando el autorizador principal no está disponible

---

## 📊 Estructura de la Vista

### Sección 1: Header
```
┌─────────────────────────────────────────┐
│ 📋 Mis Autorizaciones Pendientes        │
│ Requisiciones que requieren tu acción   │
│ [5 pendientes]                          │
│                    [Ver Historial] ──→  │
└─────────────────────────────────────────┘
```

### Sección 2: Requisiciones Pendientes de Revisión
*(Solo visible para revisores)*

```
┌─────────────────────────────────────────┐
│ 📝 Requisiciones Pendientes de Revisión │
│                      [Ver Todas] ──→    │
├─────────────────────────────────────────┤
│ 📄 Requisición #123                     │
│ Solicitante: Juan Pérez                 │
│ Proveedor: Proveedor ABC                │
│ Monto: Q1,500.00                        │
│                        [Revisar] ──→    │
└─────────────────────────────────────────┘
```

### Sección 3: Autorizaciones Unificadas
*(Agrupa todas las autorizaciones pendientes del usuario)*

```
┌─────────────────────────────────────────┐
│ ✅ Mis Autorizaciones Pendientes        │
├─────────────────────────────────────────┤
│ 💳 [Especial - Forma de Pago]          │
│ 📄 Requisición #124                     │
│ Forma de Pago: Tarjeta de Crédito      │
│ Proveedor: Proveedor XYZ                │
│ Monto: Q2,300.00                        │
│                      [Autorizar] ──→    │
├─────────────────────────────────────────┤
│ 🏢 [Centro de Costo]                    │
│ Centro: Ventas (45%)                    │
│ 📄 Requisición #125                     │
│ Proveedor: Proveedor DEF                │
│ Monto: Q1,800.00                        │
│                      [Autorizar] ──→    │
└─────────────────────────────────────────┘
```

### Sección 4: Sin Tareas Pendientes
```
┌─────────────────────────────────────────┐
│           ✅✅                           │
│         ¡Excelente!                     │
│ No tienes autorizaciones pendientes     │
│      en este momento.                   │
└─────────────────────────────────────────┘
```

---

## 🎨 Códigos de Color

| Tipo de Autorización | Color | Badge |
|---------------------|-------|-------|
| Revisión Inicial | 🔵 Azul | Primary |
| Forma de Pago | 🟢 Verde | Success |
| Cuenta Contable | 🔵 Azul claro | Info |
| Centro de Costo | 🟡 Amarillo | Warning |

---

## 🔄 Flujo de Acciones

### Para Revisar una Requisición:
1. Accede a `/autorizaciones`
2. Localiza la requisición pendiente de revisión
3. Haz clic en "Revisar"
4. Revisa los detalles completos de la requisición
5. Decide:
   - ✅ **Aprobar**: La requisición pasa al siguiente nivel
   - ❌ **Rechazar**: La requisición se marca como rechazada

### Para Autorizar:
1. Accede a `/autorizaciones`
2. Localiza la autorización pendiente
3. Haz clic en "Autorizar"
4. Revisa los detalles de la requisición
5. Decide:
   - ✅ **Autorizar**: Apruebas tu parte del flujo
   - ❌ **Rechazar**: Rechazas la requisición

---

## 📱 Funcionalidades Adicionales

### Vista Detallada
Al hacer clic en cualquier requisición, accedes a una vista detallada con:
- Información completa de la requisición
- Detalles del proveedor
- Distribución de gastos por centro de costo y cuenta contable
- Archivos adjuntos
- Historial de autorizaciones
- Estado actual del flujo

### Historial
```
/autorizaciones/historial
```
Muestra todas las autorizaciones que has procesado previamente (aprobadas o rechazadas).

---

## 🔍 Información Mostrada por Tipo

### Requisición Pendiente de Revisión
- Número de requisición
- Nombre del solicitante
- Nombre del proveedor
- Monto total
- Fecha de creación

### Autorización por Centro de Costo
- Número de requisición
- Centro de costo asignado
- Porcentaje del gasto
- Nombre del proveedor
- Monto total
- Fecha de creación

### Autorización Especial (Pago/Cuenta)
- Número de requisición
- Tipo de autorización especial
- Método de pago o cuenta contable
- Nombre del proveedor
- Monto total
- Fecha de creación

---

## 🎯 Indicadores Visuales

### Badges y Etiquetas
- **[Especial - Forma de Pago]**: Verde
- **[Especial - Cuenta Contable]**: Azul claro
- **[Centro de Costo]**: Amarillo
- **[Revisión]**: Azul
- **[Respaldo]** 🛡️: Gris

### Iconos
- 📝 Revisión
- 💳 Forma de pago
- 🧾 Cuenta contable
- 🏢 Centro de costo
- 🛡️ Autorizador de respaldo

---

## 🔧 Aspectos Técnicos

### Controlador
```php
AutorizacionController::index()
Ubicación: app/Controllers/AutorizacionController.php
```

### Vista
```php
Ubicación: app/Views/autorizaciones/index.php
```

### Servicio
```php
AutorizacionService
Métodos principales:
- getRequisicionesPendientesRevision()
- getAutorizacionesPendientes($email)
- getTodasAutorizacionesPendientes($email)
- getTipoAutorizador($email)
- esAutorizadorRespaldo($email)
```

### Rutas
```php
GET /autorizaciones              → Lista todas las pendientes
GET /autorizaciones/{id}         → Detalle de una requisición
GET /autorizaciones/historial    → Historial de autorizaciones
```

---

## 📊 Contadores y Estadísticas

En la parte superior de la vista se muestra:
- **Total de pendientes**: Suma de todas las tareas pendientes
- Badge con el número total destacado

---

## 🚀 Mejores Prácticas

1. **Revisa regularmente**: Accede frecuentemente a `/autorizaciones` para mantener el flujo ágil
2. **Prioriza por fecha**: Las requisiciones más antiguas aparecen primero
3. **Usa filtros**: Si hay muchas pendientes, usa los filtros disponibles
4. **Revisa detalles**: Siempre revisa los detalles completos antes de autorizar
5. **Documenta rechazos**: Al rechazar, proporciona comentarios claros

---

## ❓ Preguntas Frecuentes

**P: ¿Por qué no veo ninguna tarea pendiente?**
R: Puede ser que no tengas requisiciones asignadas actualmente o que no estés configurado como autorizador.

**P: ¿Puedo ver tareas pendientes de otros usuarios?**
R: No, solo ves las tareas asignadas a tu email según tu rol y configuración.

**P: ¿Qué significa "Autorizador de Respaldo"?**
R: Estás configurado como respaldo de otro autorizador y puedes actuar en su ausencia.

**P: ¿Cómo sé qué tipo de autorizador soy?**
R: En la vista de autorizaciones se muestra un badge con tu tipo (Centro, Pago, Cuenta, etc.).

---

## 📞 Soporte

Si tienes problemas para ver tus tareas pendientes o crees que deberías tener autorizaciones que no aparecen:
1. Verifica que estés logueado con el email correcto
2. Contacta al administrador del sistema
3. Revisa la configuración de autorizadores en el módulo de administración

---

*Documento generado: 2025-11-11*
*Versión del sistema: 2.0*
