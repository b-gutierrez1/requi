<?php
/**
 * CentroCostoController
 *
 * CRUD de centros de costo para administración.
 * Movido desde AdminController como parte del refactoring.
 *
 * @package RequisicionesMVC\Controllers\Admin
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Helpers\View;
use App\Helpers\Redirect;
use App\Models\Model;
use App\Models\CentroCosto;
use App\Models\UnidadNegocio;

class CentroCostoController extends Controller
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
    // CENTROS DE COSTO
    // ========================================================================

    /**
     * Lista centros de costo
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
     * Muestra el formulario para crear un nuevo centro de costo
     */
    public function createCentro()
    {
        $unidadesNegocio = UnidadNegocio::activas();

        View::render('admin/centros/create', [
            'title' => 'Nuevo Centro de Costo',
            'unidadesNegocio' => $unidadesNegocio
        ]);
    }

    /**
     * Crea un centro de costo
     */
    public function storeCentro()
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
        }

        $data = [
            'nombre'                    => $this->sanitize($_POST['nombre']),
            'codigo'                    => !empty($_POST['codigo']) ? $this->sanitize($_POST['codigo']) : null,
            'factura'                   => intval($_POST['factura'] ?? 1),
            'unidad_negocio_id'         => !empty($_POST['unidad_negocio_id']) ? intval($_POST['unidad_negocio_id']) : null,
            'requiere_asignacion_manual' => isset($_POST['requiere_asignacion_manual']) ? 1 : 0,
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
     * Muestra los detalles de un centro de costo
     */
    public function showCentro($id)
    {
        $centro = CentroCosto::find($id);

        if (!$centro) {
            Redirect::to('/admin/centros')
                ->withError('Centro de costo no encontrado')
                ->send();
            return;
        }

        View::render('admin/centros/show', [
            'centro' => $centro,
            'title' => 'Detalles del Centro de Costo'
        ]);
    }

    /**
     * Muestra el formulario para editar un centro de costo
     */
    public function editCentro($id)
    {
        $centro = CentroCosto::find($id);

        if (!$centro) {
            Redirect::to('/admin/centros')
                ->withError('Centro de costo no encontrado')
                ->send();
            return;
        }

        $unidadesNegocio = UnidadNegocio::activas();

        View::render('admin/centros/edit', [
            'centro' => $centro,
            'title' => 'Editar Centro de Costo',
            'unidadesNegocio' => $unidadesNegocio
        ]);
    }

    /**
     * Actualiza un centro de costo
     */
    public function updateCentro($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
        }

        $data = [
            'nombre'                    => $this->sanitize($_POST['nombre']),
            'factura'                   => intval($_POST['factura'] ?? 1),
            'unidad_negocio_id'         => !empty($_POST['unidad_negocio_id']) ? intval($_POST['unidad_negocio_id']) : null,
            'requiere_asignacion_manual' => isset($_POST['requiere_asignacion_manual']) ? 1 : 0,
        ];

        // Solo actualizar codigo si viene en el POST (evita borrar el valor existente)
        if (isset($_POST['codigo'])) {
            $data['codigo'] = !empty($_POST['codigo']) ? $this->sanitize($_POST['codigo']) : null;
        }

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

    /**
     * Elimina un centro de costo
     */
    public function deleteCentro($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
            return;
        }

        $centro = CentroCosto::find($id);

        if (!$centro) {
            Redirect::to('/admin/centros')
                ->withError('Centro de costo no encontrado')
                ->send();
            return;
        }

        // Verificar si el centro está siendo usado en requisiciones
        $pdo = Model::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM distribucion_gasto WHERE centro_costo_id = ?");
        $stmt->execute([$id]);
        $uso = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($uso['total'] > 0) {
            Redirect::to('/admin/centros')
                ->withError('No se puede eliminar: el centro de costo está siendo utilizado en ' . $uso['total'] . ' registros')
                ->send();
            return;
        }

        $resultado = CentroCosto::destroy($id);

        if ($resultado) {
            Redirect::to('/admin/centros')
                ->withSuccess('Centro de costo eliminado correctamente')
                ->send();
        } else {
            Redirect::to('/admin/centros')
                ->withError('Error al eliminar el centro de costo')
                ->send();
        }
    }

    public function toggleCentro($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()->withError('Token inválido')->send();
            return;
        }

        $centro = CentroCosto::find($id);
        if (!$centro) {
            Redirect::to('/admin/centros')->withError('Centro de costo no encontrado')->send();
            return;
        }

        $nuevoEstado = $centro->activo ? 0 : 1;
        CentroCosto::updateById($id, ['activo' => $nuevoEstado]);

        $mensaje = $nuevoEstado ? 'Centro de costo activado' : 'Centro de costo desactivado';
        Redirect::to('/admin/centros')->withSuccess($mensaje)->send();
    }
}
