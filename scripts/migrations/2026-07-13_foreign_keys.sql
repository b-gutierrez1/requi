-- ============================================================================
-- Relaciones reales (FOREIGN KEYS) — 2026-07-13
--
-- Antes de esto la BD solo tenia 3 FKs; el resto de relaciones eran logicas
-- (por convencion de nombre), sin proteccion de integridad. Eso permitio que
-- quedaran filas huerfanas.
--
-- BUG QUE ESTO CORRIGE: habia 14 facturas apuntando a requisiciones 47-58 que
-- ya no existen, y requisiciones.AUTO_INCREMENT esta en 47 — la proxima
-- requisicion creada habria heredado facturas fantasma de marzo.
--
-- Backup previo: backups/bd_prueba_pre_fks_20260713.sql
-- Los huerfanos se copian a tablas zz_*_huerfanos_20260713 antes de borrarlos.
-- ============================================================================

USE bd_prueba;

-- ---------------------------------------------------------------------------
-- 1. Respaldo y limpieza de huerfanos (unico paso destructivo, reversible)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS zz_facturas_huerfanas_20260713 AS
SELECT f.* FROM facturas f
LEFT JOIN requisiciones r ON r.id = f.requisicion_id
WHERE r.id IS NULL;

CREATE TABLE IF NOT EXISTS zz_acc_huerfanos_20260713 AS
SELECT acc.* FROM autorizador_centro_costo acc
LEFT JOIN autorizadores a ON a.id = acc.autorizador_id
WHERE a.id IS NULL;

DELETE f FROM facturas f
LEFT JOIN requisiciones r ON r.id = f.requisicion_id
WHERE r.id IS NULL;

DELETE acc FROM autorizador_centro_costo acc
LEFT JOIN autorizadores a ON a.id = acc.autorizador_id
WHERE a.id IS NULL;

-- ---------------------------------------------------------------------------
-- 2. Hijos de una requisicion: CASCADE
--    Si se borra la requisicion, se va todo su detalle con ella.
-- ---------------------------------------------------------------------------
ALTER TABLE detalle_items
  ADD CONSTRAINT fk_detalle_items_requisicion FOREIGN KEY (requisicion_id)
  REFERENCES requisiciones(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE distribucion_gasto
  ADD CONSTRAINT fk_distribucion_requisicion FOREIGN KEY (requisicion_id)
  REFERENCES requisiciones(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE facturas
  ADD CONSTRAINT fk_facturas_requisicion FOREIGN KEY (requisicion_id)
  REFERENCES requisiciones(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE archivos_adjuntos
  ADD CONSTRAINT fk_adjuntos_requisicion FOREIGN KEY (requisicion_id)
  REFERENCES requisiciones(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE historial_requisiciones
  ADD CONSTRAINT fk_historial_requisicion FOREIGN KEY (requisicion_id)
  REFERENCES requisiciones(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE autorizaciones
  ADD CONSTRAINT fk_autorizaciones_requisicion FOREIGN KEY (requisicion_id)
  REFERENCES requisiciones(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE autorizacion_flujo
  ADD CONSTRAINT fk_flujo_requisicion FOREIGN KEY (requisicion_id)
  REFERENCES requisiciones(id) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE aprobaciones
  ADD CONSTRAINT fk_aprobaciones_requisicion FOREIGN KEY (orden_compra_id)
  REFERENCES requisiciones(id) ON DELETE CASCADE ON UPDATE CASCADE;

-- ---------------------------------------------------------------------------
-- 3. Referencias a catalogos: RESTRICT
--    Un catalogo en uso no se puede borrar; hay que darlo de baja (activo=0).
-- ---------------------------------------------------------------------------
ALTER TABLE centro_de_costo
  ADD CONSTRAINT fk_centro_unidad_negocio FOREIGN KEY (unidad_negocio_id)
  REFERENCES unidad_de_negocio(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE distribucion_gasto
  ADD CONSTRAINT fk_distribucion_centro FOREIGN KEY (centro_costo_id)
  REFERENCES centro_de_costo(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_distribucion_cuenta FOREIGN KEY (cuenta_contable_id)
  REFERENCES cuenta_contable(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_distribucion_unidad FOREIGN KEY (unidad_negocio_id)
  REFERENCES unidad_de_negocio(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE autorizaciones
  ADD CONSTRAINT fk_autorizaciones_centro FOREIGN KEY (centro_costo_id)
  REFERENCES centro_de_costo(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_autorizaciones_cuenta FOREIGN KEY (cuenta_contable_id)
  REFERENCES cuenta_contable(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE unidad_requirente
  ADD CONSTRAINT fk_unidad_req_centro FOREIGN KEY (centro_costo_id)
  REFERENCES centro_de_costo(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE persona_autorizada
  ADD CONSTRAINT fk_persona_aut_centro FOREIGN KEY (centro_costo_id)
  REFERENCES centro_de_costo(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE requisiciones
  ADD CONSTRAINT fk_requisiciones_usuario FOREIGN KEY (usuario_id)
  REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- ---------------------------------------------------------------------------
-- 4. Configuracion de autorizadores: CASCADE
--    Si se borra un autorizador, sus asignaciones dejan de tener sentido.
-- ---------------------------------------------------------------------------
ALTER TABLE autorizador_centro_costo
  ADD CONSTRAINT fk_acc_autorizador FOREIGN KEY (autorizador_id)
  REFERENCES autorizadores(id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_acc_centro FOREIGN KEY (centro_costo_id)
  REFERENCES centro_de_costo(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE autorizadores_cuentas_contables
  ADD CONSTRAINT fk_acuentas_cuenta FOREIGN KEY (cuenta_contable_id)
  REFERENCES cuenta_contable(id) ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE autorizador_cuenta_exclusiones
  ADD CONSTRAINT fk_excl_autorizador_cuenta FOREIGN KEY (autorizador_cuenta_id)
  REFERENCES autorizadores_cuentas_contables(id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_excl_centro FOREIGN KEY (centro_costo_id)
  REFERENCES centro_de_costo(id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Nota: distribucion_gasto.ubicacion_id NO recibe FK — la entidad ubicacion
-- se esta retirando del sistema (se conserva solo el historico).
