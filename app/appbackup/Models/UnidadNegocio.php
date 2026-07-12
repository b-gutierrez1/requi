<?php
/**
 * Modelo UnidadNegocio
 * 
 * Representa las unidades de negocio de la organización.
 * Cada distribución de gasto está asociada a una unidad de negocio.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class UnidadNegocio extends Model
{
    protected static $table = 'unidad_de_negocio';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'nombre',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene las distribuciones de gasto asociadas
     * 
     * @return array
     */
    public function distribucionesGasto()
    {
        $sql = "SELECT * FROM distribucion_gasto WHERE unidad_negocio_id = ?";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todas las unidades de negocio activas
     * 
     * @return array
     */
    public static function activas()
    {
        $table = static::$table;
        
        $sql = "SELECT * FROM {$table} ORDER BY nombre ASC";
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
     * Busca unidades de negocio por término
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
     * Obtiene unidad de negocio por código (no disponible - tabla solo tiene nombre)
     * 
     * @param string $codigo
     * @return array|null
     */
    public static function porCodigo($codigo)
    {
        // La tabla unidad_de_negocio no tiene columna codigo
        return null;
    }

    /**
     * Activa o desactiva la unidad de negocio (no disponible - tabla no tiene columna activo)
     * 
     * @param bool $activo
     * @return bool
     */
    public function setActivo($activo = true)
    {
        // La tabla unidad_de_negocio no tiene columna activo
        return false;
    }

    /**
     * Obtiene el total gastado en esta unidad de negocio
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
                WHERE dg.unidad_negocio_id = ?";
        
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
     * Cuenta requisiciones por unidad de negocio
     * 
     * @return int
     */
    public function contarRequisiciones()
    {
        $sql = "SELECT COUNT(DISTINCT dg.requisicion_id) as total
                FROM distribucion_gasto dg
                WHERE dg.unidad_negocio_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene estadísticas de la unidad de negocio
     * 
     * @return array
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(DISTINCT dg.requisicion_id) as total_requisiciones,
                    SUM(dg.cantidad) as monto_total,
                    AVG(dg.cantidad) as monto_promedio,
                    COUNT(DISTINCT dg.centro_costo_id) as centros_costo_utilizados
                FROM distribucion_gasto dg
                WHERE dg.unidad_negocio_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_requisiciones' => 0,
            'monto_total' => 0,
            'monto_promedio' => 0,
            'centros_costo_utilizados' => 0
        ];
    }
}
