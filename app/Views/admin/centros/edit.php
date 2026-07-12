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

                        <!-- Unidad de Negocio -->
                        <div class="mb-3">
                            <label for="unidad_negocio_id" class="form-label">Unidad de Negocio *</label>
                            <select class="form-select" id="unidad_negocio_id" name="unidad_negocio_id" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($unidadesNegocio as $unidad): ?>
                                <option value="<?= $unidad['id'] ?>" <?= ($centro->unidad_negocio_id ?? '') == $unidad['id'] ? 'selected' : '' ?>>
                                    <?= View::e($unidad['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Seleccione la unidad de negocio a la que pertenece este centro de costo.</div>
                        </div>

                        <!-- Asignación manual -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Comportamiento en Revisión</label>
                            <div id="toggleCard" class="toggle-option-card <?= ($centro->requiere_asignacion_manual ?? 0) ? 'active' : '' ?>">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="toggle-icon-wrap">
                                        <i class="fas fa-hand-pointer toggle-icon-on"></i>
                                        <i class="fas fa-robot toggle-icon-off"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="toggle-title-on">Asignación <strong>manual</strong> activada</div>
                                        <div class="toggle-title-off">Asignación <strong>automática</strong></div>
                                        <small class="text-muted toggle-desc">
                                            <span class="toggle-desc-on">El revisor deberá elegir el autorizador al aprobar.</span>
                                            <span class="toggle-desc-off">El autorizador se asigna según la configuración del centro.</span>
                                        </small>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input toggle-switch" type="checkbox" role="switch"
                                               id="requiere_asignacion_manual" name="requiere_asignacion_manual" value="1"
                                               <?= ($centro->requiere_asignacion_manual ?? 0) ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <style>
                        .toggle-option-card {
                            border: 2px solid #dee2e6;
                            border-radius: 12px;
                            padding: 16px 20px;
                            cursor: pointer;
                            transition: all .25s ease;
                            background: #f8f9fa;
                        }
                        .toggle-option-card.active {
                            border-color: #fd7e14;
                            background: #fff8f2;
                            box-shadow: 0 0 0 3px rgba(253,126,20,.15);
                        }
                        .toggle-icon-wrap {
                            font-size: 1.6rem;
                            width: 42px;
                            text-align: center;
                            flex-shrink: 0;
                        }
                        .toggle-icon-on  { display: none; color: #fd7e14; }
                        .toggle-icon-off { display: inline; color: #adb5bd; }
                        .toggle-option-card.active .toggle-icon-on  { display: inline; }
                        .toggle-option-card.active .toggle-icon-off { display: none; }

                        .toggle-title-on, .toggle-title-off { font-size: .95rem; }
                        .toggle-title-on  { display: none; color: #fd7e14; }
                        .toggle-title-off { display: block; color: #6c757d; }
                        .toggle-option-card.active .toggle-title-on  { display: block; }
                        .toggle-option-card.active .toggle-title-off { display: none; }

                        .toggle-desc-on  { display: none; }
                        .toggle-desc-off { display: inline; }
                        .toggle-option-card.active .toggle-desc-on  { display: inline; }
                        .toggle-option-card.active .toggle-desc-off { display: none; }

                        .toggle-switch { width: 3em; height: 1.6em; cursor: pointer; }
                        .toggle-switch:checked { background-color: #fd7e14; border-color: #fd7e14; }
                        </style>

                        <script>
                        (function() {
                            const card   = document.getElementById('toggleCard');
                            const toggle = document.getElementById('requiere_asignacion_manual');
                            function sync() { card.classList.toggle('active', toggle.checked); }
                            card.addEventListener('click', function(e) {
                                if (e.target !== toggle) toggle.checked = !toggle.checked;
                                sync();
                            });
                            toggle.addEventListener('change', sync);
                        })();
                        </script>

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