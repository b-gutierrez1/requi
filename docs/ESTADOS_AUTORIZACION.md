# Estados de Autorización - Guía de Desarrollo

## ⚠️ IMPORTANTE: Uso de Estados Consistentes

Para evitar problemas como el que ocurrió con la requisición 3, **SIEMPRE** usa las constantes definidas en lugar de strings hardcodeados.

## Estados Oficiales

Definidos en `AutorizacionFlujo.php`:

```php
const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';
const ESTADO_RECHAZADO_REVISION = 'rechazado_revision';
const ESTADO_PENDIENTE_AUTORIZACION_PAGO = 'pendiente_autorizacion_pago';
const ESTADO_PENDIENTE_AUTORIZACION_CUENTA = 'pendiente_autorizacion_cuenta';
const ESTADO_PENDIENTE_AUTORIZACION_CENTROS = 'pendiente_autorizacion_centros';
const ESTADO_RECHAZADO_AUTORIZACION = 'rechazado_autorizacion';
const ESTADO_AUTORIZADO = 'autorizado';
const ESTADO_RECHAZADO = 'rechazado';
```

## ✅ Uso Correcto

```php
// BIEN - Usa constantes
use App\Models\AutorizacionFlujo;

$sql = "WHERE af.estado = ?";
$stmt->execute([AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_CENTROS]);

// BIEN - En condiciones
if ($flujo->estado === AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_CENTROS) {
    // lógica para autorizaciones de centros
}
```

## ❌ Uso Incorrecto

```php
// MAL - Strings hardcodeados
$sql = "WHERE af.estado = 'pendiente_autorizacion'";  // ¡INCORRECTO!
$sql = "WHERE af.estado = 'pendiente_autorizacion_centros'";  // Propenso a errores de tipeo

// MAL - En condiciones
if ($flujo->estado === 'pendiente_autorizacion') {  // ¡INCORRECTO!
    // lógica
}
```

## Flujo de Estados

```
pendiente_revision
     ↓ (aprobada)
pendiente_autorizacion_pago (si requiere)
     ↓ (autorizada)
pendiente_autorizacion_cuenta (si requiere)
     ↓ (autorizada)
pendiente_autorizacion_centros (siempre)
     ↓ (todas autorizadas)
autorizado
```

## Problema Identificado y Solucionado

### El Bug
- **Fecha:** 2025-11-10
- **Requisición afectada:** #3
- **Problema:** `AutorizacionCentroCosto::pendientesPorAutorizador()` buscaba estado `'pendiente_autorizacion'` pero el estado real era `'pendiente_autorizacion_centros'`
- **Resultado:** Las autorizaciones no aparecían en la vista

### La Solución
- **Archivo corregido:** `app/Models/AutorizacionCentroCosto.php` líneas 145 y 175
- **Cambio:** `'pendiente_autorizacion'` → `'pendiente_autorizacion_centros'`

## Prevención de Futuros Problemas

### 1. Usar Constantes Siempre
```php
// En lugar de esto:
WHERE estado = 'pendiente_autorizacion_centros'

// Usa esto:
WHERE estado = '" . AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_CENTROS . "'
```

### 2. Validación en Modelos
Considera agregar validación en los modelos:

```php
public static function validarEstado($estado) {
    $estadosValidos = [
        self::ESTADO_PENDIENTE_REVISION,
        self::ESTADO_PENDIENTE_AUTORIZACION_CENTROS,
        self::ESTADO_AUTORIZADO,
        // ... otros estados
    ];
    
    if (!in_array($estado, $estadosValidos)) {
        throw new InvalidArgumentException("Estado inválido: $estado");
    }
}
```

### 3. Tests Unitarios
Crea tests que verifiquen:
- Que las consultas usen estados válidos
- Que el flujo de estados funcione correctamente
- Que las autorizaciones aparezcan en las vistas

### 4. Code Review
En el code review, verifica:
- ✅ Uso de constantes en lugar de strings
- ✅ Estados correctos en consultas SQL
- ✅ Consistencia entre diferentes archivos

## Archivos Críticos a Revisar

Estos archivos contienen referencias a estados y deben revisarse al hacer cambios:

1. `app/Models/AutorizacionFlujo.php` - Definición de constantes
2. `app/Models/AutorizacionCentroCosto.php` - Consultas de autorizaciones
3. `app/Controllers/AutorizacionController.php` - Lógica de autorización
4. `app/Services/AutorizacionService.php` - Servicios de autorización
5. `app/Helpers/EstadoHelper.php` - Helper de estados
6. Vistas en `app/Views/autorizaciones/` - Visualización

## Comando de Verificación

Ejecuta este comando para buscar strings hardcodeados:

```bash
grep -r "pendiente_autorizacion[^_]" app/
```

Si encuentra resultados, revisa que usen las constantes correctas.