-- =====================================================================
-- Piloto: dejar activas solo las unidades de negocio de los 4
-- autorizadores del piloto (Mitchel, Hector, Luis, Marlen).
--
-- Las unidades desactivadas NO desaparecen del formulario de requisicion:
-- se siguen listando en gris y bloqueadas (<option disabled>), para que
-- el solicitante vea que existen pero estan cerradas por ahora.
--
-- Todo esto es reversible desde /admin/centros con el boton de
-- activar/desactivar de cada unidad. Al final del archivo queda el
-- rollback completo por si se quiere volver de un solo golpe.
--
-- Fecha: 2026-09-04
-- =====================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1) Mitchel (autorizador 144) no tenia ninguna unidad vigente asignada:
--    su unica relacion era BIBLIOTECA (28), que no esta en el catalogo
--    oficial y sigue desactivada. Se le asigna WWAC / EDUSA (AC02, id 36),
--    que estaba vigente y activa pero sin autorizador.
--
--    Nota: EDUCATION USA (19) tampoco esta en el catalogo oficial de
--    unidades vigentes, asi que no se toca ni se le asigna a nadie.
-- ---------------------------------------------------------------------
INSERT INTO autorizador_unidad_negocio (autorizador_id, unidad_negocio_id, es_principal, activo, orden)
SELECT 144, 36, 1, 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT * FROM autorizador_unidad_negocio) t
    WHERE t.autorizador_id = 144 AND t.unidad_negocio_id = 36
);

-- ---------------------------------------------------------------------
-- 2) Desactivar todo lo que no sea del piloto.
--
--    Se quedan activas exactamente estas 11:
--      14 CA01  Cursos Adultos                 (Hector)
--      29 CN01  Cursos Ninos y Adolescentes    (Hector)
--      17 --    PROGRAMAS SOCIALES             (Hector) -- sin codigo en el
--               catalogo de vigentes, pero se mantiene activa por decision
--               del area (2026-09-16)
--      21 IT01  Sistemas                       (Luis)
--      22 UN02  Mercadeo                       (Marlen)
--      23 DG03  Organizacion y Procedimientos  (Marlen)
--      24 UN05  Operaciones general            (Marlen)
--      25 UN03  Recursos Humanos               (Marlen)
--      26 UN04  Servicio al cliente            (Marlen)
--      30 UNG1  Centros de Costos General      (Marlen)
--      36 AC02  WWAC / EDUSA                   (Mitchel)
--
--    Nota: 18 "Direccion General" queda FUERA. Su unica liga con Hector
--    es a traves del registro de autorizador 141, que es un duplicado
--    desactivado; su autorizador vigente es Ana Sylvia (152), que no
--    esta en el piloto.
-- ---------------------------------------------------------------------
UPDATE unidad_de_negocio
   SET activo = 0
 WHERE id NOT IN (14, 17, 21, 22, 23, 24, 25, 26, 29, 30, 36);

-- ---------------------------------------------------------------------
-- 3) (2026-09-16) Unidad requirente EDUCATION USA fuera de uso.
--
--    La unidad requirente 18 "EDUCATION USA" lleva a la unidad de negocio
--    19, cuya persona autorizada es Marlen. La requisicion 4 (de Diana
--    Sofia, area de Mitchel) la usaba, asi que en la impresion salia Marlen
--    como "Director Unidad Requirente". La unidad requirente correcta para
--    esa area es 27 BIBLIOTECA, que ya lleva a Mitchel.
--
--    Primero se mueve la requisicion y despues se desactiva la unidad: si
--    se desactiva con la requisicion todavia apuntando ahi, el detalle
--    muestra "N/A" porque solo busca el nombre entre las activas.
-- ---------------------------------------------------------------------
UPDATE requisiciones
   SET unidad_requirente = 27
 WHERE id = 4
   AND unidad_requirente = 18;

UPDATE unidad_requirente
   SET activo = 0
 WHERE id = 18;

COMMIT;

-- Comprobacion
SELECT id, codigo, nombre, activo
  FROM unidad_de_negocio
 ORDER BY activo DESC, nombre ASC;


-- =====================================================================
-- ROLLBACK (estado exacto previo al 2026-09-04)
--
-- Antes de esta migracion habia 28 unidades activas -- las mismas 28 del
-- catalogo oficial "Vigentes" -- y 7 desactivadas, que son justo las que
-- no tienen codigo asignado (4, 12, 15, 16, 17, 19, 28).
-- =====================================================================
-- START TRANSACTION;
--
-- UPDATE unidad_de_negocio SET activo = 1
--  WHERE id NOT IN (4, 12, 15, 16, 17, 19, 28);
--
-- UPDATE unidad_de_negocio SET activo = 0
--  WHERE id IN (4, 12, 15, 16, 17, 19, 28);
--
-- DELETE FROM autorizador_unidad_negocio
--  WHERE autorizador_id = 144 AND unidad_negocio_id = 36;
--
-- UPDATE unidad_requirente SET activo = 1 WHERE id = 18;
-- UPDATE requisiciones SET unidad_requirente = 18 WHERE id = 4;
--
-- COMMIT;
