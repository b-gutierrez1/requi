<?php
/**
 * AutorizacionController
 * 
 * Controlador para gestión de autorizaciones de requisiciones.
 * Maneja revisión, aprobación y rechazo en todos los niveles.
 * 
 * @package RequisicionesMVC\Controllers
 * @version 2.0
 */

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Session;
use App\Helpers\Redirect;
use App\Helpers\EstadoHelper;
use App\Services\AutorizacionService;
use App\Services\RequisicionService;
use App\Models\OrdenCompra;
use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionFlujoAdaptador;
use App\Models\AutorizacionCentroCosto;
use App\Models\AutorizacionCentroCostoAdaptador;

class AutorizacionController extends Controller
{
    /**
     * Servicio de autorización
     * 
     * @var AutorizacionService
     */
    private $autorizacionService;

    /**
     * Servicio de requisiciones
     * 
     * @var RequisicionService
     */
    private $requisicionService;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->autorizacionService = new AutorizacionService();
        $this->requisicionService = new RequisicionService();
    }

    // ========================================================================
    // LISTADO Y VISUALIZACIÓN
    // ========================================================================

    /**
     * Lista las autorizaciones pendientes del usuario
     * 
     * @return void
     */
    public function index()
    {
        $usuarioEmail = $this->getUsuarioEmail();
        
        error_log("AutorizacionController::index() - Usuario email: $usuarioEmail");
        error_log("AutorizacionController::index() - Es revisor: " . (Session::isRevisor() ? 'SÍ' : 'NO'));
        
        // Obtener requisiciones pendientes de revisión (para revisores)
        $requisicionesPendientesRevision = [];
        $esRevisor = Session::isRevisor() || $this->isRevisorPorEmail($usuarioEmail);
        if ($esRevisor) {
            $requisicionesPendientesRevision = $this->autorizacionService->getRequisicionesPendientesRevision();
        }
        
        // Obtener autorizaciones pendientes (para autorizadores por centro de costo)
        $autorizacionesPendientes = $this->autorizacionService->getAutorizacionesPendientes($usuarioEmail);
        
        // Obtener TODAS las autorizaciones unificadas (centro + especiales + respaldos)
        $todasAutorizaciones = $this->autorizacionService->getTodasAutorizacionesPendientes($usuarioEmail);
        
        // Separar por tipo para compatibilidad con vistas existentes
        $autorizacionesPendientesPago = array_filter($todasAutorizaciones, fn($a) => $a['tipo_flujo'] === 'forma_pago');
        $autorizacionesPendientesCuenta = array_filter($todasAutorizaciones, fn($a) => $a['tipo_flujo'] === 'cuenta_contable');
        
        // Obtener información del tipo de autorizador
        $tipoAutorizador = $this->autorizacionService->getTipoAutorizador($usuarioEmail);
        
        // Verificar si es autorizador de respaldo
        $esRespaldo = $this->autorizacionService->esAutorizadorRespaldo($usuarioEmail);
        
        error_log("=== AUTORIZACIONES PENDIENTES EN CONTROLADOR ===");
        error_log("Centros de costo: " . count($autorizacionesPendientes));
        error_log("Forma de pago: " . count($autorizacionesPendientesPago));
        error_log("Cuenta contable: " . count($autorizacionesPendientesCuenta));
        foreach ($autorizacionesPendientes as $i => $auth) {
            error_log("[$i] Orden {$auth['orden_id']}: {$auth['centro_nombre']} - {$auth['nombre_razon_social']}");
        }

        // Obtener información adicional mejorada (con manejo de errores)
        $flujosPorVencer = [];
        $flujosVencidos = [];
        $flujosUrgentes = [];
        $estadisticasGenerales = [];
        
        // try {
        //     $flujosPorVencer = \App\Models\AutorizacionFlujo::proximosAVencer(2);
        //     $flujosVencidos = \App\Models\AutorizacionFlujo::vencidos();
        //     $flujosUrgentes = \App\Models\AutorizacionFlujo::porPrioridad('urgente');
        //     $estadisticasGenerales = \App\Models\AutorizacionFlujo::getEstadisticas();
        // } catch (\Exception $e) {
        //     error_log("Error obteniendo información adicional: " . $e->getMessage());
        //     // Continuar sin las funcionalidades avanzadas
        // }

        // Combinar todas las listas
        $totalPendientes = count($requisicionesPendientesRevision) + count($autorizacionesPendientes) + 
                          count($autorizacionesPendientesPago) + count($autorizacionesPendientesCuenta);

        View::render('autorizaciones/index', [
            'requisiciones_pendientes_revision' => $requisicionesPendientesRevision,
            'autorizaciones_pendientes' => $autorizacionesPendientes,
            'autorizaciones_pendientes_pago' => $autorizacionesPendientesPago,
            'autorizaciones_pendientes_cuenta' => $autorizacionesPendientesCuenta,
            'todas_autorizaciones' => $todasAutorizaciones, // NUEVO: Lista unificada
            'flujos_por_vencer' => $flujosPorVencer,
            'flujos_vencidos' => $flujosVencidos,
            'flujos_urgentes' => $flujosUrgentes,
            'estadisticas_generales' => $estadisticasGenerales,
            'total_pendientes' => $totalPendientes,
            'es_autorizador_pago' => $this->autorizacionService->esAutorizadorPago($usuarioEmail),
            'es_autorizador_cuenta' => $this->autorizacionService->esAutorizadorCuenta($usuarioEmail),
            'es_autorizador_respaldo' => $esRespaldo, // NUEVO: Indica si es respaldo
            'tipo_autorizador' => $tipoAutorizador, // NUEVO: Tipos de autorizador
            'title' => 'Mis Autorizaciones Pendientes'
        ]);
    }

    /**
     * Muestra el detalle de una autorización
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function show($id)
    {
        // Obtener requisición completa
        $requisicion = $this->requisicionService->getRequisicionCompleta($id);

        if (!$requisicion) {
            Redirect::to('/autorizaciones')
                ->withError('Requisición no encontrada')
                ->send();
        }

        // ✅ USAR SISTEMA CENTRALIZADO - Verificar permisos según el estado real
        
        // Obtener estado real de la requisición
        $orden = $requisicion['orden'];
        $estadoReal = is_object($orden) ? $orden->getEstadoReal() : EstadoHelper::getEstado($id);
        
        $flujoTemp = $requisicion['flujo'] ?? AutorizacionFlujoAdaptador::porOrdenCompra($id);
        $estadoFlujo = $estadoReal; // Usar estado centralizado
        
        $tienePermisos = false;
        $mensajeError = 'No tienes permisos para ver esta requisición';
        
        if ($estadoFlujo === 'pendiente_revision') {
            // Para revisión: verificar si es revisor o admin
            $tienePermisos = $this->isRevisor() || $this->isAdmin();
            $mensajeError = 'No tienes permisos de revisor para esta requisición';
        } elseif ($estadoFlujo === 'pendiente_autorizacion') {
            // Para autorización: verificar si puede autorizar específicamente
            $tienePermisos = $this->autorizacionService->puedeAutorizar($this->getUsuarioEmail(), $id) || $this->isAdmin();
            $mensajeError = 'No tienes permisos para autorizar esta requisición';
        } else {
            // Para otros estados: verificar si es el dueño, revisor, autorizador o admin
            $orden = $requisicion['orden'];
            $esDueño = (is_object($orden) ? $orden->usuario_id : $orden['usuario_id']) == $this->getUsuarioId();
            $esRevisor = $this->isRevisor();
            $puedeAutorizar = $this->autorizacionService->puedeAutorizar($this->getUsuarioEmail(), $id);
            $esAdmin = $this->isAdmin();
            
            $tienePermisos = $esDueño || $esRevisor || $puedeAutorizar || $esAdmin;
            $mensajeError = 'No tienes permisos para ver esta requisición';
        }
        
        if (!$tienePermisos) {
            Redirect::to('/autorizaciones')
                ->withError($mensajeError)
                ->send();
        }

        // Obtener flujo y resumen completo
        $flujo = $requisicion['flujo'] ?? null;
        $resumenCompleto = null;
        $historialCambios = [];
        
        // Si no tenemos flujo, buscarlo directamente
        if (!$flujo) {
            $flujo = AutorizacionFlujoAdaptador::porOrdenCompra($id);
        }
        
        if ($flujo) {
            $flujoId = is_object($flujo) ? $flujo->id : $flujo['id'];
            
            try {
                // Obtener resumen completo con nueva información
                $resumenCompleto = AutorizacionFlujoAdaptador::getResumenCompleto($flujoId);
                
                // Obtener historial de cambios
                $historialCambios = AutorizacionFlujoAdaptador::getHistorialCambios($flujoId);
                
                // Establecer prioridad automática si no existe
                if ($resumenCompleto && !$resumenCompleto['prioridad'] && $resumenCompleto['monto_total']) {
                    AutorizacionFlujoAdaptador::establecerPrioridad($flujoId, null, $resumenCompleto['monto_total']);
                    AutorizacionFlujoAdaptador::establecerFechaLimite($flujoId, $resumenCompleto['prioridad'] ?? 'normal');
                }
            } catch (\Exception $e) {
                error_log("Error obteniendo resumen completo: " . $e->getMessage());
                // Usar información básica del flujo
                $resumenCompleto = $flujo;
            }
        }

        // Obtener progreso de autorización
        $progreso = null;
        if ($flujo) {
            $progreso = $this->autorizacionService->getProgresoAutorizacion($flujo->id);
        }

        // Obtener autorizaciones por centro de costo
        $autorizacionesCentro = [];
        if ($flujo) {
            $flujoId = is_object($flujo) ? $flujo->id : $flujo['id'];
            $autorizacionesCentro = AutorizacionCentroCostoAdaptador::porFlujo($flujoId);
        }

        // Preparar datos para la vista con información mejorada
        $dataVista = [
            'requisicion' => $requisicion,
            'orden' => $requisicion['orden'],
            'items' => $requisicion['items'],
            'distribucion' => $requisicion['distribucion'],
            'flujo' => $flujo,
            'resumen_completo' => $resumenCompleto,
            'historial_cambios' => $historialCambios,
            'progreso' => $progreso,
            'historial' => $requisicion['historial'],
            'autorizaciones_centro' => $autorizacionesCentro,
            'title' => 'Autorizar Requisición #' . $id
        ];
        
        View::render('autorizaciones/show', $dataVista);
    }

    // ========================================================================
    // REVISIÓN (Nivel 1)
    // ========================================================================

    /**
     * Lista requisiciones pendientes de revisión
     * 
     * @return void
     */
    public function pendientesRevision()
    {
        // Verificar que es revisor
        if (!$this->isRevisor()) {
            Redirect::to('/dashboard')
                ->withError('No tienes permisos de revisor')
                ->send();
        }

        // Obtener requisiciones pendientes de revisión
        $requisiciones = $this->autorizacionService->getRequisicionesPendientesRevision();

        View::render('autorizaciones/revision', [
            'requisiciones' => $requisiciones,
            'title' => 'Requisiciones Pendientes de Revisión'
        ]);
    }

    /**
     * Aprueba una requisición en revisión
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function aprobarRevision($id)
    {
        // Para peticiones AJAX, manejo completamente separado
        if ($this->isAjaxRequest()) {
            // Limpiar cualquier output buffer existente
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Suprimir errores para evitar contaminar JSON
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            error_reporting(0);
            
            // Iniciar nuevo buffer limpio
            ob_start();
            
            $response = [
                'success' => false,
                'error' => 'Error desconocido'
            ];
            
            try {
                // Log inicial para debugging
                error_log("=== INICIO APROBACIÓN REVISIÓN ===");
                error_log("ID requisición: $id");
                error_log("Usuario actual: " . $this->getUsuarioEmail());
                error_log("Es AJAX: " . ($this->isAjaxRequest() ? 'SÍ' : 'NO'));
                
                // Validaciones básicas
                if (!$this->validateCSRF()) {
                    $response = [
                        'success' => false,
                        'error' => 'Token de seguridad inválido'
                    ];
                } elseif (!$this->isRevisor() && !$this->isAdmin()) {
                    // Log detallado para debugging
                    $user = Session::getUser();
                    $userEmail = $this->getUsuarioEmail();
                    error_log("=== VALIDACIÓN PERMISOS REVISOR ===");
                    error_log("Usuario email: $userEmail");
                    error_log("Session::isRevisor(): " . (Session::isRevisor() ? 'SÍ' : 'NO'));
                    error_log("Session::isAdmin(): " . (Session::isAdmin() ? 'SÍ' : 'NO'));
                    error_log("Session::isAuthenticated(): " . (Session::isAuthenticated() ? 'SÍ' : 'NO'));
                    error_log("Datos de usuario: " . print_r($user, true));
                    error_log("Variables de sesión directas:");
                    error_log("is_revisor directo: " . ($_SESSION['is_revisor'] ?? 'NO SET'));
                    if (isset($_SESSION['user']['is_revisor'])) {
                        error_log("user.is_revisor: " . $_SESSION['user']['is_revisor']);
                    }
                    
                    $response = [
                        'success' => false,
                        'error' => 'El usuario no tiene permisos de revisor',
                        'code' => 'NOT_REVIEWER'
                    ];
                } else {
                    // Ejecutar aprobación
                    $comentario = $_POST['comentario'] ?? '';
                    $usuarioId = $this->getUsuarioId();
                    
                    // Ejecutar aprobación real
                    $resultado = $this->autorizacionService->aprobarRevision($id, $usuarioId, $comentario);
                    
                    if (is_array($resultado) && isset($resultado['success'])) {
                        $response = $resultado;
                    } else {
                        $response = [
                            'success' => false,
                            'error' => 'Respuesta inválida del servicio'
                        ];
                    }
                }
                
            } catch (\Exception $e) {
                error_log("Error en aprobarRevision AJAX: " . $e->getMessage());
                error_log("Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
                $response = [
                    'success' => false,
                    'error' => 'Error interno del servidor: ' . $e->getMessage()
                ];
            }
            
            // Enviar respuesta AJAX limpia
            $this->sendAjaxResponse($response);
        }

        // Para peticiones no AJAX, manejo normal
        // Validar CSRF
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        // Verificar que es revisor
        if (!$this->isRevisor()) {
            Redirect::back()
                ->withError('No tienes permisos de revisor')
                ->send();
        }

        $comentario = $_POST['comentario'] ?? '';
        $usuarioId = $this->getUsuarioId();

        try {
            $resultado = $this->autorizacionService->aprobarRevision($id, $usuarioId, $comentario);
            
            if ($resultado['success']) {
                Redirect::to('/autorizaciones/revision')
                    ->withSuccess('Requisición aprobada exitosamente')
                    ->send();
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->send();
            }
        } catch (\Exception $e) {
            Redirect::back()
                ->withError('Error interno del servidor')
                ->send();
        }
    }

    /**
     * Rechaza una requisición en revisión
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function rechazarRevision($id)
    {
        // Validar CSRF
        if (!$this->validateCSRF()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Token de seguridad inválido'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        // Verificar que es revisor
        if (!$this->isRevisor()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'No tienes permisos de revisor'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('No tienes permisos de revisor')
                ->send();
        }

        $motivo = $_POST['motivo'] ?? '';
        
        if (empty($motivo)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Debes especificar el motivo del rechazo'
                ], 400);
                return;
            }
            Redirect::back()
                ->withError('Debes especificar el motivo del rechazo')
                ->withInput()
                ->send();
        }

        $usuarioId = $this->getUsuarioId();

        $resultado = $this->autorizacionService->rechazarRevision($id, $usuarioId, $motivo);

        // Responder según el tipo de petición
        if ($this->isAjaxRequest()) {
            $this->jsonResponse($resultado);
        } else {
            if ($resultado['success']) {
                Redirect::to('/autorizaciones/revision')
                    ->withSuccess('Requisición rechazada')
                    ->send();
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->send();
            }
        }
    }

    // ========================================================================
    // AUTORIZACIÓN POR CENTRO DE COSTO (Nivel 4)
    // ========================================================================


    /**
     * Autoriza un centro de costo
     * 
     * @param int $id ID de la autorización de centro de costo
     * @return void
     */
    public function autorizarCentro($id)
    {
        // Para peticiones AJAX, manejo completamente separado
        if ($this->isAjaxRequest()) {
            error_log("=== INICIO autorizarCentro AJAX ===");
            error_log("Authorization ID: $id");
            error_log("User email: " . $this->getUsuarioEmail());
            error_log("User ID: " . $this->getUsuarioId());
            error_log("POST data: " . json_encode($_POST));
            error_log("Headers sent before: " . (headers_sent() ? 'YES' : 'NO'));
            error_log("OB level before: " . ob_get_level());
            
            // Limpiar cualquier output buffer existente
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Suprimir errores para evitar contaminar JSON
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            error_reporting(0);
            
            // Iniciar nuevo buffer limpio
            ob_start();
            
            $response = [
                'success' => false,
                'error' => 'Error desconocido'
            ];
            
            try {
                // Validar CSRF
                if (!$this->validateCSRF()) {
                    $response = [
                        'success' => false,
                        'error' => 'Token de seguridad inválido'
                    ];
                } else {
                    $comentario = $_POST['comentario'] ?? '';
                    $usuarioId = $this->getUsuarioId();

                    // Obtener la autorización actual
                    $autorizacion = AutorizacionCentroCostoAdaptador::find($id);
                    if (!$autorizacion) {
                        $response = [
                            'success' => false,
                            'error' => 'Autorización no encontrada'
                        ];
                    } else {
                        $flujoId = is_object($autorizacion) ? $autorizacion->autorizacion_flujo_id : $autorizacion['autorizacion_flujo_id'];
                        $ordenId = is_object($autorizacion)
                            ? ($autorizacion->requisicion_id ?? $flujoId)
                            : ($autorizacion['requisicion_id'] ?? $flujoId);
                        $autorizadorEmail = is_object($autorizacion) ? $autorizacion->autorizador_email : $autorizacion['autorizador_email'];

                        if ($this->autorizacionService->existenAutorizacionesEspecialesPendientes((int)$ordenId)) {
                            $response = [
                                'success' => false,
                                'error' => 'Aún existen autorizaciones especiales pendientes. Deben completarse antes de autorizar los centros de costo.'
                            ];
                            $this->sendAjaxResponse($response);
                            return;
                        }

                        // Obtener todas las autorizaciones pendientes del mismo autorizador en este flujo
                        $autorizacionesPendientes = AutorizacionCentroCostoAdaptador::porFlujo($flujoId);
                        
                        $autorizacionesAAutorizar = [];
                        foreach ($autorizacionesPendientes as $auth) {
                            if ($auth['autorizador_email'] === $autorizadorEmail && $auth['estado'] === 'pendiente') {
                                $autorizacionesAAutorizar[] = $auth;
                            }
                        }

                        // Autorizar todas las que corresponden a este autorizador
                        $response = [
                            'success' => true,
                            'message' => 'Centro(s) de costo autorizado(s) exitosamente',
                            'centros_autorizados' => []
                        ];

                        error_log("=== INICIANDO AUTORIZACIÓN DE CENTROS ===");
                        error_log("Total centros a autorizar: " . count($autorizacionesAAutorizar));
                        
                        foreach ($autorizacionesAAutorizar as $i => $authPendiente) {
                            error_log("=== AUTORIZANDO CENTRO [$i] ===");
                            error_log("ID: {$authPendiente['id']}, Centro: {$authPendiente['centro_costo_id']}");
                            
                            $usuarioEmail = $this->getUsuarioEmail();
                            error_log("Usuario email obtenido: $usuarioEmail");
                            
                            error_log("=== LLAMANDO autorizarCentroCosto ===");
                            try {
                                $resultado = $this->autorizacionService->autorizarCentroCosto($authPendiente['id'], $usuarioEmail, $comentario);
                                error_log("=== RESULTADO autorizarCentroCosto ===");
                                error_log("Resultado: " . json_encode($resultado));
                            } catch (\Exception $e) {
                                error_log("EXCEPCIÓN en autorizarCentroCosto: " . $e->getMessage());
                                error_log("Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
                                $resultado = [
                                    'success' => false,
                                    'error' => 'Error interno: ' . $e->getMessage()
                                ];
                            }
                            
                            if ($resultado['success']) {
                                $response['centros_autorizados'][] = $authPendiente['centro_nombre'] ?? 'Centro #' . $authPendiente['centro_costo_id'];
                                error_log("Centro autorizado exitosamente");
                            } else {
                                error_log("ERROR autorizando centro: " . ($resultado['error'] ?? 'Error desconocido'));
                            }
                        }
                        
                        error_log("=== FIN AUTORIZACIÓN DE CENTROS ===");

                        // FORZAR verificación y completado del flujo
                        try {
                            error_log("=== FORZANDO VERIFICACIÓN DEL FLUJO $flujoId ===");
                            $verificacionResultado = $this->autorizacionService->verificarYCompletarFlujo($flujoId);
                            error_log("Resultado de verificación forzada: " . ($verificacionResultado ? 'ÉXITO' : 'FALLÓ'));
                        } catch (\Exception $e) {
                            error_log("Error en verificación forzada: " . $e->getMessage());
                        }
                        
                        // ✅ USAR SISTEMA CENTRALIZADO - Obtener estado real actualizado
                        $flujoActualizado = AutorizacionFlujoAdaptador::porOrdenCompra($flujoId);
                        if ($flujoActualizado) {
                            $ordenId = is_object($flujoActualizado) ? $flujoActualizado->orden_compra_id : $flujoActualizado['orden_compra_id'];
                            $response['flujo_estado'] = EstadoHelper::getEstado($ordenId); // Estado real centralizado
                            $response['orden_id'] = $ordenId;
                            
                            error_log("Estado final del flujo después de autorización: {$response['flujo_estado']}");
                        }
                    }
                }
                
            } catch (\Exception $e) {
                error_log("Error en autorizarCentro AJAX: " . $e->getMessage());
                error_log("Exception file: " . $e->getFile());
                error_log("Exception line: " . $e->getLine());
                error_log("Exception trace: " . $e->getTraceAsString());
                $response = [
                    'success' => false,
                    'error' => 'Error interno del servidor: ' . $e->getMessage()
                ];
            }
            
            // DEBUG antes de enviar respuesta
            error_log("=== ANTES DE sendAjaxResponse ===");
            error_log("Response a enviar: " . json_encode($response));
            error_log("Headers sent: " . (headers_sent() ? 'YES' : 'NO'));
            error_log("OB level: " . ob_get_level());
            error_log("OB contents: " . ob_get_contents());
            
            // Enviar respuesta AJAX limpia
            $this->sendAjaxResponse($response);
        }

        // Para peticiones no AJAX, manejo normal
        // Validar CSRF
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        $comentario = $_POST['comentario'] ?? '';
        $usuarioId = $this->getUsuarioId();

        try {
            // Obtener la autorización actual
            $autorizacion = AutorizacionCentroCostoAdaptador::find($id);
            if (!$autorizacion) {
                Redirect::back()
                    ->withError('Autorización no encontrada')
                    ->send();
            }

            $flujoId = is_object($autorizacion) ? $autorizacion->autorizacion_flujo_id : $autorizacion['autorizacion_flujo_id'];
            $ordenId = is_object($autorizacion)
                ? ($autorizacion->requisicion_id ?? $flujoId)
                : ($autorizacion['requisicion_id'] ?? $flujoId);

            if ($this->autorizacionService->existenAutorizacionesEspecialesPendientes((int)$ordenId)) {
                Redirect::back()
                    ->withError('Aún existen autorizaciones especiales pendientes. Deben completarse antes de autorizar los centros de costo.')
                    ->send();
                return;
            }
            $autorizadorEmail = is_object($autorizacion) ? $autorizacion->autorizador_email : $autorizacion['autorizador_email'];

            // Obtener todas las autorizaciones pendientes del mismo autorizador en este flujo
            $autorizacionesPendientes = AutorizacionCentroCostoAdaptador::porFlujo($flujoId);
            
            $autorizacionesAAutorizar = [];
            foreach ($autorizacionesPendientes as $auth) {
                if ($auth['autorizador_email'] === $autorizadorEmail && $auth['estado'] === 'pendiente') {
                    $autorizacionesAAutorizar[] = $auth;
                }
            }

            // Autorizar todas las que corresponden a este autorizador
            $exitosas = 0;
            $usuarioEmail = $this->getUsuarioEmail();
            foreach ($autorizacionesAAutorizar as $authPendiente) {
                $resultado = $this->autorizacionService->autorizarCentroCosto($authPendiente['id'], $usuarioEmail, $comentario);
                if ($resultado['success']) {
                    $exitosas++;
                }
            }

            // FORZAR verificación y completado del flujo
            try {
                error_log("=== FORZANDO VERIFICACIÓN DEL FLUJO $flujoId ===");
                $verificacionResultado = $this->autorizacionService->verificarYCompletarFlujo($flujoId);
                error_log("Resultado de verificación forzada: " . ($verificacionResultado ? 'ÉXITO' : 'FALLÓ'));
            } catch (\Exception $e) {
                error_log("Error en verificación forzada: " . $e->getMessage());
            }

            if ($exitosas > 0) {
                Redirect::back()
                    ->withSuccess('Centro(s) de costo autorizado(s) exitosamente')
                    ->send();
            } else {
                Redirect::back()
                    ->withError('Error al autorizar')
                    ->send();
            }
        } catch (\Exception $e) {
            error_log("Error en autorizarCentro: " . $e->getMessage());
            Redirect::back()
                ->withError('Error interno del servidor')
                ->send();
        }
    }

    /**
     * Rechaza un centro de costo
     * 
     * @param int $id ID de la autorización de centro de costo
     * @return void
     */
    public function rechazarCentro($id)
    {
        // Validar CSRF
        if (!$this->validateCSRF()) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Token de seguridad inválido'
            ], 403);
            return;
        }

        $motivo = $_POST['motivo'] ?? '';
        
        if (empty($motivo)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Debes especificar el motivo del rechazo'
            ], 400);
            return;
        }

        $usuarioId = $this->getUsuarioId();

        $autorizacion = AutorizacionCentroCostoAdaptador::find($id);
        if (!$autorizacion) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Autorización no encontrada'
                ], 404);
            } else {
                Redirect::back()
                    ->withError('Autorización no encontrada')
                    ->send();
            }
            return;
        }

        $ordenId = $autorizacion['requisicion_id'] ?? $autorizacion['autorizacion_flujo_id'] ?? null;
        if ($ordenId && $this->autorizacionService->existenAutorizacionesEspecialesPendientes((int)$ordenId)) {
            $mensajePendientes = 'Aún existen autorizaciones especiales pendientes. Deben completarse antes de autorizar o rechazar los centros de costo.';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => $mensajePendientes
                ], 409);
            } else {
                Redirect::back()
                    ->withError($mensajePendientes)
                    ->send();
            }
            return;
        }

        $resultado = $this->autorizacionService->rechazarCentroCosto($id, $usuarioId, $motivo);

        if ($this->isAjaxRequest()) {
            $this->jsonResponse($resultado);
        } else {
            if ($resultado['success']) {
                Redirect::to('/autorizaciones')
                    ->withSuccess('Centro de costo rechazado. La requisición ha sido rechazada.')
                    ->send();
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->send();
            }
        }
    }

    // ========================================================================
    // HISTORIAL
    // ========================================================================

    /**
     * Muestra el historial de autorizaciones del usuario
     * 
     * @return void
     */
    public function historial()
    {
        $usuarioId = $this->getUsuarioId();
        
        // Obtener filtros
        $filtros = [
            'accion' => $_GET['accion'] ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'busqueda' => $_GET['busqueda'] ?? ''
        ];

        // Obtener historial de autorizaciones
        $autorizaciones = $this->autorizacionService->getHistorialAutorizaciones($usuarioId, $filtros);

        View::render('autorizaciones/historial', [
            'autorizaciones' => $autorizaciones,
            'filtros' => $filtros,
            'title' => 'Historial de Autorizaciones'
        ]);
    }

    // ========================================================================
    // API ENDPOINTS
    // ========================================================================

    /**
     * API: Obtiene el progreso de una autorización
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function apiProgreso($id)
    {
        $flujo = AutorizacionFlujoAdaptador::porOrdenCompra($id);

        if (!$flujo) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Flujo no encontrado'
            ], 404);
            return;
        }

        $progreso = $this->autorizacionService->getProgresoAutorizacion($flujo['id']);

        $this->jsonResponse([
            'success' => true,
            'progreso' => $progreso
        ]);
    }

    /**
     * API: Obtiene autorizaciones pendientes (contador)
     * 
     * @return void
     */
    public function apiPendientes()
    {
        $usuarioEmail = $this->getUsuarioEmail();
        $count = $this->autorizacionService->contarPendientes($usuarioEmail);

        $this->jsonResponse([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * API: Autoriza múltiples centros de costo a la vez
     * 
     * @return void
     */
    public function apiAutorizarMultiple()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $ids = $data['ids'] ?? [];
        $comentario = $data['comentario'] ?? '';

        if (empty($ids)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'No se especificaron autorizaciones'
            ], 400);
            return;
        }

        $usuarioEmail = $this->getUsuarioEmail();
        $resultados = [];

        foreach ($ids as $id) {
            $resultado = $this->autorizacionService->autorizarCentroCosto($id, $usuarioEmail, $comentario);
            $resultados[] = [
                'id' => $id,
                'success' => $resultado['success']
            ];
        }

        $exitosas = count(array_filter($resultados, function($r) { return $r['success']; }));

        $this->jsonResponse([
            'success' => true,
            'total' => count($ids),
            'exitosas' => $exitosas,
            'resultados' => $resultados
        ]);
    }

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Agrupa autorizaciones por requisición
     * 
     * @param array $autorizaciones Autorizaciones
     * @return array
     */
    private function agruparPorRequisicion($autorizaciones)
    {
        $agrupadas = [];

        foreach ($autorizaciones as $auth) {
            $ordenId = $auth['orden_compra_id'];
            
            if (!isset($agrupadas[$ordenId])) {
                $agrupadas[$ordenId] = [
                    'orden' => OrdenCompra::find($ordenId),
                    'autorizaciones' => []
                ];
            }

            $agrupadas[$ordenId]['autorizaciones'][] = $auth;
        }

        return array_values($agrupadas);
    }

    // ========================================================================
    // AUTORIZACIONES ESPECIALES
    // ========================================================================

    /**
     * Lista autorizaciones especiales de forma de pago pendientes
     * 
     * @return void
     */
    public function pendientesAutorizacionPago()
    {
        $usuarioEmail = $this->getUsuarioEmail();
        
        // Verificar que es autorizador de forma de pago
        if (!$this->autorizacionService->esAutorizadorPago($usuarioEmail)) {
            Redirect::to('/dashboard')
                ->withError('No tienes permisos de autorizador de forma de pago')
                ->send();
        }

        // Obtener autorizaciones pendientes
        $autorizacionesPendientes = $this->autorizacionService->getAutorizacionesPendientesPago($usuarioEmail);

        View::render('autorizaciones/autorizacion_pago', [
            'autorizaciones_pendientes' => $autorizacionesPendientes,
            'title' => 'Autorizaciones Pendientes - Forma de Pago'
        ]);
    }

    /**
     * Lista autorizaciones especiales de cuenta contable pendientes
     * 
     * @return void
     */
    public function pendientesAutorizacionCuenta()
    {
        $usuarioEmail = $this->getUsuarioEmail();
        
        // Verificar que es autorizador de cuenta contable
        if (!$this->autorizacionService->esAutorizadorCuenta($usuarioEmail)) {
            Redirect::to('/dashboard')
                ->withError('No tienes permisos de autorizador de cuenta contable')
                ->send();
        }

        // Obtener autorizaciones pendientes
        $autorizacionesPendientes = $this->autorizacionService->getAutorizacionesPendientesCuenta($usuarioEmail);

        View::render('autorizaciones/autorizacion_cuenta', [
            'autorizaciones_pendientes' => $autorizacionesPendientes,
            'title' => 'Autorizaciones Pendientes - Cuenta Contable'
        ]);
    }

    /**
     * Aprueba una autorización especial de forma de pago
     * 
     * @param int $id ID del flujo de autorización
     * @return void
     */
    public function aprobarAutorizacionPago($id)
    {
        // Para peticiones AJAX
        if ($this->isAjaxRequest()) {
            // Limpiar output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            error_reporting(0);
            
            ob_start();
            
            $response = [
                'success' => false,
                'error' => 'Error desconocido'
            ];
            
            try {
                // Validar CSRF
                if (!$this->validateCSRF()) {
                    $response = [
                        'success' => false,
                        'error' => 'Token de seguridad inválido'
                    ];
                } else {
                    $comentario = $_POST['comentario'] ?? '';
                    $usuarioEmail = $this->getUsuarioEmail();

                    // Verificar permisos
                    if (!$this->autorizacionService->esAutorizadorPago($usuarioEmail)) {
                        $response = [
                            'success' => false,
                            'error' => 'No tienes permisos de autorizador de forma de pago'
                        ];
                    } else {
                        $resultado = $this->autorizacionService->aprobarAutorizacionPago($id, $usuarioEmail, $comentario);
                        $response = $resultado;
                    }
                }
            } catch (\Exception $e) {
                error_log("Error en aprobarAutorizacionPago AJAX: " . $e->getMessage());
                $response = [
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ];
            }
            
            $this->sendAjaxResponse($response);
            return;
        }

        // Para peticiones no AJAX
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        $usuarioEmail = $this->getUsuarioEmail();
        if (!$this->autorizacionService->esAutorizadorPago($usuarioEmail)) {
            Redirect::back()
                ->withError('No tienes permisos de autorizador de forma de pago')
                ->send();
        }

        $comentario = $_POST['comentario'] ?? '';
        $resultado = $this->autorizacionService->aprobarAutorizacionPago($id, $usuarioEmail, $comentario);

        if ($resultado['success']) {
            Redirect::to('/autorizaciones/pago')
                ->withSuccess('Autorización de forma de pago aprobada exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError($resultado['error'])
                ->send();
        }
    }

    /**
     * Rechaza una autorización especial de forma de pago
     * 
     * @param int $id ID del flujo de autorización
     * @return void
     */
    public function rechazarAutorizacionPago($id)
    {
        if (!$this->validateCSRF()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Token de seguridad inválido'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        $usuarioEmail = $this->getUsuarioEmail();
        if (!$this->autorizacionService->esAutorizadorPago($usuarioEmail)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'No tienes permisos de autorizador de forma de pago'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('No tienes permisos de autorizador de forma de pago')
                ->send();
        }

        $motivo = $_POST['motivo'] ?? '';
        if (empty($motivo)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Debes especificar el motivo del rechazo'
                ], 400);
                return;
            }
            Redirect::back()
                ->withError('Debes especificar el motivo del rechazo')
                ->withInput()
                ->send();
        }

        $resultado = $this->autorizacionService->rechazarAutorizacionPago($id, $usuarioEmail, $motivo);

        if ($this->isAjaxRequest()) {
            $this->jsonResponse($resultado);
        } else {
            if ($resultado['success']) {
                Redirect::to('/autorizaciones/pago')
                    ->withSuccess('Autorización de forma de pago rechazada')
                    ->send();
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->send();
            }
        }
    }

    /**
     * Aprueba una autorización especial de cuenta contable
     * 
     * @param int $id ID del flujo de autorización
     * @return void
     */
    public function aprobarAutorizacionCuenta($id)
    {
        // Para peticiones AJAX
        if ($this->isAjaxRequest()) {
            // Limpiar output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            error_reporting(0);
            
            ob_start();
            
            $response = [
                'success' => false,
                'error' => 'Error desconocido'
            ];
            
            try {
                // Validar CSRF
                if (!$this->validateCSRF()) {
                    $response = [
                        'success' => false,
                        'error' => 'Token de seguridad inválido'
                    ];
                } else {
                    $comentario = $_POST['comentario'] ?? '';
                    $usuarioEmail = $this->getUsuarioEmail();

                    // Verificar permisos
                    if (!$this->autorizacionService->esAutorizadorCuenta($usuarioEmail)) {
                        $response = [
                            'success' => false,
                            'error' => 'No tienes permisos de autorizador de cuenta contable'
                        ];
                    } else {
                        $resultado = $this->autorizacionService->aprobarAutorizacionCuenta($id, $usuarioEmail, $comentario);
                        $response = $resultado;
                    }
                }
            } catch (\Exception $e) {
                error_log("Error en aprobarAutorizacionCuenta AJAX: " . $e->getMessage());
                $response = [
                    'success' => false,
                    'error' => 'Error interno del servidor'
                ];
            }
            
            $this->sendAjaxResponse($response);
            return;
        }

        // Para peticiones no AJAX
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        $usuarioEmail = $this->getUsuarioEmail();
        if (!$this->autorizacionService->esAutorizadorCuenta($usuarioEmail)) {
            Redirect::back()
                ->withError('No tienes permisos de autorizador de cuenta contable')
                ->send();
        }

        $comentario = $_POST['comentario'] ?? '';
        $resultado = $this->autorizacionService->aprobarAutorizacionCuenta($id, $usuarioEmail, $comentario);

        if ($resultado['success']) {
            Redirect::to('/autorizaciones/cuenta')
                ->withSuccess('Autorización de cuenta contable aprobada exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError($resultado['error'])
                ->send();
        }
    }

    /**
     * Rechaza una autorización especial de cuenta contable
     * 
     * @param int $id ID del flujo de autorización
     * @return void
     */
    public function rechazarAutorizacionCuenta($id)
    {
        if (!$this->validateCSRF()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Token de seguridad inválido'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        $usuarioEmail = $this->getUsuarioEmail();
        if (!$this->autorizacionService->esAutorizadorCuenta($usuarioEmail)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'No tienes permisos de autorizador de cuenta contable'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('No tienes permisos de autorizador de cuenta contable')
                ->send();
        }

        $motivo = $_POST['motivo'] ?? '';
        if (empty($motivo)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Debes especificar el motivo del rechazo'
                ], 400);
                return;
            }
            Redirect::back()
                ->withError('Debes especificar el motivo del rechazo')
                ->withInput()
                ->send();
        }

        $resultado = $this->autorizacionService->rechazarAutorizacionCuenta($id, $usuarioEmail, $motivo);

        if ($this->isAjaxRequest()) {
            $this->jsonResponse($resultado);
        } else {
            if ($resultado['success']) {
                Redirect::to('/autorizaciones/cuenta')
                    ->withSuccess('Autorización de cuenta contable rechazada')
                    ->send();
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->send();
            }
        }
    }

    /**
     * Verifica si un usuario es revisor basándose en su email
     * 
     * @param string $usuarioEmail Email del usuario
     * @return bool
     */
    private function isRevisorPorEmail($usuarioEmail)
    {
        // Lista de emails que siempre son revisores
        $revisoresPorDefecto = [
            'bgutierrez@sp.iga.edu',
            'bgutierrez@iga.edu',
            'admin@iga.edu',
            // Agregar más emails según sea necesario
        ];
        
        // Verificar si está en la lista de revisores por defecto
        if (in_array($usuarioEmail, $revisoresPorDefecto)) {
            return true;
        }
        
        // Verificar si es administrador (los admins suelen ser revisores)
        if ($this->isAdmin()) {
            return true;
        }
        
        // Verificar por dominio (ejemplo: todos los @sp.iga.edu)
        if (strpos($usuarioEmail, '@sp.iga.edu') !== false) {
            return true;
        }
        
        return false;
    }
}
