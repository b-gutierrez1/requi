# Precisión decimal en importes

## La decisión

**El precio unitario admite 3 decimales. Todos los importes —el total de cada ítem y el
monto total de la requisición— se manejan con 2**, tanto en pantalla como al guardar.

La distinción es deliberada: el precio unitario describe *cuánto vale una unidad*, y eso
a veces no cae en centavos exactos (Q 0.075 por hoja, Q 10.125 por unidad en una compra
por volumen). El importe, en cambio, es *lo que se paga*, y se paga en centavos.

```
capturado:  12 unidades × Q 0.075
se guarda:  precio 0.075   total 0.90
```

### Por qué los importes no llevan más de 2

1. **Las monedas soportadas son de 2 decimales.** GTQ, USD y EUR no tienen unidades
   menores al centavo. Un importe de `12.34567` no es pagable.
2. **Las facturas se guardan con 2 decimales** (`facturas.monto_factura` es
   `decimal(15,2)`). Si el total de la requisición llevara más, se perdería al generar
   las facturas automáticas y el `monto_total` dejaría de cuadrar contra la suma de sus
   facturas.
3. Como la cantidad es entera, `cantidad × precio(3 decimales)` produce a lo sumo 3
   decimales, y redondear ese resultado a 2 desvía como mucho medio centavo por ítem.

### Consecuencia visible

Un ítem de **1 unidad a Q 10.125 se cobra como Q 10.13**. El precio se conserva tal cual
se capturó; lo que se redondea es el importe. Es el comportamiento normal de facturación,
pero conviene saberlo antes de que alguien lo reporte como error.

## Dónde se aplica

| Capa | Qué hace |
|---|---|
| Formulario (`create.php`, `edit.php`) | Los inputs de precio unitario son `step="0.001"` `min="0"`. Aplica a la fila inicial estática, a la fila de ítems ya guardados en edición **y** a la plantilla JavaScript que agrega filas nuevas. |
| JavaScript | `redondear3()` para el precio; `redondear2()` para el total del ítem, el acumulado del total general y el monto de cada línea de distribución. Se redondea el acumulado en cada suma, no solo al final, para evitar el clásico `0.1 + 0.2 = 0.30000000000000004`. |
| Servidor (`RequisicionService::procesarDatosRequisicion`) | Vuelve a normalizar cantidad (entero) y precio (3 decimales) y **recalcula** el total (2 decimales). Nunca se confía en lo que envió el navegador; el `monto_total` que se guarda es la suma de los totales ya redondeados, no el valor del campo oculto. |
| Modelo (`DetalleItem`) | Punto único de verdad: `normalizarCantidad()` (entero), `normalizarPrecio()` (`DECIMALES_PRECIO = 3`) y `normalizarMonto()` (`DECIMALES_MONEDA = 2`). |
| Presentación (`View::money`) | Tercer parámetro opcional de decimales. Por defecto 2; las vistas pasan `3` **solo** para la columna de precio unitario. |

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
De cara al usuario ese colchón nunca se muestra.

Si algún día hiciera falta subir el precio a más de 3 decimales, la columna lo aguanta
hasta 5 sin migración: basta cambiar `DetalleItem::DECIMALES_PRECIO`, el `step` de los
inputs, `redondear3()` y los `View::money(..., 3)` de las vistas.

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
