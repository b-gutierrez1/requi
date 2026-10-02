-- ============================================================================
-- Agregar el estado 'omitida' a autorizaciones.estado — 2026-08-07
--
-- El codigo ya escribe este estado desde 72416a5 (AutorizacionService omite
-- autorizaciones especiales al aprobar pago/cuenta) y lo lee en
-- RequisicionController (NOT IN (...,'omitida',...)), pero el ENUM nunca lo
-- incluyo: en modo estricto el UPDATE truena con "Data truncated for column
-- 'estado'". Este script alinea el ENUM con lo que el codigo ya hace.
--
-- No toca datos: solo amplia los valores permitidos.
-- ============================================================================

USE bd_prueba;

ALTER TABLE autorizaciones MODIFY estado
  ENUM('pendiente','aprobada','rechazada','omitida') NOT NULL DEFAULT 'pendiente';
