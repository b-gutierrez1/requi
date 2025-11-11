<?php
/**
 * Controlador de Administración de Requisiciones
 * 
 * Pantalla especial para administradores que muestra el detalle completo
 * del flujo de autorización paso a paso con historial completo.
 * 
 * @package RequisicionesMVC\Controllers\Admin
 * @version 1.0
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Helpers\View;
use App\Models\Model;
use App\Models\OrdenCompra;
use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionCentroCosto;
use App\Models\DistribucionGasto;
use App\Models\Autorizacion;
use App\Models\AutorizadorRespaldo;
use App\Models\HistorialRequisicion;

class RequisicionesController extends Controller
{
    /**
     * Lista todas las requisiciones con estado resumido
     */
    public function index()
    {
        try {
            // Obtener todas las requisiciones con información de flujo
            $sql = "
                SELECT 
                    oc.id,
                    oc.nombre_razon_social,
                    oc.forma_pago,
                    oc.monto_total,
                    oc.fecha,
                    oc.estado as estado_orden,
                    af.estado as estado_flujo,
                    af.requiere_autorizacion_especial_pago,
                    af.requiere_autorizacion_especial_cuenta,
                    af.fecha_creacion as fecha_inicio_flujo,
                    af.fecha_completado,
                    u.email as solicitante,
                    -- Estadísticas de autorizaciones
                    (SELECT COUNT(*) FROM autorizaciones a WHERE a.requisicion_id = oc.id AND a.estado = 'pendiente') as especiales_pendientes,
                    (SELECT COUNT(*) FROM autorizaciones a WHERE a.requisicion_id = oc.id AND a.estado = 'autorizada') as especiales_autorizadas,
                    (SELECT COUNT(*) FROM autorizacion_centro_costo acc WHERE acc.autorizacion_flujo_id = af.id AND acc.estado = 'pendiente') as centros_pendientes,
                    (SELECT COUNT(*) FROM autorizacion_centro_costo acc WHERE acc.autorizacion_flujo_id = af.id AND acc.estado = 'autorizado') as centros_autorizados
                FROM orden_compra oc
                LEFT JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
                LEFT JOIN usuarios u ON oc.usuario_id = u.id
                ORDER BY oc.fecha DESC, oc.id DESC
            ";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $requisiciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $data = [
                'requisiciones' => $requisiciones,
                'total_requisiciones' => count($requisiciones)
            ];

            View::render('admin/requisiciones/index', $data);
        } catch (\Exception $e) {
            error_log("Error en admin requisiciones index: " . $e->getMessage());
            header('Location: /admin/dashboard?error=Error al cargar requisiciones');
            exit;
        }
    }

    /**
     * Muestra el detalle completo de una requisición paso a paso
     */
    public function show($id)
    {
        try {
            error_log("=== ADMIN SHOW INICIADO - ID: $id ===");
            
            // 1. Información básica de la requisición
            $orden = OrdenCompra::find($id);
            error_log("Orden encontrada: " . ($orden ? 'SÍ' : 'NO'));
            if (!$orden) {
                error_log("Orden no encontrada, redirigiendo...");
                header('Location: /admin/requisiciones?error=Requisición no encontrada');
                exit;
            }

            // 2. Información del flujo
            $flujo = AutorizacionFlujo::porOrdenCompra($id);
            error_log("Flujo encontrado: " . ($flujo ? 'SÍ' : 'NO'));
            
            // 3. Items de la requisición
            $items = $this->getItems($id);
            error_log("Items cargados: " . count($items));
            
            // 4. Distribución de gastos
            $distribucion = $this->getDistribucion($id);
            error_log("Distribución cargada: " . count($distribucion));
            
            // 5. Timeline completo del flujo
            $timeline = $this->getTimelineCompleto($id, $flujo);
            error_log("Timeline cargado: " . count($timeline));
            
            // 6. Autorizaciones especiales 
            $autorizacionesEspeciales = $this->getAutorizacionesEspeciales($id);
            error_log("Autorizaciones especiales cargadas: " . count($autorizacionesEspeciales));
            
            // 7. Autorizaciones por centro
            $autorizacionesCentros = $this->getAutorizacionesCentros($flujo['id'] ?? null);
            error_log("Autorizaciones centros cargadas: " . count($autorizacionesCentros));
            
            // 8. Respaldos relacionados
            $respaldos = $this->getRespaldosRelacionados($id);
            error_log("Respaldos cargados: " . count($respaldos));
            
            // 9. Estadísticas del flujo
            $estadisticas = $this->getEstadisticasFlujo($id, $flujo);
            error_log("Estadísticas calculadas");

            $data = [
                'orden' => $orden,
                'flujo' => $flujo,
                'items' => $items,
                'distribucion' => $distribucion,
                'timeline' => $timeline,
                'autorizaciones_especiales' => $autorizacionesEspeciales,
                'autorizaciones_centros' => $autorizacionesCentros,
                'respaldos' => $respaldos,
                'estadisticas' => $estadisticas
            ];

            View::render('admin/requisiciones/show', $data);
        } catch (\Exception $e) {
            error_log("Error en admin requisiciones show: " . $e->getMessage());
            header('Location: /admin/requisiciones?error=Error al cargar detalle');
            exit;
        }
    }

    /**
     * Obtiene los items de la requisición
     */
    private function getItems($ordenId)
    {
        $sql = "SELECT * FROM detalle_items WHERE orden_compra_id = ? ORDER BY id";
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute([$ordenId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la distribución de gastos con información adicional
     */
    private function getDistribucion($ordenId)
    {
        $sql = "
            SELECT 
                dg.*,
                cc.nombre as centro_nombre,
                ct.descripcion as cuenta_nombre,
                ct.codigo as cuenta_codigo
            FROM distribucion_gasto dg
            LEFT JOIN centro_de_costo cc ON dg.centro_costo_id = cc.id
            LEFT JOIN cuenta_contable ct ON dg.cuenta_contable_id = ct.id
            WHERE dg.orden_compra_id = ?
            ORDER BY dg.id
        ";
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute([$ordenId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Genera timeline cronológico completo del flujo
     */
    private function getTimelineCompleto($ordenId, $flujo)
    {
        $timeline = [];

        // 1. Creación de la requisición
        $sql = "SELECT fecha, usuario_id FROM orden_compra WHERE id = ?";
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute([$ordenId]);
        $orden = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($orden) {
            $timeline[] = [
                'tipo' => 'creacion',
                'titulo' => 'Requisición Creada',
                'descripcion' => 'La requisición fue creada en el sistema',
                'fecha' => $orden['fecha'],
                'usuario' => $this->getUsuarioInfo($orden['usuario_id']),
                'estado' => 'completado',
                'icono' => 'fas fa-plus-circle',
                'color' => 'success'
            ];
        }

        // 2. Inicio del flujo de autorización
        if ($flujo && isset($flujo['fecha_creacion'])) {
            $timeline[] = [
                'tipo' => 'inicio_flujo',
                'titulo' => 'Flujo de Autorización Iniciado',
                'descripcion' => 'Se inició el proceso de autorización',
                'fecha' => $flujo['fecha_creacion'],
                'estado' => 'completado',
                'icono' => 'fas fa-play-circle',
                'color' => 'info'
            ];
        }

        // 3. Revisión
        if ($flujo && isset($flujo['revisor_fecha'])) {
            $timeline[] = [
                'tipo' => 'revision',
                'titulo' => 'Revisión Completada',
                'descripcion' => $flujo['revisor_comentario'] ?? 'Revisión aprobada',
                'fecha' => $flujo['revisor_fecha'],
                'usuario' => $flujo['revisor_email'],
                'estado' => 'completado',
                'icono' => 'fas fa-eye',
                'color' => 'primary'
            ];
        }

        // 4. Revisión pendiente (si corresponde)
        if ($flujo && $flujo['estado'] === 'pendiente_revision') {
            $timeline[] = [
                'tipo' => 'revision_pendiente',
                'titulo' => 'Revisión Pendiente',
                'descripcion' => 'Esperando revisión del documento - Asignado a: Revisor del Sistema',
                'fecha' => 'Pendiente',
                'estado' => 'pendiente',
                'icono' => 'fas fa-clock',
                'color' => 'warning',
                'asignado_a' => 'Revisor del Sistema'
            ];
        }

        // 5. Autorizaciones especiales
        $especialesTimeline = $this->getAutorizacionesEspecialesTimeline($ordenId);
        $timeline = array_merge($timeline, $especialesTimeline);

        // 6. Autorizaciones por centro
        $centrosTimeline = $this->getAutorizacionesCentrosTimeline($flujo['id'] ?? null);
        $timeline = array_merge($timeline, $centrosTimeline);

        // 7. Finalización
        if ($flujo && isset($flujo['fecha_completado'])) {
            $timeline[] = [
                'tipo' => 'finalizacion',
                'titulo' => 'Flujo Completado',
                'descripcion' => 'El flujo de autorización fue completado',
                'fecha' => $flujo['fecha_completado'],
                'estado' => 'completado',
                'icono' => 'fas fa-check-circle',
                'color' => $flujo['estado'] === 'autorizado' ? 'success' : 'danger'
            ];
        }

        // Ordenar timeline por fecha
        usort($timeline, function($a, $b) {
            return strtotime($a['fecha']) - strtotime($b['fecha']);
        });

        return $timeline;
    }

    /**
     * Obtiene autorizaciones especiales
     */
    private function getAutorizacionesEspeciales($ordenId)
    {
        $sql = "
            SELECT 
                a.*,
                CASE 
                    WHEN a.fecha_respuesta IS NOT NULL THEN a.fecha_respuesta
                    ELSE NULL
                END as fecha_accion
            FROM autorizaciones a
            WHERE a.requisicion_id = ?
            ORDER BY a.created_at, a.tipo, 
                CASE WHEN JSON_EXTRACT(a.metadata, '$.es_respaldo') = true THEN 1 ELSE 0 END
        ";
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute([$ordenId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene autorizaciones por centro
     */
    private function getAutorizacionesCentros($flujoId)
    {
        if (!$flujoId) return [];

        $sql = "
            SELECT 
                acc.*,
                cc.nombre as centro_nombre,
                acc.autorizador_fecha as fecha_accion
            FROM autorizacion_centro_costo acc
            LEFT JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
            WHERE acc.autorizacion_flujo_id = ?
            ORDER BY acc.id,
                CASE WHEN JSON_EXTRACT(acc.metadata, '$.es_respaldo') = true THEN 1 ELSE 0 END
        ";
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute([$flujoId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Timeline de autorizaciones especiales
     */
    private function getAutorizacionesEspecialesTimeline($ordenId)
    {
        $timeline = [];
        $especiales = $this->getAutorizacionesEspeciales($ordenId);

        foreach ($especiales as $auth) {
            $metadata = json_decode($auth['metadata'], true);
            $esRespaldo = $metadata['es_respaldo'] ?? false;
            $tipoDetalle = '';
            
            if ($auth['tipo'] === 'forma_pago') {
                $tipoDetalle = $metadata['forma_pago'] ?? 'N/A';
            } elseif ($auth['tipo'] === 'cuenta_contable') {
                $tipoDetalle = $metadata['cuenta_nombre'] ?? 'N/A';
            }

            $titulo = 'Autorización Especial: ' . ucfirst(str_replace('_', ' ', $auth['tipo']));
            if ($esRespaldo) {
                $titulo .= ' (Respaldo)';
            }

            // Descripción con asignación
            $descripcion = $tipoDetalle;
            if ($auth['estado'] === 'pendiente') {
                $descripcion .= ' - Asignado a: ' . $auth['autorizador_email'];
            } elseif ($auth['fecha_respuesta']) {
                $descripcion .= ' - Respondido por: ' . $auth['autorizador_email'];
            }

            $item = [
                'tipo' => 'autorizacion_especial',
                'subtipo' => $auth['tipo'],
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'fecha' => $auth['fecha_accion'] ?? $auth['created_at'],
                'usuario' => $auth['estado'] === 'pendiente' ? null : $auth['autorizador_email'], // Solo mostrar usuario si ya respondió
                'estado' => $this->mapearEstado($auth['estado']),
                'icono' => $auth['tipo'] === 'forma_pago' ? 'fas fa-credit-card' : 'fas fa-calculator',
                'color' => $this->getColorEstado($auth['estado']),
                'es_respaldo' => $esRespaldo,
                'asignado_a' => $auth['autorizador_email'], // Nueva propiedad para mostrar asignación
                'metadata' => $metadata
            ];

            $timeline[] = $item;
        }

        return $timeline;
    }

    /**
     * Timeline de autorizaciones por centro
     */
    private function getAutorizacionesCentrosTimeline($flujoId)
    {
        $timeline = [];
        if (!$flujoId) return $timeline;

        $centros = $this->getAutorizacionesCentros($flujoId);

        foreach ($centros as $auth) {
            $metadata = json_decode($auth['metadata'] ?? '{}', true);
            $esRespaldo = $metadata['es_respaldo'] ?? false;

            $titulo = 'Autorización Centro: ' . ($auth['centro_nombre'] ?? 'N/A');
            if ($esRespaldo) {
                $titulo .= ' (Respaldo)';
            }

            // Descripción con asignación
            $descripcion = 'Porcentaje: ' . $auth['porcentaje'] . '%';
            if ($auth['estado'] === 'pendiente') {
                $descripcion .= ' - Asignado a: ' . $auth['autorizador_email'];
            } elseif ($auth['autorizador_fecha']) {
                $descripcion .= ' - Autorizado por: ' . $auth['autorizador_email'];
            }

            $item = [
                'tipo' => 'autorizacion_centro',
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'fecha' => $auth['fecha_accion'] ?? 'Pendiente',
                'usuario' => $auth['estado'] === 'pendiente' ? null : $auth['autorizador_email'], // Solo mostrar usuario si ya respondió
                'estado' => $this->mapearEstado($auth['estado']),
                'icono' => 'fas fa-building',
                'color' => $this->getColorEstado($auth['estado']),
                'es_respaldo' => $esRespaldo,
                'asignado_a' => $auth['autorizador_email'], // Nueva propiedad para mostrar asignación
                'metadata' => $metadata
            ];

            $timeline[] = $item;
        }

        return $timeline;
    }

    /**
     * Obtiene respaldos relacionados con la requisición
     */
    private function getRespaldosRelacionados($ordenId)
    {
        // Obtener respaldos que podrían estar involucrados
        $sql = "
            SELECT DISTINCT
                ar.*,
                cc.nombre as centro_nombre
            FROM autorizador_respaldo ar
            LEFT JOIN centro_de_costo cc ON ar.centro_costo_id = cc.id
            WHERE ar.estado = 'activo'
            AND CURRENT_DATE BETWEEN ar.fecha_inicio AND ar.fecha_fin
        ";
        
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Calcula estadísticas del flujo
     */
    private function getEstadisticasFlujo($ordenId, $flujo)
    {
        $stats = [
            'tiempo_total' => null,
            'tiempo_revision' => null,
            'autorizaciones_especiales' => ['total' => 0, 'pendientes' => 0, 'autorizadas' => 0, 'rechazadas' => 0],
            'autorizaciones_centros' => ['total' => 0, 'pendientes' => 0, 'autorizadas' => 0, 'rechazadas' => 0],
            'progreso_porcentaje' => 0
        ];

        if ($flujo) {
            $fechaInicioFlujo = $flujo['fecha_inicio_flujo'] ?? null;
            $fechaCompletado = $flujo['fecha_completado'] ?? null;
            $fechaRevision = $flujo['revisor_fecha'] ?? null;

            // Tiempo total (solo si hay fecha de inicio)
            if ($fechaInicioFlujo) {
                $inicio = new \DateTime($fechaInicioFlujo);
                $fin = $fechaCompletado ? new \DateTime($fechaCompletado) : new \DateTime();
                $stats['tiempo_total'] = $inicio->diff($fin)->days;

                // Tiempo de revisión
                if ($fechaRevision) {
                    $revision = new \DateTime($fechaRevision);
                    $stats['tiempo_revision'] = $inicio->diff($revision)->days;
                }
            }

            // Estadísticas de autorizaciones especiales
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'autorizada' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
                FROM autorizaciones 
                WHERE requisicion_id = ?
            ";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$ordenId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $stats['autorizaciones_especiales'] = $result;
            }

            // Estadísticas de centros
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas
                FROM autorizacion_centro_costo 
                WHERE autorizacion_flujo_id = ?
            ";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$flujo['id']]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $stats['autorizaciones_centros'] = $result;
            }

            // Calcular progreso
            $totalAutorizaciones = $stats['autorizaciones_especiales']['total'] + $stats['autorizaciones_centros']['total'];
            $totalAutorizadas = $stats['autorizaciones_especiales']['autorizadas'] + $stats['autorizaciones_centros']['autorizadas'];
            
            if ($totalAutorizaciones > 0) {
                $stats['progreso_porcentaje'] = round(($totalAutorizadas / $totalAutorizaciones) * 100, 1);
            }
        }

        return $stats;
    }

    /**
     * Obtiene información del usuario
     */
    private function getUsuarioInfo($usuarioId)
    {
        if (!$usuarioId) return 'Sistema';

        $sql = "SELECT email, nombre FROM usuarios WHERE id = ?";
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute([$usuarioId]);
        $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $usuario ? ($usuario['email'] ?? $usuario['nombre'] ?? 'Usuario') : 'Desconocido';
    }

    /**
     * Mapea estados a texto legible
     */
    private function mapearEstado($estado)
    {
        $mapeo = [
            'pendiente' => 'Pendiente',
            'autorizado' => 'Autorizado',
            'autorizada' => 'Autorizada',
            'rechazado' => 'Rechazado',
            'rechazada' => 'Rechazada',
            'completado' => 'Completado'
        ];

        return $mapeo[$estado] ?? ucfirst($estado);
    }

    /**
     * Obtiene color según estado
     */
    private function getColorEstado($estado)
    {
        $colores = [
            'pendiente' => 'warning',
            'autorizado' => 'success',
            'autorizada' => 'success',
            'rechazado' => 'danger',
            'rechazada' => 'danger',
            'completado' => 'success'
        ];

        return $colores[$estado] ?? 'secondary';
    }
}