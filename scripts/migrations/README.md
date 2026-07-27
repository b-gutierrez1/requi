# Migraciones de base de datos

Scripts aplicados a `bd_prueba` (MariaDB 11.7), en orden cronológico por nombre.

| Script | Qué hace | Estado |
|---|---|---|
| `2026-07-12_catalogos_factura4.sql` | Códigos y facturas 1-4 en `centro_de_costo`, reagrupación de `unidad_de_negocio` según el formato oficial | Aplicado |
| `2026-07-12_cuentas_inserts.sql` | Recarga de las 369 cuentas contables | Aplicado — **no versionado** (ver abajo) |
| `2026-07-12_remapeo_y_collation.sql` | Remapeo de referencias a cuentas y collation `utf8mb4_unicode_ci` | Aplicado |
| `2026-07-13_foreign_keys.sql` | Limpieza de huérfanos y 22 foreign keys reales | **Pendiente de aplicar** |

## Por qué falta el script de cuentas contables

`2026-07-12_cuentas_inserts.sql` está excluido de git a propósito: la nomenclatura
contable incluye 20 números de cuenta bancaria del IGA y este repositorio es público.
El archivo vive solo en local. Para regenerarlo, exportar la hoja "Nomenclatura Contable"
del formato oficial de requisición.

## Cómo aplicar un script

```
"C:\Program Files\MariaDB 11.7\bin\mysql.exe" -u root --default-character-set=utf8mb4 bd_prueba < scripts\migrations\<script>.sql
```

Hacer respaldo antes; los respaldos van a `backups/`, que tampoco se versiona.
