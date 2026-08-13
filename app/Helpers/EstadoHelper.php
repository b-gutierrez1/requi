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
                // NO cae en 'borrador': un estado nuevo o no contemplado se
                // rotulaba como el estado MENOS avanzado, ocultando el
                // problema. Es preferible que se vea que no se reconoce.
                return 'desconocido';
        }
    }

    /**
     * Badge a partir del estado CRUDO del flujo, conservando el detalle.
     *
     * getBadge() trabaja sobre el estado resumido (5 valores) y pierde en
     * que etapa ocurrio cada cosa. Este metodo devuelve la etiqueta
     * especifica para que las vistas no tengan que reimplementar su propio
     * mapa: eso es justo lo que produjo que las variantes
     * pendiente_autorizacion_* salieran en gris durante meses.
     *
     * @param string|null $estadoFlujo Valor de autorizacion_flujo.estado
     * @return array{class:string,text:string,icon:string}
     */
    public static function getBadgeFlujo($estadoFlujo)
    {
        $badges = [
            'pendiente_revision'             => ['class' => 'bg-warning text-dark', 'text' => 'Pendiente de Revisión',        'icon' => 'clock'],
            'rechazado_revision'             => ['class' => 'bg-danger',            'text' => 'Rechazada en Revisión',        'icon' => 'times-circle'],
            'pendiente_autorizacion'         => ['class' => 'bg-info',              'text' => 'Pendiente de Autorización',    'icon' => 'hourglass-half'],
            'pendiente_autorizacion_centros' => ['class' => 'bg-info',              'text' => 'Pendiente de Autorización',    'icon' => 'hourglass-half'],
            'pendiente_autorizacion_pago'    => ['class' => 'bg-info',              'text' => 'Pendiente Autorización de Pago',   'icon' => 'hourglass-half'],
            'pendiente_autorizacion_cuenta'  => ['class' => 'bg-info',              'text' => 'Pendiente Autorización de Cuenta', 'icon' => 'hourglass-half'],
            'rechazado_autorizacion'         => ['class' => 'bg-danger',            'text' => 'Rechazada en Autorización',    'icon' => 'times-circle'],
            'rechazado'                      => ['class' => 'bg-danger',            'text' => 'Rechazada',                    'icon' => 'times-circle'],
            'autorizado'                     => ['class' => 'bg-success',           'text' => 'Autorizada',                   'icon' => 'check-circle'],
        ];

        if ($estadoFlujo === null || $estadoFlujo === '') {
            return ['class' => 'bg-secondary', 'text' => 'Borrador', 'icon' => 'file'];
        }

        return $badges[$estadoFlujo] ?? [
            'class' => 'bg-secondary',
            'text'  => ucfirst(str_replace('_', ' ', $estadoFlujo)),
            'icon'  => 'question-circle',
        ];
    }
    
    /**
     * Obtiene el badge para mostrar en las vistas
     * 
     * @param string $estado
     * @return array
     */
    public static function getBadge($estado)
    {
        // Clases de Bootstrap 5 (bg-*). Antes devolvia las de Bootstrap 4
        // (badge-*), que en la version 5 que carga el layout NO EXISTEN: el
        // badge salia gris sin color. Por eso cada vista termino haciendose
        // su propio mapa, y de ahi vino toda la divergencia de estados.
        $badges = [
            'borrador'               => ['class' => 'bg-secondary',        'text' => 'Borrador',                'icon' => 'file'],
            'pendiente_revision'     => ['class' => 'bg-warning text-dark','text' => 'Pendiente Revisión',      'icon' => 'clock'],
            'pendiente_autorizacion' => ['class' => 'bg-info',             'text' => 'Pendiente Autorización',  'icon' => 'hourglass-half'],
            'autorizado'             => ['class' => 'bg-success',          'text' => 'Autorizado',              'icon' => 'check-circle'],
            'rechazado'              => ['class' => 'bg-danger',           'text' => 'Rechazado',               'icon' => 'times-circle'],
            'no_encontrado'          => ['class' => 'bg-dark',             'text' => 'No Encontrado',           'icon' => 'ban'],
            'desconocido'            => ['class' => 'bg-secondary',        'text' => 'Estado desconocido',      'icon' => 'question-circle'],
        ];

        return $badges[$estado] ?? $badges['desconocido'];
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
            'desconocido' => 'Estado no reconocido por el sistema',
        ];

        return $textos[$estado] ?? 'Estado desconocido';
    }
    
}
