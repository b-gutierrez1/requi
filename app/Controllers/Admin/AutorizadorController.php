<?php
/**
 * AutorizadorController
 *
 * CRUD de autorizadores de unidad de negocio.
 * Movido desde AdminController como parte del refactoring.
 *
 * @package RequisicionesMVC\Controllers\Admin
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Helpers\View;
use App\Helpers\Redirect;
use App\Models\UnidadNegocio;
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

        $conn = PersonaAutorizada::getConnection();

        // 1) Contar autorizadores únicos
        $total      = (int)$conn->query(
            "SELECT COUNT(*) FROM autorizadores WHERE activo = 1"
        )->fetchColumn();
        $totalPages = (int)ceil($total / $perPage);
        $offset     = ($page - 1) * $perPage;

        // 2) Una fila por autorizador (paginado)
        $stmtAut = $conn->prepare(
            "SELECT id, nombre, email, cargo, activo
             FROM autorizadores
             WHERE activo = 1
             ORDER BY nombre ASC
             LIMIT :lim OFFSET :off"
        );
        $stmtAut->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $stmtAut->bindValue(':off', $offset,  \PDO::PARAM_INT);
        $stmtAut->execute();
        $autorizadoresRows = $stmtAut->fetchAll(\PDO::FETCH_ASSOC);

        // 3) Cargar los centros de esos autorizadores en una sola consulta
        $centrosPorAutorizador = [];
        if (!empty($autorizadoresRows)) {
            $ids = array_column($autorizadoresRows, 'id');
            $ph  = implode(',', array_fill(0, count($ids), '?'));
            $stmtCentros = $conn->prepare(
                "SELECT acc.autorizador_id, cc.id, cc.nombre, NULL AS codigo, acc.orden
                 FROM autorizador_unidad_negocio acc
                 JOIN unidad_de_negocio cc ON cc.id = acc.unidad_negocio_id
                 WHERE acc.autorizador_id IN ($ph) AND acc.activo = 1
                 ORDER BY acc.orden ASC, cc.nombre ASC"
            );
            $stmtCentros->execute($ids);
            foreach ($stmtCentros->fetchAll(\PDO::FETCH_ASSOC) as $c) {
                $centrosPorAutorizador[(int)$c['autorizador_id']][] = (object)$c;
            }
        }

        // Construir objetos PersonaAutorizada (uno por autorizador)
        $autorizadores = [];
        foreach ($autorizadoresRows as $row) {
            $obj = new PersonaAutorizada();
            foreach ($row as $k => $v) {
                $obj->setAttribute($k, $v);
            }
            $autorizadores[] = $obj;
        }

        $sql = "SELECT
                    acc.autorizador_id,
                    a.email,
                    acc.unidad_negocio_id,
                    COUNT(*) as total
                FROM autorizador_unidad_negocio acc
                INNER JOIN autorizadores a ON a.id = acc.autorizador_id
                WHERE acc.activo = 1
                GROUP BY acc.autorizador_id, acc.unidad_negocio_id
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

        $centros = UnidadNegocio::all();

        View::render('admin/autorizadores/index_agrupado', [
            'autorizadores'          => $autorizadores,
            'centrosPorAutorizador'  => $centrosPorAutorizador,
            'centros'                => $centros,
            'duplicadosPorEmail'     => $duplicadosPorEmail,
            'title'                  => 'Gestión de Autorizadores',
            'page'                   => $page,
            'perPage'                => $perPage,
            'total'                  => $total,
            'totalPages'             => $totalPages,
        ]);
    }

    /**
     * Muestra los detalles de un autorizador
     */
    public function showAutorizador($id)
    {
        $autorizadorObj = $this->findAutorizadorById((int)$id);

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
        } elseif (isset($autorizador['unidad_negocio_id'])) {
            $centroObj = UnidadNegocio::find($autorizador['unidad_negocio_id']);
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
        $autorizador = $this->findAutorizadorById((int)$id);

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
        $centros = UnidadNegocio::all();

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
        $cargo  = $this->sanitize($_POST['cargo'] ?? '') ?: null;
        $centroIds = array_filter(
            array_map('intval', $_POST['unidad_negocio_ids'] ?? []),
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
                ->withError('Debe seleccionar al menos un unidad de negocio')
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
                $pdo->prepare("UPDATE autorizadores SET nombre = ?, cargo = ?, activo = 1 WHERE id = ?")
                    ->execute([$nombre, $cargo, $autorizadorId]);
            } else {
                $pdo->prepare("INSERT INTO autorizadores (nombre, email, cargo, activo) VALUES (?, ?, ?, 1)")
                    ->execute([$nombre, $email, $cargo]);
                $autorizadorId = (int)$pdo->lastInsertId();
            }

            // 2. Insertar relaciones unidad de negocio (evitar duplicados)
            $stmtCheck  = $pdo->prepare("SELECT id FROM autorizador_unidad_negocio WHERE autorizador_id = ? AND unidad_negocio_id = ? AND activo = 1");
            $stmtInsert = $pdo->prepare("INSERT INTO autorizador_unidad_negocio (autorizador_id, unidad_negocio_id, activo) VALUES (?, ?, 1)");
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
        $cargo  = $this->sanitize($_POST['cargo'] ?? '') ?: null;

        if (empty($nombre) || empty($email)) {
            Redirect::back()
                ->withError('Nombre y email son obligatorios')
                ->send();
        }

        $autorizador = $this->findAutorizadorById((int)$id);
        if (!$autorizador) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        $emailAnterior = $autorizador->email;

        $pdo  = PersonaAutorizada::getConnection();
        $stmt = $pdo->prepare("UPDATE autorizadores SET nombre = ?, email = ?, cargo = ? WHERE email = ?");
        $resultado = $stmt->execute([$nombre, $email, $cargo, $emailAnterior]);

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
            Redirect::to('/admin/autorizadores')
                ->withError('Token inválido')
                ->send();
        }

        $autorizador = $this->findAutorizadorById((int)$id);
        if (!$autorizador) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        $email = $autorizador->email ?? '';
        if ($email !== '') {
            $pendientes = (int)PersonaAutorizada::contarPendientes($email);
            if ($pendientes > 0) {
                Redirect::to('/admin/autorizadores')
                    ->withError("No se puede eliminar: el autorizador tiene {$pendientes} autorización(es) pendiente(s). Reasigne o resuelva las autorizaciones antes de eliminar.")
                    ->send();
            }
        }

        // Desactivar en autorizadores (tabla base) — no se puede DELETE en la vista
        $pdo  = PersonaAutorizada::getConnection();
        $stmt = $pdo->prepare("UPDATE autorizadores SET activo = 0 WHERE id = ?");
        $resultado = $stmt->execute([(int)$id]) && $stmt->rowCount() > 0;

        if ($resultado) {
            Redirect::to('/admin/autorizadores')
                ->withSuccess('Autorizador eliminado correctamente')
                ->send();
        } else {
            Redirect::to('/admin/autorizadores')
                ->withError('Error al eliminar el autorizador')
                ->send();
        }
    }

    /**
     * Muestra el formulario de gestión de unidades de negocio de un autorizador
     */
    public function editCentrosAutorizador($id)
    {
        $autorizador = $this->findAutorizadorById((int)$id);

        if (!$autorizador) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        $todosLosCentros  = UnidadNegocio::all();
        $centrosAsignados = PersonaAutorizada::centrosCostoPorEmail($autorizador->email);
        $idsAsignados     = array_column($centrosAsignados, 'centro_id');

        // Cargar el orden actual de cada centro asignado desde la tabla base
        $ordenesPorCentro = [];
        if (!empty($autorizador->email)) {
            $pdo  = PersonaAutorizada::getConnection();
            $stmt = $pdo->prepare("
                SELECT acc.unidad_negocio_id, acc.orden
                FROM autorizador_unidad_negocio acc
                JOIN autorizadores a ON a.id = acc.autorizador_id
                WHERE a.email = ? AND acc.activo = 1
            ");
            $stmt->execute([$autorizador->email]);
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $ordenesPorCentro[(int)$row['unidad_negocio_id']] = (int)$row['orden'];
            }
        }

        View::render('admin/autorizadores/centros', [
            'autorizador'      => $autorizador,
            'todosLosCentros'  => $todosLosCentros,
            'idsAsignados'     => $idsAsignados,
            'ordenesPorCentro' => $ordenesPorCentro,
            'title'            => 'Asignar Unidades de Negocio'
        ]);
    }

    /**
     * Guarda las asignaciones de unidades de negocio de un autorizador
     */
    public function updateCentrosAutorizador($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token invalido')
                ->send();
        }

        $autorizador = $this->findAutorizadorById((int)$id);
        if (!$autorizador) {
            Redirect::to('/admin/autorizadores')
                ->withError('Autorizador no encontrado')
                ->send();
        }

        // Recibir mapa [unidad_negocio_id => orden]: orden=0 significa "quitar asignación"
        $ordenesPosted = $_POST['unidad_negocio_orden'] ?? [];
        $centrosConOrden = [];
        foreach ($ordenesPosted as $centroId => $orden) {
            $centroId = (int)$centroId;
            $orden    = (int)$orden;
            if ($centroId > 0 && $orden > 0) {
                $centrosConOrden[$centroId] = max(1, min(2, $orden)); // clamp 1-2
            }
        }
        $centrosSeleccionados = array_keys($centrosConOrden);

        $centrosActuales = PersonaAutorizada::centrosCostoPorEmail($autorizador->email);
        $idsActuales     = array_map('intval', array_column($centrosActuales, 'centro_id'));

        $pdo = PersonaAutorizada::getConnection();

        // Obtener autorizador_id de la tabla base
        $stmtId = $pdo->prepare("SELECT id FROM autorizadores WHERE email = ? LIMIT 1");
        $stmtId->execute([$autorizador->email]);
        $autorizadorId = (int)$stmtId->fetchColumn();

        if (!$autorizadorId) {
            Redirect::back()->withError('No se encontró el autorizador en la tabla base')->send();
        }

        // Insertar nuevos con su orden
        $nuevos = array_diff($centrosSeleccionados, $idsActuales);
        if (!empty($nuevos)) {
            $stmtInsert = $pdo->prepare(
                "INSERT IGNORE INTO autorizador_unidad_negocio (autorizador_id, unidad_negocio_id, orden, activo) VALUES (?, ?, ?, 1)"
            );
            foreach ($nuevos as $centroId) {
                $stmtInsert->execute([$autorizadorId, $centroId, $centrosConOrden[$centroId] ?? 1]);
            }
        }

        // Actualizar orden de los existentes si cambió
        $existentes = array_intersect($centrosSeleccionados, $idsActuales);
        if (!empty($existentes)) {
            $stmtUpdate = $pdo->prepare(
                "UPDATE autorizador_unidad_negocio SET orden = ? WHERE autorizador_id = ? AND unidad_negocio_id = ?"
            );
            foreach ($existentes as $centroId) {
                $stmtUpdate->execute([$centrosConOrden[$centroId] ?? 1, $autorizadorId, $centroId]);
            }
        }

        // Eliminar los que se quitaron
        $removidos = array_diff($idsActuales, $centrosSeleccionados);
        if (!empty($removidos)) {
            $placeholders = implode(',', array_fill(0, count($removidos), '?'));
            $stmtDelete   = $pdo->prepare(
                "DELETE FROM autorizador_unidad_negocio WHERE autorizador_id = ? AND unidad_negocio_id IN ({$placeholders})"
            );
            $stmtDelete->execute(array_merge([$autorizadorId], array_values($removidos)));
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
                'puede_autorizar_unidad_negocio'   => false,
                'puede_autorizar_flujo'          => false,
                'puede_autorizar_cuenta_contable' => false,
                'puede_autorizar_metodo_pago'    => false,
                'puede_autorizar_respaldo'       => false
            ];

            foreach ($autorizadores as $autorizador) {
                if (!empty($autorizador->unidad_negocio_id) && !in_array($autorizador->unidad_negocio_id, $centrosConsolidados)) {
                    $centrosConsolidados[] = $autorizador->unidad_negocio_id;
                }
                $permisosConsolidados['puede_autorizar_unidad_negocio']   = $permisosConsolidados['puede_autorizar_unidad_negocio']   || ($autorizador->puede_autorizar_unidad_negocio   ?? false);
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
                        'unidad_negocio_id' => $centrosConsolidados[0]
                    ], $permisosConsolidados));

                    for ($i = 1; $i < count($centrosConsolidados); $i++) {
                        PersonaAutorizada::create(array_merge($baseData, [
                            'unidad_negocio_id' => $centrosConsolidados[$i]
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
     * API: retorna unidades de negocio asignados a un autorizador por email
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
            $this->jsonResponse(['success' => false, 'error' => 'Error al obtener unidades de negocio'], 500);
        }
    }

    /**
     * API: busca el cargo de un usuario por email para pre-llenar el formulario
     *
     * GET /admin/api/autorizadores/lookup-cargo?email=...
     */
    public function apiLookupCargo()
    {
        $email = trim($_GET['email'] ?? '');

        if (empty($email)) {
            $this->jsonResponse(['cargo' => null]);
            return;
        }

        try {
            $pdo  = PersonaAutorizada::getConnection();
            $stmt = $pdo->prepare(
                "SELECT COALESCE(NULLIF(job_title,''), NULLIF(azure_job_title,'')) AS cargo
                 FROM usuarios WHERE LOWER(email) = LOWER(?) LIMIT 1"
            );
            $stmt->execute([$email]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['cargo' => $row['cargo'] ?? null]);

        } catch (\Exception $e) {
            error_log("Error apiLookupCargo: " . $e->getMessage());
            $this->jsonResponse(['cargo' => null]);
        }
    }

    // ========================================================================
    // HELPERS PRIVADOS
    // ========================================================================

    /**
     * Busca un autorizador por autorizadores.id (el ID de la tabla base, no de la vista).
     *
     * PersonaAutorizada::find() usa persona_autorizada view donde id = autorizador_unidad_negocio.id,
     * pero las URLs del controlador pasan autorizadores.id. Este helper resuelve el mismatch.
     */
    private function findAutorizadorById(int $id): ?PersonaAutorizada
    {
        $pdo  = PersonaAutorizada::getConnection();
        $stmt = $pdo->prepare("SELECT id, nombre, email, cargo, activo FROM autorizadores WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $obj = new PersonaAutorizada();
        foreach ($row as $k => $v) {
            $obj->setAttribute($k, $v);
        }

        return $obj;
    }
}
