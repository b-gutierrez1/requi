<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Autorizadores de Métodos de Pago';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
    
    .metodo-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .metodo-card:hover {
        border-color: #17a2b8;
        box-shadow: 0 6px 20px rgba(23, 162, 184, 0.2);
    }
    
    .metodo-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 2px solid #17a2b8;
        padding: 1rem;
    }
    
    .metodo-status {
        position: absolute;
        top: 15px;
        right: 15px;
    }
    
    .badge-activo {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .badge-inactivo {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
    }
    
    
    .metodo-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #17a2b8;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 600;
        margin-right: 20px;
    }
    
    .btn-create {
        background: linear-gradient(135deg, #17a2b8, #138496);
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
    }
    
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
        color: white;
    }
    
    .filter-btn {
        border-radius: 25px;
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 2px solid #dee2e6;
    }
    
    .filter-btn.active {
        background: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }
    
    .filter-btn:hover {
        border-color: #17a2b8;
        color: #17a2b8;
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
    
.metodo-badge {
        background: #e3f2fd;
        color: #1565c0;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
        margin: 2px;
    }
    
</style>

<?php
$totalAutorizadores = count($autorizadores_metodo_pago ?? []);
$autorizadoresActivos = 0;
$metodosUnicos = [];

if (!empty($autorizadores_metodo_pago)) {
    foreach ($autorizadores_metodo_pago as $autorizador) {
        $activo = $autorizador->activo ?? true;
        if ($activo) {
            $autorizadoresActivos++;
        }

        $listaMetodos = [];
        if (!empty($autorizador->metodos_pago)) {
            $listaMetodos = is_string($autorizador->metodos_pago)
                ? explode(',', $autorizador->metodos_pago)
                : (array)$autorizador->metodos_pago;
        }

        foreach ($listaMetodos as $metodo) {
            $clave = trim($metodo);
            if ($clave !== '') {
                $metodosUnicos[$clave] = true;
            }
        }
    }
}

$metodosCubiertos = count($metodosUnicos);
?>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">
                    <i class="fas fa-credit-card me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Gestiona autorizadores por método de pago específico</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="/admin/autorizadores/metodos-pago/create" class="btn btn-create">
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
                <i class="fas fa-credit-card fa-3x text-info mb-3"></i>
                <h3 class="mb-1"><?= $totalAutorizadores ?></h3>
                <p class="text-muted mb-0">Total Autorizadores</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h3 class="mb-1" id="autorizadores-activos"><?= $autorizadoresActivos ?></h3>
                <p class="text-muted mb-0">Activos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-money-bill-wave fa-3x text-primary mb-3"></i>
                <h3 class="mb-1" id="metodos-cubiertos"><?= $metodosCubiertos ?></h3>
                <p class="text-muted mb-0">Métodos Cubiertos</p>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="btn-group" role="group">
                <a href="/admin/autorizadores/metodos-pago" class="btn filter-btn active">
                    Todos
                </a>
                <a href="/admin/autorizadores/metodos-pago?filtro=activos" class="btn filter-btn">
                    Activos
                </a>
                <a href="/admin/autorizadores/metodos-pago?filtro=inactivos" class="btn filter-btn">
                    Inactivos
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de Autorizadores por Método de Pago -->
    <div id="autorizadoresContainer">
        <?php if (!empty($autorizadores_metodo_pago)): ?>
            <?php foreach ($autorizadores_metodo_pago as $autorizador): ?>
                <?php
                // Determinar el estado del autorizador
                $activo = $autorizador->activo ?? true;
                
                $estado = 'activo';
                $estadoTexto = 'Activo';
                $estadoClass = 'badge-activo';
                
                if (!$activo) {
                    $estado = 'inactivo';
                    $estadoTexto = 'Inactivo';
                    $estadoClass = 'badge-inactivo';
                }
                ?>
                
                <div class="metodo-card autorizador-item" data-estado="<?= $estado ?>">
                    <div class="metodo-header position-relative">
                        <div class="metodo-status">
                            <span class="<?= $estadoClass ?>"><?= $estadoTexto ?></span>
                        </div>
                        
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <div class="metodo-avatar">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">
                                            <strong><?= View::e($autorizador->nombre ?? 'Sin nombre') ?></strong>
                                        </h5>
                                        <p class="mb-1 text-muted">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?= View::e($autorizador->email ?? 'Sin email') ?>
                                        </p>
                                        <?php if (!empty($autorizador->cargo)): ?>
                                        <p class="mb-0 text-muted">
                                            <i class="fas fa-briefcase me-1"></i>
                                            <?= View::e($autorizador->cargo) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group btn-group-sm">
                                    <?php 
                                    // Usar email codificado para las rutas, ya que el controlador espera email
                                    // Esto funciona independientemente del usuario actual
                                    $paramUrl = urlencode($autorizador->email ?? $autorizador->id ?? '');
                                    ?>
                                    <a href="/admin/autorizadores/metodos-pago/<?= $paramUrl ?>"
                                       class="btn btn-outline-primary"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/admin/autorizadores/metodos-pago/<?= $paramUrl ?>/edit"
                                       class="btn btn-outline-warning"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="/admin/autorizadores/metodos-pago/<?= $paramUrl ?>/delete"
                                       class="btn btn-outline-danger"
                                       title="Eliminar"
                                       onclick="return confirm('¿Estás seguro de eliminar este autorizador?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-white">
                        <div class="row">
                            <div class="col-md-6">
                                <strong><i class="fas fa-credit-card me-2"></i>Métodos de Pago Autorizados:</strong>
                                <div class="mt-2">
                                    <?php if (!empty($autorizador->metodos_pago)): ?>
                                        <?php 
                                        $metodos = is_string($autorizador->metodos_pago) 
                                            ? explode(',', $autorizador->metodos_pago) 
                                            : $autorizador->metodos_pago;
                                        ?>
                                        <?php foreach ($metodos as $metodo): ?>
                                            <span class="metodo-badge">
                                                <?php
                                                $metodosTexto = [
                                                    'efectivo' => 'Efectivo',
                                                    'transferencia' => 'Transferencia Bancaria',
                                                    'cheque' => 'Cheque',
                                                    'tarjeta' => 'Tarjeta de Crédito',
                                                    'tarjeta_credito' => 'Tarjeta de Crédito',
                                                    'tarjeta_debito' => 'Tarjeta de Débito',
                                                    'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito (Lic. Milton)',
                                                    'deposito' => 'Depósito Bancario',
                                                    'otro' => 'Otro'
                                                ];
                                                $metodoKey = trim($metodo);
                                                echo View::e($metodosTexto[$metodoKey] ?? $metodoKey);
                                                ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No especificados</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <strong><i class="fas fa-infinity me-2"></i>Autorización:</strong>
                                <div class="mt-2">
                                    <span class="badge bg-success">Sin límite de monto</span>
                                </div>
                                
                                <?php if (!empty($autorizador->centros_costo_count)): ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-building me-1"></i>
                                        <?= $autorizador->centros_costo_count ?> centro(s) de costo asignado(s)
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($autorizador->observaciones)): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="border-top pt-2">
                                    <strong><i class="fas fa-sticky-note me-2"></i>Observaciones:</strong>
                                    <div class="mt-1">
                                        <em><?= View::e($autorizador->observaciones) ?></em>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <h4>No hay autorizadores por método de pago</h4>
                    <p class="mb-3">No se encontraron autorizadores configurados para métodos de pago específicos.</p>
                    <a href="/admin/autorizadores/metodos-pago/create" class="btn btn-create">
                        <i class="fas fa-plus me-2"></i>Crear Primer Autorizador
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Navegación entre Autorizadores Especiales -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Otros Autorizadores Especiales:</h6>
                    <div class="btn-group" role="group">
                        <a href="/admin/autorizadores/respaldos" class="btn btn-outline-danger">
                            <i class="fas fa-hands-helping me-1"></i>Respaldos
                        </a>
                        <a href="/admin/autorizadores/cuentas-contables" class="btn btn-outline-secondary">
                            <i class="fas fa-calculator me-1"></i>Cuentas Contables
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="/admin/autorizadores" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Volver a Autorizadores
            </a>
            <a href="/admin" class="btn btn-outline-primary me-2">
                <i class="fas fa-home me-2"></i>Panel Admin
            </a>
            <a href="/admin/autorizadores/metodos-pago/create" class="btn btn-create">
                <i class="fas fa-plus me-2"></i>Nuevo Autorizador
            </a>
        </div>
    </div>
</div>

<script>
    // Calcular estadísticas
    document.addEventListener('DOMContentLoaded', function() {
        const autorizadores = document.querySelectorAll('.autorizador-item');
        let activos = 0, metodosUnicos = new Set();
        
        autorizadores.forEach(item => {
            const estado = item.dataset.estado;
            switch(estado) {
                case 'activo':
                    activos++;
                    break;
            }
            
            // Contar métodos únicos
            const metodos = item.querySelectorAll('.metodo-badge');
            metodos.forEach(metodo => {
                metodosUnicos.add(metodo.textContent.trim());
            });
        });
        
        document.getElementById('autorizadores-activos').textContent = activos;
        document.getElementById('metodos-cubiertos').textContent = metodosUnicos.size;
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
        
        const activeBtn = document.querySelector(`a[href="/admin/autorizadores/metodos-pago?filtro=${filtro}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
</script>
<?php View::endSection(); ?>