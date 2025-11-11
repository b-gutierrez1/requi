<?php

namespace App\Models;

/**
 * Adaptador para AutorizacionCentroCosto que funciona con el nuevo esquema v3.0
 */
class AutorizacionCentroCostoAdaptador extends Model
{
    /**
     * Obtiene autorizaciones pendientes por email del autorizador
     */
    public static function pendientesPorAutorizador($autorizadorEmail)
    {
        try {
            $pdo = static::getConnection();
            
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.requisicion_id as orden_id,
                    r.numero_requisicion,
                    r.proveedor_nombre as nombre_razon_social,
                    r.monto_total,
                    a.centro_costo_id,
                    cc.nombre as centro_nombre,
                    a.autorizador_email,
                    a.estado,
                    a.comentarios,
                    a.fecha_asignacion as fecha_creacion,
                    r.fecha_solicitud
                FROM autorizaciones a
                JOIN requisiciones r ON a.requisicion_id = r.id
                LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
                WHERE a.autorizador_email = ? 
                AND a.estado = 'pendiente'
                AND a.tipo = 'centro_costo'
                ORDER BY r.fecha_solicitud DESC
            ");
            $stmt->execute([$autorizadorEmail]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionCentroCostoAdaptador::pendientesPorAutorizador: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene autorizaciones por flujo (adaptado)
     */
    public static function porFlujo($flujoId)
    {
        try {
            $pdo = static::getConnection();
            
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.requisicion_id,
                    a.centro_costo_id,
                    a.autorizador_email,
                    a.estado,
                    a.comentarios,
                    cc.nombre as centro_nombre
                FROM autorizaciones a
                LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
                WHERE a.requisicion_id = ?
                AND a.tipo = 'centro_costo'
                ORDER BY a.id
            ");
            $stmt->execute([$flujoId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionCentroCostoAdaptador::porFlujo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca autorización por ID
     */
    public static function find($id)
    {
        try {
            $pdo = static::getConnection();
            
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.requisicion_id as autorizacion_flujo_id, -- Para compatibilidad con lógica anterior
                    a.requisicion_id,
                    a.centro_costo_id,
                    a.autorizador_email,
                    a.estado,
                    a.comentarios,
                    a.fecha_asignacion,
                    a.fecha_respuesta as fecha_autorizacion
                FROM autorizaciones a
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionCentroCostoAdaptador::find: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Autoriza un centro de costo
     */
    public static function autorizar($autorizacionId, $autorizadorEmail, $comentario = '')
    {
        try {
            $pdo = static::getConnection();
            
            $stmt = $pdo->prepare("
                UPDATE autorizaciones 
                SET estado = 'aprobada',
                    fecha_respuesta = NOW(),
                    comentarios = ?
                WHERE id = ? 
                AND autorizador_email = ?
                AND estado = 'pendiente'
            ");
            
            return $stmt->execute([$comentario, $autorizacionId, $autorizadorEmail]);
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionCentroCostoAdaptador::autorizar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rechaza un centro de costo
     */
    public static function rechazar($autorizacionId, $autorizadorEmail, $motivo)
    {
        try {
            $pdo = static::getConnection();
            
            $stmt = $pdo->prepare("
                UPDATE autorizaciones 
                SET estado = 'rechazada',
                    fecha_respuesta = NOW(),
                    motivo_rechazo = ?
                WHERE id = ? 
                AND autorizador_email = ?
                AND estado = 'pendiente'
            ");
            
            return $stmt->execute([$motivo, $autorizacionId, $autorizadorEmail]);
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionCentroCostoAdaptador::rechazar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene progreso de autorizaciones
     */
    public static function getProgreso($flujoId)
    {
        try {
            $pdo = static::getConnection();
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas
                FROM autorizaciones 
                WHERE requisicion_id = ?
            ");
            $stmt->execute([$flujoId]);
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($resultado && $resultado['total'] > 0) {
                $resultado['porcentaje_completado'] = (($resultado['aprobadas'] + $resultado['rechazadas']) / $resultado['total']) * 100;
            } else {
                $resultado['porcentaje_completado'] = 0;
            }
            
            return $resultado;
            
        } catch (\Exception $e) {
            error_log("Error en AutorizacionCentroCostoAdaptador::getProgreso: " . $e->getMessage());
            return null;
        }
    }
}