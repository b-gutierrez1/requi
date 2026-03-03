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
                    <form method="POST" action="<?= url('/admin/usuarios/' . $usuario_edit->id) ?>">
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
                                <!-- Administrador -->
                                <div class="col-12">
                                    <div class="clean-field" id="adminField">
                                        <div class="field-content">
                                            <div class="field-label">
                                                <i class="fas fa-crown text-danger me-2"></i>
                                                Administrador
                                            </div>
                                            <div class="field-description">Acceso completo al sistema, configuraciones y gestión de usuarios</div>
                                        </div>
                                        <div class="modern-switch">
                                            <input type="checkbox" id="is_admin" name="is_admin" value="1"
                                                   <?php echo $usuario_edit->is_admin ? 'checked' : ''; ?>>
                                            <span class="modern-switch-track" onclick="toggleModernUserRole('admin', this)"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Revisor -->
                                <div class="col-12">
                                    <div class="clean-field" id="revisorField">
                                        <div class="field-content">
                                            <div class="field-label">
                                                <i class="fas fa-eye text-warning me-2"></i>
                                                Revisor
                                            </div>
                                            <div class="field-description">Puede revisar, evaluar y aprobar requisiciones pendientes</div>
                                        </div>
                                        <div class="modern-switch">
                                            <input type="checkbox" id="is_revisor" name="is_revisor" value="1"
                                                   <?php echo $usuario_edit->is_revisor ? 'checked' : ''; ?>>
                                            <span class="modern-switch-track" onclick="toggleModernUserRole('revisor', this)"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                
                                <!-- Autorizador -->
                                <div class="col-12">
                                    <div class="clean-field" id="autorizadorField">
                                        <div class="field-content">
                                            <div class="field-label">
                                                <i class="fas fa-check-double text-success me-2"></i>
                                                Autorizador
                                            </div>
                                            <div class="field-description">Puede autorizar requisiciones y compras según su centro de costo</div>
                                        </div>
                                        <div class="modern-switch">
                                            <input type="checkbox" id="is_autorizador" name="is_autorizador" value="1"
                                                   <?php echo $usuario_edit->is_autorizador ? 'checked' : ''; ?>>
                                            <span class="modern-switch-track" onclick="toggleModernUserRole('autorizador', this)"></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Usuario Activo -->
                                <div class="col-12">
                                    <div class="clean-field" id="activoField">
                                        <div class="field-content">
                                            <div class="field-label">
                                                <i class="fas fa-power-off text-primary me-2"></i>
                                                Estado del Usuario
                                            </div>
                                            <div class="field-description">Controla si el usuario puede acceder e interactuar con el sistema</div>
                                        </div>
                                        <div class="modern-switch">
                                            <input type="checkbox" id="activo" name="activo" value="1"
                                                   <?php echo $usuario_edit->activo ? 'checked' : ''; ?>>
                                            <span class="modern-switch-track" onclick="toggleModernUserRole('activo', this)"></span>
                                        </div>
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
                            <a href="<?= url('/admin/usuarios') ?>" class="btn btn-secondary">
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
                            <?php if ($usuario_edit->is_autorizador): ?>
                                <span class="badge bg-info me-1">Autorizador</span>
                            <?php endif; ?>
                            <?php if (!$usuario_edit->is_admin && !$usuario_edit->is_revisor && !$usuario_edit->is_autorizador): ?>
                                <span class="badge bg-secondary">Usuario</span>
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
                        <p><strong>Autorizador:</strong> Puede autorizar requisiciones por centro de costo.</p>
                        <p><strong>Activo:</strong> El usuario puede iniciar sesión y usar el sistema.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Inicializar estado de todos los switches modernos
    document.addEventListener('DOMContentLoaded', function() {
        updateModernUserRoleField('admin');
        updateModernUserRoleField('revisor');
        updateModernUserRoleField('autorizador');
        updateModernUserRoleField('activo');
    });

    // Toggle para los switches modernos de roles de usuario
    function toggleModernUserRole(role, track) {
        const checkbox = track.parentElement.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        updateModernUserRoleField(role);
    }

    // Actualizar apariencia del campo moderno según el estado
    function updateModernUserRoleField(role) {
        const checkbox = document.getElementById(`is_${role}`) || document.getElementById(role);
        const field = document.getElementById(`${role}Field`);
        
        if (checkbox && field) {
            field.classList.remove('active', 'inactive');
            if (checkbox.checked) {
                field.classList.add('active');
            } else {
                field.classList.add('inactive');
            }
        }
    }
</script>

<?php View::endSection(); ?>