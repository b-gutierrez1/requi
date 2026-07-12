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

        $this->autorizacionService = new AutorizacionService();
        $this->requisicionService = new RequisicionService(
            $this->autorizacionService
        );
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
        
        // Obtener filtros — whitelist para estado
        $estadosValidos = [
            AutorizacionFlujo::ESTADO_PENDIENTE_REVISION,
            AutorizacionFlujo::ESTADO_RECHAZADO_REVISION,
            AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_PAGO,
            AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_CUENTA,
            AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION_CENTROS,
            AutorizacionFlujo::ESTADO_PENDIENTE_AUTORIZACION,
            AutorizacionFlujo::ESTADO_RECHAZADO_AUTORIZACION,
            AutorizacionFlujo::ESTADO_AUTORIZADO,
            AutorizacionFlujo::ESTADO_RECHAZADO,
            'borrador',
        ];
        $estadoInput = $_GET['estado'] ?? '';
        $filtros = [
            'estado'      => in_array($estadoInput, $estadosValidos) ? $estadoInput : '',
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'busqueda'    => substr(trim($_GET['busqueda'] ?? ''), 0, 100),
        ];

        // Parámetros de paginación
        $pagina    = max(1, (int) ($_GET['pagina'] ?? 1));
        $porPagina = max(5, min(100, (int) ($_GET['por_pagina'] ?? 5))); // Requisiciones por página (5-100)

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
        if (!$this->tienePermisosRequisicion($orden, $id, $this->autorizacionService)) {
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
                    $data['centro_costo_ids'] = array_map('intval', $_POST['centro_costo_ids'] ?? []);
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
        $data['centro_costo_ids'] = array_map('intval', $_POST['centro_costo_ids'] ?? []);
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
        $data['centro_costo_ids'] = array_map('intval', $_POST['centro_costo_ids'] ?? []);
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
            $tipoMime       = $archivo->tipo_mime ?: 'application/octet-stream';
            $nombreDescarga = generarNombreDescarga(
                (int)$archivo->requisicion_id,
                $requisicion->proveedor_nombre ?? '',
                $archivo->nombre_original ?? 'documento'
            );

            header('Content-Type: ' . $tipoMime);
            header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
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
            $tipoMime    = $archivo->tipo_mime ?: 'application/octet-stream';
            $requisicion = \App\Models\Requisicion::find($archivo->requisicion_id);
            $nombreDescarga = generarNombreDescarga(
                (int)$archivo->requisicion_id,
                $requisicion->proveedor_nombre ?? '',
                $archivo->nombre_original ?? 'documento'
            );

            header('Content-Type: ' . $tipoMime);
            header('Content-Disposition: inline; filename="' . $nombreDescarga . '"');
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
        if (!$this->tienePermisosRequisicion($orden, $id, $this->autorizacionService)) {
            Redirect::to('/requisiciones')
                ->withError('No tienes permisos para ver esta requisición')
                ->send();
        }

        // Obtener director de la unidad requirente
        $directorUnidad = '';
        try {
            $pdo = Requisicion::getConnection();
            $unidadId = $orden->unidad_requirente ?? null;
            $stmt = $pdo->prepare("
                SELECT pa.nombre
                FROM persona_autorizada pa
                INNER JOIN unidad_requirente ur ON ur.centro_costo_id = pa.centro_costo_id
                WHERE ur.id = ?
                LIMIT 1
            ");
            $stmt->execute([$unidadId]);
            $directorUnidad = $stmt->fetchColumn() ?: '';
        } catch (\Exception $e) {
            error_log("Error obteniendo director unidad requirente: " . $e->getMessage());
        }

        // Obtener autorizaciones aprobadas para mostrar en el impreso
        $autorizacionesAprobadas = [];
        try {
            $pdo = Requisicion::getConnection();
            $stmt = $pdo->prepare("
                SELECT a.tipo,
                       MIN(a.fecha_respuesta) as fecha_respuesta,
                       COALESCE(MIN(a.autorizador_nombre), MIN(a.autorizador_email)) as nombre,
                       MIN(a.autorizador_email) as autorizador_email
                FROM autorizaciones a
                WHERE a.requisicion_id = ?
                  AND TRIM(a.estado) NOT IN ('pendiente', 'rechazada', 'omitida', 'cancelada', '')
                GROUP BY a.tipo, a.autorizador_email
                ORDER BY MIN(a.fecha_respuesta) ASC
            ");
            $stmt->execute([$id]);
            $autorizacionesAprobadas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Enriquecer con nombre real cuando solo hay email
            foreach ($autorizacionesAprobadas as &$row) {
                if (!empty($row['nombre']) && strpos($row['nombre'], '@') !== false) {
                    try {
                        $stmtNombre = $pdo->prepare(
                            "SELECT azure_display_name FROM usuarios
                             WHERE LOWER(azure_email) = LOWER(?) LIMIT 1"
                        );
                        $stmtNombre->execute([$row['nombre']]);
                        $displayName = $stmtNombre->fetchColumn();
                        if ($displayName) {
                            $row['nombre'] = $displayName;
                        }
                    } catch (\Exception $ex) {
                        // mantener email si falla
                    }
                }
            }
            unset($row);

            // Enriquecer con cargo desde autorizadores
            $stmtCargo = $pdo->prepare(
                "SELECT cargo FROM autorizadores WHERE LOWER(email) = LOWER(?) LIMIT 1"
            );
            foreach ($autorizacionesAprobadas as &$row) {
                $stmtCargo->execute([$row['autorizador_email'] ?? '']);
                $row['cargo'] = $stmtCargo->fetchColumn() ?: '';
            }
            unset($row);

        } catch (\Exception $e) {
            error_log("Error obteniendo autorizaciones para impresión: " . $e->getMessage());
        }

        View::render('requisiciones/print', [
            'requisicion' => $requisicion,
            'orden' => $orden,
            'items' => $requisicion['items'],
            'distribucion' => $requisicion['distribuciones'],
            'flujo' => $requisicion['flujo'],
            'director_unidad' => $directorUnidad,
            'autorizaciones_aprobadas' => $autorizacionesAprobadas,
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
        $termino = substr(trim($_GET['q'] ?? ''), 0, 100);
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
        $termino = substr(trim($_GET['q'] ?? ''), 0, 100);

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
            if (!$this->tienePermisosRequisicion($orden, $id, $this->autorizacionService)) {
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
                return ($req->fecha_solicitud ?? '') >= $filtros['fecha_desde'];
            });
        }

        if (!empty($filtros['fecha_hasta'])) {
            $requisiciones = array_filter($requisiciones, function($req) use ($filtros) {
                return ($req->fecha_solicitud ?? '') <= $filtros['fecha_hasta'];
            });
        }

        // Aplicar búsqueda
        if (!empty($filtros['busqueda'])) {
            $termino = strtolower($filtros['busqueda']);
            $requisiciones = array_filter($requisiciones, function($req) use ($termino) {
                return str_contains(strtolower($req->proveedor_nombre ?? ''), $termino) ||
                       str_contains(strtolower($req->justificacion ?? ''), $termino);
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

        // Obtener unidad de negocio desde la relación en BD
        $unidadNegocioId = $centroCosto->unidad_negocio_id;
        $unidadNegocio = $unidadNegocioId ? UnidadNegocio::find($unidadNegocioId) : null;

        $this->jsonResponse([
            'success' => true,
            'unidad_negocio_id' => $unidadNegocio->id ?? null,
            'unidad_negocio_nombre' => $unidadNegocio->nombre ?? null,
            'factura' => $centroCosto->factura ?? 1
        ]);
    }


}
