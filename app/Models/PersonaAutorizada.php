<?php
/**
 * Modelo PersonaAutorizada
 * 
 * Representa las personas autorizadas para aprobar requisiciones
 * de un centro de costo específico.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class PersonaAutorizada extends Model
{
    protected static $table = 'persona_autorizada';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'centro_costo_id',
        'nombre',
        'email',
        'cargo',
        'prioridad',
        'activo',
        'fecha_inicio',
        'fecha_fin',
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
     * Obtiene todas las personas autorizadas de un centro de costo
     * 
     * @param int $centroCostoId
     * @return array
     */
    public static function porCentroCosto($centroCostoId)
    {
        $table = static::getTable();
        
        $sql = "SELECT * FROM {$table} 
                WHERE centro_costo_id = ? 
                ORDER BY id ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene personas activas de un centro de costo
     * 
     * @param int $centroCostoId
     * @return array
     */
    public static function activasPorCentroCosto($centroCostoId)
    {
        $table = static::getTable();
        
        $sql = "SELECT * FROM {$table} 
                WHERE centro_costo_id = ? 
                ORDER BY id ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el autorizador principal activo de un centro
     * 
     * @param int $centroCostoId
     * @return array|null
     */
    public static function principalPorCentro($centroCostoId)
    {
        $sql = "SELECT * FROM persona_autorizada 
                WHERE centro_costo_id = ? 
                ORDER BY id ASC 
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Busca persona autorizada por email
     * 
     * @param string $email
     * @return array|null
     */
    public static function porEmail($email)
    {
        $table = static::getTable();
        
        $sql = "SELECT * FROM {$table} WHERE email = ? LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene todos los centros de costo de una persona autorizada
     * 
     * @param string $email
     * @return array
     */
    public static function centrosCostoPorEmail($email)
    {
        $table = static::getTable();
        
        $sql = "SELECT DISTINCT 
                    pa.centro_costo_id,
                    cc.nombre as centro_nombre,
                    cc.id as centro_id
                FROM {$table} pa
                INNER JOIN centro_de_costo cc ON pa.centro_costo_id = cc.id
                WHERE pa.email = ? 
                ORDER BY cc.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un email es autorizador de un centro
     * 
     * @param string $email
     * @param int $centroCostoId
     * @return bool
     */
    public static function esAutorizadorDe($email, $centroCostoId)
    {
        $table = static::getTable();
        
        $sql = "SELECT COUNT(*) as total 
                FROM {$table} 
                WHERE email = ? 
                AND centro_costo_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email, $centroCostoId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Activa o desactiva una persona autorizada
     * 
     * @param int $id
     * @param bool $activo
     * @return bool
     */
    public static function setActivo($id, $activo = true)
    {
        return self::update($id, ['activo' => $activo ? 1 : 0]);
    }

    /**
     * Verifica si la persona está vigente (por fechas)
     * 
     * @return bool
     */
    public function estaVigente()
    {
        $hoy = date('Y-m-d');
        
        $inicio = $this->attributes['fecha_inicio'] ?? null;
        $fin = $this->attributes['fecha_fin'] ?? null;
        
        // Si no hay fechas, está vigente
        if (!$inicio && !$fin) {
            return true;
        }
        
        // Verificar rango de fechas
        if ($inicio && $hoy < $inicio) {
            return false;
        }
        
        if ($fin && $hoy > $fin) {
            return false;
        }
        
        return true;
    }

    /**
     * Obtiene autorizaciones pendientes de esta persona
     * 
     * @param string $email
     * @return array
     */
    public static function autorizacionesPendientes($email)
    {
        $sql = "SELECT 
                    acc.*,
                    oc.id as orden_id,
                    oc.nombre_razon_social,
                    oc.monto_total,
                    oc.fecha,
                    cc.nombre as centro_costo_nombre
                FROM autorizacion_centro_costo acc
                INNER JOIN autorizacion_flujo af ON acc.autorizacion_flujo_id = af.id
                INNER JOIN orden_compra oc ON af.orden_compra_id = oc.id
                INNER JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
                WHERE acc.autorizador_email = ?
                AND acc.estado = 'pendiente'
                ORDER BY oc.fecha DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta autorizaciones pendientes de una persona
     * 
     * @param string $email
     * @return int
     */
    public static function contarPendientes($email)
    {
        $sql = "SELECT COUNT(*) as total
                FROM autorizacion_centro_costo acc
                INNER JOIN autorizacion_flujo af ON acc.autorizacion_flujo_id = af.id
                WHERE acc.autorizador_email = ?
                AND acc.estado = 'pendiente'
                AND af.estado = 'pendiente_autorizacion'";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene estadísticas de una persona autorizada
     * 
     * @param string $email
     * @return array
     */
    public static function getEstadisticas($email)
    {
        $sql = "SELECT 
                    COUNT(*) as total_autorizaciones,
                    SUM(CASE WHEN acc.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN acc.estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN acc.estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas
                FROM autorizacion_centro_costo acc
                WHERE acc.autorizador_email = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_autorizaciones' => 0,
            'pendientes' => 0,
            'autorizadas' => 0,
            'rechazadas' => 0
        ];
    }

    /**
     * Contar total de personas autorizadas
     * 
     * @return int
     */
    public static function count()
    {
        $stmt = self::query("SELECT COUNT(*) as total FROM " . self::getTable());
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }
}
