<?php 
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Autorizadores de Cuentas Contables';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%);
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
    
    .cuenta-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .cuenta-card:hover {
        border-color: #6f42c1;
        box-shadow: 0 6px 20px rgba(111, 66, 193, 0.2);
    }
    
    .cuenta-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 2px solid #6f42c1;
        padding: 1rem;
    }
    
    .cuenta-status {
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
    
    
    .cuenta-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #6f42c1;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 600;
        margin-right: 20px;
    }
    
    .btn-create {
        background: linear-gradient(135deg, #6f42c1, #5a2d91);
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
    }
    
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
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
        background: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }
    
    .filter-btn:hover {
        border-color: #6f42c1;
        color: #6f42c1;
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
    
    .cuenta-badge {
        background: #f3e5f5;
        color: #6f42c1;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
        margin: 2px;
        border: 1px solid #e1bee7;
    }
    
    .codigo-badge {
        background: #e8f5e8;
        color: #2e7d32;
        padding: 6px 12px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    
    .cuenta-detalle {
        background: #f8f9fa;
        border-left: 4px solid #6f42c1;
        padding: 12px;
        margin: 8px 0;
        border-radius: 0 8px 8px 0;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">
                    <i class="fas fa-calculator me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Gestiona autorizadores por cuenta contable específica</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="<?= url('/admin/autorizadores/cuentas-contables/create') ?>" class="btn btn-create">
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
                <i class="fas fa-calculator fa-3x text-purple mb-3" style="color: #6f42c1;"></i>
                <h3 class="mb-1"><?= count($autorizadores_cuenta_contable ?? []) ?></h3>
                <p class="text-muted mb-0">Total Autorizadores</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h3 class="mb-1" id="autorizadores-activos">0</h3>
                <p class="text-muted mb-0">Activos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-list-alt fa-3x text-primary mb-3"></i>
                <h3 class="mb-1" id="cuentas-cubiertas">0</h3>
                <p class="text-muted mb-0">Cuentas Cubiertas</p>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="btn-group" role="group">
                <a href="<?= url('/admin/autorizadores/cuentas-contables') ?>" class="btn filter-btn active">
                    Todos
                </a>
                <a href="<?= url('/admin/autorizadores/cuentas-contables?filtro=activos') ?>" class="btn filter-btn">
                    Activos
                </a>
                <a href="<?= url('/admin/autorizadores/cuentas-contables?filtro=inactivos') ?>" class="btn filter-btn">
                    Inactivos
                </a>
            </div>
        </div>
    </div>

    <!-- Lista de Autorizadores por Cuenta Contable -->
    <div id="autorizadoresContainer">
        <?php if (!empty($autorizadores_cuenta_contable)): ?>
            <?php foreach ($autorizadores_cuenta_contable as $autorizador): ?>
                <?php
                // Determinar el estado del autorizador
                $activo = $autorizador->activo ?? true;
                $identificadorPrimario = $autorizador->id ?? ($autorizador->registro_id ?? null);
                $identificadorFallback = $autorizador->email ?? '';
                $autorizadorSlug = rawurlencode((string)($identificadorPrimario ?? $identificadorFallback));
                
                $estado = 'activo';
                $estadoTexto = 'Activo';
                $estadoClass = 'badge-activo';
                
                if (!$activo) {
                    $estado = 'inactivo';
                    $estadoTexto = 'Inactivo';
                    $estadoClass = 'badge-inactivo';
                }
                ?>
                
                <div class="cuenta-card autorizador-item" data-estado="<?= $estado ?>">
                    <div class="cuenta-header position-relative">
                        <div class="cuenta-status">
                            <span class="<?= $estadoClass ?>"><?= $estadoTexto ?></span>
                        </div>
                        
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <div class="cuenta-avatar">
                                        <i class="fas fa-calculator"></i>
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
                                    <a href="<?= url('/admin/autorizadores/cuentas-contables/' . $autorizadorSlug) ?>"
                                       class="btn btn-outline-primary"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= url('/admin/autorizadores/cuentas-contables/' . $autorizadorSlug . '/edit') ?>"
                                       class="btn btn-outline-warning"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="<?= url('/admin/autorizadores/cuentas-contables/' . $autorizadorSlug) ?>"
                                          method="POST"
                                          style="display:inline;"
                                          onsubmit="return confirm('¿Estás seguro de eliminar este autorizador por cuenta contable? Esta acción no se puede deshacer.');">
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
                            <div class="col-md-12">
                                <strong><i class="fas fa-list-alt me-2"></i>Cuentas Contables Autorizadas:</strong>
                                <div class="mt-2">
                                    <?php if (!empty($autorizador->cuentas_contables)): ?>
                                        <?php 
                                        $cuentas = is_array($autorizador->cuentas_contables) 
                                            ? $autorizador->cuentas_contables 
                                            : json_decode($autorizador->cuentas_contables, true);
                                        if (!$cuentas) {
                                            $cuentas = [$autorizador->cuentas_contables]; // Fallback si no es JSON
                                        }
                                        ?>
                                        <?php foreach ($cuentas as $cuenta): ?>
                                            <div class="cuenta-detalle">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <?php if (is_array($cuenta)): ?>
                                                            <span class="codigo-badge"><?= View::e($cuenta['codigo'] ?? 'Sin código') ?></span>
                                                            <div class="mt-1">
                                                                <strong><?= View::e($cuenta['nombre'] ?? $cuenta['descripcion'] ?? 'Sin nombre') ?></strong>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="cuenta-badge"><?= View::e($cuenta) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="cuenta-detalle">
                                            <span class="text-muted">No hay cuentas contables asignadas</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <strong><i class="fas fa-infinity me-2"></i>Autorización:</strong>
                                <div class="mt-1">
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
                            <div class="col-md-6">
                                <?php if (!empty($autorizador->fecha_inicio)): ?>
                                <div>
                                    <strong><i class="fas fa-calendar-alt me-2"></i>Vigencia:</strong>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            Desde: <?= date('d/m/Y', strtotime($autorizador->fecha_inicio)) ?>
                                            <?php if (!empty($autorizador->fecha_fin)): ?>
                                                <br>Hasta: <?= date('d/m/Y', strtotime($autorizador->fecha_fin)) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
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
                    <i class="fas fa-calculator"></i>
                    <h4>No hay autorizadores por cuenta contable</h4>
                    <p class="mb-3">No se encontraron autorizadores configurados para cuentas contables específicas.</p>
                    <a href="<?= url('/admin/autorizadores/cuentas-contables/create') ?>" class="btn btn-create">
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
                        <a href="<?= url('/admin/autorizadores/respaldos') ?>" class="btn btn-outline-danger">
                            <i class="fas fa-hands-helping me-1"></i>Respaldos
                        </a>
                        <a href="<?= url('/admin/autorizadores/metodos-pago') ?>" class="btn btn-outline-info">
                            <i class="fas fa-credit-card me-1"></i>Métodos de Pago
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
            <a href="<?= url('/admin/autorizadores/cuentas-contables/create') ?>" class="btn btn-create">
                <i class="fas fa-plus me-2"></i>Nuevo Autorizador
            </a>
        </div>
    </div>
</div>

<script>
    // Calcular estadísticas
    document.addEventListener('DOMContentLoaded', function() {
        const autorizadores = document.querySelectorAll('.autorizador-item');
        let activos = 0, cuentasUnicas = new Set();
        
        autorizadores.forEach(item => {
            const estado = item.dataset.estado;
            switch(estado) {
                case 'activo':
                    activos++;
                    break;
            }
            
            // Contar cuentas únicas
            const cuentas = item.querySelectorAll('.codigo-badge, .cuenta-badge');
            cuentas.forEach(cuenta => {
                const texto = cuenta.textContent.trim();
                if (texto !== 'No hay cuentas contables asignadas') {
                    cuentasUnicas.add(texto);
                }
            });
        });
        
        document.getElementById('autorizadores-activos').textContent = activos;
        document.getElementById('cuentas-cubiertas').textContent = cuentasUnicas.size;
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
        
        const baseUrl = "<?= url('/admin/autorizadores/cuentas-contables?filtro=') ?>";
        const activeBtn = document.querySelector(`a[href="${baseUrl}${filtro}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
</script>
<?php View::endSection(); ?>