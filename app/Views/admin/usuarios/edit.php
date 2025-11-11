<?php
use App\Helpers\View;

View::startSection('content');
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2">
                <i class="fas fa-user-edit me-2"></i>
                Editar Usuario
            </h1>
            <p class="text-muted mb-0">Modificar información del usuario</p>
        </div>
    </div>

    <div class="row">
        <!-- Formulario Principal -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Información del Usuario
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/admin/usuarios/<?php echo $usuario_edit->id; ?>">
                        <?php echo \App\Middlewares\CsrfMiddleware::field(); ?>
                        
                        <div class="row">
                            <!-- Nombre -->
                            <div class="col-md-6 mb-3">
                                <label for="azure_display_name" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    Nombre Completo *
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="azure_display_name" 
                                       name="azure_display_name" 
                                       value="<?php echo htmlspecialchars($usuario_edit->azure_display_name ?? ''); ?>"
                                       required>
                                <div class="form-text">Nombre completo del usuario</div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6 mb-3">
                                <label for="azure_email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>
                                    Email *
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="azure_email" 
                                       name="azure_email" 
                                       value="<?php echo htmlspecialchars($usuario_edit->azure_email ?? ''); ?>"
                                       required>
                                <div class="form-text">Dirección de correo electrónico</div>
                            </div>
                        </div>

                        <!-- Roles y Permisos -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-shield-alt me-2"></i>
                                Roles y Permisos
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_admin" 
                                               name="is_admin" 
                                               <?php echo $usuario_edit->is_admin ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_admin">
                                            <i class="fas fa-crown text-danger me-1"></i>
                                            Administrador
                                        </label>
                                        <div class="form-text">Acceso completo al sistema</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_revisor" 
                                               name="is_revisor" 
                                               <?php echo $usuario_edit->is_revisor ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_revisor">
                                            <i class="fas fa-eye text-warning me-1"></i>
                                            Revisor
                                        </label>
                                        <div class="form-text">Puede revisar requisiciones</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="activo" 
                                               name="activo" 
                                               <?php echo $usuario_edit->activo ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="activo">
                                            <i class="fas fa-check-circle text-success me-1"></i>
                                            Activo
                                        </label>
                                        <div class="form-text">Usuario habilitado</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Guardar Cambios
                            </button>
                            <a href="/admin/usuarios" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Información Actual -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información Actual
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-lg mx-auto mb-2">
                            <div class="avatar-title bg-primary rounded-circle text-white fs-2">
                                <?php echo substr($usuario_edit->azure_display_name ?? $usuario_edit->azure_email ?? 'U', 0, 1); ?>
                            </div>
                        </div>
                        <h6 class="mb-1"><?php echo htmlspecialchars($usuario_edit->azure_display_name ?? 'Sin nombre'); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($usuario_edit->azure_email ?? ''); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <p class="mb-1"><strong>ID:</strong> <?php echo $usuario_edit->id; ?></p>
                        <p class="mb-1">
                            <strong>Estado:</strong> 
                            <?php if ($usuario_edit->activo): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </p>
                        <p class="mb-0">
                            <strong>Roles:</strong><br>
                            <?php if ($usuario_edit->is_admin): ?>
                                <span class="badge bg-danger me-1">Admin</span>
                            <?php endif; ?>
                            <?php if ($usuario_edit->is_revisor): ?>
                                <span class="badge bg-warning me-1">Revisor</span>
                            <?php endif; ?>
                            <?php if (!$usuario_edit->is_admin && !$usuario_edit->is_revisor): ?>
                                <span class="badge bg-info">Usuario</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Ayuda -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Ayuda
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>Administrador:</strong> Acceso completo al sistema, incluyendo panel de administración.</p>
                        <p><strong>Revisor:</strong> Puede revisar y aprobar requisiciones.</p>
                        <p><strong>Activo:</strong> El usuario puede iniciar sesión y usar el sistema.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php View::endSection(); ?>