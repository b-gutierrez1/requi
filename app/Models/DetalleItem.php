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
    /**
     * Decimales de los IMPORTES (totales de item, monto de la requisicion).
     *
     * Son 2 porque es lo que se paga: GTQ/USD/EUR son monedas de 2 decimales
     * y las facturas se guardan con 2. Ver docs/PRECISION_DECIMAL.md
     */
    const DECIMALES_MONEDA = 2;

    /**
     * Decimales del PRECIO UNITARIO.
     *
     * Se admiten 3 para poder cotizar articulos cuyo precio por unidad no
     * cae en centavos exactos. El importe que resulta de multiplicarlo por
     * la cantidad si se redondea a 2, porque es el que se cobra.
     */
    const DECIMALES_PRECIO = 3;

    protected static $table = 'detalle_items';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'requisicion_id',
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
        if (!isset($this->attributes['requisicion_id'])) {
            return null;
        }

        return Requisicion::find($this->attributes['requisicion_id']);
    }

    /**
     * Normaliza la cantidad de un item.
     *
     * La columna `cantidad` es int(11): no se admiten cantidades
     * fraccionarias. Se normaliza aqui para que el total calculado en PHP
     * coincida exactamente con lo que termina guardado en la BD.
     *
     * @param mixed $cantidad
     * @return int
     */
    public static function normalizarCantidad($cantidad)
    {
        return (int) round(floatval($cantidad));
    }

    /**
     * Normaliza un importe (total de item, monto total) a 2 decimales.
     *
     * @param mixed $monto
     * @return float
     */
    public static function normalizarMonto($monto)
    {
        return round(floatval($monto), self::DECIMALES_MONEDA);
    }

    /**
     * Normaliza el precio unitario a 3 decimales.
     *
     * @param mixed $precio
     * @return float
     */
    public static function normalizarPrecio($precio)
    {
        return round(floatval($precio), self::DECIMALES_PRECIO);
    }

    /**
     * Calcula el total del item (cantidad * precio_unitario)
     *
     * El precio admite 3 decimales, pero el total se redondea a 2 porque es
     * el importe que se cobra y que termina en la factura.
     *
     * @return float
     */
    public function calcularTotal()
    {
        if (!isset($this->attributes['cantidad']) || !isset($this->attributes['precio_unitario'])) {
            return 0;
        }

        return self::normalizarMonto(
            self::normalizarCantidad($this->attributes['cantidad'])
            * self::normalizarPrecio($this->attributes['precio_unitario'])
        );
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
     * Obtiene todos los items de una requisición
     * 
     * @param int $requisicionId
     * @return array
     */
    public static function porRequisicion($requisicionId)
    {
        return self::porOrdenCompra($requisicionId);
    }

    /**
     * Obtiene todos los items de una orden de compra (alias legacy)
     * 
     * @param int $ordenCompraId
     * @return array
     * @deprecated Usar porRequisicion() en su lugar
     */
    public static function porOrdenCompra($ordenCompraId)
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE requisicion_id = ? 
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
                WHERE requisicion_id = ?";
        
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

        // Normalizar: cantidad entera, precio 3 decimales, total 2
        $data['cantidad'] = self::normalizarCantidad($data['cantidad']);
        $data['precio_unitario'] = self::normalizarPrecio($data['precio_unitario']);
        $data['total'] = self::normalizarMonto($data['cantidad'] * $data['precio_unitario']);

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
        $conn = null;
        $ownTransaction = false;
        try {
            $conn = self::getConnection();
            $ownTransaction = !$conn->inTransaction();
            if ($ownTransaction) {
                $conn->beginTransaction();
            }

            // Eliminar items existentes
            $sql = "DELETE FROM " . static::$table . " WHERE requisicion_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$ordenCompraId]);

            // Insertar nuevos items
            foreach ($items as $item) {
                $item['requisicion_id'] = $ordenCompraId;
                $item['cantidad'] = self::normalizarCantidad($item['cantidad'] ?? 0);
                $item['precio_unitario'] = self::normalizarPrecio($item['precio_unitario'] ?? 0);
                $item['total'] = self::normalizarMonto($item['cantidad'] * $item['precio_unitario']);
                self::create($item);
            }

            if ($ownTransaction) {
                $conn->commit();
            }
            return true;
        } catch (\Exception $e) {
            if ($ownTransaction && isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Error actualizando items: " . $e->getMessage());
            throw $e;
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
        $precio = number_format($this->attributes['precio_unitario'] ?? 0, self::DECIMALES_PRECIO);
        
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
        $total = number_format($this->attributes['total'] ?? 0, self::DECIMALES_MONEDA);
        
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
                WHERE requisicion_id = ?";
        
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
