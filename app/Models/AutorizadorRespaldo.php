<?php
/**
 * Modelo AutorizadorRespaldo
 * 
 * Gestiona autorizadores de respaldo temporales para centros de costo.
 * Se activan automáticamente por fechas cuando el autorizador principal no está disponible.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class AutorizadorRespaldo extends Model
{
    protected static $table = 'autorizador_respaldo';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'centro_costo_id',
        'autorizador_principal_email',
        'autorizador_respaldo_email',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'motivo',
        'fecha_creacion',
        'creado_por',
    ];

    protected static $guarded = ['id'];

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
     * Obtiene respaldos por centro de costo
     * 
     * @param int $centroCostoId
     * @return array
     */
    public static function porCentroCosto($centroCostoId)
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table} 
                WHERE centro_costo_id = ? 
                ORDER BY fecha_inicio DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el respaldo activo actual de un centro de costo
     * 
     * @param int $centroCostoId
     * @return array|null
     */
    public static function activoPorCentro($centroCostoId)
    {
        $sql = "SELECT * FROM autorizador_respaldo 
                WHERE centro_costo_id = ? 
                AND estado = 'activo'
                AND CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene todos los respaldos activos
     * 
     * @return array
     */
    public static function todosActivos()
    {
        $instance = new static();
        
        $sql = "SELECT ar.*, cc.nombre as centro_nombre
                FROM autorizador_respaldo ar
                INNER JOIN centro_de_costo cc ON ar.centro_costo_id = cc.id
                WHERE ar.estado = 'activo'
                AND CURRENT_DATE BETWEEN ar.fecha_inicio AND ar.fecha_fin
                ORDER BY ar.fecha_inicio ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene respaldos por email del autorizador de respaldo
     * 
     * @param string $email
     * @return array
     */
    public static function porEmailRespaldo($email)
    {
        $instance = new static();
        
        $sql = "SELECT ar.*, cc.nombre as centro_nombre, cc.codigo as centro_codigo
                FROM {$instance->table} ar
                INNER JOIN centro_de_costo cc ON ar.centro_costo_id = cc.id
                WHERE ar.autorizador_respaldo_email = ?
                AND ar.estado = 'activo'
                AND CURRENT_DATE BETWEEN ar.fecha_inicio AND ar.fecha_fin
                ORDER BY cc.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si hay respaldo activo para un centro en una fecha
     * 
     * @param int $centroCostoId
     * @param string $fecha
     * @return bool
     */
    public static function hayRespaldoEnFecha($centroCostoId, $fecha = null)
    {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }

        $instance = new static();
        
        $sql = "SELECT COUNT(*) as total 
                FROM {$instance->table} 
                WHERE centro_costo_id = ? 
                AND estado = 'activo'
                AND ? BETWEEN fecha_inicio AND fecha_fin";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId, $fecha]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Activa un respaldo
     * 
     * @param int $id
     * @return bool
     */
    public static function activar($id)
    {
        return self::update($id, ['estado' => 'activo']);
    }

    /**
     * Desactiva un respaldo
     * 
     * @param int $id
     * @return bool
     */
    public static function desactivar($id)
    {
        return self::update($id, ['estado' => 'inactivo']);
    }

    /**
     * Completa un respaldo (cuando termina su período)
     * 
     * @param int $id
     * @return bool
     */
    public static function completar($id)
    {
        return self::update($id, ['estado' => 'completado']);
    }

    /**
     * Verifica si el respaldo está vigente hoy
     * 
     * @return bool
     */
    public function estaVigente()
    {
        $hoy = date('Y-m-d');
        
        if ($this->attributes['estado'] !== 'activo') {
            return false;
        }
        
        $inicio = $this->attributes['fecha_inicio'] ?? null;
        $fin = $this->attributes['fecha_fin'] ?? null;
        
        if (!$inicio || !$fin) {
            return false;
        }
        
        return $hoy >= $inicio && $hoy <= $fin;
    }

    /**
     * Obtiene días restantes del respaldo
     * 
     * @return int
     */
    public function diasRestantes()
    {
        if (!$this->estaVigente()) {
            return 0;
        }
        
        $hoy = new \DateTime();
        $fin = new \DateTime($this->attributes['fecha_fin']);
        $diff = $hoy->diff($fin);
        
        return $diff->days;
    }

    /**
     * Actualiza estados de respaldos (ejecutar en cron)
     * 
     * @return array Resultado de la actualización
     */
    public static function actualizarEstados()
    {
        $instance = new static();
        $hoy = date('Y-m-d');
        
        // Desactivar respaldos vencidos
        $sqlVencidos = "UPDATE {$instance->table} 
                        SET estado = 'completado' 
                        WHERE estado = 'activo' 
                        AND fecha_fin < ?";
        
        $stmt = self::getConnection()->prepare($sqlVencidos);
        $stmt->execute([$hoy]);
        $vencidos = $stmt->rowCount();
        
        // Activar respaldos que inician hoy
        $sqlIniciar = "UPDATE {$instance->table} 
                       SET estado = 'activo' 
                       WHERE estado = 'programado' 
                       AND fecha_inicio <= ? 
                       AND fecha_fin >= ?";
        
        $stmt = self::getConnection()->prepare($sqlIniciar);
        $stmt->execute([$hoy, $hoy]);
        $activados = $stmt->rowCount();
        
        return [
            'vencidos' => $vencidos,
            'activados' => $activados
        ];
    }

    /**
     * Obtiene respaldos próximos a vencer
     * 
     * @param int $dias Días de anticipación
     * @return array
     */
    public static function proximosAVencer($dias = 3)
    {
        $instance = new static();
        
        $sql = "SELECT ar.*, cc.nombre as centro_nombre
                FROM {$instance->table} ar
                INNER JOIN centro_de_costo cc ON ar.centro_costo_id = cc.id
                WHERE ar.estado = 'activo'
                AND ar.fecha_fin BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
                ORDER BY ar.fecha_fin ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$dias]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas de respaldos
     * 
     * @return array
     */
    public static function getEstadisticas()
    {
        $instance = new static();
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN estado = 'programado' THEN 1 ELSE 0 END) as programados,
                    SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
                FROM {$instance->table}";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total' => 0,
            'activos' => 0,
            'programados' => 0,
            'completados' => 0,
            'inactivos' => 0
        ];
    }

    /**
     * Valida que no haya conflictos de fechas
     * 
     * @param int $centroCostoId
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param int $excluirId ID a excluir en la validación
     * @return bool
     */
    public static function hayConflictoFechas($centroCostoId, $fechaInicio, $fechaFin, $excluirId = null)
    {
        $instance = new static();
        
        $sql = "SELECT COUNT(*) as total 
                FROM {$instance->table} 
                WHERE centro_costo_id = ?
                AND estado IN ('activo', 'programado')
                AND (
                    (fecha_inicio BETWEEN ? AND ?)
                    OR (fecha_fin BETWEEN ? AND ?)
                    OR (? BETWEEN fecha_inicio AND fecha_fin)
                    OR (? BETWEEN fecha_inicio AND fecha_fin)
                )";
        
        $params = [$centroCostoId, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin];
        
        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }
}
