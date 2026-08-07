<?php
/**
 * Modelo PersonaAutorizada
 * 
 * Representa las personas autorizadas para aprobar requisiciones
 * de una unidad de negocio específica.
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
        'unidad_negocio_id',
        'nombre',
        'email',
        'cargo',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene la unidad de negocio asociada
     * 
     * @return array|null
     */
    public function unidadNegocio()
    {
        if (!isset($this->attributes['unidad_negocio_id'])) {
            return null;
        }

        return UnidadNegocio::find($this->attributes['unidad_negocio_id']);
    }

    /**
     * Obtiene todas las personas autorizadas de una unidad de negocio
     * 
     * @param int $unidadNegocioId
     * @return array
     */
    public static function porUnidadNegocio($unidadNegocioId)
    {
        $table = static::getTable();
        
        $sql = "SELECT * FROM {$table} 
                WHERE unidad_negocio_id = ? 
                ORDER BY id ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$unidadNegocioId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene personas activas de una unidad de negocio.
     *
     * Alias de porUnidadNegocio() — ambos métodos eran idénticos.
     * Se mantiene por backward compatibility; preferir porUnidadNegocio() en código nuevo.
     *
     * @param int $unidadNegocioId
     * @return array
     */
    public static function activasPorUnidadNegocio($unidadNegocioId)
    {
        return self::porUnidadNegocio($unidadNegocioId);
    }

    /**
     * Obtiene el autorizador principal activo de un centro
     * 
     * @param int $unidadNegocioId
     * @return array|null
     */
    public static function principalPorCentro($unidadNegocioId)
    {
        $table = static::getTable();
        $sql = "SELECT * FROM {$table} 
                WHERE unidad_negocio_id = ? 
                ORDER BY id ASC 
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$unidadNegocioId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Devuelve todos los autorizadores activos de un centro ordenados por su turno de aprobación.
     *
     * A diferencia de principalPorCentro(), retorna todos los autorizadores del centro
     * con su campo `orden` (1=aprueba primero, 2=aprueba segundo), consultando la tabla
     * base para acceder a la columna orden que la vista persona_autorizada no expone.
     *
     * @param int $unidadNegocioId
     * @return array  Cada elemento: ['email', 'nombre', 'orden', 'autorizador_id']
     */
    public static function todosPorCentro(int $unidadNegocioId): array
    {
        $sql = "SELECT a.email, a.nombre, a.cargo, acc.orden, acc.autorizador_id
                FROM autorizador_unidad_negocio acc
                JOIN autorizadores a ON a.id = acc.autorizador_id
                WHERE acc.unidad_negocio_id = ?
                  AND acc.activo = 1
                  AND a.activo = 1
                ORDER BY acc.orden ASC, a.id ASC";

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$unidadNegocioId]);

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
     * Obtiene todas las unidades de negocio de una persona autorizada
     * 
     * @param string $email
     * @return array
     */
    public static function centrosCostoPorEmail($email)
    {
        $table = static::getTable();
        
        $sql = "SELECT DISTINCT 
                    pa.unidad_negocio_id,
                    cc.nombre as centro_nombre,
                    cc.id as centro_id
                FROM {$table} pa
                INNER JOIN unidad_de_negocio cc ON pa.unidad_negocio_id = cc.id
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
     * @param int $unidadNegocioId
     * @return bool
     */
    public static function esAutorizadorDe($email, $unidadNegocioId)
    {
        $table = static::getTable();
        
        $sql = "SELECT COUNT(*) as total 
                FROM {$table} 
                WHERE email = ? 
                AND unidad_negocio_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email, $unidadNegocioId]);
        
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
                    cc.nombre as unidad_negocio_nombre
                FROM autorizaciones a
                INNER JOIN requisiciones oc ON a.requisicion_id = oc.id
                LEFT JOIN unidad_de_negocio cc ON a.unidad_negocio_id = cc.id
                WHERE a.autorizador_email = ?
                  AND a.estado = 'pendiente'
                  AND a.tipo = 'unidad_negocio'
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
                  AND a.tipo = 'unidad_negocio'";
        
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
                  AND a.tipo = 'unidad_negocio'";
        
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
