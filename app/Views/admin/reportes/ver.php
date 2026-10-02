<?php
/**
 * Vista en pantalla genérica de reportes (todos excepto "Gasto por Unidad
 * Requirente", que tiene su propia vista por el agrupamiento en bloques
 * por moneda).
 *
 * Variables esperadas:
 *   $title           string
 *   $tipo            string  slug del reporte (para el botón de descarga CSV)
 *   $columnas        array   encabezados de la tabla
 *   $filas           array   filas de datos (cada una un array indexado igual que $columnas)
 *   $periodo         string  descripción del período/filtros aplicados
 *   $error_reporte   string|null
 */

use App\Helpers\View;

$columnas      = $columnas ?? [];
$filas         = $filas ?? [];
$periodo       = $periodo ?? '';
$error_reporte = $error_reporte ?? null;
$tipo          = $tipo ?? '';

$urlsDescarga = [
    'estado-requisiciones' => '/admin/reportes/estado-requisiciones',
    'gasto-unidad-negocio' => '/admin/reportes/gasto-unidad-negocio',
    'tasa-rechazo'         => '/admin/reportes/tasa-rechazo',
    'forma-pago'           => '/admin/reportes/forma-pago',
];
$urlDescarga = $urlsDescarga[$tipo] ?? null;

View::startSection('content');
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2"><i class="fas fa-table me-2"></i><?= View::e($title ?? 'Reporte') ?></h1>
            <p class="text-muted mb-0"><?= View::e($periodo) ?></p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/admin/reportes') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver a Reportes
            </a>
        </div>
    </div>

    <?php if ($error_reporte): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i><?= View::e($error_reporte) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i><?= count($filas) ?> fila<?= count($filas) != 1 ? 's' : '' ?></span>
            <?php if ($urlDescarga && !empty($filas)): ?>
            <form method="POST" action="<?= url($urlDescarga) ?>" class="mb-0">
                <input type="hidden" name="_token" value="<?= View::e(csrf_token()) ?>">
                <?php foreach (['fecha_inicio', 'fecha_fin', 'unidad_requirente_id', 'moneda'] as $campo): ?>
                    <?php if (!empty($_GET[$campo])): ?>
                        <input type="hidden" name="<?= $campo ?>" value="<?= View::e($_GET[$campo]) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-download me-1"></i>Descargar CSV
                </button>
            </form>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($filas)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-folder-open fa-3x mb-3"></i>
                    <p class="mb-0">No hay datos para este período.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <?php foreach ($columnas as $col): ?>
                                    <th><?= View::e($col) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filas as $fila): ?>
                                <?php if (empty($fila)): ?>
                                    <tr><td colspan="<?= max(1, count($columnas)) ?>">&nbsp;</td></tr>
                                <?php else: ?>
                                    <tr>
                                        <?php foreach ($fila as $celda): ?>
                                            <td><?= View::e((string)$celda) ?></td>
                                        <?php endforeach; ?>
                                        <?php for ($i = count($fila); $i < count($columnas); $i++): ?>
                                            <td></td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php View::endSection(); ?>
