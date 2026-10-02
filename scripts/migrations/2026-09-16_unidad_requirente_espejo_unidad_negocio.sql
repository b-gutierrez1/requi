-- =====================================================================
-- Unidad Requirente = espejo de Unidad de Negocio
--
-- En el formulario de requisicion, "Unidad Requirente" debe listar las
-- mismas unidades que "Unidad de Negocio". La relacion ya estaba pensada
-- 1 a 1 (unidad_requirente.unidad_negocio_id es UNIQUE), pero las listas
-- se habian desfasado:
--   - 9 unidades de negocio no tenian unidad requirente (entre ellas
--     WWAC / EDUSA, por eso la requisicion 4 termino en EDUCATION USA y
--     en la impresion salia Marlen como director).
--   - 21 tenian otro nombre (FINANZAS vs Financiero, COLEGIO vs Basicos...).
--   - La mayoria seguia activa aunque su unidad de negocio estuviera cerrada.
--
-- Ninguna requisicion guarda la unidad requirente como texto (todas usan
-- el id), asi que alinear los nombres no rompe historicos ni reportes.
--
-- A partir de aqui el panel /admin/centros mantiene la sincronizacion al
-- crear, editar o activar/desactivar una unidad de negocio
-- (UnidadRequirente::sincronizarConUnidadNegocio).
--
-- Fecha: 2026-09-16
-- =====================================================================

START TRANSACTION;

-- 1) Crear la unidad requirente de cada unidad de negocio que no tenga
INSERT INTO unidad_requirente (nombre, unidad_negocio_id, activo)
SELECT un.nombre, un.id, un.activo
  FROM unidad_de_negocio un
 WHERE NOT EXISTS (
       SELECT 1 FROM unidad_requirente ur WHERE ur.unidad_negocio_id = un.id
 );

-- 2) Mismo nombre y mismo estado que su unidad de negocio
UPDATE unidad_requirente ur
  JOIN unidad_de_negocio un ON un.id = ur.unidad_negocio_id
   SET ur.nombre = un.nombre,
       ur.activo = un.activo;

COMMIT;

-- Comprobacion: no debe salir ninguna fila
SELECT un.id, un.nombre, un.activo, ur.id AS ur_id, ur.nombre AS ur_nombre, ur.activo AS ur_activo
  FROM unidad_de_negocio un
  LEFT JOIN unidad_requirente ur ON ur.unidad_negocio_id = un.id
 WHERE ur.id IS NULL
    OR ur.nombre <> un.nombre
    OR ur.activo <> un.activo;


-- =====================================================================
-- ROLLBACK (estado exacto previo al 2026-09-16)
-- =====================================================================
-- START TRANSACTION;
--
-- DELETE FROM unidad_requirente WHERE unidad_negocio_id IN (10, 11, 12, 13, 32, 33, 34, 35, 36);
--
-- UPDATE unidad_requirente SET nombre = 'PARQUEO GENERAL',               activo = 1 WHERE id = 1;
-- UPDATE unidad_requirente SET nombre = 'ACTIVIDADES CULTURALES',        activo = 1 WHERE id = 2;
-- UPDATE unidad_requirente SET nombre = 'BODEGA',                        activo = 1 WHERE id = 3;
-- UPDATE unidad_requirente SET nombre = 'DISTRIBUCION FISICA',           activo = 1 WHERE id = 4;
-- UPDATE unidad_requirente SET nombre = 'DISTRIBUIDORA',                 activo = 1 WHERE id = 5;
-- UPDATE unidad_requirente SET nombre = 'LIBRERIA COBAN',                activo = 1 WHERE id = 6;
-- UPDATE unidad_requirente SET nombre = 'LIBRERIA QUETZALTENANGO',       activo = 1 WHERE id = 7;
-- UPDATE unidad_requirente SET nombre = 'LIBRERIA ZONA 4',               activo = 1 WHERE id = 8;
-- UPDATE unidad_requirente SET nombre = 'COLEGIO',                       activo = 1 WHERE id = 9;
-- UPDATE unidad_requirente SET nombre = 'CURSOS ADULTOS Z.4',            activo = 1 WHERE id = 13;
-- UPDATE unidad_requirente SET nombre = 'CURSOS EMPRESARIALES',          activo = 1 WHERE id = 14;
-- UPDATE unidad_requirente SET nombre = 'CURSOS ADULTOS DEPARTAMENTOS',  activo = 1 WHERE id = 15;
-- UPDATE unidad_requirente SET nombre = 'PROGRAMAS EXTERNOS',            activo = 1 WHERE id = 16;
-- UPDATE unidad_requirente SET nombre = 'DIRECCION GENERAL',             activo = 1 WHERE id = 17;
-- UPDATE unidad_requirente SET nombre = 'EDUCATION USA',                 activo = 0 WHERE id = 18;
-- UPDATE unidad_requirente SET nombre = 'FINANZAS',                      activo = 1 WHERE id = 19;
-- UPDATE unidad_requirente SET nombre = 'SISTEMAS',                      activo = 1 WHERE id = 20;
-- UPDATE unidad_requirente SET nombre = 'MERCADEO',                      activo = 1 WHERE id = 21;
-- UPDATE unidad_requirente SET nombre = 'ORGANIZACION Y PROCEDIMIENTOS', activo = 1 WHERE id = 22;
-- UPDATE unidad_requirente SET nombre = 'OPERACIONES',                   activo = 1 WHERE id = 23;
-- UPDATE unidad_requirente SET nombre = 'RECURSOS HUMANOS',              activo = 1 WHERE id = 24;
-- UPDATE unidad_requirente SET nombre = 'SERVICIO AL CLIENTE',           activo = 1 WHERE id = 25;
-- UPDATE unidad_requirente SET nombre = 'UNIDAD ACADEMICA',              activo = 1 WHERE id = 26;
-- UPDATE unidad_requirente SET nombre = 'BIBLIOTECA',                    activo = 1 WHERE id = 27;
-- UPDATE unidad_requirente SET nombre = 'CURSOS NIÑOS Y ADOLECENTES Z.4', activo = 1 WHERE id = 28;
-- UPDATE unidad_requirente SET nombre = 'CENTRO DE COSTO GENERAL',       activo = 1 WHERE id = 29;
--
-- COMMIT;
