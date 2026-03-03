<?php
/**
 * FlujoValidacionService v3.0
 * 
 * Servicio para gestionar el flujo completo de validación de requisiciones.
 * Maneja todos los pasos desde la creación hasta la finalización.
 * 
 * FLUJOS SOPORTADOS:
 * 1. Revisión inicial (SIEMPRE requerida)
 * 2. Autorización por centro de costo
 * 3. Autorización especial por forma de pago
 * 4. Autorización especial por cuenta contable
 * 
 * @package RequisicionesMVC\Services
 * @version 3.0
 */

namespace App\Services;

use App\Models\Model;
use App\Models\AutorizacionFlujo;
use App\Models\Requisicion;
use App\Models\DistribucionGasto;
use App\Models\HistorialRequisicion;
use App\Repositories\AutorizacionCentroRepository;

class FlujoValidacionService extends Model
{
    private ?AutorizacionCentroRepository $centroRepository = null;

    private function centrosRepo(): AutorizacionCentroRepository
    {
        if ($this->centroRepository === null) {
            $this->centroRepository = new AutorizacionCentroRepository();
        }

        return $this->centroRepository;
    }

    /**
     * Inicia el flujo completo de validación para una orden de compra
     * 
     * @param int $ordenCompraId ID de la orden de compra
     * @return array Resultado con éxito y detalles del flujo
     */
    public function iniciarFlujo($ordenCompraId)
    {
        try {
            error_log("=== INICIANDO FLUJO DE VALIDACIÓN v3.0 ===");
            error_log("Orden de Compra ID: $ordenCompraId");

            // 1. Verificar que la orden existe
            $orden = Requisicion::find($ordenCompraId);
            if (!$orden) {
                throw new \Exception("Orden de compra no encontrada");
            }

            // 2. Verificar si ya existe un flujo
            $flujoExistente = AutorizacionFlujo::porOrdenCompra($ordenCompraId);
            if ($flujoExistente) {
                error_log("⚠️ Ya existe un flujo para esta orden. ID del flujo: " . $flujoExistente['id']);
                return [
                    'success' => false,
                    'error' => 'Ya existe un flujo de autorización para esta orden',
                    'flujo_id' => $flujoExistente['id']
                ];
            }

            // 3. Crear el flujo usando AutorizacionFlujo::iniciarFlujo()
            // Este método ya maneja sus propias transacciones
            $flujoId = AutorizacionFlujo::iniciarFlujo($ordenCompraId);
            
            if (!$flujoId) {
                throw new \Exception("Error al crear el flujo de autorización");
            }

            error_log("✅ Flujo creado con ID: $flujoId");

            // 4. Obtener el flujo creado para verificar sus propiedades
            $flujo = AutorizacionFlujo::find($flujoId);
            if (!$flujo) {
                throw new \Exception("No se pudo obtener el flujo creado");
            }

            // 5. Registrar inicio en historial (manejo seguro de errores)
            try {
                HistorialRequisicion::registrar(
                    $ordenCompraId,
                    'flujo_iniciado',
                    'Flujo de validación v3.0 iniciado automáticamente',
                    $orden->usuario_id ?? null
                );
            } catch (\Exception $historialException) {
                error_log("⚠️ Error registrando en historial (no crítico): " . $historialException->getMessage());
                // No fallar el flujo por errores de historial
            }

            // 6. Notificar a revisores sobre la nueva requisición (manejo seguro de errores)
            try {
                $notificacionService = new \App\Services\NotificacionService();
                $resultNotificacion = $notificacionService->notificarNuevaRequisicion($ordenCompraId);
                if ($resultNotificacion['success']) {
                    error_log("✅ Notificación enviada a revisores para requisición $ordenCompraId");
                } else {
                    error_log("⚠️ Error enviando notificación (no crítico): " . ($resultNotificacion['error'] ?? 'Error desconocido'));
                }
            } catch (\Exception $notificacionException) {
                error_log("⚠️ Error en notificación (no crítico): " . $notificacionException->getMessage());
                // No fallar el flujo por errores de notificación
            }

            // 7. Analizar qué tipos de autorización se requerirán
            $tiposRequeridos = $this->analizarAutorizacionesRequeridas($ordenCompraId, $flujo);

            error_log("✅ Flujo v3.0 iniciado exitosamente para orden $ordenCompraId");
            error_log("Flujo ID: $flujoId");
            error_log("Estado inicial: " . ($flujo->estado ?? 'N/A'));
            error_log("Tipos de autorización requeridos: " . implode(', ', $tiposRequeridos));

            return [
                'success' => true,
                'message' => 'Flujo de validación v3.0 iniciado exitosamente',
                'flujo_id' => $flujoId,
                'tipos_requeridos' => $tiposRequeridos,
                'estado' => $flujo->estado ?? 'pendiente_revision'
            ];

        } catch (\Exception $e) {
            error_log("❌ Error iniciando flujo v3.0: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Analiza qué tipos de autorización se requerirán para una orden
     * 
     * @param int $ordenCompraId
     * @param array $flujo Datos del flujo creado
     * @return array Tipos requeridos
     */
    private function analizarAutorizacionesRequeridas($ordenCompraId, $flujo)
    {
        $tipos = [];
        
        // 1. SIEMPRE: Revisión inicial
        $tipos[] = 'revision';
        
        // 2. SIEMPRE: Autorización por centro de costo (se crea después de aprobar revisión)
        $tipos[] = 'centro_costo';
        
        // 3. Autorización especial por forma de pago (si aplica)
        if (isset($flujo->requiere_autorizacion_especial_pago) && $flujo->requiere_autorizacion_especial_pago) {
            $tipos[] = 'forma_pago';
        }
        
        // 4. Autorización especial por cuenta contable (si aplica)
        if (isset($flujo->requiere_autorizacion_especial_cuenta) && $flujo->requiere_autorizacion_especial_cuenta) {
            $tipos[] = 'cuenta_contable';
        }
        
        return $tipos;
    }

    /**
     * Verifica el progreso del flujo y actualiza estado si es necesario
     * 
     * @param int $ordenCompraId
     * @return array Estado actualizado
     */
    public function verificarProgreso($ordenCompraId)
    {
        try {
            $flujo = AutorizacionFlujo::porOrdenCompra($ordenCompraId);
            
            if (!$flujo) {
                return [
                    'success' => false,
                    'error' => 'No se encontró flujo para esta orden'
                ];
            }

            // Obtener autorizaciones por centro de costo (tabla unificada)
            $autorizaciones = $this->centrosRepo()->getByRequisicion((int) $ordenCompraId);
            
            $total = count($autorizaciones);
            $pendientes = 0;
            $autorizadas = 0;
            $rechazadas = 0;
            
            foreach ($autorizaciones as $auth) {
                switch ($auth['estado']) {
                    case 'pendiente':
                        $pendientes++;
                        break;
                    case 'autorizado':
                        $autorizadas++;
                        break;
                    case 'rechazado':
                        $rechazadas++;
                        break;
                }
            }

            return [
                'success' => true,
                'estado_flujo' => $flujo->estado,
                'progreso' => [
                    'total' => $total,
                    'pendientes' => $pendientes,
                    'autorizadas' => $autorizadas,
                    'rechazadas' => $rechazadas,
                    'porcentaje_completado' => $total > 0 ? round(($autorizadas / $total) * 100, 1) : 0
                ]
            ];

        } catch (\Exception $e) {
            error_log("Error verificando progreso: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene el resumen completo del flujo de una orden
     * 
     * @param int $ordenCompraId
     * @return array Resumen detallado
     */
    public function getResumenFlujo($ordenCompraId)
    {
        try {
            $orden = Requisicion::find($ordenCompraId);
            
            if (!$orden) {
                throw new \Exception("Orden de compra no encontrada");
            }

            $flujo = AutorizacionFlujo::porOrdenCompra($ordenCompraId);
            
            if (!$flujo) {
                return [
                    'success' => false,
                    'error' => 'No se encontró flujo para esta orden',
                    'orden' => $orden
                ];
            }

            // Obtener autorizaciones por centro de costo
            $autorizaciones = $this->centrosRepo()->getByRequisicion((int) $ordenCompraId);
            
            // Obtener historial
            $historial = HistorialRequisicion::porOrdenCompra($ordenCompraId);

            return [
                'success' => true,
                'orden' => $orden,
                'flujo' => $flujo,
                'autorizaciones' => $autorizaciones,
                'historial' => $historial,
                'progreso' => $this->verificarProgreso($ordenCompraId)['progreso'] ?? null
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo resumen de flujo: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
