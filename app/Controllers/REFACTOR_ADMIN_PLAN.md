# Plan de Refactorización: AdminController → Sub-controllers

**Fecha:** 2026-03-10
**Archivo analizado:** `app/Controllers/AdminController.php`
**Total de métodos públicos:** 70 (incluyendo `__construct`)
**Total de líneas:** ~4047

---

## Resumen del problema

`AdminController` es un God Object con 70 métodos públicos que cubre responsabilidades radicalmente distintas: dashboard, usuarios, centros de costo, autorizadores (con 3 sub-tipos), respaldos, catálogos, reportes, flujo de autorización, APIs y herramientas de mantenimiento. Esto viola el Principio de Responsabilidad Única y hace el archivo prácticamente imposible de mantener.

---

## Sub-controllers propuestos

### 1. `UsuariosAdminController`
**Namespace:** `App\Controllers\Admin`
**Archivo:** `app/Controllers/Admin/UsuariosAdminController.php`
**Responsabilidad:** CRUD y gestión de usuarios del sistema.

**Métodos (7):**
- `usuarios()` — lista con filtros y paginación
- `showUsuario($id)`
- `editUsuario($id)`
- `updateUsuario($id)`
- `toggleUsuario($id)` — API JSON toggle activo/inactivo
- `desactivarUsuario($id)`
- `apiBuscarUsuarios()` — API autocompletado

---

### 2. `CentrosAdminController`
**Namespace:** `App\Controllers\Admin`
**Archivo:** `app/Controllers/Admin/CentrosAdminController.php`
**Responsabilidad:** CRUD de centros de costo y gestión de relaciones con unidades de negocio.

**Métodos (8):**
- `centrosCosto()` — index
- `createCentro()`
- `showCentro($id)`
- `editCentro($id)`
- `storeCentro()`
- `updateCentro($id)`
- `deleteCentro($id)`
- `relaciones()` — relaciones centro-unidad negocio
- `apiListarCentrosCosto()` — API JSON

---

### 3. `AutorizadoresAdminController`
**Namespace:** `App\Controllers\Admin`
**Archivo:** `app/Controllers/Admin/AutorizadoresAdminController.php`
**Responsabilidad:** CRUD de autorizadores base (por centro de costo), incluyendo consolidación y gestión de centros asignados.

**Métodos (9):**
- `autorizadores()` — index con detección de duplicados
- `showAutorizador($id)`
- `createAutorizador()`
- `editAutorizador($id)`
- `storeAutorizador()`
- `updateAutorizador($id)`
- `deleteAutorizador($id)`
- `editCentrosAutorizador($id)`
- `updateCentrosAutorizador($id)`
- `consolidarAutorizadores()` — acción de limpieza/consolidación
- `apiCentrosCostoAutorizador()` — API JSON
- `limpiarDuplicados()` — herramienta de mantenimiento

Total: **12 métodos**

---

### 4. `RespaldosAdminController`
**Namespace:** `App\Controllers\Admin`
**Archivo:** `app/Controllers/Admin/RespaldosAdminController.php`
**Responsabilidad:** CRUD completo de autorizadores de respaldo (sustitutos temporales).

**Métodos (7):**
- `autorizadoresRespaldos()` — index
- `createRespaldo()`
- `showRespaldo($id)`
- `editRespaldo($id)`
- `storeRespaldo()`
- `updateRespaldo($id)`
- `deleteRespaldo($id)`
- `respaldos()` — alias del método `autorizadoresRespaldos()` (puede eliminarse o mantenerse como delegador)

Total: **8 métodos**

---

### 5. `MetodosPagoAdminController`
**Namespace:** `App\Controllers\Admin`
**Archivo:** `app/Controllers/Admin/MetodosPagoAdminController.php`
**Responsabilidad:** CRUD de autorizadores especiales por método de pago.

**Métodos (10):**
- `autorizadoresMetodosPago()` — index
- `createMetodoPago()`
- `showMetodoPago($id)`
- `showMetodoPagoByEmail($email)`
- `editMetodoPago($id)`
- `editMetodoPagoByEmail($email)`
- `storeMetodoPago()`
- `updateMetodoPago($id)`
- `updateMetodoPagoByEmail($email)`
- `deleteMetodoPago($id)`
- `deleteMetodoPagoByEmail($email)`
- `deleteMetodoPagoLegacy($id)`

Total: **12 métodos**

**Nota:** Contiene el método privado `obtenerAutorizadorMetodoPagoPorEmail()` y `eliminarAutorizadorMetodoPago()` que deben migrar como privados de este controller; y `handleLegacyResponse()` también migra aquí.

---

### 6. `CuentasContablesAdminController`
**Namespace:** `App\Controllers\Admin`
**Archivo:** `app/Controllers/Admin/CuentasContablesAdminController.php`
**Responsabilidad:** CRUD de autorizadores por cuenta contable Y gestión del catálogo de cuentas contables.

**Métodos (8):**
- `autorizadoresCuentasContables()` — index autorizadores
- `createCuentaContable()`
- `showCuentaContable($id)`
- `editCuentaContable($id)`
- `storeCuentaContable()`
- `updateCuentaContable($id)`
- `deleteCuentaContable($id)`
- `catalogos()` — catálogo general (cuentas + centros)
- `toggleCuentaContable($id)`
- `storeCuentaContableCatalogo()`
- `updateCuentaContableCatalogo($id)`
- `deleteCuentaContableCatalogo($id)`

Total: **12 métodos**

**Nota de diseño alternativo:** Si los catálogos de cuentas contables crecen, se puede separar en un `CatalogosAdminController` propio. Por ahora convive aquí ya que los métodos del catálogo operan sobre el mismo modelo `CuentaContable`.

---

### 7. `ReportesAdminController`
**Namespace:** `App\Controllers\Admin`
**Archivo:** `app/Controllers/Admin/ReportesAdminController.php`
**Responsabilidad:** Generación y descarga de reportes en CSV.

**Métodos (6):**
- `reportes()` — vista index
- `reporteEstadoRequisiciones()`
- `reporteGastoCentroCosto()`
- `reporteGastoUnidadRequirente()`
- `reporteTasaRechazo()`
- `reporteFormaPago()`

**Métodos privados a migrar (6):**
- `exportarCSV()`
- `generarArchivoReporte()`
- `generarCSV()`
- `generarCSVUsuarios()`
- `generarCSVRequisiciones()`
- `generarCSVAutorizaciones()`
- `generarCSVFinanciero()`
- `generarPDF()`
- `generarExcel()`
- `contarPorEstado()`

---

### 8. `AdminController` (reducido — conservar)
**Archivo:** `app/Controllers/AdminController.php`
**Responsabilidad:** Dashboard principal y métodos de utilidad compartida entre sub-controllers.

**Métodos conservados (2):**
- `dashboard()`
- `__construct()` — verificación de rol admin

**Métodos privados que permanecen o se extraen a un trait/helper:**
- `getActividadReciente()` — usado solo en dashboard, permanece aquí
- `safeCount()` / `safeMethodCall()` — pueden moverse a un `AdminHelper` o al `Controller` base
- `checkTablesExist()` — puede moverse a `AdminHelper`

---

### Nota: Métodos de flujo de autorización (NO migrar a admin)

Los siguientes métodos de `AdminController` en realidad NO pertenecen a un controller de administración. Implementan lógica de negocio del flujo de autorización y deberían vivir en un **Service** o en `AutorizacionController`:

- `procesarFlujoAutorizacion($requisicion)` — lógica de negocio pura
- `enviarARevisores()` (privado)
- `procesarMetodoPago()` (privado)
- `procesarCuentaContable()` (privado)
- `procesarAutorizacionCentro()` (privado)
- `obtenerRevisores()` (privado)
- `obtenerAutorizadorMetodoPago()` (privado)
- `obtenerAutorizadorCuentaContable()` (privado)
- `verificarCentroExcluido()` (privado)
- `obtenerRespaldoActivo()` (privado)
- `obtenerAutorizadorPrincipalCentroCosto()` (privado)
- `enviarNotificacion()` (privado)
- `registrarNotificacion()` (privado)
- `obtenerRequisicion()` (privado)
- `rechazarMetodoPago()` — marcado como DEPRECADO en el código

**Recomendación:** Moverlos a `AutorizacionService` (ya existe en `app/Services/AutorizacionService.php`). Están desconectados de cualquier ruta en `web.php` y `procesarFlujoAutorizacion()` no tiene ruta HTTP registrada, por lo que son dead code desde el punto de vista del controller.

---

## Cambios en `routes/web.php`

Agregar imports al inicio del bloque de rutas:

```php
use App\Controllers\Admin\UsuariosAdminController;
use App\Controllers\Admin\CentrosAdminController;
use App\Controllers\Admin\AutorizadoresAdminController;
use App\Controllers\Admin\RespaldosAdminController;
use App\Controllers\Admin\MetodosPagoAdminController;
use App\Controllers\Admin\CuentasContablesAdminController;
use App\Controllers\Admin\ReportesAdminController;
// Ya existen:
// use App\Controllers\Admin\RequisicionesController;
// use App\Controllers\Admin\EmailController;
```

Reemplazar referencias en el grupo `/admin`:

| Sección actual | AdminController::método | Nuevo controller |
|---|---|---|
| Dashboard | `dashboard` | `AdminController::dashboard` (sin cambio) |
| Usuarios | `usuarios`, `showUsuario`, `editUsuario`, `updateUsuario`, `toggleUsuario`, `desactivarUsuario` | `UsuariosAdminController` |
| Centros | `centrosCosto`, `createCentro`, `showCentro`, `editCentro`, `storeCentro`, `updateCentro`, `deleteCentro` | `CentrosAdminController` |
| Relaciones | `relaciones` | `CentrosAdminController` |
| Autorizadores base | `autorizadores`, `showAutorizador`, `createAutorizador`, `editAutorizador`, `storeAutorizador`, `updateAutorizador`, `deleteAutorizador`, `editCentrosAutorizador`, `updateCentrosAutorizador`, `consolidarAutorizadores`, `limpiarDuplicados` | `AutorizadoresAdminController` |
| Respaldos | `autorizadoresRespaldos`, `createRespaldo`, `showRespaldo`, `editRespaldo`, `storeRespaldo`, `updateRespaldo`, `deleteRespaldo` + rutas legacy `/respaldos/*` | `RespaldosAdminController` |
| Métodos de pago | `autorizadoresMetodosPago`, `createMetodoPago`, `showMetodoPago`, `editMetodoPago`, `storeMetodoPago`, `updateMetodoPago`, `deleteMetodoPago`, `showMetodoPagoByEmail`, `editMetodoPagoByEmail`, `updateMetodoPagoByEmail`, `deleteMetodoPagoByEmail`, `deleteMetodoPagoLegacy` | `MetodosPagoAdminController` |
| Cuentas contables | `autorizadoresCuentasContables`, `createCuentaContable`, `showCuentaContable`, `editCuentaContable`, `storeCuentaContable`, `updateCuentaContable`, `deleteCuentaContable` | `CuentasContablesAdminController` |
| Catálogos | `catalogos`, `storeCuentaContableCatalogo`, `updateCuentaContableCatalogo`, `toggleCuentaContable`, `deleteCuentaContableCatalogo` | `CuentasContablesAdminController` |
| Reportes | `reportes`, `reporteEstadoRequisiciones`, `reporteGastoCentroCosto`, `reporteGastoUnidadRequirente`, `reporteTasaRechazo`, `reporteFormaPago` | `ReportesAdminController` |

**Rutas que NO tienen entrada en web.php (agregar si se necesitan):**
- `consolidarAutorizadores` — solo mencionada en vista, falta la ruta POST
- `limpiarDuplicados` — falta ruta POST
- `editCentrosAutorizador` / `updateCentrosAutorizador` — faltan rutas GET/PUT
- `apiBuscarUsuarios` — falta ruta GET
- `apiListarCentrosCosto` — falta ruta GET
- `apiCentrosCostoAutorizador` — falta ruta GET
- `respaldos()` (alias) — convive con `autorizadoresRespaldos` pero son duplicados funcionales

---

## Estructura de directorios resultante

```
app/Controllers/
├── AdminController.php              ← reducido a dashboard + __construct
├── Admin/
│   ├── UsuariosAdminController.php
│   ├── CentrosAdminController.php
│   ├── AutorizadoresAdminController.php
│   ├── RespaldosAdminController.php
│   ├── MetodosPagoAdminController.php
│   ├── CuentasContablesAdminController.php
│   ├── ReportesAdminController.php
│   ├── RequisicionesController.php  ← ya existe
│   └── EmailController.php          ← ya existe
```

---

## Orden de implementación (mínimo riesgo)

### Fase 1 — Los más independientes y con menos interdependencias
**1. `ReportesAdminController`**
- Razón: 6 métodos completamente aislados, sin dependencias entre sí ni con otros módulos de admin. Solo usan `Requisicion::query()` y el helper privado `exportarCSV()`. Sin efectos secundarios en BD.
- Los métodos privados de generación CSV migran íntegros.
- Riesgo: muy bajo.

**2. `UsuariosAdminController`**
- Razón: solo depende del modelo `Usuario` y `Requisicion`. No comparte estado con autorizadores ni centros.
- El método `apiBuscarUsuarios()` no tiene ruta registrada actualmente — al migrar, agregar la ruta GET.
- Riesgo: bajo.

### Fase 2 — Módulos con dependencias simples hacia modelos
**3. `CentrosAdminController`**
- Razón: depende de `CentroCosto` y `UnidadNegocio`. Usado como referencia por autorizadores, pero no al revés.
- `deleteCentro()` contiene queries directas a `distribucion_gasto` — verificar que funcionen igual.
- Riesgo: bajo-medio.

**4. `CatalogosAdminController` / parte de `CuentasContablesAdminController`**
- Las operaciones del catálogo (`storeCuentaContableCatalogo`, etc.) son independientes.
- Riesgo: bajo.

### Fase 3 — Autorizadores (mayor interdependencia interna)
**5. `RespaldosAdminController`**
- Razón: usa `AutorizadorRespaldo` y `CentroCosto`. Independiente de los otros módulos de autorizadores.
- Tiene rutas legacy duplicadas (`/respaldos/*`) que deben mantenerse apuntando al mismo controller.
- Riesgo: medio.

**6. `MetodosPagoAdminController`**
- Razón: usa `AutorizadorMetodoPago` y queries directas. Tiene alias de métodos (`editMetodoPago` → `editMetodoPagoByEmail`, `updateMetodoPago` → `storeMetodoPago`) que deben preservarse.
- `handleLegacyResponse()` es un método privado que migra aquí.
- Riesgo: medio.

**7. `CuentasContablesAdminController`**
- Razón: combina dos responsabilidades (autorizadores-cuenta-contable + catálogo de cuentas). Migrar juntas para evitar que `catalogos()` quede huérfano.
- `deleteCuentaContable()` tiene lógica compleja por ID numérico vs email.
- Riesgo: medio.

**8. `AutorizadoresAdminController`**
- Razón: el más complejo. Tiene queries directas, transacciones, y métodos que afectan a `autorizador_centro_costo`. Dejarlo para el final permite que los otros ya estén estables.
- `consolidarAutorizadores()` y `limpiarDuplicados()` tienen transacciones largas — revisar manejo de errores.
- Riesgo: medio-alto.

### Fase 4 — Limpieza final
**9. Reducir `AdminController`** a solo `__construct()` + `dashboard()`.
**10. Mover lógica de flujo** (`procesarFlujoAutorizacion` y sus privados) a `AutorizacionService`.
**11. Eliminar métodos privados huérfanos** (`safeCount`, `safeMethodCall`, `checkTablesExist`) o moverlos a un `AdminHelper`.

---

## Riesgos y mitigaciones

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Las vistas usan `action=""` con rutas hardcoded — no afecta la migración de controllers | Bajo | Las URLs no cambian, solo el controller que las atiende |
| `respaldos()` y `autorizadoresRespaldos()` son dos métodos que hacen lo mismo; hay rutas legacy `/respaldos/*` y nuevas `/autorizadores/respaldos/*` apuntando al mismo controller | Medio | Ambos grupos de rutas deben apuntar a `RespaldosAdminController`; el método `respaldos()` puede eliminarse o ser un alias |
| `editMetodoPago($id)` delega a `editMetodoPagoByEmail()` y `updateMetodoPago($id)` delega a `storeMetodoPago()` — son alias internos | Bajo | Al migrar, preservar exactamente esta delegación en el nuevo controller |
| `showCuentaContable($id)` y `showRespaldo($id)` usan datos hardcoded (placeholder), no BD real | Bajo | Documentar como deuda técnica; no bloquea la migración |
| `storeCuentaContable()` solo hace redirect sin lógica real — placeholder incompleto | Bajo | Documentar; migrar tal cual |
| `procesarFlujoAutorizacion()` y sus ~15 métodos privados no tienen ruta HTTP registrada — son dead code en el controller | Medio | No migrar a ningún sub-controller de admin; evaluar si ya existe implementación equivalente en `AutorizacionService` |
| `limpiarDuplicados()` y `consolidarAutorizadores()` no tienen rutas HTTP registradas en `web.php` | Medio | Al crear `AutorizadoresAdminController`, registrar las rutas faltantes o confirmar si se acceden via JS directo |
| El constructor de `AdminController` hace redirect si no es admin — cada sub-controller debe heredar o replicar esta verificación | Alto | Crear una clase base `AdminBaseController extends Controller` con el check en `__construct`, y heredar de ella en todos los sub-controllers |
| Autoloader de Composer / PSR-4 — el subdirectorio `Admin/` debe estar mapeado | Alto | Verificar `composer.json` que el namespace `App\Controllers\Admin` esté cubierto por el autoload PSR-4 existente (normalmente `App\\` → `app/` ya lo cubre) |
| `apiBuscarUsuarios()`, `apiListarCentrosCosto()`, `apiCentrosCostoAutorizador()` no tienen rutas registradas | Bajo | Son APIs llamadas desde JS; buscar en vistas JS si se usan. Al migrar, agregar las rutas faltantes |

---

## Recomendación de clase base

Crear `app/Controllers/Admin/AdminBaseController.php`:

```php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Helpers\Session;
use App\Helpers\Redirect;

abstract class AdminBaseController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!Session::isAdmin()) {
            Redirect::to('/dashboard')
                ->withError('No tienes permisos de administrador')
                ->send();
        }
    }
}
```

Todos los sub-controllers heredan de `AdminBaseController` en lugar de `Controller`.

---

## Resumen de conteo

| Sub-controller | Métodos públicos | Líneas estimadas |
|---|---|---|
| `AdminController` (reducido) | 2 | ~150 |
| `UsuariosAdminController` | 7 | ~300 |
| `CentrosAdminController` | 9 | ~280 |
| `AutorizadoresAdminController` | 12 | ~500 |
| `RespaldosAdminController` | 8 | ~350 |
| `MetodosPagoAdminController` | 12 | ~550 |
| `CuentasContablesAdminController` | 12 | ~450 |
| `ReportesAdminController` | 6 | ~280 |
| **Total** | **68** | **~2860** |

El archivo original tiene ~4047 líneas; la diferencia (~1200 líneas) corresponde a los métodos de flujo de autorización que se moverán a `AutorizacionService` y al código de infraestructura compartida (helpers, clase base).
