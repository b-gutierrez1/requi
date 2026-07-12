<?php
/**
 * AutorizadorController
 *
 * CRUD de autorizadores de centro de costo.
 * Movido desde AdminController como parte del refactoring.
 *
 * @package RequisicionesMVC\Controllers\Admin
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Helpers\View;
use App\Helpers\Redirect;
use App\Models\CentroCosto;
use App\Models\PersonaAutorizada;
use App\Models\AutorizadorMetodoPago;
use App\Models\AutorizadorCuentaContable;

class AutorizadorController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!\App\Helpers\Session::isAdmin()) {
            Redirect::to('/dashboard')
                ->withError('No tienes permisos de administrador')
                ->send();
        }
    }

    // ========================================================================
    // AUTORIZADORES
    // ========================================================================

    /**
     * Lista autorizadores
     */
    public function autorizadores()
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $total   = PersonaAutorizada::count();
        $totalPages = (int)ceil($total / $perPage);

        $autorizadores = PersonaAutorizada::paginate($page, $perPage);

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

        $duplicadosPorEmail = [];
        foreach ($duplicadosReales as $dup) {
            $email = strtolower(trim($dup['email'] ?? ''));
            if (!isset($duplicadosPorEmail[$email])) {
                $duplicadosPorEmail[$email] = 0;
            }
            $duplicadosPorEmail[$email] += ($dup['total'] - 1);
        }

        $centros = CentroCosto::all();

        View::render('admin/autorizadores/index_agrupado', [
            'autorizadores'      => $autorizadores,
            'centros'            => $centros,
            'duplicadosPorEmail' => $duplicadosPorEmail,
            'title'              => 'Gestión de Autorizadores',
            'page'               => $page,
            'perPage'            => $perPage,
            'total'              => $total,
            'totalPages'         => $totalPages,
        ]);
    }

    /**
     * Muestra los detalles de un autorizador
     */
    public function showAutorizador($id)
    {
        $autorizadorObj = PersonaAutorizada::find($id);

        if (!$autorizadorObj) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        $autorizador = $autorizadorObj->toArray();

        $centrosCosto = [];
        if (!empty($autorizador['email'])) {
            $centrosCosto = PersonaAutorizada::centrosCostoPorEmail($autorizador['email']);
        }

        $centroPrincipal = null;
        if (!empty($centrosCosto)) {
            $centroPrincipal = $centrosCosto[0];
        } elseif (isset($autorizador['centro_costo_id'])) {
            $centroObj = CentroCosto::find($autorizador['centro_costo_id']);
            $centroPrincipal = $centroObj ? $centroObj->toArray() : null;
        }

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
            'respaldo'        => $respaldos,
            'metodo_pago'     => $email ? AutorizadorMetodoPago::where(['autorizador_email' => $email]) : [],
            'cuenta_contable' => $email ? AutorizadorCuentaContable::where(['autorizador_email' => $email]) : []
        ];

        View::render('admin/autorizadores/show', [
            'autorizador'             => $autorizador,
            'centrosCosto'            => $centrosCosto,
            'centroPrincipal'         => $centroPrincipal,
            'autorizacionesEspeciales' => $autorizacionesEspeciales,
            'title'                   => 'Detalles del Autorizador'
        ]);
    }

    /**
     * Muestra el formulario de edición de autorizador
     */
    public function editAutorizador($id)
    {
        $autorizador = PersonaAutorizada::find($id);

        if (!$autorizador) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        $centrosAsignados = [];
        if (!empty($autorizador->email)) {
            $centrosAsignados = PersonaAutorizada::centrosCostoPorEmail($autorizador->email);
        }

        View::render('admin/autorizadores/edit', [
            'autorizador'    => $autorizador,
            'centrosAsignados' => $centrosAsignados,
            'title'          => 'Editar Autorizador'
        ]);
    }

    /**
     * Muestra formulario para crear autorizador
     */
    public function createAutorizador()
    {
        $centros = CentroCosto::all();

        View::render('admin/autorizadores/create', [
            'centros' => $centros,
            'title'   => 'Nuevo Autorizador'
        ]);
    }

    /**
     * Crea un autorizador
     */
    public function storeAutorizador()
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token invalido')
                ->send();
        }

        $nombre = $this->sanitize($_POST['nombre'] ?? '');
        $email  = $this->sanitize($_POST['email'] ?? '');
        $centroIds = array_filter(
            array_map('intval', $_POST['centro_costo_ids'] ?? []),
            fn($id) => $id > 0
        );

        if (empty($nombre) || empty($email)) {
            Redirect::back()
                ->withError('Nombre y email son obligatorios')
                ->withInput($_POST)
                ->send();
        }

        if (empty($centroIds)) {
            Redirect::back()
                ->withError('Debe seleccionar al menos un centro de costo')
                ->withInput($_POST)
                ->send();
        }

        $pdo = PersonaAutorizada::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Obtener o crear el autorizador en la tabla base
            $stmtFind = $pdo->prepare("SELECT id FROM autorizadores WHERE email = ? LIMIT 1");
            $stmtFind->execute([$email]);
            $autorizadorRow = $stmtFind->fetch(\PDO::FETCH_ASSOC);

            if ($autorizadorRow) {
                $autorizadorId = $autorizadorRow['id'];
                // Actualizar nombre por si cambió
                $pdo->prepare("UPDATE autorizadores SET nombre = ?, activo = 1 WHERE id = ?")
                    ->execute([$nombre, $autorizadorId]);
            } else {
                $pdo->prepare("INSERT INTO autorizadores (nombre, email, activo) VALUES (?, ?, 1)")
                    ->execute([$nombre, $email]);
                $autorizadorId = (int)$pdo->lastInsertId();
            }

            // 2. Insertar relaciones centro de costo (evitar duplicados)
            $stmtCheck  = $pdo->prepare("SELECT id FROM autorizador_centro_costo WHERE autorizador_id = ? AND centro_costo_id = ?");
            $stmtInsert = $pdo->prepare("INSERT INTO autorizador_centro_costo (autorizador_id, centro_costo_id, activo) VALUES (?, ?, 1)");
            $creados = 0;

            foreach ($centroIds as $centroId) {
                $stmtCheck->execute([$autorizadorId, (int)$centroId]);
                if (!$stmtCheck->fetch()) {
                    $stmtInsert->execute([$autorizadorId, (int)$centroId]);
                    $creados++;
                }
            }

            $pdo->commit();

            Redirect::to('/admin/autorizadores')
                ->withSuccess("Autorizador creado exitosamente con {$creados} centro(s) de costo")
                ->send();
        } catch (\Exception $e) {
            $pdo->rollBack();
            Redirect::back()
                ->withError('Error al crear autorizador: ' . $e->getMessage())
                ->withInput($_POST)
                ->send();
        }
    }

    /**
     * Actualiza un autorizador
     */
    public function updateAutorizador($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token invalido')
                ->send();
        }

        $nombre = $this->sanitize($_POST['nombre'] ?? '');
        $email  = $this->sanitize($_POST['email'] ?? '');

        if (empty($nombre) || empty($email)) {
            Redirect::back()
                ->withError('Nombre y email son obligatorios')
                ->send();
        }

        $autorizador = PersonaAutorizada::find($id);
        if (!$autorizador) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        $emailAnterior = $autorizador->email;

        $pdo  = PersonaAutorizada::getConnection();
        $stmt = $pdo->prepare("UPDATE autorizadores SET nombre = ?, email = ? WHERE email = ?");
        $resultado = $stmt->execute([$nombre, $email, $emailAnterior]);

        if ($resultado) {
            Redirect::to('/admin/autorizadores/' . $id . '/edit')
                ->withSuccess('Autorizador actualizado exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError('Error al actualizar')
                ->send();
        }
    }

    /**
     * Elimina un autorizador
     */
    public function deleteAutorizador($id)
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }

        $autorizador = PersonaAutorizada::find($id);
        if (!$autorizador) {
            $this->jsonResponse(['success' => false, 'error' => 'Autorizador no encontrado'], 404);
            return;
        }

        $email = $autorizador->email ?? '';
        if ($email !== '') {
            $pendientes = (int)PersonaAutorizada::contarPendientes($email);
            if ($pendientes > 0) {
                $this->jsonResponse([
                    'success' => false,
                    'error'   => "No se puede eliminar: el autorizador tiene {$pendientes} autorización(es) pendiente(s) activa(s). Reasigne o resuelva las autorizaciones antes de eliminar.",
                ], 422);
                return;
            }
        }

        $resultado = PersonaAutorizada::destroy($id);

        $this->jsonResponse([
            'success' => $resultado,
            'message' => $resultado ? 'Autorizador eliminado' : 'Error al eliminar'
        ]);
    }

    /**
     * Muestra el formulario de gestión de centros de costo de un autorizador
     */
    public function editCentrosAutorizador($id)
    {
        $autorizador = PersonaAutorizada::find($id);

        if (!$autorizador) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        $todosLosCentros  = CentroCosto::all();
        $centrosAsignados = PersonaAutorizada::centrosCostoPorEmail($autorizador->email);
        $idsAsignados     = array_column($centrosAsignados, 'centro_id');

        View::render('admin/autorizadores/centros', [
            'autorizador'     => $autorizador,
            'todosLosCentros' => $todosLosCentros,
            'idsAsignados'    => $idsAsignados,
            'title'           => 'Asignar Centros de Costo'
        ]);
    }

    /**
     * Guarda las asignaciones de centros de costo de un autorizador
     */
    public function updateCentrosAutorizador($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token invalido')
                ->send();
        }

        $autorizador = PersonaAutorizada::find($id);
        if (!$autorizador) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        $centrosSeleccionados = array_filter(
            array_map('intval', $_POST['centro_costo_ids'] ?? []),
            fn($id) => $id > 0
        );

        $centrosActuales = PersonaAutorizada::centrosCostoPorEmail($autorizador->email);
        $idsActuales     = array_map('intval', array_column($centrosActuales, 'centro_id'));

        $pdo = PersonaAutorizada::getConnection();

        $nuevos = array_diff($centrosSeleccionados, $idsActuales);
        if (!empty($nuevos)) {
            $stmtInsert = $pdo->prepare(
                "INSERT INTO persona_autorizada (centro_costo_id, nombre, email) VALUES (?, ?, ?)"
            );
            foreach ($nuevos as $centroId) {
                $stmtInsert->execute([$centroId, $autorizador->nombre, $autorizador->email]);
            }
        }

        $removidos = array_diff($idsActuales, $centrosSeleccionados);
        if (!empty($removidos)) {
            $placeholders = implode(',', array_fill(0, count($removidos), '?'));
            $stmtDelete   = $pdo->prepare(
                "DELETE FROM persona_autorizada WHERE email = ? AND centro_costo_id IN ({$placeholders})"
            );
            $params = array_merge([$autorizador->email], array_values($removidos));
            $stmtDelete->execute($params);
        }

        Redirect::to('/admin/autorizadores/' . $id . '/centros')
            ->withSuccess('Centros de costo actualizados exitosamente')
            ->send();
    }

    /**
     * Consolida registros duplicados de autorizadores
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
            $autorizadores = PersonaAutorizada::where(['email' => $email]);

            if (count($autorizadores) < 2) {
                $this->jsonResponse(['success' => false, 'error' => 'No hay registros duplicados para consolidar']);
                return;
            }

            $autorizadorBase = $autorizadores[0];
            $centrosConsolidados = [];
            $permisosConsolidados = [
                'puede_autorizar_centro_costo'   => false,
                'puede_autorizar_flujo'          => false,
                'puede_autorizar_cuenta_contable' => false,
                'puede_autorizar_metodo_pago'    => false,
                'puede_autorizar_respaldo'       => false
            ];

            foreach ($autorizadores as $autorizador) {
                if (!empty($autorizador->centro_costo_id) && !in_array($autorizador->centro_costo_id, $centrosConsolidados)) {
                    $centrosConsolidados[] = $autorizador->centro_costo_id;
                }
                $permisosConsolidados['puede_autorizar_centro_costo']   = $permisosConsolidados['puede_autorizar_centro_costo']   || ($autorizador->puede_autorizar_centro_costo   ?? false);
                $permisosConsolidados['puede_autorizar_flujo']          = $permisosConsolidados['puede_autorizar_flujo']          || ($autorizador->puede_autorizar_flujo          ?? false);
                $permisosConsolidados['puede_autorizar_cuenta_contable'] = $permisosConsolidados['puede_autorizar_cuenta_contable'] || ($autorizador->puede_autorizar_cuenta_contable ?? false);
                $permisosConsolidados['puede_autorizar_metodo_pago']    = $permisosConsolidados['puede_autorizar_metodo_pago']    || ($autorizador->puede_autorizar_metodo_pago    ?? false);
                $permisosConsolidados['puede_autorizar_respaldo']       = $permisosConsolidados['puede_autorizar_respaldo']       || ($autorizador->puede_autorizar_respaldo       ?? false);
            }

            $pdo = PersonaAutorizada::getConnection();
            $pdo->beginTransaction();

            try {
                for ($i = 1; $i < count($autorizadores); $i++) {
                    PersonaAutorizada::destroy($autorizadores[$i]->id);
                }

                $dataConsolidada = array_merge($permisosConsolidados, [
                    'activo'             => 1,
                    'fecha_actualizacion' => date('Y-m-d H:i:s')
                ]);

                PersonaAutorizada::updateById($autorizadorBase->id, $dataConsolidada);

                if (count($centrosConsolidados) > 1) {
                    $baseData = [
                        'nombre' => $autorizadorBase->nombre,
                        'email'  => $autorizadorBase->email,
                        'cargo'  => $autorizadorBase->cargo,
                        'activo' => 1
                    ];

                    PersonaAutorizada::updateById($autorizadorBase->id, array_merge($baseData, [
                        'centro_costo_id' => $centrosConsolidados[0]
                    ], $permisosConsolidados));

                    for ($i = 1; $i < count($centrosConsolidados); $i++) {
                        PersonaAutorizada::create(array_merge($baseData, [
                            'centro_costo_id' => $centrosConsolidados[$i]
                        ], $permisosConsolidados));
                    }
                }

                $pdo->commit();

                $this->jsonResponse([
                    'success'              => true,
                    'message'              => 'Registros consolidados exitosamente',
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
                'error'   => 'Error interno al consolidar registros'
            ], 500);
        }
    }

    /**
     * API: retorna centros de costo asignados a un autorizador por email
     *
     * GET /admin/api/autorizadores/centros-costo?email=...
     */
    public function apiCentrosCostoAutorizador()
    {
        $email = trim($_GET['email'] ?? '');

        if (empty($email)) {
            $this->jsonResponse(['success' => false, 'error' => 'Email requerido'], 400);
        }

        try {
            $rows = PersonaAutorizada::centrosCostoPorEmail($email);

            $centros = array_map(fn($r) => [
                'id'           => $r['centro_id'],
                'nombre'       => $r['centro_nombre'],
                'es_principal' => 0,
            ], $rows);

            $this->jsonResponse(['success' => true, 'centros' => $centros]);

        } catch (\Exception $e) {
            error_log("Error apiCentrosCostoAutorizador: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Error al obtener centros de costo'], 500);
        }
    }
}
