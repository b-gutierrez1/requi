<?php
/**
 * AutorizadorEspecialController
 *
 * Gestión de autorizadores especiales: respaldos, métodos de pago y cuentas contables.
 * También contiene el flujo escalonado de autorización.
 * Movido desde AdminController como parte del refactoring.
 *
 * @package RequisicionesMVC\Controllers\Admin
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Helpers\View;
use App\Helpers\Redirect;
use App\Helpers\Session;
use App\Models\Model;
use App\Models\CentroCosto;
use App\Models\PersonaAutorizada;
use App\Models\AutorizadorRespaldo;
use App\Models\AutorizadorMetodoPago;
use App\Models\AutorizadorCuentaContable;

class AutorizadorEspecialController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!Session::isAdmin()) {
            Redirect::to('/dashboard')
                ->withError('No tienes permisos de administrador')
                ->send();
        }
    }

    // ========================================================================
    // AUTORIZADORES ESPECIALES — LISTADOS
    // ========================================================================

    /**
     * Lista autorizadores de respaldo
     */
    public function autorizadoresRespaldos()
    {
        try {
            $respaldosData = AutorizadorRespaldo::todosAgrupados();

            $respaldos = array_map(function($row) {
                return (object)$row;
            }, $respaldosData);

            $centros      = CentroCosto::all();
            $estadisticas = AutorizadorRespaldo::getEstadisticas();

            View::render('admin/autorizadores/respaldos', [
                'respaldos'    => $respaldos,
                'centros'      => $centros,
                'estadisticas' => $estadisticas,
                'title'        => 'Autorizadores de Respaldo'
            ]);

        } catch (\Exception $e) {
            error_log("Error en autorizadoresRespaldos: " . $e->getMessage());
            Redirect::to('/admin/autorizadores')
                ->withError('Error al cargar autorizadores de respaldo: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Lista autorizadores por método de pago
     */
    public function autorizadoresMetodosPago()
    {
        try {
            $sql = "SHOW TABLES LIKE 'autorizadores_metodos_pago'";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $tableExists = $stmt->fetch() !== false;

            $autorizadores_metodo_pago = [];

            if ($tableExists) {
                $sql = "SELECT
                            id,
                            autorizador_email,
                            metodo_pago,
                            descripcion as observaciones,
                            fecha_actualizacion
                        FROM autorizadores_metodos_pago
                        WHERE autorizador_email IS NOT NULL AND autorizador_email != ''
                        ORDER BY autorizador_email ASC, metodo_pago ASC";

                $stmt = Model::getConnection()->prepare($sql);
                $stmt->execute();
                $autorizadoresData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($autorizadoresData as $row) {
                    $nombreAutorizador = null;
                    if (!empty($row['autorizador_email'])) {
                        $sqlNombre = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                        $stmtNombre = Model::getConnection()->prepare($sqlNombre);
                        $stmtNombre->execute([$row['autorizador_email']]);
                        $resultNombre = $stmtNombre->fetch(\PDO::FETCH_ASSOC);
                        $nombreAutorizador = $resultNombre['nombre'] ?? null;
                    }
                    $row['autorizador_nombre'] = $nombreAutorizador;

                    $autorizador = PersonaAutorizada::porEmail($row['autorizador_email']);
                    if ($autorizador && is_array($autorizador)) {
                        $row['cargo'] = $autorizador['cargo'] ?? null;
                    }

                    try {
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
                        error_log("Error contando centros de costo: " . $e->getMessage());
                        $row['centros_costo_count'] = 0;
                    }

                    $row['autorizador_id'] = $autorizadorId ?? null;
                    $row['nombre'] = $row['autorizador_nombre'] ?? null;
                    $row['email']  = $row['autorizador_email'] ?? null;
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
            Redirect::to('/admin/autorizadores')
                ->withError('Error al cargar autorizadores por método de pago: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Lista autorizadores por cuenta contable
     */
    public function autorizadoresCuentasContables()
    {
        try {
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
                $sql = "SELECT
                            MIN(acc.id) AS registro_id,
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

                foreach ($autorizadoresData as $row) {
                    $nombreAutorizador = null;
                    if (!empty($row['autorizador_email'])) {
                        $sqlNombre = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                        $stmtNombre = Model::getConnection()->prepare($sqlNombre);
                        $stmtNombre->execute([$row['autorizador_email']]);
                        $resultNombre = $stmtNombre->fetch(\PDO::FETCH_ASSOC);
                        $nombreAutorizador = $resultNombre['nombre'] ?? null;
                    }
                    $row['autorizador_nombre'] = $nombreAutorizador;

                    $autorizador = PersonaAutorizada::porEmail($row['autorizador_email']);
                    if ($autorizador && is_array($autorizador)) {
                        $row['cargo'] = $autorizador['cargo'] ?? null;
                    }

                    $cuentasQuery = "SELECT
                                        cc.codigo,
                                        cc.descripcion as nombre
                                    FROM autorizadores_cuentas_contables acc
                                    INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                                    WHERE acc.autorizador_email = ?
                                    ORDER BY cc.codigo ASC";
                    $cuentasStmt = Model::getConnection()->prepare($cuentasQuery);
                    $cuentasStmt->execute([$row['autorizador_email']]);
                    $row['cuentas_contables'] = $cuentasStmt->fetchAll(\PDO::FETCH_ASSOC);

                    try {
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
                        error_log("Error contando centros de costo: " . $e->getMessage());
                        $row['centros_costo_count'] = 0;
                    }

                    $row['id']          = $row['registro_id'] ?? null;
                    $row['email']       = $row['autorizador_email'] ?? null;
                    $row['nombre']      = $row['autorizador_nombre'] ?? null;
                    $row['activo']      = true;
                    $row['fecha_inicio'] = date('Y-01-01');
                    $row['fecha_fin']   = null;

                    $autorizadores_cuenta_contable[] = (object)$row;
                }
            }

            View::render('admin/autorizadores/cuentas_contables', [
                'autorizadores_cuenta_contable' => $autorizadores_cuenta_contable,
                'title' => 'Autorizadores por Cuenta Contable'
            ]);

        } catch (\Exception $e) {
            error_log("Error en autorizadoresCuentasContables: " . $e->getMessage());
            Redirect::to('/admin/autorizadores')
                ->withError('Error al cargar autorizadores por cuenta contable: ' . $e->getMessage())
                ->send();
        }
    }

    // ========================================================================
    // CRUD RESPALDOS
    // ========================================================================

    public function createRespaldo()
    {
        try {
            $sql = "SELECT id, nombre, email FROM autorizadores WHERE activo = 1 ORDER BY nombre ASC";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $autorizadoresData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $autorizadores = [];
            foreach ($autorizadoresData as $auth) {
                $autorizadores[] = [
                    'nombre' => $auth['nombre'] ?? '',
                    'email'  => $auth['email'] ?? '',
                    'cargo'  => null
                ];
            }

            $centros = CentroCosto::all();
            $centrosArray = [];
            foreach ($centros as $centro) {
                $centroArray = is_object($centro) ? $centro->toArray() : $centro;
                $centrosArray[] = (object)[
                    'id'     => $centroArray['id'] ?? null,
                    'nombre' => $centroArray['nombre'] ?? '',
                    'codigo' => $centroArray['codigo'] ?? null
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
            'centros'       => $centros,
            'title'         => 'Crear Autorizador de Respaldo'
        ]);
    }

    public function showRespaldo($id)
    {
        $row = AutorizadorRespaldo::find((int)$id);

        if (!$row) {
            Redirect::to('/admin/autorizadores/respaldos')
                ->withError('Respaldo no encontrado')
                ->send();
        }

        $data = is_object($row) ? $row->toArray() : $row;
        $pdo  = AutorizadorRespaldo::getConnection();

        // Nombres de usuarios a partir de los emails
        $getNombre = function(string $email) use ($pdo): string {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(azure_display_name, nombre, email) AS nombre
                 FROM usuarios WHERE azure_email = ? OR email = ? LIMIT 1"
            );
            $stmt->execute([$email, $email]);
            return (string)($stmt->fetchColumn() ?: $email);
        };

        $data['autorizador_principal_nombre'] = $getNombre($data['autorizador_principal_email'] ?? '');
        $data['autorizador_respaldo_nombre']  = $getNombre($data['autorizador_respaldo_email']  ?? '');

        // Centros asignados al respaldo
        $stmt = $pdo->prepare(
            "SELECT cc.nombre, cc.id
             FROM autorizador_respaldo_centro arc
             INNER JOIN centro_de_costo cc ON cc.id = arc.centro_costo_id
             WHERE arc.respaldo_id = ?
             ORDER BY cc.nombre ASC"
        );
        $stmt->execute([$data['id']]);
        $centros = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // La vista usa centro_nombre (singular) — tomamos el primero
        $data['centro_nombre'] = !empty($centros) ? $centros[0]['nombre'] : null;
        $data['centros']       = $centros; // disponible si la vista los itera

        View::render('admin/autorizadores/respaldos_show', [
            'respaldo' => $data,
            'title'    => 'Detalle del Autorizador de Respaldo'
        ]);
    }

    public function storeRespaldo()
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
        }

        try {
            $centrosCostoIds = $_POST['centros_costo_ids'] ?? [];

            if (!is_array($centrosCostoIds)) {
                $centrosCostoIds = [$centrosCostoIds];
            }
            $centrosCostoIds = array_filter($centrosCostoIds);

            if (empty($centrosCostoIds)) {
                Redirect::back()
                    ->withError('Debe seleccionar al menos un centro de costo')
                    ->withInput($_POST)
                    ->send();
                return;
            }

            $data = [
                'autorizador_principal_email' => $this->sanitize($_POST['autorizador_principal_email'] ?? ''),
                'autorizador_respaldo_email'  => $this->sanitize($_POST['autorizador_respaldo_email'] ?? ''),
                'fecha_inicio'               => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin'                  => $_POST['fecha_fin'] ?? null,
                'motivo'                     => $this->sanitize($_POST['motivo'] ?? 'Sin motivo especificado'),
                'estado'                     => isset($_POST['activo']) && $_POST['activo'] == '1' ? 'activo' : 'inactivo',
                'creado_por'                 => $_SESSION['user_email'] ?? 'sistema'
            ];

            $respaldoId = AutorizadorRespaldo::crearConCentros($data, $centrosCostoIds);

            if ($respaldoId) {
                $totalCentros = count($centrosCostoIds);
                $mensaje = $totalCentros === 1
                    ? 'Respaldo creado exitosamente'
                    : "Respaldo creado exitosamente para {$totalCentros} centros de costo";

                Redirect::to('/admin/autorizadores/respaldos')
                    ->withSuccess($mensaje)
                    ->send();
            } else {
                Redirect::back()
                    ->withError('No se pudo crear el respaldo')
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

    public function editRespaldo($id)
    {
        try {
            $respaldo = AutorizadorRespaldo::find($id);

            if (!$respaldo) {
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withError('Respaldo no encontrado')
                    ->send();
                return;
            }

            if (is_object($respaldo)) {
                $respaldo = $respaldo->toArray();
            }

            $nombrePrincipal = null;
            $nombreRespaldo  = null;

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

            $respaldo['autorizador_principal_nombre'] = $nombrePrincipal;
            $respaldo['autorizador_respaldo_nombre']  = $nombreRespaldo;

            $centrosAsignados = AutorizadorRespaldo::obtenerCentrosIds($id);
            $respaldo['centros_asignados'] = $centrosAsignados;

            $sql = "SELECT id, nombre, email FROM autorizadores WHERE activo = 1 ORDER BY nombre ASC";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $autorizadoresData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $autorizadores = [];
            foreach ($autorizadoresData as $auth) {
                $autorizadores[] = [
                    'nombre' => $auth['nombre'] ?? '',
                    'email'  => $auth['email'] ?? '',
                    'cargo'  => null
                ];
            }

            $centros = CentroCosto::all();
            $centrosArray = [];
            foreach ($centros as $centro) {
                $centroArray = is_object($centro) ? $centro->toArray() : $centro;
                $centrosArray[] = (object)[
                    'id'     => $centroArray['id'] ?? null,
                    'nombre' => $centroArray['nombre'] ?? '',
                    'codigo' => $centroArray['codigo'] ?? null
                ];
            }
            $centros = $centrosArray;

            View::render('admin/autorizadores/respaldos_edit', [
                'respaldo'      => $respaldo,
                'autorizadores' => $autorizadores,
                'centros'       => $centros,
                'title'         => 'Editar Autorizador de Respaldo'
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
            $respaldo = AutorizadorRespaldo::find($id);
            if (!$respaldo) {
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withError('Respaldo no encontrado')
                    ->send();
                return;
            }

            $centrosCostoIds = $_POST['centros_costo_ids'] ?? [];
            if (!is_array($centrosCostoIds)) {
                $centrosCostoIds = [$centrosCostoIds];
            }
            $centrosCostoIds = array_filter($centrosCostoIds);

            if (empty($centrosCostoIds)) {
                Redirect::back()
                    ->withError('Debe seleccionar al menos un centro de costo')
                    ->withInput($_POST)
                    ->send();
                return;
            }

            $data = [
                'autorizador_principal_email' => $this->sanitize($_POST['autorizador_principal_email'] ?? ''),
                'autorizador_respaldo_email'  => $this->sanitize($_POST['autorizador_respaldo_email'] ?? ''),
                'fecha_inicio'               => $_POST['fecha_inicio'] ?? date('Y-m-d'),
                'fecha_fin'                  => $_POST['fecha_fin'] ?? null,
                'motivo'                     => $this->sanitize($_POST['motivo'] ?? 'Sin motivo especificado'),
                'estado'                     => isset($_POST['activo']) && $_POST['activo'] == '1' ? 'activo' : 'inactivo'
            ];

            $actualizado = AutorizadorRespaldo::actualizarConCentros($id, $data, $centrosCostoIds);

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
            $respaldo = AutorizadorRespaldo::find($id);
            if (!$respaldo) {
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withError('Respaldo no encontrado')
                    ->send();
                return;
            }

            $eliminado = AutorizadorRespaldo::eliminarConCentros($id);

            if ($eliminado) {
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withSuccess('Respaldo eliminado exitosamente')
                    ->send();
            } else {
                Redirect::to('/admin/autorizadores/respaldos')
                    ->withError('Error al eliminar el respaldo')
                    ->send();
            }

        } catch (\Exception $e) {
            error_log("Error en deleteRespaldo: " . $e->getMessage());
            Redirect::to('/admin/autorizadores/respaldos')
                ->withError('Error al eliminar respaldo: ' . $e->getMessage())
                ->send();
        }
    }

    // ========================================================================
    // CRUD MÉTODOS DE PAGO
    // ========================================================================

    public function createMetodoPago()
    {
        try {
            $sql = "SELECT id, nombre, email FROM autorizadores WHERE activo = 1 ORDER BY nombre ASC";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $autorizadores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $metodosPago = [
                'contado'                    => 'Contado',
                'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito (Lic. Milton)',
                'cheque'                     => 'Cheque',
                'transferencia'              => 'Transferencia',
                'credito'                    => 'Crédito'
            ];

            $sqlMetodos = "SELECT DISTINCT forma_pago FROM requisiciones
                          WHERE forma_pago IS NOT NULL
                          ORDER BY forma_pago ASC";
            $stmtMetodos = Model::getConnection()->prepare($sqlMetodos);
            $stmtMetodos->execute();
            $metodosEnUso = $stmtMetodos->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($metodosEnUso as $metodo) {
                if (!empty($metodo) && !isset($metodosPago[$metodo])) {
                    $metodosAdicionales = [
                        'efectivo'             => 'Efectivo',
                        'transferencia_bancaria' => 'Transferencia Bancaria',
                        'credito_30'           => 'Crédito 30 días'
                    ];

                    if (isset($metodosAdicionales[$metodo])) {
                        $metodosPago[$metodo] = $metodosAdicionales[$metodo];
                    } else {
                        $descripcion = ucwords(str_replace(['_', '-'], ' ', $metodo));
                        $metodosPago[$metodo] = $descripcion;
                    }
                }
            }

            $sqlExistentes = "SELECT DISTINCT autorizador_email FROM autorizadores_metodos_pago";
            $stmtExistentes = Model::getConnection()->prepare($sqlExistentes);
            $stmtExistentes->execute();
            $autorizadoresExistentes = $stmtExistentes->fetchAll(\PDO::FETCH_COLUMN);

        } catch (\Exception $e) {
            error_log("Error obteniendo datos para crear método de pago: " . $e->getMessage());
            $autorizadores = [
                ['id' => 0, 'nombre' => 'Sin datos disponibles', 'email' => '']
            ];
            $metodosPago = [
                'contado'                    => 'Contado',
                'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito (Lic. Milton)',
                'cheque'                     => 'Cheque',
                'transferencia'              => 'Transferencia',
                'credito'                    => 'Crédito'
            ];
            $autorizadoresExistentes = [];
        }

        View::render('admin/autorizadores/metodos_pago_create', [
            'autorizadores'          => $autorizadores,
            'metodos_pago'           => $metodosPago,
            'autorizadores_existentes' => $autorizadoresExistentes,
            'title'                  => 'Crear Autorizador por Método de Pago'
        ]);
    }

    public function showMetodoPago($id)
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

        $autorizador = $this->obtenerAutorizadorMetodoPagoPorEmail($email);

        if (!$autorizador) {
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withError('Autorizador no encontrado para el email especificado')
                ->send();
            return;
        }

        View::render('admin/autorizadores/metodos_pago_show', [
            'autorizador' => (object)$autorizador,
            'title'       => 'Detalle del Autorizador por Método de Pago'
        ]);
    }

    public function storeMetodoPago()
    {
        try {
            if (!$this->validateCSRF()) {
                Redirect::back()
                    ->withError('Token de seguridad inválido')
                    ->withInput()
                    ->send();
                return;
            }

            $autorizadorEmail = trim($_POST['autorizador_email'] ?? '');
            $metodoPago       = trim($_POST['metodo_pago'] ?? '');
            $observaciones    = trim($_POST['observaciones'] ?? '');
            $activo           = isset($_POST['activo']) ? 1 : 0;

            if (empty($autorizadorEmail) || !filter_var($autorizadorEmail, FILTER_VALIDATE_EMAIL)) {
                Redirect::back()
                    ->withError('Debe seleccionar un autorizador válido')
                    ->withInput()
                    ->send();
                return;
            }

            if (empty($metodoPago)) {
                Redirect::back()
                    ->withError('Debe seleccionar un método de pago')
                    ->withInput()
                    ->send();
                return;
            }

            $sqlVerificar = "SELECT id, nombre FROM autorizadores WHERE email = ? AND activo = 1";
            $stmtVerificar = Model::getConnection()->prepare($sqlVerificar);
            $stmtVerificar->execute([$autorizadorEmail]);
            $autorizador = $stmtVerificar->fetch(\PDO::FETCH_ASSOC);

            if (!$autorizador) {
                Redirect::back()
                    ->withError('El autorizador seleccionado no existe o no está activo')
                    ->withInput()
                    ->send();
                return;
            }

            $sqlExiste = "SELECT id FROM autorizadores_metodos_pago
                         WHERE autorizador_email = ? AND metodo_pago = ?";
            $stmtExiste = Model::getConnection()->prepare($sqlExiste);
            $stmtExiste->execute([$autorizadorEmail, $metodoPago]);

            if ($stmtExiste->fetch()) {
                Redirect::back()
                    ->withError("Ya existe un autorizador para el método de pago '$metodoPago' con este email")
                    ->withInput()
                    ->send();
                return;
            }

            $descripciones = [
                'contado'                    => 'Contado',
                'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito (Lic. Milton)',
                'cheque'                     => 'Cheque',
                'transferencia'              => 'Transferencia',
                'credito'                    => 'Crédito',
                'efectivo'                   => 'Efectivo',
                'transferencia_bancaria'     => 'Transferencia Bancaria',
                'credito_30'                 => 'Crédito 30 días'
            ];

            $descripcion = $descripciones[$metodoPago] ?? ucwords(str_replace(['_', '-'], ' ', $metodoPago));

            $pdo = Model::getConnection();
            $pdo->beginTransaction();

            $sqlInsert = "INSERT INTO autorizadores_metodos_pago
                         (metodo_pago, descripcion, autorizador_email, notificacion, actualizado_por)
                         VALUES (?, ?, ?, ?, ?)";

            $notificacion  = "La requisición con forma de pago {$descripcion} requiere su autorización antes de continuar con el flujo normal.";
            $actualizadoPor = Session::get('user.email') ?? 'sistema';

            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                $metodoPago,
                $descripcion,
                $autorizadorEmail,
                $notificacion,
                $actualizadoPor
            ]);

            $pdo->commit();

            $mensaje = "Autorizador '{$autorizador['nombre']}' configurado exitosamente para el método de pago '{$descripcion}'.";

            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withSuccess($mensaje)
                ->send();

        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error creando autorizador método de pago: " . $e->getMessage());
            Redirect::back()
                ->withError('Error al crear el autorizador por método de pago: ' . $e->getMessage())
                ->withInput()
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

    public function showMetodoPagoByEmail($email)
    {
        $emailDecoded = urldecode($email);

        if (empty($emailDecoded) || !filter_var($emailDecoded, FILTER_VALIDATE_EMAIL)) {
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withError('Email de autorizador inválido')
                ->send();
            return;
        }

        $autorizador = $this->obtenerAutorizadorMetodoPagoPorEmail($emailDecoded);

        if (!$autorizador) {
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withError('Autorizador no encontrado para el email especificado')
                ->send();
            return;
        }

        View::render('admin/autorizadores/metodos_pago_show', [
            'autorizador' => (object)$autorizador,
            'title'       => 'Detalle del Autorizador por Método de Pago'
        ]);
    }

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

            $metodos_pago = [
                'efectivo'                   => 'Efectivo',
                'tarjeta_credito'            => 'Tarjeta de Crédito',
                'tarjeta_debito'             => 'Tarjeta de Débito',
                'transferencia'              => 'Transferencia Bancaria',
                'cheque'                     => 'Cheque',
                'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito Lic. Milton',
                'otro'                       => 'Otro'
            ];

            View::render('admin/autorizadores/metodos_pago_edit', [
                'autorizador' => $autorizador,
                'metodos_pago' => $metodos_pago,
                'title'       => 'Editar Autorizador de Método de Pago'
            ]);

        } catch (\Exception $e) {
            error_log("Error en editMetodoPagoByEmail: " . $e->getMessage());
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withError('Error al cargar autorizador: ' . $e->getMessage())
                ->send();
        }
    }

    public function updateMetodoPagoByEmail($email)
    {
        try {
            $email       = urldecode($email);
            $metodo_pago = $_POST['metodo_pago'] ?? null;
            $activo      = isset($_POST['activo']) ? 1 : 0;

            if (!$metodo_pago) {
                Redirect::back()
                    ->withError('El método de pago es requerido')
                    ->withInput()
                    ->send();
                return;
            }

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

    public function deleteMetodoPago($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
            return;
        }

        $identificador = urldecode($id);

        if (empty($identificador)) {
            Redirect::back()
                ->withError('Identificador de autorizador inválido')
                ->send();
            return;
        }

        $resultado = $this->eliminarAutorizadorMetodoPago($identificador);

        if ($resultado['success']) {
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withSuccess($resultado['message'])
                ->send();
        } else {
            Redirect::back()
                ->withError($resultado['message'])
                ->send();
        }
    }

    public function deleteMetodoPagoByEmail($email)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token de seguridad inválido')
                ->send();
            return;
        }

        $emailDecoded = urldecode($email);

        if (empty($emailDecoded) || !filter_var($emailDecoded, FILTER_VALIDATE_EMAIL)) {
            Redirect::back()
                ->withError('Email de autorizador inválido')
                ->send();
            return;
        }

        $resultado = $this->eliminarAutorizadorMetodoPago($emailDecoded);

        if ($resultado['success']) {
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withSuccess($resultado['message'])
                ->send();
        } else {
            Redirect::back()
                ->withError($resultado['message'])
                ->send();
        }
    }

    public function deleteMetodoPagoLegacy($id)
    {
        $identificador = urldecode($id);

        if (empty($identificador)) {
            $this->handleLegacyResponse(false, 'Identificador de autorizador inválido');
            return;
        }

        $resultado = $this->eliminarAutorizadorMetodoPago($identificador, false);
        $this->handleLegacyResponse($resultado['success'], $resultado['message']);
    }

    // ========================================================================
    // CRUD CUENTAS CONTABLES
    // ========================================================================

    public function createCuentaContable()
    {
        try {
            $sql = "SELECT DISTINCT nombre, email FROM persona_autorizada ORDER BY nombre ASC";
            $stmt = Model::getConnection()->prepare($sql);
            $stmt->execute();
            $autorizadores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $sqlCuentas = "SELECT id, codigo, descripcion FROM cuenta_contable WHERE activo = 1 ORDER BY codigo ASC";
            $stmtCuentas = Model::getConnection()->prepare($sqlCuentas);
            $stmtCuentas->execute();
            $cuentas_contables = $stmtCuentas->fetchAll(\PDO::FETCH_ASSOC);

            $sqlCentros = "SELECT id, nombre FROM centro_de_costo ORDER BY nombre ASC";
            $stmtCentros = Model::getConnection()->prepare($sqlCentros);
            $stmtCentros->execute();
            $centros_costo = $stmtCentros->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\Exception $e) {
            error_log("Error obteniendo datos para cuenta contable: " . $e->getMessage());
            $autorizadores     = [];
            $cuentas_contables = [];
            $centros_costo     = [];
        }

        View::render('admin/autorizadores/cuentas_contables_create', [
            'autorizadores'     => $autorizadores,
            'cuentas_contables' => $cuentas_contables,
            'centros_costo'     => $centros_costo,
            'title'             => 'Crear Autorizador por Cuenta Contable'
        ]);
    }

    public function showCuentaContable($id)
    {
        $pdo = Model::getConnection();

        // $id puede ser numérico (ID de fila) o email
        $email = $id;
        if (strpos((string)$id, '@') === false) {
            $stmt = $pdo->prepare("SELECT autorizador_email FROM autorizadores_cuentas_contables WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                Redirect::to('/admin/autorizadores/cuentas-contables')
                    ->withError('Autorizador no encontrado')
                    ->send();
                return;
            }
            $email = $row['autorizador_email'];
        }

        $cuentas = AutorizadorCuentaContable::porEmail($email);

        if (empty($cuentas)) {
            Redirect::to('/admin/autorizadores/cuentas-contables')
                ->withError('Autorizador no encontrado para el email especificado')
                ->send();
            return;
        }

        $stmtUsr = $pdo->prepare(
            "SELECT COALESCE(azure_display_name, nombre, email) AS nombre, azure_job_title AS cargo, activo
             FROM usuarios WHERE azure_email = ? OR email = ? LIMIT 1"
        );
        $stmtUsr->execute([$email, $email]);
        $usuario = $stmtUsr->fetch(\PDO::FETCH_ASSOC);

        $autorizador = (object)[
            'id'               => $cuentas[0]['id'],
            'email'            => $email,
            'nombre'           => $usuario['nombre'] ?? $email,
            'cargo'            => $usuario['cargo'] ?? null,
            'activo'           => ($usuario['activo'] ?? 1) == 1,
            'cuentas_contables' => array_map(function($c) {
                return ['codigo' => $c['cuenta_codigo'], 'nombre' => $c['cuenta_descripcion']];
            }, $cuentas),
            'observaciones'    => $cuentas[0]['descripcion'] ?? null,
        ];

        View::render('admin/autorizadores/cuentas_contables_show', [
            'autorizador' => $autorizador,
            'title'       => 'Detalle del Autorizador por Cuenta Contable'
        ]);
    }

    public function storeCuentaContable()
    {
        $pdo = null;
        try {
            if (!$this->validateCSRF()) {
                Redirect::back()->withError('Token de seguridad inválido')->withInput()->send();
                return;
            }

            $autorizadorEmail = trim($_POST['autorizador_email'] ?? '');
            $cuentasIds       = $_POST['cuentas_contables'] ?? [];
            $observaciones    = trim($_POST['observaciones'] ?? '');
            $activo           = isset($_POST['activo']) ? 1 : 0;

            if (empty($autorizadorEmail) || !filter_var($autorizadorEmail, FILTER_VALIDATE_EMAIL)) {
                Redirect::back()->withError('Debe seleccionar un autorizador válido')->withInput()->send();
                return;
            }

            if (empty($cuentasIds)) {
                Redirect::back()->withError('Debe seleccionar al menos una cuenta contable')->withInput()->send();
                return;
            }

            $pdo = Model::getConnection();

            $stmtUsr = $pdo->prepare(
                "SELECT COALESCE(azure_display_name, nombre, email) AS nombre
                 FROM usuarios WHERE azure_email = ? OR email = ? LIMIT 1"
            );
            $stmtUsr->execute([$autorizadorEmail, $autorizadorEmail]);
            $nombreAutorizador = $stmtUsr->fetchColumn() ?: $autorizadorEmail;

            $pdo->beginTransaction();

            $insertadas = 0;
            foreach ($cuentasIds as $cuentaId) {
                $cuentaId = (int)$cuentaId;
                if ($cuentaId <= 0) {
                    continue;
                }
                if (AutorizadorCuentaContable::existeDuplicado($cuentaId, $autorizadorEmail)) {
                    continue;
                }
                AutorizadorCuentaContable::create([
                    'cuenta_contable_id'  => $cuentaId,
                    'autorizador_email'   => $autorizadorEmail,
                    'autorizador_nombre'  => $nombreAutorizador,
                    'descripcion'         => $observaciones ?: null,
                    'activo'              => $activo,
                    'prioridad'           => 1,
                    'ignora_centro_costo' => 0,
                ]);
                $insertadas++;
            }

            $pdo->commit();

            Redirect::to('/admin/autorizadores/cuentas-contables')
                ->withSuccess("Autorizador configurado con {$insertadas} cuenta(s) contable(s)")
                ->send();

        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Redirect::back()
                ->withError('Error al crear el autorizador: ' . $e->getMessage())
                ->withInput()
                ->send();
        }
    }

    public function editCuentaContable($id)
    {
        $pdo = Model::getConnection();

        $email = $id;
        if (strpos((string)$id, '@') === false) {
            $stmt = $pdo->prepare("SELECT autorizador_email FROM autorizadores_cuentas_contables WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                Redirect::to('/admin/autorizadores/cuentas-contables')
                    ->withError('Autorizador no encontrado')
                    ->send();
                return;
            }
            $email = $row['autorizador_email'];
        }

        $cuentasAsignadas = AutorizadorCuentaContable::porEmail($email);
        if (empty($cuentasAsignadas)) {
            Redirect::to('/admin/autorizadores/cuentas-contables')
                ->withError('Autorizador no encontrado')
                ->send();
            return;
        }

        $idsAsignados = array_column($cuentasAsignadas, 'cuenta_contable_id');

        try {
            $sqlCuentas = "SELECT id, codigo, descripcion FROM cuenta_contable WHERE activo = 1 ORDER BY codigo ASC";
            $stmtCuentas = $pdo->prepare($sqlCuentas);
            $stmtCuentas->execute();
            $cuentas_contables = $stmtCuentas->fetchAll(\PDO::FETCH_ASSOC);

            $sqlCentros = "SELECT id, nombre FROM centro_de_costo ORDER BY nombre ASC";
            $stmtCentros = $pdo->prepare($sqlCentros);
            $stmtCentros->execute();
            $centros_costo = $stmtCentros->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $cuentas_contables = [];
            $centros_costo     = [];
        }

        $stmtUsr = $pdo->prepare(
            "SELECT COALESCE(azure_display_name, nombre, email) AS nombre FROM usuarios WHERE azure_email = ? OR email = ? LIMIT 1"
        );
        $stmtUsr->execute([$email, $email]);
        $nombreAutorizador = $stmtUsr->fetchColumn() ?: $email;

        View::render('admin/autorizadores/cuentas_contables_edit', [
            'id'                => $cuentasAsignadas[0]['id'],
            'email'             => $email,
            'nombre'            => $nombreAutorizador,
            'ids_asignados'     => $idsAsignados,
            'cuentas_contables' => $cuentas_contables,
            'centros_costo'     => $centros_costo,
            'observaciones'     => $cuentasAsignadas[0]['descripcion'] ?? '',
            'activo'            => $cuentasAsignadas[0]['activo'] ?? 1,
            'title'             => 'Editar Autorizador por Cuenta Contable',
        ]);
    }

    public function updateCuentaContable($id)
    {
        $pdo = null;
        try {
            if (!$this->validateCSRF()) {
                Redirect::back()->withError('Token de seguridad inválido')->withInput()->send();
                return;
            }

            $email      = trim($_POST['autorizador_email'] ?? '');
            $cuentasIds = $_POST['cuentas_contables'] ?? [];
            $obs        = trim($_POST['observaciones'] ?? '');
            $activo     = isset($_POST['activo']) ? 1 : 0;

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Redirect::back()->withError('Email inválido')->withInput()->send();
                return;
            }
            if (empty($cuentasIds)) {
                Redirect::back()->withError('Debe seleccionar al menos una cuenta contable')->withInput()->send();
                return;
            }

            $pdo = Model::getConnection();
            $pdo->beginTransaction();

            // Borrar las asignaciones actuales del email
            $pdo->prepare("DELETE FROM autorizadores_cuentas_contables WHERE autorizador_email = ?")->execute([$email]);

            $stmtUsr = $pdo->prepare(
                "SELECT COALESCE(azure_display_name, nombre, email) AS nombre FROM usuarios WHERE azure_email = ? OR email = ? LIMIT 1"
            );
            $stmtUsr->execute([$email, $email]);
            $nombreAutorizador = $stmtUsr->fetchColumn() ?: $email;

            foreach ($cuentasIds as $cuentaId) {
                $cuentaId = (int)$cuentaId;
                if ($cuentaId <= 0) continue;
                AutorizadorCuentaContable::create([
                    'cuenta_contable_id'  => $cuentaId,
                    'autorizador_email'   => $email,
                    'autorizador_nombre'  => $nombreAutorizador,
                    'descripcion'         => $obs ?: null,
                    'activo'              => $activo,
                    'prioridad'           => 1,
                    'ignora_centro_costo' => 0,
                ]);
            }

            $pdo->commit();

            Redirect::to('/admin/autorizadores/cuentas-contables')
                ->withSuccess('Autorizador actualizado exitosamente')
                ->send();

        } catch (\Exception $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            Redirect::back()
                ->withError('Error al actualizar: ' . $e->getMessage())
                ->withInput()
                ->send();
        }
    }

    public function deleteCuentaContable($id)
    {
        $identificador = urldecode($id);

        if (empty($identificador)) {
            Redirect::back()
                ->withError('Identificador de autorizador inválido')
                ->send();
            return;
        }

        $pdo = Model::getConnection();

        try {
            $pdo->beginTransaction();

            $isNumericId = ctype_digit($identificador);

            if ($isNumericId) {
                $stmtCheck = $pdo->prepare("SELECT autorizador_email FROM autorizadores_cuentas_contables WHERE id = ?");
            } else {
                $stmtCheck = $pdo->prepare("SELECT autorizador_email FROM autorizadores_cuentas_contables WHERE autorizador_email = ?");
            }

            $stmtCheck->execute([$identificador]);
            $registros = $stmtCheck->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($registros)) {
                $pdo->rollBack();
                Redirect::back()
                    ->withError('No se encontró el autorizador por cuenta contable especificado')
                    ->send();
                return;
            }

            $emailEncontrado = $registros[0]['autorizador_email'] ?? null;

            if ($isNumericId) {
                if ($emailEncontrado) {
                    $stmtDelete = $pdo->prepare("DELETE FROM autorizadores_cuentas_contables WHERE autorizador_email = ?");
                    $stmtDelete->execute([$emailEncontrado]);
                } else {
                    $stmtDelete = $pdo->prepare("DELETE FROM autorizadores_cuentas_contables WHERE id = ?");
                    $stmtDelete->execute([$identificador]);
                }
            } else {
                $stmtDelete = $pdo->prepare("DELETE FROM autorizadores_cuentas_contables WHERE autorizador_email = ?");
                $stmtDelete->execute([$identificador]);
            }

            $pdo->commit();

            Redirect::to('/admin/autorizadores/cuentas-contables')
                ->withSuccess('Autorizador por cuenta contable eliminado exitosamente')
                ->send();

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error eliminando autorizador por cuenta contable ($identificador): " . $e->getMessage());
            Redirect::back()
                ->withError('Error al eliminar el autorizador por cuenta contable: ' . $e->getMessage())
                ->send();
        }
    }

    // ========================================================================
    // FLUJO ESCALONADO DE AUTORIZACIÓN
    // ========================================================================

    public function procesarFlujoAutorizacion($requisicion)
    {
        try {
            $estado = $requisicion['estado'] ?? 'borrador';

            switch ($estado) {
                case 'borrador':
                case 'pendiente_revision':
                    return $this->enviarARevisores($requisicion);

                case 'aprobada_revision':
                    return $this->procesarMetodoPagoFlujo($requisicion);

                case 'aprobada_metodo_pago':
                    return $this->procesarCuentaContableFlujo($requisicion);

                case 'aprobada_cuenta_contable':
                case 'sin_cuenta_especial':
                    return $this->procesarAutorizacionCentro($requisicion);

                default:
                    return [
                        'success'        => false,
                        'mensaje'        => 'Estado de requisición no válido',
                        'siguiente_paso' => 'error'
                    ];
            }

        } catch (\Exception $e) {
            error_log("Error en procesarFlujoAutorizacion: " . $e->getMessage());
            return [
                'success'        => false,
                'mensaje'        => 'Error procesando autorización: ' . $e->getMessage(),
                'siguiente_paso' => 'error'
            ];
        }
    }

    public function rechazarMetodoPago($requisicionId, $motivo = '')
    {
        error_log("DEPRECADO: rechazarMetodoPago() - usar flujo de autorización normal");
        return [
            'success' => false,
            'mensaje' => 'Método deprecado. Usar flujo de autorización normal.'
        ];
    }

    // ========================================================================
    // PRIVADOS — FLUJO
    // ========================================================================

    private function enviarARevisores($requisicion)
    {
        $revisores = $this->obtenerRevisores($requisicion['centro_costo_id']);

        if (empty($revisores)) {
            return $this->procesarMetodoPagoFlujo($requisicion);
        }

        foreach ($revisores as $revisor) {
            $this->enviarNotificacion($revisor['email'], $requisicion, 'revision');
        }

        return [
            'success'        => true,
            'mensaje'        => 'Requisición enviada a revisores',
            'siguiente_paso' => 'pendiente_revision',
            'asignado_a'     => $revisores
        ];
    }

    private function procesarMetodoPagoFlujo($requisicion)
    {
        $metodoPago = $requisicion['metodo_pago'] ?? '';

        $autorizadorMetodo = $this->obtenerAutorizadorMetodoPagoInterno($metodoPago);

        if ($autorizadorMetodo) {
            $this->enviarNotificacion($autorizadorMetodo['email'], $requisicion, 'metodo_pago');

            return [
                'success'                => true,
                'mensaje'                => "Requisición enviada a centro de pago ({$metodoPago})",
                'siguiente_paso'         => 'pendiente_metodo_pago',
                'asignado_a'             => $autorizadorMetodo,
                'tipo_autorizacion'      => 'metodo_pago',
                'puede_rechazar_y_editar' => true
            ];
        }

        return $this->procesarCuentaContableFlujo($requisicion);
    }

    private function procesarCuentaContableFlujo($requisicion)
    {
        $cuentaContable = $requisicion['cuenta_contable'] ?? '';

        $autorizadorCuenta = $this->obtenerAutorizadorCuentaContableInterno($cuentaContable);

        if ($autorizadorCuenta) {
            $centroExcluido = $this->verificarCentroExcluido(
                $autorizadorCuenta['id'],
                $requisicion['centro_costo_id']
            );

            if (!$centroExcluido) {
                $this->enviarNotificacion($autorizadorCuenta['email'], $requisicion, 'cuenta_contable');

                return [
                    'success'           => true,
                    'mensaje'           => "Requisición enviada a especialista contable (cuenta: {$cuentaContable})",
                    'siguiente_paso'    => 'pendiente_cuenta_contable',
                    'asignado_a'        => $autorizadorCuenta,
                    'tipo_autorizacion' => 'cuenta_contable'
                ];
            }
        }

        return $this->procesarAutorizacionCentro($requisicion);
    }

    private function procesarAutorizacionCentro($requisicion)
    {
        $centroCostoId = $requisicion['centro_costo_id'];

        $respaldoActivo = $this->obtenerRespaldoActivo($centroCostoId, date('Y-m-d'));

        if ($respaldoActivo) {
            $this->enviarNotificacion($respaldoActivo['autorizador_respaldo_email'], $requisicion, 'centro_costo_respaldo');

            return [
                'success'           => true,
                'mensaje'           => "Requisición enviada a autorizador de respaldo ({$respaldoActivo['nombre_respaldo']})",
                'siguiente_paso'    => 'pendiente_autorizacion_final',
                'asignado_a'        => [
                    'email'  => $respaldoActivo['autorizador_respaldo_email'],
                    'nombre' => $respaldoActivo['nombre_respaldo'],
                    'tipo'   => 'respaldo',
                    'motivo' => $respaldoActivo['motivo']
                ],
                'tipo_autorizacion' => 'centro_costo_respaldo'
            ];
        }

        $autorizadorPrincipal = $this->obtenerAutorizadorPrincipalCentroCosto($centroCostoId);

        if ($autorizadorPrincipal) {
            $this->enviarNotificacion($autorizadorPrincipal['email'], $requisicion, 'centro_costo');

            return [
                'success'           => true,
                'mensaje'           => "Requisición enviada a autorizador del centro de costo ({$autorizadorPrincipal['nombre']})",
                'siguiente_paso'    => 'pendiente_autorizacion_final',
                'asignado_a'        => $autorizadorPrincipal,
                'tipo_autorizacion' => 'centro_costo'
            ];
        }

        return [
            'success'        => false,
            'mensaje'        => 'No se encontró autorizador para el centro de costo',
            'siguiente_paso' => 'error'
        ];
    }

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

    private function obtenerAutorizadorMetodoPagoInterno($metodoPago)
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

    private function obtenerAutorizadorCuentaContableInterno($cuentaContable)
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

    private function obtenerRespaldoActivo($centroCostoId, $fecha)
    {
        try {
            $sql = "SELECT * FROM autorizador_respaldo
                    WHERE centro_costo_id = ?
                    AND estado = 'activo'
                    AND fecha_inicio <= ?
                    AND (fecha_fin IS NULL OR fecha_fin >= ?)
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

    private function enviarNotificacion($email, $requisicion, $tipo)
    {
        try {
            error_log("Notificación enviada a $email para requisición {$requisicion['id']} - tipo: $tipo");
            $this->registrarNotificacion($email, $requisicion['id'], $tipo);
            return true;
        } catch (\Exception $e) {
            error_log("Error enviando notificación: " . $e->getMessage());
            return false;
        }
    }

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

    // ========================================================================
    // PRIVADOS — HELPERS
    // ========================================================================

    private function obtenerAutorizadorMetodoPagoPorEmail(string $email): ?array
    {
        $conn = Model::getConnection();

        $stmt = $conn->prepare("SELECT * FROM autorizadores_metodos_pago WHERE autorizador_email = ? ORDER BY id DESC");
        $stmt->execute([$email]);
        $registros = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($registros)) {
            return null;
        }

        $metodos     = [];
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

        $persona = PersonaAutorizada::porEmail($email);
        if (is_object($persona)) {
            $persona = $persona->toArray();
        }

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
            'id'                   => $ultimo['id'] ?? null,
            'email'                => $email,
            'nombre'               => $nombre,
            'cargo'                => $persona['cargo'] ?? null,
            'activo'               => isset($persona['activo']) ? ((int)$persona['activo'] === 1) : true,
            'fecha_inicio'         => $persona['fecha_inicio'] ?? null,
            'fecha_fin'            => $persona['fecha_fin'] ?? null,
            'metodos_autorizados'  => $metodos,
            'metodo_pago_actual'   => $ultimo['metodo_pago'] ?? null,
            'descripcion'          => $descripcion,
            'observaciones'        => $descripcion,
            'notificacion'         => $ultimo['notificacion'] ?? null,
            'fecha_actualizacion'  => $ultimo['fecha_actualizacion'] ?? null,
            'actualizado_por'      => $ultimo['actualizado_por'] ?? null,
            'registros'            => $registros,
            'id_registro'          => $ultimo['id'] ?? null,
            'centros_costo'        => $centros,
            'centros_costo_count'  => is_array($centros) ? count($centros) : 0
        ];
    }

    private function eliminarAutorizadorMetodoPago(string $identificador, bool $usarTransaccion = true): array
    {
        $pdo = Model::getConnection();
        $transaccionIniciada = false;

        try {
            if ($usarTransaccion) {
                $pdo->beginTransaction();
                $transaccionIniciada = true;
            }

            $rows = 0;

            if (filter_var($identificador, FILTER_VALIDATE_EMAIL)) {
                $stmtDelete = $pdo->prepare("DELETE FROM autorizadores_metodos_pago WHERE autorizador_email = ?");
                $stmtDelete->execute([$identificador]);
                $rows = $stmtDelete->rowCount();
            } elseif (is_numeric($identificador)) {
                $stmtById = $pdo->prepare("DELETE FROM autorizadores_metodos_pago WHERE id = ?");
                $stmtById->execute([(int)$identificador]);
                $rows = $stmtById->rowCount();
            }

            if ($rows > 0) {
                if ($transaccionIniciada) {
                    $pdo->commit();
                }
                return ['success' => true,  'message' => 'Autorizador eliminado exitosamente'];
            }

            if ($transaccionIniciada && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['success' => false, 'message' => 'No se pudo eliminar el autorizador por método de pago'];

        } catch (\Exception $e) {
            if ($transaccionIniciada && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error eliminando autorizador de método de pago: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar el autorizador por método de pago: ' . $e->getMessage()];
        }
    }

    private function handleLegacyResponse(bool $success, string $message): void
    {
        $isDevServer = (
            (isset($_SERVER['SERVER_SOFTWARE']) &&
             strpos($_SERVER['SERVER_SOFTWARE'], 'Development Server') !== false) ||
            php_sapi_name() === 'cli-server' ||
            (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost' &&
             isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 8000)
        );

        if ($isDevServer) {
            http_response_code($success ? 200 : 400);
            echo '<!DOCTYPE html>
<html>
<head>
    <title>Resultado</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; text-align: center; }
        .success { color: #28a745; background: #d4edda; padding: 20px; border-radius: 5px; }
        .error { color: #dc3545; background: #f8d7da; padding: 20px; border-radius: 5px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="' . ($success ? 'success' : 'error') . '">
        <h2>' . ($success ? 'Exito' : 'Error') . '</h2>
        <p>' . htmlspecialchars($message) . '</p>
    </div>
    <a href="/admin/autorizadores/metodos-pago" class="btn">Volver a Autorizadores</a>
    <script>
        ' . ($success ? 'setTimeout(() => window.location.href = "/admin/autorizadores/metodos-pago", 2000);' : '') . '
    </script>
</body>
</html>';
            return;
        }

        if ($success) {
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withSuccess($message)
                ->send();
        } else {
            Redirect::to('/admin/autorizadores/metodos-pago')
                ->withError($message)
                ->send();
        }
    }

    private function checkTablesExist($tableNames)
    {
        $result = [];

        foreach ($tableNames as $tableName) {
            try {
                $sql  = "SHOW TABLES LIKE ?";
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
}
