-- ============================================================================
-- Renombre a la terminologia oficial — 2026-08-05
--
-- El formato oficial de la institucion usa estos terminos al reves que el
-- esquema historico de la BD:
--
--   DETALLE (28: Bodega, Sistemas, Basicos...) -> el formato lo llama UNIDAD DE NEGOCIO
--   GRUPO   (10: COMERCIAL, COLEGIO, CURSOS...) -> el formato lo llama CENTRO DE COSTO
--
-- Este script intercambia los NOMBRES para que la BD hable el idioma oficial.
-- NINGUNA FILA SE MUEVE: los datos se quedan donde estan, solo cambia como se
-- llaman las tablas y columnas que los contienen. Por eso todos los IDs
-- conservan su significado y el historico queda intacto.
--
-- Requiere respaldo previo: backups/bd_prueba_pre_renombre_20260805.sql
-- El codigo PHP debe desplegarse EN EL MISMO MOMENTO que este script:
-- por separado el sistema no funciona.
-- ============================================================================

USE bd_prueba;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1. Quitar la vista y las FK afectadas (se recrean al final con nombre correcto)
-- ---------------------------------------------------------------------------
DROP VIEW IF EXISTS persona_autorizada;

ALTER TABLE centro_de_costo               DROP FOREIGN KEY fk_centro_unidad_negocio;
ALTER TABLE distribucion_gasto            DROP FOREIGN KEY fk_distribucion_centro;
ALTER TABLE distribucion_gasto            DROP FOREIGN KEY fk_distribucion_unidad;
ALTER TABLE autorizaciones                DROP FOREIGN KEY fk_autorizaciones_centro;
ALTER TABLE unidad_requirente             DROP FOREIGN KEY fk_unidad_req_centro;
ALTER TABLE autorizador_centro_costo      DROP FOREIGN KEY fk_acc_centro;
ALTER TABLE autorizador_centro_costo      DROP FOREIGN KEY fk_acc_autorizador;
ALTER TABLE autorizador_cuenta_exclusiones DROP FOREIGN KEY fk_excl_centro;
ALTER TABLE autorizador_respaldo_centro   DROP FOREIGN KEY fk_arc_centro;
ALTER TABLE autorizador_respaldo_centro   DROP FOREIGN KEY fk_arc_respaldo;

-- ---------------------------------------------------------------------------
-- 2. Columnas que apuntan al DETALLE: centro_costo_id -> unidad_negocio_id
-- ---------------------------------------------------------------------------
ALTER TABLE autorizaciones                 CHANGE centro_costo_id unidad_negocio_id INT(11) NULL;
ALTER TABLE autorizador_centro_costo       CHANGE centro_costo_id unidad_negocio_id INT(11) NOT NULL;
ALTER TABLE autorizador_cuenta_exclusiones CHANGE centro_costo_id unidad_negocio_id INT(11) NOT NULL;
ALTER TABLE autorizador_respaldo_centro    CHANGE centro_costo_id unidad_negocio_id INT(11) NOT NULL;
ALTER TABLE unidad_requirente              CHANGE centro_costo_id unidad_negocio_id INT(11) NULL;

-- ---------------------------------------------------------------------------
-- 3. Columnas que apuntan al GRUPO: unidad_negocio_id -> centro_costo_id
-- ---------------------------------------------------------------------------
ALTER TABLE centro_de_costo                    CHANGE unidad_negocio_id centro_costo_id INT(11) NULL;
ALTER TABLE persona_autorizada_unidad_negocio  CHANGE unidad_negocio_id centro_costo_id INT(11) NOT NULL;

-- ---------------------------------------------------------------------------
-- 4. distribucion_gasto tiene las DOS: hay que intercambiarlas con nombre temporal
-- ---------------------------------------------------------------------------
ALTER TABLE distribucion_gasto CHANGE centro_costo_id  tmp_swap_id      INT(11) NULL;
ALTER TABLE distribucion_gasto CHANGE unidad_negocio_id centro_costo_id INT(11) NULL;
ALTER TABLE distribucion_gasto CHANGE tmp_swap_id      unidad_negocio_id INT(11) NULL;

-- ---------------------------------------------------------------------------
-- 5. Banderas booleanas que hablan del detalle
-- ---------------------------------------------------------------------------
ALTER TABLE autorizadores_cuentas_contables
  CHANGE ignorar_centro_costo ignorar_unidad_negocio TINYINT(1) NOT NULL DEFAULT 1,
  CHANGE ignora_centro_costo  ignora_unidad_negocio  TINYINT(1) NOT NULL DEFAULT 0;

-- ---------------------------------------------------------------------------
-- 6. Nombres de tabla. centro_de_costo y unidad_de_negocio se intercambian,
--    asi que pasan por un nombre temporal.
-- ---------------------------------------------------------------------------
RENAME TABLE
  centro_de_costo   TO tmp_swap_tabla,
  unidad_de_negocio TO centro_de_costo,
  tmp_swap_tabla    TO unidad_de_negocio;

RENAME TABLE autorizador_centro_costo          TO autorizador_unidad_negocio;
RENAME TABLE autorizador_respaldo_centro       TO autorizador_respaldo_unidad;
RENAME TABLE persona_autorizada_unidad_negocio TO persona_autorizada_centro_costo;

-- ---------------------------------------------------------------------------
-- 7. Recrear las FK con nombres coherentes
-- ---------------------------------------------------------------------------
ALTER TABLE unidad_de_negocio
  ADD CONSTRAINT fk_unidad_centro_costo FOREIGN KEY (centro_costo_id)
  REFERENCES centro_de_costo(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE distribucion_gasto
  ADD CONSTRAINT fk_distribucion_unidad FOREIGN KEY (unidad_negocio_id)
  REFERENCES unidad_de_negocio(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_distribucion_centro FOREIGN KEY (centro_costo_id)
  REFERENCES centro_de_costo(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE autorizaciones
  ADD CONSTRAINT fk_autorizaciones_unidad FOREIGN KEY (unidad_negocio_id)
  REFERENCES unidad_de_negocio(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE unidad_requirente
  ADD CONSTRAINT fk_unidad_req_unidad FOREIGN KEY (unidad_negocio_id)
  REFERENCES unidad_de_negocio(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE autorizador_unidad_negocio
  ADD CONSTRAINT fk_aun_unidad FOREIGN KEY (unidad_negocio_id)
  REFERENCES unidad_de_negocio(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_aun_autorizador FOREIGN KEY (autorizador_id)
  REFERENCES autorizadores(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE autorizador_cuenta_exclusiones
  ADD CONSTRAINT fk_excl_unidad FOREIGN KEY (unidad_negocio_id)
  REFERENCES unidad_de_negocio(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE autorizador_respaldo_unidad
  ADD CONSTRAINT fk_aru_unidad FOREIGN KEY (unidad_negocio_id)
  REFERENCES unidad_de_negocio(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_aru_respaldo FOREIGN KEY (respaldo_id)
  REFERENCES autorizador_respaldo(id) ON DELETE CASCADE ON UPDATE CASCADE;

-- ---------------------------------------------------------------------------
-- 8. Recrear la vista con los nombres nuevos
-- ---------------------------------------------------------------------------
CREATE VIEW persona_autorizada AS
SELECT aun.id AS id,
       aun.unidad_negocio_id AS unidad_negocio_id,
       a.nombre AS nombre,
       a.email AS email,
       a.cargo AS cargo
FROM autorizador_unidad_negocio aun
JOIN autorizadores a ON a.id = aun.autorizador_id
WHERE aun.activo = 1;

SET FOREIGN_KEY_CHECKS = 1;
