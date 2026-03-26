<?php
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Nuevo Centro de Costo';
?>

<?php View::startSection('content'); ?>
<div class="container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0">
                <i class="fas fa-plus me-2"></i>
                Nuevo Centro de Costo
            </h1>
            <p class="text-muted mb-0">Crear un nuevo centro de costo para el sistema</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= url('/admin/centros') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Cancelar
            </a>
        </div>
    </div>

    <!-- Formulario de Creación -->
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
                    <form method="POST" action="<?= url('/admin/centros') ?>" id="formNuevoCentro">
                        <?php echo CsrfMiddleware::field(); ?>

                        <!-- Nombre -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                            <div class="form-text">Nombre descriptivo del centro de costo</div>
                        </div>

                        <!-- Factura -->
                        <div class="mb-3">
                            <label for="factura" class="form-label">Factura Asignada *</label>
                            <select class="form-select" id="factura" name="factura" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="1">Factura 1</option>
                                <option value="2">Factura 2</option>
                                <option value="3">Factura 3</option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Seleccione a qué factura se cargarán los gastos de este centro de costo.
                            </div>
                        </div>

                        <!-- Unidad de Negocio -->
                        <div class="mb-3">
                            <label for="unidad_negocio_id" class="form-label">Unidad de Negocio *</label>
                            <select class="form-select" id="unidad_negocio_id" name="unidad_negocio_id" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($unidadesNegocio as $unidad): ?>
                                <option value="<?= $unidad['id'] ?>">
                                    <?= View::e($unidad['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Seleccione la unidad de negocio a la que pertenece este centro de costo.</div>
                        </div>

                        <!-- Botones -->
                        <div class="row">
                            <div class="col-md-6">
                                <a href="<?= url('/admin/centros') ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Crear Centro
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        ¿Qué sigue después?
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Una vez creado el centro de costo, podrás:
                    </p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-users text-info me-2"></i>
                            Asignar autorizadores para aprobar requisiciones
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-file-invoice text-success me-2"></i>
                            Usar el centro en nuevas requisiciones de compra
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-chart-bar text-warning me-2"></i>
                            Generar reportes de gastos por centro
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.getElementById('formNuevoCentro').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    
    if (nombre.length < 3) {
        e.preventDefault();
        alert('El nombre debe tener al menos 3 caracteres');
        document.getElementById('nombre').focus();
        return;
    }
});

// Auto-focus en el primer campo
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('nombre').focus();
});
</script>
<?php View::endSection(); ?>