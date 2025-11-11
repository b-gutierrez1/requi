<?php
/**
 * NotificacionService
 * 
 * Servicio para gestionar todas las notificaciones del sistema.
 * Orquesta el envío de emails usando EmailService y gestiona recordatorios.
 * 
 * @package RequisicionesMVC\Services
 * @version 2.0
 */

namespace App\Services;

use App\Models\Usuario;
use App\Models\OrdenCompra;
use App\Models\AutorizacionFlujo;
use App\Models\Recordatorio;
use App\Models\CentroCosto;
use App\Models\CuentaContable;
use App\Models\DistribucionGasto;
use App\Models\AutorizadorMetodoPago;
use App\Models\AutorizadorCuentaContable;
use App\Helpers\Config;

class NotificacionService
{
    /**
     * Instancia del servicio de email
     * 
     * @var EmailService
     */
    private $emailService;

    /**
     * Skip emails en desarrollo
     * 
     * @var bool
     */
    private $skipEmails;

    /**
     * Tipos de notificación
     */
    const TIPO_NUEVA_REQUISICION = 'nueva_requisicion';
    const TIPO_PENDIENTE_REVISION = 'pendiente_revision';
    const TIPO_APROBADA_REVISION = 'aprobada_revision';
    const TIPO_RECHAZADA_REVISION = 'rechazada_revision';
    const TIPO_PENDIENTE_AUTORIZACION = 'pendiente_autorizacion';
    const TIPO_AUTORIZADA = 'autorizada';
    const TIPO_RECHAZADA = 'rechazada';
    const TIPO_RECORDATORIO = 'recordatorio';

    /**
     * Constructor
     * 
     * @param EmailService $emailService Servicio de email
     */
    public function __construct(EmailService $emailService = null)
    {
        $this->emailService = $emailService ?? new EmailService();
        
        // Modo desarrollo: deshabilitar envío de emails
        $this->skipEmails = true; // Temporal para desarrollo
    }

    /**
     * Envía email verificando si está habilitado
     * 
     * @param string $method Método del EmailService
     * @param array $params Parámetros
     * @return array
     */
    private function sendEmailSafe($method, ...$params)
    {
        if ($this->skipEmails) {
            error_log("NotificacionService: Email no enviado (skip mode)");
            error_log("Método: $method | Para: " . ($params[0] ?? 'N/A'));
            return [
                'success' => true,
                'message' => 'Email no enviado (desarrollo)',
                'code' => 'SKIPPED_NOTIFICATION'
            ];
        }
        
        return $this->emailService->$method(...$params);
    }

    /**
     * Notifica a los revisores sobre una nueva requisición
     * 
     * @param int $ordenId ID de la orden de compra
     * @return array Resultado
     */
    public function notificarNuevaRequisicion($ordenId)
    {
        try {
            $orden = OrdenCompra::find($ordenId);
            if (!$orden) {
                return [
                    'success' => false,
                    'error' => 'Orden de compra no encontrada',
                    'code' => 'ORDER_NOT_FOUND'
                ];
            }

            // Obtener revisores
            $revisores = $this->getRevisores();
            
            if (empty($revisores)) {
                error_log("No hay revisores configurados para notificar");
                return [
                    'success' => false,
                    'error' => 'No hay revisores configurados',
                    'code' => 'NO_REVIEWERS'
                ];
            }

            // Preparar datos para la plantilla
            $data = $this->formatearDatosOrden($orden);
            $data['accion_requerida'] = 'Revisión';
            $data['url_revision'] = $this->obtenerUrlAccion($ordenId, 'revision');

            $resultados = [];
            foreach ($revisores as $revisor) {
                $data['destinatario_nombre'] = $revisor['nombre'];
                
                $result = $this->sendEmailSafe(
                    'sendWithTemplate',
                    $revisor['email'],
                    "Nueva Requisición #{$data['numero_orden']} - Requiere Revisión",
                    'nueva_requisicion',
                    $data
                );

                $resultados[] = $result;
            }

            return [
                'success' => true,
                'message' => 'Notificaciones enviadas a revisores',
                'count' => count($revisores)
            ];
        } catch (\Exception $e) {
            error_log("Error notificando nueva requisición: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'NOTIFICATION_ERROR'
            ];
        }
    }

    /**
     * Notifica que una requisición fue aprobada en revisión
     * 
     * @param int $ordenId ID de la orden
     * @return array Resultado
     */
    public function notificarAprobacionRevision($ordenId)
    {
        try {
            $orden = OrdenCompra::find($ordenId);
            if (!$orden) {
                return ['success' => false, 'error' => 'Orden no encontrada'];
            }

            // Notificar al creador
            $usuarioId = is_object($orden) ? $orden->usuario_id : $orden['usuario_id'];
            $creador = Usuario::find($usuarioId);
            if (!$creador) {
                return ['success' => false, 'error' => 'Creador no encontrado'];
            }

            $data = $this->formatearDatosOrden($orden);
            $creadorNombre = is_object($creador) ? $creador->azure_display_name : $creador['azure_display_name'];
            $creadorEmail = is_object($creador) ? $creador->azure_email : $creador['azure_email'];
            
            // Determinar siguiente paso según el flujo
            $flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
            $estadoFlujo = $flujo ? (is_object($flujo) ? $flujo->estado : $flujo['estado']) : '';
            
            $data['destinatario_nombre'] = $creadorNombre;
            $data['nivel_aprobacion'] = 'Revisión';
            $data['aprobador_nombre'] = 'Revisor del Sistema';
            $data['fecha_aprobacion'] = date('d/m/Y H:i');
            $data['comentario'] = 'La requisición ha pasado la revisión inicial';
            $data['url_detalle'] = $this->obtenerUrlAccion($ordenId, 'detalle');

            // Personalizar mensaje según el siguiente paso
            switch ($estadoFlujo) {
                case AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_PAGO:
                    $data['estado_actual'] = 'Pendiente de Autorización de Forma de Pago';
                    $data['mensaje_siguiente_paso'] = 'Ahora requiere autorización especial por la forma de pago seleccionada.';
                    break;
                case AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_CUENTA:
                    $data['estado_actual'] = 'Pendiente de Autorización de Cuenta Contable';
                    $data['mensaje_siguiente_paso'] = 'Ahora requiere autorización especial por las cuentas contables utilizadas.';
                    break;
                case AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_CENTROS:
                    $data['estado_actual'] = 'Pendiente de Autorización por Centros';
                    $data['mensaje_siguiente_paso'] = 'Ahora está pendiente de autorización por los centros de costo correspondientes.';
                    break;
                default:
                    $data['estado_actual'] = 'Pendiente de Autorización';
                    $data['mensaje_siguiente_paso'] = 'Ahora está en proceso de autorización.';
            }

            $result = $this->sendEmailSafe(
                'sendWithTemplate',
                $creadorEmail,
                "Requisición #{$data['numero_orden']} Aprobada en Revisión",
                'aprobacion',
                $data
            );

            // Notificar al siguiente nivel según el estado
            if ($result['success']) {
                $this->notificarSiguienteNivel($ordenId, $estadoFlujo);
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Error notificando aprobación de revisión: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Notifica que una requisición fue rechazada en revisión
     * 
     * @param int $ordenId ID de la orden
     * @param string $motivo Motivo del rechazo
     * @return array Resultado
     */
    public function notificarRechazoRevision($ordenId, $motivo)
    {
        try {
            $orden = OrdenCompra::find($ordenId);
            if (!$orden) {
                return ['success' => false, 'error' => 'Orden no encontrada'];
            }

            // Notificar al creador
            $usuarioIdOrd = is_object($orden) ? $orden->usuario_id : $orden['usuario_id'];
            $creador = Usuario::find($usuarioIdOrd);
            if (!$creador) {
                return ['success' => false, 'error' => 'Creador no encontrado'];
            }

            $data = $this->formatearDatosOrden($orden);
            $creadorNombre = is_object($creador) ? $creador->azure_display_name : $creador['azure_display_name'];
            $creadorEmail = is_object($creador) ? $creador->azure_email : $creador['azure_email'];
            
            $data['destinatario_nombre'] = $creadorNombre;
            $data['rechazador_nombre'] = 'Revisor del Sistema';
            $data['fecha_rechazo'] = date('d/m/Y H:i');
            $data['motivo_rechazo'] = $motivo;
            $data['url_detalle'] = $this->obtenerUrlAccion($ordenId, 'detalle');

            $resultado = $this->emailService->sendWithTemplate(
                $creadorEmail,
                "Requisición #{$data['numero_orden']} Rechazada",
                'rechazo',
                $data
            );

            // Notificar en copia a los revisores
            $revisores = $this->getRevisores();
            foreach ($revisores as $revisor) {
                $this->emailService->sendWithTemplate(
                    $revisor['email'],
                    "Requisición #{$data['numero_orden']} Rechazada",
                    'rechazo',
                    $data,
                    ['reply_to' => $creadorEmail]
                );
            }

            return $resultado;
        } catch (\Exception $e) {
            error_log("Error notificando rechazo: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Notifica que una requisición fue completamente autorizada
     * 
     * @param int $ordenId ID de la orden
     * @return array Resultado
     */
    public function notificarAutorizacionCompleta($ordenId)
    {
        try {
            $orden = OrdenCompra::find($ordenId);
            if (!$orden) {
                return ['success' => false, 'error' => 'Orden no encontrada'];
            }

            $usuarioIdOrd = is_object($orden) ? $orden->usuario_id : $orden['usuario_id'];
            $creador = Usuario::find($usuarioIdOrd);
            if (!$creador) {
                return ['success' => false, 'error' => 'Creador no encontrado'];
            }

            $flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
            if (!$flujo) {
                return ['success' => false, 'error' => 'Flujo no encontrado'];
            }

            $data = $this->formatearDatosOrden($orden);
            $data['destinatario_nombre'] = (is_object($creador) ? $creador->azure_display_name : $creador['azure_display_name']);
            $data['fecha_completado'] = date('d/m/Y H:i');
            $data['dias_proceso'] = $this->calcularDiasProceso($flujo);
            $data['timeline_autorizaciones'] = $this->construirTimelineAutorizaciones($ordenId);
            $data['mensaje_siguientes_pasos'] = 'Puedes proceder con la compra según los términos autorizados.';
            $data['url_detalle'] = $this->obtenerUrlAccion($ordenId, 'detalle');

            // Notificar al creador
            $creadorEmail = is_object($creador) ? $creador->azure_email : $creador['azure_email'];
            $result = $this->emailService->sendWithTemplate(
                $creadorEmail,
                "Requisición #{$data['numero_orden']} Completamente Autorizada",
                'completada',
                $data
            );

            // Notificar también a revisores (copia)
            $revisores = $this->getRevisores();
            foreach ($revisores as $revisor) {
                $this->emailService->sendWithTemplate(
                    $revisor['email'],
                    "Requisición #{$data['numero_orden']} Completada",
                    'completada',
                    $data,
                    ['reply_to' => $creadorEmail]
                );
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Error notificando autorización completa: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Notifica rechazo de una requisición
     * 
     * @param int $ordenId ID de la orden
     * @param string $motivo Motivo del rechazo
     * @return array Resultado
     */
    public function notificarRechazo($ordenId, $motivo)
    {
        try {
            $orden = OrdenCompra::find($ordenId);
            if (!$orden) {
                return ['success' => false, 'error' => 'Orden no encontrada'];
            }

            $usuarioIdOrd = is_object($orden) ? $orden->usuario_id : $orden['usuario_id'];
            $creador = Usuario::find($usuarioIdOrd);
            if (!$creador) {
                return ['success' => false, 'error' => 'Creador no encontrado'];
            }

            $data = $this->formatearDatosOrden($orden);
            $data['destinatario_nombre'] = (is_object($creador) ? $creador->azure_display_name : $creador['azure_display_name']);
            $data['rechazador_nombre'] = 'Autorizador';
            $data['fecha_rechazo'] = date('d/m/Y H:i');
            $data['motivo_rechazo'] = $motivo;
            $data['url_detalle'] = $this->obtenerUrlAccion($ordenId, 'detalle');

            $creadorEmail = is_object($creador) ? $creador->azure_email : $creador['azure_email'];
            return $this->emailService->sendWithTemplate(
                $creadorEmail,
                "Requisición #{$data['numero_orden']} Rechazada",
                'rechazo',
                $data
            );
        } catch (\Exception $e) {
            error_log("Error notificando rechazo: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }



    /**
     * Notifica URGENTEMENTE a los autorizadores sobre requisiciones pendientes
     * 
     * @param int $ordenId ID de la orden
     * @param array $centrosIds IDs de centros de costo
     * @return array Resultado
     */
    public function notificarUrgenteAutorizacionPendiente($ordenId, $centrosIds)
    {
        try {
            $orden = OrdenCompra::find($ordenId);
            if (!$orden) {
                return [
                    'success' => false,
                    'error' => 'Orden de compra no encontrada',
                    'code' => 'ORDER_NOT_FOUND'
                ];
            }

            $data = $this->formatearDatosOrden($orden);
            $data['urgente'] = true; // Marcar como urgente
            $data['prioridad'] = 'ALTA';
            $resultados = [];

            foreach ($centrosIds as $centroId) {
                $centro = CentroCosto::find($centroId);
                if (!$centro) {
                    continue;
                }

                $autorizadores = $this->getAutorizadoresCentro($centroId);
                
                foreach ($autorizadores as $autorizador) {
                    $data['destinatario_nombre'] = $autorizador['nombre'];
                    $data['centro_costo'] = $centro['nombre'];
                    $data['url_revision'] = $this->obtenerUrlAccion($ordenId, 'autorizar');
                    $data['tiempo_limite'] = '24 horas'; // Tiempo límite para respuesta

                    $result = $this->emailService->sendWithTemplate(
                        $autorizador['email'],
                        "URGENTE: Requisición #{$data['numero_orden']} Pendiente de Autorización",
                        'urgente_autorizacion',
                        $data
                    );

                    $resultados[] = $result;
                }
            }

            return [
                'success' => true,
                'message' => 'Notificaciones URGENTES enviadas a autorizadores',
                'count' => count($resultados),
                'tipo' => 'urgente'
            ];
        } catch (\Exception $e) {
            error_log("Error enviando notificaciones urgentes: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'URGENT_NOTIFICATION_ERROR'
            ];
        }
    }


    /**
     * Crea un recordatorio para una orden pendiente
     * 
     * @param int $ordenId ID de la orden
     * @param string $destinatario Email del destinatario
     * @param string $tipo Tipo de recordatorio
     * @return array Resultado
     */
    public function crearRecordatorio($ordenId, $destinatario, $tipo)
    {
        try {
            return Recordatorio::crearParaOrden($ordenId);
        } catch (\Exception $e) {
            error_log("Error creando recordatorio: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Envía recordatorios pendientes (para cron job)
     * 
     * @param int $horasDesdeUltimo Horas desde el último recordatorio
     * @return array Resultado
     */
    public function enviarRecordatoriosPendientes($horasDesdeUltimo = 24)
    {
        try {
            $recordatorios = Recordatorio::necesitanEnvio($horasDesdeUltimo);
            $enviados = 0;
            $errores = 0;

            foreach ($recordatorios as $recordatorio) {
                $orden = OrdenCompra::find($recordatorio['orden_compra_id']);
                if (!$orden) {
                    continue;
                }

                $ordenIdForFlujo = is_object($orden) ? $orden->id : $orden['id'];
                $flujo = AutorizacionFlujo::porOrdenCompra($ordenIdForFlujo);
                if (!$flujo) {
                    continue;
                }

                $data = $this->formatearDatosOrden($orden);
                $data['destinatario_nombre'] = explode('@', $recordatorio['destinatario_email'])[0];
                $estado = is_array($flujo) ? ($flujo['estado'] ?? '') : ($flujo->estado ?? '');
                $data['accion_requerida'] = $estado === 'pendiente_revision' ? 'Revisión' : 'Autorización';
                $data['estado_actual'] = $this->formatearEstado($estado);
                $data['dias_pendiente'] = $this->calcularDiasPendiente(is_array($flujo) ? $flujo : ['fecha_inicio' => $flujo->fecha_inicio ?? null]);
                $ordenIdUrl = is_object($orden) ? $orden->id : $orden['id'];
                $data['url_accion'] = $this->obtenerUrlAccion($ordenIdUrl, 
                    $estado === 'pendiente_revision' ? 'revision' : 'autorizar');

                $result = $this->emailService->sendWithTemplate(
                    $recordatorio['destinatario_email'],
                    "Recordatorio: Requisición #{$data['numero_orden']} Pendiente",
                    'recordatorio',
                    $data
                );

                if ($result['success']) {
                    Recordatorio::marcarComoEnviado($recordatorio['id']);
                    $enviados++;
                } else {
                    Recordatorio::incrementarIntentos($recordatorio['id']);
                    $errores++;
                }
            }

            return [
                'success' => true,
                'enviados' => $enviados,
                'errores' => $errores,
                'total' => count($recordatorios)
            ];
        } catch (\Exception $e) {
            error_log("Error enviando recordatorios: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cancela recordatorios de una orden
     * 
     * @param int $ordenId ID de la orden
     * @return array Resultado
     */
    public function cancelarRecordatorios($ordenId)
    {
        try {
            Recordatorio::cancelarPorOrden($ordenId);
            return ['success' => true, 'message' => 'Recordatorios cancelados'];
        } catch (\Exception $e) {
            error_log("Error cancelando recordatorios: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene emails de revisores
     * 
     * @return array Array de revisores con email y nombre
     */
    private function getRevisores()
    {
        $usuarios = Usuario::revisores();
        $revisores = [];
        
        foreach ($usuarios as $usuario) {
            $email = is_object($usuario) ? ($usuario->azure_email ?? '') : ($usuario['azure_email'] ?? '');
            $nombre = is_object($usuario) ? ($usuario->azure_display_name ?? '') : ($usuario['azure_display_name'] ?? '');
            if ($email) {
                $revisores[] = [
                    'email' => $email,
                    'nombre' => $nombre
                ];
            }
        }
        
        return $revisores;
    }

    /**
     * Obtiene autorizadores de un centro de costo
     * 
     * @param int $centroId ID del centro de costo
     * @return array Array de autorizadores
     */
    private function getAutorizadoresCentro($centroId)
    {
        $centro = CentroCosto::find($centroId);
        if (!$centro) {
            return [];
        }

        // Obtener autorizador activo (respaldo o principal)
        $emailAutorizador = (new CentroCosto())->getEmailAutorizador();
        
        if (!$emailAutorizador) {
            return [];
        }

        $centroNombre = is_object($centro) ? ($centro->nombre ?? '') : ($centro['nombre'] ?? '');
        return [[
            'email' => $emailAutorizador,
            'nombre' => 'Autorizador de ' . $centroNombre
        ]];
    }

    /**
     * Formatea los datos de una orden para las plantillas
     * 
     * @param array $orden Datos de la orden
     * @return array Datos formateados
     */
    private function formatearDatosOrden($orden)
    {
        // Acceder a propiedades de forma segura
        $ordenId = is_object($orden) ? $orden->id : $orden['id'];
        $nombreRazon = is_object($orden) ? $orden->nombre_razon_social : $orden['nombre_razon_social'];
        $montoTotal = is_object($orden) ? $orden->monto_total : $orden['monto_total'];
        $fecha = is_object($orden) ? $orden->fecha : $orden['fecha'];
        $usuarioId = is_object($orden) ? $orden->usuario_id : $orden['usuario_id'];
        
        $numeroOrden = str_pad($ordenId, 6, '0', STR_PAD_LEFT);
        
        return [
            'numero_orden' => $numeroOrden,
            'proveedor' => $nombreRazon,
            'monto_total' => 'Q ' . number_format($montoTotal, 2),
            'fecha_creacion' => date('d/m/Y', strtotime($fecha)),
            'solicitante_nombre' => $this->getNombreUsuario($usuarioId),
            'year' => date('Y'),
            'app_name' => Config::get('app.name', 'Sistema de Requisiciones')
        ];
    }


    /**
     * Obtiene el nombre de un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return string Nombre del usuario
     */
    private function getNombreUsuario($usuarioId)
    {
        $usuario = Usuario::find($usuarioId);
        if (!$usuario) {
            return 'Usuario Desconocido';
        }
        
        $nombre = is_object($usuario) ? $usuario->azure_display_name : $usuario['azure_display_name'];
        return $nombre ?? 'Usuario Desconocido';
    }

    /**
     * Calcula días del proceso
     * 
     * @param array $flujo Flujo de autorización
     * @return int Días
     */
    private function calcularDiasProceso($flujo)
    {
        if (!isset($flujo['fecha_inicio']) || !isset($flujo['fecha_completado'])) {
            return 0;
        }

        $inicio = new \DateTime($flujo['fecha_inicio']);
        $fin = new \DateTime($flujo['fecha_completado']);
        
        return $inicio->diff($fin)->days;
    }

    /**
     * Calcula días pendiente
     * 
     * @param array $flujo Flujo de autorización
     * @return int Días
     */
    private function calcularDiasPendiente($flujo)
    {
        if (!isset($flujo['fecha_inicio'])) {
            return 0;
        }

        $inicio = new \DateTime($flujo['fecha_inicio']);
        $ahora = new \DateTime();
        
        return $inicio->diff($ahora)->days;
    }

    /**
     * Construye el timeline de autorizaciones
     * 
     * @param int $ordenId ID de la orden
     * @return string HTML del timeline
     */
    private function construirTimelineAutorizaciones($ordenId)
    {
        $html = '';
        // Por ahora retornamos string vacío, se puede implementar después
        return $html;
    }

    /**
     * Formatea el estado para mostrar
     * 
     * @param string $estado Estado del flujo
     * @return string Estado formateado
     */
    private function formatearEstado($estado)
    {
        $estados = [
            'pendiente_revision' => 'Pendiente de Revisión',
            'pendiente_autorizacion' => 'Pendiente de Autorización',
            'autorizado' => 'Autorizado',
            'rechazado' => 'Rechazado'
        ];

        return $estados[$estado] ?? $estado;
    }

    /**
     * Notifica al siguiente nivel según el estado del flujo
     * 
     * @param int $ordenId ID de la orden
     * @param string $estado Estado del flujo
     * @return void
     */
    private function notificarSiguienteNivel($ordenId, $estado)
    {
        try {
            switch ($estado) {
                case AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_PAGO:
                    $this->notificarAutorizadorEspecialPago($ordenId);
                    break;
                case AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_CUENTA:
                    $this->notificarAutorizadorEspecialCuentas($ordenId);
                    break;
                case AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_CENTROS:
                    $this->notificarAutorizadoresCentros($ordenId);
                    break;
            }
        } catch (\Exception $e) {
            error_log("Error notificando siguiente nivel: " . $e->getMessage());
        }
    }

    /**
     * Notifica al autorizador especial de forma de pago
     * 
     * @param int $ordenId ID de la orden
     * @return array Resultado
     */
    public function notificarAutorizadorEspecialPago($ordenId)
    {
        try {
            $orden = OrdenCompra::find($ordenId);
            if (!$orden) {
                return ['success' => false, 'error' => 'Orden no encontrada'];
            }

            $formaPago = is_object($orden) ? $orden->forma_pago : $orden['forma_pago'];
            $autorizador = AutorizadorMetodoPago::porFormaPago($formaPago);
            
            if (!$autorizador) {
                error_log("No se encontró autorizador para forma de pago: $formaPago");
                return ['success' => false, 'error' => 'Autorizador no encontrado'];
            }

            $data = $this->formatearDatosOrden($orden);
            $data['destinatario_nombre'] = $autorizador['autorizador_nombre'];
            $data['forma_pago'] = AutorizadorMetodoPago::getDescripcionFormaPago($formaPago);
            $data['motivo_especial'] = "Esta requisición requiere autorización especial por el método de pago: " . $data['forma_pago'];
            $data['url_revision'] = $this->obtenerUrlAccion($ordenId, 'autorizar-pago');
            $data['accion_requerida'] = 'Autorización Especial de Forma de Pago';

            return $this->sendEmailSafe(
                'sendWithTemplate',
                $autorizador['autorizador_email'],
                "Requisición #{$data['numero_orden']} - Autorización Especial Requerida (Forma de Pago)",
                'nueva_requisicion',
                $data
            );
        } catch (\Exception $e) {
            error_log("Error notificando autorizador especial de pago: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Notifica a los autorizadores especiales de cuentas contables
     * 
     * @param int $ordenId ID de la orden
     * @return array Resultado
     */
    public function notificarAutorizadorEspecialCuentas($ordenId)
    {
        try {
            $orden = OrdenCompra::find($ordenId);
            if (!$orden) {
                return ['success' => false, 'error' => 'Orden no encontrada'];
            }

            // Obtener distribuciones para identificar cuentas especiales
            $distribuciones = DistribucionGasto::porOrdenCompra($ordenId);
            $autorizadoresNotificados = [];
            $resultados = [];

            foreach ($distribuciones as $dist) {
                $autorizador = AutorizadorCuentaContable::porCuentaContable($dist['cuenta_contable_id']);
                if (!$autorizador) {
                    continue;
                }

                // Evitar notificar al mismo autorizador múltiples veces
                $email = $autorizador['autorizador_email'];
                if (in_array($email, $autorizadoresNotificados)) {
                    continue;
                }
                $autorizadoresNotificados[] = $email;

                $cuenta = CuentaContable::find($dist['cuenta_contable_id']);
                $nombreCuenta = $cuenta ? ($cuenta['descripcion'] ?? $cuenta['codigo']) : 'Cuenta Especial';

                $data = $this->formatearDatosOrden($orden);
                $data['destinatario_nombre'] = $autorizador['autorizador_nombre'];
                $data['cuenta_contable'] = $nombreCuenta;
                $data['motivo_especial'] = "Esta requisición requiere autorización especial por la cuenta contable: $nombreCuenta";
                $data['url_revision'] = $this->obtenerUrlAccion($ordenId, 'autorizar-cuenta');
                $data['accion_requerida'] = 'Autorización Especial de Cuenta Contable';

                $result = $this->sendEmailSafe(
                    'sendWithTemplate',
                    $email,
                    "Requisición #{$data['numero_orden']} - Autorización Especial Requerida (Cuenta Contable)",
                    'nueva_requisicion',
                    $data
                );

                $resultados[] = $result;
            }

            return [
                'success' => true,
                'message' => 'Notificaciones enviadas a autorizadores de cuentas contables',
                'count' => count($resultados)
            ];
        } catch (\Exception $e) {
            error_log("Error notificando autorizadores especiales de cuenta: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Actualizar notificarAutorizadoresCentros para usar el nuevo estado
     * 
     * @param int $ordenId ID de la orden
     * @return array Resultado
     */
    public function notificarAutorizadoresCentros($ordenId)
    {
        try {
            $orden = OrdenCompra::find($ordenId);
            if (!$orden) {
                return ['success' => false, 'error' => 'Orden no encontrada'];
            }

            // Obtener centros de costo de las distribuciones
            $distribuciones = DistribucionGasto::porOrdenCompra($ordenId);
            $centrosIds = array_unique(array_column($distribuciones, 'centro_costo_id'));

            $data = $this->formatearDatosOrden($orden);
            $resultados = [];

            foreach ($centrosIds as $centroId) {
                $centro = CentroCosto::find($centroId);
                if (!$centro) {
                    continue;
                }

                $autorizadores = $this->getAutorizadoresCentro($centroId);
                
                foreach ($autorizadores as $autorizador) {
                    $data['destinatario_nombre'] = $autorizador['nombre'];
                    $data['centro_costo'] = $centro['nombre'];
                    $data['url_revision'] = $this->obtenerUrlAccion($ordenId, 'autorizar');
                    $data['accion_requerida'] = 'Autorización por Centro de Costo';

                    $result = $this->sendEmailSafe(
                        'sendWithTemplate',
                        $autorizador['email'],
                        "Requisición #{$data['numero_orden']} Pendiente de Autorización",
                        'nueva_requisicion',
                        $data
                    );

                    $resultados[] = $result;
                }
            }

            return [
                'success' => true,
                'message' => 'Notificaciones enviadas a autorizadores de centros',
                'count' => count($resultados)
            ];
        } catch (\Exception $e) {
            error_log("Error notificando autorizadores de centros: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Actualizar obtenerUrlAccion para incluir nuevos tipos
     * 
     * @param int $ordenId ID de la orden
     * @param string $accion Acción (revision, autorizar, autorizar-pago, autorizar-cuenta, detalle)
     * @return string URL
     */
    private function obtenerUrlAccion($ordenId, $accion)
    {
        $baseUrl = Config::get('app.url', 'http://localhost');
        
        $rutas = [
            'revision' => "/revisiones/{$ordenId}",
            'autorizar' => "/autorizaciones/{$ordenId}",
            'autorizar-pago' => "/autorizaciones/pago/{$ordenId}",
            'autorizar-cuenta' => "/autorizaciones/cuenta/{$ordenId}",
            'detalle' => "/requisiciones/{$ordenId}"
        ];
        
        return $baseUrl . ($rutas[$accion] ?? "/requisiciones/{$ordenId}");
    }
}
