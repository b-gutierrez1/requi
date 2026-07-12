<?php
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Asignar Centros de Costo';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .info-card {
        background: #e3f2fd;
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        border: 1px solid #bbdefb;
    }

    .info-card h6 {
        color: #1565c0;
        margin-bottom: 0.5rem;
    }

    .search-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e9ecef;
    }

    .search-input {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px 16px 12px 42px;
        width: 100%;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .search-input:focus {
        border-color: #e74c3c;
        box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
        outline: none;
    }

    .search-wrapper {
        position: relative;
    }

    .search-wrapper i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .counter {
        font-weight: 600;
        color: #495057;
        font-size: 0.95rem;
    }

    .counter span {
        color: #e74c3c;
        font-weight: 700;
    }

    .centros-grid {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 1.5rem;
        border: 1px solid #e9ecef;
    }

    .centro-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .centro-item:hover {
        border-color: #3498db;
        background: #f8f9ff;
    }

    .centro-item.checked {
        border-color: #28a745;
        background: #f0fff4;
    }

    .centro-item input[type="checkbox"] {
        appearance: none;
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        min-width: 20px;
        margin-right: 12px;
        border: 2px solid #ced4da;
        border-radius: 5px;
        background: white;
        cursor: pointer;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    .centro-item input[type="checkbox"]:hover {
        border-color: #e74c3c;
    }

    .centro-item input[type="checkbox"]:checked {
        background-color: #e74c3c;
        border-color: #e74c3c;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e");
        background-size: 100%;
        background-position: center;
        background-repeat: no-repeat;
    }

    .centro-info {
        flex: 1;
        min-width: 0;
    }

    .centro-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.95rem;
    }

    .centro-codigo {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .btn-save {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        color: white;
    }

    .btn-cancel {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: #5a6268;
        color: white;
    }

    .btn-select {
        background: transparent;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 6px 16px;
        color: #3498db;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-select:hover {
        background: #3498db;
        color: white;
    }

    .btn-deselect {
        background: transparent;
        border: 2px solid #e74c3c;
        border-radius: 8px;
        padding: 6px 16px;
        color: #e74c3c;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-deselect:hover {
        background: #e74c3c;
        color: white;
    }

    .no-results {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
        display: none;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-building me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Selecciona los centros de costo para este autorizador</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= url('/admin/autorizadores/' . ($autorizador->id ?? '') . '/edit') ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a Editar
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <?php
    $flash = Session::getFlash();
    if ($flash):
    ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-triangle' : ($flash['type'] === 'success' ? 'check-circle' : 'info-circle') ?> me-2"></i>
            <?= View::e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Info del autorizador -->
    <div class="info-card">
        <h6><i class="fas fa-user me-2"></i>Autorizador</h6>
        <div class="row">
            <div class="col-md-6">
                <strong>Nombre:</strong> <?= View::e($autorizador->nombre ?? '') ?>
            </div>
            <div class="col-md-6">
                <strong>Email:</strong> <?= View::e($autorizador->email ?? '') ?>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= url('/admin/autorizadores/' . ($autorizador->id ?? '') . '/centros') ?>" id="centrosForm">
        <?php echo App\Middlewares\CsrfMiddleware::field(); ?>
        <input type="hidden" name="_method" value="PUT">

        <!-- Buscador y toolbar -->
        <div class="search-container">
            <div class="toolbar">
                <div class="search-wrapper" style="flex: 1; max-width: 400px;">
                    <i class="fas fa-search"></i>
                    <input type="text"
                           class="search-input"
                           id="searchCentros"
                           placeholder="Buscar centro de costo...">
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn-select" id="btnSelectAll">
                        <i class="fas fa-check-double me-1"></i> Seleccionar Todos
                    </button>
                    <button type="button" class="btn-deselect" id="btnDeselectAll">
                        <i class="fas fa-times me-1"></i> Deseleccionar Todos
                    </button>
                </div>
            </div>
            <div class="mt-2">
                <span class="counter">
                    <span id="selectedCount"><?= count(array_filter($ordenesPorCentro ?? [], fn($o) => $o > 0)) ?></span> de
                    <span id="totalCount"><?= count($todosLosCentros ?? []) ?></span> centros seleccionados
                </span>
            </div>
        </div>

        <!-- Grid de centros -->
        <div class="centros-grid">
            <div class="row" id="centrosContainer">
                <?php if (!empty($todosLosCentros)): ?>
                    <?php foreach ($todosLosCentros as $centro): ?>
                        <?php
                            $centroId     = is_object($centro) ? $centro->id    : $centro['id'];
                            $centroNombre = is_object($centro) ? ($centro->nombre ?? 'Sin nombre') : ($centro['nombre'] ?? 'Sin nombre');
                            $centroCodigo = is_object($centro) ? ($centro->codigo ?? '') : ($centro['codigo'] ?? '');
                            $ordenActual  = (int)($ordenesPorCentro[(int)$centroId] ?? 0);
                            $isAsignado   = $ordenActual > 0;
                        ?>
                        <div class="col-lg-6 centro-col" data-nombre="<?= strtolower(View::e($centroNombre)) ?>" data-codigo="<?= strtolower(View::e($centroCodigo)) ?>">
                            <div class="centro-item <?= $isAsignado ? 'checked' : '' ?>">
                                <div class="centro-info flex-grow-1">
                                    <div class="centro-nombre"><?= View::e($centroNombre) ?></div>
                                    <?php if ($centroCodigo): ?>
                                        <div class="centro-codigo"><?= View::e($centroCodigo) ?></div>
                                    <?php endif; ?>
                                </div>
                                <select name="centro_costo_orden[<?= View::e($centroId) ?>]"
                                        class="form-select form-select-sm orden-select ms-2"
                                        style="width:auto;min-width:180px;"
                                        data-centro-id="<?= View::e($centroId) ?>">
                                    <option value="0" <?= $ordenActual === 0 ? 'selected' : '' ?>>— Sin asignar —</option>
                                    <option value="1" <?= $ordenActual === 1 ? 'selected' : '' ?>>Orden 1 · Aprueba primero</option>
                                    <option value="2" <?= $ordenActual === 2 ? 'selected' : '' ?>>Orden 2 · Aprueba segundo</option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="no-results" id="noResults">
                <i class="fas fa-search fa-2x mb-2"></i>
                <p>No se encontraron centros de costo con ese criterio</p>
            </div>
        </div>

        <!-- Botones -->
        <div class="text-center mt-4 mb-4 d-flex justify-content-center gap-3">
            <button type="submit" class="btn btn-save">
                <i class="fas fa-save me-2"></i>
                Guardar Asignaciones
            </button>
            <a href="<?= url('/admin/autorizadores/' . ($autorizador->id ?? '') . '/edit') ?>" class="btn btn-cancel">
                <i class="fas fa-times me-2"></i>
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
(function() {
    const searchInput = document.getElementById('searchCentros');
    const centrosCols = document.querySelectorAll('.centro-col');
    const noResults = document.getElementById('noResults');
    const selectedCountEl = document.getElementById('selectedCount');
    const selects = document.querySelectorAll('select.orden-select');

    function updateCount() {
        const assigned = document.querySelectorAll('select.orden-select').length
            - document.querySelectorAll('select.orden-select [value="0"]:checked').length;
        // cuenta selects cuyo valor != "0"
        let count = 0;
        document.querySelectorAll('select.orden-select').forEach(function(s) {
            if (s.value !== '0') count++;
        });
        selectedCountEl.textContent = count;
    }

    function updateItemStyle(select) {
        const item = select.closest('.centro-item');
        if (select.value !== '0') {
            item.classList.add('checked');
        } else {
            item.classList.remove('checked');
        }
    }

    // Search filter
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        let visibleCount = 0;

        centrosCols.forEach(function(col) {
            const nombre = col.getAttribute('data-nombre');
            const codigo = col.getAttribute('data-codigo');
            if (nombre.indexOf(query) !== -1 || codigo.indexOf(query) !== -1) {
                col.style.display = '';
                visibleCount++;
            } else {
                col.style.display = 'none';
            }
        });

        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    });

    // Select change
    selects.forEach(function(sel) {
        sel.addEventListener('change', function() {
            updateItemStyle(this);
            updateCount();
        });
    });

    // Select all visible → orden 1
    document.getElementById('btnSelectAll').addEventListener('click', function() {
        centrosCols.forEach(function(col) {
            if (col.style.display !== 'none') {
                const sel = col.querySelector('select.orden-select');
                if (sel && sel.value === '0') {
                    sel.value = '1';
                    updateItemStyle(sel);
                }
            }
        });
        updateCount();
    });

    // Deselect all visible
    document.getElementById('btnDeselectAll').addEventListener('click', function() {
        centrosCols.forEach(function(col) {
            if (col.style.display !== 'none') {
                const sel = col.querySelector('select.orden-select');
                if (sel && sel.value !== '0') {
                    sel.value = '0';
                    updateItemStyle(sel);
                }
            }
        });
        updateCount();
    });
})();
</script>
<?php View::endSection(); ?>
