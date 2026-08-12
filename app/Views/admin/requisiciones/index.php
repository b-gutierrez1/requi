<?php
use App\Helpers\View;

/**
 * Estados del flujo que significan que la requisición fue rechazada.
 * La BD guarda el rechazo en tres variantes según la etapa en que ocurrió.
 */
$estadosRechazo = ['rechazado', 'rechazado_revision', 'rechazado_autorizacion'];

/**
 * Arma el contenido del tooltip que adelanta el motivo del rechazo en el listado.
 * Recibe los rechazos ya cargados por el controlador (sin consultas dentro del bucle).
 */
$tooltipRechazo = function (array $rechazos) {
    if (empty($rechazos)) {
        return 'Requisición rechazada. No se registró un motivo.';
    }

    $lineas = [];
    foreach ($rechazos as $rechazo) {
        $etapa  = View::e($rechazo['etapa_label'] ?? 'Autorización');
        $detalle = ($rechazo['etapa_detalle'] ?? '') !== ''
            ? ' (' . View::e($rechazo['etapa_detalle']) . ')'
            : '';
        $autor  = ($rechazo['autor'] ?? '') !== ''
            ? View::e($rechazo['autor'])
            : 'Autorizador no registrado';

        $ts = !empty($rechazo['fecha']) ? strtotime($rechazo['fecha']) : false;
        $fecha = $ts ? ' &middot; ' . date('d/m/Y', $ts) : '';

        $motivo = ($rechazo['motivo'] ?? '') !== ''
            ? $rechazo['motivo']
            : (($rechazo['comentario'] ?? '') !== '' ? $rechazo['comentario'] : 'Sin motivo registrado');

        // Se recorta para que el tooltip no crezca de más; el detalle completo está en la pantalla de detalle
        if (function_exists('mb_strimwidth')) {
            $motivo = mb_strimwidth($motivo, 0, 140, '...', 'UTF-8');
        } elseif (strlen($motivo) > 140) {
            $motivo = substr($motivo, 0, 137) . '...';
        }

        $lineas[] = '<strong>' . $etapa . $detalle . '</strong><br>'
            . View::e($motivo) . '<br>'
            . '<em>' . $autor . $fecha . '</em>';
    }

    return implode('<hr class="my-1">', $lineas);
};

View::startSection('content');
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-chart-line me-2"></i>
                Panel de Administración - Seguimiento de Requisiciones
            </h1>
            <p class="text-muted mb-0">
                Monitoreo completo del flujo de autorizaciones paso a paso
                <span class="badge bg-info text-white ms-2">
                    <?php echo $total_requisiciones; ?> requisicion<?php echo $total_requisiciones != 1 ? 'es' : ''; ?>
                </span>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/admin/dashboard') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- Filtros y Busqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Estado del Flujo</label>
                    <select class="form-select" id="filtroEstadoFlujo">
                        <option value="">Todos los estados</option>
                        <option value="pendiente_revision">Pendiente Revisión</option>
                        <option value="pendiente_autorizacion">Pendiente Autorización</option>
                        <option value="autorizado">Autorizado</option>
                        <option value="rechazado">Rechazado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Forma de Pago</label>
                    <select class="form-select" id="filtroFormaPago">
                        <option value="">Todas las formas</option>
                        <option value="tarjeta_credito_lic_milton">Tarjeta Crédito Especial</option>
                        <option value="transferencia_bancaria">Transferencia</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="buscarRequisicion" placeholder="ID, proveedor...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button class="btn btn-primary" onclick="aplicarFiltros()">
                            <i class="fas fa-filter me-1"></i>Filtrar
                        </button>
                        <button class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Requisiciones -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Requisiciones - Vista Administrativa
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tablaRequisiciones">
                    <thead class="table-dark">
                        <tr>
                            <th width="80">ID</th>
                            <th>Proveedor</th>
                            <th width="150">Monto</th>
                            <th width="120">Fecha</th>
                            <th width="140">Estado Orden</th>
                            <th width="160">Estado Flujo</th>
                            <th width="120">Especiales</th>
                            <th width="120">Unidades</th>
                            <th width="100">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requisiciones as $req): ?>
                        <?php
                            $estadoFlujoReq = $req['estado_flujo'] ?? '';
                            $rechazosReq    = (isset($req['rechazos']) && is_array($req['rechazos'])) ? $req['rechazos'] : [];
                            // Se considera rechazada por el estado del flujo o por tener rechazos registrados
                            $fueRechazada   = in_array($estadoFlujoReq, $estadosRechazo, true) || !empty($rechazosReq);
                        ?>
                        <tr class="requisicion-row<?php echo $fueRechazada ? ' table-danger' : ''; ?>"
                            data-estado-flujo="<?php echo View::e($estadoFlujoReq); ?>"
                            data-rechazada="<?php echo $fueRechazada ? '1' : '0'; ?>"
                            data-forma-pago="<?php echo View::e($req['forma_pago'] ?? ''); ?>"
                            data-busqueda="<?php echo View::e(strtolower($req['id'] . ' ' . ($req['nombre_razon_social'] ?? ''))); ?>">
                            <td>
                                <strong class="text-primary">#<?php echo $req['id']; ?></strong>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <strong><?php echo View::e(substr($req['nombre_razon_social'], 0, 40)); ?></strong>
                                        <?php if (strlen($req['nombre_razon_social']) > 40): ?>
                                            <small class="text-muted">...</small>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-credit-card me-1"></i>
                                            <?php echo View::e($req['forma_pago']); ?>
                                        </small>
                                        
                                        <!-- Indicadores de autorizaciones especiales -->
                                        <?php if ($req['requiere_autorizacion_especial_pago']): ?>
                                            <br><span class="badge bg-success">
                                                <i class="fas fa-credit-card me-1"></i>Especial Pago
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($req['requiere_autorizacion_especial_cuenta']): ?>
                                            <span class="badge bg-info">
                                                <i class="fas fa-calculator me-1"></i>Especial Cuenta
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong class="text-success">Q<?php echo number_format($req['monto_total'], 2); ?></strong>
                            </td>
                            <td>
                                <small><?php echo date('d/m/Y', strtotime($req['fecha'])); ?></small>
                            </td>
                            <td>
                                <?php
                                    // Usar EstadoHelper para obtener el estado mapeado
                                    $estadoReal = \App\Helpers\EstadoHelper::mapearEstadoFlujo($estadoFlujoReq ?: 'borrador');
                                    $badge = \App\Helpers\EstadoHelper::getBadge($estadoReal);
                                    // EstadoHelper devuelve clases de Bootstrap 4 (badge-*); el layout usa Bootstrap 5 (bg-*)
                                    $badgeClass = str_replace('badge-', 'bg-', $badge['class']);
                                    if ($badgeClass === 'bg-warning') {
                                        $badgeClass .= ' text-dark';
                                    }
                                ?>
                                <span class="badge <?php echo View::e($badgeClass); ?>">
                                    <?php echo View::e($badge['text']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($estadoFlujoReq): ?>
                                    <?php
                                        $estadoFlujoClass = match($estadoFlujoReq) {
                                            'pendiente_revision' => 'bg-warning text-dark',
                                            'pendiente_autorizacion',
                                            'pendiente_autorizacion_pago',
                                            'pendiente_autorizacion_cuenta',
                                            'pendiente_autorizacion_centros' => 'bg-info',
                                            'autorizado' => 'bg-success',
                                            'rechazado',
                                            'rechazado_revision',
                                            'rechazado_autorizacion' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        $estadoFlujoTexto = match($estadoFlujoReq) {
                                            'pendiente_revision' => 'Pendiente de revisión',
                                            'pendiente_autorizacion',
                                            'pendiente_autorizacion_pago',
                                            'pendiente_autorizacion_cuenta',
                                            'pendiente_autorizacion_centros' => 'Pendiente de autorización',
                                            'autorizado' => 'Autorizada',
                                            'rechazado_revision' => 'Rechazada en la revisión',
                                            'rechazado_autorizacion' => 'Rechazada en la autorización',
                                            'rechazado' => 'Rechazada',
                                            default => ucfirst(str_replace('_', ' ', $estadoFlujoReq))
                                        };
                                    ?>
                                    <span class="badge <?php echo $estadoFlujoClass; ?>">
                                        <?php echo View::e($estadoFlujoTexto); ?>
                                    </span>

                                    <?php if ($fueRechazada): ?>
                                        <a href="<?= url('/admin/requisiciones/' . $req['id']) ?>"
                                           class="text-danger ms-1 indicador-rechazo"
                                           data-bs-toggle="tooltip"
                                           data-bs-html="true"
                                           data-bs-placement="left"
                                           title="<?php echo View::e($tooltipRechazo($rechazosReq)); ?>">
                                            <i class="fas fa-comment-slash"></i>
                                            <span class="visually-hidden">Ver motivo del rechazo</span>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Indicador de tiempo -->
                                    <?php if ($req['fecha_inicio_flujo']): ?>
                                        <br><small class="text-muted">
                                            <?php
                                                $diasFlujo = floor((time() - strtotime($req['fecha_inicio_flujo'])) / (60 * 60 * 24));
                                                echo $diasFlujo . ' día' . ($diasFlujo != 1 ? 's' : '');
                                            ?>
                                        </small>
                                    <?php endif; ?>

                                    <?php if ($fueRechazada && !empty($rechazosReq)): ?>
                                        <?php
                                            $primerRechazo = $rechazosReq[0];
                                            $autorPrimerRechazo = ($primerRechazo['autor'] ?? '') !== ''
                                                ? $primerRechazo['autor']
                                                : 'Autorizador no registrado';
                                        ?>
                                        <br><small class="text-danger d-inline-block text-truncate" style="max-width: 150px;"
                                                   title="<?php echo View::e($autorPrimerRechazo); ?>">
                                            <i class="fas fa-user-times me-1"></i><?php echo View::e($autorPrimerRechazo); ?>
                                        </small>
                                    <?php endif; ?>
                                <?php elseif ($fueRechazada): ?>
                                    <span class="badge bg-danger">Rechazada</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark">Sin Flujo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Autorizaciones especiales -->
                                <?php if (($req['especiales_pendientes'] ?? 0) + ($req['especiales_autorizadas'] ?? 0) > 0): ?>
                                    <div class="progress mb-1" style="height: 8px;">
                                        <?php 
                                            $totalEspeciales = ($req['especiales_pendientes'] ?? 0) + ($req['especiales_autorizadas'] ?? 0);
                                            $porcentajeEspeciales = $totalEspeciales > 0 ? (($req['especiales_autorizadas'] ?? 0) / $totalEspeciales) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-success" style="width: <?php echo $porcentajeEspeciales; ?>%"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $req['especiales_autorizadas'] ?? 0; ?>/<?php echo $totalEspeciales; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Autorizaciones por unidad de negocio -->
                                <?php if (($req['centros_pendientes'] ?? 0) + ($req['centros_autorizados'] ?? 0) > 0): ?>
                                    <div class="progress mb-1" style="height: 8px;">
                                        <?php 
                                            $totalCentros = ($req['centros_pendientes'] ?? 0) + ($req['centros_autorizados'] ?? 0);
                                            $porcentajeCentros = $totalCentros > 0 ? (($req['centros_autorizados'] ?? 0) / $totalCentros) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-warning" style="width: <?php echo $porcentajeCentros; ?>%"></div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $req['centros_autorizados'] ?? 0; ?>/<?php echo $totalCentros; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= url('/admin/requisiciones/' . $req['id']) ?>" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="Ver Detalle Completo">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-warning">
                        <?php 
                            $pendientesRevision = array_filter($requisiciones, function($r) { 
                                return ($r['estado_flujo'] ?? '') === 'pendiente_revision'; 
                            });
                            echo count($pendientesRevision);
                        ?>
                    </h5>
                    <small class="text-muted">Pendientes Revisión</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-info">
                        <?php 
                            $pendientesAuth = array_filter($requisiciones, function($r) {
                                return in_array($r['estado_flujo'] ?? '', [
                                    'pendiente_autorizacion',
                                    'pendiente_autorizacion_pago',
                                    'pendiente_autorizacion_cuenta',
                                    'pendiente_autorizacion_centros'
                                ], true);
                            });
                            echo count($pendientesAuth);
                        ?>
                    </h5>
                    <small class="text-muted">Pendientes Autorización</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-success">
                        <?php 
                            $autorizadas = array_filter($requisiciones, function($r) { 
                                return ($r['estado_flujo'] ?? '') === 'autorizado'; 
                            });
                            echo count($autorizadas);
                        ?>
                    </h5>
                    <small class="text-muted">Autorizadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-danger">
                        <?php 
                            $rechazadas = array_filter($requisiciones, function($r) use ($estadosRechazo) {
                                return in_array($r['estado_flujo'] ?? '', $estadosRechazo, true)
                                    || !empty($r['rechazos']);
                            });
                            echo count($rechazadas);
                        ?>
                    </h5>
                    <small class="text-muted">Rechazadas</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
View::endSection();

View::startSection('scripts');
?>
<script>
function aplicarFiltros() {
    const estadoFlujo = document.getElementById('filtroEstadoFlujo').value;
    const formaPago = document.getElementById('filtroFormaPago').value;
    const busqueda = document.getElementById('buscarRequisicion').value.toLowerCase();
    
    const filas = document.querySelectorAll('.requisicion-row');
    
    filas.forEach(fila => {
        let mostrar = true;
        
        // Filtro por estado de flujo
        if (estadoFlujo) {
            if (estadoFlujo === 'rechazado') {
                // El rechazo se guarda en tres variantes segun la etapa (revision / autorizacion / general)
                if (fila.dataset.rechazada !== '1') {
                    mostrar = false;
                }
            } else if (estadoFlujo === 'pendiente_autorizacion') {
                if (!fila.dataset.estadoFlujo.startsWith('pendiente_autorizacion')) {
                    mostrar = false;
                }
            } else if (fila.dataset.estadoFlujo !== estadoFlujo) {
                mostrar = false;
            }
        }
        
        // Filtro por forma de pago
        if (formaPago && fila.dataset.formaPago !== formaPago) {
            mostrar = false;
        }
        
        // Filtro por búsqueda
        if (busqueda && !fila.dataset.busqueda.includes(busqueda)) {
            mostrar = false;
        }
        
        fila.style.display = mostrar ? '' : 'none';
    });
}

function limpiarFiltros() {
    document.getElementById('filtroEstadoFlujo').value = '';
    document.getElementById('filtroFormaPago').value = '';
    document.getElementById('buscarRequisicion').value = '';
    
    const filas = document.querySelectorAll('.requisicion-row');
    filas.forEach(fila => {
        fila.style.display = '';
    });
}

// Aplicar filtros en tiempo real para la búsqueda
document.getElementById('buscarRequisicion').addEventListener('input', aplicarFiltros);
document.getElementById('filtroEstadoFlujo').addEventListener('change', aplicarFiltros);
document.getElementById('filtroFormaPago').addEventListener('change', aplicarFiltros);

// Tooltips con el motivo del rechazo (el layout carga el bundle de Bootstrap 5 pero no los inicializa)
document.addEventListener('DOMContentLoaded', function () {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
        return;
    }
    document.querySelectorAll('.indicador-rechazo[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });
});
</script>
<?php View::endSection(); ?>