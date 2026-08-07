# Terminología: Centro de Costo y Unidad de Negocio

> Lee esto antes de tocar cualquier cosa relacionada con centros de costo,
> unidades de negocio, distribución de gasto o asignación de autorizadores.

## Los dos conceptos

El sistema maneja una jerarquía de dos niveles:

| Nivel | Qué es | Cuántos | Ejemplos |
|---|---|---|---|
| **Centro de Costo** | El **grupo** | 10 vigentes | COMERCIAL, COLEGIO, CURSOS, OPERACIONES, DIRECCION |
| **Unidad de Negocio** | El **detalle** | 28 vigentes | Bodega, Sistemas, Básicos, Librería Xela, Recursos Humanos |

Cada unidad de negocio pertenece a **un solo** centro de costo. La relación es de
muchos a uno: COLEGIO agrupa a Básicos, Bachillerato, Perito, Primaria y Colegio
General.

Esta es la terminología del **formato oficial de requisición** de la institución
(hoja "NORMAS DE REPARTO" del Excel). Es la que ve el usuario y la que usa
finanzas.

## Cómo se traduce a la base de datos

| Concepto | Tabla | Columna en otras tablas | Modelo |
|---|---|---|---|
| Centro de Costo (grupo) | `centro_de_costo` | `centro_costo_id` | `App\Models\CentroCosto` |
| Unidad de Negocio (detalle) | `unidad_de_negocio` | `unidad_negocio_id` | `App\Models\UnidadNegocio` |

**Desde el 5 de agosto de 2026 los nombres coinciden con el significado.** Si
estás leyendo código o SQL, lo que dice es lo que es: no hay que traducir nada
mentalmente.

## Cómo funciona en una requisición

En la tabla de Distribución de Gasto el usuario **elige la unidad de negocio**
(el detalle) y el sistema **autocompleta el centro de costo** (su grupo) y el
**número de factura**.

```
Usuario elige: "Sistemas"  (unidad de negocio)
      ↓ el sistema deduce
Centro de Costo: OPERACIONES        (automático)
Factura:         2                  (automático)
```

El sentido de la deducción importa: va del detalle al grupo, que es determinista
porque cada detalle tiene un solo grupo. **Al revés no funciona** — si el usuario
eligiera el grupo, el sistema no podría saber cuál de sus detalles quiso
(COLEGIO tiene cinco). Ya se intentó en su momento y el sistema terminaba
asignando un detalle arbitrario.

## Dónde se usa cada uno

**La unidad de negocio (detalle) es la que manda en el flujo de aprobación.**
Los autorizadores se asignan por unidad de negocio, no por centro de costo:
`autorizador_unidad_negocio` guarda qué persona aprueba qué unidad, y en qué
nivel de la secuencia. Los respaldos y las exclusiones de cuentas también son
por unidad.

El centro de costo (grupo) se usa para reportes, para agrupar el gasto y para
determinar el número de factura.

## El número de factura

Vive en `unidad_de_negocio.factura` y va del 1 al 4, según con qué razón social
se factura:

| Factura | Agrupa | IVA |
|---|---|---|
| 1 | Comercial, Parqueo, Actividades Culturales | Afecto |
| 2 | Administración | Exento |
| 3 | Colegio | Exento |
| 4 | Cursos | Exento |

## Historia: por qué hubo que renombrar

Hasta agosto de 2026 el esquema tenía los nombres **invertidos**: la tabla
`centro_de_costo` guardaba los detalles y `unidad_de_negocio` los grupos. Es
decir, el código decía exactamente lo contrario de lo que significaba.

Eso obligaba a traducir mentalmente en cada consulta y era una fuente
permanente de errores. Se corrigió con la migración
[`2026-08-05_renombre_terminologia.sql`](../scripts/migrations/2026-08-05_renombre_terminologia.sql),
que **intercambia los nombres sin mover ni una fila**: los datos se quedaron
donde estaban y solo cambió cómo se llaman las tablas y columnas que los
contienen. Por eso todos los IDs conservaron su significado y no se rompió
ninguna relación.

Verificado contra el respaldo previo: 36 de 36 asignaciones de autorizadores,
35 de 35 jerarquías, 26 de 26 unidades requirentes y 35 de 35 números de factura
quedaron idénticos.

## Cosas que NO cambiaron, a propósito

**Las URLs.** Siguen siendo `/admin/centros`, `/admin/centros/create`, etc.,
aunque ese módulo administre unidades de negocio. Se dejaron así para no romper
enlaces guardados ni accesos directos de los usuarios.

**Los nombres de tablas de respaldo** (`*_backup`) y los scripts de migración
anteriores. Son registro histórico: describen cómo eran las cosas cuando se
escribieron, y cambiarlos falsearía ese registro.

## Al desplegar a producción

El código y la base **deben cambiar en el mismo momento**. Por separado el
sistema no funciona: el código nuevo consulta tablas que en la base vieja no
existen.

Pasos:

1. Respaldar la base de producción.
2. **Limpiar huérfanos si los hay** — en producción hay datos vivos, y las
   foreign keys no se crean si hay filas que apuntan a registros inexistentes.
   Las consultas para detectarlos están en el encabezado de
   [`2026-07-13_foreign_keys.sql`](../scripts/migrations/2026-07-13_foreign_keys.sql).
3. Poner el sistema en mantenimiento.
4. Aplicar `2026-08-05_renombre_terminologia.sql`.
5. Desplegar el código.
6. Sacar de mantenimiento.

La migración en sí es de segundos (`RENAME TABLE` es instantáneo en InnoDB), así
que la ventana es corta.

**Para revertir:** restaurar el respaldo de la base y volver el código al commit
anterior. Ambas cosas juntas, por la misma razón.
