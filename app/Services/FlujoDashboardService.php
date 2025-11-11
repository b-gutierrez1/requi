<?php
/**
 * FlujoDashboardService
 * 
 * Servicio para mostrar información de flujos en el dashboard y vistas.
 * Proporciona resúmenes, progreso y visualizaciones del estado de los flujos.
 * 
 * @package RequisicionesMVC\Services
 * @version 3.0
 */

namespace App\Services;

use App\Models\Model;

class FlujoDashboardService extends Model
{
    /**
     * Obtiene estadísticas generales de flujos
     * 
     * @return array Estadísticas
     */
    public function getEstadisticasGenerales()
    {
        try {
            $pdo = static::getConnection();

            // Contar requisiciones por estado
            $stmt = $pdo->prepare("
                SELECT estado, COUNT(*) as total
                FROM requisiciones 
                GROUP BY estado
            ");
            $stmt->execute();
            $estadosTotales = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $estadosTotales[$row['estado']] = (int)$row['total'];
            }

            // Contar autorizaciones pendientes por tipo
            $stmt = $pdo->prepare("
                SELECT tipo, COUNT(*) as total
                FROM autorizaciones 
                WHERE estado = 'pendiente'
                GROUP BY tipo
            ");
            $stmt->execute();
            $pendientesPorTipo = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $pendientesPorTipo[$row['tipo']] = (int)$row['total'];
            }

            // Calcular tiempo promedio de procesamiento (completadas en últimos 30 días)
            $stmt = $pdo->prepare("
                SELECT 
                    AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as horas_promedio
                FROM requisiciones 
                WHERE estado IN ('autorizada', 'rechazada') 
                AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $tiempoPromedio = $stmt->fetchColumn() ?: 0;

            return [
                'estados_totales' => $estadosTotales,
                'pendientes_por_tipo' => $pendientesPorTipo,
                'tiempo_promedio_horas' => round($tiempoPromedio, 1),
                'total_requisiciones' => array_sum($estadosTotales),
                'total_pendientes' => array_sum($pendientesPorTipo)
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo estadísticas generales: " . $e->getMessage());
            return [
                'estados_totales' => [],
                'pendientes_por_tipo' => [],
                'tiempo_promedio_horas' => 0,
                'total_requisiciones' => 0,
                'total_pendientes' => 0
            ];
        }
    }

    /**
     * Obtiene flujos con alertas (vencidos, por vencer, etc.)
     * 
     * @return array Alertas de flujos
     */
    public function getAlertasFlujos()
    {
        try {
            $pdo = static::getConnection();

            // Requisiciones que llevan más de 48 horas en revisión
            $stmt = $pdo->prepare("
                SELECT r.*, 
                       TIMESTAMPDIFF(HOUR, r.created_at, NOW()) as horas_transcurridas
                FROM requisiciones r
                WHERE r.estado = 'pendiente_revision' 
                AND r.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                ORDER BY r.created_at ASC
                LIMIT 10
            ");
            $stmt->execute();
            $revisionesVencidas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Autorizaciones que llevan más de 72 horas pendientes
            $stmt = $pdo->prepare("
                SELECT 
                    r.id as requisicion_id,
                    r.numero_requisicion,
                    r.nombre_razon_social,
                    a.tipo,
                    a.autorizador_email,
                    TIMESTAMPDIFF(HOUR, a.created_at, NOW()) as horas_transcurridas
                FROM autorizaciones a
                JOIN requisiciones r ON a.requisicion_id = r.id
                WHERE a.estado = 'pendiente' 
                AND a.created_at <= DATE_SUB(NOW(), INTERVAL 72 HOUR)
                ORDER BY a.created_at ASC
                LIMIT 10
            ");
            $stmt->execute();
            $autorizacionesVencidas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Flujos con alta prioridad (monto > 50000)
            $stmt = $pdo->prepare("
                SELECT r.*,
                       COUNT(a.id) as autorizaciones_pendientes
                FROM requisiciones r
                LEFT JOIN autorizaciones a ON r.id = a.requisicion_id AND a.estado = 'pendiente'
                WHERE r.estado IN ('pendiente_revision', 'pendiente_autorizacion')
                AND r.monto_total > 50000
                GROUP BY r.id
                HAVING autorizaciones_pendientes > 0
                ORDER BY r.monto_total DESC
                LIMIT 5
            ");
            $stmt->execute();
            $altaPrioridad = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'revisiones_vencidas' => $revisionesVencidas,
                'autorizaciones_vencidas' => $autorizacionesVencidas,
                'alta_prioridad' => $altaPrioridad
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo alertas de flujos: " . $e->getMessage());
            return [
                'revisiones_vencidas' => [],
                'autorizaciones_vencidas' => [],
                'alta_prioridad' => []
            ];
        }
    }

    /**
     * Obtiene progreso detallado de una requisición
     * 
     * @param int $requisicionId
     * @return array Progreso con pasos y estado
     */
    public function getProgresoRequisicion($requisicionId)
    {
        try {
            $pdo = static::getConnection();

            // Obtener información básica
            $stmt = $pdo->prepare("SELECT * FROM requisiciones WHERE id = ?");
            $stmt->execute([$requisicionId]);
            $requisicion = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$requisicion) {
                throw new \Exception("Requisición no encontrada");
            }

            // Obtener autorizaciones agrupadas por tipo
            $stmt = $pdo->prepare("
                SELECT 
                    tipo,
                    estado,
                    autorizador_email,
                    fecha_respuesta,
                    comentarios,
                    motivo_rechazo,
                    centro_costo_id,
                    cuenta_contable_id,
                    metadata
                FROM autorizaciones 
                WHERE requisicion_id = ?
                ORDER BY tipo, created_at
            ");
            $stmt->execute([$requisicionId]);
            $autorizaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Organizar por pasos del flujo
            $pasos = [
                'revision' => [
                    'nombre' => 'Revisión Inicial',
                    'orden' => 1,
                    'requerido' => true,
                    'estado' => 'pendiente',
                    'autorizaciones' => []
                ],
                'centro_costo' => [
                    'nombre' => 'Autorización por Centro de Costo',
                    'orden' => 2,
                    'requerido' => false,
                    'estado' => 'pendiente',
                    'autorizaciones' => []
                ],
                'forma_pago' => [
                    'nombre' => 'Autorización Especial - Forma de Pago',
                    'orden' => 3,
                    'requerido' => false,
                    'estado' => 'no_requerido',
                    'autorizaciones' => []
                ],
                'cuenta_contable' => [
                    'nombre' => 'Autorización Especial - Cuenta Contable',
                    'orden' => 4,
                    'requerido' => false,
                    'estado' => 'no_requerido',
                    'autorizaciones' => []
                ]
            ];

            // Llenar información de autorizaciones
            foreach ($autorizaciones as $auth) {
                $tipo = $auth['tipo'];
                if (isset($pasos[$tipo])) {
                    $pasos[$tipo]['requerido'] = true;
                    $pasos[$tipo]['autorizaciones'][] = $auth;
                }
            }

            // Calcular estado de cada paso
            foreach ($pasos as $tipo => &$paso) {
                if (!$paso['requerido'] || empty($paso['autorizaciones'])) {
                    continue;
                }

                $estados = array_column($paso['autorizaciones'], 'estado');
                
                if (in_array('rechazada', $estados)) {
                    $paso['estado'] = 'rechazado';
                } elseif (all($estados, 'aprobada')) {
                    $paso['estado'] = 'completado';
                } elseif (in_array('aprobada', $estados)) {
                    $paso['estado'] = 'parcial';
                } else {
                    $paso['estado'] = 'pendiente';
                }
            }

            // Calcular progreso general
            $pasosRequeridos = array_filter($pasos, fn($paso) => $paso['requerido']);
            $pasosCompletados = array_filter($pasosRequeridos, fn($paso) => $paso['estado'] === 'completado');
            $porcentajeProgreso = count($pasosRequeridos) > 0 ? 
                round((count($pasosCompletados) / count($pasosRequeridos)) * 100, 1) : 0;

            return [
                'success' => true,
                'requisicion' => $requisicion,
                'pasos' => $pasos,
                'progreso' => [
                    'porcentaje' => $porcentajeProgreso,
                    'pasos_totales' => count($pasosRequeridos),
                    'pasos_completados' => count($pasosCompletados),
                    'pasos_pendientes' => count($pasosRequeridos) - count($pasosCompletados)
                ]
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo progreso de requisición: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene actividad reciente del sistema
     * 
     * @param int $limite Número de registros a obtener
     * @return array Actividad reciente
     */
    public function getActividadReciente($limite = 20)
    {
        try {
            $pdo = static::getConnection();

            $stmt = $pdo->prepare("
                SELECT 
                    h.*,
                    r.numero_requisicion,
                    r.nombre_razon_social,
                    r.monto_total
                FROM historial_requisiciones h
                JOIN requisiciones r ON h.requisicion_id = r.id
                ORDER BY h.fecha_cambio DESC
                LIMIT ?
            ");
            $stmt->execute([$limite]);
            $actividad = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return $actividad;

        } catch (\Exception $e) {
            error_log("Error obteniendo actividad reciente: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene notificaciones pendientes para un usuario
     * 
     * @param string $usuarioEmail
     * @return array Notificaciones
     */
    public function getNotificacionesPendientes($usuarioEmail)
    {
        try {
            $pdo = static::getConnection();

            // Autorizaciones pendientes del usuario
            $stmt = $pdo->prepare("
                SELECT 
                    r.id as requisicion_id,
                    r.numero_requisicion,
                    r.nombre_razon_social,
                    r.monto_total,
                    a.tipo,
                    a.created_at as fecha_solicitud,
                    TIMESTAMPDIFF(HOUR, a.created_at, NOW()) as horas_transcurridas
                FROM autorizaciones a
                JOIN requisiciones r ON a.requisicion_id = r.id
                WHERE a.autorizador_email = ? 
                AND a.estado = 'pendiente'
                ORDER BY a.created_at ASC
            ");
            $stmt->execute([$usuarioEmail]);
            $autorizacionesPendientes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Clasificar por urgencia
            $urgentes = array_filter($autorizacionesPendientes, fn($a) => $a['horas_transcurridas'] > 48);
            $normales = array_filter($autorizacionesPendientes, fn($a) => $a['horas_transcurridas'] <= 48);

            return [
                'urgentes' => array_values($urgentes),
                'normales' => array_values($normales),
                'total' => count($autorizacionesPendientes)
            ];

        } catch (\Exception $e) {
            error_log("Error obteniendo notificaciones pendientes: " . $e->getMessage());
            return [
                'urgentes' => [],
                'normales' => [],
                'total' => 0
            ];
        }
    }
}

/**
 * Función helper para verificar si todos los elementos de un array son iguales a un valor
 */
function all($array, $value) {
    return count($array) > 0 && count(array_filter($array, fn($item) => $item === $value)) === count($array);
}