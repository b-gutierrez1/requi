<?php
/**
 * Modelo CuentaContable
 * 
 * Representa las cuentas contables del sistema.
 * Algunas cuentas pueden tener autorizadores especiales asignados.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class CuentaContable extends Model
{
    protected static $table = 'cuenta_contable';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'codigo',
        'descripcion',
        'activo',
        'tipo',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene el nombre completo (código + descripción)
     * 
     * @return string
     */
    public function getNombreCompleto()
    {
        if (!isset($this->attributes['codigo']) || !isset($this->attributes['descripcion'])) {
            return '';
        }
        
        return $this->attributes['codigo'] . ' - ' . $this->attributes['descripcion'];
    }

    /**
     * Obtiene las distribuciones de gasto asociadas
     * 
     * @return array
     */
    public function distribucionesGasto()
    {
        $sql = "SELECT * FROM distribucion_gasto WHERE cuenta_contable_id = ?";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si esta cuenta tiene autorizador especial
     * 
     * @return bool
     */
    public function tieneAutorizadorEspecial()
    {
        $sql = "SELECT COUNT(*) as total 
                FROM autorizadores_cuentas_contables 
                WHERE cuenta_contable_id = ? 
                AND activo = 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Obtiene el autorizador especial de esta cuenta
     * 
     * @return array|null
     */
    public function getAutorizadorEspecial()
    {
        $sql = "SELECT * FROM autorizadores_cuentas_contables 
                WHERE cuenta_contable_id = ? 
                AND activo = 1 
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene todas las cuentas contables activas
     * 
     * @return array
     */
    public static function activas()
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table} WHERE activo = 1 ORDER BY codigo ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene cuentas activas con nombre completo
     * 
     * @return array
     */
    public static function activasConNombreCompleto()
    {
        $instance = new static();
        
        $sql = "SELECT id, CONCAT(codigo, ' - ', descripcion) as nombre_completo 
                FROM {$instance->table} 
                WHERE activo = 1 
                ORDER BY codigo ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca cuentas contables por código o descripción
     * 
     * @param string $termino
     * @return array
     */
    public static function buscar($termino)
    {
        $instance = new static();
        
        $sql = "SELECT *, CONCAT(codigo, ' - ', descripcion) as nombre_completo 
                FROM {$instance->table} 
                WHERE (codigo LIKE ? OR descripcion LIKE ?) 
                AND activo = 1 
                ORDER BY codigo ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $search = "%{$termino}%";
        $stmt->execute([$search, $search]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene cuentas contables por tipo
     * 
     * @param string $tipo
     * @return array
     */
    public static function porTipo($tipo)
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table} 
                WHERE tipo = ? 
                AND activo = 1 
                ORDER BY codigo ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$tipo]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el total gastado en esta cuenta contable
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
                WHERE dg.cuenta_contable_id = ?";
        
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
     * Verifica si es una cuenta especial (como donaciones)
     * 
     * @return bool
     */
    public function esCuentaEspecial()
    {
        // Cuenta de donaciones u otras que requieren autorización especial
        $cuentasEspeciales = ['210105032-00-00'];
        
        return isset($this->attributes['codigo']) && 
               in_array($this->attributes['codigo'], $cuentasEspeciales);
    }

    /**
     * Activa o desactiva la cuenta contable
     * 
     * @param bool $activo
     * @return bool
     */
    public function setActivo($activo = true)
    {
        return self::update($this->attributes['id'], ['activo' => $activo ? 1 : 0]);
    }

    /**
     * Obtiene estadísticas de uso de la cuenta
     * 
     * @return array
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(DISTINCT dg.orden_compra_id) as total_requisiciones,
                    SUM(dg.cantidad) as monto_total,
                    AVG(dg.cantidad) as monto_promedio
                FROM distribucion_gasto dg
                WHERE dg.cuenta_contable_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_requisiciones' => 0,
            'monto_total' => 0,
            'monto_promedio' => 0
        ];
    }
}
