<?php
/**
 * AutorizacionService v3.0
 * 
 * Servicio actualizado para trabajar con la nueva arquitectura v3.0:
 * 
 * CAMBIOS PRINCIPALES v3.0:
 * 1. Manejo de autorizaciones especiales con nueva tabla 'autorizaciones'
 * 2. Compatibilidad con flujo híbrido (usa AutorizacionFlujo para gestión principal)
 * 3. Métodos actualizados para forma de pago y cuenta contable
 * 4. Logging mejorado y transacciones robustas
 * 
 * FUNCIONALIDADES:
 * - Revisión inicial (siempre requerida)
 * - Autorización por centros de costo
 * - Autorización especial por forma de pago
 * - Autorización especial por cuenta contable
 * 
 * @package RequisicionesMVC\Services
 * @version 3.0
 */

namespace App\Services;

use App\Models\Model;
use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionFlujoAdaptador;
use App\Models\AutorizacionCentroCosto;
use App\Models\AutorizacionCentroCostoAdaptador;
use App\Models\AutorizadorMetodoPago;
use App\Models\AutorizadorCuentaContable;
use App\Models\OrdenCompra;
use App\Models\Usuario;
use App\Models\HistorialRequisicion;

class AutorizacionService extends Model
{
    // Propiedades requeridas para extender Model (no las usa, pero son requeridas)
    protected static $table = 'autorizaciones'; // Tabla por defecto
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    /**
     * Servicio de notificaciones
     * @var NotificacionService
     */
    private $notificacionService;

    /**
     * Constructor
     * 
     * @param NotificacionService $notificacionService Servicio de notificaciones opcional
     */
    public function __construct(NotificacionService $notificacionService = null)
    {
        try {
            $this->notificacionService = $notificacionService ?? new NotificacionService();
        } catch (\Exception $e) {
            // Si falla crear NotificacionService, crear un mock básico
            error_log("Warning: Failed to create NotificacionService, using mock: " . $e->getMessage());
            $this->notificacionService = new class {
                public function notificarAprobacionRevision($ordenId) {
                    error_log("Mock notification: orden $ordenId aprobada");
                    return true;
                }
            };
        }
    }

    /**
     * Cuenta autorizaciones especiales pendientes (forma de pago / cuenta contable)
     *
     * @param int $ordenId
     * @return int
     */
    private function countAutorizacionesEspecialesPendientes(int $ordenId): int
    {
        try {
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM autorizaciones
                WHERE requisicion_id = ?
                  AND tipo IN ('forma_pago', 'cuenta_contable')
                  AND estado = 'pendiente'
            ");
            $stmt->execute([$ordenId]);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            error_log("Error contando autorizaciones especiales pendientes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Indica si existen autorizaciones especiales pendientes para una requisición
     *
     * @param int $ordenId
     * @return bool
     */
    private function tieneAutorizacionesEspecialesPendientes(int $ordenId): bool
    {
        return $this->countAutorizacionesEspecialesPendientes($ordenId) > 0;
    }

    /**
     * Exposición pública para verificar autorizaciones especiales pendientes
     *
     * @param int $ordenId
     * @return bool
     */
    public function existenAutorizacionesEspecialesPendientes(int $ordenId): bool
    {
        return $this->tieneAutorizacionesEspecialesPendientes($ordenId);
    }

    // ========================================================================
    // MÉTODOS DE BÚSQUEDA CENTRALIZADOS
    // ========================================================================

    /**
     * Busca un flujo de autorización por ID de orden de compra
     * 
     * @param int $ordenId ID de la orden de compra
     * @return array|null Datos del flujo o null si no existe
     */
    private function findFlujoByOrdenId($ordenId)
    {
        return AutorizacionFlujoAdaptador::porOrdenCompra($ordenId);
    }

    /**
     * Busca un flujo de autorización por ID de flujo
     * 
     * @param int $flujoId ID del flujo de autorización
     * @return array|null Datos del flujo o null si no existe
     */
    private function findFlujoByFlujoId($flujoId)
    {
        $flujo = AutorizacionFlujo::find($flujoId);
        
        // Convertir objeto a array para consistencia
        if (is_object($flujo)) {
            return [
                'id' => $flujo->id,
                'orden_compra_id' => $flujo->orden_compra_id,
                'estado' => $flujo->estado,
                'revisor_email' => $flujo->revisor_email ?? null,
                'revisor_comentario' => $flujo->revisor_comentario ?? null,
                'revisor_fecha' => $flujo->revisor_fecha ?? null,
            ];
        }
        
        return $flujo; // Ya es array o null
    }

    /**
     * Valida que un flujo esté en el estado esperado
     * 
     * @param array $flujo Datos del flujo
     * @param string $estadoEsperado Estado que debe tener el flujo
     * @return array|null Error si no está en el estado correcto, null si está bien
     */
    private function validateEstadoFlujo($flujo, $estadoEsperado)
    {
        $estadoActual = $flujo['estado'];
        
        if ($estadoActual !== $estadoEsperado) {
            $mensajesEstado = [
                'pendiente_autorizacion' => 'La requisición ya fue aprobada en revisión y está pendiente de autorización por centros de costo',
                'autorizado' => 'La requisición ya está completamente autorizada',
                'rechazado' => 'La requisición fue rechazada previamente',
                'rechazado_revision' => 'La requisición ya fue rechazada en la revisión'
            ];
            
            $mensajeDetallado = $mensajesEstado[$estadoActual] ?? "La requisición no está en estado $estadoEsperado";
            
            return [
                'success' => false,
                'error' => "$mensajeDetallado (Estado actual: $estadoActual)",
                'code' => 'INVALID_STATE',
                'estado_actual' => $estadoActual
            ];
        }
        
        return null; // Sin error
    }

    // ========================================================================
    // MÉTODO PRINCIPAL REFACTORIZADO
    // ========================================================================

    /**
     * Aprueba una requisición en nivel de revisión (MÉTODO PRINCIPAL REFACTORIZADO)
     * 
     * CAMBIO IMPORTANTE: Ahora busca por orden_compra_id PRIMERO
     * porque el controlador normalmente pasa el ID de la orden
     * 
     * @param int $idAmbiguo ID que puede ser de orden o flujo (se asume orden por defecto)
     * @param int $usuarioId ID del usuario revisor  
     * @param string $comentario Comentarios opcionales
     * @return array Resultado
     */
    public function aprobarRevision($idAmbiguo, $usuarioId, $comentario = '')
    {
        try {
            error_log("=== APROBACIÓN REVISIÓN REFACTORIZADA ===");
            error_log("ID recibido: $idAmbiguo (buscando como orden ID primero)");
            error_log("Usuario ID: $usuarioId");
            
            // Verificar permisos de revisor PRIMERO
            if (!$this->esRevisor($usuarioId)) {
                error_log("Usuario $usuarioId no es revisor");
                return [
                    'success' => false,
                    'error' => 'El usuario no tiene permisos de revisor',
                    'code' => 'NOT_REVIEWER'
                ];
            }

            // ESTRATEGIA 1: Buscar por orden_compra_id (MÁS COMÚN)
            error_log("Estrategia 1: Buscando flujo por orden_compra_id = $idAmbiguo");
            $flujo = $this->findFlujoByOrdenId($idAmbiguo);
            
            if ($flujo) {
                error_log("✅ Flujo encontrado por orden ID: {$flujo['id']}, Estado: {$flujo['estado']}");
                return $this->ejecutarAprobacionRevision($flujo, $usuarioId, $comentario);
            }

            // ESTRATEGIA 2: Buscar por flujo_id (FALLBACK)
            error_log("Estrategia 2: No encontrado como orden, buscando como flujo_id = $idAmbiguo");
            $flujo = $this->findFlujoByFlujoId($idAmbiguo);
            
            if ($flujo) {
                error_log("✅ Flujo encontrado por flujo ID: {$flujo['id']}, Orden: {$flujo['orden_compra_id']}, Estado: {$flujo['estado']}");
                return $this->ejecutarAprobacionRevision($flujo, $usuarioId, $comentario);
            }

            // No encontrado en ninguna estrategia
            error_log("❌ No se encontró flujo con ID $idAmbiguo en ninguna estrategia");
            return [
                'success' => false,
                'error' => "No se encontró flujo de autorización para ID #$idAmbiguo",
                'code' => 'FLOW_NOT_FOUND'
            ];

        } catch (\Exception $e) {
            error_log("Error en aprobarRevision refactorizado: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'code' => 'SERVER_ERROR'
            ];
        }
    }

    /**
     * Ejecuta la aprobación de revisión (lógica común)
     * 
     * @param array $flujo Datos del flujo
     * @param int $usuarioId ID del usuario revisor
     * @param string $comentario Comentarios opcionales
     * @return array Resultado
     */
    private function ejecutarAprobacionRevision($flujo, $usuarioId, $comentario = '')
    {
        try {
            $flujoId = $flujo['id'];
            $ordenId = $flujo['orden_compra_id'];
            
            error_log("Ejecutando aprobación: Flujo $flujoId, Orden $ordenId");

            // Validar estado
            $errorEstado = $this->validateEstadoFlujo($flujo, AutorizacionFlujo::ESTADO_PENDIENTE_REVISION);
            if ($errorEstado) {
                error_log("Error de estado: " . json_encode($errorEstado));
                return $errorEstado;
            }

            // Ejecutar aprobación en modelo
            error_log("Llamando AutorizacionFlujo::aprobarRevision($flujoId, $usuarioId, '$comentario')");
            $resultado = AutorizacionFlujo::aprobarRevision($flujoId, $usuarioId, $comentario);
            
            if (!$resultado) {
                error_log("❌ Error en AutorizacionFlujo::aprobarRevision()");
                return [
                    'success' => false,
                    'error' => 'Error al aprobar la revisión en el modelo',
                    'code' => 'APPROVAL_ERROR'
                ];
            }

            error_log("✅ Aprobación exitosa en modelo");

            // Notificar (no crítico si falla)
            try {
                $this->notificacionService->notificarAprobacionRevision($ordenId);
                error_log("✅ Notificación enviada");
            } catch (\Exception $e) {
                error_log("⚠️ Error en notificación (no crítico): " . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Revisión aprobada exitosamente',
                'flujo_id' => $flujoId,
                'orden_id' => $ordenId
            ];

        } catch (\Exception $e) {
            error_log("Error ejecutando aprobación: " . $e->getMessage());
            throw $e;
        }
    }

    // ========================================================================
    // MÉTODOS ESPECÍFICOS (PARA USO FUTURO)
    // ========================================================================

    /**
     * Aprueba una requisición específicamente por ID de orden
     * 
     * @param int $ordenId ID de la orden de compra
     * @param int $usuarioId ID del usuario revisor
     * @param string $comentario Comentarios opcionales
     * @return array Resultado
     */
    public function aprobarRevisionPorOrden($ordenId, $usuarioId, $comentario = '')
    {
        $flujo = $this->findFlujoByOrdenId($ordenId);
        if (!$flujo) {
            return [
                'success' => false,
                'error' => "No se encontró flujo para la orden #$ordenId",
                'code' => 'FLOW_NOT_FOUND'
            ];
        }
        
        return $this->ejecutarAprobacionRevision($flujo, $usuarioId, $comentario);
    }

    /**
     * Aprueba una requisición específicamente por ID de flujo
     * 
     * @param int $flujoId ID del flujo de autorización
     * @param int $usuarioId ID del usuario revisor
     * @param string $comentario Comentarios opcionales
     * @return array Resultado
     */
    public function aprobarRevisionPorFlujo($flujoId, $usuarioId, $comentario = '')
    {
        $flujo = $this->findFlujoByFlujoId($flujoId);
        if (!$flujo) {
            return [
                'success' => false,
                'error' => "No se encontró flujo con ID #$flujoId",
                'code' => 'FLOW_NOT_FOUND'
            ];
        }
        
        return $this->ejecutarAprobacionRevision($flujo, $usuarioId, $comentario);
    }

    // ========================================================================
    // MÉTODOS EXISTENTES (SIN CAMBIOS)
    // ========================================================================

    /**
     * Inicia el flujo de autorización
     * 
     * @param int $ordenId
     * @return array
     */
    public function iniciarFlujo($ordenId)
    {
        try {
            // Verificar que no existe ya un flujo
            $flujoExistente = AutorizacionFlujo::porOrdenCompra($ordenId);
            if ($flujoExistente) {
                return [
                    'success' => false,
                    'error' => 'Ya existe un flujo de autorización para esta orden',
                    'code' => 'FLOW_EXISTS'
                ];
            }

            // Crear el flujo
            $flujoId = AutorizacionFlujo::iniciarFlujo($ordenId);
            
            if (!$flujoId) {
                return [
                    'success' => false,
                    'error' => 'Error al crear el flujo de autorización',
                    'code' => 'FLOW_CREATION_ERROR'
                ];
            }

            return [
                'success' => true,
                'message' => 'Flujo de autorización iniciado',
                'flujo_id' => $flujoId
            ];
        } catch (\Exception $e) {
            error_log("Error iniciando flujo: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'FLOW_INIT_ERROR'
            ];
        }
    }

    /**
     * Inicia el flujo de autorización (alias para compatibilidad)
     * 
     * @param int $ordenId
     * @return array
     */
    public function iniciarFlujoAutorizacion($ordenId)
    {
        return $this->iniciarFlujo($ordenId);
    }

    /**
     * Rechaza una requisición en revisión
     * 
     * @param int $flujoIdOrOrdenId
     * @param int $usuarioId
     * @param string $motivo
     * @return array
     */
    public function rechazarRevision($flujoIdOrOrdenId, $usuarioId, $motivo)
    {
        try {
            if (!$this->esRevisor($usuarioId)) {
                return [
                    'success' => false,
                    'error' => 'El usuario no tiene permisos de revisor',
                    'code' => 'NOT_REVIEWER'
                ];
            }

            // Buscar flujo por orden primero
            $flujo = $this->findFlujoByOrdenId($flujoIdOrOrdenId);
            if (!$flujo) {
                $flujo = $this->findFlujoByFlujoId($flujoIdOrOrdenId);
                if (!$flujo) {
                    return [
                        'success' => false,
                        'error' => 'Flujo de autorización no encontrado',
                        'code' => 'FLOW_NOT_FOUND'
                    ];
                }
            }

            $flujoId = $flujo['id'];
            $ordenId = $flujo['orden_compra_id'];

            $resultado = AutorizacionFlujo::rechazarRevision($flujoId, $usuarioId, $motivo);
            
            if (!$resultado) {
                return [
                    'success' => false,
                    'error' => 'Error al rechazar la revisión',
                    'code' => 'REJECTION_ERROR'
                ];
            }

            return [
                'success' => true,
                'message' => 'Requisición rechazada exitosamente'
            ];
        } catch (\Exception $e) {
            error_log("Error en rechazarRevision: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'code' => 'SERVER_ERROR'
            ];
        }
    }

    /**
     * Autoriza un centro de costo
     * 
     * @param int $autorizacionId
     * @param string $autorizadorEmail
     * @param string $comentario
     * @return array
     */
    public function autorizarCentroCosto($autorizacionId, $autorizadorEmail, $comentario = '')
    {
        try {
            $autorizacion = AutorizacionCentroCostoAdaptador::find($autorizacionId);
            if (!$autorizacion) {
                return [
                    'success' => false,
                    'error' => 'Autorización no encontrada',
                    'code' => 'AUTHORIZATION_NOT_FOUND'
                ];
            }

            $ordenId = (int)($autorizacion['requisicion_id'] ?? $autorizacion['autorizacion_flujo_id'] ?? 0);
            if ($ordenId && $this->tieneAutorizacionesEspecialesPendientes($ordenId)) {
                return [
                    'success' => false,
                    'error' => 'Aún existen autorizaciones especiales pendientes. Deben completarse antes de autorizar los centros de costo.',
                    'code' => 'SPECIAL_PENDING'
                ];
            }

            $resultado = AutorizacionCentroCostoAdaptador::autorizar($autorizacionId, $autorizadorEmail, $comentario);
            
            if (!$resultado) {
                return [
                    'success' => false,
                    'error' => 'Error al autorizar el centro de costo',
                    'code' => 'AUTHORIZATION_ERROR'
                ];
            }

            // Verificar si todas las autorizaciones están completas
            if ($autorizacion) {
                $flujoIdReal = $autorizacion['autorizacion_flujo_id'] ?? null;
                $ordenId = (int)($autorizacion['requisicion_id'] ?? $flujoIdReal ?? 0);

                if ($ordenId) {
                    $flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
                    if ($flujo && isset($flujo['id'])) {
                        $flujoIdReal = $flujo['id'];
                    }
                }

                if ($flujoIdReal) {
                    $this->verificarYCompletarFlujo($flujoIdReal);
                }
            }

            return [
                'success' => true,
                'message' => 'Centro de costo autorizado exitosamente'
            ];
        } catch (\Exception $e) {
            error_log("Error en autorizarCentroCosto: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'code' => 'SERVER_ERROR'
            ];
        }
    }

    /**
     * Rechaza un centro de costo
     * 
     * @param int $autorizacionId
     * @param string $autorizadorEmail
     * @param string $motivo
     * @return array
     */
    public function rechazarCentroCosto($autorizacionId, $autorizadorEmail, $motivo)
    {
        try {
            $autorizacion = AutorizacionCentroCostoAdaptador::find($autorizacionId);
            if (!$autorizacion) {
                return [
                    'success' => false,
                    'error' => 'Autorización no encontrada',
                    'code' => 'AUTHORIZATION_NOT_FOUND'
                ];
            }

            $resultado = AutorizacionCentroCostoAdaptador::rechazar($autorizacionId, $autorizadorEmail, $motivo);
            
            if (!$resultado) {
                return [
                    'success' => false,
                    'error' => 'Error al rechazar el centro de costo',
                    'code' => 'REJECTION_ERROR'
                ];
            }

            // Marcar flujo como rechazado
            $flujoIdReal = $autorizacion['autorizacion_flujo_id'] ?? null;
            if ($ordenId) {
                $flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
                if ($flujo && isset($flujo['id'])) {
                    $flujoIdReal = $flujo['id'];
                }
            }

            if ($flujoIdReal) {
                AutorizacionFlujo::marcarComoRechazado($flujoIdReal);
            }

            if ($ordenId) {
                $this->notificacionService->notificarRechazo($ordenId, $motivo);
            }

            return [
                'success' => true,
                'message' => 'Centro de costo rechazado exitosamente'
            ];
        } catch (\Exception $e) {
            error_log("Error en rechazarCentroCosto: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'code' => 'SERVER_ERROR'
            ];
        }
    }

    /**
     * Verifica si un usuario es revisor
     * 
     * @param int $usuarioId
     * @return bool
     */
    public function esRevisor($usuarioId)
    {
        $usuario = Usuario::find($usuarioId);
        return $usuario && $usuario->isRevisor();
    }

    /**
     * Verifica si un usuario es autorizador para una orden específica
     * 
     * @param string $email
     * @param int $ordenId
     * @return bool
     */
    public function esAutorizadorDe($email, $ordenId)
    {
        $flujo = $this->findFlujoByOrdenId($ordenId);
        if (!$flujo) {
            return false;
        }

        $autorizaciones = AutorizacionCentroCostoAdaptador::porFlujo($flujo['id']);
        
        foreach ($autorizaciones as $auth) {
            if ($auth['autorizador_email'] === $email) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica y completa el flujo si todas las autorizaciones están listas
     * 
     * @param int $flujoId
     * @return bool
     */
    public function verificarYCompletarFlujo($flujoId)
    {
        $flujoAntes = AutorizacionFlujo::find($flujoId);
        if (!$flujoAntes) {
            return false;
        }

        $estadoAnterior = $flujoAntes['estado'] ?? ($flujoAntes->estado ?? null);
        $ordenId = $flujoAntes['orden_compra_id'] ?? ($flujoAntes->orden_compra_id ?? null);

        $resultado = AutorizacionFlujo::verificarYActualizarEstado($flujoId);

        if (!$resultado || !$ordenId) {
            return $resultado;
        }

        $flujoDespues = AutorizacionFlujo::find($flujoId);
        if (!$flujoDespues) {
            return $resultado;
        }

        $nuevoEstado = $flujoDespues['estado'] ?? ($flujoDespues->estado ?? null);

        if ($nuevoEstado && $nuevoEstado !== $estadoAnterior) {
            if ($nuevoEstado === AutorizacionFlujo::ESTADO_AUTORIZADO) {
                $this->notificacionService->notificarAutorizacionCompleta($ordenId);
            }
        }

        return $resultado;
    }

    /**
     * Obtiene las autorizaciones pendientes para una orden
     * 
     * @param int $ordenId
     * @return array
     */
    public function getAutorizacionesPendientesDetalle($ordenId)
    {
        $flujo = $this->findFlujoByOrdenId($ordenId);
        if (!$flujo) {
            return [];
        }

        $todasAutorizaciones = AutorizacionCentroCostoAdaptador::porFlujo($flujo['id']);
        
        // Filtrar solo las pendientes
        return array_filter($todasAutorizaciones, function($auth) {
            return $auth['estado'] === 'pendiente';
        });
    }

    /**
     * Obtiene las autorizaciones pendientes de un usuario específico
     * 
     * @param string $usuarioEmail Email del usuario autorizador
     * @return array Lista de autorizaciones pendientes
     */
    public function getAutorizacionesPendientes($usuarioEmail)
    {
        try {
            $autorizaciones = AutorizacionCentroCostoAdaptador::pendientesPorAutorizador($usuarioEmail);

            $autorizaciones = array_filter($autorizaciones, function ($auth) {
                $ordenId = $auth['orden_id'] ?? $auth['requisicion_id'] ?? null;

                if (!$ordenId) {
                    return true;
                }

                return !$this->tieneAutorizacionesEspecialesPendientes((int)$ordenId);
            });

            return array_values($autorizaciones);
        } catch (\Exception $e) {
            error_log("Error obteniendo autorizaciones pendientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene revisiones pendientes (flujos pendientes de revisión)
     * 
     * @return array
     */
    public function getRevisionesPendientes()
    {
        try {
            return AutorizacionFlujo::porEstado('pendiente_revision', 20);
        } catch (\Exception $e) {
            error_log("Error obteniendo revisiones pendientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene requisiciones pendientes de revisión (alias para compatibilidad)
     * 
     * @return array
     */
    public function getRequisicionesPendientesRevision()
    {
        return $this->getRevisionesPendientes();
    }

    /**
     * Cuenta las autorizaciones pendientes de un usuario
     * 
     * @param string $usuarioEmail
     * @return int
     */
    public function contarPendientes($usuarioEmail)
    {
        try {
            $pendientes = $this->getAutorizacionesPendientes($usuarioEmail);
            return count($pendientes);
        } catch (\Exception $e) {
            error_log("Error contando pendientes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verifica si un usuario puede autorizar una orden específica
     * 
     * @param string $usuarioEmail
     * @param int $ordenId
     * @return bool
     */
    public function puedeAutorizar($usuarioEmail, $ordenId)
    {
        return $this->esAutorizadorDe($usuarioEmail, $ordenId);
    }

    /**
     * Obtiene el progreso de autorización de un flujo
     * 
     * @param int $flujoId ID del flujo de autorización
     * @return array|null Progreso de autorización o null si no existe
     */
    public function getProgresoAutorizacion($flujoId)
    {
        try {
            return AutorizacionCentroCostoAdaptador::getProgreso($flujoId);
        } catch (\Exception $e) {
            error_log("Error obteniendo progreso de autorización: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Aprueba autorización especial de forma de pago (v3.0)
     * 
     * @param int $autorizacionId ID de la autorización en tabla autorizaciones
     * @param string $autorizadorEmail Email del autorizador
     * @param string $comentario Comentario opcional
     * @return array Resultado
     */
    public function aprobarAutorizacionPago($autorizacionId, $autorizadorEmail, $comentario = '')
    {
        try {
            $pdo = static::getConnection();
            $pdo->beginTransaction();

            // Verificar que la autorización existe y es del autorizador correcto
            $stmt = $pdo->prepare("
                SELECT * FROM autorizaciones 
                WHERE id = ? AND autorizador_email = ? AND tipo = 'forma_pago' AND estado = 'pendiente'
            ");
            $stmt->execute([$autorizacionId, $autorizadorEmail]);
            $autorizacion = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$autorizacion) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Autorización no encontrada o ya procesada',
                    'code' => 'AUTHORIZATION_NOT_FOUND'
                ];
            }

            $ordenId = (int)$autorizacion['requisicion_id'];
            $pendientesAntes = $this->tieneAutorizacionesEspecialesPendientes($ordenId);

            // Aprobar la autorización
            $stmt = $pdo->prepare("
                UPDATE autorizaciones 
                SET estado = 'aprobada', 
                    comentario = ?, 
                    fecha_decision = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$comentario, $autorizacionId]);

            // Registrar en historial
            HistorialRequisicion::registrarAprobacion(
                $autorizacion['requisicion_id'],
                null, // No tenemos usuario_id del email
                'Autorización Especial - Forma de Pago',
                $comentario ?: "Aprobada por: $autorizadorEmail"
            );

            $pdo->commit();

            if ($pendientesAntes && !$this->tieneAutorizacionesEspecialesPendientes($ordenId)) {
                $this->notificacionService->notificarAutorizadoresCentros($ordenId);
            }

            return [
                'success' => true,
                'message' => 'Autorización de forma de pago aprobada exitosamente'
            ];
        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error en aprobarAutorizacionPago v3.0: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'code' => 'SERVER_ERROR'
            ];
        }
    }

    /**
     * Rechaza autorización especial de forma de pago (v3.0)
     * 
     * @param int $autorizacionId ID de la autorización en tabla autorizaciones
     * @param string $autorizadorEmail Email del autorizador
     * @param string $motivo Motivo del rechazo
     * @return array Resultado
     */
    public function rechazarAutorizacionPago($autorizacionId, $autorizadorEmail, $motivo)
    {
        try {
            $pdo = static::getConnection();
            $pdo->beginTransaction();

            // Verificar que la autorización existe y es del autorizador correcto
            $stmt = $pdo->prepare("
                SELECT * FROM autorizaciones 
                WHERE id = ? AND autorizador_email = ? AND tipo = 'forma_pago' AND estado = 'pendiente'
            ");
            $stmt->execute([$autorizacionId, $autorizadorEmail]);
            $autorizacion = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$autorizacion) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Autorización no encontrada o ya procesada',
                    'code' => 'AUTHORIZATION_NOT_FOUND'
                ];
            }

            $ordenId = (int)$autorizacion['requisicion_id'];
            // Rechazar la autorización
            $stmt = $pdo->prepare("
                UPDATE autorizaciones 
                SET estado = 'rechazada', 
                    comentario = ?, 
                    fecha_decision = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$motivo, $autorizacionId]);

            // Registrar en historial
            HistorialRequisicion::registrarRechazo(
                $autorizacion['requisicion_id'],
                null, // No tenemos usuario_id del email
                "Autorización Especial de Forma de Pago rechazada por $autorizadorEmail: $motivo"
            );

            $pdo->commit();

            $flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
            if ($flujo) {
                AutorizacionFlujo::marcarComoRechazado($flujo['id']);
            }

            $this->notificacionService->notificarRechazo($ordenId, $motivo);

            return [
                'success' => true,
                'message' => 'Autorización de forma de pago rechazada'
            ];
        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error en rechazarAutorizacionPago v3.0: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'code' => 'SERVER_ERROR'
            ];
        }
    }

    /**
     * Aprueba autorización especial de cuenta contable (v3.0)
     * 
     * @param int $autorizacionId ID de la autorización en tabla autorizaciones
     * @param string $autorizadorEmail Email del autorizador
     * @param string $comentario Comentario opcional
     * @return array Resultado
     */
    public function aprobarAutorizacionCuenta($autorizacionId, $autorizadorEmail, $comentario = '')
    {
        try {
            $pdo = static::getConnection();
            $pdo->beginTransaction();

            // Verificar que la autorización existe y es del autorizador correcto
            $stmt = $pdo->prepare("
                SELECT * FROM autorizaciones 
                WHERE id = ? AND autorizador_email = ? AND tipo = 'cuenta_contable' AND estado = 'pendiente'
            ");
            $stmt->execute([$autorizacionId, $autorizadorEmail]);
            $autorizacion = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$autorizacion) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Autorización no encontrada o ya procesada',
                    'code' => 'AUTHORIZATION_NOT_FOUND'
                ];
            }

            $ordenId = (int)$autorizacion['requisicion_id'];
            $pendientesAntes = $this->tieneAutorizacionesEspecialesPendientes($ordenId);

            // Aprobar la autorización
            $stmt = $pdo->prepare("
                UPDATE autorizaciones 
                SET estado = 'aprobada', 
                    comentario = ?, 
                    fecha_decision = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$comentario, $autorizacionId]);

            // Registrar en historial
            HistorialRequisicion::registrarAprobacion(
                $autorizacion['requisicion_id'],
                null, // No tenemos usuario_id del email
                'Autorización Especial - Cuenta Contable',
                $comentario ?: "Aprobada por: $autorizadorEmail"
            );

            $pdo->commit();

            if ($pendientesAntes && !$this->tieneAutorizacionesEspecialesPendientes($ordenId)) {
                $this->notificacionService->notificarAutorizadoresCentros($ordenId);
            }

            return [
                'success' => true,
                'message' => 'Autorización de cuenta contable aprobada exitosamente'
            ];
        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error en aprobarAutorizacionCuenta v3.0: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'code' => 'SERVER_ERROR'
            ];
        }
    }

    /**
     * Rechaza autorización especial de cuenta contable (v3.0)
     * 
     * @param int $autorizacionId ID de la autorización en tabla autorizaciones
     * @param string $autorizadorEmail Email del autorizador
     * @param string $motivo Motivo del rechazo
     * @return array Resultado
     */
    public function rechazarAutorizacionCuenta($autorizacionId, $autorizadorEmail, $motivo)
    {
        try {
            $pdo = static::getConnection();
            $pdo->beginTransaction();

            // Verificar que la autorización existe y es del autorizador correcto
            $stmt = $pdo->prepare("
                SELECT * FROM autorizaciones 
                WHERE id = ? AND autorizador_email = ? AND tipo = 'cuenta_contable' AND estado = 'pendiente'
            ");
            $stmt->execute([$autorizacionId, $autorizadorEmail]);
            $autorizacion = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$autorizacion) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Autorización no encontrada o ya procesada',
                    'code' => 'AUTHORIZATION_NOT_FOUND'
                ];
            }

            $ordenId = (int)$autorizacion['requisicion_id'];

            // Rechazar la autorización
            $stmt = $pdo->prepare("
                UPDATE autorizaciones 
                SET estado = 'rechazada', 
                    comentario = ?, 
                    fecha_decision = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$motivo, $autorizacionId]);

            // Registrar en historial
            HistorialRequisicion::registrarRechazo(
                $autorizacion['requisicion_id'],
                null, // No tenemos usuario_id del email
                "Autorización Especial de Cuenta Contable rechazada por $autorizadorEmail: $motivo"
            );

            $pdo->commit();

            $flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
            if ($flujo) {
                AutorizacionFlujo::marcarComoRechazado($flujo['id']);
            }

            $this->notificacionService->notificarRechazo($ordenId, $motivo);

            return [
                'success' => true,
                'message' => 'Autorización de cuenta contable rechazada'
            ];
        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error en rechazarAutorizacionCuenta v3.0: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno del servidor',
                'code' => 'SERVER_ERROR'
            ];
        }
    }

    /**
     * Obtiene autorizaciones pendientes de forma de pago para un email (v3.0)
     * 
     * @param string $email Email del autorizador
     * @return array Lista de autorizaciones pendientes
     */
    public function getAutorizacionesPendientesPago($email)
    {
        try {
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.requisicion_id,
                    a.tipo,
                    a.metadata,
                    a.created_at,
                    oc.nombre_razon_social,
                    oc.monto_total,
                    oc.fecha
                FROM autorizaciones a
                INNER JOIN orden_compra oc ON a.requisicion_id = oc.id
                WHERE a.autorizador_email = ? 
                AND a.tipo = 'forma_pago' 
                AND a.estado = 'pendiente'
                ORDER BY a.created_at ASC
            ");
            $stmt->execute([$email]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error obteniendo autorizaciones pendientes de pago v3.0: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene autorizaciones pendientes de cuenta contable para un email (v3.0)
     * 
     * @param string $email Email del autorizador
     * @return array Lista de autorizaciones pendientes
     */
    public function getAutorizacionesPendientesCuenta($email)
    {
        try {
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.requisicion_id,
                    a.tipo,
                    a.cuenta_contable_id,
                    a.metadata,
                    a.created_at,
                    oc.nombre_razon_social,
                    oc.monto_total,
                    oc.fecha,
                    cc.descripcion as cuenta_nombre
                FROM autorizaciones a
                INNER JOIN orden_compra oc ON a.requisicion_id = oc.id
                LEFT JOIN cuenta_contable cc ON a.cuenta_contable_id = cc.id
                WHERE a.autorizador_email = ? 
                AND a.tipo = 'cuenta_contable' 
                AND a.estado = 'pendiente'
                ORDER BY a.created_at ASC
            ");
            $stmt->execute([$email]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error obteniendo autorizaciones pendientes de cuenta v3.0: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si un email es autorizador especial de forma de pago (v3.0)
     * 
     * @param string $email Email a verificar
     * @return bool
     */
    public function esAutorizadorPago($email)
    {
        try {
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM autorizadores_metodos_pago 
                WHERE autorizador_email = ?
            ");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            error_log("Error verificando autorizador de pago: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un email es autorizador especial de cuenta contable (v3.0)
     * 
     * @param string $email Email a verificar
     * @return bool
     */
    public function esAutorizadorCuenta($email)
    {
        try {
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM autorizadores_cuentas_contables 
                WHERE autorizador_email = ?
            ");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            error_log("Error verificando autorizador de cuenta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un email es autorizador de respaldo activo
     * 
     * @param string $email Email a verificar
     * @return bool
     */
    public function esAutorizadorRespaldo($email)
    {
        try {
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM autorizador_respaldo 
                WHERE autorizador_respaldo_email = ? 
                AND estado = 'activo'
                AND fecha_inicio <= CURDATE()
                AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
            ");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            error_log("Error verificando autorizador de respaldo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene todas las autorizaciones pendientes para un email (principales + respaldo + especiales)
     * 
     * @param string $email Email del autorizador
     * @return array Lista unificada de autorizaciones pendientes
     */
    public function getTodasAutorizacionesPendientes($email)
    {
        try {
            $autorizaciones = [];
            
            // 1. Autorizaciones de centro de costo (principales y respaldo)
            $centros = $this->getAutorizacionesPendientes($email);
            foreach ($centros as $auth) {
                $auth['tipo_flujo'] = 'centro_costo';
                $auth['prioridad'] = 2; // Normal
                $autorizaciones[] = $auth;
            }
            
            // 2. Autorizaciones especiales de forma de pago
            $pagos = $this->getAutorizacionesPendientesPago($email);
            foreach ($pagos as $auth) {
                $auth['tipo_flujo'] = 'forma_pago';
                $auth['prioridad'] = 1; // Alta (especiales tienen prioridad)
                $autorizaciones[] = $auth;
            }
            
            // 3. Autorizaciones especiales de cuenta contable
            $cuentas = $this->getAutorizacionesPendientesCuenta($email);
            foreach ($cuentas as $auth) {
                $auth['tipo_flujo'] = 'cuenta_contable';
                $auth['prioridad'] = 1; // Alta (especiales tienen prioridad)
                $autorizaciones[] = $auth;
            }
            
            // Ordenar por prioridad (especiales primero)
            usort($autorizaciones, function($a, $b) {
                return $a['prioridad'] - $b['prioridad'];
            });
            
            return $autorizaciones;
        } catch (\Exception $e) {
            error_log("Error obteniendo todas las autorizaciones: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene el tipo de autorizador para un email específico
     * 
     * @param string $email Email a verificar
     * @return array Información del tipo de autorizador
     */
    public function getTipoAutorizador($email)
    {
        $tipos = [];
        
        // Verificar centro de costo principal
        if ($this->esAutorizadorDe($email, null)) {
            $tipos[] = 'centro_costo_principal';
        }
        
        // Verificar respaldo
        if ($this->esAutorizadorRespaldo($email)) {
            $tipos[] = 'centro_costo_respaldo';
        }
        
        // Verificar especiales
        if ($this->esAutorizadorPago($email)) {
            $tipos[] = 'forma_pago_especial';
        }
        
        if ($this->esAutorizadorCuenta($email)) {
            $tipos[] = 'cuenta_contable_especial';
        }
        
        return [
            'tipos' => $tipos,
            'es_multiple' => count($tipos) > 1,
            'principal' => $tipos[0] ?? null
        ];
    }

    /**
     * Método simplificado para testing
     * 
     * @param int $flujoId
     * @param string $comentario
     * @return array
     */
    public function aprobarRevisionSimple($flujoId, $comentario = '')
    {
        try {
            error_log("AutorizacionService::aprobarRevisionSimple - Flujo: $flujoId");
            
            $resultado = AutorizacionFlujo::aprobarRevisionSimple($flujoId, $comentario);
            
            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Revisión aprobada exitosamente (método simple)'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al aprobar la revisión (método simple)',
                    'code' => 'APPROVAL_ERROR'
                ];
            }
        } catch (\Exception $e) {
            error_log("Error en AutorizacionService::aprobarRevisionSimple: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage(),
                'code' => 'SERVICE_ERROR'
            ];
        }
    }

    /**
     * Crea autorizaciones por centro de costo (método privado)
     * 
     * @param int $flujoId
     * @param int $ordenId
     * @return bool
     */
    private function crearAutorizacionesPorCentro($flujoId, $ordenId)
    {
        // Este método ya no se usa porque la lógica se movió al modelo
        // Se mantiene para compatibilidad
        return true;
    }
}