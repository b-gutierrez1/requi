<?php
use App\Helpers\View;

View::startSection('content');
?>

<div class="container-fluid">
    <!-- Page Header con navegación sticky -->
    <div class="sticky-top bg-white border-bottom mb-4" style="z-index: 999; padding: 1rem 0; position: sticky; top: 0;">
        <div class="row">
            <div class="col-md-8">
                <h1 class="h3 mb-0">
                    <i class="fas fa-list-alt me-2"></i>
                    Gestión de Catálogos
                </h1>
                <p class="text-muted mb-0">Administración de catálogos del sistema</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group" role="group">
                    <a href="<?= url('/admin/catalogos?tipo=cuentas') ?>" class="btn <?php echo $catalogo === 'cuentas' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="fas fa-calculator me-2"></i>
                        Cuentas Contables
                    </a>
                    <a href="<?= url('/admin/catalogos?tipo=centros') ?>" class="btn <?php echo $catalogo === 'centros' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="fas fa-building me-2"></i>
                        Centros de Costo
                    </a>
                    <a href="<?= url('/admin/relaciones') ?>" class="btn btn-outline-info" title="Ver relaciones entre Centro de Costo y Unidad de Negocio">
                        <i class="fas fa-project-diagram me-2"></i>
                        Relaciones
                    </a>
                </div>
            </div>
        </div>
    </div>


    <!-- Contenido de Catálogos -->
    <div>
        <!-- Cuentas Contables -->
        <div class="<?php echo $catalogo === 'cuentas' ? '' : 'd-none'; ?>">
            <div class="card">
                <div class="card-header" style="position: relative; z-index: 1020; background: white;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="transform: none !important;">
                            <i class="fas fa-calculator me-2"></i>
                            Cuentas Contables
                        </h5>
                        <div style="position: relative; z-index: 1025;">
                            <button type="button" class="btn btn-primary btn-nueva-cuenta" data-bs-toggle="modal" data-bs-target="#modalCuenta">
                                <i class="fas fa-plus me-2"></i>
                                Nueva Cuenta
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items) && $catalogo === 'cuentas'): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item->codigo ?? ''); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item->descripcion ?? ''); ?></td>
                                            <td>
                                                <?php if ($item->activo): ?>
                                                    <button type="button" class="btn btn-sm btn-success" onclick="toggleEstadoCuenta(<?php echo $item->id; ?>, 0)" title="Desactivar cuenta">
                                                        <i class="fas fa-toggle-on me-1"></i>Activo
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-secondary" onclick="toggleEstadoCuenta(<?php echo $item->id; ?>, 1)" title="Activar cuenta">
                                                        <i class="fas fa-toggle-off me-1"></i>Inactivo
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarCuentaModal(<?php echo $item->id; ?>, '<?php echo addslashes($item->codigo ?? ''); ?>', '<?php echo addslashes($item->descripcion ?? ''); ?>')" title="Editar cuenta">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarCuenta(<?php echo $item->id; ?>)" title="Eliminar cuenta">
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
                                            No hay cuentas contables registradas
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Centros de Costo -->
        <div class="<?php echo $catalogo === 'centros' ? '' : 'd-none'; ?>">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="position: relative; z-index: 1015;">
                    <h5 class="mb-0" style="transform: none !important;">
                        <i class="fas fa-building me-2"></i>
                        Centros de Costo
                    </h5>
                    <a href="<?= url('/admin/centros') ?>" class="btn btn-primary" style="position: relative; z-index: 1016; transform: none !important; transition: none !important;">
                        <i class="fas fa-cog me-2"></i>
                        Gestionar Centros
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Autorizadores</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items) && $catalogo === 'centros'): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item->nombre ?? ''); ?></strong></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $item->total_autorizadores ?? 0; ?> autorizadores</span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="<?= url('/admin/autorizadores?centro=' . $item->id) ?>" class="btn btn-sm btn-outline-info" title="Gestionar autorizadores">
                                                        <i class="fas fa-users"></i> Autorizadores
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                            No hay centros de costo registrados
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nueva Cuenta Contable -->
<div class="modal fade" id="modalCuenta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calculator me-2"></i>
                    Nueva Cuenta Contable
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCuenta" method="POST" action="<?= url('/admin/catalogos/cuenta') ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código *</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Cuenta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Cuenta Contable -->
<div class="modal fade" id="modalEditarCuenta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editar Cuenta Contable
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarCuenta" method="POST">
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_codigo" class="form-label">Código *</label>
                        <input type="text" class="form-control" id="edit_codigo" name="codigo" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Actualizar Cuenta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarCuentaModal(id, codigo, descripcion) {
    // Llenar el formulario con los datos actuales
    document.getElementById('edit_codigo').value = codigo;
    document.getElementById('edit_descripcion').value = descripcion;
    
    // Configurar la acción del formulario
    document.getElementById('formEditarCuenta').action = `<?= url('/admin/catalogos/cuenta/') ?>${id}`;
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('modalEditarCuenta'));
    modal.show();
}

function toggleEstadoCuenta(id, nuevoEstado) {
    const accion = nuevoEstado ? 'activar' : 'desactivar';
    
    if (confirm(`¿Estás seguro de que deseas ${accion} esta cuenta contable?`)) {
        // Crear formulario para envío
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `<?= url('/admin/catalogos/cuenta/') ?>${id}/toggle`;
        
        // Agregar campo para el nuevo estado
        const estadoField = document.createElement('input');
        estadoField.type = 'hidden';
        estadoField.name = 'activo';
        estadoField.value = nuevoEstado;
        form.appendChild(estadoField);
        
        // Agregar token CSRF si está disponible
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const tokenField = document.createElement('input');
            tokenField.type = 'hidden';
            tokenField.name = '_token';
            tokenField.value = csrfToken.getAttribute('content');
            form.appendChild(tokenField);
        }
        
        document.body.appendChild(form);
        form.submit();
    }
}

function eliminarCuenta(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta cuenta contable?')) {
        // Crear formulario para envío por DELETE method
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `<?= url('/admin/catalogos/cuenta/') ?>${id}`;
        
        // Agregar campo _method para DELETE
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        // Agregar token CSRF si está disponible
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const tokenField = document.createElement('input');
            tokenField.type = 'hidden';
            tokenField.name = '_token';
            tokenField.value = csrfToken.getAttribute('content');
            form.appendChild(tokenField);
        }
        
        document.body.appendChild(form);
        form.submit();
    }
}


// Validación del formulario de cuenta
document.getElementById('formCuenta').addEventListener('submit', function(e) {
    const codigo = document.getElementById('codigo').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    
    if (codigo.length < 3) {
        e.preventDefault();
        alert('El código debe tener al menos 3 caracteres');
        return;
    }
    
    if (descripcion.length < 3) {
        e.preventDefault();
        alert('La descripción debe tener al menos 3 caracteres');
        return;
    }
});

// Scroll suave hacia arriba al cargar la página para mostrar los botones
window.addEventListener('DOMContentLoaded', function() {
    // Pequeño delay para que termine cualquier animación CSS
    setTimeout(function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }, 100);
});
</script>

<?php View::endSection(); ?>
