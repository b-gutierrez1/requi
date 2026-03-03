<?php
use App\Helpers\View;

View::startSection('title', 'Plantillas de Correo');
View::startSection('content');
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-file-alt me-2"></i>Plantillas de Correo
            </h1>
            <p class="text-muted">Diseña y personaliza las plantillas HTML de los correos que se envían</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Plantilla</th>
                                    <th>Tamaño</th>
                                    <th>Última Modificación</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $descriptions = [
                                    'base' => 'Plantilla base para todos los correos',
                                    'nueva_requisicion' => 'Notificación de nueva requisición creada',
                                    'aprobacion' => 'Notificación de requisición aprobada',
                                    'rechazo' => 'Notificación de requisición rechazada',
                                    'completada' => 'Notificación de requisición completada',
                                    'recordatorio' => 'Recordatorio de autorización pendiente',
                                    'urgente_autorizacion' => 'Alerta de autorización urgente'
                                ];
                                
                                foreach ($templates as $template): 
                                    $desc = $descriptions[$template['name']] ?? 'Plantilla de correo';
                                ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-file-code text-primary me-2"></i>
                                            <strong><?= View::e($template['name']) ?></strong>
                                        </td>
                                        <td><?= number_format($template['size'] / 1024, 2) ?> KB</td>
                                        <td><?= date('Y-m-d H:i:s', $template['modified']) ?></td>
                                        <td class="text-muted"><?= View::e($desc) ?></td>
                                        <td class="text-end">
                                            <a href="<?= url('/admin/email/templates/' . $template['name'] . '/edit') ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit me-1"></i>Editar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        <a href="<?= url('/admin/email') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php View::endSection(); ?>



