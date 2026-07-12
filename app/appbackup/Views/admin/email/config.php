<?php
use App\Helpers\View;

View::startSection('title', 'Configuración SMTP');
View::startSection('content');
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-server me-2"></i>Configuración SMTP
            </h1>
            <p class="text-muted">Configura el servidor de correo y las credenciales para el envío de emails</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="<?= url('/admin/email/config/save') ?>">
                        <?php echo App\Middlewares\CsrfMiddleware::field(); ?>
                        
                        <h5 class="mb-4">Configuración del Servidor</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="host" class="form-label">Servidor SMTP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="host" name="host" 
                                       value="<?= View::e($config['host'] ?? '') ?>" 
                                       placeholder="smtp.gmail.com" required>
                                <small class="form-text text-muted">Ejemplo: smtp.gmail.com, smtp.office365.com</small>
                            </div>
                            <div class="col-md-4">
                                <label for="port" class="form-label">Puerto <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="port" name="port" 
                                       value="<?= View::e($config['port'] ?? 587) ?>" required>
                                <small class="form-text text-muted">587 (TLS) o 465 (SSL)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="encryption" class="form-label">Cifrado</label>
                            <select class="form-select" id="encryption" name="encryption">
                                <option value="tls" <?= ($config['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($config['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="" <?= empty($config['encryption']) ? 'selected' : '' ?>>Ninguno</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Usuario SMTP</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= View::e($config['username'] ?? '') ?>" 
                                       placeholder="tu-email@dominio.com">
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       value="<?= View::e($config['password'] ?? '') ?>" 
                                       placeholder="Tu contraseña o app password">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-4">Remitente</h5>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="from_address" class="form-label">Email Remitente <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="from_address" name="from_address" 
                                       value="<?= View::e($config['from_address'] ?? '') ?>" 
                                       placeholder="noreply@tudominio.com" required>
                            </div>
                            <div class="col-md-6">
                                <label for="from_name" class="form-label">Nombre Remitente</label>
                                <input type="text" class="form-control" id="from_name" name="from_name" 
                                       value="<?= View::e($config['from_name'] ?? '') ?>" 
                                       placeholder="Sistema de Requisiciones">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-4">Opciones de Prueba</h5>

                        <div class="mb-3">
                            <div class="switch-container">
                                <div class="flex-grow-1">
                                    <label class="switch-label" for="test_mode">
                                        <i class="fas fa-flask me-2 text-warning"></i>
                                        Modo Prueba
                                    </label>
                                    <p class="switch-description">
                                        Redirige todos los correos a un email de prueba específico
                                    </p>
                                </div>
                                <div class="custom-switch custom-switch-warning">
                                    <input type="checkbox" id="test_mode" name="test_mode" 
                                           <?= ($config['test_mode'] ?? false) ? 'checked' : '' ?>>
                                    <span class="custom-switch-slider" onclick="toggleSwitchContainer(this)"></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="test_recipient_group" style="display: <?= ($config['test_mode'] ?? false) ? 'block' : 'none' ?>;">
                            <label for="test_recipient" class="form-label">Email de Prueba</label>
                            <input type="email" class="form-control" id="test_recipient" name="test_recipient" 
                                   value="<?= View::e($config['test_recipient'] ?? '') ?>" 
                                   placeholder="prueba@tudominio.com">
                            <small class="form-text text-muted">Todos los correos se enviarán a este email cuando el modo prueba esté activo</small>
                        </div>

                        <div class="mb-3">
                            <div class="switch-container">
                                <div class="flex-grow-1">
                                    <label class="switch-label" for="skip_sending">
                                        <i class="fas fa-ban me-2 text-danger"></i>
                                        No enviar correos
                                    </label>
                                    <p class="switch-description">
                                        Solo registrar en logs - útil para desarrollo y pruebas
                                    </p>
                                </div>
                                <div class="custom-switch custom-switch-danger">
                                    <input type="checkbox" id="skip_sending" name="skip_sending" 
                                           <?= ($config['skip_sending'] ?? false) ? 'checked' : '' ?>>
                                    <span class="custom-switch-slider" onclick="toggleSwitchContainer(this)"></span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= url('/admin/email') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                            <div>
                                <button type="button" id="testConnectionBtn" class="btn btn-info me-2">
                                    <i class="fas fa-plug me-2"></i>Probar Conexión
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Configuración
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Ayuda</h6>
                </div>
                <div class="card-body">
                    <h6>Gmail</h6>
                    <ul class="small">
                        <li>Servidor: smtp.gmail.com</li>
                        <li>Puerto: 587 (TLS) o 465 (SSL)</li>
                        <li>Usa una "App Password" en lugar de tu contraseña normal</li>
                    </ul>

                    <h6 class="mt-3">Office 365 / Outlook</h6>
                    <ul class="small">
                        <li>Servidor: smtp.office365.com</li>
                        <li>Puerto: 587 (TLS)</li>
                        <li>Usuario: tu email completo</li>
                    </ul>

                    <h6 class="mt-3">Otros Servidores</h6>
                    <ul class="small">
                        <li>Consulta con tu proveedor de email</li>
                        <li>Algunos requieren autenticación especial</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 para notificaciones -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.getElementById('test_mode').addEventListener('change', function() {
    document.getElementById('test_recipient_group').style.display = this.checked ? 'block' : 'none';
});

// Prueba de conexión SMTP
document.getElementById('testConnectionBtn').addEventListener('click', async function() {
    const btn = this;
    const originalHtml = btn.innerHTML;
    
    // Obtener valores del formulario
    const host = document.getElementById('host').value;
    const port = document.getElementById('port').value;
    const encryption = document.getElementById('encryption').value;
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    // Validar campos requeridos
    if (!host || !port) {
        alert('Por favor, complete al menos el servidor SMTP y el puerto');
        return;
    }
    
    // Deshabilitar botón y mostrar loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Probando conexión...';
    
    try {
        const formData = new FormData();
        formData.append('host', host);
        formData.append('port', port);
        formData.append('encryption', encryption);
        formData.append('username', username);
        formData.append('password', password);
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        
        const response = await fetch('<?= url('/admin/email/test-connection') ?>', {
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
            // Mostrar éxito
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Conexión exitosa!',
                    text: result.message || 'La conexión al servidor SMTP se estableció correctamente',
                    confirmButtonColor: '#28a745'
                });
            } else {
                alert('¡Conexión exitosa!\n\n' + (result.message || 'La conexión al servidor SMTP se estableció correctamente'));
            }
        } else {
            // Mostrar error
            const errorMsg = result.error || 'No se pudo establecer la conexión con el servidor SMTP';
            const details = result.details ? '\n\n' + result.details : '';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    html: '<p>' + errorMsg + '</p>' + (result.details ? '<p class="text-muted small mt-2">' + result.details + '</p>' : ''),
                    confirmButtonColor: '#dc3545'
                });
            } else {
                alert('Error de conexión\n\n' + errorMsg + details);
            }
        }
    } catch (error) {
        const errorMsg = 'Error al probar la conexión: ' + error.message;
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg,
                confirmButtonColor: '#dc3545'
            });
        } else {
            alert(errorMsg);
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
});

// ========================================================================
// FUNCIÓN PARA SWITCHES MODERNOS
// ========================================================================

function toggleSwitchContainer(slider) {
    const checkbox = slider.parentElement.querySelector('input[type="checkbox"]');
    const container = slider.closest('.switch-container');
    
    // Toggle checkbox
    checkbox.checked = !checkbox.checked;
    
    // Add animation class
    container.classList.add('toggling');
    
    // Remove animation class after animation completes
    setTimeout(() => {
        container.classList.remove('toggling');
    }, 200);
    
    // Dispatch change event for any listeners
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    
    // Handle specific functionality for email config switches
    if (checkbox.id === 'test_mode') {
        const testRecipientGroup = document.getElementById('test_recipient_group');
        if (testRecipientGroup) {
            testRecipientGroup.style.display = checkbox.checked ? 'block' : 'none';
        }
    }
}
</script>

<?php View::endSection(); ?>

