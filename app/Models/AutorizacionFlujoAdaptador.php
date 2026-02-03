<?php

namespace App\Models;

/**
 * Adaptador para AutorizacionFlujo que funciona con el nuevo esquema v3.0
 * 
 * Este adaptador mantiene la compatibilidad con el código existente
 * mientras usa las nuevas tablas del esquema v3.0
 */
class AutorizacionFlujoAdaptador extends Model
{
    protected static $table = 'autorizacion_flujo'; // Tabla original (mantener para no romper)

    /**
     * Busca flujo por requisición
     */
    public static function porRequisicion($requisicionId)
    {
        return self::porOrdenCompra($requisicionId);
    }

    /**
     * Busca flujo por orden de compra (adaptado al nuevo esquema)
     * @deprecated Usar porRequisicion() en su lugar
     */
    public static function porOrdenCompra($ordenCompraId)
    {
        try {
            $pdo = static::getConnection();
            
            // Buscar directamente en la tabla autorizacion_flujo (que es la correcta)
            $stmt = $pdo->prepare("SELECT * FROM autorizacion_flujo WHERE requisicion_id = ? LIMIT 1");
            $stmt->execute([$ordenCompraId]);
            $flujo = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($flujo) {
                // Devolver el flujo tal como está, con sus IDs correctos
                return $flujo;
            }
            
            // Si no existe flujo, retornar null
            return null;
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionFlujoAdaptador::porOrdenCompra: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca por estado (adaptado)
     */
    public static function porEstado($estado, $limite = 20)
    {
        try {
            $pdo = static::getConnection();
            
            // Mapear estado viejo a nuevo
            $estadoNuevo = self::mapearEstadoViejoANuevo($estado);
            
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    id as requisicion_id,
                    estado,
                    prioridad,
                    monto_total,
                    created_at as fecha_creacion,
                    fecha_limite,
                    fecha_completada as fecha_completado
                FROM requisiciones 
                WHERE estado = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$estadoNuevo, $limite]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionFlujoAdaptador::porEstado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Aprueba revisión (adaptado)
     */
    public static function aprobarRevision($flujoId, $usuarioId, $comentario)
    {
        try {
            $pdo = static::getConnection();
            
            // Nota: Ya no actualizamos estado en requisiciones - se maneja solo desde flujo
            $resultado = true;
            
            if ($resultado) {
                // Crear autorización de revisión como aprobada
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO autorizaciones 
                    (requisicion_id, tipo, autorizador_email, estado, fecha_respuesta, comentarios)
                    VALUES (?, 'revision', 'revisor@sistema.com', 'aprobada', NOW(), ?)
                ");
                $stmt->execute([$flujoId, $comentario]);
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionFlujoAdaptador::aprobarRevision: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rechaza revisión (adaptado)
     */
    public static function rechazarRevision($flujoId, $usuarioId, $motivo)
    {
        try {
            $pdo = static::getConnection();
            
            // Nota: Ya no actualizamos estado en requisiciones - se maneja solo desde flujo
            $resultado = true;
            
            if ($resultado) {
                // Crear autorización de revisión como rechazada
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO autorizaciones 
                    (requisicion_id, tipo, autorizador_email, estado, fecha_respuesta, motivo_rechazo)
                    VALUES (?, 'revision', 'revisor@sistema.com', 'rechazada', NOW(), ?)
                ");
                $stmt->execute([$flujoId, $motivo]);
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionFlujoAdaptador::rechazarRevision: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mapea estado del nuevo esquema al viejo para compatibilidad
     */
    private static function mapearEstadoNuevoAViejo($estadoNuevo)
    {
        $mapeo = [
            'borrador' => 'pendiente_revision',
            'pendiente_revision' => 'pendiente_revision', 
            'pendiente_autorizacion' => 'pendiente_autorizacion',
            'autorizada' => 'autorizado',
            'rechazada' => 'rechazado'
        ];
        
        return $mapeo[$estadoNuevo] ?? $estadoNuevo;
    }

    /**
     * Mapea estado del viejo esquema al nuevo
     */
    private static function mapearEstadoViejoANuevo($estadoViejo)
    {
        $mapeo = [
            'pendiente_revision' => 'pendiente_revision',
            'pendiente_autorizacion' => 'pendiente_autorizacion',
            'autorizado' => 'autorizada',
            'rechazado' => 'rechazada',
            'rechazado_revision' => 'rechazada'
        ];
        
        return $mapeo[$estadoViejo] ?? $estadoViejo;
    }

    /**
     * Obtiene resumen completo (método de compatibilidad)
     */
    public static function getResumenCompleto($flujoId)
    {
        try {
            $pdo = static::getConnection();
            
            $stmt = $pdo->prepare("
                SELECT 
                    r.*,
                    COUNT(a.id) as total_autorizaciones,
                    SUM(CASE WHEN a.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN a.estado = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN a.estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
                FROM requisiciones r
                LEFT JOIN autorizaciones a ON r.id = a.requisicion_id
                WHERE r.id = ?
                GROUP BY r.id
            ");
            $stmt->execute([$flujoId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error en getResumenCompleto: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene historial de cambios (método de compatibilidad)
     */
    public static function getHistorialCambios($flujoId)
    {
        try {
            $pdo = static::getConnection();
            
            // Intentar primero con la nueva tabla
            $stmt = $pdo->prepare("
                SELECT 
                    accion as evento,
                    descripcion,
                    fecha_cambio,
                    usuario_email,
                    estado_anterior,
                    estado_nuevo
                FROM historial_requisiciones 
                WHERE requisicion_id = ?
                ORDER BY fecha_cambio DESC
                LIMIT 20
            ");
            $stmt->execute([$flujoId]);
            $historial = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Si no hay datos, intentar con tabla vieja
            if (empty($historial)) {
                $stmt = $pdo->prepare("
                    SELECT 
                        tipo_evento as evento,
                        descripcion,
                        fecha as fecha_cambio,
                        usuario_email,
                        '' as estado_anterior,
                        '' as estado_nuevo
                    FROM historial_requisiciones 
                    WHERE requisicion_id = ?
                    ORDER BY fecha DESC
                    LIMIT 20
                ");
                $stmt->execute([$flujoId]);
                $historial = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            
            return $historial;
            
        } catch (\Exception $e) {
            error_log("Error en getHistorialCambios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Establece prioridad (método de compatibilidad)
     */
    public static function establecerPrioridad($flujoId, $prioridad = null, $montoTotal = null)
    {
        try {
            if (!$prioridad) {
                // Determinar prioridad automáticamente basada en monto
                if ($montoTotal > 50000) {
                    $prioridad = 'alta';
                } elseif ($montoTotal > 20000) {
                    $prioridad = 'normal';
                } else {
                    $prioridad = 'baja';
                }
            }
            
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("UPDATE requisiciones SET prioridad = ? WHERE id = ?");
            return $stmt->execute([$prioridad, $flujoId]);
            
        } catch (\Exception $e) {
            error_log("Error en establecerPrioridad: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Establece fecha límite (método de compatibilidad)
     */
    public static function establecerFechaLimite($flujoId, $prioridad = 'normal')
    {
        try {
            $dias = [
                'urgente' => 1,
                'alta' => 3,
                'normal' => 7,
                'baja' => 14
            ];
            
            $diasLimite = $dias[$prioridad] ?? 7;
            $fechaLimite = date('Y-m-d', strtotime("+{$diasLimite} days"));
            
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("UPDATE requisiciones SET fecha_limite = ? WHERE id = ?");
            return $stmt->execute([$fechaLimite, $flujoId]);
            
        } catch (\Exception $e) {
            error_log("Error en establecerFechaLimite: " . $e->getMessage());
            return false;
        }
    }
}