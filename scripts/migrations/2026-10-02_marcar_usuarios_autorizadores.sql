-- =====================================================================
-- Encender usuarios.is_autorizador a quienes realmente autorizan
--
-- Esa columna la LEE el layout para decidir si muestra el menu de
-- Autorizaciones y el contador de pendientes, pero NINGUN punto del
-- sistema la escribe: dar de alta un autorizador en el panel crea su
-- registro en 'autorizadores' y su relacion con la unidad, y nunca marca
-- al usuario. Por eso estaba en 0 para todos los directores, que entraban
-- al sistema sin ver por donde autorizar y dependian del enlace del correo.
--
-- El codigo ya no depende de esta columna (View::getSessionData consulta
-- las asignaciones reales), pero se corrige igual para que la BD no mienta
-- y para que cualquier consulta o reporte que la use diga la verdad.
--
-- Fecha: 2026-10-02
-- =====================================================================

START TRANSACTION;

-- Autorizadores de unidad de negocio
UPDATE usuarios u
   SET u.is_autorizador = 1
 WHERE EXISTS (
       SELECT 1
         FROM autorizadores a
         JOIN autorizador_unidad_negocio aun ON aun.autorizador_id = a.id
        WHERE LOWER(a.email) = LOWER(u.azure_email)
          AND a.activo = 1
          AND aun.activo = 1
 );

-- Autorizadores especiales de forma de pago
UPDATE usuarios u
   SET u.is_autorizador = 1
 WHERE EXISTS (
       SELECT 1 FROM autorizadores_metodos_pago m
        WHERE LOWER(m.autorizador_email) = LOWER(u.azure_email)
          AND m.activo = 1
 );

-- Autorizadores especiales de cuenta contable
UPDATE usuarios u
   SET u.is_autorizador = 1
 WHERE EXISTS (
       SELECT 1 FROM autorizadores_cuentas_contables c
        WHERE LOWER(c.autorizador_email) = LOWER(u.azure_email)
          AND c.activo = 1
 );

COMMIT;

-- Comprobacion: quien quedo marcado
SELECT id, azure_display_name, azure_email, is_revisor, is_autorizador, is_admin
  FROM usuarios
 WHERE is_autorizador = 1
 ORDER BY azure_display_name;


-- =====================================================================
-- ROLLBACK (estado exacto previo: solo los usuarios 120 y 132 estaban
-- marcados; los demas quedaron marcados por esta migracion)
-- =====================================================================
-- UPDATE usuarios SET is_autorizador = 0 WHERE id NOT IN (120, 132);
