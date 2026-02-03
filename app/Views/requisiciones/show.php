<?php
use App\Helpers\View;
use App\Helpers\EstadoHelper;

View::startSection('title', 'Detalle de Requisición');
View::startSection('content');

// Función auxiliar para acceder a datos
function getData($data, $key, $default = '') {
    if (is_object($data)) {
        return $data->$key ?? $default;
    } elseif (is_array($data)) {
        return $data[$key] ?? $default;
    }
    return $default;
}

// Función helper para aproximar porcentajes a 100% cuando están cerca
function aproximarPorcentaje($porcentaje) {
    // Si el porcentaje está entre 99.9% y 100.1%, aproximar a 100%
    if ($porcentaje >= 99.9 && $porcentaje <= 100.1) {
        return 100.0;
    }
    return $porcentaje;
}

// Función para formatear porcentaje con aproximación
function formatearPorcentaje($porcentaje, $decimales = 5) {
    $porcentajeAproximado = aproximarPorcentaje($porcentaje);
    return number_format($porcentajeAproximado, $decimales) . '%';
}
?>

<style>
    .section-header {
        background: #000;
        color: #fff;
        padding: 12px 20px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #333;
        margin-bottom: 8px;
    }
    
    .table-dark-custom {
        background: #000 !important;
        color: #fff !important;
    }
    
    .table-dark-custom th {
        font-weight: 600;
        font-size: 13px;
        padding: 12px 8px;
        border: none;
    }
    
    .btn-add-item {
        color: #00bfa5;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        margin-top: 15px;
    }
    
    .btn-add-item:hover {
        color: #009688;
        text-decoration: none;
    }
    
    .btn-add-item i {
        background: #00bfa5;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    
    .upload-zone {
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        background: #fafafa;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .upload-zone:hover {
        border-color: #00bfa5;
        background: #f0f9f8;
    }
    
    .upload-zone .upload-icon {
        font-size: 32px;
        color: #666;
        margin-bottom: 10px;
    }
    
    .card-form {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 30px;
        margin-bottom: 20px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Animaciones de carga */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-spinner {
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #00bfa5;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .loading-text {
        color: #333;
        font-weight: 600;
        margin: 0;
    }
    
    /* Animaciones de validación */
    .form-control.validating {
        border-color: #ffc107;
        box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
    }
    
    .form-control.valid {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        animation: validPulse 0.6s ease-in-out;
    }
    
    .form-control.invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        animation: invalidShake 0.6s ease-in-out;
    }
    
    @keyframes validPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    
    @keyframes invalidShake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    /* Animación para botones */
    .btn-submit {
        position: relative;
        overflow: hidden;
    }
    
    .btn-submit.loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .btn-submit .btn-spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-right: 8px;
    }
    
    .btn-submit.loading .btn-spinner {
        display: inline-block;
    }
    
    /* Responsive padding para dispositivos móviles */
    @media (max-width: 768px) {
        .card-form {
            padding: 20px 15px;
            margin-bottom: 15px;
        }
        
        .container {
            padding-left: 15px !important;
            padding-right: 15px !important;
        }
    }
    
    /* Espaciado mejorado para pantallas grandes */
    @media (min-width: 1200px) {
        .container {
            max-width: 1140px !important;
            margin: 0 auto;
        }
    }
    
    @media (min-width: 992px) and (max-width: 1199px) {
        .container {
            max-width: 960px !important;
        }
    }
    
    .logo-header {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .logo-header img {
        max-width: 150px;
        height: auto;
    }
    
    .form-title {
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        color: #000;
        margin-bottom: 30px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .total-display {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        text-align: right;
        font-weight: 700;
        font-size: 16px;
        margin-top: 15px;
    }
    
    .btn-guardar {
        background: #000;
        color: #fff;
        padding: 12px 40px;
        font-weight: 600;
        border: none;
        border-radius: 4px;
    }
    
    .btn-guardar:hover {
        background: #333;
        color: #fff;
    }
    
    .btn-cancelar {
        background: #e91e63;
        color: #fff;
        padding: 12px 40px;
        font-weight: 600;
        border: none;
        border-radius: 4px;
    }
    
    .btn-cancelar:hover {
        background: #c2185b;
        color: #fff;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: #fff;
        padding: 12px 40px;
        font-weight: 600;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: #545b62;
        color: #fff;
        transform: translateY(-1px);
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #00bfa5;
        box-shadow: 0 0 0 0.2rem rgba(0,191,165,0.15);
    }
    
    /* Estilos para estado badge */
    .estado-badge {
        font-size: 14px;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .estado-pendiente { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #8b4513; border: 1px solid #ffd1dc; box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2); }
    .estado-pendiente-revision { background: linear-gradient(135deg, #e2e3e5 0%, #c6c8ca 100%); color: #495057; border: 1px solid #ced4da; box-shadow: 0 2px 8px rgba(108, 117, 125, 0.2); }
    .estado-pendiente-autorizacion { background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%); color: #856404; border: 1px solid #ffc107; box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2); }
    .estado-aprobada { background: linear-gradient(135deg, #d4edda 0%, #a3d9a4 100%); color: #155724; border: 1px solid #c3e6cb; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2); }
    .estado-autorizada { background: linear-gradient(135deg, #cff4fc 0%, #9fddff 100%); color: #055160; border: 1px solid #b6effb; box-shadow: 0 2px 8px rgba(13, 202, 240, 0.2); }
    .estado-rechazada { background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; border: 1px solid #f5c6cb; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2); }
    .estado-completada { background: linear-gradient(135deg, #d1ecf1 0%, #b8daff 100%); color: #0c5460; border: 1px solid #bee5eb; box-shadow: 0 2px 8px rgba(13, 202, 240, 0.2); }
    .estado-borrador { background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%); color: #495057; border: 1px solid #ced4da; box-shadow: 0 2px 8px rgba(108, 117, 125, 0.2); }

    /* Autocompletado cuenta contable */
.cuenta-contable-wrapper { 
    position: relative !important; 
    z-index: 2147483646 !important; /* Z-index alto para el wrapper */
    overflow: visible !important;
    isolation: isolate; /* Crear nuevo contexto de apilamiento */
}
.cuenta-contable-suggestions { 
    position: absolute; 
    top: 100%; 
    left: 0; 
    right: 0; 
    background: white; 
    border: 1px solid #ddd; 
    max-height: 250px; 
    overflow-y: auto; 
    z-index: 999999 !important; /* Z-index máximo para estar por encima de TODO */
    box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
    display: none;
    border-radius: 4px;
}
.cuenta-contable-suggestions.show { 
    display: block; 
    z-index: 999999 !important; /* Asegurar z-index cuando se muestra */
}
.cuenta-suggestion-item { 
    padding: 10px 15px; 
    cursor: pointer; 
    border-bottom: 1px solid #f0f0f0; 
    transition: all 0.2s; 
}
.cuenta-suggestion-item:hover { background: #f8f9fa; }
.cuenta-suggestion-item:last-child { border-bottom: none; }
.cuenta-suggestion-codigo { font-weight: 600; color: #333; font-size: 13px; }
.cuenta-suggestion-nombre { color: #666; font-size: 12px; margin-top: 2px; }
.cuenta-loading, .cuenta-no-results { 
    padding: 10px 15px; 
    text-align: center; 
    color: #999; 
    font-size: 13px; 
    font-style: italic;
}

/* Asegurar que las sugerencias estén por encima de todo */
.cuenta-contable-suggestions {
    position: absolute !important;
    z-index: 999999 !important; /* Z-index máximo */
    background: white !important;
    border: 1px solid #ccc !important;
    box-shadow: 0 6px 16px rgba(0,0,0,0.2) !important;
}

/* Estilos específicos para sugerencias con posición fixed */
.cuenta-contable-suggestions[style*="position: fixed"] {
    position: fixed !important;
    z-index: 999999 !important; /* Z-index máximo */
    background: white !important;
    border: 1px solid #ccc !important;
    box-shadow: 0 6px 16px rgba(0,0,0,0.2) !important;
    border-radius: 4px;
    max-height: 250px;
    overflow-y: auto;
}

/* Ajustar el z-index de la tabla para que no interfiera */
.table-responsive {
    position: relative;
    z-index: 1;
    overflow: visible !important;
    margin-bottom: 15px;
}

/* Mejorar la tabla en dispositivos móviles */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .table th, .table td {
        padding: 8px 6px;
        font-size: 0.8rem;
    }
    
    .form-control, .form-select {
        font-size: 0.875rem;
        padding: 6px 8px;
    }
}

/* Permitir que las sugerencias se salgan de la tabla */
.table {
    overflow: visible !important;
}

.table td {
    overflow: visible !important;
    position: relative;
}

/* Asegurar que los botones NO interfieran - z-index muy bajo */
.btn-add-item {
    position: relative;
    z-index: 1 !important; /* Z-index muy bajo para que las sugerencias lo cubran */
}

/* Cuando hay sugerencias activas, OCULTAR completamente el botón */
.cuenta-contable-suggestions.show ~ * .btn-add-item,
.cuenta-contable-suggestions.show + * .btn-add-item,
.cuenta-contable-suggestions.show ~ .btn-add-item,
.cuenta-contable-suggestions.show + .btn-add-item,
.cuenta-contable-suggestions.show ~ * * .btn-add-item,
.cuenta-contable-suggestions.show + * * .btn-add-item {
    z-index: -1 !important; /* Z-index negativo para ocultarlo completamente */
    opacity: 0 !important; /* Completamente invisible */
    pointer-events: none !important; /* Deshabilitar clics en el botón */
    visibility: hidden !important; /* Ocultar visualmente */
}

/* Ocultar CUALQUIER botón cuando hay sugerencias activas */
body:has(.cuenta-contable-suggestions.show) .btn-add-item {
    z-index: -1 !important;
    opacity: 0 !important;
    pointer-events: none !important;
    visibility: hidden !important;
}

/* REGLAS AGRESIVAS PARA SOBREPONERSE A TODO */
.cuenta-contable-suggestions {
    position: absolute !important;
    z-index: 2147483647 !important; /* Z-index máximo de CSS */
    background: white !important;
    border: 2px solid #007bff !important;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3) !important;
    border-radius: 6px !important;
}

.cuenta-contable-suggestions.show {
    position: absolute !important;
    z-index: 2147483647 !important; /* Z-index máximo de CSS */
    display: block !important;
    background: white !important;
    border: 2px solid #007bff !important;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3) !important;
}

/* Forzar que las sugerencias se sobrepongan a cualquier elemento */
.cuenta-contable-suggestions,
.cuenta-contable-suggestions * {
    z-index: 2147483647 !important;
    position: relative !important;
}

/* Asegurar que ningún elemento interfiera con las sugerencias */
* {
    position: relative;
    z-index: auto;
}

.cuenta-contable-suggestions {
    position: absolute !important;
    z-index: 2147483647 !important;
}

/* SOBREPONERSE A MODALES, DROPDOWNS Y CUALQUIER ELEMENTO */
.cuenta-contable-suggestions {
    z-index: 2147483647 !important;
    position: absolute !important;
}

/* Forzar que esté por encima de Bootstrap modals, dropdowns, etc. */
.cuenta-contable-suggestions {
    z-index: 2147483647 !important;
    position: absolute !important;
}

/* Asegurar que esté por encima de cualquier elemento con z-index alto */
.cuenta-contable-suggestions.show {
    z-index: 2147483647 !important;
    position: absolute !important;
    display: block !important;
}

/* SOBREPONERSE A TODOS LOS CAMPOS DE FORMULARIO */
.cuenta-contable-suggestions {
    z-index: 2147483647 !important;
    position: absolute !important;
}

/* Asegurar que se sobreponga a campos de Forma de Pago, Ubicación, etc. */
.cuenta-contable-suggestions.show {
    z-index: 2147483647 !important;
    position: absolute !important;
    display: block !important;
}

/* Forzar que esté por encima de TODOS los elementos del formulario */
.cuenta-contable-suggestions.show ~ *,
.cuenta-contable-suggestions.show ~ * *,
.cuenta-contable-suggestions.show + *,
.cuenta-contable-suggestions.show + * * {
    z-index: 1 !important;
}

/* Asegurar que las sugerencias estén por encima de Bootstrap y cualquier framework */
.cuenta-contable-suggestions {
    z-index: 2147483647 !important;
    position: absolute !important;
}

/* Forzar que esté por encima de modales, dropdowns, tooltips, etc. */
.cuenta-contable-suggestions.show {
    z-index: 2147483647 !important;
    position: absolute !important;
    display: block !important;
}
</style>

<div class="container py-4" style="max-width: 1200px;">
        <div class="card-form">
            <!-- Logo y Título -->
            <div class="logo-header">
                <img src="/assets/images/logo-iga.png" alt="IGA" onerror="this.style.display='none'">
            </div>
            
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="form-title mb-0" style="text-align: left;">
                Requisición #<?php echo View::e(getData($orden, 'id', 'N/A')); ?>
            </h1>
            <div>
                <?php 
                $estadoReal = $orden->getEstadoReal();
                $badge = EstadoHelper::getBadge($estadoReal);
                $estadoClass = 'estado-' . str_replace('_', '-', $estadoReal);
                ?>
                <span class="estado-badge <?php echo $estadoClass; ?> <?php echo $badge['class']; ?>" style="font-size: 14px; padding: 10px 20px; border-radius: 20px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                    <?php echo $badge['text']; ?>
                </span>
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="d-flex justify-content-end gap-3 mb-4">
            <a href="<?= url('/requisiciones') ?>" class="btn btn-cancelar">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
            <?php if (getData($orden, 'id')): ?>
                <a href="<?= url('/requisiciones/' . getData($orden, 'id') . '/imprimir') ?>" 
                   class="btn btn-secondary" target="_blank">
                    <i class="fas fa-print me-2"></i> Imprimir
                </a>
                <?php 
                // Solo mostrar botón de editar si:
                // 1. El usuario es el creador de la requisición
                // 2. La requisición está en estado "rechazado"
                $usuarioActual = $_SESSION['user_id'] ?? null;
                $estadoReal = getData($orden, 'id') ? EstadoHelper::getEstado(getData($orden, 'id')) : 'borrador';
                $puedeEditar = ($usuarioActual == getData($orden, 'usuario_id')) && ($estadoReal === 'rechazado');
                
                if ($puedeEditar): ?>
                    <a href="<?= url('/requisiciones/' . getData($orden, 'id') . '/editar') ?>" 
                       class="btn btn-guardar">
                        <i class="fas fa-edit me-2"></i> Editar
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
            
            <!-- INFORMACIÓN GENERAL -->
            <h2 class="h5 mb-4" style="border-bottom: 2px solid #000; padding-bottom: 10px;">
                Información General
            </h2>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Nombre o Razón Social</label>
                    <div class="form-control" style="background: #f8f9fa; border: 1px solid #dee2e6; min-height: 38px; display: flex; align-items: center;">
                        <?php echo View::e(getData($orden, 'proveedor_nombre', 'N/A')); ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Unidad Requirente</label>
                    <div class="form-control" style="background: #f8f9fa; border: 1px solid #dee2e6; min-height: 38px; display: flex; align-items: center;">
                        <?php 
                        $unidadId = getData($orden, 'unidad_requirente');
                        $unidadNombre = 'N/A';
                        if (!empty($unidades_requirentes) && $unidadId) {
                            foreach ($unidades_requirentes as $unidad) {
                                if ($unidad['id'] == $unidadId) {
                                    $unidadNombre = $unidad['nombre'] ?? $unidad['descripcion'] ?? 'Sin nombre';
                                    break;
                                }
                            }
                        }
                        echo View::e($unidadNombre);
                        ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <div class="form-control" style="background: #f8f9fa; border: 1px solid #dee2e6; min-height: 38px; display: flex; align-items: center;">
                        <?php echo date('d/m/Y', strtotime(getData($orden, 'fecha_solicitud', date('Y-m-d')))); ?>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Causal de Compra</label>
                    <div class="form-control" style="background: #f8f9fa; border: 1px solid #dee2e6; min-height: 38px; display: flex; align-items: center;">
                        <?php 
                        $causal = getData($orden, 'causal_compra', '');
                        $causales = [
                            'tramite_normal' => 'Trámite Normal',
                            'eventualidad' => 'Eventualidad',
                            'emergencia' => 'Emergencia'
                        ];
                        echo View::e($causales[$causal] ?? $causal);
                        ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Moneda</label>
                    <div class="form-control" style="background: #f8f9fa; border: 1px solid #dee2e6; min-height: 38px; display: flex; align-items: center;">
                        <?php 
                        $moneda = getData($orden, 'moneda', 'GTQ');
                        $monedas = [
                            'GTQ' => 'Quetzales (GTQ)',
                            'USD' => 'Dólares (USD)',
                            'EUR' => 'Euros (EUR)'
                        ];
                        echo View::e($monedas[$moneda] ?? $moneda);
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ITEMS/DESCRIPCIÓN -->
        <div class="card-form">
            <div class="table-responsive">
                <table class="table table-bordered form-table" id="tablaItems">
                    <thead class="table-dark-custom">
                        <tr>
                            <th style="width: 10%">Cantidad</th>
                            <th style="width: 40%">Descripción</th>
                            <th style="width: 20%">Precio Unitario</th>
                            <th style="width: 20%">Total</th>
                            <th style="width: 10%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <?php if (!empty($items)): ?>
                            <?php 
                            $totalGeneral = 0;
                            foreach ($items as $item): 
                                $totalItem = (getData($item, 'cantidad', 0) * getData($item, 'precio_unitario', 0));
                                $totalGeneral += $totalItem;
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <strong><?php echo number_format(getData($item, 'cantidad', 0), 0); ?></strong>
                            </td>
                                    <td><?php echo View::e(getData($item, 'descripcion', '')); ?></td>
                                    <td class="text-end">
                                        <?php echo View::money(getData($item, 'precio_unitario', 0), $moneda); ?>
                            </td>
                                    <td class="text-end">
                                        <strong><?php echo View::money($totalItem, $moneda); ?></strong>
                            </td>
                                    <td></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <em>No hay items registrados</em>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="total-display">
                Total: <span id="totalGeneral"><?php echo View::money($totalGeneral ?? 0, $moneda); ?></span>
            </div>
        </div>
        
        <!-- DISTRIBUCIÓN DE GASTO -->
        <div class="card-form">
            <div class="section-header">
                <i class="fas fa-chart-pie"></i>
                DISTRIBUCIÓN DE GASTO
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered form-table" id="tablaDistribucion">
                    <thead class="table-dark-custom">
                        <tr>
                            <th>Cuenta Contable</th>
                            <th>Centro de Costo</th>
                            <th>Ubicación</th>
                            <th>Unidad de Negocio</th>
                            <th>Porcentaje</th>
                            <th>Cantidad</th>
                            <th>Factura</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="distribucionBody">
                        <?php if (!empty($distribucion)): ?>
                            <?php foreach ($distribucion as $dist): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $cuentaId = getData($dist, 'cuenta_contable_id');
                                        $cuentaNombre = getData($dist, 'cuenta_contable_codigo', '') . ' - ' . getData($dist, 'cuenta_nombre', '');
                                        if (empty($cuentaNombre) && !empty($cuentas_contables) && $cuentaId) {
                                            foreach ($cuentas_contables as $cuenta) {
                                                if ($cuenta->id == $cuentaId) {
                                                    $cuentaNombre = ($cuenta->codigo ?? '') . ' - ' . ($cuenta->descripcion ?? '');
                                                    break;
                                                }
                                            }
                                        }
                                        echo View::e($cuentaNombre ?: 'N/A');
                                        ?>
                            </td>
                                    <td>
                                        <?php 
                                        $centroId = getData($dist, 'centro_costo_id');
                                        $centroNombre = getData($dist, 'centro_nombre', '');
                                        if (empty($centroNombre) && !empty($centros_costo) && $centroId) {
                                            foreach ($centros_costo as $centro) {
                                                if ($centro->id == $centroId) {
                                                    $centroNombre = $centro->nombre ?? $centro->descripcion ?? 'Sin nombre';
                                                    break;
                                                }
                                            }
                                        }
                                        echo View::e($centroNombre ?: 'N/A');
                                        ?>
                            </td>
                                    <td>
                                        <?php 
                                        $ubicacionId = getData($dist, 'ubicacion_id');
                                        $ubicacionNombre = getData($dist, 'ubicacion_nombre', '');
                                        if (empty($ubicacionNombre) && !empty($ubicaciones) && $ubicacionId) {
                                            foreach ($ubicaciones as $ubicacion) {
                                                if ($ubicacion['id'] == $ubicacionId) {
                                                    $ubicacionNombre = $ubicacion['nombre'] ?? $ubicacion['descripcion'] ?? 'Sin nombre';
                                                    break;
                                                }
                                            }
                                        }
                                        echo View::e($ubicacionNombre ?: 'N/A');
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $unidadId = getData($dist, 'unidad_negocio_id');
                                        $unidadNombre = getData($dist, 'unidad_negocio_nombre', '');
                                        if (empty($unidadNombre) && !empty($unidades_negocio) && $unidadId) {
                                            foreach ($unidades_negocio as $unidad) {
                                                if ($unidad['id'] == $unidadId) {
                                                    $unidadNombre = $unidad['nombre'] ?? $unidad['descripcion'] ?? 'Sin nombre';
                                                    break;
                                                }
                                            }
                                        }
                                        echo View::e($unidadNombre ?: 'N/A');
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo number_format(getData($dist, 'porcentaje', 0), 5); ?>%
                                    </td>
                                    <td class="text-end">
                                        <strong><?php echo View::money(getData($dist, 'cantidad', 0), $moneda); ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        $facturaVal = getData($dist, 'factura', 1);
                                        // Si es número, formatear como "Factura X"
                                        if (is_numeric($facturaVal)) {
                                            echo 'Factura ' . intval($facturaVal);
                                        } else {
                                            echo View::e($facturaVal);
                                        }
                                        ?>
                                    </td>
                                    <td></td>
                        </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    <em>No hay distribución registrada</em>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- FACTURAS -->
        <div class="card-form">
            <div class="section-header">
                <i class="fas fa-file-invoice"></i>
                FACTURAS
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="tablaResumenFacturas">
                    <thead class="table-dark-custom">
                        <tr>
                            <th style="width: 25%">Forma de Pago</th>
                            <th style="width: 15%">Anticipo</th>
                            <th style="width: 15%">Facturas</th>
                            <th style="width: 20%">Porcentaje</th>
                            <th style="width: 25%">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td rowspan="4">
                                <div class="form-control" style="background: #f8f9fa; border: 1px solid #dee2e6; min-height: 38px; display: flex; align-items: center;">
                                    <?php 
                                    $formaPago = getData($orden, 'forma_pago', '');
                                    $formasPago = [
                                        'contado' => 'Contado',
                                        'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito (Lic. Milton)',
                                        'cheque' => 'Cheque',
                                        'transferencia' => 'Transferencia',
                                        'credito' => 'Crédito'
                                    ];
                                    echo View::e($formasPago[$formaPago] ?? $formaPago ?: 'N/A');
                                    ?>
                                </div>
                            </td>
                            <td rowspan="4">
                                <div class="form-control" style="background: #f8f9fa; border: 1px solid #dee2e6; min-height: 38px; display: flex; align-items: center;">
                                    <?php echo (getData($orden, 'anticipo', 0) == 1) ? 'Sí' : 'No'; ?>
                                </div>
                            </td>
                            <td><strong>Factura 1</strong></td>
                            <td>
                                <?php 
                                $factura1 = ['porcentaje' => 0, 'monto' => 0];
                                $factura2 = ['porcentaje' => 0, 'monto' => 0];
                                $factura3 = ['porcentaje' => 0, 'monto' => 0];
                                if (!empty($distribucion)) {
                                    foreach ($distribucion as $dist) {
                                        $facturaNum = getData($dist, 'factura', 1);
                                        // Convertir a número si viene como texto
                                        if (is_string($facturaNum) && preg_match('/Factura\s*(\d)/i', $facturaNum, $matches)) {
                                            $facturaNum = intval($matches[1]);
                                        } else {
                                            $facturaNum = intval($facturaNum) ?: 1;
                                        }
                                        
                                        $porcentaje = floatval(getData($dist, 'porcentaje', 0));
                                        $monto = floatval(getData($dist, 'cantidad', 0));
                                        
                                        if ($facturaNum == 1) {
                                            $factura1['porcentaje'] += $porcentaje;
                                            $factura1['monto'] += $monto;
                                        } elseif ($facturaNum == 2) {
                                            $factura2['porcentaje'] += $porcentaje;
                                            $factura2['monto'] += $monto;
                                        } elseif ($facturaNum == 3) {
                                            $factura3['porcentaje'] += $porcentaje;
                                            $factura3['monto'] += $monto;
                                        }
                                    }
                                }
                                echo formatearPorcentaje($factura1['porcentaje'], 5);
                                ?>
                            </td>
                            <td><strong><?php echo View::money($factura1['monto'], $moneda); ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Factura 2</strong></td>
                            <td>
                                <?php echo formatearPorcentaje($factura2['porcentaje'], 5); ?>
                            </td>
                            <td><strong><?php echo View::money($factura2['monto'], $moneda); ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>Factura 3</strong></td>
                            <td>
                                <?php echo formatearPorcentaje($factura3['porcentaje'], 5); ?>
                            </td>
                            <td><strong><?php echo View::money($factura3['monto'], $moneda); ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>TOTAL</strong></td>
                            <td><strong><?php 
                                $totalPorcentaje = $factura1['porcentaje'] + $factura2['porcentaje'] + $factura3['porcentaje'];
                                $totalAproximado = aproximarPorcentaje($totalPorcentaje);
                                echo number_format($totalAproximado, 5);
                            ?></strong></td>
                            <td><strong><?php echo View::money($factura1['monto'] + $factura2['monto'] + $factura3['monto'], $moneda); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- DATOS ADJUNTOS -->
        <div class="card-form">
            <div class="section-header">
                <i class="fas fa-paperclip"></i>
                ARCHIVOS ADJUNTOS
            </div>
            
            <?php if (!empty($archivos)): ?>
                <div class="row">
                    <?php foreach ($archivos as $archivo): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="p-3 border rounded bg-light">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php 
                                        $extension = strtolower(pathinfo(getData($archivo, 'nombre_archivo', ''), PATHINFO_EXTENSION));
                                        $iconClass = match($extension) {
                                            'pdf' => 'fas fa-file-pdf text-danger',
                                            'doc', 'docx' => 'fas fa-file-word text-primary',
                                            'xls', 'xlsx' => 'fas fa-file-excel text-success',
                                            'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image text-info',
                                            default => 'fas fa-file text-secondary'
                                        };
                                        ?>
                                        <i class="<?php echo $iconClass; ?>" style="font-size: 2rem;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?php echo View::e(getData($archivo, 'nombre_original') ?: getData($archivo, 'nombre_archivo')); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php 
                                            $tamaño = getData($archivo, 'tamaño');
                                            if ($tamaño) {
                                                echo number_format($tamaño / 1024, 1) . ' KB';
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="<?= url('/archivos/' . getData($archivo, 'id') . '/descargar') ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-paperclip fa-3x mb-3"></i>
                    <p>No hay archivos adjuntos</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- ESPECIFICACIONES Y DATOS DEL PROVEEDOR -->
        <div class="card-form">
            <div class="section-header">
                <i class="fas fa-cogs"></i>
                ESPECIFICACIONES Y DATOS DEL PROVEEDOR
            </div>
            
            <div class="mb-3">
                <label class="form-label">Especificaciones y Datos del Proveedor</label>
                <div class="form-control" style="background: #f8f9fa; border: 1px solid #dee2e6; min-height: 120px; padding: 12px; white-space: pre-wrap;">
                    <?php 
                    $especificaciones = getData($orden, 'observaciones', '');
                    echo !empty($especificaciones) ? View::e($especificaciones) : '<em class="text-muted">No especificado</em>';
                    ?>
                </div>
            </div>
        </div>
        
        <!-- RAZÓN DE SELECCIÓN DE COTIZACIÓN -->
        <div class="card-form">
            <div class="section-header">
                <i class="fas fa-file-alt"></i>
                RAZÓN DE SELECCIÓN DE COTIZACIÓN
            </div>
            
            <div class="mb-3">
                <label class="form-label">Razón de Selección de Cotización</label>
                <div class="form-control" style="background: #f8f9fa; border: 1px solid #dee2e6; min-height: 120px; padding: 12px; white-space: pre-wrap;">
                    <?php 
                    $justificacion = getData($orden, 'justificacion', '');
                    echo !empty($justificacion) ? View::e($justificacion) : '<em class="text-muted">No especificado</em>';
                    ?>
            </div>
        </div>
            </div>
        </div>
</div>

<?php View::endSection(); ?>
