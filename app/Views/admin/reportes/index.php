<?php
use App\Helpers\View;

View::startSection('content');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                Reportes Administrativos
            </h1>
            <p class="text-muted mb-0">Análisis y estadísticas del sistema</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary" onclick="generarReporte('usuarios')">
                    <i class="fas fa-users me-2"></i>
                    Reporte Usuarios
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="generarReporte('requisiciones')">
                    <i class="fas fa-file-alt me-2"></i>
                    Reporte Requisiciones
                </button>
            </div>
        </div>
    </div>

    <!-- Reportes Disponibles -->
    <div class="row">
        <!-- Reporte de Usuarios -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Reporte de Usuarios
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Análisis completo de usuarios del sistema</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Usuarios activos vs inactivos</li>
                        <li><i class="fas fa-check text-success me-2"></i>Distribución por roles</li>
                        <li><i class="fas fa-check text-success me-2"></i>Actividad por período</li>
                        <li><i class="fas fa-check text-success me-2"></i>Último acceso</li>
                    </ul>
                    <div class="mt-3">
                        <button class="btn btn-primary" onclick="generarReporte('usuarios')">
                            <i class="fas fa-download me-2"></i>
                            Generar Reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reporte de Requisiciones -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Reporte de Requisiciones
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Estadísticas y análisis de requisiciones</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Volumen por período</li>
                        <li><i class="fas fa-check text-success me-2"></i>Estados de requisiciones</li>
                        <li><i class="fas fa-check text-success me-2"></i>Monto total por centro de costo</li>
                        <li><i class="fas fa-check text-success me-2"></i>Tiempo promedio de autorización</li>
                    </ul>
                    <div class="mt-3">
                        <button class="btn btn-info" onclick="generarReporte('requisiciones')">
                            <i class="fas fa-download me-2"></i>
                            Generar Reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reporte de Autorizaciones -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-user-check me-2"></i>
                        Reporte de Autorizaciones
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Análisis del flujo de autorizaciones</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Autorizadores por centro de costo</li>
                        <li><i class="fas fa-check text-success me-2"></i>Tiempo de respuesta</li>
                        <li><i class="fas fa-check text-success me-2"></i>Requisiciones pendientes</li>
                        <li><i class="fas fa-check text-success me-2"></i>Historial de autorizaciones</li>
                    </ul>
                    <div class="mt-3">
                        <button class="btn btn-warning" onclick="generarReporte('autorizaciones')">
                            <i class="fas fa-download me-2"></i>
                            Generar Reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reporte Financiero -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-dollar-sign me-2"></i>
                        Reporte Financiero
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Análisis financiero y presupuestario</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i>Gasto por centro de costo</li>
                        <li><i class="fas fa-check text-success me-2"></i>Proyecciones presupuestarias</li>
                        <li><i class="fas fa-check text-success me-2"></i>Comparativo mensual</li>
                        <li><i class="fas fa-check text-success me-2"></i>Análisis de tendencias</li>
                    </ul>
                    <div class="mt-3">
                        <button class="btn btn-success" onclick="generarReporte('financiero')">
                            <i class="fas fa-download me-2"></i>
                            Generar Reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuración de Reportes -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Configuración de Reportes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                        </div>
                        <div class="col-md-4">
                            <label for="formato" class="form-label">Formato</label>
                            <select class="form-select" id="formato" name="formato">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="centro_costo" class="form-label">Centro de Costo (Opcional)</label>
                            <select class="form-select" id="centro_costo" name="centro_costo">
                                <option value="">Todos los centros</option>
                                <!-- Aquí se cargarían los centros de costo dinámicamente -->
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary" onclick="aplicarConfiguracion()">
                                <i class="fas fa-filter me-2"></i>
                                Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Reportes -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historial de Reportes Generados
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo de Reporte</th>
                                    <th>Período</th>
                                    <th>Formato</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        No hay reportes generados aún
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function generarReporte(tipo) {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const formato = document.getElementById('formato').value;
    const centroCosto = document.getElementById('centro_costo').value;
    
    // Validar fechas
    if (!fechaInicio || !fechaFin) {
        showAlert('Por favor selecciona un rango de fechas', 'warning');
        return;
    }
    
    if (new Date(fechaInicio) > new Date(fechaFin)) {
        showAlert('La fecha de inicio debe ser anterior a la fecha de fin', 'warning');
        return;
    }
    
    // Mostrar loading
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando...';
    btn.disabled = true;
    
    // Obtener token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                     document.querySelector('input[name="_token"]')?.value;
    
    // Preparar datos
    const formData = new FormData();
    formData.append('fecha_inicio', fechaInicio);
    formData.append('fecha_fin', fechaFin);
    formData.append('formato', formato);
    if (centroCosto) {
        formData.append('centro_costo', centroCosto);
    }
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }
    
    // Determinar URL según el tipo
    let url = '';
    switch(tipo) {
        case 'usuarios':
            url = '/admin/reportes/usuarios';
            break;
        case 'requisiciones':
            url = '/admin/reportes/requisiciones';
            break;
        case 'autorizaciones':
            url = '/admin/reportes/autorizaciones';
            break;
        case 'financiero':
            url = '/admin/reportes/financiero';
            break;
        default:
            showAlert('Tipo de reporte no válido', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
            return;
    }
    
    // Hacer llamada AJAX
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.ok) {
            // Si es un archivo CSV, descargarlo
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('text/csv')) {
                return response.blob().then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `reporte_${tipo}_${new Date().toISOString().slice(0,10)}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    showAlert('Reporte descargado exitosamente', 'success');
                });
            } else {
                return response.json().then(data => {
                    if (data.success) {
                        showAlert('Reporte generado exitosamente', 'success');
                    } else {
                        showAlert(data.message || 'Error al generar el reporte', 'error');
                    }
                });
            }
        } else {
            return response.json().then(data => {
                showAlert(data.message || 'Error al generar el reporte', 'error');
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error de conexión al generar el reporte', 'error');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function aplicarConfiguracion() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const centroCosto = document.getElementById('centro_costo').value;
    
    if (!fechaInicio || !fechaFin) {
        showAlert('Por favor selecciona un rango de fechas', 'warning');
        return;
    }
    
    if (new Date(fechaInicio) > new Date(fechaFin)) {
        showAlert('La fecha de inicio debe ser anterior a la fecha de fin', 'warning');
        return;
    }
    
    showAlert('Configuración aplicada correctamente', 'success');
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

// Establecer fechas por defecto (último mes)
document.addEventListener('DOMContentLoaded', function() {
    const hoy = new Date();
    const haceUnMes = new Date(hoy.getFullYear(), hoy.getMonth() - 1, hoy.getDate());
    
    document.getElementById('fecha_inicio').value = haceUnMes.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = hoy.toISOString().split('T')[0];
});
</script>

<?php View::endSection(); ?>
