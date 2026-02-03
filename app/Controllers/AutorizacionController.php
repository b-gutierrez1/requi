<?php
/**
 * AutorizacionController
 * 
 * Controlador para gestiÃ³n de autorizaciones de requisiciones.
 * Maneja revisiÃ³n, aprobaciÃ³n y rechazo en todos los niveles.
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
use App\Models\Requisicion;
use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionFlujoAdaptador;
use App\Repositories\AutorizacionCentroRepository;

class AutorizacionController extends Controller
{
    /**
     * Servicio de autorizaciÃ³n
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
     * Repositorio de autorizaciones de centro
     * 
     * @var AutorizacionCentroRepository
     */
    private $autorizacionCentroRepo;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->autorizacionService = new AutorizacionService();
        $this->requisicionService = new RequisicionService();
        $this->autorizacionCentroRepo = new AutorizacionCentroRepository();
    }

    // ========================================================================
    // LISTADO Y VISUALIZACIÃ“N
    // ========================================================================

    /**
     * Lista las autorizaciones pendientes del usuario
     * 
     * @return void
     */
    public function index()
    {
        $usuarioEmail = $this->requireUsuarioEmail();
        error_log("AutorizacionController::index() - Usuario email: $usuarioEmail");
        error_log("AutorizacionController::index() - Es revisor: " . (Session::isRevisor() ? 'SÃ' : 'NO'));
        
        // Obtener requisiciones pendientes de revisiÃ³n (para revisores)
        $requisicionesPendientesRevision = [];
        $esRevisor = Session::isRevisor() || $this->isRevisorPorEmail($usuarioEmail);
        error_log("=== DEBUG REVISIÓN ===");
        error_log("Es revisor: " . ($esRevisor ? 'SÍ' : 'NO'));
        if ($esRevisor) {
            $requisicionesPendientesRevision = $this->autorizacionService->getRequisicionesPendientesRevision();
            error_log("Requisiciones pendientes de revisión encontradas: " . count($requisicionesPendientesRevision));
            error_log("Requisiciones: " . json_encode($requisicionesPendientesRevision));
        }
        
        // Obtener autorizaciones pendientes (para autorizadores por centro de costo)
        $autorizacionesPendientes = $this->autorizacionService->getAutorizacionesPendientes($usuarioEmail);
        
        // Obtener TODAS las autorizaciones unificadas (centro + especiales + respaldos)
        $todasAutorizaciones = $this->autorizacionService->getTodasAutorizacionesPendientes($usuarioEmail);
        
        error_log("=== TODAS LAS AUTORIZACIONES OBTENIDAS ===");
        error_log("Total autorizaciones: " . count($todasAutorizaciones));
        foreach ($todasAutorizaciones as $i => $auth) {
            error_log("[$i] Tipo: " . ($auth['tipo'] ?? $auth['tipo_flujo']) . ", ID: " . $auth['id'] . ", Estado: " . ($auth['estado'] ?? 'N/A') . ", Email: " . ($auth['autorizador_email'] ?? 'N/A'));
        }
        
        // Separar por tipo para compatibilidad con vistas existentes
        $autorizacionesPendientesPago = array_filter($todasAutorizaciones, fn($a) => $a['tipo_flujo'] === 'forma_pago');
        $autorizacionesPendientesCuenta = array_filter($todasAutorizaciones, fn($a) => $a['tipo_flujo'] === 'cuenta_contable');
        
        // Obtener informaciÃ³n del tipo de autorizador
        $tipoAutorizador = $this->autorizacionService->getTipoAutorizador($usuarioEmail);
        
        // Verificar si es autorizador de respaldo
        $esRespaldo = $this->autorizacionService->esAutorizadorRespaldo($usuarioEmail);
        
        error_log("=== AUTORIZACIONES PENDIENTES EN CONTROLADOR ===");
        error_log("Centros de costo: " . count($autorizacionesPendientes));
        error_log("Forma de pago: " . count($autorizacionesPendientesPago));
        error_log("Cuenta contable: " . count($autorizacionesPendientesCuenta));
        foreach ($autorizacionesPendientes as $i => $auth) {
            $ordenLogId = $auth['orden_id'] ?? $auth['requisicion_id'] ?? $auth['requisicion_id'] ?? 'N/A';
            $centroLog = $auth['centro_nombre'] ?? 'Centro no definido';
            $proveedorLog = $auth['nombre_razon_social'] ?? 'Proveedor no definido';
            error_log("[$i] Orden {$ordenLogId}: {$centroLog} - {$proveedorLog}");
        }

        // Obtener informaciÃ³n adicional mejorada (con manejo de errores)
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
        //     error_log("Error obteniendo informaciÃ³n adicional: " . $e->getMessage());
        //     // Continuar sin las funcionalidades avanzadas
        // }

        // Ordenar todos los arrays de mayor a menor (por ID de requisición)
        usort($requisicionesPendientesRevision, function($a, $b) {
            $idA = is_object($a) ? $a->id : ($a['id'] ?? 0);
            $idB = is_object($b) ? $b->id : ($b['id'] ?? 0);
            return $idB - $idA; // Descendente
        });
        
        usort($autorizacionesPendientes, function($a, $b) {
            $idA = $a['requisicion_id'] ?? $a['orden_id'] ?? $a['id'] ?? 0;
            $idB = $b['requisicion_id'] ?? $b['orden_id'] ?? $b['id'] ?? 0;
            return $idB - $idA; // Descendente
        });
        
        usort($autorizacionesPendientesPago, function($a, $b) {
            $idA = $a['requisicion_id'] ?? $a['orden_id'] ?? $a['id'] ?? 0;
            $idB = $b['requisicion_id'] ?? $b['orden_id'] ?? $b['id'] ?? 0;
            return $idB - $idA; // Descendente
        });
        
        usort($autorizacionesPendientesCuenta, function($a, $b) {
            $idA = $a['requisicion_id'] ?? $a['orden_id'] ?? $a['id'] ?? 0;
            $idB = $b['requisicion_id'] ?? $b['orden_id'] ?? $b['id'] ?? 0;
            return $idB - $idA; // Descendente
        });
        
        usort($todasAutorizaciones, function($a, $b) {
            $idA = $a['requisicion_id'] ?? $a['orden_id'] ?? $a['id'] ?? 0;
            $idB = $b['requisicion_id'] ?? $b['orden_id'] ?? $b['id'] ?? 0;
            return $idB - $idA; // Descendente
        });

        // Combinar todas las listas
        $totalPendientes = count($requisicionesPendientesRevision) + count($autorizacionesPendientes) + 
                          count($autorizacionesPendientesPago) + count($autorizacionesPendientesCuenta);

        View::render('autorizaciones/index', [
            'requisiciones_pendientes_revision' => $requisicionesPendientesRevision,
            'autorizaciones_pendientes' => $autorizacionesPendientes,
            'autorizaciones_pendientes_pago' => $autorizacionesPendientesPago,
            'autorizaciones_pendientes_cuenta' => $autorizacionesPendientesCuenta,
            'todas_autorizaciones' => $todasAutorizaciones,
            'flujos_por_vencer' => $flujosPorVencer,
            'flujos_vencidos' => $flujosVencidos,
            'flujos_urgentes' => $flujosUrgentes,
            'estadisticas_generales' => $estadisticasGenerales,
            'total_pendientes' => $totalPendientes,
            'es_autorizador_pago' => $this->autorizacionService->esAutorizadorPago($usuarioEmail),
            'es_autorizador_cuenta' => $this->autorizacionService->esAutorizadorCuenta($usuarioEmail),
            'es_autorizador_respaldo' => $esRespaldo,
            'tipo_autorizador' => $tipoAutorizador,
            'es_revisor' => $esRevisor, // Para mostrar/ocultar sección de revisiones
            'title' => 'Mis Autorizaciones Pendientes'
        ]);
    }

    /**
     * Muestra el detalle de una autorizaciÃ³n
     * 
     * @param int $id ID de la requisiciÃ³n
     * @return void
     */
    public function show($id)
    {
        error_log("AutorizacionController::show() - ID recibido: $id");
        
        try {
            // Primero verificar si es una autorización especial
            $autorizacionEspecial = $this->esAutorizacionEspecial($id);
            
            if ($autorizacionEspecial) {
                error_log("AutorizacionController::show() - Es autorización especial tipo: " . $autorizacionEspecial['tipo']);
                $this->mostrarAutorizacionEspecial($autorizacionEspecial);
                return;
            }
            
            // Si no es especial, manejar como requisición normal
            // Obtener requisiciÃ³n completa
            $requisicion = $this->requisicionService->getRequisicionCompleta($id);

            if (!$requisicion) {
                error_log("AutorizacionController::show() - Requisición NO encontrada con ID: $id");
                Redirect::to('/autorizaciones')
                    ->withError('RequisiciÃ³n no encontrada')
                    ->send();
                return;
            }
            
            error_log("AutorizacionController::show() - Requisición encontrada correctamente");
        } catch (\Exception $e) {
            error_log("AutorizacionController::show() - Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Redirect::to('/autorizaciones')
                ->withError('Error al cargar la requisiciÃ³n: ' . $e->getMessage())
                ->send();
            return;
        }

        // âœ… USAR SISTEMA CENTRALIZADO - Verificar permisos segÃºn el estado real
        
        // Obtener estado real de la requisiciÃ³n
        $orden = $requisicion['orden'];
        $estadoReal = is_object($orden) ? $orden->getEstadoReal() : EstadoHelper::getEstado($id);
        
        $flujoTemp = $requisicion['flujo'] ?? AutorizacionFlujoAdaptador::porRequisicion($id);
        $estadoFlujo = $estadoReal; // Usar estado centralizado
        
        // Verificar permisos: permitir acceso más amplio a revisores y autorizadores
        $orden = $requisicion['orden'];
        $esDueÃ±o = (is_object($orden) ? $orden->usuario_id : $orden['usuario_id']) == $this->getUsuarioId();
        $esRevisor = $this->isRevisor() || $this->isRevisorPorEmail($this->requireUsuarioEmail());
        $esAutorizadorGeneral = $this->autorizacionService->esAutorizadorGeneral($this->requireUsuarioEmail());
        $puedeAutorizar = $this->autorizacionService->puedeAutorizar($this->requireUsuarioEmail(), $id);
        $esAdmin = $this->isAdmin();
        
        // Permitir acceso si:
        // 1. Es el dueño de la requisición
        // 2. Es revisor (puede ver cualquier requisición para referencia)
        // 3. Es autorizador general (puede ver cualquier requisición para contexto)
        // 4. Puede autorizar específicamente esta requisición
        // 5. Es administrador
        $tienePermisos = $esDueÃ±o || $esRevisor || $esAutorizadorGeneral || $puedeAutorizar || $esAdmin;
        $mensajeError = 'No tienes permisos para ver esta requisiciÃ³n';
        
        if (!$tienePermisos) {
            Redirect::to('/autorizaciones')
                ->withError($mensajeError)
                ->send();
        }

        // Obtener flujo y resumen completo
        $flujo = $requisicion['flujo'] ?? null;
        $flujoId = null;
        $resumenCompleto = null;
        $historialCambios = [];
        
        // Si no tenemos flujo, buscarlo directamente
        if (!$flujo) {
            $flujo = AutorizacionFlujoAdaptador::porRequisicion($id);
        }
        
        if ($flujo) {
            if (is_object($flujo)) {
                $flujoId = $flujo->id ?? ($flujo->requisicion_id ?? null);
            } elseif (is_array($flujo)) {
                $flujoId = $flujo['id'] ?? ($flujo['requisicion_id'] ?? null);
            }
            
            try {
                // Obtener resumen completo con nueva informaciÃ³n
                if ($flujoId !== null) {
                    $resumenCompleto = AutorizacionFlujoAdaptador::getResumenCompleto($flujoId);
                }
                
                // Obtener historial de cambios
                if ($flujoId !== null) {
                    $historialCambios = AutorizacionFlujoAdaptador::getHistorialCambios($flujoId);
                }
                
                // Establecer prioridad automÃ¡tica si no existe
                if ($flujoId !== null && $resumenCompleto && !$resumenCompleto['prioridad'] && $resumenCompleto['monto_total']) {
                    AutorizacionFlujoAdaptador::establecerPrioridad($flujoId, null, $resumenCompleto['monto_total']);
                    AutorizacionFlujoAdaptador::establecerFechaLimite($flujoId, $resumenCompleto['prioridad'] ?? 'normal');
                }
            } catch (\Exception $e) {
                error_log("Error obteniendo resumen completo: " . $e->getMessage());
                // Usar informaciÃ³n bÃ¡sica del flujo
                $resumenCompleto = $flujo;
            }
        }

        // Obtener progreso de autorizaciÃ³n
        $progreso = null;
        if ($flujoId !== null) {
            $progreso = $this->autorizacionService->getProgresoAutorizacion($flujoId);
        }

        // Obtener autorizaciones por centro de costo
        $autorizacionesCentro = [];
        // Usar el ID de la requisiciÃ³n directamente, no el flujoId
        $autorizacionesCentro = $this->autorizacionCentroRepo->getByRequisicion($id);

        // Preparar datos para la vista con informaciÃ³n mejorada
        $dataVista = [
            'requisicion' => $requisicion,
            'orden' => $requisicion['orden'],
            'items' => $requisicion['items'] ?? [], // v3.0: Ya no se usan items separados
            'distribucion' => $requisicion['distribuciones'] ?? [], // v3.0: distribuciones (plural)
            'distribuciones' => $requisicion['distribuciones'] ?? [], // Compatibilidad v3.0
            'flujo' => $flujo,
            'resumen_completo' => $resumenCompleto,
            'historial_cambios' => $historialCambios,
            'progreso' => $progreso,
            'historial' => $requisicion['historial'],
            'autorizaciones_centro' => $autorizacionesCentro,
            'title' => 'Autorizar RequisiciÃ³n #' . $id,
            'puede_revisar' => $this->isRevisor() || $this->isAdmin() || $this->isRevisorPorEmail($this->getUsuarioEmail()),
            'orden_id' => $id
        ];
        
        View::render('autorizaciones/show', $dataVista);
    }

    // ========================================================================
    // REVISIÃ“N (Nivel 1)
    // ========================================================================

    /**
     * Lista requisiciones pendientes de revisiÃ³n
     * 
     * @return void
     */
    public function pendientesRevision()
    {
        // Verificar que es revisor (sesión, admin o email)
        if (!$this->isRevisor() && !$this->isAdmin() && !$this->isRevisorPorEmail($this->getUsuarioEmail())) {
            Redirect::to('/dashboard')
                ->withError('No tienes permisos de revisor')
                ->send();
        }

        // Obtener requisiciones pendientes de revisiÃ³n
        $requisiciones = $this->autorizacionService->getRequisicionesPendientesRevision();

        View::render('autorizaciones/revision', [
            'requisiciones' => $requisiciones,
            'title' => 'Requisiciones Pendientes de RevisiÃ³n'
        ]);
    }

    /**
     * Aprueba una requisiciÃ³n en revisiÃ³n
     * 
     * @param int $id ID de la requisiciÃ³n
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
                error_log("=== INICIO APROBACIÃ“N REVISIÃ“N ===");
                error_log("ID requisiciÃ³n: $id");
                error_log("Usuario actual: " . $this->requireUsuarioEmail());
                error_log("Es AJAX: " . ($this->isAjaxRequest() ? 'SÃ' : 'NO'));
                
                // Validaciones bÃ¡sicas
                if (!$this->validateCSRF()) {
                    $response = [
                        'success' => false,
                        'error' => 'Token de seguridad invÃ¡lido'
                    ];
                } elseif (!$this->isRevisor() && !$this->isAdmin() && !$this->isRevisorPorEmail($this->getUsuarioEmail())) {
                    // Usuario no tiene permisos de revisor
                    error_log("=== VALIDACIÃ“N PERMISOS REVISOR ===");
                    error_log("Usuario email: $userEmail");
                    error_log("Session::isRevisor(): " . (Session::isRevisor() ? 'SÃ' : 'NO'));
                    error_log("Session::isAdmin(): " . (Session::isAdmin() ? 'SÃ' : 'NO'));
                    error_log("Session::isAuthenticated(): " . (Session::isAuthenticated() ? 'SÃ' : 'NO'));
                    error_log("Datos de usuario: " . print_r($user, true));
                    error_log("Variables de sesiÃ³n directas:");
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
                    // Ejecutar aprobaciÃ³n
                    $comentario = $_POST['comentario'] ?? '';
                    $usuarioId = $this->getUsuarioId();
                    
                    // Ejecutar aprobaciÃ³n real
                    $resultado = $this->autorizacionService->aprobarRevision($id, $usuarioId, $comentario);
                    
                    if (is_array($resultado) && isset($resultado['success'])) {
                        $response = $resultado;
                    } else {
                        $response = [
                            'success' => false,
                            'error' => 'Respuesta invÃ¡lida del servicio'
                        ];
                    }
                }
                
            } catch (\Exception $e) {
                error_log("Error en aprobarRevision AJAX: " . $e->getMessage());
                error_log("Archivo: " . $e->getFile() . " LÃ­nea: " . $e->getLine());
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

        // Verificar que es revisor (sesión, admin o email)
        if (!$this->isRevisor() && !$this->isAdmin() && !$this->isRevisorPorEmail($this->getUsuarioEmail())) {
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
                    ->withSuccess('RequisiciÃ³n aprobada exitosamente')
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
     * Rechaza una requisiciÃ³n en revisiÃ³n
     * 
     * @param int $id ID de la requisiciÃ³n
     * @return void
     */
    public function rechazarRevision($id)
    {
        // Validar CSRF
        if (!$this->validateCSRF()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Token de seguridad invÃ¡lido'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('Token de seguridad invÃ¡lido')
                ->send();
        }

        // Verificar que es revisor (sesión, admin o email)
        if (!$this->isRevisor() && !$this->isAdmin() && !$this->isRevisorPorEmail($this->getUsuarioEmail())) {
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

        // Responder segÃºn el tipo de peticiÃ³n
        if ($this->isAjaxRequest()) {
            $this->jsonResponse($resultado);
        } else {
            if ($resultado['success']) {
                Redirect::to('/autorizaciones/revision')
                    ->withSuccess('RequisiciÃ³n rechazada')
                    ->send();
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->send();
            }
        }
    }

    // ========================================================================
    // AUTORIZACIÃ“N POR CENTRO DE COSTO (Nivel 4)
    // ========================================================================


    /**
     * Autoriza un centro de costo
     * 
     * @param int $id ID de la autorizaciÃ³n de centro de costo
     * @return void
     */
    public function autorizarCentro($id)
    {
        // Para peticiones AJAX, manejo completamente separado
        if ($this->isAjaxRequest()) {
            error_log("=== INICIO autorizarCentro AJAX ===");
            error_log("Authorization ID: $id");
            error_log("User email: " . $this->requireUsuarioEmail());
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
                        'error' => 'Token de seguridad invÃ¡lido'
                    ];
                } else {
                    $comentario = $_POST['comentario'] ?? '';
                    $usuarioId = $this->getUsuarioId();

                    // Obtener la autorizaciÃ³n actual
                    $autorizacion = $this->autorizacionCentroRepo->findById($id);
                    if (!$autorizacion) {
                        $response = [
                            'success' => false,
                            'error' => 'AutorizaciÃ³n no encontrada'
                        ];
                    } else {
                        // En v3.0, usamos directamente requisicion_id (no necesitamos autorizacion_flujo_id)
                        $ordenId = is_object($autorizacion) ? $autorizacion->requisicion_id : $autorizacion['requisicion_id'];
                        
                        // Si necesitamos el flujo ID, lo buscamos por separado
                        $stmt = \App\Models\Model::getConnection()->prepare("SELECT id FROM autorizacion_flujo WHERE requisicion_id = ?");
                        $stmt->execute([$ordenId]);
                        $flujoData = $stmt->fetch(\PDO::FETCH_ASSOC);
                        $flujoId = $flujoData ? $flujoData['id'] : $ordenId;
                        $autorizadorEmail = is_object($autorizacion) ? $autorizacion->autorizador_email : $autorizacion['autorizador_email'];

                        if ($this->autorizacionService->existenAutorizacionesEspecialesPendientes((int)$ordenId)) {
                            $response = [
                                'success' => false,
                                'error' => 'AÃºn existen autorizaciones especiales pendientes. Deben completarse antes de autorizar los centros de costo.'
                            ];
                            $this->sendAjaxResponse($response);
                            return;
                        }

                        // Obtener todas las autorizaciones pendientes del mismo autorizador en este flujo
                        $autorizacionesPendientes = $this->autorizacionCentroRepo->getByRequisicion($ordenId);
                        
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

                        error_log("=== INICIANDO AUTORIZACIÃ“N DE CENTROS ===");
                        error_log("Total centros a autorizar: " . count($autorizacionesAAutorizar));
                        
                        foreach ($autorizacionesAAutorizar as $i => $authPendiente) {
                            error_log("=== AUTORIZANDO CENTRO [$i] ===");
                            error_log("ID: {$authPendiente['id']}, Centro: {$authPendiente['centro_costo_id']}");
                            
                            $usuarioEmail = $this->requireUsuarioEmail();
                            error_log("Usuario email obtenido: $usuarioEmail");
                            
                            error_log("=== LLAMANDO autorizarCentroCosto ===");
                            try {
                                $resultado = $this->autorizacionService->autorizarCentroCosto($authPendiente['id'], $usuarioEmail, $comentario);
                                error_log("=== RESULTADO autorizarCentroCosto ===");
                                error_log("Resultado: " . json_encode($resultado));
                            } catch (\Exception $e) {
                                error_log("EXCEPCIÃ“N en autorizarCentroCosto: " . $e->getMessage());
                                error_log("Archivo: " . $e->getFile() . " LÃ­nea: " . $e->getLine());
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
                        
                        error_log("=== FIN AUTORIZACIÃ“N DE CENTROS ===");

                        // FORZAR verificaciÃ³n y completado del flujo
                        try {
                            error_log("=== FORZANDO VERIFICACIÃ“N DEL FLUJO $flujoId ===");
                            $verificacionResultado = $this->autorizacionService->verificarYCompletarFlujo($flujoId);
                            error_log("Resultado de verificaciÃ³n forzada: " . ($verificacionResultado ? 'Ã‰XITO' : 'FALLÃ“'));
                        } catch (\Exception $e) {
                            error_log("Error en verificaciÃ³n forzada: " . $e->getMessage());
                        }
                        
                        // âœ… USAR SISTEMA CENTRALIZADO - Obtener estado real actualizado
                        $flujoActualizado = AutorizacionFlujoAdaptador::porRequisicion($flujoId);
                        if ($flujoActualizado) {
                            $ordenId = is_object($flujoActualizado) ? $flujoActualizado->requisicion_id : $flujoActualizado['requisicion_id'];
                            $response['flujo_estado'] = EstadoHelper::getEstado($ordenId); // Estado real centralizado
                            $response['orden_id'] = $ordenId;
                            
                            error_log("Estado final del flujo despuÃ©s de autorizaciÃ³n: {$response['flujo_estado']}");
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
                ->withError('Token de seguridad invÃ¡lido')
                ->send();
        }

        $comentario = $_POST['comentario'] ?? '';
        $usuarioId = $this->getUsuarioId();

        try {
            // Obtener la autorizaciÃ³n actual
            $autorizacion = $this->autorizacionCentroRepo->findById($id);
            if (!$autorizacion) {
                Redirect::back()
                    ->withError('AutorizaciÃ³n no encontrada')
                    ->send();
            }

            // En v3.0, usamos directamente requisicion_id (no necesitamos autorizacion_flujo_id)
            $ordenId = is_object($autorizacion) ? $autorizacion->requisicion_id : $autorizacion['requisicion_id'];
            
            // Si necesitamos el flujo ID, lo buscamos por separado
            $stmt = \App\Models\Model::getConnection()->prepare("SELECT id FROM autorizacion_flujo WHERE requisicion_id = ?");
            $stmt->execute([$ordenId]);
            $flujoData = $stmt->fetch(\PDO::FETCH_ASSOC);
            $flujoId = $flujoData ? $flujoData['id'] : $ordenId;

            if ($this->autorizacionService->existenAutorizacionesEspecialesPendientes((int)$ordenId)) {
                Redirect::back()
                    ->withError('AÃºn existen autorizaciones especiales pendientes. Deben completarse antes de autorizar los centros de costo.')
                    ->send();
                return;
            }
            $autorizadorEmail = is_object($autorizacion) ? $autorizacion->autorizador_email : $autorizacion['autorizador_email'];

            // Obtener todas las autorizaciones pendientes del mismo autorizador en este flujo
            $autorizacionesPendientes = $this->autorizacionCentroRepo->getByRequisicion($ordenId);
            
            $autorizacionesAAutorizar = [];
            foreach ($autorizacionesPendientes as $auth) {
                if ($auth['autorizador_email'] === $autorizadorEmail && $auth['estado'] === 'pendiente') {
                    $autorizacionesAAutorizar[] = $auth;
                }
            }

            // Autorizar todas las que corresponden a este autorizador
            $exitosas = 0;
            $usuarioEmail = $this->requireUsuarioEmail();
            foreach ($autorizacionesAAutorizar as $authPendiente) {
                $resultado = $this->autorizacionService->autorizarCentroCosto($authPendiente['id'], $usuarioEmail, $comentario);
                if ($resultado['success']) {
                    $exitosas++;
                }
            }

            // FORZAR verificaciÃ³n y completado del flujo
            try {
                error_log("=== FORZANDO VERIFICACIÃ“N DEL FLUJO $flujoId ===");
                $verificacionResultado = $this->autorizacionService->verificarYCompletarFlujo($flujoId);
                error_log("Resultado de verificaciÃ³n forzada: " . ($verificacionResultado ? 'Ã‰XITO' : 'FALLÃ“'));
            } catch (\Exception $e) {
                error_log("Error en verificaciÃ³n forzada: " . $e->getMessage());
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
     * @param int $id ID de la autorizaciÃ³n de centro de costo
     * @return void
     */
    public function rechazarCentro($id)
    {
        // Validar CSRF
        if (!$this->validateCSRF()) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Token de seguridad invÃ¡lido'
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

        $autorizacion = $this->autorizacionCentroRepo->findById($id);
        if (!$autorizacion) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'AutorizaciÃ³n no encontrada'
                ], 404);
            } else {
                Redirect::back()
                    ->withError('AutorizaciÃ³n no encontrada')
                    ->send();
            }
            return;
        }

        $ordenId = $autorizacion['requisicion_id'] ?? null;
        if ($ordenId && $this->autorizacionService->existenAutorizacionesEspecialesPendientes((int)$ordenId)) {
            $mensajePendientes = 'AÃºn existen autorizaciones especiales pendientes. Deben completarse antes de autorizar o rechazar los centros de costo.';
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
                    ->withSuccess('Centro de costo rechazado. La requisiciÃ³n ha sido rechazada.')
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
     * API: Obtiene el progreso de una autorizaciÃ³n
     * 
     * @param int $id ID de la requisiciÃ³n
     * @return void
     */
    public function apiProgreso($id)
    {
        $flujo = AutorizacionFlujoAdaptador::porRequisicion($id);
        $flujoId = null;

        if (!$flujo) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Flujo no encontrado'
            ], 404);
            return;
        }

        if (is_object($flujo)) {
            $flujoId = $flujo->id ?? ($flujo->requisicion_id ?? null);
        } elseif (is_array($flujo)) {
            $flujoId = $flujo['id'] ?? ($flujo['requisicion_id'] ?? null);
        }

        $progreso = $this->autorizacionService->getProgresoAutorizacion($flujoId);

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
        $usuarioEmail = $this->requireUsuarioEmail();
        $count = $this->autorizacionService->contarPendientes($usuarioEmail);

        $this->jsonResponse([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * API: Autoriza mÃºltiples centros de costo a la vez
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

        $usuarioEmail = $this->requireUsuarioEmail();
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
    // MÃ‰TODOS PRIVADOS
    // ========================================================================

    /**
     * Agrupa autorizaciones por requisiciÃ³n
     * 
     * @param array $autorizaciones Autorizaciones
     * @return array
     */
    private function agruparPorRequisicion($autorizaciones)
    {
        $agrupadas = [];

        foreach ($autorizaciones as $auth) {
            $ordenId = $auth['requisicion_id'];
            
            if (!isset($agrupadas[$ordenId])) {
                $agrupadas[$ordenId] = [
                    'orden' => Requisicion::find($ordenId),
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
        $usuarioEmail = $this->requireUsuarioEmail();
        
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
        $usuarioEmail = $this->requireUsuarioEmail();
        
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
     * Aprueba una autorizaciÃ³n especial de forma de pago
     * 
     * @param int $id ID del flujo de autorizaciÃ³n
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
                        'error' => 'Token de seguridad invÃ¡lido'
                    ];
                } else {
                    $comentario = $_POST['comentario'] ?? '';
                    $usuarioEmail = $this->requireUsuarioEmail();

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
                ->withError('Token de seguridad invÃ¡lido')
                ->send();
        }

        $usuarioEmail = $this->requireUsuarioEmail();
        if (!$this->autorizacionService->esAutorizadorPago($usuarioEmail)) {
            Redirect::back()
                ->withError('No tienes permisos de autorizador de forma de pago')
                ->send();
        }

        $comentario = $_POST['comentario'] ?? '';
        $resultado = $this->autorizacionService->aprobarAutorizacionPago($id, $usuarioEmail, $comentario);

        if ($resultado['success']) {
            Redirect::to('/autorizaciones/pago')
                ->withSuccess('AutorizaciÃ³n de forma de pago aprobada exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError($resultado['error'])
                ->send();
        }
    }

    /**
     * Rechaza una autorizaciÃ³n especial de forma de pago
     * 
     * @param int $id ID del flujo de autorizaciÃ³n
     * @return void
     */
    public function rechazarAutorizacionPago($id)
    {
        if (!$this->validateCSRF()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Token de seguridad invÃ¡lido'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('Token de seguridad invÃ¡lido')
                ->send();
        }

        $usuarioEmail = $this->requireUsuarioEmail();
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
                    ->withSuccess('AutorizaciÃ³n de forma de pago rechazada')
                    ->send();
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->send();
            }
        }
    }

    /**
     * Aprueba una autorizaciÃ³n especial de cuenta contable
     * 
     * @param int $id ID del flujo de autorizaciÃ³n
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
                        'error' => 'Token de seguridad invÃ¡lido'
                    ];
                } else {
                    $comentario = $_POST['comentario'] ?? '';
                    $usuarioEmail = $this->requireUsuarioEmail();

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
                ->withError('Token de seguridad invÃ¡lido')
                ->send();
        }

        $usuarioEmail = $this->requireUsuarioEmail();
        if (!$this->autorizacionService->esAutorizadorCuenta($usuarioEmail)) {
            Redirect::back()
                ->withError('No tienes permisos de autorizador de cuenta contable')
                ->send();
        }

        $comentario = $_POST['comentario'] ?? '';
        $resultado = $this->autorizacionService->aprobarAutorizacionCuenta($id, $usuarioEmail, $comentario);

        if ($resultado['success']) {
            Redirect::to('/autorizaciones/cuenta')
                ->withSuccess('AutorizaciÃ³n de cuenta contable aprobada exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError($resultado['error'])
                ->send();
        }
    }

    /**
     * Rechaza una autorizaciÃ³n especial de cuenta contable
     * 
     * @param int $id ID del flujo de autorizaciÃ³n
     * @return void
     */
    public function rechazarAutorizacionCuenta($id)
    {
        if (!$this->validateCSRF()) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Token de seguridad invÃ¡lido'
                ], 403);
                return;
            }
            Redirect::back()
                ->withError('Token de seguridad invÃ¡lido')
                ->send();
        }

        $usuarioEmail = $this->requireUsuarioEmail();
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
                    ->withSuccess('AutorizaciÃ³n de cuenta contable rechazada')
                    ->send();
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->send();
            }
        }
    }


    /**
     * Verifica si el ID corresponde a una autorización especial
     * 
     * @param int $id ID a verificar
     * @return array|false Datos de la autorización especial o false
     */
    private function esAutorizacionEspecial($id)
    {
        try {
            $pdo = \App\Models\Model::getConnection();
            $stmt = $pdo->prepare("
                SELECT a.*, r.numero_requisicion, r.proveedor_nombre, r.monto_total, 
                       r.fecha_solicitud, u.azure_display_name as usuario_nombre
                FROM autorizaciones a
                INNER JOIN requisiciones r ON a.requisicion_id = r.id
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                WHERE a.id = ? AND a.tipo IN ('forma_pago', 'cuenta_contable')
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ?: false;
        } catch (\Exception $e) {
            error_log("Error verificando autorización especial: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Muestra la página de autorización especial
     * 
     * @param array $autorizacion Datos de la autorización
     */
    private function mostrarAutorizacionEspecial($autorizacion)
    {
        // Verificar permisos
        $usuarioEmail = $this->requireUsuarioEmail();
        
        // DEBUG: Agregar logging para verificar emails
        error_log("=== DEBUG PERMISOS AUTORIZACIÓN ESPECIAL ===");
        error_log("Usuario en sesión: " . ($usuarioEmail ?: 'NULL'));
        error_log("Autorizador esperado: " . ($autorizacion['autorizador_email'] ?: 'NULL'));
        error_log("Son iguales: " . ($autorizacion['autorizador_email'] === $usuarioEmail ? 'SÍ' : 'NO'));
        error_log("Estado autorización: " . ($autorizacion['estado'] ?: 'NULL'));
        
        if ($autorizacion['autorizador_email'] !== $usuarioEmail) {
            error_log("ERROR: Permisos denegados - emails no coinciden");
            Redirect::to('/autorizaciones')
                ->withError('No tienes permisos para autorizar esta solicitud')
                ->send();
            return;
        }
        
        error_log("✅ Permisos OK - permitir acceso");

        // Obtener requisición completa para mostrar detalles
        $requisicion = $this->requisicionService->getRequisicionCompleta($autorizacion['requisicion_id']);
        
        if (!$requisicion) {
            Redirect::to('/autorizaciones')
                ->withError('Error al cargar los datos de la requisición')
                ->send();
            return;
        }

        // Preparar datos para la vista
        $data = [
            'title' => 'Autorizar ' . ucfirst(str_replace('_', ' ', $autorizacion['tipo'])) . ' - Requisición #' . $autorizacion['numero_requisicion'],
            'autorizacion_especial' => $autorizacion,
            'tipo_autorizacion' => $autorizacion['tipo'],
            'requisicion' => $requisicion,
            'orden' => $requisicion['orden'],
            'distribucion' => $requisicion['distribuciones'] ?? [],
            'flujo' => $requisicion['flujo'],
            'historial' => $requisicion['historial'] ?? []
        ];

        View::render('autorizaciones/especial', $data);
    }
}
