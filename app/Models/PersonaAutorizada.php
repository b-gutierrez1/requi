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
     * Obtiene personas activas de un centro de costo.
     *
     * Alias de porCentroCosto() — ambos métodos eran idénticos.
     * Se mantiene por backward compatibility; preferir porCentroCosto() en código nuevo.
     *
     * @param int $centroCostoId
     * @return array
     */
    public static function activasPorCentroCosto($centroCostoId)
    {
        return self::porCentroCosto($centroCostoId);
    }

    /**
     * Obtiene el autorizador principal activo de un centro
     * 
     * @param int $centroCostoId
     * @return array|null
     */
    public static function principalPorCentro($centroCostoId)
    {
        $table = static::getTable();
        $sql = "SELECT * FROM {$table} 
                WHERE centro_costo_id = ? 
                ORDER BY id ASC 
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Devuelve todos los autorizadores activos de un centro ordenados por su turno de aprobación.
     *
     * A diferencia de principalPorCentro(), retorna todos los autorizadores del centro
     * con su campo `orden` (1=aprueba primero, 2=aprueba segundo), consultando la tabla
     * base para acceder a la columna orden que la vista persona_autorizada no expone.
     *
     * @param int $centroCostoId
     * @return array  Cada elemento: ['email', 'nombre', 'orden', 'autorizador_id']
     */
    public static function todosPorCentro(int $centroCostoId): array
    {
        $sql = "SELECT a.email, a.nombre, a.cargo, acc.orden, acc.autorizador_id
                FROM autorizador_centro_costo acc
                JOIN autorizadores a ON a.id = acc.autorizador_id
                WHERE acc.centro_costo_id = ?
                  AND acc.activo = 1
                  AND a.activo = 1
                ORDER BY acc.orden ASC, a.id ASC";

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
                    a.*,
                    oc.id as orden_id,
                    oc.nombre_razon_social,
                    oc.monto_total,
                    oc.fecha,
                    cc.nombre as centro_costo_nombre
                FROM autorizaciones a
                INNER JOIN requisiciones oc ON a.requisicion_id = oc.id
                LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
                WHERE a.autorizador_email = ?
                  AND a.estado = 'pendiente'
                  AND a.tipo = 'centro_costo'
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
                FROM autorizaciones a
                WHERE a.autorizador_email = ?
                  AND a.estado = 'pendiente'
                  AND a.tipo = 'centro_costo'";
        
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
                    SUM(CASE WHEN a.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN a.estado = 'aprobada' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN a.estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
                FROM autorizaciones a
                WHERE a.autorizador_email = ?
                  AND a.tipo = 'centro_costo'";
        
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

    /**
     * Obtiene personas autorizadas paginadas
     *
     * @param int $page    Página actual (base 1)
     * @param int $perPage Registros por página
     * @return array
     */
    public static function paginate($page = 1, $perPage = 20)
    {
        $page    = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset  = ($page - 1) * $perPage;

        $table = static::getTable();
        $sql   = "SELECT * FROM {$table} ORDER BY id ASC LIMIT ? OFFSET ?";
        $stmt  = self::getConnection()->prepare($sql);
        $stmt->bindValue(1, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset,  \PDO::PARAM_INT);
        $stmt->execute();

        $rows    = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $objects = [];
        foreach ($rows as $row) {
            $obj = new static();
            foreach ($row as $key => $value) {
                $obj->setAttribute($key, $value);
            }
            $objects[] = $obj;
        }
        return $objects;
    }
}
