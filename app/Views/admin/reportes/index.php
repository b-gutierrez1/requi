<?php
use App\Helpers\View;

$unidades_requirentes = $unidades_requirentes ?? [];

View::startSection('content');
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0">
                <i class="fas fa-chart-bar me-2"></i>Reportes
            </h1>
            <p class="text-muted mb-0">Consulta en pantalla o descarga en CSV, filtrando por período, unidad requirente y moneda</p>
        </div>
    </div>

    <!-- Filtros compartidos -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Unidad Requirente</label>
                    <select class="form-select" id="unidad_requirente_id">
                        <option value="">Todas</option>
                        <?php foreach ($unidades_requirentes as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= View::e($u['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Moneda</label>
                    <select class="form-select" id="moneda">
                        <option value="">Todas</option>
                        <option value="GTQ">GTQ</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                    </select>
                </div>
            </div>
            <div class="text-muted small mt-2">
                <i class="fas fa-info-circle me-1"></i>
                Los filtros aplican tanto a las gráficas como a "Ver en pantalla" y "Descargar CSV" de cada reporte.
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="fas fa-chart-line me-2"></i>Monto por mes (últimos 6 meses)</h6>
                    <canvas id="graficaMensual" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><i class="fas fa-chart-pie me-2"></i>Distribución por estado (rango seleccionado)</h6>
                    <canvas id="graficaEstados" height="110"></canvas>
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
                    <div class="d-grid gap-2 d-md-flex">
                        <button class="btn btn-primary flex-fill" onclick="verEnPantalla('estado-requisiciones')">
                            <i class="fas fa-eye me-2"></i>Ver en pantalla
                        </button>
                        <button class="btn btn-outline-primary flex-fill" onclick="descargar('estado-requisiciones')">
                            <i class="fas fa-download me-2"></i>CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Gasto por Unidad de Negocio -->
        <div class="col-md-6">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Gasto por Unidad de Negocio</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Resumen del gasto consolidado por unidad de negocio según la distribución registrada en cada requisición.</p>
                    <ul class="list-unstyled mb-3">
                        <li><i class="fas fa-check text-success me-2"></i>Centro de costo</li>
                        <li><i class="fas fa-check text-success me-2"></i>Número de requisiciones</li>
                        <li><i class="fas fa-check text-success me-2"></i>Monto total acumulado</li>
                    </ul>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="d-grid gap-2 d-md-flex">
                        <button class="btn btn-success flex-fill" onclick="verEnPantalla('gasto-unidad-negocio')">
                            <i class="fas fa-eye me-2"></i>Ver en pantalla
                        </button>
                        <button class="btn btn-outline-success flex-fill" onclick="descargar('gasto-unidad-negocio')">
                            <i class="fas fa-download me-2"></i>CSV
                        </button>
                    </div>
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
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="<?= url('/admin/reportes/gasto-unidad-requirente/ver') ?>"
                           class="btn btn-info flex-fill" id="btnVerGastoUnidadRequirente">
                            <i class="fas fa-eye me-2"></i>Ver en pantalla
                        </a>
                        <button class="btn btn-outline-info flex-fill" onclick="descargar('gasto-unidad-requirente')">
                            <i class="fas fa-download me-2"></i>CSV
                        </button>
                    </div>
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
                    <div class="d-grid gap-2 d-md-flex">
                        <button class="btn btn-danger flex-fill" onclick="verEnPantalla('tasa-rechazo')">
                            <i class="fas fa-eye me-2"></i>Ver en pantalla
                        </button>
                        <button class="btn btn-outline-danger flex-fill" onclick="descargar('tasa-rechazo')">
                            <i class="fas fa-download me-2"></i>CSV
                        </button>
                    </div>
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
                    <div class="d-grid gap-2 d-md-flex">
                        <button class="btn btn-warning flex-fill" onclick="verEnPantalla('forma-pago')">
                            <i class="fas fa-eye me-2"></i>Ver en pantalla
                        </button>
                        <button class="btn btn-outline-warning flex-fill" onclick="descargar('forma-pago')">
                            <i class="fas fa-download me-2"></i>CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
window.REPORT_URLS = {
    'estado-requisiciones':    '<?= url('/admin/reportes/estado-requisiciones') ?>',
    'gasto-unidad-negocio':      '<?= url('/admin/reportes/gasto-unidad-negocio') ?>',
    'gasto-unidad-requirente': '<?= url('/admin/reportes/gasto-unidad-requirente') ?>',
    'tasa-rechazo':            '<?= url('/admin/reportes/tasa-rechazo') ?>',
    'forma-pago':              '<?= url('/admin/reportes/forma-pago') ?>',
};
window.REPORT_VER_BASE = '<?= url('/admin/reportes/ver') ?>';

// Lee los filtros compartidos (fecha, unidad, moneda) tal como estan en pantalla.
function leerFiltrosReportes() {
    return {
        fecha_inicio: document.getElementById('fecha_inicio')?.value || '',
        fecha_fin: document.getElementById('fecha_fin')?.value || '',
        unidad_requirente_id: document.getElementById('unidad_requirente_id')?.value || '',
        moneda: document.getElementById('moneda')?.value || '',
    };
}

// Abre la vista en pantalla generica (ver.php) para un tipo de reporte,
// arrastrando los filtros compartidos en la query string.
function verEnPantalla(tipo) {
    var f = leerFiltrosReportes();
    if (!f.fecha_inicio || !f.fecha_fin) {
        alert('Selecciona un rango de fechas antes de ver el reporte.');
        return;
    }
    var params = new URLSearchParams();
    Object.keys(f).forEach(function (k) { if (f[k]) { params.set(k, f[k]); } });
    window.location.href = window.REPORT_VER_BASE + '/' + tipo + '?' + params.toString();
}

// El boton "Ver en pantalla" de Gasto por Unidad Requirente arrastra los
// mismos filtros compartidos (tiene su propia vista, no la generica).
(function () {
    var base = '<?= url('/admin/reportes/gasto-unidad-requirente/ver') ?>';
    var btn  = document.getElementById('btnVerGastoUnidadRequirente');
    if (!btn) { return; }

    btn.addEventListener('click', function (e) {
        var f = leerFiltrosReportes();
        if (!f.fecha_inicio || !f.fecha_fin) { return; }

        e.preventDefault();
        var params = new URLSearchParams();
        params.set('fecha_inicio', f.fecha_inicio);
        params.set('fecha_fin', f.fecha_fin);
        if (f.moneda) { params.set('moneda', f.moneda); }
        window.location.href = base + '?' + params.toString();
    });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    var urlResumen = '<?= url('/admin/reportes/api/resumen-grafico') ?>';
    var coloresMoneda = { GTQ: '#2563eb', USD: '#16a34a', EUR: '#9333ea' };
    var graficaMensual = null;
    var graficaEstados = null;

    function cargarResumen() {
        var f = leerFiltrosReportes();
        var params = new URLSearchParams();
        Object.keys(f).forEach(function (k) { if (f[k]) { params.set(k, f[k]); } });

        fetch(urlResumen + (params.toString() ? '?' + params.toString() : ''))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { return; }

                var ctxMensual = document.getElementById('graficaMensual');
                if (ctxMensual && typeof Chart !== 'undefined') {
                    var monedas = Object.keys(data.mensual.series);
                    var apilar = monedas.length > 1;
                    var datasets = monedas.map(function (moneda) {
                        return {
                            label: moneda,
                            data: data.mensual.series[moneda],
                            backgroundColor: coloresMoneda[moneda] || '#6b7280'
                        };
                    });
                    if (graficaMensual) { graficaMensual.destroy(); }
                    graficaMensual = new Chart(ctxMensual, {
                        type: 'bar',
                        data: { labels: data.mensual.labels, datasets: datasets },
                        options: {
                            responsive: true,
                            scales: {
                                x: { stacked: apilar },
                                y: { stacked: apilar, beginAtZero: true }
                            },
                            plugins: { legend: { display: apilar } }
                        }
                    });
                }

                var ctxEstados = document.getElementById('graficaEstados');
                if (ctxEstados && typeof Chart !== 'undefined') {
                    if (graficaEstados) { graficaEstados.destroy(); }
                    graficaEstados = new Chart(ctxEstados, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pendientes', 'Autorizadas', 'Rechazadas'],
                            datasets: [{
                                data: [data.estados.pendientes, data.estados.autorizadas, data.estados.rechazadas],
                                backgroundColor: ['#f59e0b', '#16a34a', '#dc2626']
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { position: 'bottom' } }
                        }
                    });
                }
            })
            .catch(function (err) { console.error('Error cargando graficas de reportes:', err); });
    }

    document.addEventListener('DOMContentLoaded', function () {
        cargarResumen();
        // Las graficas respetan los filtros compartidos: se recalculan si
        // el usuario cambia fecha, unidad o moneda.
        ['fecha_inicio', 'fecha_fin', 'unidad_requirente_id', 'moneda'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) { el.addEventListener('change', cargarResumen); }
        });
    });
})();
</script>
<script src="<?php echo \App\Helpers\View::asset('js/admin/reportes-index.js'); ?>"></script>

<?php View::endSection(); ?>
