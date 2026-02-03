<?php
use App\Helpers\View;

View::startSection('title', 'Configuración de Correo');
View::startSection('content');
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-envelope me-2"></i>Configuración de Correo
            </h1>
            <p class="text-muted">Gestiona la configuración SMTP y las plantillas de correo del sistema</p>
        </div>
    </div>

    <div class="row">
        <!-- Configuración SMTP -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-server me-2"></i>Configuración SMTP
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Configura el servidor de correo y las credenciales para el envío de emails.</p>
                    <div class="mb-3">
                        <strong>Estado:</strong>
                        <?php if ($config['skip_sending']): ?>
                            <span class="badge bg-warning">Modo Prueba (No envía)</span>
                        <?php elseif ($config['test_mode']): ?>
                            <span class="badge bg-info">Modo Prueba (Redirige a: <?= View::e($config['test_recipient']) ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-success">Activo</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <strong>Servidor:</strong> <?= View::e($config['host'] ?: 'No configurado') ?>
                    </div>
                    <div class="mb-2">
                        <strong>Puerto:</strong> <?= View::e($config['port'] ?? 587) ?>
                    </div>
                    <div class="mb-2">
                        <strong>Remitente:</strong> <?= View::e($config['from_address'] ?: 'No configurado') ?>
                    </div>
                    <a href="<?= url('/admin/email/config') ?>" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i>Configurar SMTP
                    </a>
                </div>
            </div>
        </div>

        <!-- Plantillas de Correo -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>Plantillas de Correo
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Diseña y personaliza las plantillas HTML de los correos que se envían.</p>
                    <div class="mb-3">
                        <strong>Plantillas disponibles:</strong> <?= count($templates) ?>
                    </div>
                    <ul class="list-unstyled">
                        <?php foreach (array_slice($templates, 0, 5) as $template): ?>
                            <li class="mb-2">
                                <i class="fas fa-file-code text-muted me-2"></i>
                                <?= View::e($template['name']) ?>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($templates) > 5): ?>
                            <li class="text-muted">... y <?= count($templates) - 5 ?> más</li>
                        <?php endif; ?>
                    </ul>
                    <a href="<?= url('/admin/email/templates') ?>" class="btn btn-success">
                        <i class="fas fa-edit me-2"></i>Gestionar Plantillas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Prueba de Correo -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-paper-plane me-2"></i>Prueba de Envío
                    </h5>
                </div>
                <div class="card-body">
                    <form id="testEmailForm" method="POST" action="<?= url('/admin/email/test') ?>">
                        <?php echo App\Middlewares\CsrfMiddleware::field(); ?>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="email" class="form-label">Email de destino</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= View::e($config['test_recipient'] ?: Session::get('user_email', '')) ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="subject" class="form-label">Asunto</label>
                                <input type="text" class="form-control" id="subject" name="subject" 
                                       value="Prueba de correo - <?= date('Y-m-d H:i:s') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="template" class="form-label">Plantilla</label>
                                <select class="form-select" id="template" name="template" required>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?= View::e($template['name']) ?>">
                                            <?= View::e($template['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Correo de Prueba
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('testEmailForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    
    try {
        const response = await fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Respuesta no es JSON:', text);
            throw new Error('El servidor devolvió una respuesta inválida. Verifique los logs del servidor.');
        }
        
        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Correo enviado correctamente'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.error || 'Error desconocido al enviar el correo'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al enviar correo: ' + error.message
        });
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
</script>

<?php View::endSection(); ?>

