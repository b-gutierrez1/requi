<?php
/**
 * Modelo Ubicacion
 * 
 * Representa las ubicaciones físicas de la organización
 * donde se pueden entregar o recibir las requisiciones.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class Ubicacion extends Model
{
    protected static $table = 'ubicacion';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'nombre',
        'descripcion',
        'direccion',
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
        $sql = "SELECT * FROM distribucion_gasto WHERE ubicacion_id = ?";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todas las ubicaciones activas
     * 
     * @return array
     */
    public static function activas()
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE activo = 1 ORDER BY nombre ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca ubicaciones por nombre
     * 
     * @param string $termino
     * @return array
     */
    public static function buscar($termino)
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE nombre LIKE ? 
                AND activo = 1 
                ORDER BY nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $search = "%{$termino}%";
        $stmt->execute([$search]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Activa o desactiva la ubicación
     * 
     * @param bool $activo
     * @return bool
     */
    public function setActivo($activo = true)
    {
        return self::update($this->attributes['id'], ['activo' => $activo ? 1 : 0]);
    }

    /**
     * Obtiene el total gastado en esta ubicación
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
                WHERE dg.ubicacion_id = ?";
        
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
     * Cuenta requisiciones por ubicación
     * 
     * @return int
     */
    public function contarRequisiciones()
    {
        $sql = "SELECT COUNT(DISTINCT dg.requisicion_id) as total
                FROM distribucion_gasto dg
                WHERE dg.ubicacion_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
