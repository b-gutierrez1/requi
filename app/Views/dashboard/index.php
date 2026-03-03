<?php
use App\Helpers\View;
use App\Helpers\EstadoHelper;

// Forzar actualización del navegador
$version = time();

View::startSection('content');
?>

<!-- Versión: <?php echo $version; ?> -->

<style>
    .dashboard-hero {
        background: var(--gradient-accent);
        color: white;
        border-radius: 20px;
        padding: 3rem 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .dashboard-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    .stats-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border: none;
        transition: all 0.3s ease;
    }
    
    .stats-card:hover {
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .stats-icon.requisitions { background: var(--gradient-primary); }
    .stats-icon.pendientes { background: linear-gradient(135deg, var(--warning-color), #d97706); }
    .stats-icon.autorizadas { background: linear-gradient(135deg, var(--success-color), #16a34a); }
    
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .quick-action {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
        border: 2px solid transparent;
    }
    
    .quick-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        border-color: var(--primary-color);
        color: inherit;
        text-decoration: none;
    }
    
    .quick-action-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin: 0 auto 1rem;
        color: white;
        box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    }
    
    .quick-action-icon.create { 
        background: var(--gradient-primary);
    }
    .quick-action-icon.view { 
        background: linear-gradient(135deg, var(--info-color), #0284c7);
    }
    .quick-action-icon.authorize { 
        background: linear-gradient(135deg, var(--warning-color), #d97706);
    }
    .quick-action-icon.reports { 
        background: linear-gradient(135deg, var(--success-color), #16a34a);
    }
    .quick-action-icon.admin { 
        background: linear-gradient(135deg, var(--danger-color), #dc2626);
    }
    
    .recent-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .section-header {
        background: var(--gradient-accent);
        color: white;
        padding: 1.5rem 2rem;
        margin: 0;
    }
    
    .section-body {
        padding: 2rem;
    }
    
    .requisicion-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s ease;
    }
    
    .requisicion-item:hover {
        background: #f8f9fa;
        transform: translateX(5px);
    }
    
    .requisicion-item:last-child {
        border-bottom: none;
    }
    
    .requisicion-status {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .requisicion-status.pendiente { background: #f39c12; }
    .requisicion-status.autorizada { background: #27ae60; }
    .requisicion-status.rechazada { background: #e74c3c; }
    
    .requisicion-info {
        flex-grow: 1;
    }
    
    .requisicion-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .requisicion-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .requisicion-amount {
        font-weight: 700;
        color: var(--primary-color);
        margin-left: 1rem;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .chart-placeholder {
        height: 300px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 1.1rem;
    }
</style>

<div class="container py-4" style="max-width: 1200px;">
    <!-- Hero Section -->
    <div class="dashboard-hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 mb-3">
                    <i class="fas fa-tachometer-alt me-3"></i>
                    Bienvenido, <?php echo View::e($usuario['azure_display_name'] ?? $usuario['name'] ?? 'Usuario'); ?>
                </h1>
                <p class="lead mb-0">
                    Sistema de Gestión de Requisiciones de Compra - Panel de Control
                </p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex align-items-center justify-content-end">
                    <div class="me-3">
                        <div class="h4 mb-0"><?php echo date('d'); ?></div>
                        <div class="small"><?php echo date('M Y'); ?></div>
                    </div>
                    <div class="fs-1">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon requisitions">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3 class="text-primary"><?php echo $estadisticas['total_requisiciones'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Solicitudes Registradas</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon pendientes">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="text-warning"><?php echo $estadisticas['pendientes'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">En Proceso de Evaluación</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon autorizadas">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-success"><?php echo $estadisticas['autorizadas'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Solicitudes Aprobadas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="quick-actions">
        <a href="<?= url('/requisiciones/crear') ?>" class="quick-action">
            <div class="quick-action-icon create">
                <i class="fas fa-plus"></i>
            </div>
            <h6>Crear Requisición</h6>
            <p class="small text-muted mb-0">Registrar nueva solicitud de compra</p>
        </a>
        
        <a href="<?= url('/requisiciones') ?>" class="quick-action">
            <div class="quick-action-icon view">
                <i class="fas fa-eye"></i>
            </div>
            <h6>Consultar Requisiciones</h6>
            <p class="small text-muted mb-0">Revisar estado de solicitudes</p>
        </a>
        
        <a href="<?= url('/autorizaciones') ?>" class="quick-action">
            <div class="quick-action-icon authorize">
                <i class="fas fa-check-circle"></i>
            </div>
            <h6>Proceso de Autorización</h6>
            <p class="small text-muted mb-0">Evaluar solicitudes pendientes</p>
        </a>
        
        <?php if (isset($usuario) && ($usuario['is_admin'] ?? 0) == 1): ?>
        <a href="<?= url('/admin') ?>" class="quick-action">
            <div class="quick-action-icon admin">
                <i class="fas fa-cog"></i>
            </div>
            <h6>Panel Administrativo</h6>
            <p class="small text-muted mb-0">Gestión del sistema</p>
        </a>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Requisiciones Recientes -->
        <div class="col-md-8">
            <div class="recent-section">
                <div class="section-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Registro de Actividad Reciente
                    </h5>
                </div>
                <div class="section-body">
                    <?php if (!empty($requisiciones_recientes)): ?>
                        <?php foreach ($requisiciones_recientes as $req): ?>
                        <?php 
                            // ✅ USAR HELPER CENTRALIZADO PARA ESTADO
                            
                            if (is_object($req)) {
                                $reqId = $req->id;
                                $reqProveedor = $req->nombre_razon_social;
                                $reqFecha = $req->fecha;
                                $reqMonto = $req->monto_total;
                                $reqMoneda = $req->moneda ?? 'GTQ';
                                // Obtener estado real desde la orden de compra
                                $estado = $req->getEstadoReal();
                            } else {
                                $reqId = $req['id'];
                                $reqProveedor = $req['nombre_razon_social'];
                                $reqFecha = $req['fecha'];
                                $reqMonto = $req['monto_total'];
                                $reqMoneda = $req['moneda'] ?? 'GTQ';
                                // Obtener estado desde datos del array
                                $estado = EstadoHelper::getEstadoFromData($req);
                            }
                            
                            // Obtener badge info centralizado
                            $badgeInfo = EstadoHelper::getBadge($estado);
                            
                            // Normalizar para clase CSS (mantener compatibilidad)
                            $estadoClass = strtolower(str_replace('_', '-', $estado));
                            if (strpos($estadoClass, 'pendiente') !== false) {
                                $estadoClass = 'pendiente';
                            } elseif (strpos($estadoClass, 'autoriza') !== false) {
                                $estadoClass = 'autorizada';
                            } elseif (strpos($estadoClass, 'rechaz') !== false) {
                                $estadoClass = 'rechazada';
                            }
                        ?>
                        <div class="requisicion-item">
                            <div class="requisicion-status <?php echo $estadoClass; ?>"></div>
                            <div class="requisicion-info">
                                <div class="requisicion-title">
                                    Solicitud #<?php echo $reqId; ?>
                                </div>
                                <div class="requisicion-meta">
                                    <?php echo View::e($reqProveedor ?? 'Proveedor no especificado'); ?> • 
                                    <?php echo View::formatDate($reqFecha); ?>
                                </div>
                            </div>
                            <div class="requisicion-amount">
                                <?php echo View::money($reqMonto, $reqMoneda); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-3">
                            <a href="<?= url('/requisiciones') ?>" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>Consultar Registro Completo
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice"></i>
                            <h5>No hay actividad registrada</h5>
                            <p>Inicie el proceso registrando una nueva solicitud</p>
                            <a href="<?= url('/requisiciones/crear') ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Registrar Solicitud
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Revisiones Pendientes (solo para revisores) -->
            <?php if (($es_revisor ?? false) && !empty($revisiones_pendientes)): ?>
            <div class="recent-section">
                <div class="section-header">
                    <h5 class="mb-0">
                        <i class="fas fa-eye me-2"></i>
                        Pendientes de Aprobación
                    </h5>
                </div>
                <div class="section-body">
                    <?php foreach ($revisiones_pendientes as $revision): ?>
                    <div class="requisicion-item">
                        <div class="requisicion-status pendiente"></div>
                        <div class="requisicion-info">
                            <div class="requisicion-title">
                                Solicitud #<?php echo $revision['numero_requisicion'] ?? $revision['requisicion_id'] ?? 'N/A'; ?>
                            </div>
                            <div class="requisicion-meta">
                                <?php echo View::e($revision['nombre_razon_social'] ?? 'Proveedor no especificado'); ?>
                            </div>
                        </div>
                        <div class="requisicion-amount">
                            <?php echo View::money($revision['monto_total'] ?? 0, $revision['moneda'] ?? 'GTQ'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-3">
                        <a href="<?= url('/autorizaciones/revision') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-check me-2"></i>Aprobar Revisiones
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Autorizaciones Pendientes -->
            <?php if (!empty($autorizaciones_pendientes)): ?>
            <div class="recent-section">
                <div class="section-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Evaluaciones Pendientes
                    </h5>
                </div>
                <div class="section-body">
                    <?php foreach ($autorizaciones_pendientes as $auth): ?>
                    <div class="requisicion-item">
                        <div class="requisicion-status pendiente"></div>
                        <div class="requisicion-info">
                            <div class="requisicion-title">
                                Solicitud #<?php echo $auth['numero_requisicion'] ?? $auth['requisicion_id'] ?? 'N/A'; ?>
                            </div>
                            <div class="requisicion-meta">
                                <?php echo View::e($auth['nombre_razon_social'] ?? 'Proveedor no especificado'); ?>
                            </div>
                        </div>
                        <div class="requisicion-amount">
                            <?php echo View::money($auth['monto_total'] ?? 0, $auth['moneda'] ?? 'GTQ'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-3">
                        <a href="<?= url('/autorizaciones') ?>" class="btn btn-outline-warning">
                            <i class="fas fa-tasks me-2"></i>Procesar Evaluaciones
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gráfico de Estado -->
            <div class="chart-container">
                <h6 class="mb-3">
                    <i class="fas fa-chart-pie me-2"></i>
                    Análisis de Solicitudes
                </h6>
                <div class="chart-placeholder">
                    <div class="text-center">
                        <i class="fas fa-chart-pie fs-1 mb-3"></i>
                        <p>Gráfico de estados</p>
                        <small class="text-muted">Próximamente</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
View::endSection();

View::startSection('scripts');
?>
<script>
// Aplicar efectos de entrada suaves al dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('✨ Aplicando efectos suaves al dashboard...');
    
    // Hero section con animación especial
    const hero = document.querySelector('.dashboard-hero');
    if (hero) {
        hero.style.opacity = '0';
        hero.style.transform = 'scale(0.95) translateY(20px)';
        hero.style.transition = 'all 0.8s ease';
        
        setTimeout(() => {
            hero.style.opacity = '1';
            hero.style.transform = 'scale(1) translateY(0)';
        }, 100);
    }
    
    // Stats cards con entrada progresiva
    document.querySelectorAll('.stats-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.6s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 200 + (index * 100));
    });
    
    // Quick actions con entrada escalonada
    document.querySelectorAll('.quick-action').forEach((action, index) => {
        action.style.opacity = '0';
        action.style.transform = 'scale(0.9) translateY(20px)';
        action.style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            action.style.opacity = '1';
            action.style.transform = 'scale(1) translateY(0)';
        }, 600 + (index * 80));
    });
    
    // Recent sections con animación lateral
    document.querySelectorAll('.recent-section').forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateX(' + (index % 2 === 0 ? '-20px' : '20px') + ')';
        section.style.transition = 'all 0.6s ease';
        
        setTimeout(() => {
            section.style.opacity = '1';
            section.style.transform = 'translateX(0)';
        }, 1000 + (index * 200));
    });
    
    // Requisicion items con hover mejorado
    document.querySelectorAll('.requisicion-item').forEach(item => {
        item.style.transition = 'all 0.3s ease';
        
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(8px) scale(1.01)';
            this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0) scale(1)';
            this.style.boxShadow = '';
        });
    });
    
    console.log('🌟 Dashboard con efectos aplicados correctamente!');
});
</script>
<?php View::endSection(); ?>