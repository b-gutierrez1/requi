<?php
/**
 * Modelo HistorialRequisicion
 * 
 * Registra todos los eventos y cambios de una requisición.
 * Proporciona trazabilidad completa del proceso.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class HistorialRequisicion extends Model
{
    protected static $table = 'historial_requisiciones';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'requisicion_id',
        'accion',              // Columna real en BD (antes tipo_evento)
        'usuario_email',
        'descripcion',
        'fecha_cambio',        // Columna real en BD (antes fecha)
        'estado_anterior',
        'estado_nuevo',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene la requisición asociada
     * 
     * @return Requisicion|null
     */
    public function ordenCompra()
    {
        if (!isset($this->attributes['requisicion_id'])) {
            return null;
        }

        return Requisicion::find($this->attributes['requisicion_id']);
    }

    /**
     * Obtiene el usuario que realizó la acción
     * 
     * @return array|null
     */
    public function usuario()
    {
        if (!isset($this->attributes['usuario_id'])) {
            return null;
        }

        return Usuario::find($this->attributes['usuario_id']);
    }

    /**
     * Obtiene el historial completo de una orden
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function porRequisicion($ordenCompraId)
    {
        // Usar columnas reales de la BD
        $sql = "SELECT h.*, 
                       h.accion as tipo_evento,
                       h.fecha_cambio as fecha,
                       h.usuario_email as usuario_nombre
                FROM " . static::$table . " h
                WHERE h.requisicion_id = ?
                ORDER BY h.fecha_cambio DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Registra un evento en el historial
     * 
     * @param int $ordenCompraId
     * @param string $evento Tipo de evento
     * @param string $descripcion Descripción del evento
     * @param int $usuarioId ID del usuario (opcional)
     * @param array $datos Datos adicionales (opcional)
     * @return int|false
     */
    public static function registrar($ordenCompraId, $evento, $descripcion, $usuarioEmail = null, $datos = [])
    {
        try {
            return self::create([
                'requisicion_id' => $ordenCompraId,
                'accion' => $evento,                    // Columna correcta en BD
                'usuario_email' => $usuarioEmail,
                'descripcion' => $descripcion,
                'fecha_cambio' => date('Y-m-d H:i:s'),  // Columna correcta en BD
            ]);
        } catch (\Exception $e) {
            error_log("Error registrando historial: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un cambio de estado
     * 
     * @param int $ordenCompraId
     * @param string $estadoAnterior
     * @param string $estadoNuevo
     * @param int $usuarioId
     * @param string $comentario
     * @return int|false
     */
    public static function registrarCambioEstado($ordenCompraId, $estadoAnterior, $estadoNuevo, $usuarioId, $comentario = '')
    {
        $descripcion = "Estado cambiado de '{$estadoAnterior}' a '{$estadoNuevo}'";
        if ($comentario) {
            $descripcion .= ". Comentario: {$comentario}";
        }

        return self::create([
            'requisicion_id' => $ordenCompraId,
            'usuario_id' => $usuarioId,
            'evento' => 'cambio_estado',
            'descripcion' => $descripcion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'datos_adicionales' => json_encode(['comentario' => $comentario]),
        ]);
    }

    /**
     * Registra una aprobación
     * 
     * @param int $ordenCompraId
     * @param int $usuarioId
     * @param string $nivelAprobacion
     * @param string $comentario
     * @return int|false
     */
    public static function registrarAprobacion($ordenCompraId, $usuarioId, $nivelAprobacion, $comentario = '')
    {
        $descripcion = "Aprobado en nivel: {$nivelAprobacion}";
        if ($comentario) {
            $descripcion .= ". Comentario: {$comentario}";
        }

        return self::registrar(
            $ordenCompraId,
            'aprobacion',
            $descripcion,
            $usuarioId,
            ['nivel' => $nivelAprobacion, 'comentario' => $comentario]
        );
    }

    /**
     * Registra un rechazo
     * 
     * @param int $ordenCompraId
     * @param int $usuarioId
     * @param string $motivo
     * @return int|false
     */
    public static function registrarRechazo($ordenCompraId, $usuarioId, $motivo)
    {
        return self::registrar(
            $ordenCompraId,
            'rechazo',
            "Rechazado. Motivo: {$motivo}",
            $usuarioId,
            ['motivo' => $motivo]
        );
    }

    /**
     * Registra la creación de la requisición
     * 
     * @param int $ordenCompraId
     * @param int $usuarioId
     * @return int|false
     */
    public static function registrarCreacion($ordenCompraId, $usuarioId)
    {
        return self::registrar(
            $ordenCompraId,
            'creacion',
            'Requisición creada',
            $usuarioId
        );
    }

    /**
     * Registra una edición
     * 
     * @param int $ordenCompraId
     * @param int $usuarioId
     * @param array $cambios
     * @return int|false
     */
    public static function registrarEdicion($ordenCompraId, $usuarioId, $cambios = [])
    {
        $descripcion = 'Requisición editada';
        if (!empty($cambios)) {
            $descripcion .= '. Campos modificados: ' . implode(', ', array_keys($cambios));
        }

        return self::registrar(
            $ordenCompraId,
            'edicion',
            $descripcion,
            $usuarioId,
            $cambios
        );
    }

    /**
     * Obtiene eventos por tipo
     * 
     * @param int $ordenCompraId
     * @param string $tipoEvento
     * @return array
     */
    public static function porTipoEvento($ordenCompraId, $tipoEvento)
    {
        $instance = new static();
        
        $sql = "SELECT h.*, u.azure_display_name as usuario_nombre
                FROM {$instance->table} h
                LEFT JOIN usuarios u ON h.usuario_id = u.id
                WHERE h.requisicion_id = ? AND h.evento = ?
                ORDER BY h.fecha_cambio DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId, $tipoEvento]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el último evento de una orden
     * 
     * @param int $ordenCompraId
     * @return array|null
     */
    public static function ultimoEvento($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT h.*, u.azure_display_name as usuario_nombre
                FROM {$instance->table} h
                LEFT JOIN usuarios u ON h.usuario_id = u.id
                WHERE h.requisicion_id = ?
                ORDER BY h.fecha_cambio DESC
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Cuenta eventos por tipo
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function contarEventos($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT 
                    evento,
                    COUNT(*) as total
                FROM {$instance->table}
                WHERE requisicion_id = ?
                GROUP BY evento";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        $resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $conteo = [];
        foreach ($resultados as $row) {
            $conteo[$row['evento']] = $row['total'];
        }
        
        return $conteo;
    }

    /**
     * Obtiene el tiempo transcurrido desde la creación
     * 
     * @param int $ordenCompraId
     * @return int Días transcurridos
     */
    public static function diasDesdeCreacion($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT DATEDIFF(NOW(), MIN(fecha_cambio)) as dias
                FROM {$instance->table}
                WHERE requisicion_id = ? AND evento = 'creacion'";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['dias'] ?? 0;
    }

    /**
     * Obtiene el timeline completo de la requisición
     * 
     * @param int $ordenCompraId
     * @return array Timeline formateado para visualización
     */
    public static function getTimeline($ordenCompraId)
    {
        $historial = self::porRequisicion($ordenCompraId);
        
        $timeline = [];
        foreach ($historial as $evento) {
            $timeline[] = [
                'fecha' => $evento['fecha_cambio'],
                'evento' => $evento['evento'],
                'descripcion' => $evento['descripcion'],
                'usuario' => $evento['usuario_nombre'] ?? 'Sistema',
                'icono' => self::getIconoEvento($evento['evento']),
                'color' => self::getColorEvento($evento['evento']),
            ];
        }
        
        return $timeline;
    }

    /**
     * Obtiene el ícono para un tipo de evento
     * 
     * @param string $evento
     * @return string
     */
    private static function getIconoEvento($evento)
    {
        $iconos = [
            'creacion' => 'fa-plus-circle',
            'edicion' => 'fa-edit',
            'aprobacion' => 'fa-check-circle',
            'rechazo' => 'fa-times-circle',
            'cambio_estado' => 'fa-exchange-alt',
            'comentario' => 'fa-comment',
            'archivo' => 'fa-paperclip',
        ];
        
        return $iconos[$evento] ?? 'fa-circle';
    }

    /**
     * Obtiene el color para un tipo de evento
     * 
     * @param string $evento
     * @return string
     */
    private static function getColorEvento($evento)
    {
        $colores = [
            'creacion' => 'primary',
            'edicion' => 'info',
            'aprobacion' => 'success',
            'rechazo' => 'danger',
            'cambio_estado' => 'warning',
            'comentario' => 'secondary',
            'archivo' => 'info',
        ];
        
        return $colores[$evento] ?? 'secondary';
    }
}
