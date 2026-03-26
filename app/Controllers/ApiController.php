<?php
/**
 * API Controller
 *
 * Endpoints API para consultas AJAX y operaciones rápidas
 *
 * @package RequisicionesMVC\Controllers
 * @version 2.0
 */

namespace App\Controllers;

use App\Models\CentroCosto;
use App\Models\UnidadNegocio;
use App\Repositories\AutorizacionCentroRepository;

class ApiController extends Controller
{
    /**
     * Repositorio de autorizaciones por centro de costo.
     *
     * @var AutorizacionCentroRepository
     */
    private AutorizacionCentroRepository $centroRepository;

    public function __construct()
    {
        parent::__construct();
        $this->centroRepository = new AutorizacionCentroRepository();
    }

    /**
     * Obtiene la unidad de negocio de un centro de costo
     *
     * GET /api/centro-costo/{id}/unidad-negocio
     *
     * @param int $centroCostoId
     * @return void
     */
    public function getUnidadNegocioPorCentro($centroCostoId)
    {
        try {
            $centroCosto = CentroCosto::find($centroCostoId);

            if (!$centroCosto) {
                $this->jsonResponse(['success' => false, 'error' => 'Centro de costo no encontrado'], 404);
            }

            $unidadNegocioId = $centroCosto['unidad_negocio_id'] ?? null;

            if (!$unidadNegocioId) {
                $this->jsonResponse([
                    'success' => true,
                    'data' => null,
                    'message' => 'Este centro de costo no tiene unidad de negocio asignada'
                ]);
            }

            $unidadNegocio = UnidadNegocio::find($unidadNegocioId);

            if (!$unidadNegocio) {
                $this->jsonResponse(['success' => false, 'error' => 'Unidad de negocio no encontrada'], 404);
            }

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'id'          => $unidadNegocio['id'],
                    'nombre'      => $unidadNegocio['nombre'],
                    'codigo'      => $unidadNegocio['codigo'],
                    'descripcion' => $unidadNegocio['descripcion'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error al obtener la unidad de negocio: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene todas las unidades de negocio activas
     *
     * GET /api/unidades-negocio
     *
     * @return void
     */
    public function getUnidadesNegocio()
    {
        try {
            $unidades = UnidadNegocio::activas();
            $this->jsonResponse(['success' => true, 'data' => $unidades]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error al obtener unidades de negocio: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene todos los centros de costo activos con sus unidades de negocio
     *
     * GET /api/centros-costo
     *
     * @return void
     */
    public function getCentrosCosto()
    {
        try {
            $centros = CentroCosto::activos();
            $this->jsonResponse(['success' => true, 'data' => $centros]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error al obtener centros de costo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene autorizaciones pendientes del usuario actual
     *
     * GET /api/autorizaciones/pendientes
     *
     * @return void
     */
    public function getAutorizacionesPendientes()
    {
        try {
            if (!$this->isAuthenticated()) {
                $this->jsonResponse(['success' => false, 'error' => 'No autenticado'], 401);
            }

            $usuarioId = $this->getUsuarioId();

            $stmtUsuario = $this->db->prepare("SELECT email FROM usuarios WHERE id = ?");
            $stmtUsuario->execute([$usuarioId]);
            $usuario = $stmtUsuario->fetch(\PDO::FETCH_ASSOC);

            if (!$usuario || empty($usuario['email'])) {
                $this->jsonResponse(['success' => true, 'count' => 0]);
            }

            $totalPendientes = $this->centroRepository->countPendingByEmail($usuario['email']);

            $this->jsonResponse(['success' => true, 'count' => (int)$totalPendientes]);

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error al obtener autorizaciones pendientes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Busca proveedores por término
     *
     * GET /api/proveedores/buscar?q=termino
     *
     * @return void
     */
    public function buscarProveedores()
    {
        try {
            $termino = substr(trim($_GET['q'] ?? ''), 0, 100);

            if (strlen($termino) < 2) {
                $this->jsonResponse(['success' => true, 'data' => []]);
            }

            $sql = "SELECT DISTINCT nombre_razon_social, nit
                    FROM requisiciones
                    WHERE nombre_razon_social LIKE ? OR nit LIKE ?
                    ORDER BY nombre_razon_social ASC
                    LIMIT 20";

            $stmt = $this->db->prepare($sql);
            $search = "%{$termino}%";
            $stmt->execute([$search, $search]);
            $resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $this->jsonResponse(['success' => true, 'data' => $resultados]);

        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error al buscar proveedores: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verifica el estado del sistema
     *
     * GET /api/health
     *
     * @return void
     */
    public function health()
    {
        $this->jsonResponse([
            'success'   => true,
            'status'    => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'version'   => '2.0'
        ]);
    }
}
