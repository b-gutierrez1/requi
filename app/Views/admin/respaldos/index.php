<?php
use App\Helpers\View;

View::startSection('content');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-user-shield me-2"></i>
                Respaldos de Autorizadores
            </h1>
            <p class="text-muted mb-0">Gestión de autorizadores de respaldo</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRespaldo">
                <i class="fas fa-plus me-2"></i>
                Nuevo Respaldo
            </button>
        </div>
    </div>

    <!-- Lista de Respaldos -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Centro de Costo</th>
                            <th>Autorizador Respaldo</th>
                            <th>Período</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($respaldos)): ?>
                            <?php foreach ($respaldos as $respaldo): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($respaldo->centro_costo_nombre ?? ''); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($respaldo->centro_costo_codigo ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($respaldo->autorizador_respaldo_nombre ?? ''); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($respaldo->autorizador_respaldo_email ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <strong>Inicio:</strong> <?php echo date('d/m/Y', strtotime($respaldo->fecha_inicio)); ?><br>
                                        <strong>Fin:</strong> <?php echo date('d/m/Y', strtotime($respaldo->fecha_fin)); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($respaldo->motivo ?? ''); ?></td>
                                    <td>
                                        <?php if ($respaldo->activo): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarRespaldo(<?php echo $respaldo->id; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarRespaldo(<?php echo $respaldo->id; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No hay respaldos registrados
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nuevo/Editar Respaldo -->
<div class="modal fade" id="modalRespaldo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-shield me-2"></i>
                    Nuevo Autorizador de Respaldo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRespaldo" method="POST" action="/admin/respaldos">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="centro_costo_id" class="form-label">Centro de Costo *</label>
                            <select class="form-select" id="centro_costo_id" name="centro_costo_id" required>
                                <option value="">Seleccionar centro de costo</option>
                                <?php if (!empty($centros)): ?>
                                    <?php foreach ($centros as $centro): ?>
                                        <option value="<?php echo $centro->id; ?>">
                                            <?php echo htmlspecialchars($centro->nombre); ?> - <?php echo htmlspecialchars($centro->codigo); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre del Autorizador *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_inicio" class="form-label">Fecha Inicio *</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_fin" class="form-label">Fecha Fin *</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="motivo" class="form-label">Motivo del Respaldo</label>
                            <input type="text" class="form-control" id="motivo" name="motivo" placeholder="Ej: Vacaciones, Permiso médico">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Respaldo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarRespaldo(id) {
    // Aquí se implementaría la lógica para editar un respaldo
    console.log('Editar respaldo:', id);
}

function eliminarRespaldo(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este respaldo?')) {
        // Aquí se implementaría la lógica para eliminar un respaldo
        console.log('Eliminar respaldo:', id);
    }
}

// Validación del formulario
document.getElementById('formRespaldo').addEventListener('submit', function(e) {
    const fechaInicio = new Date(document.getElementById('fecha_inicio').value);
    const fechaFin = new Date(document.getElementById('fecha_fin').value);
    
    if (fechaInicio >= fechaFin) {
        e.preventDefault();
        alert('La fecha de fin debe ser posterior a la fecha de inicio');
        return;
    }
    
    // Establecer fechas por defecto si no están configuradas
    const hoy = new Date();
    if (!document.getElementById('fecha_inicio').value) {
        document.getElementById('fecha_inicio').value = hoy.toISOString().split('T')[0];
    }
});

// Establecer fecha mínima para fecha_inicio como hoy
document.getElementById('fecha_inicio').setAttribute('min', new Date().toISOString().split('T')[0]);
document.getElementById('fecha_fin').setAttribute('min', new Date().toISOString().split('T')[0]);
</script>

<?php View::endSection(); ?>
