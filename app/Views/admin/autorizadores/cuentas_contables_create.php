<?php 
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Crear Autorizador por Cuenta Contable';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header { background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 15px 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .form-container { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); padding: 2rem; border: 1px solid #e9ecef; }
    .form-section { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e9ecef; }
    .form-section:last-child { border-bottom: none; margin-bottom: 0; }
    .section-title { font-size: 1.2rem; font-weight: 600; color: #6f42c1; margin-bottom: 1rem; display: flex; align-items: center; }
    .section-title i { margin-right: 10px; }
    .form-control:focus, .form-select:focus { border-color: #6f42c1; box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25); }
    .btn-save { background: linear-gradient(135deg, #6f42c1, #5a2d91); border: none; border-radius: 8px; padding: 12px 30px; color: white; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3); }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4); color: white; }
    .cuenta-item { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem; transition: all 0.3s ease; cursor: pointer; }
    .cuenta-item:hover { border-color: #6f42c1; background: #f3e5f5; }
    .cuenta-item.selected { border-color: #6f42c1; background: #f3e5f5; }
</style>

<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0"><i class="fas fa-calculator me-3"></i><?= View::e($title) ?></h1>
                <p class="mb-0 opacity-75">Configurar autorizador para cuentas contables específicas</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/admin/autorizadores/cuentas-contables" class="btn btn-light">
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
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información:</strong> Los autorizadores por cuenta contable permiten asignar personas específicas para autorizar requisiciones según las cuentas contables utilizadas.
                </div>

                <form action="/admin/autorizadores/cuentas-contables" method="POST" id="cuentaContableForm">
                    <?= CsrfMiddleware::field() ?>
                    
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-user"></i>Información del Autorizador</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="autorizador_email" class="form-label">Email del Autorizador <span class="text-danger">*</span></label>
                                <select name="autorizador_email" id="autorizador_email" class="form-select" required>
                                    <option value="">Seleccionar autorizador...</option>
                                    <?php if (!empty($autorizadores)): ?>
                                        <?php foreach ($autorizadores as $auth): ?>
                                            <option value="<?= View::e($auth['email'] ?? '') ?>">
                                                <?= View::e($auth['nombre'] ?? '') ?> (<?= View::e($auth['email'] ?? '') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-list-alt"></i>Cuentas Contables Autorizadas</h3>
                        <label class="form-label">Seleccionar Cuentas Contables <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-3">Seleccione una o más cuentas contables que este autorizador podrá autorizar</small>
                        
                        <div class="row">
                            <?php 
                            $cuentasEjemplo = [
                                ['codigo' => '1101', 'nombre' => 'Caja General'],
                                ['codigo' => '1102', 'nombre' => 'Bancos Nacionales'],
                                ['codigo' => '1103', 'nombre' => 'Bancos Extranjeros'],
                                ['codigo' => '2101', 'nombre' => 'Proveedores Nacionales'],
                                ['codigo' => '2102', 'nombre' => 'Proveedores Extranjeros'],
                                ['codigo' => '5101', 'nombre' => 'Gastos de Oficina'],
                                ['codigo' => '5102', 'nombre' => 'Gastos de Viaje'],
                                ['codigo' => '5103', 'nombre' => 'Gastos de Capacitación']
                            ];
                            
                            if (!empty($cuentas_contables)) {
                                $cuentasDisponibles = $cuentas_contables;
                            } else {
                                $cuentasDisponibles = $cuentasEjemplo;
                            }
                            ?>
                            
                            <?php foreach ($cuentasDisponibles as $cuenta): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="cuenta-item" onclick="toggleCuenta('cuenta_<?= View::e($cuenta['codigo']) ?>')">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <strong><?= View::e($cuenta['codigo']) ?></strong>
                                                <div class="text-muted small"><?= View::e($cuenta['nombre']) ?></div>
                                            </div>
                                            <input type="checkbox" name="cuentas_contables[]" value="<?= View::e($cuenta['codigo']) ?>" 
                                                   id="cuenta_<?= View::e($cuenta['codigo']) ?>" class="form-check-input">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-ban"></i>
                            Exclusiones de Centros de Costo
                        </h3>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atención:</strong> Los centros de costo seleccionados abajo serán <strong>excluidos</strong> del flujo de autorización. 
                            Si una requisición usa uno de estos centros de costo, este autorizador NO será llamado.
                        </div>
                        
                        <label class="form-label">Centros de Costo a Excluir (Opcional)</label>
                        <small class="text-muted d-block mb-3">Seleccione los centros de costo que NO requieren autorización de esta persona</small>
                        
                        <div id="centros_exclusion_container" style="border: 2px solid #e9ecef; border-radius: 8px; padding: 15px; max-height: 300px; overflow-y: auto; background: #f8f9fa;">
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="select_all_exclusiones">
                                    <i class="fas fa-check-double"></i> Seleccionar Todos
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="deselect_all_exclusiones">
                                    <i class="fas fa-times"></i> Deseleccionar Todos
                                </button>
                            </div>
                            <div id="centros_exclusion_list">
                                <p class="text-muted">Cargando centros de costo...</p>
                            </div>
                        </div>
                        <div id="exclusiones_count" class="mt-2" style="display: none;">
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-ban"></i> <span id="excluded_count">0</span> centros excluidos
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-info"></i>Información Adicional</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                                <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                <small class="text-muted">Opcional - dejar vacío para autorización permanente</small>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea name="observaciones" id="observaciones" class="form-control" rows="3" 
                                          placeholder="Notas adicionales sobre esta autorización (opcional)"></textarea>
                            </div>
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" checked>
                            <label class="form-check-label" for="activo">Activar autorización inmediatamente</label>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-save me-3">
                                <i class="fas fa-save me-2"></i>Crear Autorizador
                            </button>
                            <a href="/admin/autorizadores/cuentas-contables" class="btn btn-secondary">
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
    const form = document.getElementById('cuentaContableForm');
    
    form.addEventListener('submit', function(e) {
        const cuentasSeleccionadas = document.querySelectorAll('input[name="cuentas_contables[]"]:checked');
        
        if (cuentasSeleccionadas.length === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos una cuenta contable');
            return false;
        }
        
        const autorizadorEmail = document.getElementById('autorizador_email').value;
        if (!autorizadorEmail) {
            e.preventDefault();
            alert('Debe seleccionar un autorizador');
            return false;
        }
    });
    
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const hoy = new Date().toISOString().split('T')[0];
    fechaInicio.min = hoy;
    
    fechaInicio.addEventListener('change', function() {
        fechaFin.min = this.value;
    });
    
    // ========================================================================
    // GESTIÓN DE EXCLUSIONES DE CENTROS DE COSTO
    // ========================================================================
    
    const centrosExclusionList = document.getElementById('centros_exclusion_list');
    const exclusionesCount = document.getElementById('exclusiones_count');
    const excludedCountSpan = document.getElementById('excluded_count');
    
    // Cargar centros de costo para exclusiones
    fetch('/admin/api/centros-costo-list')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.centros && data.centros.length > 0) {
                renderCentrosExclusion(data.centros);
                exclusionesCount.style.display = 'block';
                updateExcludedCount();
            } else {
                centrosExclusionList.innerHTML = '<p class="text-muted">No se pudieron cargar los centros de costo</p>';
            }
        })
        .catch(error => {
            console.error('Error cargando centros:', error);
            centrosExclusionList.innerHTML = '<p class="text-danger">Error al cargar los centros de costo</p>';
        });
    
    // Renderizar centros de costo para exclusión
    function renderCentrosExclusion(centros) {
        let html = '';
        centros.forEach(centro => {
            const nombre = centro.nombre || 'Sin nombre';
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input centro-exclusion-checkbox" 
                           type="checkbox" 
                           name="centros_excluidos[]" 
                           value="${centro.id}" 
                           id="exclusion_${centro.id}">
                    <label class="form-check-label" for="exclusion_${centro.id}">
                        ${nombre}
                    </label>
                </div>
            `;
        });
        centrosExclusionList.innerHTML = html;
        
        // Agregar event listeners
        document.querySelectorAll('.centro-exclusion-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateExcludedCount);
        });
    }
    
    // Actualizar contador de exclusiones
    function updateExcludedCount() {
        const count = document.querySelectorAll('.centro-exclusion-checkbox:checked').length;
        excludedCountSpan.textContent = count;
    }
    
    // Botón seleccionar todos las exclusiones
    document.getElementById('select_all_exclusiones').addEventListener('click', function() {
        document.querySelectorAll('.centro-exclusion-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateExcludedCount();
    });
    
    // Botón deseleccionar todas las exclusiones
    document.getElementById('deselect_all_exclusiones').addEventListener('click', function() {
        document.querySelectorAll('.centro-exclusion-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateExcludedCount();
    });
});

function toggleCuenta(cuentaId) {
    const checkbox = document.getElementById(cuentaId);
    const item = checkbox.closest('.cuenta-item');
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        item.classList.add('selected');
    } else {
        item.classList.remove('selected');
    }
}
</script>
<?php View::endSection(); ?>