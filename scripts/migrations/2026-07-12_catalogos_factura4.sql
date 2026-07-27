-- ============================================================================
-- Migración catálogos según "Formato Requisición (NUEVO).xlsx" — 2026-07-12
-- 1. unidad_de_negocio: columnas codigo/activo, nuevas unidades, renombres
-- 2. centro_de_costo: columna codigo, renombres, nuevos, bajas, facturas 1-4
-- 3. cuenta_contable: recarga completa (inserts en archivo aparte) + remapeo
-- Requiere backup previo: backups/bd_prueba_full_20260712.sql
-- ============================================================================

USE bd_prueba;

-- ---------------------------------------------------------------------------
-- 0. Copias de seguridad internas (rollback rápido sin restaurar dump)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS zz_centro_de_costo_bak_20260712 AS SELECT * FROM centro_de_costo;
CREATE TABLE IF NOT EXISTS zz_unidad_de_negocio_bak_20260712 AS SELECT * FROM unidad_de_negocio;
CREATE TABLE IF NOT EXISTS zz_cuenta_contable_bak_20260712 AS SELECT * FROM cuenta_contable;

-- ---------------------------------------------------------------------------
-- 1. UNIDAD DE NEGOCIO
-- ---------------------------------------------------------------------------
ALTER TABLE unidad_de_negocio
  ADD COLUMN IF NOT EXISTS codigo VARCHAR(10) NULL AFTER nombre,
  ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1 AFTER codigo;

-- Renombres / códigos de unidades existentes
UPDATE unidad_de_negocio SET codigo = 'AC'  WHERE id = 2;   -- ACTIVIDADES CULTURALES
UPDATE unidad_de_negocio SET codigo = 'CR'  WHERE id = 3;   -- COMERCIAL
UPDATE unidad_de_negocio SET codigo = 'CO'  WHERE id = 9;   -- COLEGIO
UPDATE unidad_de_negocio SET nombre = 'CURSOS', codigo = 'CA' WHERE id = 14;  -- antes CURSOS ADULTOS
UPDATE unidad_de_negocio SET nombre = 'UNIDAD DE NEGOCIOS GENERAL', codigo = 'UNG' WHERE id = 30;

-- Bajas lógicas (se reagrupan)
UPDATE unidad_de_negocio SET activo = 0 WHERE id IN (1, 29); -- ADMINISTRACION, CURSOS NIÑOS

-- Nuevas unidades
INSERT INTO unidad_de_negocio (nombre, codigo, activo)
SELECT * FROM (SELECT 'PARQUEO' n, 'PA' c, 1 a UNION ALL
               SELECT 'DIRECCION', 'DG', 1 UNION ALL
               SELECT 'OPERACIONES', 'DO', 1 UNION ALL
               SELECT 'FINANCIERO', 'FI', 1 UNION ALL
               SELECT 'SISTEMAS', 'IT', 1) nuevas
WHERE NOT EXISTS (SELECT 1 FROM unidad_de_negocio u WHERE u.codigo = nuevas.c);

-- ---------------------------------------------------------------------------
-- 2. CENTRO DE COSTO
-- ---------------------------------------------------------------------------
ALTER TABLE centro_de_costo
  ADD COLUMN IF NOT EXISTS codigo VARCHAR(10) NULL AFTER nombre,
  MODIFY COLUMN factura INT(11) DEFAULT 1 COMMENT 'Numero de factura (1-4) segun empresa que factura';

-- Variables de unidades (por código, ids según entorno)
SET @un_pa  = (SELECT id FROM unidad_de_negocio WHERE codigo = 'PA');
SET @un_ac  = 2;
SET @un_cr  = 3;
SET @un_co  = 9;
SET @un_ca  = 14;
SET @un_dg  = (SELECT id FROM unidad_de_negocio WHERE codigo = 'DG');
SET @un_do  = (SELECT id FROM unidad_de_negocio WHERE codigo = 'DO');
SET @un_fi  = (SELECT id FROM unidad_de_negocio WHERE codigo = 'FI');
SET @un_it  = (SELECT id FROM unidad_de_negocio WHERE codigo = 'IT');
SET @un_ung = 30;

-- Renombres y reasignación de centros existentes (id → nuevo nombre/código/factura/unidad)
UPDATE centro_de_costo SET nombre='Parqueo',                          codigo='PA01', factura=1, unidad_negocio_id=@un_pa  WHERE id=1;  -- PARQUEO GENERAL
UPDATE centro_de_costo SET nombre='Actividades culturales',           codigo='AC01', factura=1, unidad_negocio_id=@un_ac  WHERE id=2;
UPDATE centro_de_costo SET nombre='Bodega',                           codigo='CR01', factura=1, unidad_negocio_id=@un_cr  WHERE id=3;
UPDATE centro_de_costo SET nombre='Distribuidora',                    codigo='CR02', factura=1, unidad_negocio_id=@un_cr  WHERE id=5;
UPDATE centro_de_costo SET nombre='Librería Cobán',                   codigo='CR04', factura=1, unidad_negocio_id=@un_cr  WHERE id=6;
UPDATE centro_de_costo SET nombre='Librería Xela',                    codigo='CR05', factura=1, unidad_negocio_id=@un_cr  WHERE id=7;  -- antes LIBRERIA QUETZALTENANGO
UPDATE centro_de_costo SET nombre='Librería - Z4',                    codigo='CR03', factura=1, unidad_negocio_id=@un_cr  WHERE id=8;
UPDATE centro_de_costo SET nombre='Básicos',                          codigo='CO01', factura=3, unidad_negocio_id=@un_co  WHERE id=9;
UPDATE centro_de_costo SET nombre='Bachillerato',                     codigo='CO02', factura=3, unidad_negocio_id=@un_co  WHERE id=10;
UPDATE centro_de_costo SET nombre='Perito',                           codigo='CO03', factura=3, unidad_negocio_id=@un_co  WHERE id=11; -- antes PERITO CONTADOR
UPDATE centro_de_costo SET nombre='Primaria',                         codigo='CO04', factura=3, unidad_negocio_id=@un_co  WHERE id=13;
UPDATE centro_de_costo SET nombre='Cursos Adultos',                   codigo='CA01', factura=4, unidad_negocio_id=@un_ca  WHERE id=14; -- antes CURSOS ADULTOS Z.4
UPDATE centro_de_costo SET nombre='Dirección General',                codigo='DG01', factura=2, unidad_negocio_id=@un_dg  WHERE id=18;
UPDATE centro_de_costo SET nombre='Financiero',                       codigo='FI01', factura=2, unidad_negocio_id=@un_fi  WHERE id=20; -- antes FINANZAS
UPDATE centro_de_costo SET nombre='Sistemas',                         codigo='IT01', factura=2, unidad_negocio_id=@un_it  WHERE id=21;
UPDATE centro_de_costo SET nombre='Mercadeo',                         codigo='UN02', factura=2, unidad_negocio_id=@un_do  WHERE id=22;
UPDATE centro_de_costo SET nombre='Organización y Procedimientos',    codigo='DG03', factura=2, unidad_negocio_id=@un_dg  WHERE id=23;
UPDATE centro_de_costo SET nombre='Operaciones general',              codigo='UN05', factura=2, unidad_negocio_id=@un_do  WHERE id=24; -- antes OPERACIONES
UPDATE centro_de_costo SET nombre='Recursos Humanos',                 codigo='UN03', factura=2, unidad_negocio_id=@un_do  WHERE id=25;
UPDATE centro_de_costo SET nombre='Servicio al cliente',              codigo='UN04', factura=2, unidad_negocio_id=@un_do  WHERE id=26;
UPDATE centro_de_costo SET nombre='Unidad de desarrollo profesional', codigo='UD01', factura=4, unidad_negocio_id=@un_ca  WHERE id=27;
UPDATE centro_de_costo SET nombre='Cursos Niños y Adolescentes',      codigo='CN01', factura=4, unidad_negocio_id=@un_ca  WHERE id=29; -- antes ...Z.4
UPDATE centro_de_costo SET nombre='Centros de Costos General',        codigo='UNG1', factura=2, unidad_negocio_id=@un_ung WHERE id=30;

-- Bajas lógicas (no vigentes en el nuevo catálogo)
UPDATE centro_de_costo SET activo = 0 WHERE id IN (4, 12, 15, 16, 17, 19, 28);
-- 4 DISTRIBUCION FISICA, 12 SECRETARIADO, 15 CURSOS EMPRESARIALES,
-- 16 CURSOS ADULTOS DEPARTAMENTOS, 17 PROGRAMAS SOCIALES, 19 EDUCATION USA, 28 BIBLIOTECA

-- Nuevos centros
INSERT INTO centro_de_costo (nombre, codigo, factura, unidad_negocio_id, activo, requiere_asignacion_manual)
SELECT * FROM (
  SELECT 'Colegio General' n,          'CO05' c, 3 f, @un_co v, 1 a, 0 m UNION ALL
  SELECT 'Cursos Programas Externos',  'CA02',   4,   @un_ca,   1,   0 UNION ALL
  SELECT 'Auditoría Interna',          'DG02',   2,   @un_dg,   1,   0 UNION ALL
  SELECT 'Compras y operaciones',      'UN01',   2,   @un_do,   1,   0 UNION ALL
  SELECT 'WWAC / EDUSA',               'AC02',   2,   @un_ac,   1,   0
) nuevos
WHERE NOT EXISTS (SELECT 1 FROM centro_de_costo cc WHERE cc.codigo = nuevos.c);

-- Índice único de código
ALTER TABLE centro_de_costo ADD UNIQUE INDEX IF NOT EXISTS uq_centro_codigo (codigo);

-- ---------------------------------------------------------------------------
-- 3. CUENTA CONTABLE — vaciado (la recarga viene de cuentas_inserts.sql)
-- ---------------------------------------------------------------------------
DELETE FROM cuenta_contable;
ALTER TABLE cuenta_contable AUTO_INCREMENT = 1;
