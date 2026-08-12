# Precisión decimal en importes

## La decisión

**El precio unitario, el total de cada ítem y el monto total de la requisición se manejan
con 2 decimales**, tanto en pantalla como al guardar.

### Por qué 2 y no más

1. **Las monedas soportadas son de 2 decimales.** GTQ, USD y EUR no tienen unidades
   menores al centavo. Un precio de `12.34567` no es representable en una factura real.
2. **Las facturas se guardan con 2 decimales** (`facturas.monto_factura` es
   `decimal(15,2)`). Cualquier decimal extra en el precio se pierde al generar las
   facturas automáticas y hace que el `monto_total` de la requisición deje de cuadrar
   contra la suma de sus facturas.
3. **La cantidad es un entero**, así que `cantidad × precio` con precio de 2 decimales
   siempre da un resultado exacto en centavos. No hay redondeo que perder.

## Dónde se aplica

| Capa | Qué hace |
|---|---|
| Formulario (`create.php`, `edit.php`) | Los inputs de precio unitario son `step="0.01"` `min="0"`. Aplica a la fila inicial estática **y** a la plantilla JavaScript que agrega filas nuevas. |
| JavaScript | `redondear2()` redondea explícitamente a 2 decimales en cada paso: total del ítem, acumulado del total general y monto de cada línea de distribución. Se redondea el acumulado en cada suma, no solo al final, para evitar el clásico `0.1 + 0.2 = 0.30000000000000004`. |
| Servidor (`RequisicionService::procesarDatosRequisicion`) | Vuelve a normalizar cantidad (entero) y precio (2 decimales) y **recalcula** el total. Nunca se confía en lo que envió el navegador; el `monto_total` que se guarda es la suma de los totales ya redondeados, no el valor del campo oculto. |
| Modelo (`DetalleItem`) | `DetalleItem::normalizarCantidad()` y `DetalleItem::normalizarMonto()` son el punto único de verdad. `DECIMALES_MONEDA = 2`. |

## Por qué la BD tiene más decimales (y por qué no importa)

| Columna | Tipo |
|---|---|
| `detalle_items.precio_unitario` | `decimal(12,5)` |
| `detalle_items.total` | `decimal(12,5)` |
| `detalle_items.cantidad` | `int(11)` |
| `requisiciones.monto_total` | `decimal(15,5)` |
| `distribucion_gasto.cantidad` | `decimal(12,5)` |
| `facturas.monto_factura` | `decimal(15,2)` |

Los 5 decimales **no se cambian**: sirven de colchón para los cálculos intermedios de la
distribución por porcentaje, donde `porcentaje` admite hasta 5 decimales
(`step="0.00001"`) y repartos como 1/3 no son exactos en centavos. Guardar el importe de
la línea con más precisión evita amplificar el error de redondeo en pasos posteriores.
De cara al usuario ese colchón nunca se muestra: todo se presenta y valida con 2
decimales.

## Limitación conocida: la cantidad es entera

`detalle_items.cantidad` es `int(11)`. **No se pueden pedir cantidades fraccionarias**
(2.5 kg, 1.5 horas, 0.75 m). Si se necesita algo así hay dos alternativas sin tocar el
esquema:

- Cambiar la unidad para que la cantidad sea entera (2500 g en vez de 2.5 kg).
- Pedir 1 unidad y poner el importe completo en el precio unitario.

El servidor normaliza la cantidad con `(int) round(...)`, de modo que el total calculado
en PHP siempre coincide con lo que la BD termina guardando. Antes se calculaba en PHP con
el valor fraccionario y la BD truncaba, produciendo un `total` guardado que no
correspondía a `cantidad × precio_unitario`.

## Descuadre conocido: distribución y facturas

Cuando el porcentaje de una línea no reparte exacto en centavos (por ejemplo 3 líneas al
33.33333% de Q 100.00), la suma de los importes redondeados de las líneas puede quedar a
uno o dos centavos del `monto_total`. Lo mismo aplica a las facturas automáticas, que se
guardan con 2 decimales (`RequisicionService::generarFacturasAutomaticas`).

Esto **no** se origina en el precio unitario: los ítems siempre cuadran exactamente. Es
inherente a repartir un importe por porcentaje. Corregirlo requiere asignar el residuo a
una de las líneas (típicamente la mayor o la última), lo que cambia la lógica de
distribución y está pendiente como decisión aparte. Las validaciones actuales toleran
±0.01.
