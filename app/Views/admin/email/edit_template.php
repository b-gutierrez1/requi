<?php
use App\Helpers\View;

View::startSection('title', 'Editar Plantilla: ' . $template);
View::startSection('content');
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-edit me-2"></i>Editar Plantilla: <?= View::e($template) ?>
            </h1>
            <p class="text-muted">Edita el contenido HTML de la plantilla de correo</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="<?= url('/admin/email/templates/' . $template . '/save') ?>">
                        <?php echo App\Middlewares\CsrfMiddleware::field(); ?>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Contenido HTML</label>
                            <textarea class="form-control font-monospace" id="content" name="content" 
                                      rows="25" style="font-size: 12px;" required><?= View::e($content) ?></textarea>
                            <small class="form-text text-muted">Puedes usar variables como {{variable_name}} que se reemplazarán al enviar</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= url('/admin/email/templates') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-info" onclick="previewTemplate()">
                                    <i class="fas fa-eye me-2"></i>Vista Previa
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Plantilla
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Variables Disponibles</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Usa estas variables en tu plantilla:</p>
                    <ul class="list-unstyled small">
                        <?php foreach ($variables as $var): ?>
                            <li class="mb-2">
                                <code>{{<?= View::e($var) ?>}}</code>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <hr>
                    
                    <h6 class="small">Variables Comunes:</h6>
                    <ul class="list-unstyled small">
                        <li><code>{{app_name}}</code> - Nombre de la aplicación</li>
                        <li><code>{{year}}</code> - Año actual</li>
                        <li><code>{{titulo}}</code> - Título del correo</li>
                        <li><code>{{content}}</code> - Contenido principal</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Precaución</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-0">
                        Asegúrate de mantener la estructura HTML válida. 
                        Las plantillas se validan antes de guardar.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Vista Previa -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="fas fa-eye me-2"></i>Vista Previa del Correo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-warning rounded-0 mb-0 py-2 px-3 small">
                    <i class="fas fa-info-circle me-1"></i>
                    Las variables <code>{{variable}}</code> se muestran con datos de ejemplo. El correo real usará los datos reales.
                </div>
                <iframe id="previewFrame" style="width:100%; height:600px; border:none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const PREVIEW_SAMPLES = {
    app_name:             '<?= addslashes(config('app.name', 'Sistema de Requisiciones')) ?>',
    year:                 '<?= date('Y') ?>',
    titulo:               'Notificación del Sistema',
    content:              '<p>Este es el contenido principal del correo.</p>',
    destinatario_nombre:  'Juan Pérez',
    requisicion_id:       '42',
    numero_orden:         '0042',
    estado_actual:        'Aprobado',
    nivel_aprobacion:     '2',
    aprobador_nombre:     'María García',
    autorizador_nombre:   'María García',
    comentario:           'Revisado y aprobado correctamente.',
    fecha_aprobacion:     '<?= date('d/m/Y') ?>',
    fecha_rechazo:        '<?= date('d/m/Y') ?>',
    motivo_rechazo:       'Presupuesto insuficiente para este período.',
    solicitante_nombre:   'Carlos López',
    monto_total:          'Q 1,500.00',
    centro_costo:         'Centro de Costo Ejemplo',
    unidad_requirente:    'Departamento de TI',
    descripcion:          'Compra de insumos de oficina',
};

function previewTemplate() {
    let html = document.getElementById('content').value;

    // Reemplazar todas las variables {{var}} con datos de ejemplo
    html = html.replace(/\{\{(\w+)\}\}/g, (match, key) => {
        return PREVIEW_SAMPLES[key] !== undefined ? PREVIEW_SAMPLES[key] : `<mark title="sin dato de ejemplo">${match}</mark>`;
    });

    const modalEl = document.getElementById('previewModal');

    // Mover el modal al root del <body> antes de mostrarlo.
    // Bootstrap hace focus-trap al abrir, y el navegador scrollea
    // nativamente hacia el elemento enfocado. Si el modal está al
    // final del DOM (dentro del section), eso baja toda la página.
    // Moviéndolo a body.appendChild el nodo queda fuera del flujo
    // de contenido y el scroll no ocurre.
    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    // Escribir al iframe DESPUÉS de que el modal ya es visible,
    // para que doc.write() no cause scroll adicional.
    modalEl.addEventListener('shown.bs.modal', () => {
        const frame = document.getElementById('previewFrame');
        const doc = frame.contentDocument || frame.contentWindow.document;
        doc.open();
        doc.write(html);
        doc.close();
    }, { once: true });

    modal.show();
}
</script>

<?php View::endSection(); ?>



