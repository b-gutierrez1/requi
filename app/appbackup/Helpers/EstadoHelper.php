<?php
/**
 * Helper para manejo centralizado de estados de requisiciones
 * 
 * ESTE ES EL ÚNICO LUGAR DONDE SE DEBE CONSULTAR EL ESTADO
 * DE UNA REQUISICIÓN EN TODO EL SISTEMA
 * 
 * @package RequisicionesMVC\Helpers
 * @version 2.0
 */

namespace App\Helpers;

use App\Models\Requisicion;
use App\Models\AutorizacionFlujo;

class EstadoHelper
{
    /**
     * Obtiene el estado real de una requisición por su ID
     * MÉTODO PRINCIPAL - USAR ESTE SIEMPRE
     * 
     * @param int $ordenCompraId
     * @return string
     */
    public static function getEstado($ordenCompraId)
    {
        // Verificar que la requisición existe
        $orden = Requisicion::find($ordenCompraId);
        if (!$orden) {
            return 'no_encontrado';
        }
        
        // Obtener el flujo de autorización
        $flujo = AutorizacionFlujo::porRequisicion($ordenCompraId);
        if (!$flujo) {
            // Si no hay flujo, está en borrador
            return 'borrador';
        }
        
        // Mapear el estado del flujo al estado de la requisición
        $estadoFlujo = is_object($flujo) ? $flujo->estado : $flujo['estado'];
        return self::mapearEstadoFlujo($estadoFlujo);
    }
    
    /**
     * Obtiene el estado desde un array de datos de requisición
     * Para usar en vistas que ya tienen los datos cargados
     * 
     * @param array $requisicionData
     * @return string
     */
    public static function getEstadoFromData($requisicionData)
    {
        // Si tiene flujo asociado, usar ese estado
        if (isset($requisicionData['flujo'])) {
            $flujo = $requisicionData['flujo'];
            $estadoFlujo = is_object($flujo) ? $flujo->estado : $flujo['estado'];
            
            return self::mapearEstadoFlujo($estadoFlujo);
        }
        
        // Si no hay flujo, está en borrador
        return 'borrador';
    }
    
    /**
     * Mapea el estado del flujo al estado final de la requisición
     * 
     * @param string $estadoFlujo
     * @return string
     */
    public static function mapearEstadoFlujo($estadoFlujo)
    {
        switch ($estadoFlujo) {
            case 'pendiente_revision':
                return 'pendiente_revision';
            case 'rechazado_revision':
                return 'rechazado';
            // Todos los estados de autorización pendiente se mapean a pendiente_autorizacion
            case 'pendiente_autorizacion_pago':
            case 'pendiente_autorizacion_cuenta':
            case 'pendiente_autorizacion_centros':
            case 'pendiente_autorizacion':
                return 'pendiente_autorizacion';
            case 'rechazado_autorizacion':
            case 'rechazado':
                return 'rechazado';
            case 'autorizado':
                return 'autorizado';
            default:
                return 'borrador';
        }
    }
    
    /**
     * Obtiene el badge para mostrar en las vistas
     * 
     * @param string $estado
     * @return array
     */
    public static function getBadge($estado)
    {
        $badges = [
            'borrador' => ['class' => 'badge-secondary', 'text' => 'Borrador'],
            'pendiente_revision' => ['class' => 'badge-warning', 'text' => 'Pendiente Revisión'],
            'pendiente_autorizacion' => ['class' => 'badge-info', 'text' => 'Pendiente Autorización'],
            'autorizado' => ['class' => 'badge-success', 'text' => 'Autorizado'],
            'rechazado' => ['class' => 'badge-danger', 'text' => 'Rechazado'],
            'no_encontrado' => ['class' => 'badge-dark', 'text' => 'No Encontrado'],
        ];
        
        return $badges[$estado] ?? $badges['borrador'];
    }
    
    /**
     * Verifica si un estado es "pendiente" (cualquier tipo)
     * 
     * @param string $estado
     * @return bool
     */
    public static function estaPendiente($estado)
    {
        return in_array($estado, ['pendiente_revision', 'pendiente_autorizacion']);
    }
    
    /**
     * Verifica si un estado está "completo" (autorizado o rechazado)
     * 
     * @param string $estado
     * @return bool
     */
    public static function estaCompleto($estado)
    {
        return in_array($estado, ['autorizado', 'rechazado']);
    }
    
    /**
     * Obtiene el texto descriptivo del estado
     * 
     * @param string $estado
     * @return string
     */
    public static function getTexto($estado)
    {
        $textos = [
            'borrador' => 'En edición',
            'pendiente_revision' => 'Esperando revisión',
            'pendiente_autorizacion' => 'Esperando autorización',
            'autorizado' => 'Completamente autorizado',
            'rechazado' => 'Rechazado en el proceso',
            'no_encontrado' => 'Requisición no encontrada',
        ];
        
        return $textos[$estado] ?? 'Estado desconocido';
    }
    
    /**
     * MÉTODO DE MIGRACIÓN: Corrige estados inconsistentes
     * Ejecutar UNA SOLA VEZ para limpiar el sistema
     * 
     * @return array Estadísticas de la corrección
     */
    public static function corregirEstadosInconsistentes()
    {
        $stats = [
            'total_revisados' => 0,
            'corregidos' => 0,
            'sin_cambios' => 0,
            'errores' => 0
        ];
        
        try {
            // Obtener todas las requisiciones
            $ordenes = Requisicion::all();
            
            foreach ($ordenes as $orden) {
                $stats['total_revisados']++;
                
                $estadoActualBD = $orden->estado ?? 'borrador';
                $estadoRealCalculado = $orden->getEstadoReal();
                
                if ($estadoActualBD !== $estadoRealCalculado) {
                    // Estado inconsistente - corregir
                    try {
                        Requisicion::update($orden->id, ['estado' => $estadoRealCalculado]);
                        $stats['corregidos']++;
                        error_log("Estado corregido para requisición {$orden->id}: '$estadoActualBD' → '$estadoRealCalculado'");
                    } catch (\Exception $e) {
                        $stats['errores']++;
                        error_log("Error corrigiendo requisición {$orden->id}: " . $e->getMessage());
                    }
                } else {
                    $stats['sin_cambios']++;
                }
            }
            
        } catch (\Exception $e) {
            error_log("Error en corrección masiva: " . $e->getMessage());
            $stats['errores']++;
        }
        
        return $stats;
    }
}