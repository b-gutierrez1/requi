<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Gestión de Autorizadores';
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
        background: #e74c3c;
        border-color: #e74c3c;
        color: white;
    }
    
    .filter-btn:hover {
        border-color: #e74c3c;
        color: #e74c3c;
    }
    
    .search-box {
        border-radius: 25px;
        border: 2px solid #dee2e6;
        padding: 10px 20px;
        transition: all 0.3s ease;
    }
    
    .search-box:focus {
        border-color: #e74c3c;
        box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
    }
    
    .autorizadores-table {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid #e9ecef;
    }
    
    .autorizadores-table .table {
        margin: 0;
    }
    
    .autorizadores-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        border: none;
        padding: 15px 12px;
        font-size: 14px;
    }
    
    .autorizadores-table td {
        padding: 15px 12px;
        border-top: 1px solid #e9ecef;
        vertical-align: middle;
    }
    
    .autorizadores-table tbody tr:hover {
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
    
    .badge-tipo {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .badge-centro {
        background: #e3f2fd;
        color: #1565c0;
    }
    
    .badge-flujo {
        background: #f3e5f5;
        color: #7b1fa2;
    }
    
    .badge-cuenta {
        background: #e8f5e8;
        color: #2e7d32;
    }
    
    .badge-metodo {
        background: #fff3e0;
        color: #ef6c00;
    }
    
    .badge-respaldo {
        background: #fce4ec;
        color: #c2185b;
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
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        border: none;
        border-radius: 25px;
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
    
    .tipo-badge-container {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .centro-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .centro-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e74c3c;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">
                    <i class="fas fa-user-shield me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Administra los autorizadores del sistema</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="/admin/autorizadores/create" class="btn btn-create">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Autorizador
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
                <i class="fas fa-user-shield fa-3x text-danger mb-3"></i>
                <h3 class="mb-1"><?= count($autorizadores ?? []) ?></h3>
                <p class="text-muted mb-0">Total Autorizadores</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h3 class="mb-1"><?= count(array_filter($autorizadores ?? [], function($a) { return $a->activo ?? true; })) ?></h3>
                <p class="text-muted mb-0">Activos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                <h3 class="mb-1"><?= count(array_filter($autorizadores ?? [], function($a) { return !($a->activo ?? true); })) ?></h3>
                <p class="text-muted mb-0">Inactivos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-building fa-3x text-info mb-3"></i>
                <h3 class="mb-1"><?= count(array_unique(array_column(array_map(function($a) { return (array)$a; }, $autorizadores ?? []), 'centro_costo_id'))) ?></h3>
                <p class="text-muted mb-0">Centros Asignados</p>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="btn-group" role="group">
                <a href="/admin/autorizadores" class="btn filter-btn active">
                    Todos
                </a>
                <a href="/admin/autorizadores?filtro=activos" class="btn filter-btn">
                    Activos
                </a>
                <a href="/admin/autorizadores?filtro=inactivos" class="btn filter-btn">
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
                       placeholder="Buscar autorizador..." id="searchInput">
            </div>
        </div>
    </div>

    <!-- Tabla de Autorizadores -->
    <div class="autorizadores-table">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="autorizadoresTable">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="20%">Autorizador</th>
                        <th width="20%">Centro de Costo</th>
                        <th width="20%">Tipos</th>
                        <th width="10%">Estado</th>
                        <th width="15%">Límites</th>
                        <th width="10%" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($autorizadores)): ?>
                        <?php foreach ($autorizadores as $autorizador): ?>
                            <tr class="autorizador-row">
                                <td>
                                    <span class="badge bg-secondary">#<?= View::e($autorizador->id ?? 'N/A') ?></span>
                                </td>
                                <td>
                                    <div class="centro-info">
                                        <div class="centro-avatar">
                                            <?= View::e(substr($autorizador->nombre ?? 'A', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= View::e($autorizador->nombre ?? 'Sin nombre') ?></div>
                                            <small class="text-muted"><?= View::e($autorizador->email ?? 'Sin email') ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($autorizador->centro_costo_id)): ?>
                                        <?php 
                                        $centro = array_filter($centros ?? [], function($c) use ($autorizador) { 
                                            return $c->id == $autorizador->centro_costo_id; 
                                        });
                                        $centro = reset($centro);
                                        ?>
                                        <?php if ($centro): ?>
                                            <div class="fw-bold"><?= View::e($centro->nombre ?? 'Sin nombre') ?></div>
                                            <small class="text-muted"><?= View::e($centro->codigo ?? 'Sin código') ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Centro no encontrado</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="tipo-badge-container">
                                        <?php if ($autorizador->puede_autorizar_centro_costo ?? false): ?>
                                            <span class="badge badge-tipo badge-centro">Centro</span>
                                        <?php endif; ?>
                                        <?php if ($autorizador->puede_autorizar_flujo ?? false): ?>
                                            <span class="badge badge-tipo badge-flujo">Flujo</span>
                                        <?php endif; ?>
                                        <?php if ($autorizador->puede_autorizar_cuenta_contable ?? false): ?>
                                            <span class="badge badge-tipo badge-cuenta">Cuenta</span>
                                        <?php endif; ?>
                                        <?php if ($autorizador->puede_autorizar_metodo_pago ?? false): ?>
                                            <span class="badge badge-tipo badge-metodo">Método</span>
                                        <?php endif; ?>
                                        <?php if ($autorizador->puede_autorizar_respaldo ?? false): ?>
                                            <span class="badge badge-tipo badge-respaldo">Respaldo</span>
                                        <?php endif; ?>
                                        <?php if (!($autorizador->puede_autorizar_centro_costo ?? false) && 
                                                 !($autorizador->puede_autorizar_flujo ?? false) && 
                                                 !($autorizador->puede_autorizar_cuenta_contable ?? false) && 
                                                 !($autorizador->puede_autorizar_metodo_pago ?? false) && 
                                                 !($autorizador->puede_autorizar_respaldo ?? false)): ?>
                                            <span class="badge badge-tipo text-muted">Sin permisos</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($autorizador->activo ?? true): ?>
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
                                    <?php if (!empty($autorizador->monto_limite)): ?>
                                        <div class="fw-bold text-success">Q <?= number_format($autorizador->monto_limite, 2) ?></div>
                                        <small class="text-muted">Límite</small>
                                    <?php else: ?>
                                        <span class="text-muted">Sin límite</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/autorizadores/<?= View::e($autorizador->id) ?>"
                                           class="btn btn-outline-primary btn-action"
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/admin/autorizadores/<?= View::e($autorizador->id) ?>/edit"
                                           class="btn btn-outline-warning btn-action"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/admin/autorizadores/<?= View::e($autorizador->id) ?>/delete"
                                           class="btn btn-outline-danger btn-action"
                                           title="Eliminar"
                                           onclick="return confirm('¿Estás seguro de eliminar este autorizador?')">
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
                                    <i class="fas fa-user-shield"></i>
                                    <h4>No hay autorizadores</h4>
                                    <p class="mb-3">No se encontraron autorizadores en el sistema.</p>
                                    <a href="/admin/autorizadores/create" class="btn btn-create">
                                        <i class="fas fa-plus me-2"></i>Crear Primer Autorizador
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
            <a href="/admin/autorizadores/create" class="btn btn-create">
                <i class="fas fa-plus me-2"></i>Nuevo Autorizador
            </a>
        </div>
    </div>
</div>

<script>
    // Búsqueda en tiempo real
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#autorizadoresTable .autorizador-row');

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
        
        const activeBtn = document.querySelector(`a[href="/admin/autorizadores?filtro=${filtro}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
</script>
<?php View::endSection(); ?>
