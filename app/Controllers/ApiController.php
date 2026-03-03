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
        header('Content-Type: application/json');

        try {
            // Validar que el centro de costo exista
            $centroCosto = CentroCosto::find($centroCostoId);
            
            if (!$centroCosto) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Centro de costo no encontrado'
                ]);
                return;
            }

            // Obtener la unidad de negocio
            $unidadNegocioId = $centroCosto['unidad_negocio_id'] ?? null;

            if (!$unidadNegocioId) {
                echo json_encode([
                    'success' => true,
                    'data' => null,
                    'message' => 'Este centro de costo no tiene unidad de negocio asignada'
                ]);
                return;
            }

            $unidadNegocio = UnidadNegocio::find($unidadNegocioId);

            if (!$unidadNegocio) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Unidad de negocio no encontrada'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $unidadNegocio['id'],
                    'nombre' => $unidadNegocio['nombre'],
                    'codigo' => $unidadNegocio['codigo'],
                    'descripcion' => $unidadNegocio['descripcion'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener la unidad de negocio: ' . $e->getMessage()
            ]);
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
        header('Content-Type: application/json');

        try {
            $unidades = UnidadNegocio::activas();

            echo json_encode([
                'success' => true,
                'data' => $unidades
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener unidades de negocio: ' . $e->getMessage()
            ]);
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
        header('Content-Type: application/json');

        try {
            $centros = CentroCosto::activos();

            echo json_encode([
                'success' => true,
                'data' => $centros
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener centros de costo: ' . $e->getMessage()
            ]);
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
        header('Content-Type: application/json');

        try {
            // Verificar que el usuario esté autenticado
            if (!isset($_SESSION['usuario_id'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'No autenticado'
                ]);
                return;
            }

            $usuarioId = $_SESSION['usuario_id'];

            $stmtUsuario = $this->db->prepare("SELECT email FROM usuarios WHERE id = ?");
            $stmtUsuario->execute([$usuarioId]);
            $usuario = $stmtUsuario->fetch(\PDO::FETCH_ASSOC);

            if (!$usuario || empty($usuario['email'])) {
                echo json_encode([
                    'success' => true,
                    'count' => 0
                ]);
                return;
            }

            $totalPendientes = $this->centroRepository->countPendingByEmail($usuario['email']);

            echo json_encode([
                'success' => true,
                'count' => (int)$totalPendientes
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener autorizaciones pendientes: ' . $e->getMessage()
            ]);
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
        header('Content-Type: application/json');

        try {
            $termino = $_GET['q'] ?? '';

            if (strlen($termino) < 2) {
                echo json_encode([
                    'success' => true,
                    'data' => []
                ]);
                return;
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

            echo json_encode([
                'success' => true,
                'data' => $resultados
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al buscar proveedores: ' . $e->getMessage()
            ]);
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
        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '2.0'
        ]);
    }
}
