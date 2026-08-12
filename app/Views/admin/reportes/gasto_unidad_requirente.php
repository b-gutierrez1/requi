<?php
/**
 * Reporte en pantalla: Gasto por Unidad Requirente.
 *
 * La unidad requirente es QUIEN PIDE la requisición (requisiciones.unidad_requirente).
 * No confundir con la unidad de negocio, que es a quién se le carga el gasto.
 *
 * Variables esperadas:
 *   $fecha_inicio, $fecha_fin  string Y-m-d
 *   $por_moneda                array  [MONEDA => ['filas'=>[], 'monto_total'=>, ...]]
 *   $total_requisiciones       int
 *   $error_reporte             string|null
 */

use App\Helpers\View;

$por_moneda          = $por_moneda ?? [];
$total_requisiciones = (int)($total_requisiciones ?? 0);
$error_reporte       = $error_reporte ?? null;
$fecha_inicio        = $fecha_inicio ?? date('Y-m-01');
$fecha_fin           = $fecha_fin ?? date('Y-m-t');

$nombresMoneda = [
    'GTQ' => 'Quetzales',
    'USD' => 'Dólares',
    'EUR' => 'Euros',
];

View::startSection('content');
?>

<div class="container-fluid py-4">

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-sitemap me-2"></i>Gasto por Unidad Requirente
            </h1>
            <p class="text-muted mb-0">
                Cuánto ha solicitado cada unidad requirente en el período seleccionado.
                <span class="badge bg-info text-white ms-2">
                    <?= $total_requisiciones ?> requisicion<?= $total_requisiciones != 1 ? 'es' : '' ?>
                </span>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/admin/reportes') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Reportes
            </a>
        </div>
    </div>

    <!-- Filtro de fechas + descarga CSV -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('/admin/reportes/gasto-unidad-requirente/ver') ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="fecha_inicio">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                               value="<?= View::e($fecha_inicio) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="fecha_fin">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                               value="<?= View::e($fecha_fin) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i>Aplicar filtro
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" form="formDescargarCsv" class="btn btn-outline-info w-100">
                            <i class="fas fa-download me-1"></i>Descargar CSV
                        </button>
                    </div>
                </div>
            </form>

            <!-- Reutiliza el endpoint CSV que ya existía (POST + CSRF) -->
            <form id="formDescargarCsv" method="POST"
                  action="<?= url('/admin/reportes/gasto-unidad-requirente') ?>" class="d-none">
                <input type="hidden" name="_token" value="<?= View::e(csrf_token()) ?>">
                <input type="hidden" name="fecha_inicio" value="<?= View::e($fecha_inicio) ?>">
                <input type="hidden" name="fecha_fin" value="<?= View::e($fecha_fin) ?>">
            </form>

            <div class="text-muted small mt-3">
                <i class="fas fa-info-circle me-1"></i>
                Período del <strong><?= View::e($fecha_inicio) ?></strong>
                al <strong><?= View::e($fecha_fin) ?></strong>
                (por fecha de solicitud).
            </div>
        </div>
    </div>

    <?php if ($error_reporte): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?= View::e($error_reporte) ?>
        </div>
    <?php endif; ?>

    <!-- Nota de monedas -->
    <div class="alert alert-secondary d-flex align-items-start">
        <i class="fas fa-coins me-2 mt-1"></i>
        <div>
            <strong>Sobre las monedas:</strong>
            las requisiciones pueden estar en GTQ, USD o EUR y
            <strong>no se suman entre sí</strong> (no hay tipo de cambio en el sistema).
            Por eso el reporte se presenta en un bloque independiente por moneda,
            cada uno con su propio total.
        </div>
    </div>

    <?php if (empty($por_moneda) && !$error_reporte): ?>

        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <h5 class="mb-2">Sin requisiciones en este período</h5>
                <p class="text-muted mb-0">
                    No se encontraron requisiciones con unidad requirente registrada
                    entre <?= View::e($fecha_inicio) ?> y <?= View::e($fecha_fin) ?>.
                    Prueba con otro rango de fechas.
                </p>
            </div>
        </div>

    <?php else: ?>

        <?php foreach ($por_moneda as $moneda => $bloque): ?>
            <?php
                $filas       = $bloque['filas'] ?? [];
                $montoBloque = (float)($bloque['monto_total'] ?? 0);
                $reqsBloque  = (int)($bloque['requisiciones'] ?? 0);
                $etiqueta    = $nombresMoneda[$moneda] ?? $moneda;
            ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        Montos en <?= View::e($moneda) ?>
                        <small class="text-muted fw-normal">(<?= View::e($etiqueta) ?>)</small>
                    </h5>
                    <span class="badge bg-primary">
                        <?= $reqsBloque ?> requisicion<?= $reqsBloque != 1 ? 'es' : '' ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Unidad Requirente</th>
                                    <th class="text-center" width="120">Requisiciones</th>
                                    <th class="text-end" width="180">Monto Total</th>
                                    <th class="text-center" width="110">Aprobadas</th>
                                    <th class="text-center" width="110">Rechazadas</th>
                                    <th class="text-center" width="110">En Proceso</th>
                                    <th class="text-end" width="110">% del Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filas as $fila): ?>
                                    <?php
                                        $unidad    = $fila['unidad'] ?? '';
                                        $sinNombre = ($unidad !== '' && ctype_digit((string)$unidad));
                                        $monto     = (float)($fila['monto_total'] ?? 0);
                                        // Guardas contra división entre cero
                                        $porcentaje = $montoBloque > 0
                                            ? round($monto / $montoBloque * 100, 2)
                                            : 0.0;
                                        $sinFlujo = (int)($fila['sin_flujo'] ?? 0);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= View::e($unidad !== '' ? $unidad : '(sin unidad)') ?></strong>
                                            <?php if ($sinNombre): ?>
                                                <br>
                                                <span class="badge bg-warning text-dark"
                                                      title="El valor guardado es un ID que no existe en el catálogo de unidades requirentes">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>ID sin catálogo
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($sinFlujo > 0): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <?= $sinFlujo ?> sin flujo de autorización
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?= (int)($fila['total_requisiciones'] ?? 0) ?>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <?= View::e(View::money($monto, $moneda)) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?= (int)($fila['aprobadas'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?= (int)($fila['rechazadas'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark"><?= (int)($fila['en_proceso'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-end text-muted">
                                            <?= number_format($porcentaje, 2) ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($filas)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Sin datos para esta moneda.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>Total <?= View::e($moneda) ?></td>
                                    <td class="text-center"><?= $reqsBloque ?></td>
                                    <td class="text-end"><?= View::e(View::money($montoBloque, $moneda)) ?></td>
                                    <td class="text-center"><?= (int)($bloque['aprobadas'] ?? 0) ?></td>
                                    <td class="text-center"><?= (int)($bloque['rechazadas'] ?? 0) ?></td>
                                    <td class="text-center"><?= (int)($bloque['en_proceso'] ?? 0) ?></td>
                                    <td class="text-end"><?= $montoBloque > 0 ? '100.00%' : '0.00%' ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<?php View::endSection(); ?>
