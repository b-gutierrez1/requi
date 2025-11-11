<?php
use App\Helpers\View;

View::startSection('content');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-list-alt me-2"></i>
                Gestión de Catálogos
            </h1>
            <p class="text-muted mb-0">Administración de catálogos del sistema</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <a href="/admin/catalogos?tipo=cuentas" class="btn <?php echo $catalogo === 'cuentas' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-calculator me-2"></i>
                    Cuentas Contables
                </a>
                <a href="/admin/catalogos?tipo=centros" class="btn <?php echo $catalogo === 'centros' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-building me-2"></i>
                    Centros de Costo
                </a>
            </div>
        </div>
    </div>

    <!-- Navegación de Catálogos -->
    <div class="card mb-4">
        <div class="card-body">
            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link <?php echo $catalogo === 'cuentas' ? 'active' : ''; ?>" 
                            id="nav-cuentas-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#nav-cuentas" 
                            type="button" 
                            role="tab">
                        <i class="fas fa-calculator me-2"></i>
                        Cuentas Contables
                    </button>
                    <button class="nav-link <?php echo $catalogo === 'centros' ? 'active' : ''; ?>" 
                            id="nav-centros-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#nav-centros" 
                            type="button" 
                            role="tab">
                        <i class="fas fa-building me-2"></i>
                        Centros de Costo
                    </button>
                </div>
            </nav>
        </div>
    </div>

    <!-- Contenido de Catálogos -->
    <div class="tab-content" id="nav-tabContent">
        <!-- Cuentas Contables -->
        <div class="tab-pane fade <?php echo $catalogo === 'cuentas' ? 'show active' : ''; ?>" 
             id="nav-cuentas" 
             role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator me-2"></i>
                        Cuentas Contables
                    </h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCuenta">
                        <i class="fas fa-plus me-2"></i>
                        Nueva Cuenta
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items) && $catalogo === 'cuentas'): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item->codigo ?? ''); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item->nombre ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($item->descripcion ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($item->tipo ?? 'N/A'); ?></span>
                                            </td>
                                            <td>
                                                <?php if ($item->activo): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarCuenta(<?php echo $item->id; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarCuenta(<?php echo $item->id; ?>)">
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
        <div class="tab-pane fade <?php echo $catalogo === 'centros' ? 'show active' : ''; ?>" 
             id="nav-centros" 
             role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        Centros de Costo
                    </h5>
                    <a href="/admin/centros" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i>
                        Gestionar Centros
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Autorizadores</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($items) && $catalogo === 'centros'): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item->codigo ?? ''); ?></strong></td>
                                            <td><?php echo htmlspecialchars($item->nombre ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($item->descripcion ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $item->total_autorizadores ?? 0; ?> autorizadores</span>
                                            </td>
                                            <td>
                                                <?php if ($item->activo): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarCentro(<?php echo $item->id; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="/admin/autorizadores?centro=<?php echo $item->id; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-users"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
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
            <form id="formCuenta" method="POST" action="/admin/catalogos/cuenta">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código *</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" required>
                    </div>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo *</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="">Seleccionar tipo</option>
                            <option value="activo">Activo</option>
                            <option value="pasivo">Pasivo</option>
                            <option value="capital">Capital</option>
                            <option value="ingreso">Ingreso</option>
                            <option value="gasto">Gasto</option>
                        </select>
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

<script>
function editarCuenta(id) {
    // Aquí se implementaría la lógica para editar una cuenta contable
    console.log('Editar cuenta:', id);
}

function eliminarCuenta(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta cuenta contable?')) {
        // Aquí se implementaría la lógica para eliminar una cuenta contable
        console.log('Eliminar cuenta:', id);
    }
}

function editarCentro(id) {
    // Aquí se implementaría la lógica para editar un centro de costo
    console.log('Editar centro:', id);
}

// Validación del formulario de cuenta
document.getElementById('formCuenta').addEventListener('submit', function(e) {
    const codigo = document.getElementById('codigo').value.trim();
    const nombre = document.getElementById('nombre').value.trim();
    
    if (codigo.length < 3) {
        e.preventDefault();
        alert('El código debe tener al menos 3 caracteres');
        return;
    }
    
    if (nombre.length < 3) {
        e.preventDefault();
        alert('El nombre debe tener al menos 3 caracteres');
        return;
    }
});
</script>

<?php View::endSection(); ?>
