<?php
/**
 * Modelo DetalleItem
 * 
 * Representa los items/productos individuales de una orden de compra.
 * Cada requisición puede tener múltiples items con cantidad, precio y descripción.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class DetalleItem extends Model
{
    protected static $table = 'detalle_items';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'orden_compra_id',
        'cantidad',
        'descripcion',
        'precio_unitario',
        'total',
    ];

    protected static $guarded = ['id'];

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
     * Calcula el total del item (cantidad * precio_unitario)
     * 
     * @return float
     */
    public function calcularTotal()
    {
        if (!isset($this->attributes['cantidad']) || !isset($this->attributes['precio_unitario'])) {
            return 0;
        }

        return floatval($this->attributes['cantidad']) * floatval($this->attributes['precio_unitario']);
    }

    /**
     * Actualiza el total del item
     * 
     * @return bool
     */
    public function actualizarTotal()
    {
        $total = $this->calcularTotal();
        return self::update($this->attributes['id'], ['total' => $total]);
    }

    /**
     * Obtiene todos los items de una orden de compra
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function porOrdenCompra($ordenCompraId)
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE orden_compra_id = ? 
                ORDER BY id ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Calcula el total de todos los items de una orden
     * 
     * @param int $ordenCompraId
     * @return float
     */
    public static function calcularTotalOrden($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT SUM(total) as total_orden 
                FROM {$instance->table} 
                WHERE orden_compra_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return floatval($result['total_orden'] ?? 0);
    }

    /**
     * Valida que los datos del item sean correctos
     * 
     * @param array $data
     * @return array Errores encontrados (vacío si no hay errores)
     */
    public static function validar($data)
    {
        $errores = [];

        // Validar cantidad
        if (!isset($data['cantidad']) || $data['cantidad'] <= 0) {
            $errores[] = 'La cantidad debe ser mayor a 0';
        }

        // Validar descripción
        if (!isset($data['descripcion']) || trim($data['descripcion']) === '') {
            $errores[] = 'La descripción es requerida';
        }

        // Validar precio unitario
        if (!isset($data['precio_unitario']) || $data['precio_unitario'] <= 0) {
            $errores[] = 'El precio unitario debe ser mayor a 0';
        }

        return $errores;
    }

    /**
     * Crea un item con validación
     * 
     * @param array $data
     * @return int|array ID del item creado o array de errores
     */
    public static function crearConValidacion($data)
    {
        $errores = self::validar($data);
        
        if (!empty($errores)) {
            return ['errores' => $errores];
        }

        // Calcular el total
        $data['total'] = floatval($data['cantidad']) * floatval($data['precio_unitario']);

        return self::create($data);
    }

    /**
     * Actualiza múltiples items de una orden
     * 
     * @param int $ordenCompraId
     * @param array $items Array de items con sus datos
     * @return bool
     */
    public static function actualizarMultiples($ordenCompraId, $items)
    {
        try {
            $conn = self::getConnection();
            $conn->beginTransaction();

            // Eliminar items existentes
            $instance = new static();
            $sql = "DELETE FROM {$instance->table} WHERE orden_compra_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$ordenCompraId]);

            // Insertar nuevos items
            foreach ($items as $item) {
                $item['orden_compra_id'] = $ordenCompraId;
                $item['total'] = floatval($item['cantidad']) * floatval($item['precio_unitario']);
                self::create($item);
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            error_log("Error actualizando items: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el precio unitario formateado
     * 
     * @param string $moneda
     * @return string
     */
    public function getPrecioFormateado($moneda = 'GTQ')
    {
        $simbolo = $moneda === 'USD' ? '$' : 'Q';
        $precio = number_format($this->attributes['precio_unitario'] ?? 0, 2);
        
        return $simbolo . ' ' . $precio;
    }

    /**
     * Obtiene el total formateado
     * 
     * @param string $moneda
     * @return string
     */
    public function getTotalFormateado($moneda = 'GTQ')
    {
        $simbolo = $moneda === 'USD' ? '$' : 'Q';
        $total = number_format($this->attributes['total'] ?? 0, 2);
        
        return $simbolo . ' ' . $total;
    }

    /**
     * Obtiene estadísticas de items por orden
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function getEstadisticas($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(cantidad) as cantidad_total,
                    SUM(total) as monto_total,
                    AVG(precio_unitario) as precio_promedio
                FROM {$instance->table} 
                WHERE orden_compra_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_items' => 0,
            'cantidad_total' => 0,
            'monto_total' => 0,
            'precio_promedio' => 0
        ];
    }
}
