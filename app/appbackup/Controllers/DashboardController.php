<?php
/**
 * DashboardController
 * 
 * Controlador para el dashboard principal del sistema.
 * Muestra estadísticas, resúmenes y accesos rápidos.
 * 
 * @package RequisicionesMVC\Controllers
 * @version 2.0
 */

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Session;
use App\Models\Requisicion;
use App\Repositories\AutorizacionCentroRepository;
use App\Services\AutorizacionService;
use App\Services\RequisicionService;

class DashboardController extends Controller
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
     * Repositorio centralizado para autorizaciones de centro de costo
     *
     * @var AutorizacionCentroRepository|null
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
        $this->autorizacionCentroRepo = null;
    }

    /**
     * Muestra el dashboard principal
     * 
     * @return void
     */
    public function index()
    {
        // Prevenir caché del navegador
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Verificar que el usuario esté autenticado
        if (!Session::isAuthenticated()) {
            header('Location: ' . \App\Helpers\Redirect::url('/login'));
            exit;
        }
        
        $usuario = Session::getUser();
        
        // Verificar que los datos del usuario sean válidos
        if (!$usuario || !isset($usuario['id']) || !isset($usuario['email'])) {
            error_log("DashboardController: Datos de usuario inválidos o nulos");
            Session::logout();
            header('Location: ' . \App\Helpers\Redirect::url('/login?error=session_invalid'));
            exit;
        }
        
        $usuarioId = $usuario['id'];
        $usuarioEmail = $usuario['email'];

        // Obtener estadísticas según el rol del usuario
        $estadisticas = $this->getEstadisticasUsuario($usuarioId);
        $requisiciones_recientes = $this->getRequisicionesRecientes($usuarioId);
        $resumen_mensual = $this->getResumenMensual($usuarioId);
        
        // Debug temporal - remover después
        error_log("Dashboard Debug - Usuario ID: $usuarioId");
        error_log("Dashboard Debug - Estadísticas: " . json_encode($estadisticas));
        error_log("Dashboard Debug - Requisiciones recientes: " . count($requisiciones_recientes));
        error_log("Dashboard Debug - Resumen mensual: " . json_encode($resumen_mensual));
        
        // Determinar roles del usuario
        $esRevisor = $this->isRevisor() || $this->isRevisorPorEmail($usuarioEmail);
        $esAutorizador = isset($usuario['es_autorizador']) || $this->isAutorizadorDeCentro($usuarioEmail);
        
        $data = [
            'usuario' => $usuario,
            'estadisticas' => $estadisticas,
            'requisiciones_recientes' => $requisiciones_recientes,
            'autorizaciones_pendientes' => [],
            'resumen_mensual' => $resumen_mensual,
            'es_revisor' => $esRevisor, // Para mostrar/ocultar sección de revisiones
            'es_autorizador' => $esAutorizador
        ];
        
        if ($esRevisor || $esAutorizador) {
            // Si es autorizador, obtener sus autorizaciones pendientes
            if ($esAutorizador) {
                $autorizaciones = $this->getAutorizacionesPendientes($usuarioEmail);
                $data['autorizaciones_pendientes'] = $autorizaciones;
            }
            
            // Si es revisor, agregar también flujos pendientes de revisión
            if ($esRevisor) {
                $revisiones = $this->getRevisionesPendientes();
                $data['revisiones_pendientes'] = $revisiones;
            }
        }

        // Si es admin, agregar estadísticas generales
        if ($this->isAdmin()) {
            $data['estadisticas_generales'] = $this->getEstadisticasGenerales();
        }

        View::render('dashboard/index', $data);
    }

    // ========================================================================
    // ESTADÍSTICAS
    // ========================================================================

    /**
     * Obtiene estadísticas del usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return array
     */
    private function getEstadisticasUsuario($usuarioId)
    {
        try {
            $stats = Requisicion::getEstadisticasUsuario($usuarioId);

            return [
                'total_requisiciones' => $stats['total'] ?? 0,
                'pendientes' => $stats['pendientes'] ?? 0,
                'autorizadas' => $stats['autorizadas'] ?? 0,
                'rechazadas' => $stats['rechazadas'] ?? 0,
                'monto_total' => $stats['monto_total'] ?? 0,
                'monto_mes_actual' => $stats['monto_mes_actual'] ?? 0
            ];
        } catch (\Exception $e) {
            error_log("Error obteniendo estadísticas de usuario: " . $e->getMessage());
            return [
                'total_requisiciones' => 0,
                'pendientes' => 0,
                'autorizadas' => 0,
                'rechazadas' => 0,
                'monto_total' => 0,
                'monto_mes_actual' => 0
            ];
        }
    }

    /**
     * Obtiene estadísticas generales del sistema (admin)
     * 
     * @return array
     */
    private function getEstadisticasGenerales()
    {
        try {
            $stats = Requisicion::getEstadisticasGenerales();

            return [
                'total_requisiciones' => $stats['total'] ?? 0,
                'pendientes_revision' => $stats['pendientes_revision'] ?? 0,
                'pendientes_autorizacion' => $stats['pendientes_autorizacion'] ?? 0,
                'autorizadas_hoy' => $stats['autorizadas_hoy'] ?? 0,
                'monto_total_mes' => $stats['monto_total_mes'] ?? 0,
                'usuarios_activos' => $stats['usuarios_activos'] ?? 0,
                'tiempo_promedio_autorizacion' => $stats['tiempo_promedio'] ?? 0
            ];
        } catch (\Exception $e) {
            error_log("Error obteniendo estadísticas generales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene resumen mensual del usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return array
     */
    private function getResumenMensual($usuarioId)
    {
        try {
            // Obtener requisiciones del mes actual
            $mesActual = date('Y-m');
            $requisiciones = Requisicion::porUsuarioYMes($usuarioId, $mesActual);

            $resumen = [
                'total' => count($requisiciones),
                'monto_total' => 0,
                'por_estado' => [
                    'pendiente_revision' => 0,
                    'pendiente_autorizacion' => 0,
                    'autorizado' => 0,
                    'rechazado' => 0
                ]
            ];

            foreach ($requisiciones as $req) {
                $resumen['monto_total'] += $req->monto_total;
                
                $flujo = $req->autorizacionFlujo();
                if ($flujo) {
                    $estado = $flujo->estado;
                    if (isset($resumen['por_estado'][$estado])) {
                        $resumen['por_estado'][$estado]++;
                    }
                }
            }

            return $resumen;
        } catch (\Exception $e) {
            error_log("Error obteniendo resumen mensual: " . $e->getMessage());
            return [
                'total' => 0,
                'monto_total' => 0,
                'por_estado' => []
            ];
        }
    }

    // ========================================================================
    // REQUISICIONES
    // ========================================================================

    /**
     * Obtiene las requisiciones recientes del usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return array
     */
    private function getRequisicionesRecientes($usuarioId)
    {
        try {
            return Requisicion::recentesPorUsuario($usuarioId, 5);
        } catch (\Exception $e) {
            error_log("Error obteniendo requisiciones recientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene (o inicializa) el repositorio de autorizaciones de centros.
     *
     * @return AutorizacionCentroRepository
     */
    private function getAutorizacionCentroRepo(): AutorizacionCentroRepository
    {
        if ($this->autorizacionCentroRepo === null) {
            $this->autorizacionCentroRepo = new AutorizacionCentroRepository();
        }

        return $this->autorizacionCentroRepo;
    }

    // ========================================================================
    // AUTORIZACIONES
    // ========================================================================

    /**
     * Verifica si un usuario es autorizador de algún centro de costo
     * 
     * @param string $usuarioEmail Email del usuario
     * @return bool
     */
    private function isAutorizadorDeCentro($usuarioEmail)
    {
        try {
            $repo = $this->getAutorizacionCentroRepo();

            if ($repo->countPendingByEmail($usuarioEmail) > 0) {
                return true;
            }

            return $repo->existsByEmail($usuarioEmail);
        } catch (\Exception $e) {
            error_log("Error verificando si es autorizador de centro: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Obtiene autorizaciones pendientes del usuario
     * 
     * @param string $usuarioEmail Email del usuario
     * @return array
     */
    private function getAutorizacionesPendientes($usuarioEmail)
    {
        try {
            return $this->autorizacionService->getAutorizacionesPendientes($usuarioEmail);
        } catch (\Exception $e) {
            error_log("Error obteniendo autorizaciones pendientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene revisiones pendientes (para revisores)
     * 
     * @return array
     */
    private function getRevisionesPendientes()
    {
        try {
            return $this->autorizacionService->getRevisionesPendientes();
        } catch (\Exception $e) {
            error_log("Error obteniendo revisiones pendientes: " . $e->getMessage());
            return [];
        }
    }

    // ========================================================================
    // WIDGETS Y SECCIONES
    // ========================================================================

    /**
     * Widget de autorizaciones pendientes
     * 
     * @return void
     */
    public function widgetAutorizaciones()
    {
        // Verificar autenticación
        if (!Session::isAuthenticated()) {
            $this->jsonResponse(['error' => 'No autenticado'], 401);
            return;
        }
        
        $usuario = Session::getUser();
        if (!$usuario || !isset($usuario['email'])) {
            $this->jsonResponse(['error' => 'Datos de usuario inválidos'], 401);
            return;
        }
        
        $autorizaciones = $this->getAutorizacionesPendientes($usuario['email']);

        View::renderPartial('dashboard/widgets/autorizaciones', [
            'autorizaciones' => $autorizaciones
        ]);
    }

    /**
     * Widget de requisiciones recientes
     * 
     * @return void
     */
    public function widgetRequisiciones()
    {
        // Verificar autenticación
        if (!Session::isAuthenticated()) {
            $this->jsonResponse(['error' => 'No autenticado'], 401);
            return;
        }
        
        $usuario = Session::getUser();
        if (!$usuario || !isset($usuario['id'])) {
            $this->jsonResponse(['error' => 'Datos de usuario inválidos'], 401);
            return;
        }
        
        $requisiciones = $this->getRequisicionesRecientes($usuario['id']);

        View::renderPartial('dashboard/widgets/requisiciones', [
            'requisiciones' => $requisiciones
        ]);
    }

    /**
     * Widget de estadísticas
     * 
     * @return void
     */
    public function widgetEstadisticas()
    {
        // Verificar autenticación
        if (!Session::isAuthenticated()) {
            $this->jsonResponse(['error' => 'No autenticado'], 401);
            return;
        }
        
        $usuario = Session::getUser();
        if (!$usuario || !isset($usuario['id'])) {
            $this->jsonResponse(['error' => 'Datos de usuario inválidos'], 401);
            return;
        }
        
        $estadisticas = $this->getEstadisticasUsuario($usuario['id']);

        View::renderPartial('dashboard/widgets/estadisticas', [
            'estadisticas' => $estadisticas
        ]);
    }

    // ========================================================================
    // API ENDPOINTS (para AJAX)
    // ========================================================================

    /**
     * API: Obtiene estadísticas en formato JSON
     * 
     * @return void
     */
    public function apiEstadisticas()
    {
        // Verificar autenticación
        if (!Session::isAuthenticated()) {
            $this->jsonResponse(['error' => 'No autenticado'], 401);
            return;
        }
        
        $usuario = Session::getUser();
        if (!$usuario || !isset($usuario['id'])) {
            $this->jsonResponse(['error' => 'Datos de usuario inválidos'], 401);
            return;
        }
        
        $data = [
            'usuario' => $this->getEstadisticasUsuario($usuario['id']),
            'resumen_mensual' => $this->getResumenMensual($usuario['id'])
        ];

        if ($this->isAdmin()) {
            $data['generales'] = $this->getEstadisticasGenerales();
        }

        $this->jsonResponse([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * API: Obtiene notificaciones del usuario
     * 
     * @return void
     */
    public function apiNotificaciones()
    {
        // Verificar autenticación
        if (!Session::isAuthenticated()) {
            $this->jsonResponse(['error' => 'No autenticado'], 401);
            return;
        }
        
        $usuario = Session::getUser();
        if (!$usuario || !isset($usuario['id']) || !isset($usuario['email'])) {
            $this->jsonResponse(['error' => 'Datos de usuario inválidos'], 401);
            return;
        }
        
        // Obtener autorizaciones pendientes
        $autorizaciones = $this->getAutorizacionesPendientes($usuario['email']);
        
        // Obtener requisiciones con actualización reciente
        $requisicionesActualizadas = Requisicion::actualizadasReciente($usuario['id'], 24); // últimas 24 horas

        $notificaciones = [];

        // Agregar autorizaciones pendientes
        foreach ($autorizaciones as $auth) {
            $notificaciones[] = [
                'tipo' => 'autorizacion_pendiente',
                'mensaje' => 'Tienes una autorización pendiente',
                'url' => '/autorizaciones/' . $auth['id'],
                'fecha' => $auth['fecha_creacion'] ?? date('Y-m-d H:i:s')
            ];
        }

        // Agregar requisiciones actualizadas
        foreach ($requisicionesActualizadas as $req) {
            $notificaciones[] = [
                'tipo' => 'requisicion_actualizada',
                'mensaje' => 'Requisición #' . $req->id . ' actualizada',
                'url' => '/requisiciones/' . $req->id,
                'fecha' => $req->fecha_actualizacion ?? $req->fecha
            ];
        }

        // Ordenar por fecha descendente
        usort($notificaciones, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        $this->jsonResponse([
            'success' => true,
            'count' => count($notificaciones),
            'notificaciones' => array_slice($notificaciones, 0, 10) // Solo las 10 más recientes
        ]);
    }

    /**
     * API: Obtiene gráfica de requisiciones por mes
     * 
     * @return void
     */
    public function apiGraficaMensual()
    {
        // Verificar autenticación
        if (!Session::isAuthenticated()) {
            $this->jsonResponse(['error' => 'No autenticado'], 401);
            return;
        }
        
        $usuario = Session::getUser();
        if (!$usuario || !isset($usuario['id'])) {
            $this->jsonResponse(['error' => 'Datos de usuario inválidos'], 401);
            return;
        }
        
        $usuarioId = $usuario['id'];

        try {
            // Obtener datos de los últimos 6 meses
            $meses = [];
            $datos = [];

            for ($i = 5; $i >= 0; $i--) {
                $fecha = date('Y-m', strtotime("-{$i} months"));
                $meses[] = date('M Y', strtotime($fecha . '-01'));
                
                $requisiciones = Requisicion::porUsuarioYMes($usuarioId, $fecha);
                $monto = array_sum(array_column($requisiciones, 'monto_total'));
                $datos[] = $monto;
            }

            $this->jsonResponse([
                'success' => true,
                'labels' => $meses,
                'data' => $datos
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
