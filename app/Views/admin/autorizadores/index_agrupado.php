<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Gestión de Autorizadores';
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
    
    .stats-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .autorizador-group {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }
    
    .autorizador-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: 2px solid #e74c3c;
    }
    
    .centro-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #e74c3c;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 600;
        margin-right: 15px;
    }
    
    .centro-item {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        transition: all 0.2s ease;
    }
    
    .centro-item:hover {
        background: #e3f2fd;
        border-color: #2196f3;
        transform: translateY(-1px);
    }
    
    .badge-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .badge-active {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .badge-tipo {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        margin-right: 4px;
        margin-bottom: 4px;
    }
    
    .badge-centro { background: #e3f2fd; color: #1565c0; }
    .badge-flujo { background: #f3e5f5; color: #7b1fa2; }
    .badge-cuenta { background: #e8f5e8; color: #2e7d32; }
    .badge-metodo { background: #fff3e0; color: #ef6c00; }
    .badge-respaldo { background: #fce4ec; color: #c2185b; }
    
    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .btn-action:hover {
        transform: scale(1.1);
    }
    
    .btn-create {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        border: none;
        border-radius: 25px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    }
    
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        color: white;
    }
    
    .filter-btn {
        border-radius: 25px;
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 2px solid #dee2e6;
    }
    
    .filter-btn.active {
        background: #e74c3c;
        border-color: #e74c3c;
        color: white;
    }
    
    .filter-btn:hover {
        border-color: #e74c3c;
        color: #e74c3c;
    }
    
    .search-box {
        border-radius: 25px;
        border: 2px solid #dee2e6;
        padding: 10px 20px;
        transition: all 0.3s ease;
    }
    
    .search-box:focus {
        border-color: #e74c3c;
        box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .centros-count {
        background: #e74c3c;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        margin-left: 8px;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">
                    <i class="fas fa-user-shield me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Administra los autorizadores del sistema</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="<?= url('/admin/autorizadores/create') ?>" class="btn btn-create">
                    <i class="fas fa-plus me-2"></i>
                    Nuevo Autorizador
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Navegación de Autorizadores Especiales -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>
                        Autorizadores Especiales
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="<?= url('/admin/autorizadores/respaldos') ?>" class="btn btn-outline-danger w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3" style="min-height: 120px;">
                                <i class="fas fa-hands-helping fa-2x mb-2 text-danger"></i>
                                <strong>Autorizadores de Respaldo</strong>
                                <small class="text-muted mt-1">Gestionar respaldos temporales</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="<?= url('/admin/autorizadores/metodos-pago') ?>" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3" style="min-height: 120px;">
                                <i class="fas fa-credit-card fa-2x mb-2 text-info"></i>
                                <strong>Por Método de Pago</strong>
                                <small class="text-muted mt-1">Autorización por forma de pago</small>
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="<?= url('/admin/autorizadores/cuentas-contables') ?>" class="btn btn-outline-purple w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3" style="min-height: 120px; border-color: #6f42c1; color: #6f42c1;">
                                <i class="fas fa-calculator fa-2x mb-2" style="color: #6f42c1;"></i>
                                <strong>Por Cuenta Contable</strong>
                                <small class="text-muted mt-1">Autorización por cuenta específica</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-user-shield fa-3x text-danger mb-3"></i>
                <h3 class="mb-1"><?= count($autorizadores ?? []) ?></h3>
                <p class="text-muted mb-0">Total Registros</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h3 class="mb-1" id="total-autorizadores">0</h3>
                <p class="text-muted mb-0">Autorizadores Únicos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-building fa-3x text-info mb-3"></i>
                <h3 class="mb-1" id="total-centros">0</h3>
                <p class="text-muted mb-0">Centros Asignados</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card p-4 text-center">
                <i class="fas fa-chart-pie fa-3x text-success mb-3"></i>
                <h3 class="mb-1" id="promedio-centros">0</h3>
                <p class="text-muted mb-0">Promedio por Autorizador</p>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="btn-group" role="group">
                <a href="<?= url('/admin/autorizadores') ?>" class="btn filter-btn active">
                    Todos
                </a>
                <a href="<?= url('/admin/autorizadores?filtro=activos') ?>" class="btn filter-btn">
                    Activos
                </a>
                <a href="<?= url('/admin/autorizadores?filtro=inactivos') ?>" class="btn filter-btn">
                    Inactivos
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control search-box border-start-0"
                       placeholder="Buscar autorizador..." id="searchInput">
            </div>
        </div>
    </div>

    <!-- Lista Agrupada de Autorizadores -->
    <div id="autorizadoresContainer">
        <?php if (!empty($autorizadores)): ?>
            <?php 
            // ✅ AGRUPAR AUTORIZADORES MEJORADO - Eliminar duplicaciones
            $autorizadoresAgrupados = [];
            $centrosUnicos = []; // Evitar centros duplicados
            
            foreach ($autorizadores as $autorizador) {
                // Limpiar y normalizar datos
                $nombre = trim($autorizador->nombre ?? 'Sin nombre');
                $email = trim(strtolower($autorizador->email ?? 'sin.email@example.com'));
                
                // Clave única basada en email normalizado (más confiable que nombre)
                $key = $email;
                
                if (!isset($autorizadoresAgrupados[$key])) {
                    $autorizadoresAgrupados[$key] = [
                        'autorizador' => $autorizador,
                        'centros' => [],
                        'centro_ids' => [], // Para evitar duplicados
                        'permisos' => [
                            'centro_costo' => $autorizador->puede_autorizar_centro_costo ?? false,
                            'flujo' => $autorizador->puede_autorizar_flujo ?? false,
                            'cuenta_contable' => $autorizador->puede_autorizar_cuenta_contable ?? false,
                            'metodo_pago' => $autorizador->puede_autorizar_metodo_pago ?? false,
                            'respaldo' => $autorizador->puede_autorizar_respaldo ?? false
                        ],
                        'monto_limite_max' => $autorizador->monto_limite ?? 0,
                        'registros_count' => 1
                    ];
                } else {
                    // Si ya existe, actualizar información
                    $grupo = &$autorizadoresAgrupados[$key];
                    $grupo['registros_count']++;
                    
                    // Mantener el límite más alto
                    if (($autorizador->monto_limite ?? 0) > $grupo['monto_limite_max']) {
                        $grupo['monto_limite_max'] = $autorizador->monto_limite;
                    }
                    
                    // Combinar permisos (OR lógico)
                    $grupo['permisos']['centro_costo'] = $grupo['permisos']['centro_costo'] || ($autorizador->puede_autorizar_centro_costo ?? false);
                    $grupo['permisos']['flujo'] = $grupo['permisos']['flujo'] || ($autorizador->puede_autorizar_flujo ?? false);
                    $grupo['permisos']['cuenta_contable'] = $grupo['permisos']['cuenta_contable'] || ($autorizador->puede_autorizar_cuenta_contable ?? false);
                    $grupo['permisos']['metodo_pago'] = $grupo['permisos']['metodo_pago'] || ($autorizador->puede_autorizar_metodo_pago ?? false);
                    $grupo['permisos']['respaldo'] = $grupo['permisos']['respaldo'] || ($autorizador->puede_autorizar_respaldo ?? false);
                }
                
                // Agregar centro de costo único
                if (!empty($autorizador->centro_costo_id)) {
                    $centroId = $autorizador->centro_costo_id;
                    
                    // Solo agregar si no existe ya
                    if (!in_array($centroId, $autorizadoresAgrupados[$key]['centro_ids'])) {
                        $centro = array_filter($centros ?? [], function($c) use ($centroId) { 
                            return $c->id == $centroId; 
                        });
                        $centro = reset($centro);
                        
                        if ($centro) {
                            $autorizadoresAgrupados[$key]['centros'][] = $centro;
                            $autorizadoresAgrupados[$key]['centro_ids'][] = $centroId;
                        }
                    }
                }
            }
            
            // Ordenar por nombre para mejor presentación
            uasort($autorizadoresAgrupados, function($a, $b) {
                return strcasecmp($a['autorizador']->nombre ?? '', $b['autorizador']->nombre ?? '');
            });
            ?>
            
            <?php foreach ($autorizadoresAgrupados as $email => $grupo): ?>
                <?php $autorizador = $grupo['autorizador']; ?>
                <div class="autorizador-group autorizador-item" data-name="<?= strtolower($autorizador->nombre ?? '') ?>" data-email="<?= strtolower($autorizador->email ?? '') ?>">
                    <div class="autorizador-header p-3">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="centro-info d-flex align-items-center">
                                    <div class="centro-avatar">
                                        <?= View::e(substr($autorizador->nombre ?? 'A', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold fs-5"><?= View::e($autorizador->nombre ?? 'Sin nombre') ?></div>
                                        <small class="text-muted"><?= View::e($autorizador->email ?? 'Sin email') ?></small>
                                        <?php 
                                        // Solo mostrar badge si hay duplicados REALES (mismo autorizador + mismo centro)
                                        $emailKey = strtolower(trim($autorizador->email ?? ''));
                                        $duplicadosReales = $duplicadosPorEmail[$emailKey] ?? 0;
                                        if ($duplicadosReales > 0): ?>
                                            <span class="badge bg-danger badge-sm ms-2"><?= $duplicadosReales ?> duplicados</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="tipo-badge-container">
                                    <?php if ($grupo['permisos']['centro_costo']): ?>
                                        <span class="badge badge-tipo badge-centro">Centro</span>
                                    <?php endif; ?>
                                    <?php if ($grupo['permisos']['flujo']): ?>
                                        <span class="badge badge-tipo badge-flujo">Flujo</span>
                                    <?php endif; ?>
                                    <?php if ($grupo['permisos']['cuenta_contable']): ?>
                                        <span class="badge badge-tipo badge-cuenta">Cuenta</span>
                                    <?php endif; ?>
                                    <?php if ($grupo['permisos']['metodo_pago']): ?>
                                        <span class="badge badge-tipo badge-metodo">Método</span>
                                    <?php endif; ?>
                                    <?php if ($grupo['permisos']['respaldo']): ?>
                                        <span class="badge badge-tipo badge-respaldo">Respaldo</span>
                                    <?php endif; ?>
                                    <?php if (!array_filter($grupo['permisos'])): ?>
                                        <span class="badge badge-tipo text-muted">Sin permisos</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <?php if ($autorizador->activo ?? true): ?>
                                        <span class="badge badge-status badge-active">
                                            <i class="fas fa-check me-1"></i>Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-status badge-inactive">
                                            <i class="fas fa-times me-1"></i>Inactivo
                                        </span>
                                    <?php endif; ?>
                                    <span class="centros-count"><?= count($grupo['centros']) ?></span>
                                </div>
                                <div class="mt-1">
                                    <?php if (!empty($grupo['monto_limite_max'])): ?>
                                        <small class="text-success">Límite: Q <?= number_format($grupo['monto_limite_max'], 2) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Sin límite</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/admin/autorizadores/' . View::e($autorizador->id ?? '')) ?>"
                                       class="btn btn-outline-primary btn-action"
                                       title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= url('/admin/autorizadores/' . View::e($autorizador->id ?? '') . '/edit') ?>"
                                       class="btn btn-outline-warning btn-action"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-outline-info btn-action"
                                            title="Consolidar registros duplicados"
                                            onclick="consolidarAutorizador('<?= View::e($email) ?>', '<?= View::e($autorizador->nombre) ?>')">
                                        <i class="fas fa-compress-arrows-alt"></i>
                                    </button>
                                    <a href="<?= url('/admin/autorizadores/' . View::e($autorizador->id ?? '') . '/delete') ?>"
                                       class="btn btn-outline-danger btn-action"
                                       title="Eliminar"
                                       onclick="return confirm('¿Estás seguro de eliminar este autorizador?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($grupo['centros'])): ?>
                        <div class="centros-list p-3 bg-white">
                            <h6 class="mb-3">
                                <i class="fas fa-building me-2"></i>
                                Centros de Costo que Puede Autorizar (<?= count($grupo['centros']) ?> únicos)
                                <?php 
                                $emailKey = strtolower(trim($autorizador->email ?? ''));
                                $duplicadosReales = $duplicadosPorEmail[$emailKey] ?? 0;
                                if ($duplicadosReales > 0): ?>
                                    <span class="badge bg-warning text-dark">Consolidado de <?= ($duplicadosReales + 1) ?> registros duplicados</span>
                                <?php endif; ?>
                            </h6>
                            <div class="row">
                                <?php 
                                // Ordenar centros por nombre para mejor visualización
                                usort($grupo['centros'], function($a, $b) {
                                    return strcasecmp($a->nombre ?? '', $b->nombre ?? '');
                                });
                                ?>
                                <?php foreach ($grupo['centros'] as $index => $centro): ?>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="centro-item p-2 border rounded position-relative">
                                            <div class="fw-bold text-primary"><?= View::e($centro->nombre ?? 'Sin nombre') ?></div>
                                            <small class="text-muted">
                                                <?= View::e($centro->codigo ?? 'Sin código') ?> 
                                                <?php if (!empty($centro->descripcion)): ?>
                                                    - <?= View::e($centro->descripcion) ?>
                                                <?php endif; ?>
                                            </small>
                                            <span class="badge bg-secondary position-absolute top-0 end-0 m-1" style="font-size: 0.6rem;"><?= $index + 1 ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php 
                            $emailKey = strtolower(trim($autorizador->email ?? ''));
                            $duplicadosReales = $duplicadosPorEmail[$emailKey] ?? 0;
                            if ($duplicadosReales > 0): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Nota:</strong> Este autorizador tiene <?= ($duplicadosReales + 1) ?> registros duplicados en el sistema.
                                    Se recomienda ejecutar la limpieza de duplicados para consolidar los registros.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="centros-list p-3 bg-white">
                            <div class="text-center text-muted">
                                <i class="fas fa-building me-2"></i>
                                No tiene centros de costo asignados para autorizar
                                <?php if ($grupo['registros_count'] > 1): ?>
                                    <br><small class="text-warning">Tiene <?= $grupo['registros_count'] ?> registros duplicados sin centros asignados</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="empty-state">
                    <i class="fas fa-user-shield"></i>
                    <h4>No hay autorizadores</h4>
                    <p class="mb-3">No se encontraron autorizadores en el sistema.</p>
                    <a href="<?= url('/admin/autorizadores/create') ?>" class="btn btn-create">
                        <i class="fas fa-plus me-2"></i>Crear Primer Autorizador
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Acciones Rápidas -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="<?= url('/admin') ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Volver al Admin
            </a>
            <a href="<?= url('/dashboard') ?>" class="btn btn-outline-primary me-2">
                <i class="fas fa-home me-2"></i>Dashboard
            </a>
            <a href="<?= url('/admin/autorizadores/create') ?>" class="btn btn-create">
                <i class="fas fa-plus me-2"></i>Nuevo Autorizador
            </a>
        </div>
    </div>
</div>

<script>
    // ✅ MEJORAR GESTIÓN DE AUTORIZADORES - JavaScript optimizado
    
    // Calcular estadísticas mejoradas
    document.addEventListener('DOMContentLoaded', function() {
        const autorizadores = document.querySelectorAll('.autorizador-item');
        const totalAutorizadores = autorizadores.length;
        
        let totalCentros = 0;
        let totalRegistrosDuplicados = 0;
        
        autorizadores.forEach(item => {
            const countElement = item.querySelector('.centros-count');
            if (countElement) {
                totalCentros += parseInt(countElement.textContent);
            }
            
            // Contar registros duplicados
            const registrosElement = item.querySelector('.badge-sm');
            if (registrosElement) {
                const registrosText = registrosElement.textContent;
                const match = registrosText.match(/(\d+) registros/);
                if (match) {
                    totalRegistrosDuplicados += parseInt(match[1]) - 1; // -1 porque el original no es duplicado
                }
            }
        });
        
        const promedioCentros = totalAutorizadores > 0 ? (totalCentros / totalAutorizadores).toFixed(1) : 0;
        
        document.getElementById('total-autorizadores').textContent = totalAutorizadores;
        document.getElementById('total-centros').textContent = totalCentros;
        document.getElementById('promedio-centros').textContent = promedioCentros;
        
        // Mostrar alerta si hay duplicados
        if (totalRegistrosDuplicados > 0) {
            mostrarAlertaDuplicados(totalRegistrosDuplicados);
        }
    });

    // Búsqueda mejorada con resaltado
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const items = document.querySelectorAll('.autorizador-item');
        let visibleCount = 0;

        items.forEach(item => {
            const name = item.dataset.name || '';
            const email = item.dataset.email || '';
            const centrosText = item.querySelector('.centros-list')?.textContent.toLowerCase() || '';
            
            if (name.includes(searchTerm) || email.includes(searchTerm) || centrosText.includes(searchTerm)) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Actualizar contador de resultados
        actualizarContadorResultados(visibleCount, items.length);
    });

    // Función para consolidar autorizador
    function consolidarAutorizador(email, nombre) {
        if (confirm(`¿Deseas consolidar todos los registros duplicados de ${nombre}?\n\nEsto combinará todos los centros de costo y permisos en un solo registro.`)) {
            // Aquí iría la llamada AJAX para consolidar
            fetch('/admin/autorizadores/consolidar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    email: email,
                    accion: 'consolidar'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarExito('Registros consolidados exitosamente');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    mostrarError(data.error || 'Error al consolidar registros');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error de conexión al consolidar registros');
            });
        }
    }
    
    // Mostrar alerta de duplicados
    function mostrarAlertaDuplicados(count) {
        const alertHtml = `
            <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Atención:</strong> Se detectaron ${count} registros duplicados en el sistema.
                <small class="d-block mt-1">Usa el botón <i class="fas fa-compress-arrows-alt"></i> para consolidar registros del mismo autorizador.</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.querySelector('.container').insertAdjacentHTML('afterbegin', alertHtml);
    }
    
    // Actualizar contador de resultados de búsqueda
    function actualizarContadorResultados(visible, total) {
        let contadorElement = document.getElementById('resultados-contador');
        if (!contadorElement) {
            contadorElement = document.createElement('small');
            contadorElement.id = 'resultados-contador';
            contadorElement.className = 'text-muted ms-2';
            document.getElementById('searchInput').parentNode.appendChild(contadorElement);
        }
        
        if (visible < total) {
            contadorElement.textContent = `${visible} de ${total} resultados`;
            contadorElement.style.display = 'inline';
        } else {
            contadorElement.style.display = 'none';
        }
    }
    
    // Mostrar mensajes de éxito y error
    function mostrarExito(mensaje) {
        mostrarToast(mensaje, 'success');
    }
    
    function mostrarError(mensaje) {
        mostrarToast(mensaje, 'danger');
    }
    
    function mostrarToast(mensaje, tipo) {
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${tipo} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${mensaje}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();
        
        // Limpiar después de mostrar
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    // Efecto hover en las tarjetas de estadísticas
    document.querySelectorAll('.stats-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Actualizar filtros activos basado en URL
    const urlParams = new URLSearchParams(window.location.search);
    const filtro = urlParams.get('filtro');
    
    if (filtro) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        const activeBtn = document.querySelector(`a[href="<?= url('/admin/autorizadores') ?>?filtro=${filtro}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
    
    // Expandir/colapsar lista de centros para grupos grandes
    document.querySelectorAll('.centros-list').forEach(lista => {
        const centros = lista.querySelectorAll('.centro-item');
        if (centros.length > 6) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'btn btn-link btn-sm mt-2';
            toggleBtn.textContent = `Ver todos los ${centros.length} centros`;
            
            // Ocultar centros después del 6to
            centros.forEach((centro, index) => {
                if (index >= 6) {
                    centro.style.display = 'none';
                }
            });
            
            toggleBtn.addEventListener('click', function() {
                const ocultos = Array.from(centros).slice(6);
                const mostrarTodos = ocultos[0].style.display === 'none';
                
                ocultos.forEach(centro => {
                    centro.style.display = mostrarTodos ? '' : 'none';
                });
                
                this.textContent = mostrarTodos ? 'Ver menos' : `Ver todos los ${centros.length} centros`;
            });
            
            lista.appendChild(toggleBtn);
        }
    });
</script>
<?php View::endSection(); ?>
