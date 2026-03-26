<?php
/**
 * ReporteController
 *
 * Maneja los reportes administrativos del sistema.
 * Movido desde AdminController como parte del refactoring.
 *
 * @package RequisicionesMVC\Controllers\Admin
 */

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Helpers\View;
use App\Helpers\EstadoHelper;
use App\Models\Requisicion;

class ReporteController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!\App\Helpers\Session::isAdmin()) {
            \App\Helpers\Redirect::to('/dashboard')
                ->withError('No tienes permisos de administrador')
                ->send();
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

    public function reporteEstadoRequisiciones()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }
        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin    = $_POST['fecha_fin']    ?? date('Y-m-t');

            $sql = "SELECT r.numero_requisicion,
                           u.azure_display_name AS solicitante,
                           r.proveedor_nombre, r.monto_total, r.moneda,
                           r.fecha_solicitud, af.estado
                    FROM requisiciones r
                    LEFT JOIN autorizacion_flujo af ON r.id = af.requisicion_id
                    LEFT JOIN usuarios u ON r.usuario_id = u.id
                    WHERE DATE(r.fecha_solicitud) BETWEEN ? AND ?
                    ORDER BY r.fecha_solicitud DESC";

            $stmt = Requisicion::query($sql, [$fechaInicio, $fechaFin]);
            $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $columnas = ['# Requisición', 'Solicitante', 'Proveedor', 'Monto', 'Moneda', 'Fecha', 'Estado'];
            $datos = array_map(fn($r) => [
                $r['numero_requisicion'],
                $r['solicitante'] ?? '',
                $r['proveedor_nombre'],
                $r['monto_total'],
                $r['moneda'],
                $r['fecha_solicitud'],
                $r['estado'] ?? '',
            ], $filas);

            $this->exportarCSV(
                'reporte_estado_requisiciones_' . date('Y-m-d'),
                'Estado de Requisiciones',
                "Del $fechaInicio al $fechaFin",
                $columnas, $datos
            );
        } catch (\Exception $e) {
            error_log("Error reporte estado requisiciones: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al generar el reporte'], 500);
        }
    }

    public function reporteGastoCentroCosto()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }
        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin    = $_POST['fecha_fin']    ?? date('Y-m-t');

            $sql = "SELECT cc.nombre AS centro_costo,
                           COUNT(DISTINCT dg.requisicion_id) AS total_requisiciones,
                           SUM(dg.cantidad) AS monto_total
                    FROM centro_de_costo cc
                    INNER JOIN distribucion_gasto dg ON cc.id = dg.centro_costo_id
                    INNER JOIN requisiciones r ON dg.requisicion_id = r.id
                    WHERE DATE(r.fecha_solicitud) BETWEEN ? AND ?
                    GROUP BY cc.id, cc.nombre
                    ORDER BY monto_total DESC";

            $stmt = Requisicion::query($sql, [$fechaInicio, $fechaFin]);
            $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $columnas = ['Centro de Costo', 'Total Requisiciones', 'Monto Total'];
            $datos = array_map(fn($r) => [
                $r['centro_costo'],
                $r['total_requisiciones'],
                $r['monto_total'],
            ], $filas);

            $this->exportarCSV(
                'reporte_gasto_centro_costo_' . date('Y-m-d'),
                'Gasto por Centro de Costo',
                "Del $fechaInicio al $fechaFin",
                $columnas, $datos
            );
        } catch (\Exception $e) {
            error_log("Error reporte gasto centro costo: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al generar el reporte'], 500);
        }
    }

    public function reporteGastoUnidadRequirente()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }
        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin    = $_POST['fecha_fin']    ?? date('Y-m-t');

            $sql = "SELECT r.unidad_requirente AS unidad,
                           COUNT(r.id) AS total_requisiciones,
                           SUM(r.monto_total) AS monto_total
                    FROM requisiciones r
                    WHERE DATE(r.fecha_solicitud) BETWEEN ? AND ?
                      AND r.unidad_requirente IS NOT NULL AND r.unidad_requirente != ''
                    GROUP BY r.unidad_requirente
                    ORDER BY monto_total DESC";

            $stmt = Requisicion::query($sql, [$fechaInicio, $fechaFin]);
            $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $columnas = ['Unidad Requirente', 'Total Requisiciones', 'Monto Total'];
            $datos = array_map(fn($r) => [
                $r['unidad'],
                $r['total_requisiciones'],
                $r['monto_total'],
            ], $filas);

            $this->exportarCSV(
                'reporte_gasto_unidad_requirente_' . date('Y-m-d'),
                'Gasto por Unidad Requirente',
                "Del $fechaInicio al $fechaFin",
                $columnas, $datos
            );
        } catch (\Exception $e) {
            error_log("Error reporte gasto unidad requirente: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al generar el reporte'], 500);
        }
    }

    public function reporteTasaRechazo()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }
        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin    = $_POST['fecha_fin']    ?? date('Y-m-t');

            $sqlResumen = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN af.estado IN ('rechazado','rechazado_revision','rechazado_autorizacion') THEN 1 ELSE 0 END) AS rechazadas,
                SUM(CASE WHEN af.estado = 'autorizado' THEN 1 ELSE 0 END) AS aprobadas,
                ROUND(SUM(CASE WHEN af.estado IN ('rechazado','rechazado_revision','rechazado_autorizacion') THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) AS tasa_rechazo
            FROM autorizacion_flujo af
            INNER JOIN requisiciones r ON af.requisicion_id = r.id
            WHERE DATE(r.fecha_solicitud) BETWEEN ? AND ?";

            $resumen = Requisicion::query($sqlResumen, [$fechaInicio, $fechaFin])
                ->fetch(\PDO::FETCH_ASSOC);

            $sqlMotivos = "SELECT COALESCE(af.motivo_rechazo, '(sin motivo)') AS motivo,
                                  COUNT(*) AS cantidad
                           FROM autorizacion_flujo af
                           INNER JOIN requisiciones r ON af.requisicion_id = r.id
                           WHERE af.estado IN ('rechazado','rechazado_revision','rechazado_autorizacion')
                             AND DATE(r.fecha_solicitud) BETWEEN ? AND ?
                           GROUP BY af.motivo_rechazo
                           ORDER BY cantidad DESC";

            $motivos = Requisicion::query($sqlMotivos, [$fechaInicio, $fechaFin])
                ->fetchAll(\PDO::FETCH_ASSOC);

            $columnas = ['Concepto', 'Valor'];
            $datos = [
                ['Total Requisiciones', $resumen['total'] ?? 0],
                ['Aprobadas',           $resumen['aprobadas'] ?? 0],
                ['Rechazadas',          $resumen['rechazadas'] ?? 0],
                ['Tasa de Rechazo (%)', $resumen['tasa_rechazo'] ?? 0],
                [],
                ['Motivo de Rechazo', 'Cantidad'],
            ];
            foreach ($motivos as $m) {
                $datos[] = [$m['motivo'], $m['cantidad']];
            }

            $this->exportarCSV(
                'reporte_tasa_rechazo_' . date('Y-m-d'),
                'Tasa de Rechazo',
                "Del $fechaInicio al $fechaFin",
                $columnas, $datos
            );
        } catch (\Exception $e) {
            error_log("Error reporte tasa rechazo: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al generar el reporte'], 500);
        }
    }

    public function reporteFormaPago()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }
        try {
            $fechaInicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin    = $_POST['fecha_fin']    ?? date('Y-m-t');

            $sql = "SELECT r.forma_pago,
                           COUNT(r.id) AS cantidad,
                           SUM(r.monto_total) AS monto_total,
                           ROUND(COUNT(r.id) / (SELECT COUNT(*) FROM requisiciones WHERE DATE(fecha_solicitud) BETWEEN ? AND ?) * 100, 2) AS porcentaje
                    FROM requisiciones r
                    WHERE DATE(r.fecha_solicitud) BETWEEN ? AND ?
                    GROUP BY r.forma_pago
                    ORDER BY cantidad DESC";

            $stmt = Requisicion::query($sql, [$fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
            $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $columnas = ['Forma de Pago', 'Cantidad', 'Monto Total', '% del Total'];
            $datos = array_map(fn($r) => [
                $r['forma_pago'] ?? '(sin especificar)',
                $r['cantidad'],
                $r['monto_total'],
                ($r['porcentaje'] ?? 0) . '%',
            ], $filas);

            $this->exportarCSV(
                'reporte_forma_pago_' . date('Y-m-d'),
                'Distribución por Forma de Pago',
                "Del $fechaInicio al $fechaFin",
                $columnas, $datos
            );
        } catch (\Exception $e) {
            error_log("Error reporte forma pago: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al generar el reporte'], 500);
        }
    }

    // ========================================================================
    // MÉTODOS PRIVADOS DE GENERACIÓN
    // ========================================================================

    private function exportarCSV(string $nombreArchivo, string $titulo, string $periodo, array $columnas, array $filas): void
    {
        if (empty($filas)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No hay datos para el período seleccionado',
            ], 422);
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [$titulo]);
        fputcsv($output, ['Generado el: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, ["Período: $periodo"]);
        fputcsv($output, []);
        fputcsv($output, $columnas);
        foreach ($filas as $fila) {
            fputcsv($output, $fila);
        }

        fclose($output);
        exit;
    }

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

    private function generarCSV($datos, $nombreArchivo)
    {
        $tieneFilas = !empty($datos['usuarios'])
            || !empty($datos['requisiciones'])
            || !empty($datos['autorizaciones'])
            || !empty($datos['datos_financieros']);

        if (!$tieneFilas) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No hay datos para el período seleccionado',
            ], 422);
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [$datos['titulo']]);
        fputcsv($output, ['Generado el: ' . $datos['fecha_generacion']]);
        fputcsv($output, ['Período: ' . $datos['periodo']]);
        fputcsv($output, []);

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

    private function generarCSVRequisiciones($output, $datos)
    {
        fputcsv($output, ['Estadísticas']);
        fputcsv($output, ['Total Requisiciones', $datos['estadisticas']['total']]);
        fputcsv($output, ['Monto Total', 'Q ' . number_format($datos['estadisticas']['monto_total'], 5)]);
        fputcsv($output, []);

        fputcsv($output, ['Detalle de Requisiciones']);
        fputcsv($output, ['ID', 'Fecha', 'Proveedor', 'Usuario', 'Monto', 'Estado']);

        foreach ($datos['requisiciones'] as $req) {
            $simbolo = ($req['moneda'] ?? 'GTQ') === 'USD' ? '$' : 'Q';
            fputcsv($output, [
                $req['id'],
                $req['fecha'],
                $req['nombre_razon_social'],
                $req['usuario_nombre'] ?? '',
                $simbolo . ' ' . number_format($req['monto_total'], 5),
                $req['estado']
            ]);
        }
    }

    private function generarCSVAutorizaciones($output, $datos)
    {
        fputcsv($output, ['Detalle de Autorizaciones']);
        fputcsv($output, ['ID Flujo', 'Fecha', 'Proveedor', 'Monto', 'Autorizador', 'Estado', 'Fecha Autorización', 'Centro Costo']);

        foreach ($datos['autorizaciones'] as $auth) {
            $simbolo = ($auth['moneda'] ?? 'GTQ') === 'USD' ? '$' : 'Q';
            fputcsv($output, [
                $auth['id'],
                $auth['fecha_creacion'],
                $auth['nombre_razon_social'],
                $simbolo . ' ' . number_format($auth['monto_total'], 5),
                $auth['autorizador_email'] ?? '',
                $auth['estado_auth'] ?? '',
                $auth['fecha_autorizacion'] ?? '',
                $auth['centro_costo_nombre'] ?? ''
            ]);
        }
    }

    private function generarCSVFinanciero($output, $datos)
    {
        fputcsv($output, ['Resumen Financiero']);
        fputcsv($output, ['Monto Total General', 'Q ' . number_format($datos['monto_total_general'], 5)]);
        fputcsv($output, []);

        fputcsv($output, ['Gasto por Centro de Costo']);
        fputcsv($output, ['Código', 'Nombre', 'Monto Total', 'Total Requisiciones']);

        foreach ($datos['datos_financieros'] as $centro) {
            fputcsv($output, [
                $centro['codigo'],
                $centro['nombre'],
                'Q ' . number_format($centro['monto_total'] ?? 0, 5),
                $centro['total_requisiciones'] ?? 0
            ]);
        }
    }

    private function generarPDF($datos, $nombreArchivo)
    {
        $this->generarCSV($datos, $nombreArchivo);
    }

    private function generarExcel($datos, $nombreArchivo)
    {
        $this->generarCSV($datos, $nombreArchivo);
    }

    private function contarPorEstado($requisiciones)
    {
        $conteo = [];
        foreach ($requisiciones as $req) {
            $estado = is_object($req) ? $req->getEstadoReal() : EstadoHelper::getEstadoFromData($req);
            $conteo[$estado] = ($conteo[$estado] ?? 0) + 1;
        }
        return $conteo;
    }
}
