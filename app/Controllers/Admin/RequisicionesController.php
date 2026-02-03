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
use App\Models\Requisicion;
use App\Models\AutorizacionFlujo;
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
            // Usar consulta más simple y robusta para evitar errores
            $sql = "
                SELECT 
                    r.id,
                    COALESCE(r.proveedor_nombre, '') as nombre_razon_social,
                    COALESCE(r.forma_pago, '') as forma_pago,
                    COALESCE((SELECT SUM(total) FROM detalle_items WHERE requisicion_id = r.id), 0) as monto_total,
                    COALESCE(r.fecha_solicitud, NOW()) as fecha,
                    COALESCE(af.estado, '') as estado_flujo,
                    af.fecha_creacion as fecha_inicio_flujo,
                    COALESCE(af.requiere_autorizacion_especial_pago, 0) as requiere_autorizacion_especial_pago,
                    COALESCE(af.requiere_autorizacion_especial_cuenta, 0) as requiere_autorizacion_especial_cuenta,
                    COALESCE(u.azure_email, '') as solicitante,
                    COALESCE(u.azure_display_name, '') as solicitante_nombre,
                    0 as especiales_pendientes,
                    0 as especiales_autorizadas,
                    0 as centros_pendientes,
                    0 as centros_autorizados
                FROM requisiciones r
                LEFT JOIN autorizacion_flujo af ON r.id = af.requisicion_id
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                ORDER BY COALESCE(r.fecha_solicitud, NOW()) DESC, r.id DESC
            ";
            
            $pdo = Model::getConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $requisiciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $data = [
                'requisiciones' => $requisiciones,
                'total_requisiciones' => count($requisiciones)
            ];

            View::render('admin/requisiciones/index', $data);
        } catch (\PDOException $e) {
            error_log("Error PDO en admin requisiciones index: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            error_log("Error Info: " . print_r($e->errorInfo, true));
            \App\Helpers\Redirect::to('/admin/dashboard')
                ->withError('Error al cargar requisiciones: ' . $e->getMessage())
                ->send();
        } catch (\Exception $e) {
            error_log("Error en admin requisiciones index: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            \App\Helpers\Redirect::to('/admin/dashboard')
                ->withError('Error al cargar requisiciones: ' . $e->getMessage())
                ->send();
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
            $orden = Requisicion::find($id);
            error_log("Orden encontrada: " . ($orden ? 'SÍ' : 'NO'));
            if (!$orden) {
                error_log("Orden no encontrada, redirigiendo...");
                header('Location: /admin/requisiciones?error=Requisición no encontrada');
                exit;
            }

            // 2. Información del flujo
            $flujo = AutorizacionFlujo::porRequisicion($id);
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
            $autorizacionesCentros = $this->getAutorizacionesCentros($id);
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
        $sql = "SELECT * FROM detalle_items WHERE requisicion_id = ? ORDER BY id";
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
            WHERE dg.requisicion_id = ?
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
        $sql = "SELECT fecha_solicitud, usuario_id FROM requisiciones WHERE id = ?";
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute([$ordenId]);
        $orden = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($orden) {
            $timeline[] = [
                'tipo' => 'creacion',
                'titulo' => 'Requisición Creada',
                'descripcion' => 'La requisición fue creada en el sistema',
                'fecha' => $orden['fecha_solicitud'],
                'usuario' => $this->getUsuarioInfo($orden['usuario_id']),
                'estado' => 'completado',
                'icono' => 'fas fa-plus-circle',
                'color' => 'success'
            ];
        }

        // 2. Inicio del flujo de autorización
        $fechaInicioFlujo = $flujo['fecha_inicio'] ?? $flujo['fecha_creacion'] ?? null;
        if ($flujo && $fechaInicioFlujo) {
            $timeline[] = [
                'tipo' => 'inicio_flujo',
                'titulo' => 'Flujo de Autorización Iniciado',
                'descripcion' => 'Se inició el proceso de autorización',
                'fecha' => $fechaInicioFlujo,
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
        $centrosTimeline = $this->getAutorizacionesCentrosTimeline($ordenId);
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

        // Ordenar timeline por fecha (manejar "Pendiente" al final)
        usort($timeline, function($a, $b) {
            $fechaA = $a['fecha'] ?? 'Pendiente';
            $fechaB = $b['fecha'] ?? 'Pendiente';
            
            // Si ambas son "Pendiente", mantener orden original
            if ($fechaA === 'Pendiente' && $fechaB === 'Pendiente') {
                return 0;
            }
            
            // "Pendiente" siempre va al final
            if ($fechaA === 'Pendiente') {
                return 1;
            }
            if ($fechaB === 'Pendiente') {
                return -1;
            }
            
            // Comparar fechas normales
            $timestampA = strtotime($fechaA);
            $timestampB = strtotime($fechaB);
            
            // Si alguna fecha no es válida, ponerla al final
            if ($timestampA === false) return 1;
            if ($timestampB === false) return -1;
            
            return $timestampA - $timestampB;
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
              AND a.tipo IN ('forma_pago', 'cuenta_contable')
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
    private function getAutorizacionesCentros($requisicionId)
    {
        if (!$requisicionId) return [];

        $sql = "
            SELECT 
                a.*,
                cc.nombre as centro_nombre,
                COALESCE(dg.porcentaje, 0) as porcentaje,
                CASE 
                    WHEN a.fecha_respuesta IS NOT NULL THEN a.fecha_respuesta
                    ELSE NULL
                END as fecha_accion
            FROM autorizaciones a
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            LEFT JOIN distribucion_gasto dg ON dg.requisicion_id = a.requisicion_id AND dg.centro_costo_id = a.centro_costo_id
            WHERE a.requisicion_id = ?
              AND a.tipo = 'centro_costo'
            ORDER BY a.id,
                CASE WHEN JSON_EXTRACT(COALESCE(a.metadata, '{}'), '$.es_respaldo') = true THEN 1 ELSE 0 END
        ";
        $stmt = Model::getConnection()->prepare($sql);
        $stmt->execute([$requisicionId]);
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
    private function getAutorizacionesCentrosTimeline($requisicionId)
    {
        $timeline = [];
        if (!$requisicionId) return $timeline;

        $centros = $this->getAutorizacionesCentros($requisicionId);

        foreach ($centros as $auth) {
            $metadata = json_decode($auth['metadata'] ?? '{}', true);
            $esRespaldo = $metadata['es_respaldo'] ?? false;

            $titulo = 'Autorización Centro: ' . ($auth['centro_nombre'] ?? 'N/A');
            if ($esRespaldo) {
                $titulo .= ' (Respaldo)';
            }

            // Descripción con asignación
            $porcentaje = $auth['porcentaje'] ?? 0;
            $descripcion = 'Porcentaje: ' . number_format($porcentaje, 5) . '%';
            if ($auth['estado'] === 'pendiente') {
                $descripcion .= ' - Asignado a: ' . $auth['autorizador_email'];
            } elseif ($auth['fecha_respuesta']) {
                $descripcion .= ' - Respuesta por: ' . $auth['autorizador_email'];
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
        try {
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
        } catch (\Exception $e) {
            error_log("Error en getRespaldosRelacionados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula estadísticas del flujo
     */
    private function getEstadisticasFlujo($ordenId, $flujo)
    {
        try {
            $stats = [
                'tiempo_total' => null,
                'tiempo_revision' => null,
                'autorizaciones_especiales' => ['total' => 0, 'pendientes' => 0, 'autorizadas' => 0, 'rechazadas' => 0],
                'autorizaciones_centros' => ['total' => 0, 'pendientes' => 0, 'autorizadas' => 0, 'rechazadas' => 0],
                'progreso_porcentaje' => 0
            ];

            if ($flujo) {
                $fechaInicioFlujo = $flujo['fecha_inicio'] ?? $flujo['fecha_inicio_flujo'] ?? $flujo['fecha_creacion'] ?? null;
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
                        SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as autorizadas,
                        SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
                    FROM autorizaciones 
                    WHERE requisicion_id = ?
                      AND tipo IN ('forma_pago', 'cuenta_contable', 'revision')
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
                        SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as autorizadas,
                        SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
                    FROM autorizaciones 
                    WHERE requisicion_id = ?
                      AND tipo = 'centro_costo'
                ";
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute([$ordenId]);
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
        } catch (\Exception $e) {
            error_log("Error en getEstadisticasFlujo: " . $e->getMessage());
            return [
                'tiempo_total' => null,
                'tiempo_revision' => null,
                'autorizaciones_especiales' => ['total' => 0, 'pendientes' => 0, 'autorizadas' => 0, 'rechazadas' => 0],
                'autorizaciones_centros' => ['total' => 0, 'pendientes' => 0, 'autorizadas' => 0, 'rechazadas' => 0],
                'progreso_porcentaje' => 0
            ];
        }
    }

    /**
     * Obtiene información del usuario
     */
    private function getUsuarioInfo($usuarioId)
    {
        if (!$usuarioId) return 'Sistema';

        try {
            $sql = "SELECT azure_email, azure_display_name, nombre, email FROM usuarios WHERE id = ?";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($usuario) {
                return $usuario['azure_display_name'] 
                    ?? $usuario['azure_email'] 
                    ?? $usuario['nombre'] 
                    ?? $usuario['email'] 
                    ?? 'Usuario';
            }
        } catch (\Exception $e) {
            error_log("Error obteniendo usuario info: " . $e->getMessage());
        }

        return 'Desconocido';
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
            'aprobada' => 'Aprobada',
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
            'aprobada' => 'success',
            'rechazado' => 'danger',
            'rechazada' => 'danger',
            'completado' => 'success'
        ];

        return $colores[$estado] ?? 'secondary';
    }

    /**
     * Muestra los logs detallados de una requisición
     * 
     * @param int $id ID de la requisición
     */
    public function logs($id)
    {
        // Verificar permisos de administrador
        if (!$this->isAdmin() && !$this->isRevisor()) {
            \App\Helpers\Redirect::to('/requisiciones')
                ->withError('No tienes permisos para acceder a los logs de administración')
                ->send();
        }

        // Obtener la requisición
        $orden = Requisicion::find($id);
        if (!$orden) {
            \App\Helpers\Redirect::to('/admin/requisiciones')
                ->withError('Requisición no encontrada')
                ->send();
        }

        // Convertir objeto a array para la vista
        $ordenArray = $orden->toArray();
        // Asegurar que tenga los campos esperados por la vista usando el mapeo del modelo
        if (!isset($ordenArray['nombre_razon_social'])) {
            $ordenArray['nombre_razon_social'] = $orden->nombre_razon_social ?? $orden->proveedor_nombre ?? '';
        }
        // Asegurar que el ID esté disponible
        if (!isset($ordenArray['id'])) {
            $ordenArray['id'] = $orden->id ?? $id;
        }

        // Obtener logs del sistema
        $logs = $this->getSystemLogs($id);
        
        // Obtener logs de archivos adjuntos
        $archivoLogs = $this->getArchivoLogs($id);
        
        // Obtener logs de autorizaciones
        $autorizacionLogs = $this->getAutorizacionLogs($id);
        
        // Obtener logs de errores PHP relacionados
        $errorLogs = $this->getErrorLogs($id);

        View::render('admin/requisiciones/logs', [
            'orden' => $ordenArray,
            'logs' => $logs,
            'archivo_logs' => $archivoLogs,
            'autorizacion_logs' => $autorizacionLogs,
            'error_logs' => $errorLogs,
            'title' => 'Logs - Requisición #' . $id
        ]);
    }

    /**
     * Obtiene logs del sistema para una requisición
     */
    private function getSystemLogs($requisicionId)
    {
        try {
            // Obtener del historial de requisiciones
            // Manejar tanto 'fecha' como 'fecha_cambio' para compatibilidad
            $sql = "
                SELECT 
                    hr.*,
                    COALESCE(hr.tipo_evento, hr.accion, 'evento') as tipo_evento,
                    COALESCE(hr.usuario_email, 'Sistema') as usuario_email,
                    COALESCE(hr.usuario_email, 'Sistema') as usuario_nombre,
                    COALESCE(hr.fecha, hr.fecha_cambio, NOW()) as fecha_log
                FROM historial_requisiciones hr
                WHERE hr.requisicion_id = ?
                ORDER BY COALESCE(hr.fecha, hr.fecha_cambio, NOW()) DESC
            ";
        
            $stmt = \App\Models\Model::getConnection()->prepare($sql);
            $stmt->execute([$requisicionId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error en getSystemLogs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene logs de archivos adjuntos
     */
    private function getArchivoLogs($requisicionId)
    {
        try {
            $sql = "
                SELECT 
                    aa.*,
                    'archivo_subido' as tipo_log,
                    COALESCE(aa.fecha_creacion, aa.created_at) as fecha_log
                FROM archivos_adjuntos aa
                WHERE aa.requisicion_id = ?
                ORDER BY COALESCE(aa.fecha_creacion, aa.created_at) DESC
            ";
            
            $stmt = \App\Models\Model::getConnection()->prepare($sql);
            $stmt->execute([$requisicionId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error en getArchivoLogs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene logs de autorizaciones
     */
    private function getAutorizacionLogs($requisicionId)
    {
        try {
            $sql = "
                SELECT 
                    a.*,
                    'autorizacion' as tipo_log,
                    COALESCE(a.fecha_respuesta, a.created_at) as fecha_log,
                    CASE 
                        WHEN a.fecha_respuesta IS NOT NULL THEN 'respuesta'
                        ELSE 'creacion'
                    END as subtipo_log
                FROM autorizaciones a
                WHERE a.requisicion_id = ?
                ORDER BY COALESCE(a.fecha_respuesta, a.created_at) DESC
            ";
            
            $stmt = \App\Models\Model::getConnection()->prepare($sql);
            $stmt->execute([$requisicionId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error en getAutorizacionLogs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene logs de errores PHP relacionados con la requisición
     */
    private function getErrorLogs($requisicionId)
    {
        try {
            // Buscar en los logs de PHP errores relacionados con esta requisición
            $errorLogPath = ini_get('error_log');
            if (!$errorLogPath || !file_exists($errorLogPath)) {
                // Intentar ubicaciones comunes
                $possiblePaths = [
                    'C:\xampp\apache\logs\error.log',
                    'C:\xampp\php\logs\php_error.log',
                    __DIR__ . '/../../../storage/logs/app.log'
                ];
                
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $errorLogPath = $path;
                        break;
                    }
                }
            }

            $logs = [];
            
            if ($errorLogPath && file_exists($errorLogPath)) {
                // Leer últimas 1000 líneas del log
                $lines = $this->tailFile($errorLogPath, 1000);
                
                // Filtrar líneas que contengan el ID de la requisición
                foreach ($lines as $line) {
                    if (strpos($line, "requisicion_id: $requisicionId") !== false || 
                        strpos($line, "orden_id: $requisicionId") !== false ||
                        strpos($line, "Orden ID: $requisicionId") !== false ||
                        strpos($line, "#$requisicionId") !== false) {
                        
                        // Extraer timestamp si existe
                        preg_match('/\[(.*?)\]/', $line, $matches);
                        $timestamp = $matches[1] ?? null;
                        
                        $logs[] = [
                            'timestamp' => $timestamp,
                            'message' => $line,
                            'type' => $this->detectLogType($line)
                        ];
                    }
                }
            }

            return array_reverse($logs); // Mostrar más recientes primero
        } catch (\Exception $e) {
            error_log("Error en getErrorLogs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Detecta el tipo de log basado en el contenido
     */
    private function detectLogType($message)
    {
        if (strpos($message, 'ERROR') !== false || strpos($message, 'Fatal error') !== false) {
            return 'error';
        } elseif (strpos($message, 'WARNING') !== false || strpos($message, 'Warning') !== false) {
            return 'warning';
        } elseif (strpos($message, 'DEBUG') !== false) {
            return 'debug';
        } else {
            return 'info';
        }
    }

    /**
     * Obtiene las últimas N líneas de un archivo (como tail)
     */
    private function tailFile($filename, $lines = 100)
    {
        if (!file_exists($filename)) {
            return [];
        }

        $file = file($filename);
        if ($file === false) {
            return [];
        }

        return array_slice($file, -$lines);
    }
}