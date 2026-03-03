<?php
use App\Helpers\View;

View::startSection('title', $title ?? 'Logs de Requisición');
View::startSection('content');
?>

<style>
    .log-section {
        margin-bottom: 30px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .log-header {
        background: #f8f9fa;
        padding: 15px 20px;
        border-bottom: 1px solid #e0e0e0;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .log-content {
        max-height: 400px;
        overflow-y: auto;
    }

    .log-item {
        padding: 12px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .log-item:last-child {
        border-bottom: none;
    }

    .log-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: white;
        margin-top: 2px;
    }

    .log-icon.success { background: #28a745; }
    .log-icon.info { background: #17a2b8; }
    .log-icon.warning { background: #ffc107; color: #000; }
    .log-icon.danger { background: #dc3545; }
    .log-icon.debug { background: #6c757d; }

    .log-details {
        flex: 1;
    }

    .log-message {
        margin: 0;
        font-size: 14px;
        line-height: 1.4;
    }

    .log-meta {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }

    .log-timestamp {
        font-size: 11px;
        color: #999;
        margin-left: auto;
        white-space: nowrap;
    }

    .empty-logs {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
        font-style: italic;
    }

    .requisicion-header {
        background: #000;
        color: #fff;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .code-block {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 4px;
        padding: 8px 12px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        white-space: pre-wrap;
        word-wrap: break-word;
        max-height: 100px;
        overflow-y: auto;
    }

    .badge-count {
        background: #007bff;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: normal;
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header de la requisición -->
            <div class="requisicion-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h2><i class="fas fa-file-alt"></i> Logs - Requisición #<?= $orden['id'] ?></h2>
                        <p class="mb-0"><?= View::e($orden['nombre_razon_social'] ?? 'Sin nombre') ?></p>
                    </div>
                    <div class="text-end">
                        <a href="<?= url('/admin/requisiciones/' . $orden['id']) ?>" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver a Detalle
                        </a>
                        <a href="<?= url('/admin/requisiciones') ?>" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-list"></i> Lista de Requisiciones
                        </a>
                    </div>
                </div>
            </div>

            <!-- Logs del Sistema -->
            <div class="log-section">
                <div class="log-header">
                    <span><i class="fas fa-history"></i> Historial del Sistema</span>
                    <span class="badge-count"><?= count($logs) ?></span>
                </div>
                <div class="log-content">
                    <?php if (empty($logs)): ?>
                        <div class="empty-logs">
                            <i class="fas fa-inbox fa-2x mb-3 d-block"></i>
                            No hay logs de sistema registrados
                        </div>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php 
                            // Determinar el tipo de icono y color según el evento
                            $tipoEvento = $log['tipo_evento'] ?? $log['accion'] ?? 'info';
                            $esError = strpos($tipoEvento, 'error') !== false;
                            $esExito = in_array($tipoEvento, ['creacion', 'archivo_subido', 'aprobacion', 'autorizado']);
                            $iconClass = $esError ? 'danger' : ($esExito ? 'success' : 'info');
                            $iconName = $esError ? 'exclamation-triangle' : ($esExito ? 'check-circle' : 'cog');
                            ?>
                            <div class="log-item">
                                <div class="log-icon <?= $iconClass ?>">
                                    <i class="fas fa-<?= $iconName ?>"></i>
                                </div>
                                <div class="log-details">
                                    <p class="log-message">
                                        <strong><?= View::e(ucfirst(str_replace('_', ' ', $tipoEvento))) ?>:</strong> 
                                        <?= View::e($log['descripcion'] ?? 'Sin descripción') ?>
                                    </p>
                                    <div class="log-meta">
                                        <?php if (!empty($log['usuario_nombre']) || !empty($log['usuario_email'])): ?>
                                            Por: <?= View::e($log['usuario_nombre'] ?? $log['usuario_email'] ?? 'Sistema') ?>
                                        <?php else: ?>
                                            Por: Sistema
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="log-timestamp">
                                    <?php 
                                    $fecha = $log['fecha_log'] ?? $log['fecha_cambio'] ?? $log['fecha'] ?? null;
                                    echo $fecha ? date('d/m/Y H:i:s', strtotime($fecha)) : 'Sin fecha';
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Logs de Archivos Adjuntos -->
            <div class="log-section">
                <div class="log-header">
                    <span><i class="fas fa-file-upload"></i> Archivos Adjuntos</span>
                    <span class="badge-count"><?= count($archivo_logs) ?></span>
                </div>
                <div class="log-content">
                    <?php if (empty($archivo_logs)): ?>
                        <div class="empty-logs">
                            <i class="fas fa-paperclip fa-2x mb-3 d-block"></i>
                            No se han subido archivos adjuntos
                        </div>
                    <?php else: ?>
                        <?php foreach ($archivo_logs as $archivo): ?>
                            <div class="log-item">
                                <div class="log-icon success">
                                    <i class="fas fa-upload"></i>
                                </div>
                                <div class="log-details">
                                    <p class="log-message">
                                        <strong>Archivo subido:</strong> <?= View::e($archivo['nombre_original']) ?>
                                    </p>
                                    <div class="log-meta">
                                        Tamaño: <?= number_format($archivo['tamano_bytes'] / 1024, 2) ?> KB | 
                                        Tipo: <?= View::e($archivo['tipo_mime']) ?> | 
                                        Archivo interno: <?= View::e($archivo['nombre_archivo']) ?>
                                    </div>
                                </div>
                                <div class="log-timestamp">
                                    <?= date('d/m/Y H:i:s', strtotime($archivo['fecha_log'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Logs de Autorizaciones -->
            <div class="log-section">
                <div class="log-header">
                    <span><i class="fas fa-shield-alt"></i> Autorizaciones</span>
                    <span class="badge-count"><?= count($autorizacion_logs) ?></span>
                </div>
                <div class="log-content">
                    <?php if (empty($autorizacion_logs)): ?>
                        <div class="empty-logs">
                            <i class="fas fa-user-check fa-2x mb-3 d-block"></i>
                            No hay autorizaciones registradas
                        </div>
                    <?php else: ?>
                        <?php foreach ($autorizacion_logs as $auth): ?>
                            <div class="log-item">
                                <div class="log-icon <?= $auth['estado'] === 'autorizada' ? 'success' : ($auth['estado'] === 'rechazada' ? 'danger' : 'warning') ?>">
                                    <i class="fas fa-<?= $auth['estado'] === 'autorizada' ? 'check' : ($auth['estado'] === 'rechazada' ? 'times' : 'clock') ?>"></i>
                                </div>
                                <div class="log-details">
                                    <p class="log-message">
                                        <strong><?= ucfirst($auth['subtipo_log']) ?> de autorización:</strong> 
                                        <?= ucfirst(str_replace('_', ' ', $auth['tipo'])) ?>
                                    </p>
                                    <div class="log-meta">
                                        Autorizador: <?= View::e($auth['autorizador_email']) ?> | 
                                        Estado: <span class="badge badge-<?= $auth['estado'] === 'autorizada' ? 'success' : ($auth['estado'] === 'rechazada' ? 'danger' : 'warning') ?>"><?= ucfirst($auth['estado']) ?></span>
                                        <?php if (!empty($auth['comentarios'])): ?>
                                            <br>Comentarios: <?= View::e($auth['comentarios']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="log-timestamp">
                                    <?= date('d/m/Y H:i:s', strtotime($auth['fecha_log'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Logs de Errores PHP -->
            <div class="log-section">
                <div class="log-header">
                    <span><i class="fas fa-bug"></i> Errores y Debug del Sistema</span>
                    <span class="badge-count"><?= count($error_logs) ?></span>
                </div>
                <div class="log-content">
                    <?php if (empty($error_logs)): ?>
                        <div class="empty-logs">
                            <i class="fas fa-check-circle fa-2x mb-3 d-block"></i>
                            No se encontraron errores relacionados con esta requisición
                        </div>
                    <?php else: ?>
                        <?php foreach ($error_logs as $error): ?>
                            <div class="log-item">
                                <div class="log-icon <?= $error['type'] ?>">
                                    <i class="fas fa-<?= $error['type'] === 'error' ? 'exclamation-triangle' : ($error['type'] === 'warning' ? 'exclamation' : ($error['type'] === 'debug' ? 'bug' : 'info-circle')) ?>"></i>
                                </div>
                                <div class="log-details">
                                    <p class="log-message"><strong><?= ucfirst($error['type']) ?>:</strong></p>
                                    <div class="code-block"><?= View::e(trim($error['message'])) ?></div>
                                </div>
                                <div class="log-timestamp">
                                    <?= $error['timestamp'] ? date('d/m/Y H:i:s', strtotime($error['timestamp'])) : 'Sin timestamp' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Información sobre los logs:</h6>
                <ul class="mb-0 small">
                    <li><strong>Historial del Sistema:</strong> Cambios de estado y acciones registradas en el sistema</li>
                    <li><strong>Archivos Adjuntos:</strong> Logs de subida y gestión de archivos</li>
                    <li><strong>Autorizaciones:</strong> Creación y respuestas de autorizaciones</li>
                    <li><strong>Errores del Sistema:</strong> Errores PHP y debug relacionados con esta requisición (últimas 1000 líneas del log)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-scroll a la sección con más actividad
    const sections = document.querySelectorAll('.log-content');
    sections.forEach(section => {
        const items = section.querySelectorAll('.log-item');
        if (items.length > 0) {
            // Auto-scroll al primer item si hay muchos logs
            if (items.length > 5) {
                section.scrollTop = 0;
            }
        }
    });

    // Expandir/colapsar código largo
    const codeBlocks = document.querySelectorAll('.code-block');
    codeBlocks.forEach(block => {
        if (block.scrollHeight > 100) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'btn btn-sm btn-outline-secondary mt-2';
            toggleBtn.textContent = 'Ver más';
            toggleBtn.onclick = function() {
                if (block.style.maxHeight === 'none') {
                    block.style.maxHeight = '100px';
                    this.textContent = 'Ver más';
                } else {
                    block.style.maxHeight = 'none';
                    this.textContent = 'Ver menos';
                }
            };
            block.parentNode.insertBefore(toggleBtn, block.nextSibling);
        }
    });
});
</script>

<?php
View::endSection();
?>