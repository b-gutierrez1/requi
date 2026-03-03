<?php 
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Autorizadores de Respaldo';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
    
    .respaldo-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .respaldo-card:hover {
        border-color: #ff6b6b;
        box-shadow: 0 6px 20px rgba(255, 107, 107, 0.2);
    }
    
    .respaldo-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 2px solid #ff6b6b;
        padding: 1rem;
    }
    
    .respaldo-status {
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
    
    .badge-vencido {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .badge-proximamente {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .respaldo-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #ff6b6b;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 600;
        margin-right: 20px;
    }
    
    .btn-create {
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }
    
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
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
        background: #ff6b6b;
        border-color: #ff6b6b;
        color: white;
    }
    
    .filter-btn:hover {
        border-color: #ff6b6b;
        color: #ff6b6b;
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
    
    .period-badge {
        background: #e3f2fd;
        color: #1565c0;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 500;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">
                    <i class="fas fa-hands-helping me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Gestiona los autorizadores de respaldo del sistema</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="<?= url('/admin/autorizadores/respaldos/create') ?>" class="btn btn-create">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Respaldo
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
                <i class="fas fa-hands-helping fa-3x text-danger mb-3"></i>
                <h3 class="mb-1"><?= count($respaldos ?? []) ?></h3>
                <p class="text-muted mb-0">Total Respaldos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h3 class="mb-1" id="respaldos-activos">0</h3>
                <p class="text-muted mb-0">Activos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                <h3 class="mb-1" id="respaldos-proximos">0</h3>
                <p class="text-muted mb-0">Por Vencer</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                <h3 class="mb-1" id="respaldos-vencidos">0</h3>
                <p class="text-muted mb-0">Vencidos</p>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="btn-group" role="group">
                <a href="<?= url('/admin/autorizadores/respaldos') ?>" class="btn filter-btn active">
                    Todos
                </a>
                <a href="<?= url('/admin/autorizadores/respaldos?filtro=activos') ?>" class="btn filter-btn">
                    Activos
                </a>
                <a href="<?= url('/admin/autorizadores/respaldos?filtro=proximos') ?>" class="btn filter-btn">
                    Por Vencer
                </a>
                <a href="<?= url('/admin/autorizadores/respaldos?filtro=vencidos') ?>" class="btn filter-btn">
                    Vencidos
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de Respaldos -->
    <div id="respaldosContainer">
        <?php if (!empty($respaldos)): ?>
            <?php foreach ($respaldos as $respaldo): ?>
                <?php
                // Determinar el estado del respaldo
                $fechaInicio = $respaldo->fecha_inicio ?? '';
                $fechaFin = $respaldo->fecha_fin ?? '';
                $fechaHoy = date('Y-m-d');
                
                $estado = 'inactivo';
                $estadoTexto = 'Inactivo';
                $estadoClass = 'badge-secondary';
                
                if ($fechaInicio && $fechaFin) {
                    if ($fechaHoy >= $fechaInicio && $fechaHoy <= $fechaFin) {
                        $estado = 'activo';
                        $estadoTexto = 'Activo';
                        $estadoClass = 'badge-activo';
                    } elseif ($fechaHoy > $fechaFin) {
                        $estado = 'vencido';
                        $estadoTexto = 'Vencido';
                        $estadoClass = 'badge-vencido';
                    } elseif ($fechaHoy < $fechaInicio) {
                        // Calcular días hasta el inicio
                        $diasHasta = ceil((strtotime($fechaInicio) - strtotime($fechaHoy)) / (60 * 60 * 24));
                        if ($diasHasta <= 7) {
                            $estado = 'proximo';
                            $estadoTexto = "Inicia en $diasHasta día" . ($diasHasta != 1 ? 's' : '');
                            $estadoClass = 'badge-proximamente';
                        }
                    }
                }
                ?>
                
                <div class="respaldo-card respaldo-item" data-estado="<?= $estado ?>">
                    <div class="respaldo-header position-relative">
                        <div class="respaldo-status">
                            <span class="<?= $estadoClass ?>"><?= $estadoTexto ?></span>
                        </div>
                        
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <div class="respaldo-avatar">
                                        <?= View::e(substr($respaldo->autorizador_respaldo_nombre ?? 'R', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">
                                            <strong>Respaldo:</strong> <?= View::e($respaldo->autorizador_respaldo_nombre ?? 'Sin nombre') ?>
                                        </h5>
                                        <p class="mb-1 text-muted">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?= View::e($respaldo->autorizador_respaldo_email ?? 'Sin email') ?>
                                        </p>
                                        <p class="mb-0 text-muted">
                                            <strong>Reemplaza a:</strong> 
                                            <?= View::e($respaldo->autorizador_principal_nombre ?? $respaldo->autorizador_principal_email ?? 'Sin especificar') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($respaldo->centro_costo_id): ?>
                                    <?php 
                                    $centro = array_filter($centros ?? [], function($c) use ($respaldo) { 
                                        return $c->id == $respaldo->centro_costo_id; 
                                    });
                                    $centro = reset($centro);
                                    ?>
                                    <?php if ($centro): ?>
                                        <div class="mb-2">
                                            <i class="fas fa-building text-primary me-1"></i>
                                            <strong><?= View::e($centro->nombre ?? 'Centro sin nombre') ?></strong>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/admin/autorizadores/respaldos/' . View::e($respaldo->id ?? '')) ?>"
                                       class="btn btn-outline-primary"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= url('/admin/autorizadores/respaldos/' . View::e($respaldo->id ?? '') . '/edit') ?>"
                                       class="btn btn-outline-warning"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="/admin/autorizadores/respaldos/<?= View::e($respaldo->id ?? '') ?>" 
                                          method="POST" 
                                          style="display: inline;"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este respaldo? Esta acción no se puede deshacer.')">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <?= CsrfMiddleware::field() ?>
                                        <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3 bg-white">
                        <div class="row">
                            <div class="col-md-6">
                                <strong><i class="fas fa-calendar-alt me-2"></i>Período de Respaldo:</strong>
                                <div class="mt-1">
                                    <?php if ($fechaInicio && $fechaFin): ?>
                                        <span class="period-badge">
                                            <?= date('d/m/Y', strtotime($fechaInicio)) ?> - <?= date('d/m/Y', strtotime($fechaFin)) ?>
                                        </span>
                                        <small class="d-block text-muted mt-1">
                                            <?php 
                                            $dias = ceil((strtotime($fechaFin) - strtotime($fechaInicio)) / (60 * 60 * 24));
                                            echo "$dias día" . ($dias != 1 ? 's' : '') . " de duración";
                                            ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">Fechas no especificadas</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php if (!empty($respaldo->motivo)): ?>
                                    <strong><i class="fas fa-comment me-2"></i>Motivo:</strong>
                                    <div class="mt-1">
                                        <em><?= View::e($respaldo->motivo) ?></em>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="empty-state">
                    <i class="fas fa-hands-helping"></i>
                    <h4>No hay respaldos configurados</h4>
                    <p class="mb-3">No se encontraron autorizadores de respaldo en el sistema.</p>
                    <a href="<?= url('/admin/autorizadores/respaldos/create') ?>" class="btn btn-create">
                        <i class="fas fa-plus me-2"></i>Crear Primer Respaldo
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
                        <a href="<?= url('/admin/autorizadores/metodos-pago') ?>" class="btn btn-outline-info">
                            <i class="fas fa-credit-card me-1"></i>Métodos de Pago
                        </a>
                        <a href="<?= url('/admin/autorizadores/cuentas-contables') ?>" class="btn btn-outline-secondary">
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
            <a href="<?= url('/admin/autorizadores') ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Volver a Autorizadores
            </a>
            <a href="<?= url('/admin') ?>" class="btn btn-outline-primary me-2">
                <i class="fas fa-home me-2"></i>Panel Admin
            </a>
            <a href="<?= url('/admin/autorizadores/respaldos/create') ?>" class="btn btn-create">
                <i class="fas fa-plus me-2"></i>Nuevo Respaldo
            </a>
        </div>
    </div>
</div>

<script>
    // Calcular estadísticas
    document.addEventListener('DOMContentLoaded', function() {
        const respaldos = document.querySelectorAll('.respaldo-item');
        let activos = 0, proximos = 0, vencidos = 0;
        
        respaldos.forEach(item => {
            const estado = item.dataset.estado;
            switch(estado) {
                case 'activo':
                    activos++;
                    break;
                case 'proximo':
                    proximos++;
                    break;
                case 'vencido':
                    vencidos++;
                    break;
            }
        });
        
        document.getElementById('respaldos-activos').textContent = activos;
        document.getElementById('respaldos-proximos').textContent = proximos;
        document.getElementById('respaldos-vencidos').textContent = vencidos;
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
        
        const activeBtn = document.querySelector(`a[href="<?= url('/admin/autorizadores/respaldos') ?>?filtro=${filtro}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
</script>
<?php View::endSection(); ?>