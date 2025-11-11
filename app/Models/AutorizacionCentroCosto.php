<?php
/**
 * Modelo AutorizacionCentroCosto
 * 
 * Gestiona las autorizaciones individuales por centro de costo.
 * Cada centro de costo involucrado en una requisición tiene su propia
 * autorización que debe ser aprobada por el autorizador correspondiente.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class AutorizacionCentroCosto extends Model
{
    protected static $table = 'autorizacion_centro_costo';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'autorizacion_flujo_id',
        'centro_costo_id',
        'porcentaje',
        'autorizador_email',
        'estado',
        'comentario',
    ];

    protected static $guarded = ['id', 'fecha_autorizacion'];


    /**
     * Obtiene el flujo de autorización asociado
     * 
     * @return array|null
     */
    public function autorizacionFlujo()
    {
        if (!isset($this->attributes['autorizacion_flujo_id'])) {
            return null;
        }

        $sql = "SELECT * FROM autorizacion_flujo WHERE id = ? LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['autorizacion_flujo_id']]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene el centro de costo asociado
     * 
     * @return array|null
     */
    public function centroCosto()
    {
        if (!isset($this->attributes['centro_costo_id'])) {
            return null;
        }

        return CentroCosto::find($this->attributes['centro_costo_id']);
    }

    /**
     * Obtiene la orden de compra a través del flujo
     * 
     * @return array|null
     */
    public function ordenCompra()
    {
        $flujo = $this->autorizacionFlujo();
        if (!$flujo) {
            return null;
        }

        return OrdenCompra::find($flujo['orden_compra_id']);
    }

    /**
     * Obtiene todas las autorizaciones de un flujo
     * 
     * @param int $flujoId
     * @return array
     */
    public static function porFlujo($flujoId)
    {
        $instance = new static();
        
        error_log("=== DEBUG porFlujo ===");
        error_log("Flujo ID: $flujoId");
        
        $sql = "SELECT acc.*, cc.nombre as centro_nombre
                FROM autorizacion_centro_costo acc
                INNER JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
                WHERE acc.autorizacion_flujo_id = ?
                ORDER BY cc.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$flujoId]);
        
        $resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        error_log("Autorizaciones encontradas por flujo: " . count($resultados));
        foreach ($resultados as $i => $auth) {
            error_log("  [$i] ID: {$auth['id']}, Centro: {$auth['centro_nombre']}, Autorizador: {$auth['autorizador_email']}, Estado: {$auth['estado']}");
        }
        
        return $resultados;
    }

    /**
     * Obtiene autorizaciones pendientes de un autorizador
     * 
     * @param string $email
     * @return array
     */
    public static function pendientesPorAutorizador($email)
    {
        $instance = new static();
        
        error_log("=== BUSCANDO AUTORIZACIONES PENDIENTES ===");
        error_log("Email buscado: '$email'");
        
        // Primero verificar qué emails existen en la tabla
        $sqlEmails = "SELECT DISTINCT autorizador_email FROM autorizacion_centro_costo WHERE estado = 'pendiente'";
        $stmtEmails = self::getConnection()->prepare($sqlEmails);
        $stmtEmails->execute();
        $emails = $stmtEmails->fetchAll(\PDO::FETCH_COLUMN);
        error_log("Emails de autorizadores pendientes en BD: " . implode(', ', $emails));
        
        $sql = "SELECT 
                    acc.*,
                    cc.nombre as centro_nombre,
                    oc.id as orden_id,
                    oc.nombre_razon_social,
                    oc.monto_total,
                    oc.fecha as fecha_orden,
                    af.estado as estado_flujo
                FROM autorizacion_centro_costo acc
                INNER JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
                INNER JOIN autorizacion_flujo af ON acc.autorizacion_flujo_id = af.id
                INNER JOIN orden_compra oc ON af.orden_compra_id = oc.id
                WHERE acc.autorizador_email = ?
                AND acc.estado = 'pendiente'
                AND af.estado = 'pendiente_autorizacion_centros'
                ORDER BY oc.fecha DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        $resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        error_log("Autorizaciones encontradas: " . count($resultados));
        
        foreach ($resultados as $auth) {
            error_log("  - Orden {$auth['orden_id']}: Centro {$auth['centro_nombre']}, Email {$auth['autorizador_email']}");
        }
        
        return $resultados;
    }

    /**
     * Cuenta autorizaciones pendientes de un autorizador
     * 
     * @param string $email
     * @return int
     */
    public static function contarPendientes($email)
    {
        $instance = new static();
        
        $sql = "SELECT COUNT(*) as total
                FROM autorizacion_centro_costo acc
                INNER JOIN autorizacion_flujo af ON acc.autorizacion_flujo_id = af.id
                WHERE acc.autorizador_email = ?
                AND acc.estado = 'pendiente'
                AND af.estado = 'pendiente_autorizacion_centros'";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Autoriza una autorización de centro de costo
     * 
     * @param int $id
     * @param string $comentario
     * @return bool
     */
    public static function autorizar($id, $comentario = '')
    {
        $sql = "UPDATE autorizacion_centro_costo 
                SET estado = 'autorizado',
                    autorizador_fecha = NOW(),
                    autorizador_comentario = ?
                WHERE id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([$comentario, $id]);
    }

    /**
     * Rechaza una autorización de centro de costo
     * 
     * @param int $id
     * @param string $comentario
     * @return bool
     */
    public static function rechazar($id, $comentario)
    {
        $sql = "UPDATE autorizacion_centro_costo 
                SET estado = 'rechazado' 
                WHERE id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Verifica si todas las autorizaciones de un flujo están completas
     * 
     * @param int $flujoId
     * @return bool
     */
    public static function todasAutorizadas($flujoId)
    {
        $instance = new static();
        
        // Contar total, pendientes y autorizadas
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas
                FROM autorizacion_centro_costo
                WHERE autorizacion_flujo_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$flujoId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $total = $result['total'] ?? 0;
        $pendientes = $result['pendientes'] ?? 0;
        $autorizadas = $result['autorizadas'] ?? 0;
        $rechazadas = $result['rechazadas'] ?? 0;
        
        // Debug detallado
        error_log("=== VERIFICACIÓN todasAutorizadas FLUJO $flujoId ===");
        error_log("Total autorizaciones: $total");
        error_log("Pendientes: $pendientes");
        error_log("Autorizadas: $autorizadas");
        error_log("Rechazadas: $rechazadas");
        
        // Obtener detalle de cada autorización para log
        $sqlDetalle = "SELECT id, centro_costo_id, autorizador_email, estado 
                       FROM autorizacion_centro_costo 
                       WHERE autorizacion_flujo_id = ? 
                       ORDER BY id";
        $stmtDetalle = self::getConnection()->prepare($sqlDetalle);
        $stmtDetalle->execute([$flujoId]);
        $detalles = $stmtDetalle->fetchAll(\PDO::FETCH_ASSOC);
        
        error_log("Detalle de autorizaciones:");
        foreach ($detalles as $detalle) {
            error_log("  ID {$detalle['id']}: Centro {$detalle['centro_costo_id']}, Email {$detalle['autorizador_email']}, Estado {$detalle['estado']}");
        }
        
        // Solo considerar "todas autorizadas" si:
        // 1) Hay al menos 1 fila
        // 2) No hay pendientes
        // 3) Todas las filas están autorizadas (total == autorizadas)
        $resultado = ($total > 0 && $pendientes == 0 && $autorizadas == $total);
        error_log("¿Todas autorizadas? " . ($resultado ? 'SÍ' : 'NO'));
        error_log("Lógica: total($total) > 0 && pendientes($pendientes) == 0 && autorizadas($autorizadas) == total($total)");
        error_log("=== FIN VERIFICACIÓN todasAutorizadas ===");
        
        return $resultado;
    }

    /**
     * Verifica si hay alguna autorización rechazada en el flujo
     * 
     * @param int $flujoId
     * @return bool
     */
    public static function algunaRechazada($flujoId)
    {
        $instance = new static();
        
        $sql = "SELECT COUNT(*) as total
                FROM autorizacion_centro_costo
                WHERE autorizacion_flujo_id = ?
                AND estado = 'rechazado'";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$flujoId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Obtiene el progreso de autorizaciones de un flujo
     * 
     * @param int $flujoId
     * @return array
     */
    public static function getProgreso($flujoId)
    {
        $instance = new static();
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(porcentaje) as porcentaje_total,
                    SUM(CASE WHEN estado = 'autorizado' THEN porcentaje ELSE 0 END) as porcentaje_autorizado
                FROM autorizacion_centro_costo
                WHERE autorizacion_flujo_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$flujoId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result) {
            $result['porcentaje_completado'] = $result['total'] > 0 
                ? round(($result['autorizadas'] / $result['total']) * 100, 2) 
                : 0;
        }
        
        return $result ?: [
            'total' => 0,
            'autorizadas' => 0,
            'rechazadas' => 0,
            'pendientes' => 0,
            'porcentaje_total' => 0,
            'porcentaje_autorizado' => 0,
            'porcentaje_completado' => 0
        ];
    }

    /**
     * Crea autorizaciones para un flujo basado en la distribución de gastos
     * 
     * @param int $flujoId
     * @param int $ordenCompraId
     * @return bool
     */
    public static function crearParaFlujo($flujoId, $ordenCompraId)
    {
        try {
            // Obtener centros de costo de la distribución
            $sql = "SELECT DISTINCT 
                        dg.centro_costo_id,
                        SUM(dg.porcentaje) as porcentaje_total
                    FROM distribucion_gasto dg
                    WHERE dg.orden_compra_id = ?
                    GROUP BY dg.centro_costo_id";
            
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute([$ordenCompraId]);
            $centros = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Crear autorización para cada centro
            foreach ($centros as $centro) {
                $centroCostoId = $centro['centro_costo_id'];
                $porcentaje = $centro['porcentaje_total'];
                
                // Obtener autorizadores principales y de respaldo
                $autorizadores = [];
                
                // 1. Obtener autorizador principal
                $persona = PersonaAutorizada::principalPorCentro($centroCostoId);
                if ($persona && isset($persona['email'])) {
                    $autorizadores[] = [
                        'email' => $persona['email'],
                        'es_respaldo' => false,
                        'tipo' => 'principal'
                    ];
                }
                
                // 2. Obtener autorizadores de respaldo activos
                $respaldos = AutorizadorRespaldo::activoPorCentro($centroCostoId);
                if ($respaldos) {
                    // Si hay respaldo activo, incluirlo
                    $autorizadores[] = [
                        'email' => $respaldos['autorizador_respaldo_email'],
                        'es_respaldo' => true,
                        'tipo' => 'respaldo',
                        'motivo_respaldo' => $respaldos['motivo']
                    ];
                }
                
                if (empty($autorizadores)) {
                    error_log("No se encontraron autorizadores (principal + respaldo) para centro de costo ID: {$centroCostoId}");
                    continue;
                }
                
                // 3. Crear autorizaciones para todos los autorizadores (principal + respaldos)
                foreach ($autorizadores as $autorizador) {
                    self::create([
                        'autorizacion_flujo_id' => $flujoId,
                        'centro_costo_id' => $centroCostoId,
                        'porcentaje' => $porcentaje,
                        'autorizador_email' => $autorizador['email'],
                        'estado' => 'pendiente',
                        'metadata' => json_encode([
                            'es_respaldo' => $autorizador['es_respaldo'],
                            'tipo_autorizador' => $autorizador['tipo'],
                            'motivo_respaldo' => $autorizador['motivo_respaldo'] ?? null
                        ])
                    ]);
                    
                    $tipo = $autorizador['es_respaldo'] ? 'RESPALDO' : 'PRINCIPAL';
                    error_log("✅ Autorización centro de costo creada para: {$autorizador['email']} ($tipo)");
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error creando autorizaciones de centro: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene autorizaciones pendientes por flujo
     *
     * @param int $flujoId
     * @return array
     */
    public static function pendientesPorFlujo($flujoId)
    {
        $sql = "SELECT *
                FROM autorizacion_centro_costo
                WHERE autorizacion_flujo_id = ?
                  AND estado = 'pendiente'";

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$flujoId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el badge de estado
     * 
     * @return array
     */
    public function getEstadoBadge()
    {
        $estado = $this->attributes['estado'] ?? 'pendiente';
        
        $badges = [
            'pendiente' => ['class' => 'badge-warning', 'text' => 'Pendiente'],
            'autorizado' => ['class' => 'badge-success', 'text' => 'Autorizado'],
            'rechazado' => ['class' => 'badge-danger', 'text' => 'Rechazado'],
        ];

        return $badges[$estado] ?? $badges['pendiente'];
    }

    /**
     * Obtiene estadísticas por centro de costo
     * 
     * @param int $centroCostoId
     * @return array
     */
    public static function getEstadisticasCentro($centroCostoId)
    {
        $instance = new static();
        
        $sql = "SELECT 
                    COUNT(*) as total_autorizaciones,
                    SUM(CASE WHEN estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    AVG(porcentaje) as porcentaje_promedio
                FROM autorizacion_centro_costo
                WHERE centro_costo_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_autorizaciones' => 0,
            'autorizadas' => 0,
            'rechazadas' => 0,
            'pendientes' => 0,
            'porcentaje_promedio' => 0
        ];
    }

    /**
     * Obtiene autorizaciones por estado
     * 
     * @param int $flujoId
     * @param string $estado
     * @return array
     */
    public static function porEstado($flujoId, $estado)
    {
        $instance = new static();
        
        $sql = "SELECT acc.*, cc.nombre as centro_nombre
                FROM autorizacion_centro_costo acc
                INNER JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
                WHERE acc.autorizacion_flujo_id = ?
                AND acc.estado = ?
                ORDER BY cc.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$flujoId, $estado]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
