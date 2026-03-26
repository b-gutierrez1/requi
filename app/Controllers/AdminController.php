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
use App\Models\UnidadNegocio;
use App\Models\PersonaAutorizada;
use App\Models\AutorizadorRespaldo;
use App\Models\AutorizadorMetodoPago;
use App\Models\AutorizadorCuentaContable;
use App\Models\Requisicion;

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
        try {
            error_log("=== ADMIN DASHBOARD INICIADO ===");
            
            // Usar conexión directa a la base de datos para evitar problemas del ORM
            $pdo = \App\Models\Model::getConnection();
            
            // Estadísticas básicas con consultas directas
            $stats = [
                'total_usuarios' => 0,
                'usuarios_activos' => 0,
                'total_requisiciones' => 0,
                'requisiciones_mes' => 0,
                'monto_total_mes' => 0,
                'total_centros' => 0,
                'autorizadores' => 0,
                'autorizaciones_pendientes' => 0
            ];
            
            try {
                // Total requisiciones
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM requisiciones");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $stats['total_requisiciones'] = (int) $result['total'];
                
                // Requisiciones del mes
                $stmt = $pdo->query("
                    SELECT COUNT(*) as total 
                    FROM requisiciones 
                    WHERE YEAR(fecha_solicitud) = YEAR(CURDATE()) 
                    AND MONTH(fecha_solicitud) = MONTH(CURDATE())
                ");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $stats['requisiciones_mes'] = (int) $result['total'];
                
                // Monto del mes
                $stmt = $pdo->query("
                    SELECT COALESCE(SUM(monto_total), 0) as total 
                    FROM requisiciones 
                    WHERE YEAR(fecha_solicitud) = YEAR(CURDATE()) 
                    AND MONTH(fecha_solicitud) = MONTH(CURDATE())
                ");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $stats['monto_total_mes'] = (float) $result['total'];

                // Total usuarios
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $stats['total_usuarios'] = (int) $result['total'];

                // Usuarios activos
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $stats['usuarios_activos'] = (int) $result['total'];

                // Total centros de costo
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM centro_de_costo");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $stats['total_centros'] = (int) $result['total'];

            } catch (\Exception $e) {
                error_log("Error calculando estadísticas: " . $e->getMessage());
            }

            error_log("Estadísticas calculadas: " . json_encode($stats));

            // Datos simples para no causar errores
            $usuarios_recientes = [];
            $autorizaciones_pendientes = [];
            $actividad_reciente = [];

            error_log("Renderizando vista admin/dashboard...");
            View::render('admin/dashboard', [
                'stats' => $stats,
                'usuarios_recientes' => $usuarios_recientes,
                'autorizaciones_pendientes' => $autorizaciones_pendientes,
                'actividad_reciente' => $actividad_reciente,
                'title' => 'Panel de Administración'
            ]);
            error_log("=== ADMIN DASHBOARD COMPLETADO ===");
            
        } catch (\Exception $e) {
            error_log("ERROR EN ADMIN DASHBOARD: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo "<h1>Error en Dashboard</h1>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            exit;
        }
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
        
        $filtrosValidos = ['todos', 'activos', 'revisores', 'admins'];
        $filtroInput = $_GET['filtro'] ?? 'todos';
        $filtro = in_array($filtroInput, $filtrosValidos) ? $filtroInput : 'todos';
        $page  = max(1, (int) ($_GET['page'] ?? 1));
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
        $estadisticas = Requisicion::getEstadisticasUsuario($id);

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
            'is_autorizador' => isset($_POST['is_autorizador']) ? 1 : 0,
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
     * Cambia el estado activo/inactivo de un usuario
     * 
     * @param int $id ID del usuario
     * @return void
     */
    public function toggleUsuario($id)
    {
        try {
            $usuario = Usuario::find($id);
            
            if (!$usuario) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Usuario no encontrado'
                ], 404);
                return;
            }

            // Obtener datos JSON del body
            $input = json_decode(file_get_contents('php://input'), true);
            $nuevoEstado = $input['activo'] ?? !$usuario->activo;

            // Actualizar estado
            $resultado = Usuario::updateById($id, ['activo' => $nuevoEstado ? 1 : 0]);

            if ($resultado) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Estado del usuario actualizado exitosamente',
                    'activo' => $nuevoEstado
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al actualizar el estado del usuario'
                ], 500);
            }

        } catch (\Exception $e) {
            error_log("Error en toggleUsuario: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
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
    // RESPALDOS (movido a Admin\AutorizadorEspecialController)
    // ========================================================================

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
        $tiposValidos = ['cuentas', 'centros'];
        $tipoInput = $_GET['tipo'] ?? 'cuentas';
        $catalogo = in_array($tipoInput, $tiposValidos) ? $tipoInput : 'cuentas';

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

    /**
     * Toggle estado activo/inactivo de una cuenta contable
     * 
     * @param int $id
     * @return void
     */
    public function toggleCuentaContable($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()->withError('Token CSRF inválido')->send();
            return;
        }

        try {
            $activo = $_POST['activo'] ?? 0;
            $activo = (int) $activo; // Convertir a entero (0 o 1)
            
            $cuenta = CuentaContable::find($id);
            if (!$cuenta) {
                Redirect::back()->withError('Cuenta contable no encontrada')->send();
                return;
            }

            // Actualizar estado
            CuentaContable::updateById($id, ['activo' => $activo]);
            
            $mensaje = $activo ? 'Cuenta contable activada correctamente' : 'Cuenta contable desactivada correctamente';
            Redirect::to('/admin/catalogos?tipo=cuentas')->withSuccess($mensaje)->send();
            
        } catch (\Exception $e) {
            error_log("Error toggle cuenta contable: " . $e->getMessage());
            Redirect::back()->withError('Error al cambiar estado de la cuenta contable')->send();
        }
    }

    /**
     * Crear nueva cuenta contable desde catálogos
     * 
     * @return void
     */
    public function storeCuentaContableCatalogo()
    {
        if (!$this->validateCSRF()) {
            Redirect::back()->withError('Token CSRF inválido')->send();
            return;
        }

        try {
            $codigo = trim($_POST['codigo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            
            if (empty($codigo) || empty($descripcion)) {
                Redirect::back()->withError('Código y descripción son requeridos')->send();
                return;
            }

            // Verificar si ya existe el código
            $existente = CuentaContable::where('codigo', $codigo)->first();
            if ($existente) {
                Redirect::back()->withError('Ya existe una cuenta contable con ese código')->send();
                return;
            }

            // Crear nueva cuenta
            CuentaContable::create([
                'codigo' => $codigo,
                'descripcion' => $descripcion,
                'activo' => 1
            ]);
            
            Redirect::to('/admin/catalogos?tipo=cuentas')->withSuccess('Cuenta contable creada correctamente')->send();
            
        } catch (\Exception $e) {
            error_log("Error crear cuenta contable: " . $e->getMessage());
            Redirect::back()->withError('Error al crear la cuenta contable')->send();
        }
    }

    /**
     * Actualizar cuenta contable desde catálogos
     * 
     * @param int $id
     * @return void
     */
    public function updateCuentaContableCatalogo($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()->withError('Token CSRF inválido')->send();
            return;
        }

        try {
            $codigo = trim($_POST['codigo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            
            if (empty($codigo) || empty($descripcion)) {
                Redirect::back()->withError('Código y descripción son requeridos')->send();
                return;
            }

            $cuenta = CuentaContable::find($id);
            if (!$cuenta) {
                Redirect::back()->withError('Cuenta contable no encontrada')->send();
                return;
            }

            // Verificar si el nuevo código ya existe (solo si cambió)
            if ($codigo !== $cuenta->codigo) {
                $existente = CuentaContable::where('codigo', $codigo)->first();
                if ($existente) {
                    Redirect::back()->withError('Ya existe una cuenta contable con ese código')->send();
                    return;
                }
            }

            // Actualizar cuenta
            CuentaContable::updateById($id, [
                'codigo' => $codigo,
                'descripcion' => $descripcion
            ]);
            
            Redirect::to('/admin/catalogos?tipo=cuentas')->withSuccess('Cuenta contable actualizada correctamente')->send();
            
        } catch (\Exception $e) {
            error_log("Error actualizar cuenta contable: " . $e->getMessage());
            Redirect::back()->withError('Error al actualizar la cuenta contable')->send();
        }
    }

    /**
     * Eliminar cuenta contable desde catálogos
     * 
     * @param int $id
     * @return void
     */
    public function deleteCuentaContableCatalogo($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()->withError('Token CSRF inválido')->send();
            return;
        }

        try {
            $cuenta = CuentaContable::find($id);
            if (!$cuenta) {
                Redirect::back()->withError('Cuenta contable no encontrada')->send();
                return;
            }

            // Verificar si la cuenta está siendo usada
            $pdo = Model::getConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM distribucion_gasto WHERE cuenta_contable_id = ?");
            $stmt->execute([$id]);
            $uso = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($uso['total'] > 0) {
                Redirect::back()->withError('No se puede eliminar: la cuenta contable está siendo utilizada en ' . $uso['total'] . ' registros')->send();
                return;
            }

            // Eliminar cuenta
            CuentaContable::destroy($id);
            
            Redirect::to('/admin/catalogos?tipo=cuentas')->withSuccess('Cuenta contable eliminada correctamente')->send();
            
        } catch (\Exception $e) {
            error_log("Error eliminar cuenta contable: " . $e->getMessage());
            Redirect::back()->withError('Error al eliminar la cuenta contable')->send();
        }
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
                    
                    // Actualizar autorizaciones de centro de costo
                    $sql = "UPDATE autorizaciones SET centro_costo_id = ? WHERE centro_costo_id = ? AND tipo = 'centro_costo'";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idBase, $idEliminar]);
                    
                    // Actualizar persona_autorizada
                    $table = PersonaAutorizada::getTable();
                    $sql = "UPDATE {$table} SET centro_costo_id = ? WHERE centro_costo_id = ?";
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
    // MÉTODOS PRIVADOS
    // ========================================================================

    /**
     * Obtiene actividad reciente del sistema
     * 
     * @return array
     */
    private function getActividadReciente()
    {
        try {
            error_log("getActividadReciente: Iniciando");
            
            // Obtener últimas 10 requisiciones creadas
            $requisiciones = Requisicion::recientes(10);
            error_log("getActividadReciente: Obtenidas " . count($requisiciones) . " requisiciones");

            $actividad = [];
            foreach ($requisiciones as $req) {
                $actividad[] = [
                    'tipo' => 'requisicion_creada',
                    'descripcion' => "Requisición #{$req->id} creada",
                    'usuario' => $req->usuario_id,
                    'fecha' => $req->fecha ?? $req->fecha_solicitud ?? 'N/A'
                ];
            }
            
            error_log("getActividadReciente: Actividad generada con " . count($actividad) . " items");
            return $actividad;
            
        } catch (\Exception $e) {
            error_log("Error en getActividadReciente: " . $e->getMessage());
            return [];
        }
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
            'id' => $ultimo['id'] ?? null,  // Agregar campo id para compatibilidad con la vista
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
            'id_registro' => $ultimo['id'] ?? null,  // Mantener para backward compatibility
            'centros_costo' => $centros,
            'centros_costo_count' => is_array($centros) ? count($centros) : 0
        ];
    }

    // ========================================================================
    // RELACIONES CENTRO COSTO - UNIDAD NEGOCIO
    // ========================================================================
    
    /**
     * Muestra la página de relaciones entre centros de costo y unidades de negocio
     * 
     * @return void
     */
    public function relaciones()
    {
        // Obtener centros de costo con sus relaciones (unidad de negocio y factura)
        $centrosCosto = CentroCosto::activos();
        $unidadesNegocio = UnidadNegocio::activas();

        View::render('admin/relaciones/index', [
            'title' => 'Relaciones Centro de Costo - Unidad de Negocio',
            'centros_costo' => $centrosCosto,
            'unidades_negocio' => $unidadesNegocio
        ]);
    }

    /**
     * API: busca usuarios por nombre o email (autocompletado)
     *
     * GET /admin/api/usuarios/buscar?q=...
     */
    public function apiBuscarUsuarios()
    {
        $q = substr(trim($_GET['q'] ?? ''), 0, 100);

        if (strlen($q) < 2) {
            $this->jsonResponse(['success' => true, 'usuarios' => []]);
        }

        try {
            $pdo  = Usuario::getConnection();
            $like = '%' . $q . '%';
            $stmt = $pdo->prepare("
                SELECT azure_display_name AS nombre,
                       COALESCE(azure_email, email) AS email,
                       azure_job_title AS cargo
                FROM usuarios
                WHERE activo = 1
                  AND (azure_display_name LIKE ? OR azure_email LIKE ? OR email LIKE ?)
                ORDER BY azure_display_name ASC
                LIMIT 20
            ");
            $stmt->execute([$like, $like, $like]);
            $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'usuarios' => $usuarios]);

        } catch (\Exception $e) {
            error_log("Error apiBuscarUsuarios: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Error al buscar usuarios'], 500);
        }
    }
}
