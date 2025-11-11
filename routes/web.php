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

// ============================================================================
// RUTAS DE DESARROLLO (Solo para desarrollo local)
// ============================================================================

/**
 * Login de desarrollo (bypasea Azure AD)
 * Solo habilitado cuando APP_ENV=development
 */
if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
    $router->get('/dev/login', [DevAuthController::class, 'showLogin']);
    $router->post('/dev/login', [DevAuthController::class, 'login']);
    $router->get('/dev/logout', [DevAuthController::class, 'logout']);
}

// ============================================================================
// RUTAS PÚBLICAS
// ============================================================================

/**
 * Ruta raíz - Redirige según autenticación y entorno
 */
$router->get('/', function() {
    if (isset($_SESSION['user_id'])) {
        header('Location: /dashboard');
    } else {
        // Si está en desarrollo, redirigir a dev login
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
            header('Location: /dev/login');
        } else {
            header('Location: /login');
        }
    }
    exit;
});

/**
 * Autenticación
 */
$router->get('/login', [AuthController::class, 'showLogin']);
$router->get('/auth/azure', [AuthController::class, 'login']);
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
        
        // Ver
        $router->get('/', [AutorizacionController::class, 'index']);
        $router->get('/{id}', [AutorizacionController::class, 'show']);
        $router->get('/historial', [AutorizacionController::class, 'historial']);
        
        // Revisión (requiere rol revisor)
        $router->group(['middlewares' => ['RoleMiddleware:revisor']], function($router) {
            $router->get('/revision/pendientes', [AutorizacionController::class, 'pendientesRevision']);
            
            // Con CSRF
            $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
                $router->post('/{id}/aprobar-revision', [AutorizacionController::class, 'aprobarRevision']);
                $router->post('/{id}/rechazar-revision', [AutorizacionController::class, 'rechazarRevision']);
            });
        });
        
        // Autorización por centro de costo (con CSRF)
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/centro/{id}/autorizar', [AutorizacionController::class, 'autorizarCentro']);
            $router->post('/centro/{id}/rechazar', [AutorizacionController::class, 'rechazarCentro']);
        });
        
        // APIs
        $router->get('/api/{id}/progreso', [AutorizacionController::class, 'apiProgreso']);
        $router->get('/api/pendientes', [AutorizacionController::class, 'apiPendientes']);
        $router->post('/api/autorizar-multiple', [AutorizacionController::class, 'apiAutorizarMultiple']);
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
        $router->get('/usuarios/{id}', [AdminController::class, 'showUsuario']);
        
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/usuarios/{id}', [AdminController::class, 'updateUsuario']);
            $router->delete('/usuarios/{id}/desactivar', [AdminController::class, 'desactivarUsuario']);
        });
        
        // -------- CENTROS DE COSTO --------
        $router->get('/centros', [AdminController::class, 'centrosCosto']);
        
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/centros', [AdminController::class, 'storeCentro']);
            $router->put('/centros/{id}', [AdminController::class, 'updateCentro']);
        });
        
        // -------- AUTORIZADORES --------
        $router->get('/autorizadores', [AdminController::class, 'autorizadores']);
        $router->get('/autorizadores/{id}/edit', [AdminController::class, 'editAutorizador']);
        
        // Autorizadores Especiales (deben ir ANTES de las rutas con parámetros)
        $router->get('/autorizadores/metodos-pago', [AdminController::class, 'autorizadoresMetodosPago']);
        $router->get('/autorizadores/metodos-pago/create', [AdminController::class, 'createMetodoPago']);
        $router->get('/autorizadores/metodos-pago/{email}/edit', [AdminController::class, 'editMetodoPagoByEmail']);
        $router->get('/autorizadores/metodos-pago/{id}', [AdminController::class, 'showMetodoPago']);
        $router->get('/autorizadores/metodos-pago/{id}/edit', [AdminController::class, 'editMetodoPago']);

        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/autorizadores', [AdminController::class, 'storeAutorizador']);
            $router->put('/autorizadores/{id}', [AdminController::class, 'updateAutorizador']);
            $router->delete('/autorizadores/{id}', [AdminController::class, 'deleteAutorizador']);
            
            // CRUD para Autorizadores de Métodos de Pago
            $router->post('/autorizadores/metodos-pago', [AdminController::class, 'storeMetodoPago']);
            $router->put('/autorizadores/metodos-pago/{email}/edit', [AdminController::class, 'updateMetodoPagoByEmail']);
            $router->put('/autorizadores/metodos-pago/{id}', [AdminController::class, 'updateMetodoPago']);
            $router->delete('/autorizadores/metodos-pago/{id}', [AdminController::class, 'deleteMetodoPago']);
        });
        
        // -------- RESPALDOS --------
        $router->get('/autorizadores/respaldos', [AdminController::class, 'autorizadoresRespaldos']);
        $router->get('/autorizadores/respaldos/create', [AdminController::class, 'createRespaldo']);
        $router->get('/autorizadores/respaldos/{id}', [AdminController::class, 'showRespaldo']);
        $router->get('/autorizadores/respaldos/{id}/edit', [AdminController::class, 'editRespaldo']);
        // Rutas legacy para compatibilidad
        $router->get('/respaldos', [AdminController::class, 'autorizadoresRespaldos']);
        $router->get('/respaldos/create', [AdminController::class, 'createRespaldo']);
        $router->get('/respaldos/{id}', [AdminController::class, 'showRespaldo']);
        $router->get('/respaldos/{id}/edit', [AdminController::class, 'editRespaldo']);
        
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/autorizadores/respaldos', [AdminController::class, 'storeRespaldo']);
            $router->put('/autorizadores/respaldos/{id}', [AdminController::class, 'updateRespaldo']);
            $router->delete('/autorizadores/respaldos/{id}', [AdminController::class, 'deleteRespaldo']);
            // Rutas legacy
            $router->post('/respaldos', [AdminController::class, 'storeRespaldo']);
            $router->put('/respaldos/{id}', [AdminController::class, 'updateRespaldo']);
            $router->delete('/respaldos/{id}', [AdminController::class, 'deleteRespaldo']);
        });
        
        // -------- CATÁLOGOS --------
        $router->get('/catalogos', [AdminController::class, 'catalogos']);
        
        // -------- REPORTES --------
        $router->get('/reportes', [AdminController::class, 'reportes']);
        
        $router->group(['middlewares' => ['CsrfMiddleware']], function($router) {
            $router->post('/reportes/usuarios', [AdminController::class, 'generarReporteUsuarios']);
            $router->post('/reportes/requisiciones', [AdminController::class, 'generarReporteRequisiciones']);
            $router->post('/reportes/autorizaciones', [AdminController::class, 'generarReporteAutorizaciones']);
            $router->post('/reportes/financiero', [AdminController::class, 'generarReporteFinanciero']);
            $router->post('/reportes/export', [AdminController::class, 'exportReporte']);
        });

        // -------- SEGUIMIENTO DE REQUISICIONES --------
        $router->get('/requisiciones', [\App\Controllers\Admin\RequisicionesController::class, 'index']);
        $router->get('/requisiciones/{id}', [\App\Controllers\Admin\RequisicionesController::class, 'show']);
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
