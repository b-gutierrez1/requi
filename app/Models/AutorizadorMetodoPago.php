<?php
/**
 * Modelo AutorizadorMetodoPago
 * 
 * Gestiona autorizadores especiales según la forma de pago.
 * Por ejemplo, tarjeta de crédito requiere autorización especial.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class AutorizadorMetodoPago extends Model
{
    protected static $table = 'autorizadores_metodos_pago';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'metodo_pago',
        'autorizador_email',
        'descripcion',
        'notificacion',
        'fecha_actualizacion',
        'actualizado_por',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene autorizador por forma de pago
     * 
     * @param string $formaPago
     * @return array|null
     */
    public static function porFormaPago($formaPago)
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE metodo_pago = ? 
                ORDER BY id ASC 
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$formaPago]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Verifica si una forma de pago requiere autorización especial
     * 
     * @param string $formaPago
     * @return bool
     */
    public static function requiereAutorizacionEspecial($formaPago)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM " . static::$table . " 
                WHERE metodo_pago = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$formaPago]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Obtiene todos los autorizadores activos
     * 
     * @return array
     */
    public static function todosActivos()
    {
        $sql = "SELECT * FROM " . static::$table . " 
                ORDER BY metodo_pago ASC, id ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene autorizaciones por email del autorizador
     * 
     * @param string $email
     * @return array
     */
    public static function porEmail($email)
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE autorizador_email = ? 
                ORDER BY metodo_pago ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un email es autorizador de una forma de pago
     * 
     * @param string $email
     * @param string $formaPago
     * @return bool
     */
    public static function esAutorizadorDe($email, $formaPago)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM " . static::$table . " 
                WHERE autorizador_email = ? 
                AND metodo_pago = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email, $formaPago]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Obtiene formas de pago que requieren autorización especial
     * 
     * @return array
     */
    public static function formasPagoConAutorizacion()
    {
        $sql = "SELECT DISTINCT metodo_pago FROM " . static::$table;
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Actualiza un autorizador
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function actualizar($id, $data)
    {
        $data['fecha_actualizacion'] = date('Y-m-d H:i:s');
        if (isset($_SESSION['user']['email'])) {
            $data['actualizado_por'] = $_SESSION['user']['email'];
        }
        return self::update($id, $data);
    }

    /**
     * Obtiene la descripción de la forma de pago
     * 
     * @param string $formaPago
     * @return string
     */
    public static function getDescripcionFormaPago($formaPago)
    {
        $formasPago = [
            'efectivo' => 'Efectivo',
            'cheque' => 'Cheque',
            'transferencia' => 'Transferencia Bancaria',
            'tarjeta_credito' => 'Tarjeta de Crédito',
            'tarjeta_debito' => 'Tarjeta de Débito',
        ];
        
        return $formasPago[$formaPago] ?? $formaPago;
    }

    /**
     * Obtiene autorizaciones pendientes para un autorizador
     * 
     * @param string $email
     * @return array
     */
    public static function autorizacionesPendientes($email)
    {
        $sql = "SELECT 
                    oc.*,
                    af.estado,
                    amp.metodo_pago,
                    amp.descripcion as motivo_autorizacion
                FROM orden_compra oc
                INNER JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
                INNER JOIN autorizadores_metodos_pago amp ON oc.forma_pago = amp.metodo_pago
                WHERE amp.autorizador_email = ?
                AND af.estado = 'pendiente_autorizacion_pago'
                ORDER BY oc.fecha DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta autorizaciones pendientes para un email
     * 
     * @param string $email
     * @return int
     */
    public static function contarPendientes($email)
    {
        $sql = "SELECT COUNT(*) as total
                FROM orden_compra oc
                INNER JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
                INNER JOIN autorizadores_metodos_pago amp ON oc.forma_pago = amp.metodo_pago
                WHERE amp.autorizador_email = ?
                AND af.estado = 'pendiente_autorizacion_pago'";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene estadísticas de autorizaciones por forma de pago
     * 
     * @param string $formaPago
     * @return array
     */
    public static function getEstadisticasFormaPago($formaPago)
    {
        $sql = "SELECT 
                    COUNT(*) as total_requisiciones,
                    SUM(CASE WHEN af.estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN af.estado = 'rechazado_autorizacion' THEN 1 ELSE 0 END) as rechazadas,
                    SUM(CASE WHEN af.estado = 'pendiente_autorizacion_pago' THEN 1 ELSE 0 END) as pendientes,
                    SUM(oc.monto_total) as monto_total
                FROM orden_compra oc
                INNER JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
                WHERE oc.forma_pago = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$formaPago]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_requisiciones' => 0,
            'autorizadas' => 0,
            'rechazadas' => 0,
            'pendientes' => 0,
            'monto_total' => 0
        ];
    }

    /**
     * Obtiene todas las configuraciones agrupadas por forma de pago
     * 
     * @return array
     */
    public static function todasConfiguraciones()
    {
        $sql = "SELECT 
                    metodo_pago,
                    descripcion,
                    GROUP_CONCAT(autorizador_email ORDER BY id) as autorizadores,
                    COUNT(*) as cantidad_autorizadores
                FROM " . static::$table . "
                GROUP BY metodo_pago, descripcion
                ORDER BY metodo_pago ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Valida si existe duplicado
     * 
     * @param string $formaPago
     * @param string $email
     * @param int $excluirId ID a excluir en la validación
     * @return bool
     */
    public static function existeDuplicado($formaPago, $email, $excluirId = null)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM " . static::$table . " 
                WHERE metodo_pago = ? 
                AND autorizador_email = ?";
        
        $params = [$formaPago, $email];
        
        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }
}
