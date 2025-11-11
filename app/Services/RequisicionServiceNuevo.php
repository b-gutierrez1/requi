<?php

namespace App\Services;

use App\Models\Requisicion;
use App\Models\Autorizacion;
use App\Models\HistorialRequisicion;
use App\Events\EventDispatcher;

/**
 * Servicio para el nuevo sistema de requisiciones
 * 
 * Lógica de negocio limpia basada en el nuevo esquema v3.0
 */
class RequisicionServiceNuevo
{
    /**
     * Crea una nueva requisición
     */
    public function crearRequisicion(array $data, int $usuarioId): Requisicion
    {
        // Validar datos básicos
        $this->validarDatosRequisicion($data);

        // Generar número único
        $numeroRequisicion = Requisicion::generarNumeroRequisicion();

        // Crear requisición
        $requisicion = Requisicion::create([
            'numero_requisicion' => $numeroRequisicion,
            'estado' => Requisicion::ESTADO_BORRADOR,
            'usuario_id' => $usuarioId,
            'unidad_requirente' => $data['unidad_requirente'] ?? null,
            'proveedor_nombre' => $data['proveedor_nombre'],
            'proveedor_nit' => $data['proveedor_nit'] ?? null,
            'proveedor_direccion' => $data['proveedor_direccion'] ?? null,
            'proveedor_telefono' => $data['proveedor_telefono'] ?? null,
            'moneda' => $data['moneda'] ?? 'GTQ',
            'forma_pago' => $data['forma_pago'] ?? null,
            'fecha_solicitud' => $data['fecha_solicitud'] ?? date('Y-m-d'),
            'causal_compra' => $data['causal_compra'] ?? null,
            'justificacion' => $data['justificacion'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ]);

        // Registrar en historial
        HistorialRequisicion::registrarCreacion($requisicion->id, $usuarioId);

        // Disparar evento
        EventDispatcher::dispatch('requisicion.creada', [
            'requisicion_id' => $requisicion->id,
            'usuario_id' => $usuarioId
        ]);

        return $requisicion;
    }

    /**
     * Envía requisición a revisión
     */
    public function enviarARevision(int $requisicionId, int $usuarioId): bool
    {
        $requisicion = Requisicion::find($requisicionId);
        
        if (!$requisicion) {
            throw new \Exception("Requisición no encontrada");
        }

        if (!$requisicion->esBorrador()) {
            throw new \Exception("Solo se pueden enviar a revisión requisiciones en borrador");
        }

        // Validar que tenga items y distribución
        if ($requisicion->items()->count() === 0) {
            throw new \Exception("La requisición debe tener al menos un ítem");
        }

        if ($requisicion->distribucionCentros()->count() === 0) {
            throw new \Exception("La requisición debe tener distribución por centros de costo");
        }

        // Enviar a revisión (esto crea la autorización de revisión)
        $resultado = $requisicion->enviarARevision($usuarioId);

        if ($resultado) {
            EventDispatcher::dispatch('requisicion.enviada_revision', [
                'requisicion_id' => $requisicionId,
                'usuario_id' => $usuarioId
            ]);
        }

        return $resultado;
    }

    /**
     * Aprueba una revisión
     */
    public function aprobarRevision(int $requisicionId, int $usuarioId, string $comentarios = null): bool
    {
        $requisicion = Requisicion::find($requisicionId);
        
        if (!$requisicion) {
            throw new \Exception("Requisición no encontrada");
        }

        if (!$requisicion->estaPendienteRevision()) {
            throw new \Exception("La requisición no está pendiente de revisión");
        }

        // Verificar permisos de revisor
        if (!$this->esRevisor($usuarioId)) {
            throw new \Exception("El usuario no tiene permisos de revisor");
        }

        $resultado = $requisicion->aprobarRevision($usuarioId, $comentarios);

        if ($resultado) {
            EventDispatcher::dispatch('revision.aprobada', [
                'requisicion_id' => $requisicionId,
                'usuario_id' => $usuarioId,
                'comentarios' => $comentarios
            ]);
        }

        return $resultado;
    }

    /**
     * Rechaza una revisión
     */
    public function rechazarRevision(int $requisicionId, int $usuarioId, string $motivo): bool
    {
        $requisicion = Requisicion::find($requisicionId);
        
        if (!$requisicion) {
            throw new \Exception("Requisición no encontrada");
        }

        if (!$requisicion->estaPendienteRevision()) {
            throw new \Exception("La requisición no está pendiente de revisión");
        }

        // Verificar permisos de revisor
        if (!$this->esRevisor($usuarioId)) {
            throw new \Exception("El usuario no tiene permisos de revisor");
        }

        $resultado = $requisicion->rechazarRevision($usuarioId, $motivo);

        if ($resultado) {
            EventDispatcher::dispatch('revision.rechazada', [
                'requisicion_id' => $requisicionId,
                'usuario_id' => $usuarioId,
                'motivo' => $motivo
            ]);
        }

        return $resultado;
    }

    /**
     * Procesa autorización por centro de costo
     */
    public function procesarAutorizacionCentro(int $autorizacionId, string $accion, string $comentarios, string $usuarioEmail): array
    {
        if (!in_array($accion, ['aprobar', 'rechazar'])) {
            return ['success' => false, 'error' => 'Acción inválida'];
        }

        $autorizacion = Autorizacion::find($autorizacionId);
        
        if (!$autorizacion) {
            return ['success' => false, 'error' => 'Autorización no encontrada'];
        }

        if (!$autorizacion->estaPendiente()) {
            return ['success' => false, 'error' => 'La autorización ya fue procesada'];
        }

        // Verificar que el usuario sea el autorizador asignado
        if ($autorizacion->autorizador_email !== $usuarioEmail) {
            return ['success' => false, 'error' => 'No tienes permisos para esta autorización'];
        }

        try {
            if ($accion === 'aprobar') {
                $resultado = $autorizacion->aprobar($comentarios);
                $mensaje = 'Autorización aprobada exitosamente';
            } else {
                $resultado = $autorizacion->rechazar($comentarios);
                $mensaje = 'Autorización rechazada';
            }

            if ($resultado) {
                EventDispatcher::dispatch('autorizacion.' . ($accion === 'aprobar' ? 'aprobada' : 'rechazada'), [
                    'autorizacion_id' => $autorizacionId,
                    'requisicion_id' => $autorizacion->requisicion_id,
                    'usuario_email' => $usuarioEmail,
                    'comentarios' => $comentarios
                ]);

                return ['success' => true, 'message' => $mensaje];
            } else {
                return ['success' => false, 'error' => 'Error al procesar la autorización'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene autorizaciones pendientes para un usuario
     */
    public function obtenerAutorizacionesPendientes(string $usuarioEmail): array
    {
        return Autorizacion::pendientesParaAutorizador($usuarioEmail);
    }

    /**
     * Obtiene requisiciones pendientes de revisión
     */
    public function obtenerRequisicionesPendientesRevision(): array
    {
        return Requisicion::porEstado(Requisicion::ESTADO_PENDIENTE_REVISION);
    }

    /**
     * Obtiene el resumen de una requisición
     */
    public function obtenerResumenRequisicion(int $requisicionId): array
    {
        $requisicion = Requisicion::find($requisicionId);
        
        if (!$requisicion) {
            throw new \Exception("Requisición no encontrada");
        }

        return [
            'requisicion' => $requisicion,
            'items' => $requisicion->items,
            'distribucion_centros' => $requisicion->distribucionCentros,
            'autorizaciones' => $requisicion->autorizaciones,
            'progreso' => $requisicion->progresoAutorizacion(),
            'historial' => $requisicion->historial
        ];
    }

    /**
     * Verifica si un usuario puede autorizar una requisición específica
     */
    public function puedeAutorizar(int $requisicionId, string $usuarioEmail): array
    {
        $autorizacionesPendientes = Autorizacion::where('requisicion_id', $requisicionId)
            ->where('autorizador_email', $usuarioEmail)
            ->where('estado', Autorizacion::ESTADO_PENDIENTE)
            ->get();

        if ($autorizacionesPendientes->isEmpty()) {
            return [
                'puede_autorizar' => false,
                'motivo_rechazo' => 'No tienes autorizaciones pendientes para esta requisición'
            ];
        }

        return [
            'puede_autorizar' => true,
            'autorizaciones_pendientes' => $autorizacionesPendientes
        ];
    }

    /**
     * Obtiene estadísticas del sistema
     */
    public function obtenerEstadisticas(): array
    {
        $pdo = Requisicion::getConnection();

        // Estadísticas generales
        $stmt = $pdo->prepare("
            SELECT 
                estado,
                COUNT(*) as cantidad
            FROM requisiciones
            GROUP BY estado
        ");
        $stmt->execute();
        $estadosPorCantidad = $stmt->fetchAll();

        // Autorizaciones pendientes por tipo
        $stmt = $pdo->prepare("
            SELECT 
                tipo,
                COUNT(*) as pendientes
            FROM autorizaciones
            WHERE estado = 'pendiente'
            GROUP BY tipo
        ");
        $stmt->execute();
        $autorizacionesPendientesPorTipo = $stmt->fetchAll();

        // Tiempo promedio de respuesta
        $estadisticasAutorizacion = Autorizacion::estadisticas();

        return [
            'estados_requisiciones' => $estadosPorCantidad,
            'autorizaciones_pendientes_por_tipo' => $autorizacionesPendientesPorTipo,
            'estadisticas_autorizacion' => $estadisticasAutorizacion
        ];
    }

    /**
     * Valida datos de requisición
     */
    private function validarDatosRequisicion(array $data): void
    {
        if (empty($data['proveedor_nombre'])) {
            throw new \Exception("El nombre del proveedor es requerido");
        }

        if (isset($data['monto_total']) && $data['monto_total'] < 0) {
            throw new \Exception("El monto total no puede ser negativo");
        }

        // Agregar más validaciones según sea necesario
    }

    /**
     * Verifica si un usuario es revisor
     */
    private function esRevisor(int $usuarioId): bool
    {
        // Aquí implementar la lógica de verificación de permisos
        // Por ahora, simplificado
        $pdo = Requisicion::getConnection();
        $stmt = $pdo->prepare("SELECT is_revisor FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $resultado = $stmt->fetchColumn();
        
        return (bool) $resultado;
    }
}