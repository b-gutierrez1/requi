<?php
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Nuevo Autorizador';
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

    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px 16px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #e74c3c;
        box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
    }

    .btn-create {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    }

    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
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

    .centros-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
    }

    .search-centros {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 10px 14px 10px 38px;
        width: 100%;
        transition: all 0.3s ease;
    }

    .search-centros:focus {
        border-color: #e74c3c;
        box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
        outline: none;
    }

    .search-centros-wrapper {
        position: relative;
    }

    .search-centros-wrapper i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .centro-item {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 6px;
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
        width: 18px;
        height: 18px;
        min-width: 18px;
        margin-right: 10px;
        border: 2px solid #ced4da;
        border-radius: 4px;
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

    .centro-nombre {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
    }

    .centro-codigo {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .toolbar-centros {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .counter-centros {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
    }

    .counter-centros span {
        color: #e74c3c;
        font-weight: 700;
    }

    .btn-sel {
        background: transparent;
        border: 1px solid #3498db;
        border-radius: 6px;
        padding: 4px 12px;
        color: #3498db;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .btn-sel:hover { background: #3498db; color: white; }

    .btn-desel {
        background: transparent;
        border: 1px solid #e74c3c;
        border-radius: 6px;
        padding: 4px 12px;
        color: #e74c3c;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
    }

    .btn-desel:hover { background: #e74c3c; color: white; }

    .centros-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .no-results-centros {
        text-align: center;
        padding: 1rem;
        color: #6c757d;
        display: none;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">
                    <i class="fas fa-user-plus me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Crear un nuevo autorizador para el sistema</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="<?= url('/admin/autorizadores') ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Listado
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="form-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-user-shield me-2 text-danger"></i>
                        Informacion del Autorizador
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="<?= url('/admin/autorizadores') ?>" id="formNuevoAutorizador">
                        <?= \App\Middlewares\CsrfMiddleware::field() ?>

                        <!-- Nombre y Email -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required
                                           value="<?= View::e(Session::old('nombre') ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?= View::e(Session::old('email') ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Puesto / Cargo -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cargo" class="form-label">Puesto / Cargo</label>
                                    <input type="text" class="form-control" id="cargo" name="cargo"
                                           placeholder="Ej: Gerente de Finanzas"
                                           value="<?= View::e(Session::old('cargo') ?? '') ?>">
                                    <div class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Opcional.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Centros de Costo con checkboxes -->
                        <div class="centros-section">
                            <h6 class="mb-3">
                                <i class="fas fa-building me-2 text-danger"></i>
                                Centros de Costo a Asignar *
                            </h6>

                            <div class="toolbar-centros">
                                <div class="search-centros-wrapper" style="flex: 1; max-width: 350px;">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="search-centros" id="searchCentrosCreate" placeholder="Buscar centro de costo...">
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn-sel" id="btnSelAll">
                                        <i class="fas fa-check-double me-1"></i> Todos
                                    </button>
                                    <button type="button" class="btn-desel" id="btnDeselAll">
                                        <i class="fas fa-times me-1"></i> Ninguno
                                    </button>
                                </div>
                            </div>

                            <div class="counter-centros mb-2">
                                <span id="selCount">0</span> de <?= count($centros ?? []) ?> centros seleccionados
                            </div>

                            <div class="centros-list" id="centrosListCreate">
                                <div class="row">
                                    <?php if (!empty($centros)): ?>
                                        <?php foreach ($centros as $centro): ?>
                                            <?php
                                                $cId = is_object($centro) ? $centro->id : $centro['id'];
                                                $cNombre = is_object($centro) ? ($centro->nombre ?? 'Sin nombre') : ($centro['nombre'] ?? 'Sin nombre');
                                                $cCodigo = is_object($centro) ? ($centro->codigo ?? '') : ($centro['codigo'] ?? '');
                                            ?>
                                            <div class="col-lg-6 centro-col-create"
                                                 data-nombre="<?= strtolower(View::e($cNombre)) ?>"
                                                 data-codigo="<?= strtolower(View::e($cCodigo)) ?>">
                                                <label class="centro-item">
                                                    <input type="checkbox" name="centro_costo_ids[]" value="<?= View::e($cId) ?>">
                                                    <div>
                                                        <div class="centro-nombre"><?= View::e($cNombre) ?></div>
                                                        <?php if ($cCodigo): ?>
                                                            <div class="centro-codigo"><?= View::e($cCodigo) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="no-results-centros" id="noResultsCreate">
                                <i class="fas fa-search"></i> No se encontraron centros
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row mt-4">
                            <div class="col-12 d-flex justify-content-center gap-3">
                                <button type="submit" class="btn btn-create">
                                    <i class="fas fa-save me-2"></i>
                                    Crear Autorizador
                                </button>
                                <a href="<?= url('/admin/autorizadores') ?>" class="btn btn-cancel">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo \App\Helpers\View::asset('js/admin/autorizadores-create.js'); ?>"></script>
<?php View::endSection(); ?>
