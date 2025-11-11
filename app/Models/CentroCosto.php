<?php
/**
 * Modelo CentroCosto
 * 
 * Representa los centros de costo de la organización.
 * Cada centro de costo tiene autorizadores asignados y está vinculado a una Unidad de Negocio.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.1
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
        'descripcion',
        'activo',
        'unidad_negocio_id',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene la unidad de negocio asociada a este centro de costo
     * 
     * @return array|null
     */
    public function getUnidadNegocio()
    {
        if (!isset($this->attributes['unidad_negocio_id']) || !$this->attributes['unidad_negocio_id']) {
            return null;
        }

        return UnidadNegocio::find($this->attributes['unidad_negocio_id']);
    }

    /**
     * Obtiene el ID de la unidad de negocio
     * 
     * @return int|null
     */
    public function getUnidadNegocioId()
    {
        return $this->attributes['unidad_negocio_id'] ?? null;
    }

    /**
     * Obtiene las personas autorizadas de este centro de costo
     * 
     * @return array
     */
    public function personasAutorizadas()
    {
        $sql = "SELECT * FROM persona_autorizada WHERE centro_costo_id = ?";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el autorizador principal activo
     * 
     * @return array|null
     */
    public function getAutorizadorPrincipal()
    {
        $sql = "SELECT * FROM persona_autorizada 
                WHERE centro_costo_id = ? 
                ORDER BY id ASC 
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $centroId = $this->attributes['id'] ?? $this->id ?? null;
        if (!$centroId) {
            return null;
        }
        $stmt->execute([$centroId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene el autorizador de respaldo activo actual
     * 
     * @return array|null
     */
    public function getAutorizadorRespaldoActivo()
    {
        $sql = "SELECT * FROM autorizador_respaldo 
                WHERE centro_costo_id = ? 
                AND estado = 'activo'
                AND CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $centroId = $this->attributes['id'] ?? $this->id ?? null;
        if (!$centroId) {
            return null;
        }
        $stmt->execute([$centroId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene el email del autorizador (respaldo o principal)
     * 
     * @return string|null
     */
    public function getEmailAutorizador()
    {
        // Primero buscar respaldo activo
        $respaldo = $this->getAutorizadorRespaldoActivo();
        if ($respaldo) {
            return $respaldo['autorizador_respaldo_email'];
        }

        // Si no hay respaldo, buscar principal
        $principal = $this->getAutorizadorPrincipal();
        if ($principal) {
            return $principal['email'];
        }

        return null;
    }

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
     * Obtiene todos los centros de costo activos con su unidad de negocio
     * 
     * @return array
     */
    public static function activos()
    {
        $table = static::$table;
        
        $sql = "SELECT cc.*, un.nombre as unidad_negocio_nombre 
                FROM {$table} cc
                LEFT JOIN unidad_de_negocio un ON cc.unidad_negocio_id = un.id
                WHERE cc.activo = 1 
                ORDER BY cc.nombre ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca centros de costo por nombre o código
     * 
     * @param string $termino
     * @return array
     */
    public static function buscar($termino)
    {
        $table = static::$table;
        
        $sql = "SELECT cc.*, un.nombre as unidad_negocio_nombre 
                FROM {$table} cc
                LEFT JOIN unidad_de_negocio un ON cc.unidad_negocio_id = un.id
                WHERE (cc.nombre LIKE ? OR cc.codigo LIKE ?) 
                AND cc.activo = 1 
                ORDER BY cc.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $search = "%{$termino}%";
        $stmt->execute([$search, $search]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
                INNER JOIN orden_compra oc ON dg.orden_compra_id = oc.id
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
     * Contar total de centros de costo
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
     * Verifica si tiene autorizador asignado
     * 
     * @return bool
     */
    public function tieneAutorizador()
    {
        return $this->getEmailAutorizador() !== null;
    }

    /**
     * Activa o desactiva el centro de costo
     * 
     * @param bool $activo
     * @return bool
     */
    public function setActivo($activo = true)
    {
        return self::update($this->attributes['id'], ['activo' => $activo ? 1 : 0]);
    }
}
