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

                        <!-- Código -->
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" maxlength="10">
                            <div class="form-text">Código corto del centro de costo (ej. PA01, CR01). Opcional.</div>
                        </div>

                        <!-- Factura -->
                        <div class="mb-3">
                            <label for="factura" class="form-label">Factura Asignada *</label>
                            <select class="form-select" id="factura" name="factura" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="1">Factura 1</option>
                                <option value="2">Factura 2</option>
                                <option value="3">Factura 3</option>
                                <option value="4">Factura 4</option>
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

                        <!-- Asignación manual -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Comportamiento en Revisión</label>
                            <div id="toggleCard" class="toggle-option-card">
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
                                               id="requiere_asignacion_manual" name="requiere_asignacion_manual" value="1">
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