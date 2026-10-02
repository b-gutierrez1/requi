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
use App\Models\Requisicion;
use App\Models\UnidadRequirente;

class ReporteController extends Controller
{
    /**
     * Reportes que tienen vista en pantalla genérica (además del CSV).
     * "gasto-unidad-requirente" no está aquí: tiene su propia vista porque
     * se agrupa por moneda de una forma que no encaja en la tabla genérica.
     */
    private const TIPOS_REPORTE = [
        'estado-requisiciones' => ['metodo' => 'datosEstadoRequisiciones', 'titulo' => 'Estado de Requisiciones'],
        'gasto-unidad-negocio' => ['metodo' => 'datosGastoUnidadNegocio', 'titulo' => 'Gasto por Unidad de Negocio'],
        'tasa-rechazo'         => ['metodo' => 'datosTasaRechazo',        'titulo' => 'Tasa de Rechazo'],
        'forma-pago'           => ['metodo' => 'datosFormaPago',          'titulo' => 'Distribución por Forma de Pago'],
    ];

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
            'title'                => 'Reportes',
            'unidades_requirentes' => UnidadRequirente::activas(),
        ]);
    }

    /**
     * Datos para las gráficas del apartado de reportes: monto por mes
     * (últimos 6 meses, agrupado por moneda, respetando los filtros de
     * unidad/moneda) y distribución por estado dentro del rango de fechas
     * seleccionado.
     *
     * @return void
     */
    public function apiResumenGrafico()
    {
        try {
            $filtros = $this->filtrosDesdeRequest($_GET);

            // Monto por mes: UNA sola consulta agrupada (antes eran 6, una
            // por mes, con la misma vuelta a la base de datos repetida).
            $meses = [];
            for ($i = 5; $i >= 0; $i--) {
                $meses[] = date('Y-m', strtotime("-{$i} months"));
            }
            $filtrosMensual = [
                'fecha_inicio'          => $meses[0] . '-01',
                'fecha_fin'             => date('Y-m-t'),
                'unidad_requirente_id'  => $filtros['unidad_requirente_id'],
                'moneda'                => $filtros['moneda'],
            ];
            [$condMensual, $paramsMensual] = $this->condicionesFiltro($filtrosMensual);

            $filas = Requisicion::query(
                "SELECT DATE_FORMAT(r.fecha_solicitud, '%Y-%m') AS ym,
                        r.moneda AS moneda,
                        COALESCE(SUM(r.monto_total), 0) AS monto
                 FROM requisiciones r
                 WHERE {$condMensual}
                 GROUP BY ym, r.moneda",
                $paramsMensual
            )->fetchAll(\PDO::FETCH_ASSOC);

            $montosPorMes = array_fill_keys($meses, []);
            foreach ($filas as $fila) {
                if (isset($montosPorMes[$fila['ym']])) {
                    $montosPorMes[$fila['ym']][$fila['moneda']] = (float)$fila['monto'];
                }
            }

            // Cada serie de moneda debe tener un valor por mes (0 si no tuvo
            // movimiento), sin importar en qué mes apareció primero.
            $monedas = [];
            foreach ($montosPorMes as $montos) {
                $monedas = array_merge($monedas, array_keys($montos));
            }
            $monedas = array_values(array_unique($monedas));

            $series = [];
            foreach ($monedas as $moneda) {
                $series[$moneda] = array_map(
                    fn($ym) => $montosPorMes[$ym][$moneda] ?? 0,
                    $meses
                );
            }

            $labels = array_map(fn($ym) => date('M Y', strtotime($ym . '-01')), $meses);

            // Distribución por estado dentro del rango de fechas + filtros
            // elegidos en la pantalla de reportes.
            [$condEstado, $paramsEstado] = $this->condicionesFiltro($filtros);
            $estados = Requisicion::query(
                "SELECT
                    SUM(CASE WHEN af.estado IN ('pendiente_revision','pendiente_autorizacion_pago','pendiente_autorizacion_cuenta','pendiente_autorizacion_centros','pendiente_autorizacion') THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN af.estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN af.estado IN ('rechazado_revision','rechazado_autorizacion','rechazado') THEN 1 ELSE 0 END) as rechazadas
                 FROM requisiciones r
                 LEFT JOIN autorizacion_flujo af ON r.id = af.requisicion_id
                 WHERE {$condEstado}",
                $paramsEstado
            )->fetch(\PDO::FETCH_ASSOC) ?: [];

            $this->jsonResponse([
                'success' => true,
                'mensual' => ['labels' => $labels, 'series' => $series],
                'estados' => [
                    'pendientes'  => (int)($estados['pendientes'] ?? 0),
                    'autorizadas' => (int)($estados['autorizadas'] ?? 0),
                    'rechazadas'  => (int)($estados['rechazadas'] ?? 0),
                ],
            ]);
        } catch (\Exception $e) {
            error_log("Error en apiResumenGrafico: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Error al generar los datos'], 500);
        }
    }

    // ========================================================================
    // DESCARGA CSV
    // ========================================================================

    public function reporteEstadoRequisiciones()
    {
        $this->descargarReporte('estado-requisiciones', 'reporte_estado_requisiciones_');
    }

    public function reporteGastoUnidadNegocio()
    {
        $this->descargarReporte('gasto-unidad-negocio', 'reporte_gasto_unidad_negocio_');
    }

    public function reporteTasaRechazo()
    {
        $this->descargarReporte('tasa-rechazo', 'reporte_tasa_rechazo_');
    }

    public function reporteFormaPago()
    {
        $this->descargarReporte('forma-pago', 'reporte_forma_pago_');
    }

    /**
     * Descarga en CSV cualquiera de los reportes de TIPOS_REPORTE. Reutiliza
     * exactamente la misma consulta que la vista en pantalla (datosXxx()),
     * así que nunca pueden mostrar números distintos entre sí.
     */
    private function descargarReporte(string $tipo, string $prefijoArchivo): void
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }
        try {
            $filtros = $this->filtrosDesdeRequest($_POST);
            $config  = self::TIPOS_REPORTE[$tipo];
            $datos   = $this->{$config['metodo']}($filtros);

            $this->exportarCSV(
                $prefijoArchivo . date('Y-m-d'),
                $config['titulo'],
                $this->descripcionPeriodo($filtros),
                $datos['columnas'],
                $datos['filas']
            );
        } catch (\Exception $e) {
            error_log("Error generando reporte {$tipo}: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al generar el reporte'], 500);
        }
    }

    // ========================================================================
    // VISTA EN PANTALLA (genérica)
    // ========================================================================

    /**
     * Vista en pantalla genérica para cualquiera de los reportes en
     * TIPOS_REPORTE. Solo lectura (GET): el filtro viaja en la query string,
     * igual que el resto de listados administrativos, sin pasar por CSRF.
     *
     * @param string $tipo
     * @return void
     */
    public function verReporte(string $tipo)
    {
        if (!isset(self::TIPOS_REPORTE[$tipo])) {
            \App\Helpers\Redirect::to('/admin/reportes')
                ->withError('Reporte no reconocido')
                ->send();
            return;
        }

        $filtros = $this->filtrosDesdeRequest($_GET);
        $config  = self::TIPOS_REPORTE[$tipo];

        $datos = ['columnas' => [], 'filas' => []];
        $error = null;
        try {
            $datos = $this->{$config['metodo']}($filtros);
        } catch (\Exception $e) {
            error_log("Error viendo reporte {$tipo}: " . $e->getMessage());
            $error = 'No se pudo generar el reporte. Revisa el log del sistema.';
        }

        View::render('admin/reportes/ver', [
            'title'         => $config['titulo'],
            'tipo'          => $tipo,
            'columnas'      => $datos['columnas'],
            'filas'         => $datos['filas'],
            'periodo'       => $this->descripcionPeriodo($filtros),
            'error_reporte' => $error,
        ]);
    }

    /**
     * Vista en pantalla del reporte "Gasto por Unidad Requirente".
     *
     * Tiene su propia vista (no la genérica) porque se agrupa por moneda:
     * cada moneda es su propio bloque con su propio total, en vez de una
     * sola tabla con una columna "Moneda" más.
     *
     * @return void
     */
    public function verGastoUnidadRequirente()
    {
        $filtros = $this->filtrosDesdeRequest($_GET);

        $filas = [];
        $error = null;

        try {
            $filas = $this->consultarGastoUnidadRequirente($filtros);
        } catch (\Exception $e) {
            error_log("Error vista gasto unidad requirente: " . $e->getMessage());
            $error = 'No se pudo generar el reporte. Revisa el log del sistema.';
        }

        // Agrupamos en bloques por moneda. NUNCA se suman GTQ + USD + EUR en una
        // sola cifra: cada moneda tiene su propia tabla y su propio total.
        $porMoneda = [];
        $totalRequisiciones = 0;

        foreach ($filas as $fila) {
            $moneda = $fila['moneda'] !== null && $fila['moneda'] !== ''
                ? $fila['moneda']
                : 'GTQ';

            if (!isset($porMoneda[$moneda])) {
                $porMoneda[$moneda] = [
                    'filas'        => [],
                    'monto_total'  => 0.0,
                    'requisiciones'=> 0,
                    'aprobadas'    => 0,
                    'rechazadas'   => 0,
                    'en_proceso'   => 0,
                    'sin_flujo'    => 0,
                ];
            }

            $porMoneda[$moneda]['filas'][]       = $fila;
            $porMoneda[$moneda]['monto_total']  += (float)($fila['monto_total'] ?? 0);
            $porMoneda[$moneda]['requisiciones']+= (int)($fila['total_requisiciones'] ?? 0);
            $porMoneda[$moneda]['aprobadas']    += (int)($fila['aprobadas'] ?? 0);
            $porMoneda[$moneda]['rechazadas']   += (int)($fila['rechazadas'] ?? 0);
            $porMoneda[$moneda]['en_proceso']   += (int)($fila['en_proceso'] ?? 0);
            $porMoneda[$moneda]['sin_flujo']    += (int)($fila['sin_flujo'] ?? 0);

            $totalRequisiciones += (int)($fila['total_requisiciones'] ?? 0);
        }

        ksort($porMoneda);

        View::render('admin/reportes/gasto_unidad_requirente', [
            'title'               => 'Gasto por Unidad Requirente',
            'fecha_inicio'        => $filtros['fecha_inicio'],
            'fecha_fin'           => $filtros['fecha_fin'],
            'moneda'              => $filtros['moneda'],
            'por_moneda'          => $porMoneda,
            'total_requisiciones' => $totalRequisiciones,
            'error_reporte'       => $error,
        ]);
    }

    public function reporteGastoUnidadRequirente()
    {
        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'error' => 'Token inválido'], 403);
            return;
        }
        try {
            $filtros = $this->filtrosDesdeRequest($_POST);
            $filas   = $this->consultarGastoUnidadRequirente($filtros);

            // Las monedas NO se suman entre sí: cada fila lleva su moneda y el CSV
            // sale ordenado por moneda para que Excel pueda subtotalizar por grupo.
            $columnas = [
                'Unidad Requirente', 'Moneda', 'Total Requisiciones', 'Monto Total',
                'Aprobadas', 'Rechazadas', 'En Proceso', 'Sin Flujo',
            ];
            $datos = array_map(fn($r) => [
                $r['unidad'],
                $r['moneda'],
                $r['total_requisiciones'],
                $r['monto_total'],
                $r['aprobadas'],
                $r['rechazadas'],
                $r['en_proceso'],
                $r['sin_flujo'],
            ], $filas);

            $this->exportarCSV(
                'reporte_gasto_unidad_requirente_' . date('Y-m-d'),
                'Gasto por Unidad Requirente',
                $this->descripcionPeriodo($filtros),
                $columnas, $datos
            );
        } catch (\Exception $e) {
            error_log("Error reporte gasto unidad requirente: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al generar el reporte'], 500);
        }
    }

    /**
     * Consulta base del reporte de gasto por unidad requirente.
     *
     * `requisiciones.unidad_requirente` es varchar(255) y en la práctica guarda
     * el ID del catálogo como texto ('20', '24'...), aunque hay filas históricas
     * que podrían traer el nombre. Por eso la etiqueta se resuelve en cascada:
     *   1. si el valor es numérico, se busca por `unidad_requirente.id`
     *   2. si no, se intenta empatar contra `unidad_requirente.nombre`
     *   3. si nada cuadra, se usa el valor tal cual quedó guardado
     * Se usan subconsultas escalares (no JOIN) para que ninguna fila de
     * requisiciones se pueda duplicar al resolver el nombre.
     *
     * El resultado viene desglosado por moneda porque GTQ/USD/EUR no son sumables.
     * No aplica el filtro de unidad_requirente_id (es la dimensión que agrupa
     * este reporte); sí aplica moneda si se seleccionó una.
     *
     * @param array $filtros
     * @return array
     */
    private function consultarGastoUnidadRequirente(array $filtros): array
    {
        $etiquetaUnidad = "COALESCE(
                (SELECT cat_id.nombre
                   FROM unidad_requirente cat_id
                  WHERE cat_id.id = CASE
                            WHEN TRIM(r.unidad_requirente) REGEXP '^[0-9]+$'
                            THEN CAST(TRIM(r.unidad_requirente) AS UNSIGNED)
                            ELSE NULL
                        END
                  LIMIT 1),
                (SELECT cat_nom.nombre
                   FROM unidad_requirente cat_nom
                  WHERE cat_nom.nombre = TRIM(r.unidad_requirente)
                  LIMIT 1),
                NULLIF(TRIM(r.unidad_requirente), '')
            )";

        $params = [$filtros['fecha_inicio'], $filtros['fecha_fin']];
        $condMoneda = '';
        if (!empty($filtros['moneda'])) {
            $condMoneda = ' AND r.moneda = ?';
            $params[] = $filtros['moneda'];
        }

        // Un solo registro de flujo por requisición (el más reciente), para que
        // el desglose por estado no infle los conteos ni los montos.
        $sql = "SELECT {$etiquetaUnidad} AS unidad,
                       r.moneda AS moneda,
                       COUNT(*) AS total_requisiciones,
                       SUM(r.monto_total) AS monto_total,
                       SUM(CASE WHEN fl.estado = 'autorizado' THEN 1 ELSE 0 END) AS aprobadas,
                       SUM(CASE WHEN fl.estado IN ('rechazado','rechazado_revision','rechazado_autorizacion') THEN 1 ELSE 0 END) AS rechazadas,
                       SUM(CASE WHEN fl.estado IS NOT NULL
                                 AND fl.estado NOT IN ('autorizado','rechazado','rechazado_revision','rechazado_autorizacion')
                                THEN 1 ELSE 0 END) AS en_proceso,
                       SUM(CASE WHEN fl.estado IS NULL THEN 1 ELSE 0 END) AS sin_flujo
                FROM requisiciones r
                LEFT JOIN (
                    SELECT af.requisicion_id, af.estado
                    FROM autorizacion_flujo af
                    INNER JOIN (
                        SELECT requisicion_id, MAX(id) AS max_id
                        FROM autorizacion_flujo
                        GROUP BY requisicion_id
                    ) ult ON ult.max_id = af.id
                ) fl ON fl.requisicion_id = r.id
                WHERE DATE(r.fecha_solicitud) BETWEEN ? AND ?{$condMoneda}
                  AND r.unidad_requirente IS NOT NULL
                  AND TRIM(r.unidad_requirente) <> ''
                GROUP BY unidad, r.moneda
                ORDER BY r.moneda ASC, monto_total DESC";

        return Requisicion::query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    // ========================================================================
    // DATOS POR REPORTE (compartidos entre CSV y vista en pantalla)
    // ========================================================================

    private function datosEstadoRequisiciones(array $filtros): array
    {
        [$cond, $params] = $this->condicionesFiltro($filtros);

        $sql = "SELECT r.numero_requisicion,
                       u.azure_display_name AS solicitante,
                       r.proveedor_nombre, r.monto_total, r.moneda,
                       r.fecha_solicitud, af.estado
                FROM requisiciones r
                LEFT JOIN autorizacion_flujo af ON r.id = af.requisicion_id
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                WHERE {$cond}
                ORDER BY r.fecha_solicitud DESC";

        $filas = Requisicion::query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

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

        return ['columnas' => $columnas, 'filas' => $datos];
    }

    /**
     * OJO: este reporte suma distribucion_gasto.cantidad, NO
     * requisiciones.monto_total, y esta bien asi: mide cuanto gasto se
     * cargo a cada unidad de negocio, que es otra pregunta. Consecuencia
     * esperada: su total puede diferir en centavos del total de los
     * reportes que suman monto_total, porque el reparto por porcentaje no
     * siempre cuadra exacto (3 lineas al 33.33% de Q100 suman Q99.99). No
     * es un error: no lo "corrijas" apuntandolo a monto_total. Ver
     * docs/PRECISION_DECIMAL.md
     */
    private function datosGastoUnidadNegocio(array $filtros): array
    {
        [$cond, $params] = $this->condicionesFiltro($filtros);

        $sql = "SELECT cc.nombre AS unidad_negocio,
                       COUNT(DISTINCT dg.requisicion_id) AS total_requisiciones,
                       SUM(dg.cantidad) AS monto_total
                FROM unidad_de_negocio cc
                INNER JOIN distribucion_gasto dg ON cc.id = dg.unidad_negocio_id
                INNER JOIN requisiciones r ON dg.requisicion_id = r.id
                WHERE {$cond}
                GROUP BY cc.id, cc.nombre
                ORDER BY monto_total DESC";

        $filas = Requisicion::query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $columnas = ['Unidad de Negocio', 'Total Requisiciones', 'Monto Total'];
        $datos = array_map(fn($r) => [
            $r['unidad_negocio'],
            $r['total_requisiciones'],
            $r['monto_total'],
        ], $filas);

        return ['columnas' => $columnas, 'filas' => $datos];
    }

    private function datosTasaRechazo(array $filtros): array
    {
        [$cond, $params] = $this->condicionesFiltro($filtros);

        $sqlResumen = "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN af.estado IN ('rechazado','rechazado_revision','rechazado_autorizacion') THEN 1 ELSE 0 END) AS rechazadas,
            SUM(CASE WHEN af.estado = 'autorizado' THEN 1 ELSE 0 END) AS aprobadas,
            ROUND(SUM(CASE WHEN af.estado IN ('rechazado','rechazado_revision','rechazado_autorizacion') THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) AS tasa_rechazo
        FROM autorizacion_flujo af
        INNER JOIN requisiciones r ON af.requisicion_id = r.id
        WHERE {$cond}";

        $resumen = Requisicion::query($sqlResumen, $params)->fetch(\PDO::FETCH_ASSOC);

        $sqlMotivos = "SELECT COALESCE(af.motivo_rechazo, '(sin motivo)') AS motivo,
                              COUNT(*) AS cantidad
                       FROM autorizacion_flujo af
                       INNER JOIN requisiciones r ON af.requisicion_id = r.id
                       WHERE af.estado IN ('rechazado','rechazado_revision','rechazado_autorizacion')
                         AND {$cond}
                       GROUP BY af.motivo_rechazo
                       ORDER BY cantidad DESC";

        $motivos = Requisicion::query($sqlMotivos, $params)->fetchAll(\PDO::FETCH_ASSOC);

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

        return ['columnas' => $columnas, 'filas' => $datos];
    }

    private function datosFormaPago(array $filtros): array
    {
        [$cond, $params] = $this->condicionesFiltro($filtros);

        // El porcentaje se calcula con una funcion de ventana en vez de una
        // subconsulta correlacionada: una sola pasada por la tabla en vez
        // de repetir el COUNT total por cada fila agrupada.
        $sql = "SELECT r.forma_pago,
                       COUNT(r.id) AS cantidad,
                       SUM(r.monto_total) AS monto_total,
                       ROUND(COUNT(r.id) / SUM(COUNT(r.id)) OVER () * 100, 2) AS porcentaje
                FROM requisiciones r
                WHERE {$cond}
                GROUP BY r.forma_pago
                ORDER BY cantidad DESC";

        $filas = Requisicion::query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $columnas = ['Forma de Pago', 'Cantidad', 'Monto Total', '% del Total'];
        $datos = array_map(fn($r) => [
            $r['forma_pago'] ?? '(sin especificar)',
            $r['cantidad'],
            $r['monto_total'],
            ($r['porcentaje'] ?? 0) . '%',
        ], $filas);

        return ['columnas' => $columnas, 'filas' => $datos];
    }

    // ========================================================================
    // FILTROS COMPARTIDOS
    // ========================================================================

    /**
     * Normaliza los filtros comunes a todos los reportes (fecha, unidad
     * requirente, moneda) desde $_GET o $_POST, con valores por defecto
     * seguros. Centralizar esto evita que cada reporte reinvente su propia
     * validación (y su propio bug) para lo mismo.
     *
     * @param array $origen $_GET o $_POST
     * @return array{fecha_inicio:string,fecha_fin:string,unidad_requirente_id:?int,moneda:?string}
     */
    private function filtrosDesdeRequest(array $origen): array
    {
        [$fechaInicio, $fechaFin] = $this->normalizarRangoFechas(
            $origen['fecha_inicio'] ?? null,
            $origen['fecha_fin'] ?? null
        );

        $unidadRequirenteId = (!empty($origen['unidad_requirente_id']) && is_numeric($origen['unidad_requirente_id']))
            ? (int)$origen['unidad_requirente_id']
            : null;

        $moneda = (!empty($origen['moneda']) && in_array($origen['moneda'], ['GTQ', 'USD', 'EUR'], true))
            ? $origen['moneda']
            : null;

        return [
            'fecha_inicio'         => $fechaInicio,
            'fecha_fin'            => $fechaFin,
            'unidad_requirente_id' => $unidadRequirenteId,
            'moneda'               => $moneda,
        ];
    }

    /**
     * Construye el fragmento WHERE (sin la palabra WHERE) y sus parámetros
     * a partir de los filtros normalizados. Reutilizado por todos los
     * reportes que consultan `requisiciones` bajo el alias indicado.
     *
     * @param array $filtros
     * @param string $alias Alias de la tabla requisiciones en la consulta
     * @return array{0:string,1:array}
     */
    private function condicionesFiltro(array $filtros, string $alias = 'r'): array
    {
        $sql = "DATE({$alias}.fecha_solicitud) BETWEEN ? AND ?";
        $params = [$filtros['fecha_inicio'], $filtros['fecha_fin']];

        if (!empty($filtros['unidad_requirente_id'])) {
            $sql .= " AND TRIM({$alias}.unidad_requirente) = ?";
            $params[] = (string)$filtros['unidad_requirente_id'];
        }

        if (!empty($filtros['moneda'])) {
            $sql .= " AND {$alias}.moneda = ?";
            $params[] = $filtros['moneda'];
        }

        return [$sql, $params];
    }

    /**
     * Texto descriptivo del período + filtros aplicados, usado como
     * subtítulo tanto en el CSV como en la vista en pantalla.
     */
    private function descripcionPeriodo(array $filtros): string
    {
        $desc = "Del {$filtros['fecha_inicio']} al {$filtros['fecha_fin']}";

        if (!empty($filtros['moneda'])) {
            $desc .= " · Moneda: {$filtros['moneda']}";
        }

        if (!empty($filtros['unidad_requirente_id'])) {
            $unidad = UnidadRequirente::find($filtros['unidad_requirente_id']);
            if ($unidad) {
                $desc .= " · Unidad: " . ($unidad->nombre ?? '');
            }
        }

        return $desc;
    }

    /**
     * Valida y normaliza un rango de fechas Y-m-d.
     *
     * Si vienen vacías o mal formadas usa el mes en curso; si vienen invertidas
     * las intercambia en vez de devolver un reporte vacío.
     *
     * @param mixed $inicio
     * @param mixed $fin
     * @return array{0:string,1:string}
     */
    private function normalizarRangoFechas($inicio, $fin): array
    {
        $limpiar = function ($valor, $porDefecto) {
            $valor = is_string($valor) ? trim($valor) : '';
            $fecha = \DateTime::createFromFormat('Y-m-d', $valor);

            if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
                return $porDefecto;
            }

            return $valor;
        };

        $fechaInicio = $limpiar($inicio, date('Y-m-01'));
        $fechaFin    = $limpiar($fin, date('Y-m-t'));

        if ($fechaInicio > $fechaFin) {
            [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
        }

        return [$fechaInicio, $fechaFin];
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
}
