<?php 
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Editar Centro de Costo';
?>

<?php View::startSection('content'); ?>
<div class="container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0">
                <i class="fas fa-edit me-2"></i>
                Editar Centro de Costo
            </h1>
            <p class="text-muted mb-0">Centro: <?= View::e($centro->nombre ?? 'Sin nombre') ?></p>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= url('/admin/centros/' . $centro->id) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Cancelar
            </a>
        </div>
    </div>

    <!-- Formulario de Edición -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        Información del Centro de Costo
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('/admin/centros/' . $centro->id) ?>" id="formEditarCentro">
                        <?php echo CsrfMiddleware::field(); ?>
                        <input type="hidden" name="_method" value="PUT">
                        
                        <!-- Nombre -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= View::e($centro->nombre ?? '') ?>" required>
                            <div class="form-text">Nombre descriptivo del centro de costo</div>
                        </div>

                        <!-- Factura -->
                        <div class="mb-3">
                            <label for="factura" class="form-label">Factura Asignada *</label>
                            <select class="form-select" id="factura" name="factura" required>
                                <option value="1" <?= ($centro->factura ?? 1) == 1 ? 'selected' : '' ?>>Factura 1</option>
                                <option value="2" <?= ($centro->factura ?? 1) == 2 ? 'selected' : '' ?>>Factura 2</option>
                                <option value="3" <?= ($centro->factura ?? 1) == 3 ? 'selected' : '' ?>>Factura 3</option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Seleccione a qué factura se cargarán los gastos de este centro de costo.
                                <br><strong>Ejemplo:</strong> Si este centro pertenece a "Actividad Cultural" y debe cargarse a Factura 3, seleccione "Factura 3".
                            </div>
                        </div>

                        <!-- Unidad de Negocio (solo lectura) -->
                        <?php if (!empty($centro->unidad_negocio_id)): ?>
                        <div class="mb-3">
                            <label class="form-label">Unidad de Negocio</label>
                            <input type="text" class="form-control" value="<?= View::e($centro->unidad_negocio_nombre ?? 'N/A') ?>" readonly disabled>
                            <div class="form-text">La unidad de negocio se asigna automáticamente según la configuración del sistema.</div>
                        </div>
                        <?php endif; ?>

                        <!-- Botones -->
                        <div class="row">
                            <div class="col-md-6">
                                <a href="<?= url('/admin/centros/' . $centro->id) ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Actualizar Centro
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Peligro -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Zona de Peligro
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Eliminar este centro de costo es una acción irreversible. 
                        Solo se puede eliminar si no tiene requisiciones asociadas.
                    </p>
                    <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminacion()">
                        <i class="fas fa-trash me-2"></i>Eliminar Centro de Costo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.getElementById('formEditarCentro').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    
    if (nombre.length < 3) {
        e.preventDefault();
        alert('El nombre debe tener al menos 3 caracteres');
        document.getElementById('nombre').focus();
        return;
    }
});

// Confirmar eliminación
function confirmarEliminacion() {
    if (confirm('¿Estás seguro de que deseas eliminar este centro de costo?\n\nEsta acción no se puede deshacer.')) {
        // Crear formulario para envío por DELETE method
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url('/admin/centros/' . $centro->id) ?>';
        
        // Agregar campo _method para DELETE
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        // Agregar token CSRF
        const tokenField = document.createElement('input');
        tokenField.type = 'hidden';
        tokenField.name = '_token';
        tokenField.value = '<?= CsrfMiddleware::getToken() ?>';
        form.appendChild(tokenField);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
<?php View::endSection(); ?>