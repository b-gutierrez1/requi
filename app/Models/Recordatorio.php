<?php
/**
 * Modelo Recordatorio
 * 
 * Gestiona los recordatorios automáticos para autorizaciones pendientes.
 * Envía notificaciones periódicas a los autorizadores.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class Recordatorio extends Model
{
    protected static $table = 'recordatorios';
    protected static $primaryKey = 'id';
    protected static $timestamps = true;
    protected static $timestampFields = ['fecha_creacion', 'fecha_enviado'];

    protected static $fillable = [
        'orden_compra_id',
        'destinatario_email',
        'tipo_recordatorio',
        'mensaje',
        'estado',
        'fecha_enviado',
        'intentos',
    ];

    protected static $guarded = ['id', 'fecha_creacion'];

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
     * Obtiene recordatorios pendientes de enviar
     * 
     * @return array
     */
    public static function pendientes()
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table} 
                WHERE estado = 'pendiente' 
                AND intentos < 3 
                ORDER BY fecha_creacion ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene recordatorios por orden de compra
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function porOrdenCompra($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table} 
                WHERE orden_compra_id = ? 
                ORDER BY fecha_creacion DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Crea recordatorios para una orden pendiente
     * 
     * @param int $ordenCompraId
     * @return bool
     */
    public static function crearParaOrden($ordenCompraId)
    {
        try {
            $orden = OrdenCompra::find($ordenCompraId);
            if (!$orden) {
                return false;
            }

            $flujo = $orden->autorizacionFlujo();
            if (!$flujo) {
                return false;
            }

            // Obtener autorizadores pendientes
            $autorizadores = [];

            if ($flujo['estado'] === 'pendiente_revision') {
                // Obtener revisores
                $sql = "SELECT azure_email FROM usuarios WHERE es_revisor = 1";
                $stmt = self::getConnection()->prepare($sql);
                $stmt->execute();
                $autorizadores = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            } elseif ($flujo['estado'] === 'pendiente_autorizacion') {
                // Obtener autorizadores de centros de costo
                $sql = "SELECT DISTINCT autorizador_email 
                        FROM autorizacion_centro_costo 
                        WHERE autorizacion_flujo_id = ? 
                        AND estado = 'pendiente'";
                $stmt = self::getConnection()->prepare($sql);
                $stmt->execute([$flujo['id']]);
                $autorizadores = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            }

            // Crear recordatorios
            foreach ($autorizadores as $email) {
                self::create([
                    'orden_compra_id' => $ordenCompraId,
                    'destinatario_email' => $email,
                    'tipo_recordatorio' => $flujo['estado'],
                    'mensaje' => self::generarMensaje($orden, $flujo['estado']),
                    'estado' => 'pendiente',
                    'intentos' => 0,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error creando recordatorios: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera el mensaje del recordatorio
     * 
     * @param array $orden
     * @param string $tipoRecordatorio
     * @return string
     */
    private static function generarMensaje($orden, $tipoRecordatorio)
    {
        $numeroOrden = str_pad($orden['id'], 6, '0', STR_PAD_LEFT);
        
        if ($tipoRecordatorio === 'pendiente_revision') {
            return "Recordatorio: La requisición #{$numeroOrden} está pendiente de revisión.";
        }
        
        return "Recordatorio: La requisición #{$numeroOrden} está pendiente de su autorización.";
    }

    /**
     * Marca el recordatorio como enviado
     * 
     * @param int $id
     * @return bool
     */
    public static function marcarComoEnviado($id)
    {
        return self::update($id, [
            'estado' => 'enviado',
            'fecha_enviado' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Incrementa el contador de intentos
     * 
     * @param int $id
     * @return bool
     */
    public static function incrementarIntentos($id)
    {
        $recordatorio = self::find($id);
        if (!$recordatorio) {
            return false;
        }

        $intentos = ($recordatorio['intentos'] ?? 0) + 1;
        
        return self::update($id, [
            'intentos' => $intentos,
            'estado' => $intentos >= 3 ? 'fallido' : 'pendiente',
        ]);
    }

    /**
     * Cancela recordatorios de una orden
     * 
     * @param int $ordenCompraId
     * @return bool
     */
    public static function cancelarPorOrden($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "UPDATE {$instance->table} 
                SET estado = 'cancelado' 
                WHERE orden_compra_id = ? 
                AND estado = 'pendiente'";
        
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([$ordenCompraId]);
    }

    /**
     * Obtiene recordatorios que necesitan ser enviados
     * 
     * @param int $horasDesdeUltimo Horas desde el último recordatorio
     * @return array
     */
    public static function necesitanEnvio($horasDesdeUltimo = 24)
    {
        $instance = new static();
        
        $sql = "SELECT r.*, oc.id as orden_id, oc.nombre_razon_social
                FROM {$instance->table} r
                INNER JOIN orden_compra oc ON r.orden_compra_id = oc.id
                WHERE r.estado = 'pendiente'
                AND r.intentos < 3
                AND (
                    r.fecha_enviado IS NULL 
                    OR r.fecha_enviado < DATE_SUB(NOW(), INTERVAL ? HOUR)
                )
                ORDER BY r.fecha_creacion ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$horasDesdeUltimo]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas de recordatorios
     * 
     * @return array
     */
    public static function getEstadisticas()
    {
        $instance = new static();
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
                    SUM(CASE WHEN estado = 'fallido' THEN 1 ELSE 0 END) as fallidos,
                    SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
                FROM {$instance->table}";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total' => 0,
            'pendientes' => 0,
            'enviados' => 0,
            'fallidos' => 0,
            'cancelados' => 0
        ];
    }

    /**
     * Limpia recordatorios antiguos
     * 
     * @param int $dias Días de antigüedad
     * @return bool
     */
    public static function limpiarAntiguos($dias = 30)
    {
        $instance = new static();
        
        $sql = "DELETE FROM {$instance->table} 
                WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND estado IN ('enviado', 'cancelado', 'fallido')";
        
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([$dias]);
    }
}
