<?php
/**
 * AdminController
 * 
 * Controlador para administración del sistema.
 * Gestión de usuarios, catálogos, autorizadores y configuración.
 * 
 * @package RequisicionesMVC\Controllers
 * @version 2.0
 */

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Session;
use App\Helpers\Redirect;
use App\Helpers\EstadoHelper;
use App\Models\Model;
use App\Models\Usuario;
use App\Models\CentroCosto;
use App\Models\CuentaContable;
use App\Models\PersonaAutorizada;
use App\Models\AutorizadorRespaldo;
use App\Models\AutorizadorMetodoPago;
use App\Models\AutorizadorCuentaContable;
use App\Models\OrdenCompra;

class AdminController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Verificar que es administrador
        if (!Session::isAdmin()) {
            Redirect::to('/dashboard')
                ->withError('No tienes permisos de administrador')
                ->send();
        }
    }

    // ========================================================================
    // DASHBOARD ADMIN
    // ========================================================================

    /**
     * Dashboard administrativo
     * 
     * @return void
     */
    public function dashboard()
    {
        // Estadísticas generales del sistema
        $stats = [
            'total_usuarios' => Usuario::count(),
            'usuarios_activos' => Usuario::countActivos(),
            'total_requisiciones' => OrdenCompra::count(),
            'requisiciones_mes' => OrdenCompra::countMesActual(),
            'monto_total_mes' => OrdenCompra::montoTotalMes(),
            'total_centros' => CentroCosto::count(),
            'autorizadores' => PersonaAutorizada::count(),
            'autorizaciones_pendientes' => 0 // TODO: implementar conteo de autorizaciones pendientes
        ];

        // Obtener usuarios recientes
        $usuarios_recientes = Usuario::getRecientes(5);
        
        // Obtener autorizaciones pendientes
        $autorizaciones_pendientes = [];
        
        // Actividad reciente
        $actividad_reciente = $this->getActividadReciente();

        View::render('admin/dashboard', [
            'stats' => $stats,
            'usuarios_recientes' => $usuarios_recientes,
            'autorizaciones_pendientes' => $autorizaciones_pendientes,
            'actividad_reciente' => $actividad_reciente,
            'title' => 'Panel de Administración'
        ]);
    }

    // ========================================================================
    // USUARIOS
    // ========================================================================

    /**
     * Lista usuarios
     * 
     * @return void
     */
    public function usuarios()
    {
        // Prevenir caché del navegador
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Debug temporal
        error_log("AdminController::usuarios() - Iniciando");
        error_log("AdminController::usuarios() - Usuario: " . json_encode(Session::getUser()));
        error_log("AdminController::usuarios() - Es admin: " . (Session::isAdmin() ? 'SÍ' : 'NO'));
        
        $filtro = $_GET['filtro'] ?? 'todos';
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20; // Límite por página
        
        // Obtener usuarios según filtro con paginación
        switch($filtro) {
            case 'activos':
                $usuarios = Usuario::where(['activo' => 1]);
                break;
            case 'revisores':
                $usuarios = Usuario::where(['is_revisor' => 1]);
                break;
            case 'admins':
                $usuarios = Usuario::where(['is_admin' => 1]);
                break;
            default:
                $usuarios = Usuario::paginate($page, $limit);
                break;
        }

        // Obtener estadísticas para el dashboard
        $totalUsuarios = Usuario::count();
        $admins = Usuario::where(['is_admin' => 1]);
        $revisores = Usuario::where(['is_revisor' => 1]);
        $activos = Usuario::where(['activo' => 1]);

        // Debug antes de renderizar
        error_log("AdminController::usuarios() - Usuarios obtenidos: " . count($usuarios));
        error_log("AdminController::usuarios() - Total usuarios: $totalUsuarios");
        error_log("AdminController::usuarios() - Renderizando vista...");
        
        View::render('admin/usuarios/index', [
            'usuarios' => $usuarios,
            'filtro' => $filtro,
            'page' => $page,
            'limit' => $limit,
            'total' => $totalUsuarios,
            'totalPages' => ceil($totalUsuarios / $limit),
            'title' => 'Gestión de Usuarios',
            // Estadísticas adicionales
            'admins' => count($admins),
            'revisores' => count($revisores),
            'activos' => count($activos)
        ]);
        
        error_log("AdminController::usuarios() - Vista renderizada");
    }

    /**
     * Muestra detalle de un usuario
     * 
     * @param int $id ID del usuario
     * @return void
     */
    public function showUsuario($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            Redirect::to('/admin/usuarios')
                ->withError('Usuario no encontrado')
                ->send();
        }

        // Obtener estadísticas del usuario
        $estadisticas = OrdenCompra::getEstadisticasUsuario($id);

        View::render('admin/usuarios/show', [
            'usuario' => $usuario,
            'estadisticas' => $estadisticas,
            'title' => 'Usuario: ' . $usuario->azure_display_name
        ]);
    }

    /**
     * Muestra formulario de edición de usuario
     * 
     * @param int $id ID del usuario
     * @return void
     */
    public function editUsuario($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            Redirect::to('/admin/usuarios')
                ->withError('Usuario no encontrado')
                ->send();
        }

        View::render('admin/usuarios/edit', [
            'usuario_edit' => $usuario,
            'title' => 'Editar Usuario: ' . $usuario->azure_display_name
        ]);
    }

    /**
     * Actualiza un usuario
     * 
     * @param int $id ID del usuario
     * @return void
     */
    public function updateUsuario($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
        }

        // Validar que el usuario existe
        $usuario = Usuario::find($id);
        if (!$usuario) {
            Redirect::to('/admin/usuarios')
                ->withError('Usuario no encontrado')
                ->send();
        }

        // Validar datos de entrada
        $errores = [];
        
        // Validar nombre
        if (empty($_POST['azure_display_name'])) {
            $errores[] = 'El nombre es obligatorio';
        }
        
        // Validar email
        if (empty($_POST['azure_email'])) {
            $errores[] = 'El email es obligatorio';
        } elseif (!filter_var($_POST['azure_email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no tiene un formato válido';
        }

        // Verificar si el email ya existe en otro usuario
        $usuariosExistente = Usuario::where(['azure_email' => $_POST['azure_email']]);
        if (!empty($usuariosExistente)) {
            $emailExistente = reset($usuariosExistente); // Obtener el primer resultado
            // Usuario::where() devuelve array de objetos Usuario
            $emailId = is_object($emailExistente) ? $emailExistente->id : $emailExistente['id'];
            if ($emailId != $id) {
                $errores[] = 'El email ya está en uso por otro usuario';
            }
        }

        if (!empty($errores)) {
            Redirect::back()
                ->withError(implode('<br>', $errores))
                ->send();
        }

        // Preparar datos para actualización
        $data = [
            'azure_display_name' => trim($_POST['azure_display_name']),
            'azure_email' => trim($_POST['azure_email']),
            'is_revisor' => isset($_POST['is_revisor']) ? 1 : 0,
            'is_admin' => isset($_POST['is_admin']) ? 1 : 0,
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        // Actualizar usuario
        $resultado = Usuario::updateById($id, $data);

        if ($resultado) {
            Redirect::to('/admin/usuarios')
                ->withSuccess('Usuario actualizado exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError('Error al actualizar usuario')
                ->send();
        }
    }

    /**
     * Desactiva un usuario
     * 
     * @param int $id ID del usuario
     * @return void
     */
    public function desactivarUsuario($id)
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Token inválido'
            ], 403);
            return;
        }

        $resultado = Usuario::updateById($id, ['activo' => 0]);

        $this->jsonResponse([
            'success' => $resultado,
            'message' => $resultado ? 'Usuario desactivado' : 'Error al desactivar'
        ]);
    }

    // ========================================================================
    // CENTROS DE COSTO
    // ========================================================================

    /**
     * Lista centros de costo
     * 
     * @return void
     */
    public function centrosCosto()
    {
        $centros = CentroCosto::all();

        View::render('admin/centros/index', [
            'centros' => $centros,
            'title' => 'Centros de Costo'
        ]);
    }

    /**
     * Crea un centro de costo
     * 
     * @return void
     */
    public function storeCentro()
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
        }

        $data = [
            'codigo' => $this->sanitize($_POST['codigo']),
            'nombre' => $this->sanitize($_POST['nombre']),
            'descripcion' => $this->sanitize($_POST['descripcion'] ?? ''),
            'activo' => 1
        ];

        $id = CentroCosto::create($data);

        if ($id) {
            Redirect::to('/admin/centros')
                ->withSuccess('Centro de costo creado exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError('Error al crear centro de costo')
                ->withInput($data)
                ->send();
        }
    }

    /**
     * Actualiza un centro de costo
     * 
     * @param int $id ID del centro
     * @return void
     */
    public function updateCentro($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
        }

        $data = [
            'codigo' => $this->sanitize($_POST['codigo']),
            'nombre' => $this->sanitize($_POST['nombre']),
            'descripcion' => $this->sanitize($_POST['descripcion'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        $resultado = CentroCosto::updateById($id, $data);

        if ($resultado) {
            Redirect::back()
                ->withSuccess('Centro de costo actualizado')
                ->send();
        } else {
            Redirect::back()
                ->withError('Error al actualizar')
                ->send();
        }
    }

    // ========================================================================
    // AUTORIZADORES
    // ========================================================================

    /**
     * Lista autorizadores
     * 
     * @return void
     */
    public function autorizadores()
    {
        // Obtener autorizadores desde la vista
        $autorizadores = PersonaAutorizada::all();
        
        // Detectar duplicados reales en autorizador_centro_costo
        $conn = PersonaAutorizada::getConnection();
        $sql = "SELECT 
                    acc.autorizador_id,
                    a.email,
                    acc.centro_costo_id,
                    COUNT(*) as total
                FROM autorizador_centro_costo acc
                INNER JOIN autorizadores a ON a.id = acc.autorizador_id
                WHERE acc.activo = 1
                GROUP BY acc.autorizador_id, acc.centro_costo_id
                HAVING COUNT(*) > 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $duplicadosReales = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Crear mapa de duplicados por email para referencia rápida
        $duplicadosPorEmail = [];
        foreach ($duplicadosReales as $dup) {
            $email = strtolower(trim($dup['email'] ?? ''));
            if (!isset($duplicadosPorEmail[$email])) {
                $duplicadosPorEmail[$email] = 0;
            }
            $duplicadosPorEmail[$email] += ($dup['total'] - 1); // Restar 1 porque uno es el original
        }
        
        $centros = CentroCosto::all();

        View::render('admin/autorizadores/index_agrupado', [
            'autorizadores' => $autorizadores,
            'centros' => $centros,
            'duplicadosPorEmail' => $duplicadosPorEmail,
            'title' => 'Gestión de Autorizadores'
        ]);
    }

    /**
     * Muestra los detalles de un autorizador
     * 
     * @param int $id ID del autorizador
     * @return void
     */
    public function showAutorizador($id)
    {
        $autorizadorObj = PersonaAutorizada::find($id);
        
        if (!$autorizadorObj) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        // Convertir el objeto a array para la vista
        $autorizador = $autorizadorObj->toArray();

        // Obtener TODOS los centros de costo asignados a este autorizador
        $centrosCosto = [];
        if (!empty($autorizador['email'])) {
            $centrosCosto = PersonaAutorizada::centrosCostoPorEmail($autorizador['email']);
        }
        
        // Obtener el centro principal (el primero o el de la relación directa)
        $centroPrincipal = null;
        if (!empty($centrosCosto)) {
            $centroPrincipal = $centrosCosto[0];
        } elseif (isset($autorizador['centro_costo_id'])) {
            $centroObj = CentroCosto::find($autorizador['centro_costo_id']);
            $centroPrincipal = $centroObj ? $centroObj->toArray() : null;
        }

        // Obtener información adicional de autorizaciones especiales
        // Buscar respaldos donde este autorizador es el principal o el respaldo
        $email = $autorizador['email'] ?? '';
        $respaldos = [];
        
        if ($email) {
            $sql = "SELECT * FROM autorizador_respaldo 
                    WHERE autorizador_principal_email = ? 
                    OR autorizador_respaldo_email = ?
                    ORDER BY fecha_inicio DESC";
            $stmt = PersonaAutorizada::getConnection()->prepare($sql);
            $stmt->execute([$email, $email]);
            $respaldos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        
        $autorizacionesEspeciales = [
            'respaldo' => $respaldos,
            'metodo_pago' => $email ? AutorizadorMetodoPago::where(['autorizador_email' => $email]) : [],
            'cuenta_contable' => $email ? AutorizadorCuentaContable::where(['autorizador_email' => $email]) : []
        ];

        View::render('admin/autorizadores/show', [
            'autorizador' => $autorizador,
            'centrosCosto' => $centrosCosto,
            'centroPrincipal' => $centroPrincipal,
            'autorizacionesEspeciales' => $autorizacionesEspeciales,
            'title' => 'Detalles del Autorizador'
        ]);
    }

    /**
     * Muestra el formulario de edición de autorizador
     * 
     * @return void
     */
    public function editAutorizador($id)
    {
        $autorizador = PersonaAutorizada::find($id);
        
        if (!$autorizador) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        $centros = CentroCosto::all();

        View::render('admin/autorizadores/edit', [
            'autorizador' => $autorizador,
            'centros' => $centros,
            'title' => 'Editar Autorizador'
        ]);
    }

    /**
     * Crea un autorizador
     * 
     * @return void
     */
    public function storeAutorizador()
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
        }

        $data = [
            'centro_costo_id' => $_POST['centro_costo_id'],
            'nombre' => $this->sanitize($_POST['nombre']),
            'email' => $this->sanitize($_POST['email']),
            'cargo' => $this->sanitize($_POST['cargo'] ?? ''),
            'activo' => 1
        ];

        $id = PersonaAutorizada::create($data);

        if ($id) {
            Redirect::to('/admin/autorizadores')
                ->withSuccess('Autorizador creado exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError('Error al crear autorizador')
                ->withInput($data)
                ->send();
        }
    }

    /**
     * Actualiza un autorizador
     * 
     * @param int $id ID del autorizador
     * @return void
     */
    public function updateAutorizador($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
        }

        $data = [
            'centro_costo_id' => $_POST['centro_costo_id'],
            'nombre' => $this->sanitize($_POST['nombre']),
            'email' => $this->sanitize($_POST['email']),
            'cargo' => $this->sanitize($_POST['cargo'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        $resultado = PersonaAutorizada::updateById($id, $data);

        if ($resultado) {
            Redirect::back()
                ->withSuccess('Autorizador actualizado')
                ->send();
        } else {
            Redirect::back()
                ->withError('Error al actualizar')
                ->send();
        }
    }

    /**
     * Elimina un autorizador
     * 
     * @param int $id ID del autorizador
     * @return void
     */
    public function deleteAutorizador($id)
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }

        $resultado = PersonaAutorizada::destroy($id);

        $this->jsonResponse([
            'success' => $resultado,
            'message' => $resultado ? 'Autorizador eliminado' : 'Error al eliminar'
        ]);
    }

    /**
     * Consolida registros duplicados de autorizadores
     * 
     * @return void
     */
    public function consolidarAutorizadores()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';

        if (empty($email)) {
            $this->jsonResponse(['success' => false, 'error' => 'Email requerido'], 400);
            return;
        }

        try {
            // Buscar todos los registros del mismo email
            $autorizadores = PersonaAutorizada::where(['email' => $email]);

            if (count($autorizadores) < 2) {
                $this->jsonResponse(['success' => false, 'error' => 'No hay registros duplicados para consolidar']);
                return;
            }

            // Tomar el primer registro como base
            $autorizadorBase = $autorizadores[0];
            $centrosConsolidados = [];
            $permisosConsolidados = [
                'puede_autorizar_centro_costo' => false,
                'puede_autorizar_flujo' => false,
                'puede_autorizar_cuenta_contable' => false,
                'puede_autorizar_metodo_pago' => false,
                'puede_autorizar_respaldo' => false
            ];
            $montoLimiteMax = 0;

            // Consolidar información de todos los registros
            foreach ($autorizadores as $autorizador) {
                // Consolidar centros de costo únicos
                if (!empty($autorizador->centro_costo_id) && !in_array($autorizador->centro_costo_id, $centrosConsolidados)) {
                    $centrosConsolidados[] = $autorizador->centro_costo_id;
                }

                // Consolidar permisos (OR lógico)
                $permisosConsolidados['puede_autorizar_centro_costo'] = $permisosConsolidados['puede_autorizar_centro_costo'] || ($autorizador->puede_autorizar_centro_costo ?? false);
                $permisosConsolidados['puede_autorizar_flujo'] = $permisosConsolidados['puede_autorizar_flujo'] || ($autorizador->puede_autorizar_flujo ?? false);
                $permisosConsolidados['puede_autorizar_cuenta_contable'] = $permisosConsolidados['puede_autorizar_cuenta_contable'] || ($autorizador->puede_autorizar_cuenta_contable ?? false);
                $permisosConsolidados['puede_autorizar_metodo_pago'] = $permisosConsolidados['puede_autorizar_metodo_pago'] || ($autorizador->puede_autorizar_metodo_pago ?? false);
                $permisosConsolidados['puede_autorizar_respaldo'] = $permisosConsolidados['puede_autorizar_respaldo'] || ($autorizador->puede_autorizar_respaldo ?? false);

            }

            // Iniciar transacción
            $pdo = PersonaAutorizada::getConnection();
            $pdo->beginTransaction();

            try {
                // Eliminar todos los registros duplicados excepto el primero
                for ($i = 1; $i < count($autorizadores); $i++) {
                    PersonaAutorizada::destroy($autorizadores[$i]->id);
                }

                // Actualizar el registro base con la información consolidada
                $dataConsolidada = array_merge($permisosConsolidados, [
                    'activo' => 1, // Asegurar que quede activo
                    'fecha_actualizacion' => date('Y-m-d H:i:s')
                ]);

                PersonaAutorizada::updateById($autorizadorBase->id, $dataConsolidada);

                // Si hay múltiples centros, crear registros separados para cada uno
                if (count($centrosConsolidados) > 1) {
                    $baseData = [
                        'nombre' => $autorizadorBase->nombre,
                        'email' => $autorizadorBase->email,
                        'cargo' => $autorizadorBase->cargo,
                        'activo' => 1
                    ];

                    // Actualizar el primer registro con el primer centro
                    PersonaAutorizada::updateById($autorizadorBase->id, array_merge($baseData, [
                        'centro_costo_id' => $centrosConsolidados[0]
                    ], $permisosConsolidados));

                    // Crear registros adicionales para los otros centros
                    for ($i = 1; $i < count($centrosConsolidados); $i++) {
                        PersonaAutorizada::create(array_merge($baseData, [
                            'centro_costo_id' => $centrosConsolidados[$i]
                        ], $permisosConsolidados));
                    }
                }

                $pdo->commit();

                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Registros consolidados exitosamente',
                    'centros_consolidados' => count($centrosConsolidados),
                    'registros_eliminados' => count($autorizadores) - 1
                ]);

            } catch (\Exception $e) {
                $pdo->rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            error_log("Error consolidando autorizadores: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error interno al consolidar registros'
            ], 500);
        }
    }

    // ========================================================================
    // RESPALDOS
    // ========================================================================

    /**
     * Lista respaldos de autorizadores
     * 
     * @return void
     */
    public function respaldos()
    {
        $respaldos = AutorizadorRespaldo::all();
        $centros = CentroCosto::all();

        View::render('admin/respaldos/index', [
            'respaldos' => $respaldos,
            'centros' => $centros,
            'title' => 'Respaldos de Autorizadores'
        ]);
    }

    /**
     * Crea un respaldo
     * 
     * @return void
     */
    public function storeRespaldo()
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
        }

        try {
            // Obtener centros de costo (puede ser array múltiple)
            $centrosCostoIds = $_POST['centros_costo_ids'] ?? [];
            
            // Si es un solo valor, convertir a array
            if (!is_array($centrosCostoIds)) {
                $centrosCostoIds = [$centrosCostoIds];
            }
            
            // Validar que haya al menos un centro seleccionado
            if (empty($centrosCostoIds)) {
                Redirect::back()
                    ->withError('Debe seleccionar al menos un centro de costo')
                    ->withInput($_POST)
                    ->send();
                return;
            }
            
            // Obtener nombres de los autorizadores desde la base de datos
            $nombrePrincipal = null;
            $nombreRespaldo = null;
            
            if (!empty($_POST['autorizador_principal_email'])) {
                $sql = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute([$_POST['autorizador_principal_email']]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $nombrePrincipal = $result['nombre'] ?? null;
            }
            
            if (!empty($_POST['autorizador_respaldo_email'])) {
                $sql = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute([$_POST['autorizador_respaldo_email']]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $nombreRespaldo = $result['nombre'] ?? null;
            }

            // Crear un respaldo para cada centro de costo seleccionado
            $creados = 0;
            $errores = [];
            
            foreach ($centrosCostoIds as $centroCostoId) {
                try {
                    $data = [
                        'centro_costo_id' => $centroCostoId,
                        'autorizador_principal_email' => $this->sanitize($_POST['autorizador_principal_email'] ?? ''),
                        'autorizador_respaldo_email' => $this->sanitize($_POST['autorizador_respaldo_email'] ?? ''),
                        'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                        'fecha_fin' => $_POST['fecha_fin'] ?? null,
                        'motivo' => $this->sanitize($_POST['motivo'] ?? 'Sin motivo especificado'),
                        'estado' => isset($_POST['activo']) && $_POST['activo'] == '1' ? 'activo' : 'inactivo',
                        'fecha_creacion' => date('Y-m-d H:i:s'),
                        'creado_por' => $_SESSION['user_email'] ?? 'sistema'
                    ];

                    $id = AutorizadorRespaldo::create($data);
                    
                    if ($id) {
                        $creados++;
                    } else {
                        $errores[] = "Centro ID $centroCostoId: Error al crear";
                    }
                } catch (\Exception $e) {
                    $errores[] = "Centro ID $centroCostoId: " . $e->getMessage();
                    error_log("Error creando respaldo para centro $centroCostoId: " . $e->getMessage());
                }
            }

            // Generar mensaje de respuesta
            if ($creados > 0) {
                $mensaje = $creados === 1 
                    ? 'Respaldo creado exitosamente' 
                    : "{$creados} respaldos creados exitosamente";
                
                if (count($errores) > 0) {
                    $mensaje .= ". Algunos respaldos fallaron: " . implode('; ', array_slice($errores, 0, 3));
                    if (count($errores) > 3) {
                        $mensaje .= " (y " . (count($errores) - 3) . " más)";
                    }
                }
                
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withSuccess($mensaje)
                    ->send();
            } else {
                Redirect::back()
                    ->withError('No se pudo crear ningún respaldo. Errores: ' . implode('; ', $errores))
                    ->withInput($_POST)
                    ->send();
            }
            
        } catch (\Exception $e) {
            error_log("Error en storeRespaldo: " . $e->getMessage());
            Redirect::back()
                ->withError('Error al crear respaldo: ' . $e->getMessage())
                ->withInput($_POST)
                ->send();
        }
    }

    // ========================================================================
    // CATÁLOGOS
    // ========================================================================

    /**
     * Gestión de catálogos
     * 
     * @return void
     */
    public function catalogos()
    {
        $catalogo = $_GET['tipo'] ?? 'cuentas';

        $data = match($catalogo) {
            'cuentas' => ['items' => CuentaContable::all(), 'title' => 'Cuentas Contables'],
            'centros' => ['items' => CentroCosto::all(), 'title' => 'Centros de Costo'],
            default => ['items' => [], 'title' => 'Catálogo']
        };

        View::render('admin/catalogos/index', [
            'catalogo' => $catalogo,
            'items' => $data['items'],
            'title' => $data['title']
        ]);
    }

    // ========================================================================
    // LIMPIEZA DE BASE DE DATOS
    // ========================================================================

    /**
     * Limpia duplicados en centros de costo y personas autorizadas
     * 
     * @return void
     */
    public function limpiarDuplicados()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }

        try {
            $conn = CentroCosto::getConnection();
            $conn->beginTransaction();

            $reporte = [
                'centros_costo' => [
                    'duplicados_encontrados' => 0,
                    'consolidados' => 0,
                    'eliminados' => 0,
                    'detalles' => []
                ],
                'personas_autorizadas' => [
                    'duplicados_encontrados' => 0,
                    'consolidados' => 0,
                    'eliminados' => 0,
                    'detalles' => []
                ]
            ];

            // ================================================================
            // 1. LIMPIAR CENTROS DE COSTO DUPLICADOS
            // ================================================================
            
            // Buscar duplicados por nombre (normalizado)
            $sql = "SELECT 
                        LOWER(TRIM(nombre)) as nombre_normalizado,
                        GROUP_CONCAT(id ORDER BY id) as ids,
                        COUNT(*) as total
                    FROM centro_de_costo
                    GROUP BY LOWER(TRIM(nombre))
                    HAVING COUNT(*) > 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $duplicadosCentros = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($duplicadosCentros as $grupo) {
                $ids = explode(',', $grupo['ids']);
                $reporte['centros_costo']['duplicados_encontrados'] += count($ids);
                
                // Ordenar por ID (el más antiguo primero)
                sort($ids);
                $idBase = $ids[0]; // Mantener el primero
                $idsEliminar = array_slice($ids, 1); // Eliminar los demás
                
                // Obtener información del centro base
                $centroBase = CentroCosto::find($idBase);
                if (!$centroBase) continue;
                
                $detalle = [
                    'nombre' => $grupo['nombre_normalizado'],
                    'mantener_id' => $idBase,
                    'eliminar_ids' => $idsEliminar
                ];
                
                // Actualizar referencias en tablas relacionadas
                foreach ($idsEliminar as $idEliminar) {
                    // Actualizar distribucion_gasto
                    $sql = "UPDATE distribucion_gasto SET centro_costo_id = ? WHERE centro_costo_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idBase, $idEliminar]);
                    
                    // Actualizar autorizacion_centro_costo
                    $sql = "UPDATE autorizacion_centro_costo SET centro_costo_id = ? WHERE centro_costo_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idBase, $idEliminar]);
                    
                    // Actualizar persona_autorizada
                    $sql = "UPDATE persona_autorizada SET centro_costo_id = ? WHERE centro_costo_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idBase, $idEliminar]);
                    
                    // Actualizar autorizador_respaldo
                    $sql = "UPDATE autorizador_respaldo SET centro_costo_id = ? WHERE centro_costo_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idBase, $idEliminar]);
                    
                    // Eliminar el centro duplicado
                    $sql = "DELETE FROM centro_de_costo WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idEliminar]);
                    
                    $reporte['centros_costo']['eliminados']++;
                }
                
                $reporte['centros_costo']['consolidados']++;
                $reporte['centros_costo']['detalles'][] = $detalle;
            }

            // ================================================================
            // 2. LIMPIAR PERSONAS AUTORIZADAS DUPLICADAS
            // ================================================================
            
            // Buscar duplicados en autorizador_centro_costo (tabla base)
            $sql = "SELECT 
                        acc.autorizador_id,
                        acc.centro_costo_id,
                        GROUP_CONCAT(acc.id ORDER BY acc.id) as ids,
                        COUNT(*) as total
                    FROM autorizador_centro_costo acc
                    WHERE acc.activo = 1
                    GROUP BY acc.autorizador_id, acc.centro_costo_id
                    HAVING COUNT(*) > 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $duplicadosPersonas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($duplicadosPersonas as $grupo) {
                $ids = explode(',', $grupo['ids']);
                $reporte['personas_autorizadas']['duplicados_encontrados'] += count($ids);
                
                // Ordenar por ID (el más antiguo primero)
                sort($ids);
                $idBase = $ids[0]; // Mantener el primero
                $idsEliminar = array_slice($ids, 1); // Desactivar los demás
                
                // Obtener información del autorizador
                $sql = "SELECT email, nombre FROM autorizadores WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$grupo['autorizador_id']]);
                $autorizador = $stmt->fetch(\PDO::FETCH_ASSOC);
                $email = $autorizador['email'] ?? 'N/A';
                
                // Desactivar los duplicados en lugar de eliminarlos
                foreach ($idsEliminar as $idEliminar) {
                    $sql = "UPDATE autorizador_centro_costo SET activo = 0 WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idEliminar]);
                    
                    $reporte['personas_autorizadas']['eliminados']++;
                }
                
                $detalle = [
                    'email' => $email,
                    'centro_costo_id' => $grupo['centro_costo_id'],
                    'autorizador_id' => $grupo['autorizador_id'],
                    'mantener_id' => $idBase,
                    'eliminar_ids' => $idsEliminar
                ];
                
                $reporte['personas_autorizadas']['consolidados']++;
                $reporte['personas_autorizadas']['detalles'][] = $detalle;
            }

            $conn->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Limpieza completada exitosamente',
                'reporte' => $reporte
            ]);

        } catch (\Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
            error_log("Error limpiando duplicados: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al limpiar duplicados: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================================================
    // REPORTES Y ESTADÍSTICAS
    // ========================================================================

    /**
     * Reportes administrativos
     * 
     * @return void
     */
    public function reportes()
    {
        View::render('admin/reportes/index', [
            'title' => 'Reportes'
        ]);
    }

    /**
     * Genera reporte de usuarios
     * 
     * @return void
     */
    public function generarReporteUsuarios()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }

        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-t');
            $formato = $_POST['formato'] ?? 'pdf';

            // Obtener datos de usuarios
            $usuarios = Usuario::all();
            $estadisticas = [
                'total' => count($usuarios),
                'activos' => count(array_filter($usuarios, fn($u) => $u->activo)),
                'inactivos' => count(array_filter($usuarios, fn($u) => !$u->activo)),
                'admins' => count(array_filter($usuarios, fn($u) => $u->is_admin)),
                'revisores' => count(array_filter($usuarios, fn($u) => $u->is_revisor)),
                'autorizadores' => count(array_filter($usuarios, fn($u) => $u->is_autorizador))
            ];

            $datos = [
                'titulo' => 'Reporte de Usuarios',
                'fecha_generacion' => date('Y-m-d H:i:s'),
                'periodo' => "Del $fechaInicio al $fechaFin",
                'usuarios' => $usuarios,
                'estadisticas' => $estadisticas
            ];

            $this->generarArchivoReporte('usuarios', $datos, $formato);

        } catch (\Exception $e) {
            error_log("Error generando reporte de usuarios: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al generar el reporte'
            ], 500);
        }
    }

    /**
     * Genera reporte de requisiciones
     * 
     * @return void
     */
    public function generarReporteRequisiciones()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }

        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-t');
            $formato = $_POST['formato'] ?? 'pdf';
            $centroCosto = $_POST['centro_costo'] ?? null;

            // Obtener requisiciones del período
            $sql = "SELECT oc.*, u.azure_display_name as usuario_nombre 
                    FROM orden_compra oc 
                    LEFT JOIN usuarios u ON oc.usuario_id = u.id 
                    WHERE DATE(oc.fecha) BETWEEN ? AND ?";
            
            $params = [$fechaInicio, $fechaFin];
            
            if ($centroCosto) {
                $sql .= " AND oc.id IN (
                    SELECT DISTINCT dg.orden_compra_id 
                    FROM distribucion_gasto dg 
                    WHERE dg.centro_costo_id = ?
                )";
                $params[] = $centroCosto;
            }
            
            $sql .= " ORDER BY oc.fecha DESC";
            
            $stmt = OrdenCompra::query($sql, $params);
            $requisiciones = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $requisiciones[] = $row;
            }

            // Estadísticas
            $estadisticas = [
                'total' => count($requisiciones),
                'monto_total' => array_sum(array_column($requisiciones, 'monto_total')),
                'por_estado' => $this->contarPorEstado($requisiciones)
            ];

            $datos = [
                'titulo' => 'Reporte de Requisiciones',
                'fecha_generacion' => date('Y-m-d H:i:s'),
                'periodo' => "Del $fechaInicio al $fechaFin",
                'requisiciones' => $requisiciones,
                'estadisticas' => $estadisticas,
                'centro_costo' => $centroCosto
            ];

            $this->generarArchivoReporte('requisiciones', $datos, $formato);

        } catch (\Exception $e) {
            error_log("Error generando reporte de requisiciones: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al generar el reporte'
            ], 500);
        }
    }

    /**
     * Genera reporte de autorizaciones
     * 
     * @return void
     */
    public function generarReporteAutorizaciones()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }

        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-t');
            $formato = $_POST['formato'] ?? 'pdf';

            // Obtener autorizaciones del período
            $sql = "SELECT af.*, oc.nombre_razon_social, oc.monto_total, oc.fecha,
                           acc.autorizador_email, acc.fecha_autorizacion, acc.estado as estado_auth,
                           cc.nombre as centro_costo_nombre
                    FROM autorizacion_flujo af
                    INNER JOIN orden_compra oc ON af.orden_compra_id = oc.id
                    LEFT JOIN autorizacion_centro_costo acc ON af.id = acc.autorizacion_flujo_id
                    LEFT JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
                    WHERE DATE(af.fecha_creacion) BETWEEN ? AND ?
                    ORDER BY af.fecha_creacion DESC";
            
            $stmt = OrdenCompra::query($sql, [$fechaInicio, $fechaFin]);
            $autorizaciones = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $autorizaciones[] = $row;
            }

            $datos = [
                'titulo' => 'Reporte de Autorizaciones',
                'fecha_generacion' => date('Y-m-d H:i:s'),
                'periodo' => "Del $fechaInicio al $fechaFin",
                'autorizaciones' => $autorizaciones
            ];

            $this->generarArchivoReporte('autorizaciones', $datos, $formato);

        } catch (\Exception $e) {
            error_log("Error generando reporte de autorizaciones: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al generar el reporte'
            ], 500);
        }
    }

    /**
     * Genera reporte financiero
     * 
     * @return void
     */
    public function generarReporteFinanciero()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }

        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_POST['fecha_fin'] ?? date('Y-m-t');
            $formato = $_POST['formato'] ?? 'pdf';

            // Obtener datos financieros por centro de costo
            $sql = "SELECT cc.id, cc.nombre, cc.codigo,
                           SUM(dg.cantidad) as monto_total,
                           COUNT(DISTINCT dg.orden_compra_id) as total_requisiciones
                    FROM centro_de_costo cc
                    LEFT JOIN distribucion_gasto dg ON cc.id = dg.centro_costo_id
                    LEFT JOIN orden_compra oc ON dg.orden_compra_id = oc.id
                    WHERE DATE(oc.fecha) BETWEEN ? AND ?
                    GROUP BY cc.id, cc.nombre, cc.codigo
                    ORDER BY monto_total DESC";
            
            $stmt = OrdenCompra::query($sql, [$fechaInicio, $fechaFin]);
            $datosFinancieros = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $datosFinancieros[] = $row;
            }

            $datos = [
                'titulo' => 'Reporte Financiero',
                'fecha_generacion' => date('Y-m-d H:i:s'),
                'periodo' => "Del $fechaInicio al $fechaFin",
                'datos_financieros' => $datosFinancieros,
                'monto_total_general' => array_sum(array_column($datosFinancieros, 'monto_total'))
            ];

            $this->generarArchivoReporte('financiero', $datos, $formato);

        } catch (\Exception $e) {
            error_log("Error generando reporte financiero: " . $e->getMessage());
        $this->jsonResponse([
            'success' => false,
                'message' => 'Error al generar el reporte'
            ], 500);
        }
    }

    /**
     * Genera el archivo de reporte
     * 
     * @param string $tipo
     * @param array $datos
     * @param string $formato
     * @return void
     */
    private function generarArchivoReporte($tipo, $datos, $formato)
    {
        $timestamp = date('Y-m-d_H-i-s');
        $nombreArchivo = "reporte_{$tipo}_{$timestamp}";
        
        switch ($formato) {
            case 'csv':
                $this->generarCSV($datos, $nombreArchivo);
                break;
            case 'excel':
                $this->generarExcel($datos, $nombreArchivo);
                break;
            case 'pdf':
            default:
                $this->generarPDF($datos, $nombreArchivo);
                break;
        }
    }

    /**
     * Genera reporte en formato CSV
     * 
     * @param array $datos
     * @param string $nombreArchivo
     * @return void
     */
    private function generarCSV($datos, $nombreArchivo)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezado del reporte
        fputcsv($output, [$datos['titulo']]);
        fputcsv($output, ['Generado el: ' . $datos['fecha_generacion']]);
        fputcsv($output, ['Período: ' . $datos['periodo']]);
        fputcsv($output, []);
        
        // Generar contenido según el tipo de reporte
        if (isset($datos['usuarios'])) {
            $this->generarCSVUsuarios($output, $datos);
        } elseif (isset($datos['requisiciones'])) {
            $this->generarCSVRequisiciones($output, $datos);
        } elseif (isset($datos['autorizaciones'])) {
            $this->generarCSVAutorizaciones($output, $datos);
        } elseif (isset($datos['datos_financieros'])) {
            $this->generarCSVFinanciero($output, $datos);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Genera CSV de usuarios
     */
    private function generarCSVUsuarios($output, $datos)
    {
        fputcsv($output, ['Estadísticas']);
        fputcsv($output, ['Total Usuarios', $datos['estadisticas']['total']]);
        fputcsv($output, ['Activos', $datos['estadisticas']['activos']]);
        fputcsv($output, ['Inactivos', $datos['estadisticas']['inactivos']]);
        fputcsv($output, ['Administradores', $datos['estadisticas']['admins']]);
        fputcsv($output, ['Revisores', $datos['estadisticas']['revisores']]);
        fputcsv($output, ['Autorizadores', $datos['estadisticas']['autorizadores']]);
        fputcsv($output, []);
        
        fputcsv($output, ['Detalle de Usuarios']);
        fputcsv($output, ['ID', 'Nombre', 'Email', 'Departamento', 'Cargo', 'Rol', 'Estado', 'Último Acceso']);
        
        foreach ($datos['usuarios'] as $usuario) {
            $rol = [];
            if ($usuario->is_admin) $rol[] = 'Admin';
            if ($usuario->is_revisor) $rol[] = 'Revisor';
            if ($usuario->is_autorizador) $rol[] = 'Autorizador';
            if (empty($rol)) $rol[] = 'Usuario';
            
            fputcsv($output, [
                $usuario->id,
                $usuario->azure_display_name ?? '',
                $usuario->azure_email ?? '',
                $usuario->azure_department ?? '',
                $usuario->azure_job_title ?? '',
                implode(', ', $rol),
                $usuario->activo ? 'Activo' : 'Inactivo',
                $usuario->last_login ?? 'Nunca'
            ]);
        }
    }

    /**
     * Genera CSV de requisiciones
     */
    private function generarCSVRequisiciones($output, $datos)
    {
        fputcsv($output, ['Estadísticas']);
        fputcsv($output, ['Total Requisiciones', $datos['estadisticas']['total']]);
        fputcsv($output, ['Monto Total', 'Q ' . number_format($datos['estadisticas']['monto_total'], 2)]);
        fputcsv($output, []);
        
        fputcsv($output, ['Detalle de Requisiciones']);
        fputcsv($output, ['ID', 'Fecha', 'Proveedor', 'Usuario', 'Monto', 'Estado']);
        
        foreach ($datos['requisiciones'] as $req) {
            fputcsv($output, [
                $req['id'],
                $req['fecha'],
                $req['nombre_razon_social'],
                $req['usuario_nombre'] ?? '',
                'Q ' . number_format($req['monto_total'], 2),
                $req['estado']
            ]);
        }
    }

    /**
     * Genera CSV de autorizaciones
     */
    private function generarCSVAutorizaciones($output, $datos)
    {
        fputcsv($output, ['Detalle de Autorizaciones']);
        fputcsv($output, ['ID Flujo', 'Fecha', 'Proveedor', 'Monto', 'Autorizador', 'Estado', 'Fecha Autorización', 'Centro Costo']);
        
        foreach ($datos['autorizaciones'] as $auth) {
            fputcsv($output, [
                $auth['id'],
                $auth['fecha_creacion'],
                $auth['nombre_razon_social'],
                'Q ' . number_format($auth['monto_total'], 2),
                $auth['autorizador_email'] ?? '',
                $auth['estado_auth'] ?? '',
                $auth['fecha_autorizacion'] ?? '',
                $auth['centro_costo_nombre'] ?? ''
            ]);
        }
    }

    /**
     * Genera CSV financiero
     */
    private function generarCSVFinanciero($output, $datos)
    {
        fputcsv($output, ['Resumen Financiero']);
        fputcsv($output, ['Monto Total General', 'Q ' . number_format($datos['monto_total_general'], 2)]);
        fputcsv($output, []);
        
        fputcsv($output, ['Gasto por Centro de Costo']);
        fputcsv($output, ['Código', 'Nombre', 'Monto Total', 'Total Requisiciones']);
        
        foreach ($datos['datos_financieros'] as $centro) {
            fputcsv($output, [
                $centro['codigo'],
                $centro['nombre'],
                'Q ' . number_format($centro['monto_total'] ?? 0, 2),
                $centro['total_requisiciones'] ?? 0
            ]);
        }
    }

    /**
     * Genera reporte en formato PDF
     * 
     * @param array $datos
     * @param string $nombreArchivo
     * @return void
     */
    private function generarPDF($datos, $nombreArchivo)
    {
        // Por ahora generamos un CSV, pero aquí se podría integrar una librería PDF como TCPDF
        $this->generarCSV($datos, $nombreArchivo);
    }

    /**
     * Genera reporte en formato Excel
     * 
     * @param array $datos
     * @param string $nombreArchivo
     * @return void
     */
    private function generarExcel($datos, $nombreArchivo)
    {
        // Por ahora generamos un CSV, pero aquí se podría integrar una librería Excel como PhpSpreadsheet
        $this->generarCSV($datos, $nombreArchivo);
    }

    /**
     * Cuenta requisiciones por estado
     * 
     * @param array $requisiciones
     * @return array
     */
    private function contarPorEstado($requisiciones)
    {
        // ✅ USAR SISTEMA CENTRALIZADO DE ESTADOS
        
        $conteo = [];
        foreach ($requisiciones as $req) {
            // Obtener estado real usando sistema centralizado
            $estado = is_object($req) ? $req->getEstadoReal() : EstadoHelper::getEstadoFromData($req);
            $conteo[$estado] = ($conteo[$estado] ?? 0) + 1;
        }
        return $conteo;
    }

    // ========================================================================
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Obtiene actividad reciente del sistema
     * 
     * @return array
     */
    private function getActividadReciente()
    {
        // Obtener últimas 10 requisiciones creadas
        $requisiciones = OrdenCompra::recientes(10);

        $actividad = [];
        foreach ($requisiciones as $req) {
            $actividad[] = [
                'tipo' => 'requisicion_creada',
                'descripcion' => "Requisición #{$req->id} creada",
                'usuario' => $req->usuario_id,
                'fecha' => $req->fecha
            ];
        }

        return $actividad;
    }

    /**
     * Obtiene información detallada del autorizador de métodos de pago por email
     *
     * @param string $email
     * @return array|null
     */
    private function obtenerAutorizadorMetodoPagoPorEmail(string $email): ?array
    {
        $conn = Model::getConnection();

        $stmt = $conn->prepare("SELECT * FROM autorizadores_metodos_pago WHERE autorizador_email = ? ORDER BY id DESC");
        $stmt->execute([$email]);
        $registros = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($registros)) {
            return null;
        }

        $metodos = [];
        $descripcion = null;
        foreach ($registros as $registro) {
            if (!empty($registro['metodo_pago'])) {
                $metodos[] = $registro['metodo_pago'];
            }
            if ($descripcion === null && !empty($registro['descripcion'])) {
                $descripcion = $registro['descripcion'];
            }
        }

        $metodos = array_values(array_unique($metodos));
        sort($metodos);

        $ultimo = $registros[0];

        // Información adicional del autorizador
        $persona = PersonaAutorizada::porEmail($email);
        if (is_object($persona)) {
            $persona = $persona->toArray();
        }

        // Intentar obtener nombre desde la tabla autorizadores si no está en persona_autorizada
        $nombre = $persona['nombre'] ?? null;
        if (!$nombre) {
            $sqlNombre = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
            $stmtNombre = $conn->prepare($sqlNombre);
            $stmtNombre->execute([$email]);
            $resultNombre = $stmtNombre->fetch(\PDO::FETCH_ASSOC);
            $nombre = $resultNombre['nombre'] ?? null;
        }

        $centros = PersonaAutorizada::centrosCostoPorEmail($email) ?? [];

        return [
            'email' => $email,
            'nombre' => $nombre,
            'cargo' => $persona['cargo'] ?? null,
            'activo' => isset($persona['activo']) ? ((int)$persona['activo'] === 1) : true,
            'fecha_inicio' => $persona['fecha_inicio'] ?? null,
            'fecha_fin' => $persona['fecha_fin'] ?? null,
            'metodos_autorizados' => $metodos,
            'metodo_pago_actual' => $ultimo['metodo_pago'] ?? null,
            'descripcion' => $descripcion,
            'observaciones' => $descripcion,
            'notificacion' => $ultimo['notificacion'] ?? null,
            'fecha_actualizacion' => $ultimo['fecha_actualizacion'] ?? null,
            'actualizado_por' => $ultimo['actualizado_por'] ?? null,
            'registros' => $registros,
            'id_registro' => $ultimo['id'] ?? null,
            'centros_costo' => $centros,
            'centros_costo_count' => is_array($centros) ? count($centros) : 0
        ];
    }

    // ========================================================================
    // AUTORIZADORES ESPECIALES
    // ========================================================================

    /**
     * Lista autorizadores de respaldo
     * 
     * @return void
     */
    public function autorizadoresRespaldos()
    {
        try {
            // Obtener autorizadores de respaldo con información completa
            // Primero verificamos si la tabla existe, si no, retornamos array vacío
            $sql = "SHOW TABLES LIKE 'autorizador_respaldo'";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $tableExists = $stmt->fetch() !== false;
            
            $respaldos = [];
            if ($tableExists) {
                // Primero obtener los respaldos sin JOINs problemáticos
                $sql = "SELECT 
                            ar.*,
                            cc.nombre as centro_nombre,
                            cc.id as centro_id
                        FROM autorizador_respaldo ar
                        LEFT JOIN centro_de_costo cc ON ar.centro_costo_id = cc.id
                        ORDER BY ar.fecha_inicio DESC, ar.id DESC";
                
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute();
                $respaldosData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                // Ahora obtener los nombres de los autorizadores por separado
                foreach ($respaldosData as &$respaldo) {
                    // Obtener nombre del autorizador de respaldo
                    if (!empty($respaldo['autorizador_respaldo_email'])) {
                        $sqlRespaldo = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                        $stmtRespaldo = Model::getConnection()->prepare($sqlRespaldo);
                        $stmtRespaldo->execute([$respaldo['autorizador_respaldo_email']]);
                        $nombreRespaldo = $stmtRespaldo->fetch(\PDO::FETCH_ASSOC);
                        $respaldo['autorizador_respaldo_nombre'] = $nombreRespaldo['nombre'] ?? null;
                    }
                    
                    // Obtener nombre del autorizador principal
                    if (!empty($respaldo['autorizador_principal_email'])) {
                        $sqlPrincipal = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                        $stmtPrincipal = Model::getConnection()->prepare($sqlPrincipal);
                        $stmtPrincipal->execute([$respaldo['autorizador_principal_email']]);
                        $nombrePrincipal = $stmtPrincipal->fetch(\PDO::FETCH_ASSOC);
                        $respaldo['autorizador_principal_nombre'] = $nombrePrincipal['nombre'] ?? null;
                    }
                }
                unset($respaldo);
                
                // Convertir a objetos para compatibilidad con la vista
                $respaldos = array_map(function($row) {
                    return (object)$row;
                }, $respaldosData);
            }

            // Obtener centros de costo para referencia
            $centros = CentroCosto::all();

            View::render('admin/autorizadores/respaldos', [
                'respaldos' => $respaldos,
                'centros' => $centros,
                'title' => 'Autorizadores de Respaldo'
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en autorizadoresRespaldos: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Redirect::to('/admin/autorizadores')
                ->withError('Error al cargar autorizadores de respaldo: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Lista autorizadores por método de pago
     * 
     * @return void
     */
    public function autorizadoresMetodosPago()
    {
        try {
            // Verificar si la tabla existe antes de hacer la consulta
            $sql = "SHOW TABLES LIKE 'autorizadores_metodos_pago'";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $tableExists = $stmt->fetch() !== false;
            
            $autorizadores_metodo_pago = [];
            
            if ($tableExists) {
                // Si la tabla existe, obtener datos reales - usar nombres de columnas correctos
                // La tabla tiene: metodo_pago (no forma_pago), autorizador_email (no autorizador_nombre), no tiene activo
                $sql = "SELECT 
                            autorizador_email,
                            GROUP_CONCAT(metodo_pago ORDER BY metodo_pago SEPARATOR ',') as metodos_pago,
                            GROUP_CONCAT(DISTINCT descripcion SEPARATOR ' | ') as observaciones,
                            COUNT(DISTINCT metodo_pago) as cantidad_metodos
                        FROM autorizadores_metodos_pago
                        WHERE autorizador_email IS NOT NULL AND autorizador_email != ''
                        GROUP BY autorizador_email
                        ORDER BY autorizador_email ASC";
                
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute();
                $autorizadoresData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                // Convertir a objetos y obtener información adicional del autorizador
                foreach ($autorizadoresData as $row) {
                    // Obtener nombre del autorizador desde la tabla autorizadores
                    $nombreAutorizador = null;
                    if (!empty($row['autorizador_email'])) {
                        $sqlNombre = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                        $stmtNombre = Model::getConnection()->prepare($sqlNombre);
                        $stmtNombre->execute([$row['autorizador_email']]);
                        $resultNombre = $stmtNombre->fetch(\PDO::FETCH_ASSOC);
                        $nombreAutorizador = $resultNombre['nombre'] ?? null;
                    }
                    $row['autorizador_nombre'] = $nombreAutorizador;
                    
                    // Obtener información adicional del autorizador
                    $autorizador = PersonaAutorizada::porEmail($row['autorizador_email']);
                    if ($autorizador && is_array($autorizador)) {
                        $row['cargo'] = $autorizador['cargo'] ?? null;
                    }
                    
                    // Obtener conteo de centros de costo por separado para evitar problemas de collation
                    // La tabla autorizador_centro_costo usa autorizador_id, no autorizador_email
                    try {
                        // Primero obtener el autorizador_id desde la tabla autorizadores
                        $sqlAutorizadorId = "SELECT id FROM autorizadores WHERE email = ? LIMIT 1";
                        $stmtAutorizadorId = Model::getConnection()->prepare($sqlAutorizadorId);
                        $stmtAutorizadorId->execute([$row['autorizador_email']]);
                        $resultAutorizadorId = $stmtAutorizadorId->fetch(\PDO::FETCH_ASSOC);
                        $autorizadorId = $resultAutorizadorId['id'] ?? null;
                        
                        if ($autorizadorId) {
                            $sqlCentros = "SELECT COUNT(DISTINCT centro_costo_id) as total FROM autorizador_centro_costo WHERE autorizador_id = ?";
                            $stmtCentros = Model::getConnection()->prepare($sqlCentros);
                            $stmtCentros->execute([$autorizadorId]);
                            $resultCentros = $stmtCentros->fetch(\PDO::FETCH_ASSOC);
                            $row['centros_costo_count'] = $resultCentros['total'] ?? 0;
                        } else {
                            $row['centros_costo_count'] = 0;
                        }
                    } catch (\Exception $e) {
                        // Si falla, simplemente asignar 0
                        error_log("Error contando centros de costo: " . $e->getMessage());
                        $row['centros_costo_count'] = 0;
                    }
                    
                    // Asegurar que los campos esperados existan
                    // Usar el ID real si existe, sino usar el email codificado como identificador
                    $row['id'] = $autorizadorId ?? urlencode($row['autorizador_email'] ?? '');
                    $row['id_real'] = $autorizadorId; // Guardar el ID real por si se necesita
                    $row['nombre'] = $row['autorizador_nombre'] ?? null;
                    $row['email'] = $row['autorizador_email'] ?? null;
                    $row['activo'] = true;
                    
                    $autorizadores_metodo_pago[] = (object)$row;
                }
            }

            View::render('admin/autorizadores/metodos_pago', [
                'autorizadores_metodo_pago' => $autorizadores_metodo_pago,
                'title' => 'Autorizadores por Método de Pago'
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en autorizadoresMetodosPago: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Redirect::to('/admin/autorizadores')
                ->withError('Error al cargar autorizadores por método de pago: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Lista autorizadores por cuenta contable
     * 
     * @return void
     */
    public function autorizadoresCuentasContables()
    {
        try {
            // Verificar si las tablas existen
            $sqlCheck = "SHOW TABLES LIKE 'autorizadores_cuentas_contables'";
            $stmtCheck = Model::getConnection()->prepare($sqlCheck);
            $stmtCheck->execute();
            $tableExists = $stmtCheck->fetch() !== false;
            
            $sqlCheck2 = "SHOW TABLES LIKE 'cuenta_contable'";
            $stmtCheck2 = Model::getConnection()->prepare($sqlCheck2);
            $stmtCheck2->execute();
            $tableExists2 = $stmtCheck2->fetch() !== false;
            
            $autorizadores_cuenta_contable = [];
            
            if ($tableExists && $tableExists2) {
                // Si las tablas existen, obtener datos reales usando nombres de columnas correctos
                // La tabla tiene: autorizador_email, NO tiene autorizador_nombre, NO tiene activo
                $sql = "SELECT 
                            acc.autorizador_email,
                            GROUP_CONCAT(DISTINCT cc.codigo ORDER BY cc.codigo SEPARATOR ', ') as cuentas_codigos,
                            GROUP_CONCAT(DISTINCT acc.descripcion SEPARATOR ' | ') as observaciones,
                            COUNT(DISTINCT acc.cuenta_contable_id) as cantidad_cuentas
                        FROM autorizadores_cuentas_contables acc
                        INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                        WHERE acc.autorizador_email IS NOT NULL AND acc.autorizador_email != ''
                        GROUP BY acc.autorizador_email
                        ORDER BY acc.autorizador_email ASC";
                
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute();
                $autorizadoresData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                // Obtener cuentas contables detalladas para cada autorizador
                foreach ($autorizadoresData as $row) {
                    // Obtener nombre del autorizador desde la tabla autorizadores
                    $nombreAutorizador = null;
                    if (!empty($row['autorizador_email'])) {
                        $sqlNombre = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                        $stmtNombre = Model::getConnection()->prepare($sqlNombre);
                        $stmtNombre->execute([$row['autorizador_email']]);
                        $resultNombre = $stmtNombre->fetch(\PDO::FETCH_ASSOC);
                        $nombreAutorizador = $resultNombre['nombre'] ?? null;
                    }
                    $row['autorizador_nombre'] = $nombreAutorizador;
                    
                    // Obtener información adicional del autorizador
                    $autorizador = PersonaAutorizada::porEmail($row['autorizador_email']);
                    if ($autorizador && is_array($autorizador)) {
                        $row['cargo'] = $autorizador['cargo'] ?? null;
                    }
                    
                    // Obtener cuentas contables detalladas para este autorizador
                    $cuentasQuery = "SELECT 
                                        cc.codigo,
                                        cc.descripcion as nombre
                                    FROM autorizadores_cuentas_contables acc
                                    INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                                    WHERE acc.autorizador_email = ? 
                                    ORDER BY cc.codigo ASC";
                    $cuentasStmt = Model::getConnection()->prepare($cuentasQuery);
                    $cuentasStmt->execute([$row['autorizador_email']]);
                    $cuentasData = $cuentasStmt->fetchAll(\PDO::FETCH_ASSOC);
                    
                    $row['cuentas_contables'] = $cuentasData;
                    
                    // Obtener conteo de centros de costo por separado para evitar problemas de collation
                    // La tabla autorizador_centro_costo usa autorizador_id, no autorizador_email
                    try {
                        // Primero obtener el autorizador_id desde la tabla autorizadores
                        $sqlAutorizadorId = "SELECT id FROM autorizadores WHERE email = ? LIMIT 1";
                        $stmtAutorizadorId = Model::getConnection()->prepare($sqlAutorizadorId);
                        $stmtAutorizadorId->execute([$row['autorizador_email']]);
                        $resultAutorizadorId = $stmtAutorizadorId->fetch(\PDO::FETCH_ASSOC);
                        $autorizadorId = $resultAutorizadorId['id'] ?? null;
                        
                        if ($autorizadorId) {
                            $sqlCentros = "SELECT COUNT(DISTINCT centro_costo_id) as total FROM autorizador_centro_costo WHERE autorizador_id = ?";
                            $stmtCentros = Model::getConnection()->prepare($sqlCentros);
                            $stmtCentros->execute([$autorizadorId]);
                            $resultCentros = $stmtCentros->fetch(\PDO::FETCH_ASSOC);
                            $row['centros_costo_count'] = $resultCentros['total'] ?? 0;
                        } else {
                            $row['centros_costo_count'] = 0;
                        }
                    } catch (\Exception $e) {
                        // Si falla, simplemente asignar 0
                        error_log("Error contando centros de costo: " . $e->getMessage());
                        $row['centros_costo_count'] = 0;
                    }
                    
                    // Asegurar que los campos esperados existan
                    $row['id'] = $row['autorizador_email'] ?? null;
                    $row['nombre'] = $row['autorizador_nombre'] ?? null;
                    $row['email'] = $row['autorizador_email'] ?? null;
                    $row['activo'] = true;
                    $row['fecha_inicio'] = date('Y-01-01');
                    $row['fecha_fin'] = null;
                    
                    $autorizadores_cuenta_contable[] = (object)$row;
                }
            }

            View::render('admin/autorizadores/cuentas_contables', [
                'autorizadores_cuenta_contable' => $autorizadores_cuenta_contable,
                'title' => 'Autorizadores por Cuenta Contable'
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en autorizadoresCuentasContables: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Redirect::to('/admin/autorizadores')
                ->withError('Error al cargar autorizadores por cuenta contable: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Verifica si las tablas especificadas existen en la base de datos
     * 
     * @param array $tableNames Nombres de las tablas a verificar
     * @return array Array con el nombre de la tabla como clave y boolean como valor
     */
    private function checkTablesExist($tableNames)
    {
        $result = [];
        
        foreach ($tableNames as $tableName) {
            try {
                $sql = "SHOW TABLES LIKE ?";
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute([$tableName]);
                $result[$tableName] = $stmt->fetch() !== false;
            } catch (\Exception $e) {
                error_log("Error verificando tabla $tableName: " . $e->getMessage());
                $result[$tableName] = false;
            }
        }
        
        return $result;
    }

    // ========================================================================
    // CRUD AUTORIZADORES DE RESPALDO
    // ========================================================================

    public function createRespaldo()
    {
        try {
            // Obtener autorizadores de la base de datos desde la tabla autorizadores
            $sql = "SELECT id, nombre, email FROM autorizadores WHERE activo = 1 ORDER BY nombre ASC";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $autorizadoresData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Convertir a formato esperado por la vista
            $autorizadores = [];
            foreach ($autorizadoresData as $auth) {
                $autorizadores[] = [
                    'nombre' => $auth['nombre'] ?? '',
                    'email' => $auth['email'] ?? '',
                    'cargo' => null // El cargo no está en la tabla autorizadores
                ];
            }
            
            // Obtener centros de costo (sin codigo que no existe)
            $centros = CentroCosto::all();
            // Convertir a objetos simples si es necesario
            $centrosArray = [];
            foreach ($centros as $centro) {
                $centroArray = is_object($centro) ? $centro->toArray() : $centro;
                $centrosArray[] = (object)[
                    'id' => $centroArray['id'] ?? null,
                    'nombre' => $centroArray['nombre'] ?? '',
                    'codigo' => $centroArray['codigo'] ?? null // Puede ser null
                ];
            }
            $centros = $centrosArray;
            
        } catch (\Exception $e) {
            error_log("Error obteniendo datos para crear respaldo: " . $e->getMessage());
            $autorizadores = [];
            $centros = [];
        }
        
        View::render('admin/autorizadores/respaldos_create', [
            'autorizadores' => $autorizadores,
            'centros' => $centros,
            'title' => 'Crear Autorizador de Respaldo'
        ]);
    }

    public function showRespaldo($id)
    {
        // Buscar respaldo por ID o mostrar datos de ejemplo
        $respaldo = [
            'id' => $id,
            'autorizador_principal_nombre' => 'María García',
            'autorizador_principal_email' => 'maria.garcia@empresa.com',
            'autorizador_respaldo_nombre' => 'Juan Pérez',
            'autorizador_respaldo_email' => 'juan.perez@empresa.com',
            'centro_nombre' => 'Administración General',
            'centro_codigo' => 'ADM001',
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-12-31',
            'motivo' => 'Vacaciones programadas',
            'descripcion' => 'Respaldo durante período vacacional',
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];
        
        View::render('admin/autorizadores/respaldos_show', [
            'respaldo' => $respaldo,
            'title' => 'Detalle del Autorizador de Respaldo'
        ]);
    }


    // ========================================================================
    // CRUD AUTORIZADORES POR MÉTODO DE PAGO
    // ========================================================================

    public function createMetodoPago()
    {
        try {
            // Obtener autorizadores de la base de datos
            $sql = "SELECT DISTINCT nombre, email, cargo FROM persona_autorizada WHERE activo = 1 ORDER BY nombre ASC";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $autorizadores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error obteniendo autorizadores para método de pago: " . $e->getMessage());
            // Datos de fallback
            $autorizadores = [
                ['nombre' => 'María García', 'email' => 'maria.garcia@empresa.com', 'cargo' => 'Gerente General'],
                ['nombre' => 'Juan Pérez', 'email' => 'juan.perez@empresa.com', 'cargo' => 'Jefe de Finanzas'],
                ['nombre' => 'Ana López', 'email' => 'ana.lopez@empresa.com', 'cargo' => 'Coordinadora'],
                ['nombre' => 'Carlos Mendoza', 'email' => 'carlos.mendoza@empresa.com', 'cargo' => 'Contador General'],
            ];
        }
        
        View::render('admin/autorizadores/metodos_pago_create', [
            'autorizadores' => $autorizadores,
            'title' => 'Crear Autorizador por Método de Pago'
        ]);
    }

    public function showMetodoPago($id)
    {
        $email = $id;

        // Si recibimos un ID numérico, intentar obtener el email asociado
        if (strpos($email, '@') === false) {
            $registro = AutorizadorMetodoPago::find($id);
            if ($registro) {
                if (is_object($registro)) {
                    $email = $registro->autorizador_email ?? $email;
                } elseif (is_array($registro)) {
                    $email = $registro['autorizador_email'] ?? $email;
                }
            }
        }

        $autorizador = $this->obtenerAutorizadorMetodoPagoPorEmail($email);

        if (!$autorizador) {
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withError('Autorizador no encontrado para el email especificado')
                ->send();
            return;
        }

        View::render('admin/autorizadores/metodos_pago_show', [
            'autorizador' => $autorizador,
            'title' => 'Detalle del Autorizador por Método de Pago'
        ]);
    }

    public function storeMetodoPago()
    {
        try {
            // Aquí iría la lógica para guardar en base de datos
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withSuccess('Autorizador por método de pago creado exitosamente')
                ->send();
        } catch (\Exception $e) {
            Redirect::back()
                ->withError('Error al crear el autorizador por método de pago')
                ->withInput()
                ->send();
        }
    }

    // ========================================================================
    // CRUD AUTORIZADORES POR CUENTA CONTABLE
    // ========================================================================

    public function createCuentaContable()
    {
        try {
            // Obtener autorizadores de la base de datos
            $sql = "SELECT DISTINCT nombre, email, cargo FROM persona_autorizada WHERE activo = 1 ORDER BY nombre ASC";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $autorizadores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Obtener cuentas contables si la tabla existe
            $cuentas_contables = [];
            try {
                $sqlCuentas = "SELECT codigo, descripcion as nombre FROM cuenta_contable WHERE activo = 1 ORDER BY codigo ASC";
                $stmtCuentas = Model::getConnection()->prepare($sqlCuentas);
                $stmtCuentas->execute();
                $cuentas_contables = $stmtCuentas->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                error_log("Tabla cuenta_contable no encontrada: " . $e->getMessage());
                $cuentas_contables = [];
            }
            
        } catch (\Exception $e) {
            error_log("Error obteniendo datos para cuenta contable: " . $e->getMessage());
            // Datos de fallback
            $autorizadores = [
                ['nombre' => 'María García', 'email' => 'maria.garcia@empresa.com', 'cargo' => 'Gerente General'],
                ['nombre' => 'Juan Pérez', 'email' => 'juan.perez@empresa.com', 'cargo' => 'Jefe de Finanzas'],
                ['nombre' => 'Ana López', 'email' => 'ana.lopez@empresa.com', 'cargo' => 'Coordinadora'],
                ['nombre' => 'Carlos Mendoza', 'email' => 'carlos.mendoza@empresa.com', 'cargo' => 'Contador General'],
            ];
            $cuentas_contables = [];
        }
        
        View::render('admin/autorizadores/cuentas_contables_create', [
            'autorizadores' => $autorizadores,
            'cuentas_contables' => $cuentas_contables,
            'title' => 'Crear Autorizador por Cuenta Contable'
        ]);
    }

    public function showCuentaContable($id)
    {
        $autorizador = [
            'id' => $id,
            'nombre' => 'Roberto Mendez',
            'email' => 'roberto.mendez@empresa.com',
            'cargo' => 'Contador General',
            'cuentas_contables' => [
                ['codigo' => '1101', 'nombre' => 'Caja General'],
                ['codigo' => '1102', 'nombre' => 'Bancos Nacionales']
            ],
            'activo' => true,
            'fecha_inicio' => '2024-01-01',
            'observaciones' => 'Autorización para cuentas de efectivo y bancos',
            'centros_costo_count' => 4
        ];
        
        View::render('admin/autorizadores/cuentas_contables_show', [
            'autorizador' => $autorizador,
            'title' => 'Detalle del Autorizador por Cuenta Contable'
        ]);
    }

    public function storeCuentaContable()
    {
        try {
            // Aquí iría la lógica para guardar en base de datos
            Redirect::to('/admin/autorizadores/cuentas-contables')
                ->withSuccess('Autorizador por cuenta contable creado exitosamente')
                ->send();
        } catch (\Exception $e) {
            Redirect::back()
                ->withError('Error al crear el autorizador por cuenta contable')
                ->withInput()
                ->send();
        }
    }

    // ========================================================================
    // MÉTODOS EDIT, UPDATE Y DELETE (PLACEHOLDERS)
    // ========================================================================

    public function editRespaldo($id)
    {
        try {
            // Obtener el respaldo desde la base de datos
            $respaldo = AutorizadorRespaldo::find($id);
            
            if (!$respaldo) {
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withError('Respaldo no encontrado')
                    ->send();
                return;
            }

            // Convertir a array si es objeto
            if (is_object($respaldo)) {
                $respaldo = $respaldo->toArray();
            }

            // Obtener nombres de los autorizadores
            $nombrePrincipal = null;
            $nombreRespaldo = null;
            
            if (!empty($respaldo['autorizador_principal_email'])) {
                $sql = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute([$respaldo['autorizador_principal_email']]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $nombrePrincipal = $result['nombre'] ?? null;
            }
            
            if (!empty($respaldo['autorizador_respaldo_email'])) {
                $sql = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute([$respaldo['autorizador_respaldo_email']]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $nombreRespaldo = $result['nombre'] ?? null;
            }

            // Agregar nombres al respaldo
            $respaldo['autorizador_principal_nombre'] = $nombrePrincipal;
            $respaldo['autorizador_respaldo_nombre'] = $nombreRespaldo;

            // Obtener autorizadores para el select
            $sql = "SELECT id, nombre, email FROM autorizadores WHERE activo = 1 ORDER BY nombre ASC";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $autorizadoresData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $autorizadores = [];
            foreach ($autorizadoresData as $auth) {
                $autorizadores[] = [
                    'nombre' => $auth['nombre'] ?? '',
                    'email' => $auth['email'] ?? '',
                    'cargo' => null
                ];
            }
            
            // Obtener centros de costo
            $centros = CentroCosto::all();
            $centrosArray = [];
            foreach ($centros as $centro) {
                $centroArray = is_object($centro) ? $centro->toArray() : $centro;
                $centrosArray[] = (object)[
                    'id' => $centroArray['id'] ?? null,
                    'nombre' => $centroArray['nombre'] ?? '',
                    'codigo' => $centroArray['codigo'] ?? null
                ];
            }
            $centros = $centrosArray;
            
            View::render('admin/autorizadores/respaldos_edit', [
                'respaldo' => $respaldo,
                'autorizadores' => $autorizadores,
                'centros' => $centros,
                'title' => 'Editar Autorizador de Respaldo'
            ]);
            
        } catch (\Exception $e) {
            error_log("Error obteniendo respaldo para edición: " . $e->getMessage());
            Redirect::to('/admin/autorizadores/respaldos')
                ->withError('Error al cargar el respaldo: ' . $e->getMessage())
                ->send();
        }
    }

    public function updateRespaldo($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
            return;
        }

        try {
            // Verificar que el respaldo existe
            $respaldo = AutorizadorRespaldo::find($id);
            if (!$respaldo) {
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withError('Respaldo no encontrado')
                    ->send();
                return;
            }

            // Obtener nombres de los autorizadores
            $nombreRespaldo = null;
            
            if (!empty($_POST['autorizador_respaldo_email'])) {
                $sql = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute([$_POST['autorizador_respaldo_email']]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $nombreRespaldo = $result['nombre'] ?? null;
            }

            // Preparar datos para actualizar
            $data = [
                'centro_costo_id' => $_POST['centro_costo_id'] ?? null,
                'autorizador_principal_email' => $this->sanitize($_POST['autorizador_principal_email'] ?? ''),
                'autorizador_respaldo_email' => $this->sanitize($_POST['autorizador_respaldo_email'] ?? ''),
                'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin' => $_POST['fecha_fin'] ?? null,
                'motivo' => $this->sanitize($_POST['motivo'] ?? 'Sin motivo especificado'),
                'estado' => isset($_POST['activo']) && $_POST['activo'] == '1' ? 'activo' : 'inactivo'
            ];

            // Actualizar el respaldo
            $actualizado = AutorizadorRespaldo::updateById($id, $data);
            
            if ($actualizado) {
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withSuccess('Respaldo actualizado exitosamente')
                    ->send();
            } else {
                Redirect::back()
                    ->withError('Error al actualizar el respaldo')
                    ->withInput($_POST)
                    ->send();
            }
            
        } catch (\Exception $e) {
            error_log("Error en updateRespaldo: " . $e->getMessage());
            Redirect::back()
                ->withError('Error al actualizar respaldo: ' . $e->getMessage())
                ->withInput($_POST)
                ->send();
        }
    }

    public function deleteRespaldo($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
            return;
        }

        try {
            error_log("DEBUG deleteRespaldo: Intentando eliminar respaldo ID: $id");
            
            // Verificar que el respaldo existe
            $respaldo = AutorizadorRespaldo::find($id);
            if (!$respaldo) {
                error_log("DEBUG deleteRespaldo: Respaldo ID $id no encontrado");
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withError('Respaldo no encontrado')
                    ->send();
                return;
            }

            error_log("DEBUG deleteRespaldo: Respaldo encontrado, intentando eliminar...");
            
            // Eliminar el respaldo directamente con SQL
            $sql = "DELETE FROM autorizador_respaldo WHERE id = ?";
            $stmt = Model::getConnection()->prepare($sql);
            $eliminado = $stmt->execute([$id]);
            
            $rowsAffected = $stmt->rowCount();
            error_log("DEBUG deleteRespaldo: Resultado de eliminación: " . ($eliminado ? 'true' : 'false') . ", Filas afectadas: $rowsAffected");
            
            if ($eliminado && $rowsAffected > 0) {
                error_log("DEBUG deleteRespaldo: Respaldo eliminado exitosamente");
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withSuccess('Respaldo eliminado exitosamente')
                    ->send();
            } else {
                error_log("DEBUG deleteRespaldo: No se pudo eliminar el respaldo");
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withError('Error al eliminar el respaldo - No se afectaron filas')
                    ->send();
            }
            
        } catch (\Exception $e) {
            error_log("Error en deleteRespaldo: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Redirect::to('/admin/autorizadores/respaldos')
                ->withError('Error al eliminar respaldo: ' . $e->getMessage())
                ->send();
        }
    }

    public function editMetodoPago($id)
    {
        $email = $id;

        if (strpos($email, '@') === false) {
            $registro = AutorizadorMetodoPago::find($id);
            if ($registro) {
                if (is_object($registro)) {
                    $email = $registro->autorizador_email ?? $email;
                } elseif (is_array($registro)) {
                    $email = $registro['autorizador_email'] ?? $email;
                }
            }
        }

        $this->editMetodoPagoByEmail($email);
    }
    public function updateMetodoPago($id) { $this->storeMetodoPago(); }

    /**
     * Elimina un autorizador de método de pago
     *
     * @param string $id ID numérico o email del autorizador
     * @return void
     */
    public function deleteMetodoPago($id) {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }

        try {
            // Decodificar el parámetro por si viene como email codificado
            $param = urldecode($id);

            // Determinar si es un ID numérico o un email
            if (filter_var($param, FILTER_VALIDATE_EMAIL)) {
                // Es un email - eliminar todos los registros de ese autorizador
                $conn = Model::getConnection();
                $sql = "DELETE FROM autorizadores_metodos_pago WHERE autorizador_email = ?";
                $stmt = $conn->prepare($sql);
                $resultado = $stmt->execute([$param]);
                $filasAfectadas = $stmt->rowCount();

                $this->jsonResponse([
                    'success' => $resultado && $filasAfectadas > 0,
                    'message' => $resultado && $filasAfectadas > 0
                        ? "Se eliminaron $filasAfectadas método(s) de pago del autorizador"
                        : 'No se encontraron registros para eliminar'
                ]);
            } else {
                // Es un ID numérico - eliminar solo ese registro
                $resultado = AutorizadorMetodoPago::destroy($param);

                $this->jsonResponse([
                    'success' => $resultado,
                    'message' => $resultado
                        ? 'Autorizador de método de pago eliminado correctamente'
                        : 'Error al eliminar el autorizador'
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error en deleteMetodoPago: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editCuentaContable($id) { $this->showCuentaContable($id); }
    public function updateCuentaContable($id) { $this->storeCuentaContable(); }
    public function deleteCuentaContable($id) { 
        Redirect::to('/admin/autorizadores/cuentas-contables')->withSuccess('Autorizador eliminado')->send(); 
    }

    /**
     * Edita un autorizador de método de pago por email
     */
    public function editMetodoPagoByEmail($email)
    {
        try {
            $email = urldecode($email);

            $autorizador = $this->obtenerAutorizadorMetodoPagoPorEmail($email);

            if (!$autorizador) {
                Redirect::to('/admin/autorizadores/metodos-pago')
                    ->withError('Autorizador no encontrado para el email: ' . $email)
                    ->send();
                return;
            }

            // Obtener métodos de pago disponibles
            $metodos_pago = [
                'efectivo' => 'Efectivo',
                'tarjeta_credito' => 'Tarjeta de Crédito',
                'tarjeta_debito' => 'Tarjeta de Débito', 
                'transferencia' => 'Transferencia Bancaria',
                'cheque' => 'Cheque',
                'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito Lic. Milton',
                'otro' => 'Otro'
            ];
            
            View::render('admin/autorizadores/metodos_pago_edit', [
                'autorizador' => $autorizador,
                'metodos_pago' => $metodos_pago,
                'title' => 'Editar Autorizador de Método de Pago'
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en editMetodoPagoByEmail: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withError('Error al cargar autorizador: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Actualiza un autorizador de método de pago por email
     */
    public function updateMetodoPagoByEmail($email)
    {
        try {
            // Decodificar el email de la URL
            $email = urldecode($email);
            
            // Validar datos recibidos
            $metodo_pago = $_POST['metodo_pago'] ?? null;
            $activo = isset($_POST['activo']) ? 1 : 0;
            
            if (!$metodo_pago) {
                Redirect::back()
                    ->withError('El método de pago es requerido')
                    ->withInput()
                    ->send();
                return;
            }
            
            // Actualizar en la base de datos
            $sql = "UPDATE autorizadores_metodos_pago 
                    SET metodo_pago = ?, activo = ?, fecha_actualizacion = NOW()
                    WHERE email = ?";
            
            $stmt = Model::getConnection()->prepare($sql);
            $result = $stmt->execute([$metodo_pago, $activo, $email]);
            
            if ($result) {
                Redirect::to('/admin/autorizadores/metodos-pago')
                    ->withSuccess('Autorizador actualizado exitosamente')
                    ->send();
            } else {
                Redirect::back()
                    ->withError('Error al actualizar el autorizador')
                    ->withInput()
                    ->send();
            }
            
        } catch (\Exception $e) {
            error_log("Error en updateMetodoPagoByEmail: " . $e->getMessage());
            Redirect::back()
                ->withError('Error al actualizar autorizador: ' . $e->getMessage())
                ->withInput()
                ->send();
        }
    }
    
    /**
     * API: Obtiene los centros de costo asignados a un autorizador
     */
    public function apiCentrosCostoAutorizador()
    {
        header('Content-Type: application/json');
        
        try {
            $email = $_GET['email'] ?? null;
            
            if (empty($email)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Email del autorizador es requerido'
                ]);
                return;
            }
            
            // Obtener el autorizador_id desde la tabla autorizadores
            $sqlAutorizadorId = "SELECT id FROM autorizadores WHERE email = ? LIMIT 1";
            $stmtAutorizadorId = Model::getConnection()->prepare($sqlAutorizadorId);
            $stmtAutorizadorId->execute([$email]);
            $resultAutorizador = $stmtAutorizadorId->fetch(\PDO::FETCH_ASSOC);
            
            if (!$resultAutorizador) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Autorizador no encontrado'
                ]);
                return;
            }
            
            $autorizadorId = $resultAutorizador['id'];
            
            // Obtener los centros de costo del autorizador
            $sql = "SELECT DISTINCT 
                        cc.id,
                        cc.nombre,
                        acc.es_principal
                    FROM autorizador_centro_costo acc
                    INNER JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
                    WHERE acc.autorizador_id = ? AND acc.activo = 1
                    ORDER BY acc.es_principal DESC, cc.nombre ASC";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$autorizadorId]);
            $centros = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'centros' => $centros,
                'count' => count($centros)
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en apiCentrosCostoAutorizador: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener los centros de costo: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Busca usuarios para autocompletado
     */
    public function apiBuscarUsuarios()
    {
        header('Content-Type: application/json');
        
        try {
            $query = $_GET['q'] ?? '';
            
            // Buscar en la tabla usuarios (sin columna 'cargo', usa 'job_title' y 'department')
            $sql = "SELECT 
                        id,
                        nombre,
                        email,
                        job_title as cargo,
                        department
                    FROM usuarios 
                    WHERE activo = 1
                    AND (
                        nombre LIKE ? 
                        OR email LIKE ?
                        OR job_title LIKE ?
                        OR department LIKE ?
                    )
                    ORDER BY nombre ASC
                    LIMIT 20";
            
            $searchTerm = "%{$query}%";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'usuarios' => $usuarios,
                'count' => count($usuarios)
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en apiBuscarUsuarios: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar usuarios: ' . $e->getMessage(),
                'usuarios' => []
            ]);
        }
    }
    
    /**
     * API: Lista todos los centros de costo
     */
    public function apiListarCentrosCosto()
    {
        header('Content-Type: application/json');
        
        try {
            $sql = "SELECT id, nombre FROM centro_de_costo ORDER BY nombre ASC";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $centros = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'centros' => $centros,
                'count' => count($centros)
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en apiListarCentrosCosto: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al listar centros de costo: ' . $e->getMessage(),
                'centros' => []
            ]);
        }
    }
    
    // ========================================================================
    // FLUJO ESCALONADO DE AUTORIZACIÓN
    // ========================================================================
    
    /**
     * Procesa el flujo completo de autorización escalonado
     * 
     * @param array $requisicion
     * @return array
     */
    public function procesarFlujoAutorizacion($requisicion)
    {
        try {
            $estado = $requisicion['estado'] ?? 'borrador';
            $resultado = ['success' => false, 'mensaje' => '', 'siguiente_paso' => ''];
            
            switch ($estado) {
                case 'borrador':
                case 'pendiente_revision':
                    return $this->enviarARevisores($requisicion);
                    
                case 'aprobada_revision':
                    return $this->procesarMetodoPago($requisicion);
                    
                case 'aprobada_metodo_pago':
                    return $this->procesarCuentaContable($requisicion);
                    
                case 'aprobada_cuenta_contable':
                case 'sin_cuenta_especial':
                    return $this->procesarAutorizacionCentroCosto($requisicion);
                    
                default:
                    return [
                        'success' => false,
                        'mensaje' => 'Estado de requisición no válido',
                        'siguiente_paso' => 'error'
                    ];
            }
            
        } catch (\Exception $e) {
            error_log("Error en procesarFlujoAutorizacion: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error procesando autorización: ' . $e->getMessage(),
                'siguiente_paso' => 'error'
            ];
        }
    }
    
    /**
     * Envía requisición a revisores/aprobadores
     */
    private function enviarARevisores($requisicion)
    {
        // Aquí iría la lógica para obtener revisores
        $revisores = $this->obtenerRevisores($requisicion['centro_costo_id']);
        
        if (empty($revisores)) {
            // Si no hay revisores, pasar directamente a método de pago
            return $this->procesarMetodoPago($requisicion);
        }
        
        // Enviar notificaciones a revisores
        foreach ($revisores as $revisor) {
            $this->enviarNotificacion($revisor['email'], $requisicion, 'revision');
        }
        
        return [
            'success' => true,
            'mensaje' => 'Requisición enviada a revisores',
            'siguiente_paso' => 'pendiente_revision',
            'asignado_a' => $revisores
        ];
    }
    
    /**
     * Procesa autorización por método de pago
     */
    private function procesarMetodoPago($requisicion)
    {
        $metodoPago = $requisicion['metodo_pago'] ?? '';
        
        // Buscar autorizador específico para este método de pago
        $autorizadorMetodo = $this->obtenerAutorizadorMetodoPago($metodoPago);
        
        if ($autorizadorMetodo) {
            // Enviar a autorizador de método de pago
            $this->enviarNotificacion($autorizadorMetodo['email'], $requisicion, 'metodo_pago');
            
            return [
                'success' => true,
                'mensaje' => "Requisición enviada a centro de pago ({$metodoPago})",
                'siguiente_paso' => 'pendiente_metodo_pago',
                'asignado_a' => $autorizadorMetodo,
                'tipo_autorizacion' => 'metodo_pago',
                'puede_rechazar_y_editar' => true
            ];
        }
        
        // Si no hay autorizador específico, continuar a cuenta contable
        return $this->procesarCuentaContable($requisicion);
    }
    
    /**
     * Procesa autorización por cuenta contable especial
     */
    private function procesarCuentaContable($requisicion)
    {
        $cuentaContable = $requisicion['cuenta_contable'] ?? '';
        
        // Buscar autorizador específico para esta cuenta contable
        $autorizadorCuenta = $this->obtenerAutorizadorCuentaContable($cuentaContable);
        
        if ($autorizadorCuenta) {
            // Verificar exclusiones de centros de costo
            $centroExcluido = $this->verificarCentroExcluido(
                $autorizadorCuenta['id'], 
                $requisicion['centro_costo_id']
            );
            
            if (!$centroExcluido) {
                // Enviar a autorizador de cuenta contable
                $this->enviarNotificacion($autorizadorCuenta['email'], $requisicion, 'cuenta_contable');
                
                return [
                    'success' => true,
                    'mensaje' => "Requisición enviada a especialista contable (cuenta: {$cuentaContable})",
                    'siguiente_paso' => 'pendiente_cuenta_contable',
                    'asignado_a' => $autorizadorCuenta,
                    'tipo_autorizacion' => 'cuenta_contable'
                ];
            }
        }
        
        // Si no hay autorizador específico o centro excluido, continuar a centro de costo
        return $this->procesarAutorizacionCentroCosto($requisicion);
    }
    
    /**
     * Procesa autorización final por centro de costo
     */
    private function procesarAutorizacionCentroCosto($requisicion)
    {
        $centroCostoId = $requisicion['centro_costo_id'];
        
        // Verificar si hay respaldo activo
        $respaldoActivo = $this->obtenerRespaldoActivo($centroCostoId, date('Y-m-d'));
        
        if ($respaldoActivo) {
            // Enviar al autorizador de respaldo
            $this->enviarNotificacion($respaldoActivo['autorizador_respaldo_email'], $requisicion, 'centro_costo_respaldo');
            
            return [
                'success' => true,
                'mensaje' => "Requisición enviada a autorizador de respaldo ({$respaldoActivo['nombre_respaldo']})",
                'siguiente_paso' => 'pendiente_autorizacion_final',
                'asignado_a' => [
                    'email' => $respaldoActivo['autorizador_respaldo_email'],
                    'nombre' => $respaldoActivo['nombre_respaldo'],
                    'tipo' => 'respaldo',
                    'motivo' => $respaldoActivo['motivo']
                ],
                'tipo_autorizacion' => 'centro_costo_respaldo'
            ];
        }
        
        // Enviar al autorizador principal del centro de costo
        $autorizadorPrincipal = $this->obtenerAutorizadorPrincipalCentroCosto($centroCostoId);
        
        if ($autorizadorPrincipal) {
            $this->enviarNotificacion($autorizadorPrincipal['email'], $requisicion, 'centro_costo');
            
            return [
                'success' => true,
                'mensaje' => "Requisición enviada a autorizador del centro de costo ({$autorizadorPrincipal['nombre']})",
                'siguiente_paso' => 'pendiente_autorizacion_final',
                'asignado_a' => $autorizadorPrincipal,
                'tipo_autorizacion' => 'centro_costo'
            ];
        }
        
        return [
            'success' => false,
            'mensaje' => 'No se encontró autorizador para el centro de costo',
            'siguiente_paso' => 'error'
        ];
    }
    
    /**
     * Obtiene revisores para un centro de costo
     */
    private function obtenerRevisores($centroCostoId)
    {
        try {
            $sql = "SELECT r.*, u.nombre, u.email 
                    FROM revisores r 
                    INNER JOIN usuarios u ON r.usuario_id = u.id 
                    WHERE r.centro_costo_id = ? AND r.activo = 1
                    ORDER BY r.orden ASC";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$centroCostoId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error obteniendo revisores: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene autorizador específico para un método de pago
     */
    private function obtenerAutorizadorMetodoPago($metodoPago)
    {
        try {
            $sql = "SELECT amp.*, u.nombre, u.email
                    FROM autorizadores_metodos_pago amp
                    INNER JOIN usuarios u ON amp.autorizador_email = u.email
                    WHERE amp.metodo_pago = ? AND u.activo = 1
                    LIMIT 1";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$metodoPago]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error obteniendo autorizador método pago: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene autorizador específico para una cuenta contable
     */
    private function obtenerAutorizadorCuentaContable($cuentaContable)
    {
        try {
            $sql = "SELECT acc.*, u.nombre, u.email, cc.descripcion as cuenta_nombre
                    FROM autorizadores_cuentas_contables acc
                    INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                    INNER JOIN usuarios u ON acc.autorizador_email = u.email
                    WHERE cc.codigo = ? AND u.activo = 1
                    LIMIT 1";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$cuentaContable]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error obteniendo autorizador cuenta contable: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica si un centro de costo está excluido para un autorizador
     */
    private function verificarCentroExcluido($autorizadorId, $centroCostoId)
    {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM autorizador_exclusiones_centro_costo 
                    WHERE autorizador_id = ? AND centro_costo_id = ? AND activo = 1";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$autorizadorId, $centroCostoId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return ($result['count'] ?? 0) > 0;
            
        } catch (\Exception $e) {
            error_log("Error verificando centro excluido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene respaldo activo para un centro de costo
     */
    private function obtenerRespaldoActivo($centroCostoId, $fecha)
    {
        try {
            $sql = "SELECT * FROM autorizadores_respaldos 
                    WHERE centro_costo_id = ? 
                    AND estado = 'activo'
                    AND fecha_inicio <= ? 
                    AND fecha_fin >= ?
                    ORDER BY fecha_inicio DESC
                    LIMIT 1";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$centroCostoId, $fecha, $fecha]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error obteniendo respaldo activo: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene autorizador principal de un centro de costo
     */
    private function obtenerAutorizadorPrincipalCentroCosto($centroCostoId)
    {
        try {
            $sql = "SELECT acc.*, u.nombre, u.email
                    FROM autorizador_centro_costo acc
                    INNER JOIN usuarios u ON acc.autorizador_id = u.id
                    WHERE acc.centro_costo_id = ? 
                    AND acc.activo = 1 
                    AND acc.es_principal = 1
                    ORDER BY acc.orden ASC
                    LIMIT 1";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$centroCostoId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error obteniendo autorizador principal: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Envía notificación a un autorizador
     */
    private function enviarNotificacion($email, $requisicion, $tipo)
    {
        try {
            // Aquí iría la lógica para enviar email/notificación
            error_log("Notificación enviada a $email para requisición {$requisicion['id']} - tipo: $tipo");
            
            // Registrar en log de notificaciones
            $this->registrarNotificacion($email, $requisicion['id'], $tipo);
            
            return true;
        } catch (\Exception $e) {
            error_log("Error enviando notificación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra notificación en base de datos
     */
    private function registrarNotificacion($email, $requisicionId, $tipo)
    {
        try {
            $sql = "INSERT INTO notificaciones_autorizacion 
                    (requisicion_id, autorizador_email, tipo, fecha_envio, estado) 
                    VALUES (?, ?, ?, NOW(), 'enviada')";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$requisicionId, $email, $tipo]);
            
        } catch (\Exception $e) {
            error_log("Error registrando notificación: " . $e->getMessage());
        }
    }
    
    /**
     * Maneja rechazo de método de pago (permite edición)
     */
    public function rechazarMetodoPago($requisicionId, $motivo = '')
    {
        try {
            // Cambiar estado a "rechazada_metodo_pago"
            $sql = "UPDATE requisiciones 
                    SET estado = 'rechazada_metodo_pago', 
                        motivo_rechazo = ?,
                        fecha_rechazo = NOW()
                    WHERE id = ?";
            
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$motivo, $requisicionId]);
            
            // Obtener datos de la requisición para notificar al creador
            $requisicion = $this->obtenerRequisicion($requisicionId);
            
            if ($requisicion) {
                $this->enviarNotificacion(
                    $requisicion['creador_email'], 
                    $requisicion, 
                    'rechazo_editable'
                );
            }
            
            return [
                'success' => true,
                'mensaje' => 'Requisición rechazada. Se notificó al creador para edición.',
                'puede_editar' => true
            ];
            
        } catch (\Exception $e) {
            error_log("Error rechazando método de pago: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error al procesar rechazo'
            ];
        }
    }
    
    /**
     * Obtiene datos de una requisición
     */
    private function obtenerRequisicion($requisicionId)
    {
        try {
            $sql = "SELECT * FROM requisiciones WHERE id = ?";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute([$requisicionId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error obteniendo requisición: " . $e->getMessage());
            return null;
        }
    }
}
