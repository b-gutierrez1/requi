<?php
/**
 * Rutas Web del Sistema de Requisiciones
 * 
 * Define todas las rutas HTTP de la aplicación con sus controllers correspondientes.
 * 
 * @package RequisicionesMVC
 * @version 2.0
 */

use App\Controllers\AuthController;
use App\Controllers\DevAuthController;
use App\Controllers\DashboardController;
use App\Controllers\RequisicionController;
use App\Controllers\AutorizacionController;
use App\Controllers\AdminController;
use App\Controllers\Admin\ReporteController;
use App\Controllers\Admin\CentroCostoController;
use App\Controllers\Admin\AutorizadorController;
use App\Controllers\Admin\AutorizadorEspecialController;
use App\Helpers\Redirect;

// ============================================================================
// RUTAS DE DESARROLLO (Solo para desarrollo local)
// ============================================================================

/**
 * Login de desarrollo (bypasea Azure AD)
 * Solo habilitado cuando APP_ENV=development
 */
if (false) {} // Se deshabilitan las rutas de desarrollo

// ============================================================================
// RUTAS PÚBLICAS
// ============================================================================

/**
 * Ruta raíz - Redirige según autenticación y entorno
 */
$router->get('/', function() {
    if (isset($_SESSION['user_id'])) {
        Redirect::to('/dashboard')->send();
    } else {
        Redirect::to('/login')->send();
    }
});
/**
 * Autenticación
 */
$router->get('/login', [AuthController::class, 'showLogin']);
$router->get('/auth/azure', [AuthController::class, 'login']);
$router->get('/auth/azure/callback', [AuthController::class, 'azureCallback']);
// Ruta heredada para compatibilidad
$router->get('/auth/callback', [AuthController::class, 'azureCallback']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/auth/status', [AuthController::class, 'status']); // API
$router->get('/auth/refresh-session', [AuthController::class, 'refreshSession']); // Actualizar sesión

// ============================================================================
// RUTAS PROTEGIDAS - Requieren autenticación
// ============================================================================

$router->group(['middlewares' => ['AuthMiddleware']], function($router) {
    
    // ------------------------------------------------------------------------
    // DASHBOARD
    // ------------------------------------------------------------------------
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/perfil', [AuthController::class, 'perfil']);
    
    // Widgets (AJAX)
    $router->get('/dashboard/widgets/autorizaciones', [DashboardController::class, 'widgetAutorizaciones']);
    $router->get('/dashboard/widgets/requisiciones', [DashboardController::class, 'widgetRequisiciones']);
    $router->get('/dashboard/widgets/estadisticas', [DashboardController::class, 'widgetEstadisticas']);
    
    // APIs Dashboard
    $router->get('/api/dashboard/estadisticas', [DashboardController::class, 'apiEstadisticas']);
    $router->get('/api/dashboard/notificaciones', [DashboardController::class, 'apiNotificaciones']);
    $router->get('/api/dashboard/grafica-mensual', [DashboardController::class, 'apiGraficaMensual']);
    
    // ------------------------------------------------------------------------
    // REQUISICIONES
    // ------------------------------------------------------------------------
    $router->group(['prefix' => '/requisiciones'], function($router) {
        
        // Listar y ver
        $router->get('/', [RequisicionController::class, 'index']);
        $router->get('/crear', [RequisicionController::class, 'create']);
        $router->get('/{id}', [RequisicionController::class, 'show']);
        $router->get('/{id}/editar', [RequisicionController::class, 'edit']);
        $router->get('/{id}/imprimir', [RequisicionController::class, 'print']);
        
        // Acciones con CSRF
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/', [RequisicionController::class, 'store']);
            $router->put('/{id}', [RequisicionController::class, 'update']);
            $router->delete('/{id}', [RequisicionController::class, 'destroy']);
            $router->delete('/archivo/{id}', [RequisicionController::class, 'deleteArchivo']);
        });
        
        // APIs
        $router->get('/api/buscar', [RequisicionController::class, 'apiBuscar']);
        $router->get('/api/buscar-cuentas', [RequisicionController::class, 'apiBuscarCuentas']);
        $router->post('/api/validar-presupuesto', [RequisicionController::class, 'apiValidarPresupuesto']);
        $router->get('/api/export', [RequisicionController::class, 'export']);
    });
    
    // API para detalle de requisición (fuera del grupo de CSRF para permitir GET)
    $router->get('/api/requisiciones/{id}/detalle', [RequisicionController::class, 'apiDetalleRequisicion']);

    // Descarga de archivos adjuntos
    $router->get('/archivos/{id}/descargar', [RequisicionController::class, 'descargarArchivo']);
    
    // ------------------------------------------------------------------------
    // RUTA DE PRUEBA TEMPORAL
    // ------------------------------------------------------------------------
    $router->get('/test-layout', function() {
        \App\Helpers\View::render('test-layout', ['title' => 'Test Layout']);
    });
    
    $router->get('/test-edit', function() {
        $data = [
            'requisicion' => [
                'orden' => (object)['id' => 10, 'justificacion' => 'Prueba de justificación'],
                'flujo' => (object)['estado' => 'rechazado_revision']
            ]
        ];
        \App\Helpers\View::render('requisiciones/edit-simple', $data);
    });
    
    // ------------------------------------------------------------------------
    // AUTORIZACIONES
    // ------------------------------------------------------------------------
    $router->group(['prefix' => '/autorizaciones'], function($router) {
        
        // IMPORTANTE: Las rutas específicas deben ir ANTES de las rutas con parámetros dinámicos
        
        // Ver
        $router->get('/', [AutorizacionController::class, 'index']);
        
        // Rutas específicas (deben ir antes de /{id})
        $router->get('/historial', [AutorizacionController::class, 'historial']);
        
        // Revisión (requiere rol revisor)
        $router->group(['middlewares' => ['RoleMiddleware:revisor']], function($router) {
            // Ruta principal de revisión (alias para /revision/pendientes)
            // IMPORTANTE: Debe ir ANTES de /{id} para que funcione correctamente
            $router->get('/revision', [AutorizacionController::class, 'pendientesRevision']);
            $router->get('/revision/pendientes', [AutorizacionController::class, 'pendientesRevision']);
            
            // Con CSRF
            $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
                $router->post('/{id}/aprobar-revision', [AutorizacionController::class, 'aprobarRevision']);
                $router->post('/{id}/rechazar-revision', [AutorizacionController::class, 'rechazarRevision']);
            });
        });
        
        // Ruta genérica con parámetro (debe ir AL FINAL después de todas las rutas específicas)
        $router->get('/{id}', [AutorizacionController::class, 'show']);
        
        // Autorización por centro de costo (con CSRF)
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/centro/{id}/autorizar', [AutorizacionController::class, 'autorizarCentro']);
            $router->post('/centro/{id}/rechazar', [AutorizacionController::class, 'rechazarCentro']);
        });
        
        // Autorizaciones especiales (con CSRF)
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/pago/{id}/aprobar', [AutorizacionController::class, 'aprobarAutorizacionPago']);
            $router->post('/pago/{id}/rechazar', [AutorizacionController::class, 'rechazarAutorizacionPago']);
            $router->post('/cuenta/{id}/aprobar', [AutorizacionController::class, 'aprobarAutorizacionCuenta']);
            $router->post('/cuenta/{id}/rechazar', [AutorizacionController::class, 'rechazarAutorizacionCuenta']);
        });
        
        // APIs
        $router->get('/api/{id}/progreso', [AutorizacionController::class, 'apiProgreso']);
        $router->get('/api/pendientes', [AutorizacionController::class, 'apiPendientes']);
        $router->post('/api/autorizar-multiple', [AutorizacionController::class, 'apiAutorizarMultiple']);
        $router->get('/api/centros/{id}/autorizadores', [AutorizacionController::class, 'apiAutorizadoresCentro']);
    });
    
    // ------------------------------------------------------------------------
    // ADMINISTRACIÓN - Requiere rol admin
    // ------------------------------------------------------------------------
    $router->group([
        'prefix' => '/admin',
        'middlewares' => ['RoleMiddleware:admin']
    ], function($router) {
        
        // Dashboard Admin
        $router->get('/', [AdminController::class, 'dashboard']);
        $router->get('/dashboard', [AdminController::class, 'dashboard']);
        
        // -------- USUARIOS --------
        $router->get('/usuarios', [AdminController::class, 'usuarios']);
        $router->get('/usuarios/{id}/edit', [AdminController::class, 'editUsuario']); // Específica ANTES que genérica
        $router->get('/usuarios/{id}', [AdminController::class, 'showUsuario']);
        
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/usuarios/{id}', [AdminController::class, 'updateUsuario']);
            $router->post('/usuarios/{id}/toggle', [AdminController::class, 'toggleUsuario']);
            $router->delete('/usuarios/{id}/desactivar', [AdminController::class, 'desactivarUsuario']);
        });
        
        // -------- CENTROS DE COSTO --------
        $router->get('/centros', [CentroCostoController::class, 'centrosCosto']);
        $router->get('/centros/create', [CentroCostoController::class, 'createCentro']);
        $router->get('/centros/{id}', [CentroCostoController::class, 'showCentro']);
        $router->get('/centros/{id}/edit', [CentroCostoController::class, 'editCentro']);

        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/centros', [CentroCostoController::class, 'storeCentro']);
            $router->put('/centros/{id}', [CentroCostoController::class, 'updateCentro']);
            $router->delete('/centros/{id}', [CentroCostoController::class, 'deleteCentro']);
            $router->post('/centros/{id}/toggle', [CentroCostoController::class, 'toggleCentro']);
        });
        
        // -------- AUTORIZADORES --------
        $router->get('/autorizadores', [AutorizadorController::class, 'autorizadores']);
        $router->get('/autorizadores/create', [AutorizadorController::class, 'createAutorizador']);
        $router->get('/autorizadores/{id}', [AutorizadorController::class, 'showAutorizador']);
        $router->get('/autorizadores/{id}/edit', [AutorizadorController::class, 'editAutorizador']);
        $router->get('/autorizadores/{id}/centros', [AutorizadorController::class, 'editCentrosAutorizador']);
        $router->get('/api/autorizadores/centros-costo', [AutorizadorController::class, 'apiCentrosCostoAutorizador']);
        $router->get('/api/autorizadores/lookup-cargo', [AutorizadorController::class, 'apiLookupCargo']);
        $router->get('/api/usuarios/buscar', [AdminController::class, 'apiBuscarUsuarios']);

        // Autorizadores Especiales (deben ir ANTES de las rutas con parámetros)
        $router->get('/autorizadores/metodos-pago', [AutorizadorEspecialController::class, 'autorizadoresMetodosPago']);
        $router->get('/autorizadores/metodos-pago/create', [AutorizadorEspecialController::class, 'createMetodoPago']);
        $router->get('/autorizadores/metodos-pago/email/{email}/edit', [AutorizadorEspecialController::class, 'editMetodoPagoByEmail']);
        $router->get('/autorizadores/metodos-pago/{id}', [AutorizadorEspecialController::class, 'showMetodoPago']);
        $router->get('/autorizadores/metodos-pago/{id}/edit', [AutorizadorEspecialController::class, 'editMetodoPago']);
        $router->get('/autorizadores/cuentas-contables', [AutorizadorEspecialController::class, 'autorizadoresCuentasContables']);
        $router->get('/autorizadores/cuentas-contables/create', [AutorizadorEspecialController::class, 'createCuentaContable']);
        $router->get('/autorizadores/cuentas-contables/{id}', [AutorizadorEspecialController::class, 'showCuentaContable']);
        $router->get('/autorizadores/cuentas-contables/{id}/edit', [AutorizadorEspecialController::class, 'editCuentaContable']);

        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/autorizadores', [AutorizadorController::class, 'storeAutorizador']);
            $router->put('/autorizadores/{id}', [AutorizadorController::class, 'updateAutorizador']);
            $router->put('/autorizadores/{id}/centros', [AutorizadorController::class, 'updateCentrosAutorizador']);
            $router->delete('/autorizadores/{id}', [AutorizadorController::class, 'deleteAutorizador']);

            // CRUD para Autorizadores de Métodos de Pago
            $router->post('/autorizadores/metodos-pago', [AutorizadorEspecialController::class, 'storeMetodoPago']);
            $router->post('/autorizadores/metodos-pago/email/{email}/edit', [AutorizadorEspecialController::class, 'updateMetodoPagoByEmail']);
            $router->delete('/autorizadores/metodos-pago/email/{email}', [AutorizadorEspecialController::class, 'deleteMetodoPagoByEmail']);
            $router->put('/autorizadores/metodos-pago/{id}', [AutorizadorEspecialController::class, 'updateMetodoPago']);
            $router->delete('/autorizadores/metodos-pago/{id}', [AutorizadorEspecialController::class, 'deleteMetodoPago']);

            // CRUD Autorizadores por Cuenta Contable
            $router->post('/autorizadores/cuentas-contables', [AutorizadorEspecialController::class, 'storeCuentaContable']);
            $router->put('/autorizadores/cuentas-contables/{id}', [AutorizadorEspecialController::class, 'updateCuentaContable']);
            $router->delete('/autorizadores/cuentas-contables/{id}', [AutorizadorEspecialController::class, 'deleteCuentaContable']);
        });

        // -------- RESPALDOS --------
        $router->get('/autorizadores/respaldos', [AutorizadorEspecialController::class, 'autorizadoresRespaldos']);
        $router->get('/autorizadores/respaldos/create', [AutorizadorEspecialController::class, 'createRespaldo']);
        $router->get('/autorizadores/respaldos/{id}', [AutorizadorEspecialController::class, 'showRespaldo']);
        $router->get('/autorizadores/respaldos/{id}/edit', [AutorizadorEspecialController::class, 'editRespaldo']);
        // Rutas legacy para compatibilidad
        $router->get('/respaldos', [AutorizadorEspecialController::class, 'autorizadoresRespaldos']);
        $router->get('/respaldos/create', [AutorizadorEspecialController::class, 'createRespaldo']);
        $router->get('/respaldos/{id}', [AutorizadorEspecialController::class, 'showRespaldo']);
        $router->get('/respaldos/{id}/edit', [AutorizadorEspecialController::class, 'editRespaldo']);

        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/autorizadores/respaldos', [AutorizadorEspecialController::class, 'storeRespaldo']);
            $router->put('/autorizadores/respaldos/{id}', [AutorizadorEspecialController::class, 'updateRespaldo']);
            $router->delete('/autorizadores/respaldos/{id}', [AutorizadorEspecialController::class, 'deleteRespaldo']);
            // Rutas legacy
            $router->post('/respaldos', [AutorizadorEspecialController::class, 'storeRespaldo']);
            $router->put('/respaldos/{id}', [AutorizadorEspecialController::class, 'updateRespaldo']);
            $router->delete('/respaldos/{id}', [AutorizadorEspecialController::class, 'deleteRespaldo']);
        });

        // -------- RELACIONES --------
        $router->get('/relaciones', [AdminController::class, 'relaciones']);

        // -------- CATÁLOGOS --------
        $router->get('/catalogos', [AdminController::class, 'catalogos']);
        
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            // CRUD para Cuentas Contables
            $router->post('/catalogos/cuenta', [AdminController::class, 'storeCuentaContableCatalogo']);
            $router->put('/catalogos/cuenta/{id}', [AdminController::class, 'updateCuentaContableCatalogo']);
            $router->post('/catalogos/cuenta/{id}/toggle', [AdminController::class, 'toggleCuentaContable']);
            $router->delete('/catalogos/cuenta/{id}', [AdminController::class, 'deleteCuentaContableCatalogo']);
        });
        
        // -------- REPORTES --------
        $router->get('/reportes', [ReporteController::class, 'reportes']);

        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/reportes/estado-requisiciones', [ReporteController::class, 'reporteEstadoRequisiciones']);
            $router->post('/reportes/gasto-centro-costo', [ReporteController::class, 'reporteGastoCentroCosto']);
            $router->post('/reportes/gasto-unidad-requirente', [ReporteController::class, 'reporteGastoUnidadRequirente']);
            $router->post('/reportes/tasa-rechazo', [ReporteController::class, 'reporteTasaRechazo']);
            $router->post('/reportes/forma-pago', [ReporteController::class, 'reporteFormaPago']);
        });

        // -------- SEGUIMIENTO DE REQUISICIONES --------
        $router->get('/requisiciones', [\App\Controllers\Admin\RequisicionesController::class, 'index']);
        $router->get('/requisiciones/{id}', [\App\Controllers\Admin\RequisicionesController::class, 'show']);
        $router->get('/requisiciones/{id}/logs', [\App\Controllers\Admin\RequisicionesController::class, 'logs']);

        // -------- CONFIGURACIÓN DE CORREO --------
        $router->get('/email', [\App\Controllers\Admin\EmailController::class, 'index']);
        $router->get('/email/config', [\App\Controllers\Admin\EmailController::class, 'config']);
        $router->get('/email/templates', [\App\Controllers\Admin\EmailController::class, 'templates']);
        $router->get('/email/templates/{template}/edit', [\App\Controllers\Admin\EmailController::class, 'editTemplate']);

        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/email/config/save', [\App\Controllers\Admin\EmailController::class, 'saveConfig']);
            $router->post('/email/templates/{template}/save', [\App\Controllers\Admin\EmailController::class, 'saveTemplate']);
            $router->post('/email/test', [\App\Controllers\Admin\EmailController::class, 'testEmail']);
            $router->post('/email/test-connection', [\App\Controllers\Admin\EmailController::class, 'testConnection']);
        });
    });
});

// ============================================================================
// RUTA DE PRUEBA
// ============================================================================

/**
 * Endpoint de prueba para verificar el routing
 */
$router->get('/test', function() {
    echo json_encode([
        'success' => true,
        'message' => 'El sistema de routing está funcionando correctamente',
        'timestamp' => date('Y-m-d H:i:s'),
        'session_active' => isset($_SESSION['user_id']),
        'php_version' => PHP_VERSION,
        'routes_loaded' => true
    ]);
});


/**
 * Ruta de prueba con parámetros
 */
$router->get('/test/{param}', function($param) {
    echo json_encode([
        'success' => true,
        'message' => 'Parámetro recibido correctamente',
        'param' => $param,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});


