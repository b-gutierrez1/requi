<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Gestión de Centros de Costo';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .stats-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .filter-btn {
        border-radius: 25px;
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 2px solid #dee2e6;
    }
    
    .filter-btn.active {
        background: #007bff;
        border-color: #007bff;
        color: white;
    }
    
    .filter-btn:hover {
        border-color: #007bff;
        color: #007bff;
    }
    
    .search-box {
        border-radius: 25px;
        border: 2px solid #dee2e6;
        padding: 10px 20px;
        transition: all 0.3s ease;
    }
    
    .search-box:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    
    .centros-table {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid #e9ecef;
    }
    
    .centros-table .table {
        margin: 0;
    }
    
    .centros-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        border: none;
        padding: 15px 12px;
        font-size: 14px;
    }
    
    .centros-table td {
        padding: 15px 12px;
        border-top: 1px solid #e9ecef;
        vertical-align: middle;
    }
    
    .centros-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .badge-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .badge-active {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .btn-action:hover {
        transform: scale(1.1);
    }
    
    .btn-create {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .pagination .page-link {
        border-radius: 8px;
        margin: 0 2px;
        border: 1px solid #dee2e6;
        color: #007bff;
        font-weight: 500;
    }
    
    .pagination .page-item.active .page-link {
        background: #007bff;
        border-color: #007bff;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">
                    <i class="fas fa-building me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Administra los centros de costo del sistema</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="/admin/centros/create" class="btn btn-create">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Centro de Costo
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-building fa-3x text-primary mb-3"></i>
                <h3 class="mb-1"><?= count($centros ?? []) ?></h3>
                <p class="text-muted mb-0">Total Centros</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h3 class="mb-1"><?= count(array_filter($centros ?? [], function($c) { return $c->activo ?? true; })) ?></h3>
                <p class="text-muted mb-0">Activos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                <h3 class="mb-1"><?= count(array_filter($centros ?? [], function($c) { return !($c->activo ?? true); })) ?></h3>
                <p class="text-muted mb-0">Inactivos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                <h3 class="mb-1"><?= count(array_unique(array_column(array_map(function($c) { return (array)$c; }, $centros ?? []), 'unidad_negocio_id'))) ?></h3>
                <p class="text-muted mb-0">Unidades</p>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="btn-group" role="group">
                <a href="/admin/centros" class="btn filter-btn active">
                    Todos
                </a>
                <a href="/admin/centros?filtro=activos" class="btn filter-btn">
                    Activos
                </a>
                <a href="/admin/centros?filtro=inactivos" class="btn filter-btn">
                    Inactivos
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control search-box border-start-0"
                       placeholder="Buscar centro de costo..." id="searchInput">
            </div>
        </div>
    </div>

    <!-- Tabla de Centros de Costo -->
    <div class="centros-table">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="centrosTable">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="25%">Nombre</th>
                        <th width="25%">Descripción</th>
                        <th width="15%">Código</th>
                        <th width="10%">Estado</th>
                        <th width="10%">Unidad Negocio</th>
                        <th width="10%" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($centros)): ?>
                        <?php foreach ($centros as $centro): ?>
                            <tr class="centro-row">
                                <td>
                                    <span class="badge bg-secondary">#<?= View::e($centro->id) ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= View::e($centro->nombre ?? 'Sin nombre') ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($centro->descripcion)): ?>
                                        <div class="text-muted"><?= View::e($centro->descripcion) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">Sin descripción</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($centro->codigo)): ?>
                                        <code><?= View::e($centro->codigo) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">Sin código</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($centro->activo ?? true): ?>
                                        <span class="badge badge-status badge-active">
                                            <i class="fas fa-check me-1"></i>Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-status badge-inactive">
                                            <i class="fas fa-times me-1"></i>Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($centro->unidad_negocio_id)): ?>
                                        <small class="text-muted">ID: <?= View::e($centro->unidad_negocio_id) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/centros/<?= View::e($centro->id) ?>"
                                           class="btn btn-outline-primary btn-action"
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/admin/centros/<?= View::e($centro->id) ?>/edit"
                                           class="btn btn-outline-warning btn-action"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/admin/centros/<?= View::e($centro->id) ?>/delete"
                                           class="btn btn-outline-danger btn-action"
                                           title="Eliminar"
                                           onclick="return confirm('¿Estás seguro de eliminar este centro de costo?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-building"></i>
                                    <h4>No hay centros de costo</h4>
                                    <p class="mb-3">No se encontraron centros de costo en el sistema.</p>
                                    <a href="/admin/centros/create" class="btn btn-create">
                                        <i class="fas fa-plus me-2"></i>Crear Primer Centro
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="/admin" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Volver al Admin
            </a>
            <a href="/dashboard" class="btn btn-outline-primary me-2">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a href="/admin/centros/create" class="btn btn-create">
                <i class="fas fa-plus me-2"></i>Nuevo Centro
            </a>
        </div>
    </div>
</div>

<script>
    // Búsqueda en tiempo real
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#centrosTable .centro-row');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Efecto hover en las tarjetas de estadísticas
    document.querySelectorAll('.stats-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Actualizar filtros activos basado en URL
    const urlParams = new URLSearchParams(window.location.search);
    const filtro = urlParams.get('filtro');
    
    if (filtro) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeBtn = document.querySelector(`a[href="/admin/centros?filtro=${filtro}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
</script>
<?php View::endSection(); ?>
