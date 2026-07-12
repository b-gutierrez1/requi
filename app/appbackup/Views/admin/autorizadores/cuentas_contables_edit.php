<?php
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = $title ?? 'Editar Autorizador por Cuenta Contable';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header { background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 15px 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .form-container { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); padding: 2rem; border: 1px solid #e9ecef; }
    .form-section { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e9ecef; }
    .form-section:last-child { border-bottom: none; margin-bottom: 0; }
    .section-title { font-size: 1.2rem; font-weight: 600; color: #6f42c1; margin-bottom: 1rem; display: flex; align-items: center; }
    .section-title i { margin-right: 10px; }
    .btn-save { background: linear-gradient(135deg, #6f42c1, #5a2d91); border: none; border-radius: 8px; padding: 12px 30px; color: white; font-weight: 600; transition: all 0.3s ease; }
    .btn-save:hover { transform: translateY(-2px); color: white; }
    .cuenta-item { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem; transition: all 0.3s ease; cursor: pointer; }
    .cuenta-item:hover { border-color: #6f42c1; background: #f3e5f5; }
    .cuenta-item.selected { border-color: #6f42c1; background: #f3e5f5; }
    #cuentaContableForm input[type="checkbox"] {
        appearance: none !important; -webkit-appearance: none !important;
        width: 20px !important; height: 20px !important; min-width: 20px !important;
        border: 2px solid #ced4da !important; border-radius: 5px !important;
        background-color: white !important; background-image: none !important;
        cursor: pointer !important; flex-shrink: 0;
        transition: border-color 0.2s ease, background-color 0.2s ease;
        vertical-align: middle; display: inline-block;
    }
    #cuentaContableForm input[type="checkbox"]:checked {
        background-color: #6f42c1 !important; border-color: #6f42c1 !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e") !important;
        background-size: 100% !important; background-position: center !important; background-repeat: no-repeat !important;
    }
</style>

<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0"><i class="fas fa-calculator me-3"></i><?= View::e($title) ?></h1>
                <p class="mb-0 opacity-75">Modificar cuentas contables del autorizador</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= url('/admin/autorizadores/cuentas-contables') ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="form-container">
                <form action="<?= url('/admin/autorizadores/cuentas-contables/' . View::e($id)) ?>" method="POST" id="cuentaContableForm">
                    <?= CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="autorizador_email" value="<?= View::e($email) ?>">

                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-user"></i>Autorizador</h3>
                        <div class="alert alert-secondary">
                            <i class="fas fa-user-circle me-2"></i>
                            <strong><?= View::e($nombre) ?></strong>
                            <span class="text-muted ms-2"><?= View::e($email) ?></span>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-list-alt"></i>Cuentas Contables Autorizadas</h3>
                        <small class="text-muted d-block mb-3">Seleccione las cuentas contables que este autorizador podrá autorizar</small>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0" id="buscador_cuentas"
                                           placeholder="Buscar por código o nombre..." oninput="filtrarCuentas()">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="seleccionarTodas()">
                                    <i class="fas fa-check-square me-1"></i>Todas
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deseleccionarTodas()">
                                    <i class="fas fa-square me-1"></i>Ninguna
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <small class="text-info">
                                Seleccionadas: <strong id="contador_seleccionadas">0</strong> de <strong id="contador_total">0</strong>
                            </small>
                        </div>

                        <div class="row" id="cuentas_container">
                            <?php
                            $idsAsignadosSet = array_flip($ids_asignados ?? []);
                            foreach ($cuentas_contables as $cuenta):
                                $checked = isset($idsAsignadosSet[$cuenta['id']]) ? 'checked' : '';
                                $selectedClass = $checked ? 'selected' : '';
                            ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="cuenta-item <?= $selectedClass ?>"
                                         onclick="toggleCuenta('cuenta_<?= View::e($cuenta['codigo']) ?>')">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <strong><?= View::e($cuenta['codigo']) ?></strong>
                                                <div class="text-muted small"><?= View::e($cuenta['descripcion']) ?></div>
                                            </div>
                                            <input type="checkbox" name="cuentas_contables[]"
                                                   value="<?= View::e($cuenta['id']) ?>"
                                                   id="cuenta_<?= View::e($cuenta['codigo']) ?>"
                                                   <?= $checked ?>>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-info"></i>Información Adicional</h3>
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea name="observaciones" id="observaciones" class="form-control" rows="3"><?= View::e($observaciones ?? '') ?></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                                   <?= ($activo ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">Autorización activa</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-save me-3">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                            <a href="<?= url('/admin/autorizadores/cuentas-contables') ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const total = document.querySelectorAll('.cuenta-item').length;
    document.getElementById('contador_total').textContent = total;
    actualizarContador();

    document.querySelectorAll('input[name="cuentas_contables[]"]').forEach(cb => {
        cb.addEventListener('change', function() {
            this.closest('.cuenta-item').classList.toggle('selected', this.checked);
            actualizarContador();
        });
    });

    document.getElementById('cuentaContableForm').addEventListener('submit', function(e) {
        if (!document.querySelectorAll('input[name="cuentas_contables[]"]:checked').length) {
            e.preventDefault();
            alert('Debe seleccionar al menos una cuenta contable');
        }
    });
});

function toggleCuenta(id) {
    const cb = document.getElementById(id);
    cb.checked = !cb.checked;
    cb.closest('.cuenta-item').classList.toggle('selected', cb.checked);
    actualizarContador();
}

function actualizarContador() {
    document.getElementById('contador_seleccionadas').textContent =
        document.querySelectorAll('input[name="cuentas_contables[]"]:checked').length;
}

function filtrarCuentas() {
    const q = document.getElementById('buscador_cuentas').value.toLowerCase();
    document.querySelectorAll('.cuenta-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.closest('.col-md-6').style.display = text.includes(q) ? '' : 'none';
    });
}

function seleccionarTodas() {
    document.querySelectorAll('.cuenta-item:not([style*="none"]) input[type="checkbox"]').forEach(cb => {
        cb.checked = true; cb.closest('.cuenta-item').classList.add('selected');
    });
    actualizarContador();
}

function deseleccionarTodas() {
    document.querySelectorAll('input[name="cuentas_contables[]"]').forEach(cb => {
        cb.checked = false; cb.closest('.cuenta-item').classList.remove('selected');
    });
    actualizarContador();
}
</script>
<?php View::endSection(); ?>
