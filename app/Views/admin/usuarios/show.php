<?php
use App\Helpers\View;

View::startSection('content');
?>

<div class="container py-4" style="max-width: 1200px;">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-user me-2"></i>
                Detalle de Usuario
            </h1>
            <p class="text-muted mb-0">Información completa del usuario</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/admin/usuarios" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Volver a Usuarios
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Información del Usuario -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        Información Personal
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <div class="avatar-title bg-primary rounded-circle text-white fs-1">
                            <?php echo substr($usuario->azure_display_name ?? $usuario->azure_email ?? 'U', 0, 1); ?>
                        </div>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($usuario->azure_display_name ?? 'Sin nombre'); ?></h4>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($usuario->azure_email ?? ''); ?></p>
                    
                    <div class="d-flex justify-content-center mb-3">
                        <?php if ($usuario->is_admin): ?>
                            <span class="badge bg-danger me-1">Admin</span>
                        <?php endif; ?>
                        <?php if ($usuario->is_revisor): ?>
                            <span class="badge bg-warning me-1">Revisor</span>
                        <?php endif; ?>
                        <?php if ($usuario->is_autorizador): ?>
                            <span class="badge bg-info me-1">Autorizador</span>
                        <?php endif; ?>
                        <?php if ($usuario->activo): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </div>

                    <div class="text-start">
                        <p><strong>Departamento:</strong><br><?php echo htmlspecialchars($usuario->azure_department ?? 'No especificado'); ?></p>
                        <p><strong>Cargo:</strong><br><?php echo htmlspecialchars($usuario->azure_job_title ?? 'No especificado'); ?></p>
                        <p><strong>Último acceso:</strong><br><?php echo $usuario->last_login ? date('d/m/Y H:i', strtotime($usuario->last_login)) : 'Nunca'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas del Usuario -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Estadísticas de Requisiciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-primary"><?php echo $estadisticas['total'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Total Requisiciones</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-warning"><?php echo $estadisticas['pendientes'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Pendientes</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-success"><?php echo $estadisticas['autorizadas'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Autorizadas</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3 class="text-danger"><?php echo $estadisticas['rechazadas'] ?? 0; ?></h3>
                                <p class="text-muted mb-0">Rechazadas</p>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-center">
                                <h4 class="text-info">Q <?php echo number_format($estadisticas['monto_total'] ?? 0, 2); ?></h4>
                                <p class="text-muted mb-0">Monto Total</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <h4 class="text-success">Q <?php echo number_format($estadisticas['monto_mes_actual'] ?? 0, 2); ?></h4>
                                <p class="text-muted mb-0">Este Mes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Requisiciones del Usuario -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        Requisiciones Recientes
                    </h5>
                    <a href="/requisiciones?usuario=<?php echo $usuario->id; ?>" class="btn btn-sm btn-outline-primary">
                        Ver Todas
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        No hay requisiciones registradas
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php View::endSection(); ?>
