-- ============================================================================
-- Paso 3: remapeo de referencias a cuenta_contable (correr DESPUÉS de los
-- inserts) y unificación de collation — 2026-07-12
-- ============================================================================
USE bd_prueba;

-- Remapear autorizador especial por cuenta (codigo viejo "XXXXXXXXX-00-00" → nuevo "XXXXXXXXX")
UPDATE autorizadores_cuentas_contables acc
JOIN zz_cuenta_contable_bak_20260712 o ON acc.cuenta_contable_id = o.id
JOIN (SELECT codigo, MIN(id) id FROM cuenta_contable GROUP BY codigo) n
  ON n.codigo = SUBSTRING_INDEX(o.codigo, '-', 1)
SET acc.cuenta_contable_id = n.id;

-- Remapear histórico de distribucion_gasto de la misma forma
UPDATE distribucion_gasto dg
JOIN zz_cuenta_contable_bak_20260712 o ON dg.cuenta_contable_id = o.id
JOIN (SELECT codigo, MIN(id) id FROM cuenta_contable GROUP BY codigo) n
  ON n.codigo = SUBSTRING_INDEX(o.codigo, '-', 1)
SET dg.cuenta_contable_id = n.id;

-- ---------------------------------------------------------------------------
-- Collation única: utf8mb4_unicode_ci en todas las tablas base
-- ---------------------------------------------------------------------------
ALTER DATABASE bd_prueba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
