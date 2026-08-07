<?php
/**
 * UnidadNegocioController
 *
 * CRUD de unidades de negocio para administración.
 * Movido desde AdminController como parte del refactoring.
 *
 * @package RequisicionesMVC\Controllers\Admin
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Helpers\View;
use App\Helpers\Redirect;
use App\Models\Model;
use App\Models\UnidadNegocio;
use App\Models\CentroCosto;

class UnidadNegocioController extends Controller
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
    // UNIDADES DE NEGOCIO
    // ========================================================================

    /**
     * Lista unidades de negocio
     */
    public function centrosCosto()
    {
        $centros = UnidadNegocio::all();

        View::render('admin/centros/index', [
            'centros' => $centros,
            'title' => 'Unidades de Negocio'
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo unidad de negocio
     */
    public function createCentro()
    {
        $unidadesNegocio = CentroCosto::activas();

        View::render('admin/centros/create', [
            'title' => 'Nuevo Unidad de Negocio',
            'unidadesNegocio' => $unidadesNegocio
        ]);
    }

    /**
     * Crea un unidad de negocio
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
            'centro_costo_id'         => !empty($_POST['centro_costo_id']) ? intval($_POST['centro_costo_id']) : null,
            'requiere_asignacion_manual' => isset($_POST['requiere_asignacion_manual']) ? 1 : 0,
        ];

        $id = UnidadNegocio::create($data);

        if ($id) {
            Redirect::to('/admin/centros')
                ->withSuccess('Centro de costo creado exitosamente')
                ->send();
        } else {
            Redirect::back()
                ->withError('Error al crear unidad de negocio')
                ->withInput($data)
                ->send();
        }
    }

    /**
     * Muestra los detalles de un unidad de negocio
     */
    public function showCentro($id)
    {
        $centro = UnidadNegocio::find($id);

        if (!$centro) {
            Redirect::to('/admin/centros')
                ->withError('Centro de costo no encontrado')
                ->send();
            return;
        }

        View::render('admin/centros/show', [
            'centro' => $centro,
            'title' => 'Detalles del Unidad de Negocio'
        ]);
    }

    /**
     * Muestra el formulario para editar un unidad de negocio
     */
    public function editCentro($id)
    {
        $centro = UnidadNegocio::find($id);

        if (!$centro) {
            Redirect::to('/admin/centros')
                ->withError('Centro de costo no encontrado')
                ->send();
            return;
        }

        $unidadesNegocio = CentroCosto::activas();

        View::render('admin/centros/edit', [
            'centro' => $centro,
            'title' => 'Editar Unidad de Negocio',
            'unidadesNegocio' => $unidadesNegocio
        ]);
    }

    /**
     * Actualiza un unidad de negocio
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
            'centro_costo_id'         => !empty($_POST['centro_costo_id']) ? intval($_POST['centro_costo_id']) : null,
            'requiere_asignacion_manual' => isset($_POST['requiere_asignacion_manual']) ? 1 : 0,
        ];

        // Solo actualizar codigo si viene en el POST (evita borrar el valor existente)
        if (isset($_POST['codigo'])) {
            $data['codigo'] = !empty($_POST['codigo']) ? $this->sanitize($_POST['codigo']) : null;
        }

        $resultado = UnidadNegocio::updateById($id, $data);

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
     * Elimina un unidad de negocio
     */
    public function deleteCentro($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()
                ->withError('Token inválido')
                ->send();
            return;
        }

        $centro = UnidadNegocio::find($id);

        if (!$centro) {
            Redirect::to('/admin/centros')
                ->withError('Centro de costo no encontrado')
                ->send();
            return;
        }

        // Verificar si el centro está siendo usado en requisiciones
        $pdo = Model::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM distribucion_gasto WHERE unidad_negocio_id = ?");
        $stmt->execute([$id]);
        $uso = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($uso['total'] > 0) {
            Redirect::to('/admin/centros')
                ->withError('No se puede eliminar: el unidad de negocio está siendo utilizado en ' . $uso['total'] . ' registros')
                ->send();
            return;
        }

        $resultado = UnidadNegocio::destroy($id);

        if ($resultado) {
            Redirect::to('/admin/centros')
                ->withSuccess('Centro de costo eliminado correctamente')
                ->send();
        } else {
            Redirect::to('/admin/centros')
                ->withError('Error al eliminar el unidad de negocio')
                ->send();
        }
    }

    public function toggleCentro($id)
    {
        if (!$this->validateCSRF()) {
            Redirect::back()->withError('Token inválido')->send();
            return;
        }

        $centro = UnidadNegocio::find($id);
        if (!$centro) {
            Redirect::to('/admin/centros')->withError('Centro de costo no encontrado')->send();
            return;
        }

        $nuevoEstado = $centro->activo ? 0 : 1;
        UnidadNegocio::updateById($id, ['activo' => $nuevoEstado]);

        $mensaje = $nuevoEstado ? 'Centro de costo activado' : 'Centro de costo desactivado';
        Redirect::to('/admin/centros')->withSuccess($mensaje)->send();
    }
}
