<?php
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Mi Perfil';
?>

<?php View::startSection('content'); ?>
<style>
    .perfil-header {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 2.5rem 0;
        margin-bottom: 2rem;
    }
    .avatar-circle {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: rgba(255,255,255,0.25);
        border: 3px solid rgba(255,255,255,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.2rem;
        font-weight: 700;
        color: white;
        flex-shrink: 0;
    }
    .perfil-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        border: 1px solid #e9ecef;
        padding: 1.75rem;
        margin-bottom: 1.5rem;
    }
    .perfil-card h5 {
        font-size: 1rem;
        font-weight: 700;
        color: #3498db;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.6rem;
        margin-bottom: 1.2rem;
    }
    .info-row {
        display: flex;
        align-items: flex-start;
        padding: 0.6rem 0;
        border-bottom: 1px solid #f1f3f5;
    }
    .info-row:last-child { border-bottom: none; }
    .info-label {
        width: 160px;
        flex-shrink: 0;
        font-weight: 600;
        color: #6c757d;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding-top: 2px;
    }
    .info-value {
        flex: 1;
        color: #212529;
        font-size: 0.95rem;
    }
    .info-value.empty { color: #adb5bd; font-style: italic; }
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 600;
        margin-right: 6px;
        margin-bottom: 4px;
    }
    .role-admin    { background: #fff3cd; color: #856404; }
    .role-revisor  { background: #cff4fc; color: #0c5460; }
    .role-autor    { background: #d1e7dd; color: #0a3622; }
    .role-user     { background: #f8f9fa; color: #495057; }
</style>

<div class="perfil-header">
    <div class="container">
        <div class="d-flex align-items-center gap-4">
            <div class="avatar-circle">
                <?= strtoupper(substr($usuario['name'] ?? $usuario['email'] ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <h2 class="mb-1 fw-bold"><?= View::e($usuario['name'] ?? $usuario['email'] ?? 'Usuario') ?></h2>
                <p class="mb-0 opacity-75">
                    <i class="fas fa-envelope me-2"></i><?= View::e($usuario['email'] ?? '') ?>
                </p>
                <?php if (!empty($usuario['job_title'])): ?>
                <p class="mb-0 opacity-75 mt-1">
                    <i class="fas fa-briefcase me-2"></i><?= View::e($usuario['job_title']) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">

        <!-- Información Personal -->
        <div class="col-lg-7">
            <div class="perfil-card">
                <h5><i class="fas fa-user me-2"></i>Información Personal</h5>

                <div class="info-row">
                    <span class="info-label">Nombre</span>
                    <span class="info-value"><?= View::e($usuario['name'] ?? '') ?: '<span class="empty">No disponible</span>' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Correo</span>
                    <span class="info-value"><?= View::e($usuario['email'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cargo</span>
                    <span class="info-value">
                        <?php if (!empty($usuario['job_title'])): ?>
                            <?= View::e($usuario['job_title']) ?>
                        <?php else: ?>
                            <span class="empty">No especificado</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Departamento</span>
                    <span class="info-value">
                        <?php if (!empty($usuario['department'])): ?>
                            <?= View::e($usuario['department']) ?>
                        <?php else: ?>
                            <span class="empty">No especificado</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Roles y Permisos -->
            <div class="perfil-card">
                <h5><i class="fas fa-shield-alt me-2"></i>Roles y Permisos</h5>
                <div class="d-flex flex-wrap mt-1">
                    <?php if (!empty($usuario['is_admin'])): ?>
                        <span class="role-badge role-admin"><i class="fas fa-crown"></i> Administrador</span>
                    <?php endif; ?>
                    <?php if (!empty($usuario['is_revisor'])): ?>
                        <span class="role-badge role-revisor"><i class="fas fa-search"></i> Revisor</span>
                    <?php endif; ?>
                    <?php if (!empty($usuario['is_autorizador'])): ?>
                        <span class="role-badge role-autor"><i class="fas fa-check-circle"></i> Autorizador</span>
                    <?php endif; ?>
                    <?php if (empty($usuario['is_admin']) && empty($usuario['is_revisor']) && empty($usuario['is_autorizador'])): ?>
                        <span class="role-badge role-user"><i class="fas fa-user"></i> Usuario</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actividad y Cuenta -->
        <div class="col-lg-5">
            <div class="perfil-card">
                <h5><i class="fas fa-history me-2"></i>Actividad</h5>

                <div class="info-row">
                    <span class="info-label">Último acceso</span>
                    <span class="info-value">
                        <?php if (!empty($usuario['last_login'])): ?>
                            <?= date('d/m/Y H:i', strtotime($usuario['last_login'])) ?>
                        <?php else: ?>
                            <span class="empty">No registrado</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cuenta creada</span>
                    <span class="info-value">
                        <?php if (!empty($usuario['fecha_creacion'])): ?>
                            <?= date('d/m/Y', strtotime($usuario['fecha_creacion'])) ?>
                        <?php else: ?>
                            <span class="empty">No disponible</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado</span>
                    <span class="info-value">
                        <?php if (!isset($usuario['activo']) || $usuario['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($usuario['azure_id'])): ?>
            <div class="perfil-card">
                <h5><i class="fab fa-microsoft me-2"></i>Cuenta Microsoft</h5>
                <div class="info-row">
                    <span class="info-label">Sincronizado</span>
                    <span class="info-value">
                        <?php if (!empty($usuario['azure_last_sync'])): ?>
                            <?= date('d/m/Y H:i', strtotime($usuario['azure_last_sync'])) ?>
                        <?php else: ?>
                            <span class="empty">No disponible</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
<?php View::endSection(); ?>
