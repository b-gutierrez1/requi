<?php
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Editar Autorizador';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .form-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 2rem;
        border: 1px solid #e9ecef;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    .form-control {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px 16px;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #e74c3c;
        box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
    }

    .btn-save {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        color: white;
    }

    .btn-cancel {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: #5a6268;
        color: white;
    }

    .btn-centros {
        background: linear-gradient(135deg, #3498db, #2980b9);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    }

    .btn-centros:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        color: white;
    }

    .current-info {
        background: #e3f2fd;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .current-info h6 {
        color: #1565c0;
        margin-bottom: 0.5rem;
    }

    .centros-badge {
        display: inline-block;
        background: #e74c3c;
        color: white;
        border-radius: 12px;
        padding: 2px 10px;
        font-size: 0.85rem;
        font-weight: 600;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-user-edit me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Modifica los datos del autorizador</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= url('/admin/autorizadores') ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a la Lista
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-container">
                <?php
                $flash = Session::getFlash();
                if ($flash):
                ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-triangle' : ($flash['type'] === 'success' ? 'check-circle' : 'info-circle') ?> me-2"></i>
                        <?= View::e($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Informacion Actual -->
                <div class="current-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Informacion Actual</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>ID:</strong> #<?= View::e($autorizador->id ?? 'N/A') ?>
                        </div>
                        <div class="col-md-8">
                            <strong>Centros de Costo asignados:</strong>
                            <span class="centros-badge"><?= count($centrosAsignados ?? []) ?></span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="<?= url('/admin/autorizadores/' . ($autorizador->id ?? '')) ?>" id="editForm">
                    <?php echo App\Middlewares\CsrfMiddleware::field(); ?>
                    <input type="hidden" name="_method" value="PUT">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input type="text"
                                       class="form-control"
                                       id="nombre"
                                       name="nombre"
                                       value="<?= View::e($autorizador->nombre ?? '') ?>"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email"
                                       class="form-control"
                                       id="email"
                                       name="email"
                                       value="<?= View::e($autorizador->email ?? '') ?>"
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row mt-4">
                        <div class="col-12 d-flex justify-content-center gap-3 flex-wrap">
                            <button type="submit" class="btn btn-save">
                                <i class="fas fa-save me-2"></i>
                                Guardar Cambios
                            </button>
                            <a href="<?= url('/admin/autorizadores/' . ($autorizador->id ?? '') . '/centros') ?>" class="btn btn-centros">
                                <i class="fas fa-building me-2"></i>
                                Gestionar Centros de Costo
                            </a>
                            <a href="<?= url('/admin/autorizadores') ?>" class="btn btn-cancel">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('editForm').addEventListener('submit', function(e) {
        const nombre = document.getElementById('nombre').value.trim();
        const email = document.getElementById('email').value.trim();

        if (!nombre || !email) {
            e.preventDefault();
            alert('Por favor complete todos los campos obligatorios.');
            return false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Por favor ingrese un email valido.');
            return false;
        }
    });
</script>
<?php View::endSection(); ?>
