<?php
use App\Helpers\View;
use App\Middlewares\CsrfMiddleware;

View::startSection('content');
?>

<style>
    /* Efectos suaves adicionales para admin */
    .admin-card, .stats-card, .quick-action-card, .admin-section {
        border-radius: 16px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    
    .btn, .action-btn {
        border-radius: 25px !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
    }
    
    .btn:hover, .action-btn:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 16px rgba(0,0,0,0.15) !important;
    }
    
    .badge {
        border-radius: 15px !important;
        padding: 6px 12px !important;
    }
    
    .table-admin {
        border-radius: 12px !important;
        overflow: hidden !important;
    }

    .admin-card {
        background: var(--gradient-accent);
        color: white;
        border: none;
        border-radius: 16px !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .admin-card:hover {
        transform: translateY(-5px);
    }
    
    .admin-card .card-body {
        padding: 2rem;
    }
    
    .admin-card .card-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }
    
    .admin-card .card-text {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .admin-card .card-subtitle {
        opacity: 0.9;
        font-size: 0.9rem;
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
    
    .stats-icon.users { background: var(--gradient-primary); }
    .stats-icon.centers { background: linear-gradient(135deg, var(--success-color), #16a34a); }
    .stats-icon.requisitions { background: linear-gradient(135deg, var(--info-color), #0284c7); }
    .stats-icon.authorizations { background: linear-gradient(135deg, var(--warning-color), #d97706); }
    
    .admin-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
        overflow: hidden;
    }
    
    .admin-section .section-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        margin: 0;
    }
    
    .admin-section .section-body {
        padding: 2rem;
    }
    
    .action-btn {
        border-radius: 25px;
        padding: 0.5rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .btn-primary.action-btn {
        background: linear-gradient(45deg, #2c3e50, #34495e);
    }
    
    .btn-success.action-btn {
        background: linear-gradient(45deg, #27ae60, #229954);
    }
    
    .btn-warning.action-btn {
        background: linear-gradient(45deg, #f39c12, #e67e22);
    }
    
    .btn-danger.action-btn {
        background: linear-gradient(45deg, #e74c3c, #c0392b);
    }
    
    .table-admin {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .table-admin thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .table-admin tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .quick-action-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .quick-action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        border-color: #667eea;
    }
    
    .quick-action-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 1rem;
        color: white;
    }
    
    .quick-action-icon.create { background: linear-gradient(45deg, #2c3e50, #34495e); }
    .quick-action-icon.view { background: linear-gradient(45deg, #8e44ad, #9b59b6); }
    .quick-action-icon.manage { background: linear-gradient(45deg, #27ae60, #229954); }
    .quick-action-icon.reports { background: linear-gradient(45deg, #3498db, #2980b9); }
    .quick-action-icon.settings { background: linear-gradient(45deg, #f39c12, #e67e22); }
    .quick-action-icon.email { background: linear-gradient(45deg, #e74c3c, #c0392b); }
</style>

<div class="container py-4" style="max-width: 1200px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="admin-card">
                <div class="card-body text-center">
                    <h1 class="card-title">
                        <i class="fas fa-cogs me-3"></i>
                        Panel de Administración
                    </h1>
                    <p class="card-subtitle mb-0">
                        Gestiona usuarios, centros de costo, autorizadores y configuración del sistema
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="text-primary"><?php echo $stats['total_usuarios'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Usuarios Activos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon centers">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3 class="text-success"><?php echo $stats['total_centros'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Centros de Costo</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon requisitions">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3 class="text-info"><?php echo $stats['total_requisiciones'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Requisiciones</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="card-body text-center">
                    <div class="stats-icon authorizations">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-warning"><?php echo $stats['autorizaciones_pendientes'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Pendientes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="quick-actions">
        <div class="quick-action-card">
            <div class="quick-action-icon create">
                <i class="fas fa-user-plus"></i>
            </div>
            <h5>Gestionar Usuarios</h5>
            <p class="text-muted">Crear, editar y asignar roles a usuarios del sistema</p>
            <a href="<?= url('/admin/usuarios') ?>" class="btn btn-primary action-btn">
                <i class="fas fa-users me-2"></i>Ver Usuarios
            </a>
        </div>
        
        <div class="quick-action-card">
            <div class="quick-action-icon view">
                <i class="fas fa-chart-line"></i>
            </div>
            <h5>Seguimiento de Requisiciones</h5>
            <p class="text-muted">Monitorear flujo completo paso a paso con historial detallado</p>
            <a href="<?= url('/admin/requisiciones') ?>" class="btn btn-info action-btn">
                <i class="fas fa-chart-line me-2"></i>Ver Timeline
            </a>
        </div>
        
        <div class="quick-action-card">
            <div class="quick-action-icon manage">
                <i class="fas fa-building"></i>
            </div>
            <h5>Centros de Costo</h5>
            <p class="text-muted">Administrar centros de costo del sistema</p>
            <a href="<?= url('/admin/catalogos?tipo=centros') ?>" class="btn btn-success action-btn">
                <i class="fas fa-building me-2"></i>Gestionar
            </a>
        </div>
        
        <div class="quick-action-card">
            <div class="quick-action-icon" style="background: linear-gradient(45deg, #6f42c1, #9b59b6);">
                <i class="fas fa-calculator"></i>
            </div>
            <h5>Cuentas Contables</h5>
            <p class="text-muted">Administrar catálogo de cuentas contables</p>
            <a href="<?= url('/admin/catalogos?tipo=cuentas') ?>" class="btn action-btn" style="background: linear-gradient(45deg, #6f42c1, #9b59b6); color: white;">
                <i class="fas fa-calculator me-2"></i>Gestionar
            </a>
        </div>
        
        <div class="quick-action-card">
            <div class="quick-action-icon" style="background: linear-gradient(45deg, #17a2b8, #20c997);">
                <i class="fas fa-project-diagram"></i>
            </div>
            <h5>Relaciones</h5>
            <p class="text-muted">Ver mapeo Centro Costo → Unidad Negocio → Factura</p>
            <a href="<?= url('/admin/relaciones') ?>" class="btn action-btn" style="background: linear-gradient(45deg, #17a2b8, #20c997); color: white;">
                <i class="fas fa-project-diagram me-2"></i>Ver Mapeo
            </a>
        </div>
        
        <div class="quick-action-card">
            <div class="quick-action-icon reports">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h5>Reportes</h5>
            <p class="text-muted">Generar reportes y estadísticas del sistema</p>
            <a href="<?= url('/admin/reportes') ?>" class="btn btn-warning action-btn">
                <i class="fas fa-file-alt me-2"></i>Ver Reportes
            </a>
        </div>
        
        <div class="quick-action-card">
            <div class="quick-action-icon settings">
                <i class="fas fa-cog"></i>
            </div>
            <h5>Autorizadores</h5>
            <p class="text-muted">Gestionar autorizadores generales y especiales</p>
            <div class="btn-group-vertical w-100" role="group">
                <a href="<?= url('/admin/autorizadores') ?>" class="btn btn-danger action-btn mb-1">
                    <i class="fas fa-shield-alt me-2"></i>Generales
                </a>
                <div class="btn-group" role="group">
                    <a href="<?= url('/admin/autorizadores/respaldos') ?>" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-hands-helping me-1"></i>Respaldos
                    </a>
                    <a href="<?= url('/admin/autorizadores/metodos-pago') ?>" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-credit-card me-1"></i>M. Pago
                    </a>
                    <a href="<?= url('/admin/autorizadores/cuentas-contables') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-calculator me-1"></i>Cuentas
                    </a>
                </div>
            </div>
        </div>
        
        <div class="quick-action-card">
            <div class="quick-action-icon email">
                <i class="fas fa-envelope"></i>
            </div>
            <h5>Configuración de Correo</h5>
            <p class="text-muted">Gestionar servidor SMTP y plantillas de correo electrónico</p>
            <a href="<?= url('/admin/email') ?>" class="btn btn-danger action-btn">
                <i class="fas fa-envelope me-2"></i>Configurar Correo
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Gestión de Usuarios -->
        <div class="col-md-6">
            <div class="admin-section">
                <div class="section-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Usuarios Recientes
                    </h5>
                </div>
                <div class="section-body">
                    <?php if (!empty($usuarios_recientes)): ?>
                    <div class="table-responsive">
                        <table class="table table-admin">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios_recientes as $usuario): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <?php echo strtoupper(substr($usuario->nombre ?? 'U', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo View::e($usuario->nombre ?? 'Sin nombre'); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo View::e($usuario->email); ?></td>
                                    <td>
                                        <?php if ($usuario->is_admin): ?>
                                            <span class="badge bg-danger">Admin</span>
                                        <?php elseif ($usuario->is_revisor): ?>
                                            <span class="badge bg-warning">Revisor</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Usuario</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usuario->activo): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-users fs-1 mb-3"></i>
                        <p>No hay usuarios registrados</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="<?= url('/admin/usuarios') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-2"></i>Ver Todos los Usuarios
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Autorizaciones Pendientes -->
        <div class="col-md-6">
            <div class="admin-section">
                <div class="section-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Autorizaciones Pendientes
                    </h5>
                </div>
                <div class="section-body">
                    <?php if (!empty($autorizaciones_pendientes)): ?>
                    <div class="table-responsive">
                        <table class="table table-admin">
                            <thead>
                                <tr>
                                    <th>Requisición</th>
                                    <th>Proveedor</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($autorizaciones_pendientes as $auth): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $auth['orden']->id; ?></strong>
                                    </td>
                                    <td><?php echo View::e($auth['orden']->nombre_razon_social ?? 'N/A'); ?></td>
                                    <td>
                                        <strong class="text-primary">
                                            <?php echo View::money($auth['orden']->monto_total, $auth['orden']->moneda ?? 'GTQ'); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-hourglass-half me-1"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $auth['flujo']->estado ?? 'pendiente')); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fs-1 mb-3 text-success"></i>
                        <p>¡Excelente! No hay autorizaciones pendientes</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="<?= url('/autorizaciones') ?>" class="btn btn-outline-warning">
                            <i class="fas fa-tasks me-2"></i>Ver Autorizaciones
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="row">
        <div class="col-md-12">
            <div class="admin-section">
                <div class="section-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Actividad Reciente del Sistema
                    </h5>
                </div>
                <div class="section-body">
                    <?php if (!empty($actividad_reciente)): ?>
                    <div class="timeline">
                        <?php foreach ($actividad_reciente as $actividad): ?>
                        <div class="timeline-item d-flex mb-3">
                            <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="fas fa-<?php echo $actividad['icono'] ?? 'info'; ?>"></i>
                            </div>
                            <div class="timeline-content flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo View::e($actividad['descripcion']); ?></h6>
                                        <p class="text-muted mb-0 small">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo View::e($actividad['usuario_nombre'] ?? 'Sistema'); ?>
                                        </p>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo View::formatDate($actividad['fecha']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history fs-1 mb-3"></i>
                        <p>No hay actividad reciente</p>
                    </div>
                    <?php endif; ?>
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
// Aplicar efectos suaves al panel de admin
document.addEventListener('DOMContentLoaded', function() {
    console.log('✨ Aplicando efectos suaves al panel de administración...');
    
    // Header principal con animación especial
    const adminCard = document.querySelector('.admin-card');
    if (adminCard) {
        adminCard.style.opacity = '0';
        adminCard.style.transform = 'scale(0.95) translateY(30px)';
        adminCard.style.transition = 'all 0.8s ease';
        
        setTimeout(() => {
            adminCard.style.opacity = '1';
            adminCard.style.transform = 'scale(1) translateY(0)';
        }, 100);
    }
    
    // Stats cards con entrada progresiva
    document.querySelectorAll('.stats-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px) scale(0.95)';
        card.style.transition = 'all 0.6s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
        }, 200 + (index * 100));
    });
    
    // Quick action cards con entrada escalonada
    document.querySelectorAll('.quick-action-card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'scale(0.9) translateY(20px)';
        card.style.transition = 'all 0.6s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'scale(1) translateY(0)';
        }, 600 + (index * 120));
    });
    
    // Admin sections con animación lateral
    document.querySelectorAll('.admin-section').forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateX(' + (index % 2 === 0 ? '-30px' : '30px') + ')';
        section.style.transition = 'all 0.7s ease';
        
        setTimeout(() => {
            section.style.opacity = '1';
            section.style.transform = 'translateX(0)';
        }, 1000 + (index * 200));
    });
    
    // Efectos hover mejorados para filas de tabla
    document.querySelectorAll('.table-admin tbody tr').forEach(row => {
        row.style.transition = 'all 0.3s ease';
        
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02) translateX(4px)';
            this.style.backgroundColor = '#f8faff';
            this.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.1)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) translateX(0)';
            this.style.backgroundColor = '';
            this.style.boxShadow = '';
        });
    });
    
    // Efectos para elementos del timeline
    document.querySelectorAll('.timeline-item').forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        item.style.transition = 'all 0.5s ease';
        
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 1500 + (index * 100));
        
        // Hover effect
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
            this.style.backgroundColor = 'rgba(102, 126, 234, 0.05)';
            this.style.borderRadius = '12px';
            this.style.padding = '0.5rem';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
            this.style.backgroundColor = '';
            this.style.padding = '';
        });
    });
    
    // Efectos adicionales para badges
    document.querySelectorAll('.badge').forEach(badge => {
        badge.style.transition = 'all 0.3s ease';
        
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    console.log('🌟 Panel de admin con efectos aplicados correctamente!');
});
</script>
<?php View::endSection(); ?>