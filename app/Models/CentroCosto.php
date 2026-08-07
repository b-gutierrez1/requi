<?php
/**
 * Modelo CentroCosto
 * 
 * Representa los centros de costo de la organización.
 * Cada distribución de gasto está asociada a un centro de costo.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class CentroCosto extends Model
{
    protected static $table = 'centro_de_costo';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'nombre',
        'codigo',
        'activo',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene las distribuciones de gasto asociadas
     * 
     * @return array
     */
    public function distribucionesGasto()
    {
        $sql = "SELECT * FROM distribucion_gasto WHERE centro_costo_id = ?";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los centros de costo activos
     *
     * @return array
     */
    public static function activas()
    {
        $table = static::$table;
        
        $sql = "SELECT * FROM {$table} WHERE activo = 1 ORDER BY nombre ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca por nombre
     * 
     * @param string $nombre
     * @return array|null
     */
    public static function buscarPorNombre($nombre)
    {
        $table = static::$table;
        
        $sql = "SELECT * FROM {$table} 
                WHERE nombre = ? 
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$nombre]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Busca centros de costo por término
     * 
     * @param string $termino
     * @return array
     */
    public static function buscar($termino)
    {
        $table = static::$table;
        
        $sql = "SELECT * FROM {$table} 
                WHERE nombre LIKE ? 
                ORDER BY nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $search = "%{$termino}%";
        $stmt->execute([$search]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene centro de costo por código
     *
     * @param string $codigo
     * @return array|null
     */
    public static function porCodigo($codigo)
    {
        $table = static::$table;

        $sql = "SELECT * FROM {$table}
                WHERE codigo = ?
                LIMIT 1";

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$codigo]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Activa o desactiva el centro de costo
     *
     * @param bool $activo
     * @return bool
     */
    public function setActivo($activo = true)
    {
        return static::updateById($this->attributes['id'], ['activo' => $activo ? 1 : 0]);
    }

    /**
     * Obtiene el total gastado en este centro de costo
     * 
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return float
     */
    public function getTotalGastado($fechaInicio = null, $fechaFin = null)
    {
        $sql = "SELECT SUM(dg.cantidad) as total
                FROM distribucion_gasto dg
                INNER JOIN requisiciones oc ON dg.requisicion_id = oc.id
                WHERE dg.centro_costo_id = ?";
        
        $params = [$this->attributes['id']];
        
        if ($fechaInicio && $fechaFin) {
            $sql .= " AND oc.fecha BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Cuenta requisiciones por centro de costo
     * 
     * @return int
     */
    public function contarRequisiciones()
    {
        $sql = "SELECT COUNT(DISTINCT dg.requisicion_id) as total
                FROM distribucion_gasto dg
                WHERE dg.centro_costo_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene estadísticas del centro de costo
     * 
     * @return array
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(DISTINCT dg.requisicion_id) as total_requisiciones,
                    SUM(dg.cantidad) as monto_total,
                    AVG(dg.cantidad) as monto_promedio,
                    COUNT(DISTINCT dg.unidad_negocio_id) as unidades_negocio_utilizados
                FROM distribucion_gasto dg
                WHERE dg.centro_costo_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_requisiciones' => 0,
            'monto_total' => 0,
            'monto_promedio' => 0,
            'unidades_negocio_utilizados' => 0
        ];
    }
}
