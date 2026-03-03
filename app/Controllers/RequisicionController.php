<?php
/**
 * RequisicionController
 * 
 * Controlador principal para gestión de requisiciones.
 * Maneja el CRUD completo y visualización de requisiciones.
 * 
 * @package RequisicionesMVC\Controllers
 * @version 2.0
 */

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Session;
use App\Helpers\Redirect;
use App\Helpers\EstadoHelper;
use App\Helpers\ErrorLogger;
use App\Services\RequisicionService;
use App\Services\AutorizacionService;
use App\Models\Requisicion;
use App\Models\AutorizacionFlujo;
use App\Models\CentroCosto;
use App\Models\CuentaContable;
use App\Models\Ubicacion;
use App\Models\UnidadNegocio;

class RequisicionController extends Controller
{
    /**
     * Servicio de requisiciones
     * 
     * @var RequisicionService
     */
    private $requisicionService;

    /**
     * Servicio de autorización
     * 
     * @var AutorizacionService
     */
    private $autorizacionService;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->requisicionService = new RequisicionService(
            new AutorizacionService()
        );
        $this->autorizacionService = new AutorizacionService();
    }

    // ========================================================================
    // LISTADO Y VISUALIZACIÓN
    // ========================================================================

    /**
     * Lista las requisiciones del usuario
     * 
     * @return void
     */
    public function index()
    {
        $usuarioId = $this->getUsuarioId();
        
        // Obtener filtros
        $filtros = [
            'estado' => $_GET['estado'] ?? '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'busqueda' => $_GET['busqueda'] ?? ''
        ];
        
        // Parámetros de paginación
        $pagina = max(1, intval($_GET['pagina'] ?? 1));
        $porPagina = max(5, min(100, intval($_GET['por_pagina'] ?? 5))); // Requisiciones por página (5-100)

        // Obtener requisiciones filtradas
        $todasRequisiciones = $this->aplicarFiltros($usuarioId, $filtros);
        
        // Ordenar de mayor a menor (por ID descendente)
        usort($todasRequisiciones, function($a, $b) {
            $idA = is_object($a) ? $a->id : ($a['id'] ?? 0);
            $idB = is_object($b) ? $b->id : ($b['id'] ?? 0);
            return $idB - $idA; // Descendente
        });
        
        // Calcular paginación
        $totalRequisiciones = count($todasRequisiciones);
        $totalPaginas = max(1, ceil($totalRequisiciones / $porPagina));
        $pagina = min($pagina, $totalPaginas); // No exceder el total de páginas
        $offset = ($pagina - 1) * $porPagina;
        
        // Obtener solo las requisiciones de la página actual
        $requisiciones = array_slice($todasRequisiciones, $offset, $porPagina);

        View::render('requisiciones/index', [
            'requisiciones' => $requisiciones,
            'filtros' => $filtros,
            'paginacion' => [
                'pagina_actual' => $pagina,
                'total_paginas' => $totalPaginas,
                'total_requisiciones' => $totalRequisiciones,
                'por_pagina' => $porPagina
            ],
            'title' => 'Mis Requisiciones'
        ]);
    }

    /**
     * Muestra el detalle de una requisición
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function show($id)
    {
        // DEBUG temporal
        error_log("DEBUG: show() llamado con ID: " . $id);
        
        $requisicion = $this->requisicionService->getRequisicionCompleta($id);

        if (!$requisicion) {
            Redirect::to('/requisiciones')
                ->withError('Requisición no encontrada')
                ->send();
        }

        // ✅ USAR SISTEMA CENTRALIZADO - Verificar y actualizar estado si es necesario
        
        if ($requisicion['flujo']) {
            $flujoId = is_object($requisicion['flujo']) ? $requisicion['flujo']->id : $requisicion['flujo']['id'];
            $estadoReal = $requisicion['orden']->getEstadoReal(); // Estado real centralizado
            
            // Si está en pendiente_autorizacion, verificar si todas ya están autorizadas
            if ($estadoReal === 'pendiente_autorizacion') {
                error_log("Verificando si todas las autorizaciones están completas para flujo $flujoId");
                
                // Verificar y actualizar estado del flujo
                $this->autorizacionService->verificarYCompletarFlujo($flujoId);
                
                // Recargar el flujo después de la verificación
                $requisicion['flujo'] = $requisicion['orden']->autorizacionFlujo();
            }
        }

        // Verificar permisos: permitir acceso amplio a revisores y autorizadores
        $orden = $requisicion['orden'];
        $esDueño = (is_object($orden) ? $orden->usuario_id : $orden['usuario_id']) == $this->getUsuarioId();
        $esRevisor = $this->isRevisor() || $this->isRevisorPorEmail($this->getUsuarioEmail());
        $esAutorizadorGeneral = $this->autorizacionService->esAutorizadorGeneral($this->getUsuarioEmail());
        $puedeAutorizar = $this->autorizacionService->puedeAutorizar($this->getUsuarioEmail(), $id);
        $esAdmin = $this->isAdmin();
        
        // Permitir acceso si:
        // 1. Es el dueño de la requisición
        // 2. Es revisor (puede ver cualquier requisición para referencia)
        // 3. Es autorizador general (puede ver cualquier requisición para contexto)
        // 4. Puede autorizar específicamente esta requisición
        // 5. Es administrador
        $tienePermisos = $esDueño || $esRevisor || $esAutorizadorGeneral || $puedeAutorizar || $esAdmin;
        
        if (!$tienePermisos) {
            Redirect::to('/requisiciones')
                ->withError('No tienes permisos para ver esta requisición')
                ->send();
        }

        // Obtener información de autorizaciones pendientes para administradores y revisores
        $autorizacionesPendientes = [];
        if (($this->isAdmin() || $this->isRevisor()) && $requisicion['flujo']) {
            $autorizacionesPendientes = $this->autorizacionService->getAutorizacionesPendientesDetalle($id);
        }

        // Agregar headers para evitar caché de estados
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Cargar items y distribución
        $items = \App\Models\DetalleItem::porRequisicion($id);
        $distribucion = \App\Models\DistribucionGasto::porRequisicion($id);
        
        // Obtener catálogos para mostrar nombres completos
        $catalogos = $this->getCatalogos();

        View::render('requisiciones/show', [
            'requisicion' => $requisicion,
            'orden' => $orden,
            'items' => $items,
            'distribucion' => $distribucion,
            'distribuciones' => $distribucion, // Compatibilidad
            'facturas' => $requisicion['facturas'] ?? [], // v3.0: Tabla eliminada
            'archivos' => $requisicion['archivos'] ?? [], // v3.0: Tabla eliminada
            'flujo' => $requisicion['flujo'],
            'historial' => $requisicion['historial'],
            'autorizaciones_pendientes' => $autorizacionesPendientes,
            'autorizaciones' => $autorizacionesPendientes,
            'centros_costo' => $catalogos['centros_costo'] ?? [],
            'cuentas_contables' => $catalogos['cuentas_contables'] ?? [],
            'ubicaciones' => $catalogos['ubicaciones'] ?? [],
            'unidades_negocio' => $catalogos['unidades_negocio'] ?? [],
            'unidades_requirentes' => \App\Models\UnidadRequirente::activas(),
            'title' => 'Requisición #' . $id,
            'timestamp' => time() // Para anti-caché
        ]);
    }

    // ========================================================================
    // CREACIÓN
    // ========================================================================

    /**
     * Muestra el formulario de creación
     * 
     * @return void
     */
    public function create()
    {
        // Obtener catálogos para el formulario
        $catalogos = $this->getCatalogos();

        View::render('requisiciones/create', [
            'centros_costo' => $catalogos['centros_costo'] ?? [],
            'cuentas_contables' => $catalogos['cuentas_contables'] ?? [],
            'ubicaciones' => $catalogos['ubicaciones'] ?? [],
            'unidades_negocio' => $catalogos['unidades_negocio'] ?? [],
            'unidades_requirentes' => \App\Models\UnidadRequirente::activas(),
            'title' => 'Nueva Requisición'
        ]);
    }


    /**
     * Guarda una nueva requisición
     * 
     * @return void
     */
    public function store()
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
                // Validar CSRF
                if (!$this->validateCSRF()) {
                    $response = [
                        'success' => false,
                        'error' => 'Token de seguridad inválido'
                    ];
                } else {
                    $data = $_POST;
                    $usuarioId = $this->getUsuarioId();
                    
                    // Verificar que el usuario esté autenticado
                    if (!$usuarioId) {
                        // Log para diagnóstico
                        error_log("=== ERROR AUTENTICACIÓN ===");
                        error_log("Session ID: " . session_id());
                        error_log("Session data: " . json_encode($_SESSION ?? []));
                        error_log("User agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'));

                        http_response_code(401); // Código de no autorizado
                        $response = [
                            'success' => false,
                            'error' => 'Usuario no autenticado. Por favor, inicie sesión nuevamente.',
                            'redirect' => \App\Helpers\Redirect::url('/login')
                        ];
                    } else {
                        // Debug: Log de datos recibidos
                        error_log("=== DATOS RECIBIDOS EN STORE (AJAX) ===");
                        error_log("Usuario ID: " . $usuarioId);
                        error_log("Nombre/Razón Social: " . ($data['nombre_razon_social'] ?? 'VACÍO'));
                        error_log("Items count: " . (isset($data['items']) ? count($data['items']) : '0 o NO EXISTE'));
                        error_log("Distribución count: " . (isset($data['distribucion']) ? count($data['distribucion']) : '0 o NO EXISTE'));
                        
                        // Log detallado de items
                        if (isset($data['items'])) {
                            foreach ($data['items'] as $i => $item) {
                                error_log("Item $i: " . json_encode($item, JSON_UNESCAPED_UNICODE));
                            }
                        }
                        
                        // Log detallado de distribución  
                        if (isset($data['distribucion'])) {
                            foreach ($data['distribucion'] as $i => $dist) {
                                error_log("Distribución $i: " . json_encode($dist, JSON_UNESCAPED_UNICODE));
                            }
                        }
                        
                        error_log("POST keys: " . implode(', ', array_keys($data)));
                        error_log("POST completo: " . json_encode($data, JSON_UNESCAPED_UNICODE));

                        // Determinar el estado según el tipo de acción
                        $actionType = $_POST['action_type'] ?? 'enviar';
                        $estado = ($actionType === 'borrador') ? Requisicion::ESTADO_BORRADOR : Requisicion::ESTADO_PENDIENTE_REVISION;
                        
                        // Crear la requisición
                        $resultado = $this->requisicionService->crearRequisicion($data, $usuarioId, $estado);

                        if ($resultado['success']) {
                            // Manejar archivos adjuntos - DEBUG
                            error_log("=== DEBUG ARCHIVOS ADJUNTOS (AJAX) ===");
                            error_log("FILES array: " . json_encode($_FILES));
                            
                            if (isset($_FILES['archivos'])) {
                                error_log("FILES[archivos]: " . json_encode($_FILES['archivos']));
                                error_log("Name[0]: " . ($_FILES['archivos']['name'][0] ?? 'NO EXISTE'));
                                error_log("Empty check: " . (empty($_FILES['archivos']['name'][0]) ? 'TRUE' : 'FALSE'));
                            } else {
                                error_log("FILES[archivos] NO EXISTE");
                            }
                            
                            if (!empty($_FILES['archivos']['name'][0])) {
                                error_log("Llamando guardarArchivos...");
                                $archivoResultado = $this->requisicionService->guardarArchivos($resultado['orden_id'], $_FILES['archivos']);
                                error_log("Resultado guardarArchivos: " . json_encode($archivoResultado));
                            } else {
                                error_log("NO hay archivos para procesar");
                            }

                            // NOTA: El flujo de autorización ya se inicia automáticamente en RequisicionService::crearRequisicion()
                            // No es necesario (ni correcto) iniciarlo aquí de nuevo

                            $response = [
                                'success' => true,
                                'message' => 'Requisición creada exitosamente',
                                'orden_id' => $resultado['orden_id'],
                                'redirect_url' => Redirect::url('/requisiciones/' . $resultado['orden_id'])
                            ];
                        } else {
                            $response = [
                                'success' => false,
                                'error' => $resultado['error']
                            ];
                        }
                    } // Close authentication check
                }
            } catch (\Exception $e) {
                // Usar nuestro sistema de logging personalizado
                ErrorLogger::logRequisicionError(
                    'crear_requisicion_ajax',
                    $_POST ?? [],
                    $e->getMessage(),
                    [
                        'usuario_id' => $this->getUsuarioId(),
                        'usuario_email' => $this->getUsuarioEmail(),
                        'exception_file' => $e->getFile(),
                        'exception_line' => $e->getLine(),
                        'stack_trace' => $e->getTraceAsString()
                    ]
                );
                
                // Log de backup en PHP log
                error_log("Error en store AJAX: " . $e->getMessage());
                
                $response = [
                    'success' => false,
                    'error' => 'Error interno del servidor: ' . $e->getMessage()
                ];
            } catch (\Error $e) {
                // Capturar errores fatales también
                ErrorLogger::logException($e, 'crear_requisicion_ajax_fatal', [
                    'usuario_id' => $this->getUsuarioId(),
                    'usuario_email' => $this->getUsuarioEmail(),
                    'post_data' => $_POST ?? []
                ]);
                
                error_log("Error FATAL en store AJAX: " . $e->getMessage());
                
                $response = [
                    'success' => false,
                    'error' => 'Error fatal del servidor: ' . $e->getMessage()
                ];
            }
            
            // Enviar respuesta AJAX limpia
            $this->sendAjaxResponse($response);
            return;
        }

        // Para peticiones no AJAX, manejo normal
        // Validar CSRF
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->withInput()
                ->send();
        }

        $data = $_POST;
        $usuarioId = $this->getUsuarioId();
        
        // Verificar que el usuario esté autenticado
        if (!$usuarioId) {
            Redirect::to('/login')
                ->withError('Usuario no autenticado. Por favor, inicie sesión.')
                ->send();
            return;
        }

        // Determinar el estado según el tipo de acción  
        $actionType = $_POST['action_type'] ?? 'enviar';
        $estado = ($actionType === 'borrador') ? Requisicion::ESTADO_BORRADOR : Requisicion::ESTADO_PENDIENTE_REVISION;
        
        // Crear la requisición
        $resultado = $this->requisicionService->crearRequisicion($data, $usuarioId, $estado);

        if ($resultado['success']) {
            // Manejar archivos adjuntos - DEBUG
            error_log("=== DEBUG ARCHIVOS ADJUNTOS (NO-AJAX) ===");
            error_log("FILES array: " . json_encode($_FILES));
            
            if (isset($_FILES['archivos'])) {
                error_log("FILES[archivos]: " . json_encode($_FILES['archivos']));
                error_log("Name[0]: " . ($_FILES['archivos']['name'][0] ?? 'NO EXISTE'));
                error_log("Empty check: " . (empty($_FILES['archivos']['name'][0]) ? 'TRUE' : 'FALSE'));
            } else {
                error_log("FILES[archivos] NO EXISTE");
            }
            
            if (!empty($_FILES['archivos']['name'][0])) {
                error_log("Llamando guardarArchivos...");
                $archivoResultado = $this->requisicionService->guardarArchivos($resultado['orden_id'], $_FILES['archivos']);
                error_log("Resultado guardarArchivos: " . json_encode($archivoResultado));
            } else {
                error_log("NO hay archivos para procesar");
            }

            // NOTA: El flujo de autorización ya se inicia automáticamente en RequisicionService::crearRequisicion()
            // No es necesario (ni correcto) iniciarlo aquí de nuevo

            Redirect::to('/requisiciones/' . $resultado['orden_id'])
                ->withSuccess('Requisición creada exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError($resultado['error'])
                ->withInput($data)
                ->send();
        }
    }


    // ========================================================================
    // EDICIÓN
    // ========================================================================

    /**
     * Muestra el formulario de edición
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function edit($id)
    {
        $requisicion = $this->requisicionService->getRequisicionCompleta($id);

        if (!$requisicion) {
            Redirect::to('/requisiciones')
                ->withError('Requisición no encontrada')
                ->send();
        }

        // Verificar permisos
        if (!$this->requisicionService->puedeEditar($id, $this->getUsuarioId())) {
            Redirect::to('/requisiciones/' . $id)
                ->withError('No puedes editar esta requisición')
                ->send();
        }

        $catalogos = $this->getCatalogos();
        
        // Cargar items y distribución
        $requisicion['items'] = \App\Models\DetalleItem::porRequisicion($id);
        $requisicion['distribucion'] = \App\Models\DistribucionGasto::porRequisicion($id);

        View::render('requisiciones/edit', [
            'requisicion' => $requisicion,
            'centros_costo' => $catalogos['centros_costo'] ?? [],
            'cuentas_contables' => $catalogos['cuentas_contables'] ?? [],
            'ubicaciones' => $catalogos['ubicaciones'] ?? [],
            'unidades_negocio' => $catalogos['unidades_negocio'] ?? [],
            'unidades_requirentes' => \App\Models\UnidadRequirente::activas(),
            'title' => 'Editar Requisición #' . $id
        ]);
    }

    /**
     * Actualiza una requisición
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function update($id)
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

        $data = $_POST;
        $usuarioId = $this->getUsuarioId();

        $resultado = $this->requisicionService->editarRequisicion($id, $data, $usuarioId);

        if ($resultado['success']) {
            // Manejar nuevos archivos
            if (!empty($_FILES['archivos']['name'][0])) {
                $this->requisicionService->guardarArchivos($id, $_FILES['archivos']);
            }

            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Requisición actualizada exitosamente',
                    'redirect_url' => Redirect::url('/requisiciones/' . $id)
                ]);
            } else {
                Redirect::to('/requisiciones/' . $id)
                    ->withSuccess('Requisición actualizada exitosamente')
                    ->send();
            }
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => $resultado['error']
                ]);
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->withInput($data)
                    ->send();
            }
        }
    }

    // ========================================================================
    // ELIMINACIÓN
    // ========================================================================

    /**
     * Elimina una requisición
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function destroy($id)
    {
        // Validar CSRF
        if (!$this->validateCSRF()) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Token de seguridad inválido'
            ], 403);
            return;
        }

        $usuarioId = $this->getUsuarioId();
        $resultado = $this->requisicionService->eliminarRequisicion($id, $usuarioId);

        if ($this->isAjaxRequest()) {
            $this->jsonResponse($resultado);
        } else {
            if ($resultado['success']) {
                Redirect::to('/requisiciones')
                    ->withSuccess('Requisición eliminada exitosamente')
                    ->send();
            } else {
                Redirect::back()
                    ->withError($resultado['error'])
                    ->send();
            }
        }
    }

    // ========================================================================
    // ARCHIVOS ADJUNTOS
    // ========================================================================

    /**
     * Elimina un archivo adjunto
     * 
     * @param int $id ID del archivo
     * @return void
     */
    public function deleteArchivo($id)
    {
        $usuarioId = $this->getUsuarioId();
        $resultado = $this->requisicionService->eliminarArchivo($id, $usuarioId);

        $this->jsonResponse($resultado);
    }

    /**
     * Descarga un archivo adjunto
     * 
     * @param int $id ID del archivo
     * @return void
     */
    public function descargarArchivo($id)
    {
        try {
            $archivo = \App\Models\ArchivoAdjunto::find($id);
            
            if (!$archivo) {
                http_response_code(404);
                echo "Archivo no encontrado";
                return;
            }
            
            // Verificar que el archivo físico existe
            $rutaArchivo = $archivo->ruta_archivo;
            if (!file_exists($rutaArchivo)) {
                http_response_code(404);
                echo "El archivo físico no existe en el servidor";
                return;
            }
            
            // Verificar permisos (el usuario debe tener acceso a la requisición)
            $requisicion = \App\Models\Requisicion::find($archivo->requisicion_id);
            if (!$requisicion) {
                http_response_code(403);
                echo "No tienes permiso para acceder a este archivo";
                return;
            }
            
            // Enviar archivo para descarga
            $nombreOriginal = $archivo->nombre_original;
            $tipoMime = $archivo->tipo_mime ?: 'application/octet-stream';
            
            header('Content-Type: ' . $tipoMime);
            header('Content-Disposition: attachment; filename="' . $nombreOriginal . '"');
            header('Content-Length: ' . filesize($rutaArchivo));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            readfile($rutaArchivo);
            exit;
            
        } catch (\Exception $e) {
            error_log("Error descargando archivo: " . $e->getMessage());
            http_response_code(500);
            echo "Error al descargar el archivo";
        }
    }
    
    /**
     * Muestra un archivo adjunto en el navegador
     * 
     * @param int $id ID del archivo
     * @return void
     */
    public function verArchivo($id)
    {
        try {
            $archivo = \App\Models\ArchivoAdjunto::find($id);
            
            if (!$archivo) {
                http_response_code(404);
                echo "Archivo no encontrado";
                return;
            }
            
            // Verificar que el archivo físico existe
            $rutaArchivo = $archivo->ruta_archivo;
            if (!file_exists($rutaArchivo)) {
                http_response_code(404);
                echo "El archivo físico no existe en el servidor";
                return;
            }
            
            // Enviar archivo para visualización
            $tipoMime = $archivo->tipo_mime ?: 'application/octet-stream';
            
            header('Content-Type: ' . $tipoMime);
            header('Content-Disposition: inline; filename="' . $archivo->nombre_original . '"');
            header('Content-Length: ' . filesize($rutaArchivo));
            
            readfile($rutaArchivo);
            exit;
            
        } catch (\Exception $e) {
            error_log("Error mostrando archivo: " . $e->getMessage());
            http_response_code(500);
            echo "Error al mostrar el archivo";
        }
    }

    // ========================================================================
    // IMPRESIÓN Y EXPORTACIÓN
    // ========================================================================

    /**
     * Imprime una requisición
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function print($id)
    {
        $requisicion = $this->requisicionService->getRequisicionCompleta($id);

        if (!$requisicion) {
            Redirect::to('/requisiciones')
                ->withError('Requisición no encontrada')
                ->send();
        }

        // Verificar permisos: permitir acceso amplio a revisores y autorizadores
        $orden = $requisicion['orden'];
        $esDueño = $orden->usuario_id == $this->getUsuarioId();
        $esRevisor = $this->isRevisor() || $this->isRevisorPorEmail($this->getUsuarioEmail());
        $esAutorizadorGeneral = $this->autorizacionService->esAutorizadorGeneral($this->getUsuarioEmail());
        $puedeAutorizar = $this->autorizacionService->puedeAutorizar($this->getUsuarioEmail(), $id);
        $esAdmin = $this->isAdmin();
        
        $tienePermisos = $esDueño || $esRevisor || $esAutorizadorGeneral || $puedeAutorizar || $esAdmin;
        
        if (!$tienePermisos) {
            Redirect::to('/requisiciones')
                ->withError('No tienes permisos para ver esta requisición')
                ->send();
        }

        View::render('requisiciones/print', [
            'requisicion' => $requisicion,
            'orden' => $orden,
            'items' => $requisicion['items'],
            'distribucion' => $requisicion['distribucion'],
            'flujo' => $requisicion['flujo'],
        ], null); // Sin layout
    }

    /**
     * Exporta requisiciones a Excel
     * 
     * @return void
     */
    public function export()
    {
        // TODO: Implementar exportación a Excel
        $this->jsonResponse([
            'success' => false,
            'message' => 'Exportación no implementada aún'
        ], 501);
    }

    // ========================================================================
    // API ENDPOINTS
    // ========================================================================

    /**
     * API: Busca requisiciones
     * 
     * @return void
     */
    public function apiBuscar()
    {
        $termino = $_GET['q'] ?? '';
        $usuarioId = $this->getUsuarioId();

        if (strlen($termino) < 2) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'El término de búsqueda debe tener al menos 2 caracteres'
            ]);
            return;
        }

        $requisiciones = $this->requisicionService->buscar($termino);

        // Filtrar solo las del usuario (a menos que sea admin)
        if (!$this->isAdmin()) {
            $requisiciones = array_filter($requisiciones, function($req) use ($usuarioId) {
                return $req['usuario_id'] == $usuarioId;
            });
        }

        $this->jsonResponse([
            'success' => true,
            'results' => array_values($requisiciones)
        ]);
    }

    /**
     * API: Valida presupuesto
     * 
     * @return void
     */
    public function apiValidarPresupuesto()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        // TODO: Implementar validación de presupuesto
        $this->jsonResponse([
            'success' => true,
            'disponible' => true,
            'monto_disponible' => 10000
        ]);
    }

    /**
     * API: Busca cuentas contables
     * 
     * @return void
     */
    public function apiBuscarCuentas()
    {
        $termino = $_GET['q'] ?? '';
        
        if (strlen($termino) < 2) {
            $this->jsonResponse([]);
            return;
        }

        $cuentas = CuentaContable::buscar($termino);
        
        $resultados = array_map(function($cuenta) {
            return [
                'id' => $cuenta['id'] ?? '',
                'codigo' => $cuenta['codigo'] ?? '',
                'nombre' => $cuenta['descripcion'] ?? '',
                'label' => ($cuenta['codigo'] ?? '') . ' - ' . ($cuenta['descripcion'] ?? '')
            ];
        }, $cuentas);

        $this->jsonResponse($resultados);
    }

    /**
     * API: Obtiene el detalle completo de una requisición para modal
     * 
     * @param int $id ID de la requisición
     * @return void
     */
    public function apiDetalleRequisicion($id)
    {
        try {
            $requisicion = $this->requisicionService->getRequisicionCompleta($id);

            if (!$requisicion) {
                http_response_code(404);
                echo '<div class="alert alert-danger">Requisición no encontrada</div>';
                return;
            }

            // Verificar permisos: permitir acceso amplio a revisores y autorizadores
            $orden = $requisicion['orden'];
            $esDueño = $orden->usuario_id == $this->getUsuarioId();
            $esRevisor = $this->isRevisor() || $this->isRevisorPorEmail($this->getUsuarioEmail());
            $esAutorizadorGeneral = $this->autorizacionService->esAutorizadorGeneral($this->getUsuarioEmail());
            $puedeAutorizar = $this->autorizacionService->puedeAutorizar($this->getUsuarioEmail(), $id);
            $esAdmin = $this->isAdmin();
            
            $tienePermisos = $esDueño || $esRevisor || $esAutorizadorGeneral || $puedeAutorizar || $esAdmin;
            
            if (!$tienePermisos) {
                http_response_code(403);
                echo '<div class="alert alert-danger">No tienes permisos para ver esta requisición</div>';
                return;
            }

            // Cargar datos adicionales
            $items = \App\Models\DetalleItem::porRequisicion($id);
            $distribucion = \App\Models\DistribucionGasto::porRequisicion($id);
            $catalogos = $this->getCatalogos();

            // Renderizar vista parcial del detalle
            $this->renderDetalleRequisicion($requisicion, $items, $distribucion, $catalogos);

        } catch (\Exception $e) {
            error_log("Error en apiDetalleRequisicion: " . $e->getMessage());
            http_response_code(500);
            echo '<div class="alert alert-danger">Error al cargar el detalle de la requisición</div>';
        }
    }

    /**
     * Renderiza el contenido del detalle de requisición para el modal
     */
    private function renderDetalleRequisicion($requisicion, $items, $distribucion, $catalogos)
    {
        $orden = $requisicion['orden'];
        $flujo = $requisicion['flujo'];
        
        // Helper para obtener datos
        function getData($data, $key, $default = '') {
            if (is_object($data)) {
                return $data->$key ?? $default;
            } elseif (is_array($data)) {
                return $data[$key] ?? $default;
            }
            return $default;
        }
        
        // Incluir la vista parcial del detalle
        include APP_PATH . '/Views/partials/detalle_requisicion.php';
    }

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Obtiene catálogos para formularios
     * 
     * @return array
     */
    private function getCatalogos()
    {
        return [
            'centros_costo' => CentroCosto::activos(),
            'cuentas_contables' => CuentaContable::activas(),
            'ubicaciones' => Ubicacion::activas(),
            'unidades_negocio' => UnidadNegocio::activas(),
            'formas_pago' => [
                'efectivo' => 'Efectivo',
                'transferencia' => 'Transferencia',
                'cheque' => 'Cheque',
                'tarjeta' => 'Tarjeta de Crédito'
            ]
        ];
    }

    /**
     * Aplica filtros a las requisiciones
     * 
     * @param int $usuarioId ID del usuario
     * @param array $filtros Filtros a aplicar
     * @return array
     */
    private function aplicarFiltros($usuarioId, $filtros)
    {
        // Si es admin o revisor, obtener todas las requisiciones
        // Si no, solo obtener las del usuario logueado
        if ($this->isAdmin() || $this->isRevisor()) {
            $requisiciones = Requisicion::all();
        } else {
            // Si no hay usuario logueado, devolver array vacío
            if ($usuarioId === null) {
                $requisiciones = [];
            } else {
                $requisiciones = Requisicion::porUsuario($usuarioId);
            }
        }

        // Agregar información del usuario creador a cada requisición
        foreach ($requisiciones as $req) {
            $usuario = $req->usuario();
            if ($usuario) {
                $req->usuario_nombre = $usuario->azure_display_name ?? $usuario->nombre ?? 'Usuario desconocido';
                $req->usuario_email = $usuario->azure_email ?? $usuario->email ?? '';
            } else {
                $req->usuario_nombre = 'Usuario no encontrado';
                $req->usuario_email = '';
            }
        }

        // ✅ USAR SISTEMA CENTRALIZADO - Filtro de estado
        if (!empty($filtros['estado'])) {
            $requisiciones = array_filter($requisiciones, function($req) use ($filtros) {
                $estadoReal = $req->getEstadoReal(); // Estado real centralizado
                return $estadoReal === $filtros['estado'];
            });
        }

        // Aplicar filtro de fechas
        if (!empty($filtros['fecha_desde'])) {
            $requisiciones = array_filter($requisiciones, function($req) use ($filtros) {
                return $req['fecha'] >= $filtros['fecha_desde'];
            });
        }

        if (!empty($filtros['fecha_hasta'])) {
            $requisiciones = array_filter($requisiciones, function($req) use ($filtros) {
                return $req['fecha'] <= $filtros['fecha_hasta'];
            });
        }

        // Aplicar búsqueda
        if (!empty($filtros['busqueda'])) {
            $termino = strtolower($filtros['busqueda']);
            $requisiciones = array_filter($requisiciones, function($req) use ($termino) {
                return str_contains(strtolower($req['nombre_razon_social']), $termino) ||
                       str_contains(strtolower($req['justificacion']), $termino);
            });
        }

        return array_values($requisiciones);
    }

    /**
     * API: Obtiene la unidad de negocio y autorizadores para un centro de costo
     * 
     * @return void
     */
    public function apiObtenerUnidadNegocio()
    {
        $centroCostoId = $_GET['centro_costo_id'] ?? null;

        if (!$centroCostoId) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Centro de costo no especificado'
            ], 400);
            return;
        }

        $centroCosto = CentroCosto::find($centroCostoId);

        if (!$centroCosto) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Centro de costo no encontrado'
            ], 404);
            return;
        }

        // TODO: Implementar métodos en el modelo CentroCosto
        $unidadNegocio = null; // $centroCosto->getUnidadNegocio();
        $autorizadores = []; // $centroCosto->getAutorizadoresAsignados();

        $this->jsonResponse([
            'success' => true,
            'unidad_negocio_id' => $unidadNegocio['id'] ?? null,
            'unidad_negocio_nombre' => $unidadNegocio['nombre'] ?? null,
            'autorizadores' => $autorizadores
        ]);
    }

    /**
     * API: Obtiene todos los centros de costo con su mapeo a unidades de negocio y facturas
     * 
     * @return void
     */
    public function apiCentrosCosto()
    {
        try {
            // Obtener todos los centros de costo activos
            $centrosCosto = CentroCosto::activos();
            $unidadesNegocio = UnidadNegocio::activas();
            
            // Crear mapeo de centros de costo a unidades de negocio y facturas
            $mapeo = [];
            
            foreach ($centrosCosto as $centro) {
                $nombreCentro = strtoupper($centro->nombre ?? $centro->descripcion ?? '');
                
                // Determinar unidad de negocio basada en el nombre del centro de costo
                $unidadNegocio = $this->determinarUnidadNegocio($nombreCentro);
                
                // Determinar tipo de factura basado en el centro de costo
                $tipoFactura = $this->determinarTipoFactura($nombreCentro);
                
                $mapeo[$centro->id] = [
                    'id' => $centro->id,
                    'nombre' => $centro->nombre ?? $centro->descripcion ?? 'Sin nombre',
                    'codigo' => $centro->codigo ?? '',
                    'unidad_negocio' => $unidadNegocio,
                    'tipo_factura' => $tipoFactura,
                    'factura_numero' => "Factura {$tipoFactura}"
                ];
            }
            
            $this->jsonResponse([
                'success' => true,
                'centros_costo' => $mapeo,
                'unidades_negocio' => $unidadesNegocio
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en apiCentrosCosto: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Determina la unidad de negocio basada en el nombre del centro de costo
     * 
     * @param string $nombreCentro Nombre del centro de costo en mayúsculas
     * @return string Unidad de negocio correspondiente
     */
    private function determinarUnidadNegocio($nombreCentro)
    {
        // ADMINISTRACION
        if (strpos($nombreCentro, 'PARQUEO GENERAL') !== false ||
            strpos($nombreCentro, 'DIRECCION GENERAL') !== false ||
            strpos($nombreCentro, 'EDUCATION USA') !== false ||
            strpos($nombreCentro, 'FINANZAS') !== false ||
            strpos($nombreCentro, 'SISTEMAS') !== false ||
            strpos($nombreCentro, 'MERCADEO') !== false ||
            strpos($nombreCentro, 'ORGANIZACION Y PROCEDIMIENTOS') !== false ||
            strpos($nombreCentro, 'OPERACIONES') !== false ||
            strpos($nombreCentro, 'RECURSOS HUMANOS') !== false ||
            strpos($nombreCentro, 'SERVICIO AL CLIENTE') !== false ||
            strpos($nombreCentro, 'UNIDAD ACADEMICA') !== false) {
            return 'ADMINISTRACION';
        }
        
        // COMERCIAL
        if (strpos($nombreCentro, 'BODEGA') !== false ||
            strpos($nombreCentro, 'DISTRIBUCION FISICA') !== false ||
            strpos($nombreCentro, 'DISTRIBUIDORA') !== false ||
            strpos($nombreCentro, 'LIBRERIA COBAN') !== false ||
            strpos($nombreCentro, 'LIBRERIA QUETZALTENANGO') !== false ||
            strpos($nombreCentro, 'LIBRERIA ZONA 4') !== false) {
            return 'COMERCIAL';
        }
        
        // COLEGIO
        if (strpos($nombreCentro, 'BASICOS') !== false ||
            strpos($nombreCentro, 'BACHILLERATO') !== false ||
            strpos($nombreCentro, 'PERITO CONTADOR') !== false ||
            strpos($nombreCentro, 'SECRETARIADO') !== false ||
            strpos($nombreCentro, 'PRIMARIA') !== false) {
            return 'COLEGIO';
        }
        
        // CURSOS ADULTOS
        if (strpos($nombreCentro, 'CURSOS ADULTOS Z.4') !== false ||
            strpos($nombreCentro, 'CURSOS EMPRESARIALES') !== false ||
            strpos($nombreCentro, 'CURSOS ADULTOS HUEHUE') !== false ||
            strpos($nombreCentro, 'PROGRAMAS EXTERNOS') !== false) {
            return 'CURSOS ADULTOS';
        }
        
        // ACTIVIDADES CULTURALES
        if (strpos($nombreCentro, 'ACTIVIDADES CULTURALES') !== false ||
            strpos($nombreCentro, 'BIBLIOTECA') !== false) {
            return 'ACTIVIDADES CULTURALES';
        }
        
        // CURSOS NIÑOS
        if (strpos($nombreCentro, 'CURSOS NIÑOS Y ADOLECENTES Z.4') !== false) {
            return 'CURSOS NIÑOS';
        }
        
        // GENERAL
        if (strpos($nombreCentro, 'CENTRO DE COSTO GENERAL') !== false) {
            return 'UNIDAD DE NEGOCIO GENERAL';
        }
        
        // Valor por defecto
        return 'UNIDAD DE NEGOCIO GENERAL';
    }

    /**
     * Determina el tipo de factura basado en el nombre del centro de costo
     * 
     * @param string $nombreCentro Nombre del centro de costo en mayúsculas
     * @return int Tipo de factura (1, 2, o 3)
     */
    private function determinarTipoFactura($nombreCentro)
    {
        // FACTURA 1 - COMERCIAL Y ADMINISTRACIÓN
        if (strpos($nombreCentro, 'PARQUEO GENERAL') !== false ||
            strpos($nombreCentro, 'BODEGA') !== false ||
            strpos($nombreCentro, 'DISTRIBUCION FISICA') !== false ||
            strpos($nombreCentro, 'DISTRIBUIDORA') !== false ||
            strpos($nombreCentro, 'LIBRERIA COBAN') !== false ||
            strpos($nombreCentro, 'LIBRERIA QUETZALTENANGO') !== false ||
            strpos($nombreCentro, 'LIBRERIA ZONA 4') !== false ||
            strpos($nombreCentro, 'ACTIVIDADES CULTURALES') !== false) {
            return 1;
        }
        
        // FACTURA 2 - COLEGIO
        if (strpos($nombreCentro, 'BASICOS') !== false ||
            strpos($nombreCentro, 'BACHILLERATO') !== false ||
            strpos($nombreCentro, 'PERITO CONTADOR') !== false ||
            strpos($nombreCentro, 'SECRETARIADO') !== false ||
            strpos($nombreCentro, 'PRIMARIA') !== false) {
            return 2;
        }
        
        // FACTURA 3 - CURSOS Y ACTIVIDADES
        if (strpos($nombreCentro, 'CURSOS ADULTOS Z.4') !== false ||
            strpos($nombreCentro, 'CURSOS EMPRESARIALES') !== false ||
            strpos($nombreCentro, 'CURSOS ADULTOS HUEHUE') !== false ||
            strpos($nombreCentro, 'PROGRAMAS EXTERNOS') !== false ||
            strpos($nombreCentro, 'DIRECCION GENERAL') !== false ||
            strpos($nombreCentro, 'EDUCATION USA') !== false ||
            strpos($nombreCentro, 'FINANZAS') !== false ||
            strpos($nombreCentro, 'SISTEMAS') !== false ||
            strpos($nombreCentro, 'MERCADEO') !== false ||
            strpos($nombreCentro, 'ORGANIZACION Y PROCEDIMIENTOS') !== false ||
            strpos($nombreCentro, 'OPERACIONES') !== false ||
            strpos($nombreCentro, 'RECURSOS HUMANOS') !== false ||
            strpos($nombreCentro, 'SERVICIO AL CLIENTE') !== false ||
            strpos($nombreCentro, 'UNIDAD ACADEMICA') !== false ||
            strpos($nombreCentro, 'BIBLIOTECA') !== false ||
            strpos($nombreCentro, 'CURSOS NIÑOS Y ADOLECENTES Z.4') !== false ||
            strpos($nombreCentro, 'CENTRO DE COSTO GENERAL') !== false) {
            return 3;
        }
        
        // Valor por defecto
        return 1;
    }
}
