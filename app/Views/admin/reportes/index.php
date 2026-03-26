<?php
use App\Helpers\View;

View::startSection('content');
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0">
                <i class="fas fa-chart-bar me-2"></i>Reportes
            </h1>
            <p class="text-muted mb-0">Descarga reportes en CSV según el período seleccionado</p>
        </div>
    </div>

    <!-- Filtro de fechas compartido -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin">
                </div>
                <div class="col-md-4 text-muted small pt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Selecciona el rango antes de descargar cualquier reporte.
                </div>
            </div>
        </div>
    </div>

    <!-- Reportes -->
    <div class="row g-4">

        <!-- 1. Estado de Requisiciones -->
        <div class="col-md-6">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Estado de Requisiciones</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Listado de todas las requisiciones del período con su estado actual en el flujo de autorización.</p>
                    <ul class="list-unstyled mb-3">
                        <li><i class="fas fa-check text-success me-2"></i>Número de requisición y solicitante</li>
                        <li><i class="fas fa-check text-success me-2"></i>Proveedor, monto y moneda</li>
                        <li><i class="fas fa-check text-success me-2"></i>Estado en flujo (autorizado, pendiente, rechazado)</li>
                    </ul>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-primary w-100" onclick="descargar('estado-requisiciones')">
                        <i class="fas fa-download me-2"></i>Descargar CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Gasto por Centro de Costo -->
        <div class="col-md-6">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Gasto por Centro de Costo</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Resumen del gasto consolidado por centro de costo según la distribución registrada en cada requisición.</p>
                    <ul class="list-unstyled mb-3">
                        <li><i class="fas fa-check text-success me-2"></i>Centro de costo</li>
                        <li><i class="fas fa-check text-success me-2"></i>Número de requisiciones</li>
                        <li><i class="fas fa-check text-success me-2"></i>Monto total acumulado</li>
                    </ul>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-success w-100" onclick="descargar('gasto-centro-costo')">
                        <i class="fas fa-download me-2"></i>Descargar CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Gasto por Unidad Requirente -->
        <div class="col-md-6">
            <div class="card h-100 border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i>Gasto por Unidad Requirente</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Cuánto ha solicitado cada unidad/departamento en el período seleccionado.</p>
                    <ul class="list-unstyled mb-3">
                        <li><i class="fas fa-check text-success me-2"></i>Unidad requirente</li>
                        <li><i class="fas fa-check text-success me-2"></i>Número de requisiciones</li>
                        <li><i class="fas fa-check text-success me-2"></i>Monto total solicitado</li>
                    </ul>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-info w-100" onclick="descargar('gasto-unidad-requirente')">
                        <i class="fas fa-download me-2"></i>Descargar CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. Tasa de Rechazo -->
        <div class="col-md-6">
            <div class="card h-100 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>Tasa de Rechazo</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Porcentaje de requisiciones rechazadas y desglose por motivo de rechazo.</p>
                    <ul class="list-unstyled mb-3">
                        <li><i class="fas fa-check text-success me-2"></i>Total aprobadas vs rechazadas</li>
                        <li><i class="fas fa-check text-success me-2"></i>Tasa de rechazo (%)</li>
                        <li><i class="fas fa-check text-success me-2"></i>Ranking de motivos de rechazo</li>
                    </ul>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-danger w-100" onclick="descargar('tasa-rechazo')">
                        <i class="fas fa-download me-2"></i>Descargar CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- 5. Distribución por Forma de Pago -->
        <div class="col-md-6">
            <div class="card h-100 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Distribución por Forma de Pago</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Cuántas requisiciones y qué monto corresponde a cada forma de pago registrada.</p>
                    <ul class="list-unstyled mb-3">
                        <li><i class="fas fa-check text-success me-2"></i>Forma de pago</li>
                        <li><i class="fas fa-check text-success me-2"></i>Cantidad y monto total</li>
                        <li><i class="fas fa-check text-success me-2"></i>Porcentaje sobre el total</li>
                    </ul>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-warning w-100" onclick="descargar('forma-pago')">
                        <i class="fas fa-download me-2"></i>Descargar CSV
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
window.REPORT_URLS = {
    'estado-requisiciones':    '<?= url('/admin/reportes/estado-requisiciones') ?>',
    'gasto-centro-costo':      '<?= url('/admin/reportes/gasto-centro-costo') ?>',
    'gasto-unidad-requirente': '<?= url('/admin/reportes/gasto-unidad-requirente') ?>',
    'tasa-rechazo':            '<?= url('/admin/reportes/tasa-rechazo') ?>',
    'forma-pago':              '<?= url('/admin/reportes/forma-pago') ?>',
};
</script>
<script src="<?php echo \App\Helpers\View::asset('js/admin/reportes-index.js'); ?>"></script>

<?php View::endSection(); ?>
