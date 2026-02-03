<?php
/**
 * Modelo Factura
 * 
 * Representa las facturas asociadas a una orden de compra.
 * Permite dividir el pago en múltiples facturas con diferentes 
 * formas de pago y montos.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class Factura extends Model
{
    protected static $table = 'facturas';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    // Columnas de la tabla facturas en bd_prueba:
    // id, requisicion_id, numero_factura, fecha_factura, monto_factura, proveedor_nombre, archivo_factura, estado, created_at
    protected static $fillable = [
        'requisicion_id',
        'numero_factura',
        'fecha_factura',
        'monto_factura',
        'proveedor_nombre',
        'archivo_factura',
        'estado',
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
     * Obtiene todas las facturas de una orden de compra
     * 
     * @param int $ordenCompraId
     * @return array
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
     * Valida que el total de porcentajes sea 100%
     * 
     * @param array $facturas Array de facturas
     * @return bool
     */
    public static function validarPorcentajes($facturas)
    {
        $totalPorcentaje = 0;
        
        foreach ($facturas as $factura) {
            $totalPorcentaje += floatval($factura['porcentaje'] ?? 0);
        }

        // Permitir un pequeño margen de error por redondeo
        return abs($totalPorcentaje - 100) < 0.01;
    }

    /**
     * Valida los datos de una factura
     * 
     * @param array $data
     * @return array Errores encontrados
     */
    public static function validar($data)
    {
        $errores = [];

        // Validar forma de pago
        if (!isset($data['forma_pago']) || empty($data['forma_pago'])) {
            $errores[] = 'La forma de pago es requerida';
        }

        // Validar porcentaje
        if (!isset($data['porcentaje']) || $data['porcentaje'] <= 0 || $data['porcentaje'] > 100) {
            $errores[] = 'El porcentaje debe estar entre 0 y 100';
        }

        // Validar monto
        if (!isset($data['monto']) || $data['monto'] <= 0) {
            $errores[] = 'El monto debe ser mayor a 0';
        }

        return $errores;
    }

    /**
     * Crea múltiples facturas para una orden
     * 
     * @param int $ordenCompraId
     * @param array $facturas
     * @return bool
     */
    public static function crearMultiples($ordenCompraId, $facturas)
    {
        try {
            $conn = self::getConnection();
            $conn->beginTransaction();

            // Filtrar facturas con monto mayor a 0
            $facturasValidas = array_filter($facturas, function($f) {
                return isset($f['monto']) && floatval($f['monto']) > 0;
            });

            // Si no hay facturas válidas, no hacer nada
            if (empty($facturasValidas)) {
                $conn->commit();
                return true;
            }

            // Validar que los porcentajes sumen 100%
            if (!self::validarPorcentajes($facturasValidas)) {
                throw new \Exception('Los porcentajes de las facturas deben sumar 100%');
            }

            // Crear cada factura
            foreach ($facturasValidas as $factura) {
                $factura['requisicion_id'] = $ordenCompraId;
                
                $errores = self::validar($factura);
                if (!empty($errores)) {
                    throw new \Exception(implode(', ', $errores));
                }

                self::create($factura);
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            error_log("Error creando facturas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza las facturas de una orden
     * 
     * @param int $ordenCompraId
     * @param array $facturas
     * @return bool
     */
    public static function actualizarMultiples($ordenCompraId, $facturas)
    {
        try {
            $conn = self::getConnection();
            $conn->beginTransaction();

            // Eliminar facturas existentes
            $instance = new static();
            $sql = "DELETE FROM {$instance->table} WHERE requisicion_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$ordenCompraId]);

            // Crear nuevas facturas
            $result = self::crearMultiples($ordenCompraId, $facturas);
            
            if (!$result) {
                throw new \Exception('Error al crear nuevas facturas');
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            if (isset($conn)) {
                $conn->rollBack();
            }
            error_log("Error actualizando facturas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcula el total de todas las facturas de una orden
     * 
     * @param int $ordenCompraId
     * @return float
     */
    public static function calcularTotalOrden($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT SUM(monto) as total 
                FROM {$instance->table} 
                WHERE requisicion_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return floatval($result['total'] ?? 0);
    }

    /**
     * Obtiene facturas por forma de pago
     * 
     * @param int $ordenCompraId
     * @param string $formaPago
     * @return array
     */
    public static function porFormaPago($ordenCompraId, $formaPago)
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table} 
                WHERE requisicion_id = ? AND forma_pago = ?
                ORDER BY id ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId, $formaPago]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Marca una factura como pagada
     * 
     * @param int $id
     * @param string $fechaPago
     * @return bool
     */
    public static function marcarComoPagada($id, $fechaPago = null)
    {
        if (!$fechaPago) {
            $fechaPago = date('Y-m-d');
        }

        return self::update($id, [
            'estado' => 'pagada',
            'fecha_pago' => $fechaPago
        ]);
    }

    /**
     * Marca una factura como pendiente
     * 
     * @param int $id
     * @return bool
     */
    public static function marcarComoPendiente($id)
    {
        return self::update($id, [
            'estado' => 'pendiente',
            'fecha_pago' => null
        ]);
    }

    /**
     * Obtiene el monto formateado
     * 
     * @param string $moneda
     * @return string
     */
    public function getMontoFormateado($moneda = 'GTQ')
    {
        $simbolo = $moneda === 'USD' ? '$' : 'Q';
        $monto = number_format($this->attributes['monto'] ?? 0, 5);
        
        return $simbolo . ' ' . $monto;
    }

    /**
     * Obtiene la descripción de la forma de pago
     * 
     * @return string
     */
    public function getFormaPagoDescripcion()
    {
        $formaPago = $this->attributes['forma_pago'] ?? '';
        
        // Usar la función helper si está disponible
        if (function_exists('getFormaPagoLabel')) {
            return getFormaPagoLabel($formaPago);
        }
        
        // Fallback a valores antiguos si la función no existe
        $formasPago = [
            'efectivo' => 'Efectivo',
            'cheque' => 'Cheque',
            'transferencia' => 'Transferencia Bancaria',
            'tarjeta_credito' => 'Tarjeta de Crédito',
            'tarjeta_debito' => 'Tarjeta de Débito',
            'contado' => 'Contado',
            'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito (Lic. Milton)',
            'credito' => 'Crédito',
        ];

        return $formasPago[$formaPago] ?? $formaPago;
    }

    /**
     * Verifica si la factura está pagada
     * 
     * @return bool
     */
    public function estaPagada()
    {
        return isset($this->attributes['estado']) && 
               $this->attributes['estado'] === 'pagada';
    }

    /**
     * Obtiene el badge de estado
     * 
     * @return array
     */
    public function getEstadoBadge()
    {
        $estado = $this->attributes['estado'] ?? 'pendiente';
        
        $badges = [
            'pendiente' => ['class' => 'badge-warning', 'text' => 'Pendiente'],
            'pagada' => ['class' => 'badge-success', 'text' => 'Pagada'],
            'cancelada' => ['class' => 'badge-danger', 'text' => 'Cancelada'],
        ];

        return $badges[$estado] ?? $badges['pendiente'];
    }

    /**
     * Obtiene estadísticas de facturas por orden
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function getEstadisticas($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT 
                    COUNT(*) as total_facturas,
                    SUM(monto) as monto_total,
                    SUM(CASE WHEN estado = 'pagada' THEN monto ELSE 0 END) as monto_pagado,
                    SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as monto_pendiente,
                    SUM(porcentaje) as suma_porcentajes
                FROM {$instance->table}
                WHERE requisicion_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_facturas' => 0,
            'monto_total' => 0,
            'monto_pagado' => 0,
            'monto_pendiente' => 0,
            'suma_porcentajes' => 0
        ];
    }
}
