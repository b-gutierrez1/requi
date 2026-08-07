<?php
/**
 * Modelo UnidadNegocio
 * 
 * Representa los unidades de negocio de la organización.
 * Cada unidad de negocio tiene autorizadores asignados y está vinculado a una Centro de Costo.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.1
 */

namespace App\Models;

class UnidadNegocio extends Model
{
    protected static $table = 'unidad_de_negocio';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'nombre',
        'codigo',
        'factura',
        'centro_costo_id',
        'requiere_asignacion_manual',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene la centro de costo asociada a este unidad de negocio
     * 
     * @return array|null
     */
    public function getCentroCosto()
    {
        if (!isset($this->attributes['centro_costo_id']) || !$this->attributes['centro_costo_id']) {
            return null;
        }

        return CentroCosto::find($this->attributes['centro_costo_id']);
    }

    /**
     * Obtiene el ID de la centro de costo
     * 
     * @return int|null
     */
    public function getCentroCostoId()
    {
        return $this->attributes['centro_costo_id'] ?? null;
    }

    /**
     * Obtiene las personas autorizadas de este unidad de negocio
     * 
     * @return array
     */
    public function personasAutorizadas()
    {
        $table = \App\Models\PersonaAutorizada::getTable();
        $sql = "SELECT * FROM {$table} WHERE unidad_negocio_id = ?";
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
        $table = \App\Models\PersonaAutorizada::getTable();
        $sql = "SELECT * FROM {$table} 
                WHERE unidad_negocio_id = ? 
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
        $centroId = $this->attributes['id'] ?? $this->id ?? null;
        if (!$centroId) {
            return null;
        }

        return AutorizadorRespaldo::activoPorCentro($centroId);
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
        $sql = "SELECT * FROM distribucion_gasto WHERE unidad_negocio_id = ?";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los unidades de negocio activos con su centro de costo y factura
     * 
     * @return array
     */
    public static function activos()
    {
        $table = static::$table;
        
        // Nota: cc.* ya incluye cc.factura, pero agregamos COALESCE para asegurar un valor por defecto
        $sql = "SELECT cc.*, 
                       un.id as rel_centro_costo_id,
                       un.nombre as centro_costo_nombre,
                       cc.factura as factura_numero
                FROM {$table} cc
                LEFT JOIN centro_de_costo un ON cc.centro_costo_id = un.id
                WHERE cc.activo = 1
                ORDER BY cc.nombre ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca unidades de negocio por nombre o código
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
     * Obtiene el total gastado en este unidad de negocio
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
     * Obtiene centros que requieren asignación manual de autorizador en revisión
     */
    public static function conAsignacionManual(): array
    {
        $table = static::$table;
        $sql = "SELECT id, nombre FROM {$table} WHERE requiere_asignacion_manual = 1 AND activo = 1 ORDER BY nombre ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Contar total de unidades de negocio
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
     * Activa o desactiva el unidad de negocio
     * 
     * @param bool $activo
     * @return bool
     */
    public function setActivo($activo = true)
    {
        return self::update($this->attributes['id'], ['activo' => $activo ? 1 : 0]);
    }
}
