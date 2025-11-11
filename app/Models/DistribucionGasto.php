<?php
/**
 * Modelo DistribucionGasto
 * 
 * Representa la distribución de gastos de una requisición entre
 * diferentes centros de costo, cuentas contables y ubicaciones.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class DistribucionGasto extends Model
{
    protected static $table = 'distribucion_gasto';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'orden_compra_id',
        'cuenta_contable_id',
        'centro_costo_id',
        'ubicacion_id',
        'unidad_negocio_id',
        'porcentaje',
        'cantidad',
        'factura',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtener atributos fillable procesando valores vacíos
     * 
     * @return array
     */
    protected function getFillableAttributes()
    {
        $fillable = [];
        
        foreach ($this->attributes as $key => $value) {
            if ($this->isFillable($key)) {
                // Convertir strings vacíos a null para campos de ID
                if (in_array($key, ['ubicacion_id', 'unidad_negocio_id']) && ($value === '' || $value === '0')) {
                    $fillable[$key] = null;
                } else {
                    $fillable[$key] = $value;
                }
            }
        }
        
        return $fillable;
    }

    /**
     * Obtiene la orden de compra asociada
     * 
     * @return array|null
     */
    public function ordenCompra()
    {
        if (!isset($this->attributes['orden_compra_id'])) {
            return null;
        }

        return OrdenCompra::find($this->attributes['orden_compra_id']);
    }

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
     * Obtiene la cuenta contable asociada
     * 
     * @return array|null
     */
    public function cuentaContable()
    {
        if (!isset($this->attributes['cuenta_contable_id'])) {
            return null;
        }

        return CuentaContable::find($this->attributes['cuenta_contable_id']);
    }

    /**
     * Obtiene la ubicación asociada
     * 
     * @return array|null
     */
    public function ubicacion()
    {
        if (!isset($this->attributes['ubicacion_id'])) {
            return null;
        }

        $sql = "SELECT * FROM ubicacion WHERE id = ? LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['ubicacion_id']]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

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

        $sql = "SELECT * FROM unidad_de_negocio WHERE id = ? LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['unidad_negocio_id']]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene todas las distribuciones de una orden de compra
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function porOrdenCompra($ordenCompraId)
    {
        $sql = "SELECT dg.*, 
                       cc.nombre as centro_nombre,
                       cu.codigo as cuenta_contable_codigo,
                       cu.descripcion as cuenta_nombre,
                       u.nombre as ubicacion_nombre,
                       un.nombre as unidad_negocio_nombre,
                       (dg.porcentaje * oc.monto_total / 100) as monto
                   FROM " . static::$table . " dg
                   LEFT JOIN centro_de_costo cc ON dg.centro_costo_id = cc.id
                   LEFT JOIN cuenta_contable cu ON dg.cuenta_contable_id = cu.id
                   LEFT JOIN ubicacion u ON dg.ubicacion_id = u.id
                   LEFT JOIN unidad_de_negocio un ON dg.unidad_negocio_id = un.id
                   LEFT JOIN orden_compra oc ON dg.orden_compra_id = oc.id
                   WHERE dg.orden_compra_id = ?
                   ORDER BY dg.id ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los centros de costo únicos de una orden
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function getCentrosCostoOrden($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT DISTINCT dg.centro_costo_id, cc.nombre
                FROM {$instance->table} dg
                INNER JOIN centro_de_costo cc ON dg.centro_costo_id = cc.id
                WHERE dg.orden_compra_id = ?
                ORDER BY cc.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Valida que el total de porcentajes sea 100%
     * 
     * @param array $distribuciones Array de distribuciones
     * @return bool
     */
    public static function validarPorcentajes($distribuciones)
    {
        $totalPorcentaje = 0;
        
        foreach ($distribuciones as $dist) {
            $totalPorcentaje += floatval($dist['porcentaje'] ?? 0);
        }

        // Permitir un pequeño margen de error por redondeo
        return abs($totalPorcentaje - 100) < 0.01;
    }

    /**
     * Valida los datos de una distribución
     * 
     * @param array $data
     * @return array Errores encontrados
     */
    public static function validar($data)
    {
        $errores = [];

        // Validar cuenta contable
        if (!isset($data['cuenta_contable_id']) || empty($data['cuenta_contable_id'])) {
            $errores[] = 'La cuenta contable es requerida';
        }

        // Validar centro de costo
        if (!isset($data['centro_costo_id']) || empty($data['centro_costo_id'])) {
            $errores[] = 'El centro de costo es requerido';
        }

        // Validar ubicación
        if (!isset($data['ubicacion_id']) || empty($data['ubicacion_id'])) {
            $errores[] = 'La ubicación es requerida';
        }

        // Validar porcentaje
        if (!isset($data['porcentaje']) || $data['porcentaje'] <= 0 || $data['porcentaje'] > 100) {
            $errores[] = 'El porcentaje debe estar entre 0 y 100';
        }

        // Validar cantidad
        if (!isset($data['cantidad']) || $data['cantidad'] <= 0) {
            $errores[] = 'La cantidad debe ser mayor a 0';
        }

        return $errores;
    }

    /**
     * Crea múltiples distribuciones para una orden
     * 
     * @param int $ordenCompraId
     * @param array $distribuciones
     * @return bool
     */
    public static function crearMultiples($ordenCompraId, $distribuciones)
    {
        try {
            $conn = self::getConnection();
            $conn->beginTransaction();

            // Validar que los porcentajes sumen 100%
            if (!self::validarPorcentajes($distribuciones)) {
                throw new \Exception('Los porcentajes deben sumar 100%');
            }

            // Crear cada distribución
            foreach ($distribuciones as $dist) {
                $dist['orden_compra_id'] = $ordenCompraId;
                
                $errores = self::validar($dist);
                if (!empty($errores)) {
                    throw new \Exception(implode(', ', $errores));
                }

                self::create($dist);
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            error_log("Error creando distribuciones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza las distribuciones de una orden
     * 
     * @param int $ordenCompraId
     * @param array $distribuciones
     * @return bool
     */
    public static function actualizarMultiples($ordenCompraId, $distribuciones)
    {
        try {
            $conn = self::getConnection();
            $conn->beginTransaction();

            // Eliminar distribuciones existentes
            $instance = new static();
            $sql = "DELETE FROM {$instance->table} WHERE orden_compra_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$ordenCompraId]);

            // Crear nuevas distribuciones
            $result = self::crearMultiples($ordenCompraId, $distribuciones);
            
            if (!$result) {
                throw new \Exception('Error al crear nuevas distribuciones');
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            error_log("Error actualizando distribuciones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcula el total de distribución por centro de costo
     * 
     * @param int $ordenCompraId
     * @param int $centroCostoId
     * @return float
     */
    public static function getTotalPorCentroCosto($ordenCompraId, $centroCostoId)
    {
        $instance = new static();
        
        $sql = "SELECT SUM(cantidad) as total
                FROM {$instance->table}
                WHERE orden_compra_id = ? AND centro_costo_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId, $centroCostoId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return floatval($result['total'] ?? 0);
    }

    /**
     * Obtiene el monto formateado
     * 
     * @param string $moneda
     * @return string
     */
    public function getCantidadFormateada($moneda = 'GTQ')
    {
        $simbolo = $moneda === 'USD' ? '$' : 'Q';
        $cantidad = number_format($this->attributes['cantidad'] ?? 0, 2);
        
        return $simbolo . ' ' . $cantidad;
    }

    /**
     * Obtiene estadísticas de distribución por orden
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function getEstadisticas($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT 
                    COUNT(*) as total_distribuciones,
                    COUNT(DISTINCT centro_costo_id) as centros_costo_distintos,
                    SUM(porcentaje) as suma_porcentajes,
                    SUM(cantidad) as monto_total
                FROM {$instance->table}
                WHERE orden_compra_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_distribuciones' => 0,
            'centros_costo_distintos' => 0,
            'suma_porcentajes' => 0,
            'monto_total' => 0
        ];
    }
}
