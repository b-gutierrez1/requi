<?php
use App\Helpers\View;

View::startSection('content');
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
    <form id="requisicionForm" method="POST" action="/requisiciones" enctype="multipart/form-data">
        <?php echo App\Middlewares\CsrfMiddleware::field(); ?>
        
        <div class="card-form">
            <!-- Logo y Título -->
            <div class="logo-header">
                <img src="/assets/images/logo-iga.png" alt="IGA" onerror="this.style.display='none'">
            </div>
            
            <h1 class="form-title">
                Requisición para compra de bienes y contratación de servicios
            </h1>
            
            <!-- INFORMACIÓN GENERAL -->
            <h2 class="h5 mb-4" style="border-bottom: 2px solid #000; padding-bottom: 10px;">
                Información General
            </h2>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="nombre_razon_social" class="form-label">Nombre o Razón Social</label>
                    <input type="text" class="form-control" id="nombre_razon_social" name="nombre_razon_social" required>
                </div>
                
                <div class="col-md-4">
                    <label for="unidad_requirente" class="form-label">Unidad Requirente</label>
                    <select class="form-select" id="unidad_requirente" name="unidad_requirente" required>
                        <option value="">Seleccione...</option>
                        <?php if (!empty($unidades_requirentes)): ?>
                            <?php foreach ($unidades_requirentes as $unidad): ?>
                                <option value="<?= $unidad->id ?>">
                                    <?= View::e($unidad->nombre ?? $unidad->descripcion ?? 'Sin nombre') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="causal_compra" class="form-label">Causal de Compra</label>
                    <select class="form-select" id="causal_compra" name="causal_compra" required>
                        <option value="">Seleccione una opción</option>
                        <option value="tramite_normal">Trámite Normal</option>
                        <option value="eventualidad">Eventualidad</option>
                        <option value="emergencia">Emergencia</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="moneda" class="form-label">Moneda</label>
                    <select class="form-select" id="moneda" name="moneda" required>
                        <option value="GTQ" selected>Quetzales (GTQ)</option>
                        <option value="USD">Dólares (USD)</option>
                        <option value="EUR">Euros (EUR)</option>
                    </select>
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
                        <tr class="item-row">
                            <td>
                                <input type="number" class="form-control item-cantidad" name="items[0][cantidad]" min="1" step="0.01" value="1" required>
                            </td>
                            <td>
                                <textarea class="form-control item-descripcion" name="items[0][descripcion]" rows="2" required></textarea>
                            </td>
                            <td>
                                <input type="number" class="form-control item-precio" name="items[0][precio_unitario]" min="0" step="0.01" required>
                            </td>
                            <td>
                                <input type="number" class="form-control item-total" name="items[0][total]" readonly>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger btn-eliminar-item" onclick="eliminarItem(this)">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <a href="javascript:void(0)" class="btn-add-item" onclick="agregarItem()">
                <i class="fas fa-plus"></i>
                Agregar Item
            </a>
            
            <div class="total-display">
                Total: <span id="totalGeneral">Q 0.00</span>
                <input type="hidden" id="total_general" name="total_general" value="0">
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
                        <tr class="distribucion-row">
                            <td>
                                <div class="cuenta-contable-wrapper">
                                    <input type="text" 
                                           class="form-control cuenta-contable-input" 
                                           name="distribucion[0][cuenta_contable_display]"
                                           placeholder="Buscar cuenta..." 
                                           autocomplete="off"
                                           data-index="0">
                                    <input type="hidden" 
                                           name="distribucion[0][cuenta_contable_id]" 
                                           class="cuenta-contable-id" 
                                           required>
                                    <div class="cuenta-contable-suggestions"></div>
                                </div>
                            </td>
                            <td><select class="form-select" name="distribucion[0][centro_costo_id]" required>
                                    <option value="">Seleccione...</option>
                                    <?php if (!empty($centros_costo)): ?>
                                        <?php foreach ($centros_costo as $centro): ?>
                                            <option value="<?= $centro->id ?>">
                                                <?= View::e($centro->nombre ?? $centro->descripcion ?? 'Sin nombre') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select></td>
                            <td><select class="form-select" name="distribucion[0][ubicacion_id]">
                                    <option value="">Seleccione...</option>
                                    <?php if (!empty($ubicaciones)): ?>
                                        <?php foreach ($ubicaciones as $ubicacion): ?>
                                            <option value="<?= $ubicacion->id ?>">
                                                <?= View::e($ubicacion->nombre ?? $ubicacion->descripcion ?? 'Sin nombre') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select></td>
                            <td><select class="form-select" name="distribucion[0][unidad_negocio_id]">
                                    <option value="">Seleccione...</option>
                                    <?php if (!empty($unidades_negocio)): ?>
                                        <?php foreach ($unidades_negocio as $unidad): ?>
                                            <option value="<?= $unidad->id ?>">
                                                <?= View::e($unidad->nombre ?? $unidad->descripcion ?? 'Sin nombre') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select></td>
                            <td><input type="number" class="form-control dist-porcentaje" name="distribucion[0][porcentaje]" min="0" max="100" step="0.01" value="100" required></td>
                            <td><input type="number" class="form-control dist-cantidad" name="distribucion[0][cantidad]" readonly></td>
                            <td><input type="text" class="form-control dist-factura" name="distribucion[0][factura]" value="Factura 1" data-factura-numero="1" readonly></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="eliminarDistribucion(this)"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <a href="javascript:void(0)" class="btn-add-item" onclick="agregarDistribucion()">
                <i class="fas fa-plus"></i>
                Agregar Distribución
            </a>
            
            <!-- Indicadores de Validación -->
            <div class="mt-3 p-3 bg-light rounded">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <span class="me-2">Total de Porcentajes:</span>
                            <span id="indicadorPorcentaje" class="fw-bold">0.00%</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="mensajeValidacionPorcentajes" class="small"></div>
                    </div>
                </div>
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
                                <select class="form-select" id="forma_pago" name="forma_pago" required>
                                    <option value="">Seleccione...</option>
                                    <option value="contado">Contado</option>
                                    <option value="tarjeta_credito_lic_milton">Tarjeta de Crédito (Lic. Milton)</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="credito">Crédito</option>
                                </select>
                            </td>
                            <td rowspan="4">
                                <select class="form-select" id="anticipo" name="anticipo" required>
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </td>
                            <td><strong>Factura 1</strong></td>
                            <td><span class="porcentaje-factura" id="porcentaje-factura-1">0.00%</span></td>
                            <td><span class="monto-factura" id="monto-factura-1">Q 0.00</span></td>
                        </tr>
                        <tr>
                            <td><strong>Factura 2</strong></td>
                            <td><span class="porcentaje-factura" id="porcentaje-factura-2">0.00%</span></td>
                            <td><span class="monto-factura" id="monto-factura-2">Q 0.00</span></td>
                        </tr>
                        <tr>
                            <td><strong>Factura 3</strong></td>
                            <td><span class="porcentaje-factura" id="porcentaje-factura-3">0.00%</span></td>
                            <td><span class="monto-factura" id="monto-factura-3">Q 0.00</span></td>
                        </tr>
                        <tr>
                            <td><strong>TOTAL</strong></td>
                            <td><strong id="totalPorcentajeFacturas">0.00000</strong></td>
                            <td><strong id="totalMontoFacturas">0.00000</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- DATOS ADJUNTOS -->
        <div class="card-form">
            <h3 class="h6 mb-3" style="font-weight: 700;">Datos adjuntos</h3>
            
            <div class="upload-zone" onclick="document.getElementById('archivos').click()">
                <div class="upload-icon"><i class="fas fa-paperclip"></i></div>
                <p class="mb-0" style="font-weight: 600;">Haga clic aquí para adjuntar un archivo</p>
                <p class="text-muted small mb-0">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Máx. 10MB)</p>
            </div>
            
            <input type="file" id="archivos" name="archivos[]" multiple class="d-none" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" onchange="mostrarArchivosSeleccionados(this)">
            <div id="archivosSeleccionados" class="mt-3"></div>
        </div>
        
        <!-- ESPECIFICACIONES TÉCNICAS Y JUSTIFICACIÓN -->
        <div class="card-form">
            <div class="section-header">
                <i class="fas fa-cogs"></i>
                ESPECIFICACIONES TÉCNICAS Y JUSTIFICACIÓN
            </div>
            
            <div class="mb-3">
                <label for="datos_proveedor" class="form-label">Especificaciones Técnicas y Detalles</label>
                <textarea class="form-control" id="datos_proveedor" name="datos_proveedor" rows="5" placeholder="Ingrese las especificaciones técnicas, características y detalles del bien o servicio solicitado..."></textarea>
            </div>
        </div>
        
        <!-- JUSTIFICACIÓN DE LA REQUISICIÓN -->
        <div class="card-form">
            <div class="section-header">
                <i class="fas fa-file-alt"></i>
                JUSTIFICACIÓN DE LA REQUISICIÓN
            </div>
            
            <div class="mb-3">
                <label for="razon_seleccion" class="form-label">Justificación y Razón de la Requisición</label>
                <textarea class="form-control" id="razon_seleccion" name="razon_seleccion" rows="5" placeholder="Indique la justificación, necesidad y razones por las cuales se requiere esta compra..."></textarea>
            </div>
        </div>
        
        <!-- BOTONES DE ACCIÓN -->
        <div class="card-form">
            <div class="d-flex justify-content-end gap-3">
                <a href="/requisiciones" class="btn btn-cancelar">
                    <i class="fas fa-times me-2"></i> Cancelar
                </a>
                <button type="button" class="btn btn-secondary btn-submit" id="saveDraftBtn">
                    <span class="btn-spinner"></span>
                    <i class="fas fa-file-alt me-2"></i> 
                    <span class="btn-text">Guardar como Borrador</span>
                </button>
                <button type="submit" class="btn btn-guardar btn-submit" id="submitBtn">
                    <span class="btn-spinner"></span>
                    <i class="fas fa-paper-plane me-2"></i> 
                    <span class="btn-text">Enviar Requisición</span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Overlay de carga -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p class="loading-text" id="loadingText">Creando requisición...</p>
    </div>
</div>

<script>
// [JavaScript será muy largo, lo agrego en el siguiente archivo]
let contadorItems = 1;
let contadorDistribucion = 1;

function agregarItem() {
    const tbody = document.getElementById('itemsBody');
    const newRow = document.createElement('tr');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <td><input type="number" class="form-control item-cantidad" name="items[${contadorItems}][cantidad]" min="1" step="0.01" value="1" required></td>
        <td><textarea class="form-control item-descripcion" name="items[${contadorItems}][descripcion]" rows="2" required></textarea></td>
        <td><input type="number" class="form-control item-precio" name="items[${contadorItems}][precio_unitario]" min="0" step="0.01" required></td>
        <td><input type="number" class="form-control item-total" name="items[${contadorItems}][total]" readonly></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="eliminarItem(this)"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(newRow);
    contadorItems++;
    attachItemEventListeners(newRow);
}

function eliminarItem(btn) {
    if (document.querySelectorAll('.item-row').length > 1) {
        btn.closest('tr').remove();
        calcularTotalGeneral();
    }
}

function attachItemEventListeners(row) {
    row.querySelector('.item-cantidad').addEventListener('input', () => calcularTotalItem(row));
    row.querySelector('.item-precio').addEventListener('input', () => calcularTotalItem(row));
}

function calcularTotalItem(row) {
    const cantidad = parseFloat(row.querySelector('.item-cantidad').value) || 0;
    const precio = parseFloat(row.querySelector('.item-precio').value) || 0;
    row.querySelector('.item-total').value = (cantidad * precio).toFixed(2);
    calcularTotalGeneral();
}

function calcularTotalGeneral() {
    let total = 0;
    document.querySelectorAll('.item-total').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('totalGeneral').textContent = 'Q ' + total.toFixed(2);
    document.getElementById('total_general').value = total.toFixed(2);
    actualizarFacturas();
    calcularDistribucionPorcentajes();
    return total;
}

// Función global para agregar distribución (sobrescribe cualquier otra implementación)
window.agregarDistribucion = function() {
    const tbody = document.getElementById('distribucionBody');
    const newRow = document.createElement('tr');
    newRow.className = 'distribucion-row';
    newRow.innerHTML = `
        <td>
            <div class="cuenta-contable-wrapper">
                <input type="text" 
                       class="form-control cuenta-contable-input" 
                       name="distribucion[${contadorDistribucion}][cuenta_contable_display]"
                       placeholder="Buscar cuenta..." 
                       autocomplete="off"
                       data-index="${contadorDistribucion}">
                <input type="hidden" 
                       name="distribucion[${contadorDistribucion}][cuenta_contable_id]" 
                       class="cuenta-contable-id" 
                       required>
                <div class="cuenta-contable-suggestions"></div>
            </div>
        </td>
        <td>
            <select class="form-select" name="distribucion[${contadorDistribucion}][centro_costo_id]" required>
                <option value="">Seleccione...</option>
                <?php if (!empty($centros_costo)): ?>
                    <?php foreach ($centros_costo as $centro): ?>
                        <option value="<?= $centro->id ?>">
                            <?= View::e($centro->nombre ?? $centro->descripcion ?? 'Sin nombre') ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </td>
        <td>
            <select class="form-select" name="distribucion[${contadorDistribucion}][ubicacion_id]">
                <option value="">Seleccione...</option>
                <?php if (!empty($ubicaciones)): ?>
                    <?php foreach ($ubicaciones as $ubicacion): ?>
                        <option value="<?= $ubicacion->id ?>">
                            <?= View::e($ubicacion->nombre ?? $ubicacion->descripcion ?? 'Sin nombre') ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </td>
        <td>
            <select class="form-select" name="distribucion[${contadorDistribucion}][unidad_negocio_id]">
                <option value="">Seleccione...</option>
                <?php if (!empty($unidades_negocio)): ?>
                    <?php foreach ($unidades_negocio as $unidad): ?>
                        <option value="<?= $unidad->id ?>">
                            <?= View::e($unidad->nombre ?? $unidad->descripcion ?? 'Sin nombre') ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </td>
        <td><input type="number" class="form-control dist-porcentaje" name="distribucion[${contadorDistribucion}][porcentaje]" min="0" max="100" step="0.01" required></td>
        <td><input type="number" class="form-control dist-cantidad" name="distribucion[${contadorDistribucion}][cantidad]" readonly></td>
        <td><input type="text" class="form-control dist-factura" name="distribucion[${contadorDistribucion}][factura]" value="Factura 1" data-factura-numero="1" readonly></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="eliminarDistribucion(this)"><i class="fas fa-trash"></i></button></td>
    `;
    tbody.appendChild(newRow);
    
    // Agregar event listener para el porcentaje
    const porcentajeInput = newRow.querySelector('.dist-porcentaje');
    if (porcentajeInput) {
        porcentajeInput.addEventListener('input', calcularDistribucionPorcentajes);
    }
    
    // Agregar event listener para el cambio de centro de costo
    const centroCostoSelect = newRow.querySelector('select[name*="[centro_costo_id]"]');
    if (centroCostoSelect) {
        centroCostoSelect.addEventListener('change', function() {
            if (window.calculadorAutomatico) {
                window.calculadorAutomatico.actualizarFacturaPorCentroCosto(newRow);
            }
        });
    }
    
    contadorDistribucion++;
};


function eliminarDistribucion(btn) {
    if (document.querySelectorAll('.distribucion-row').length > 1) {
        btn.closest('tr').remove();
        calcularDistribucionPorcentajes();
        actualizarFacturas(); // Asegurar que se actualicen las facturas después de eliminar
    }
}

function calcularDistribucionPorcentajes() {
    const totalGeneral = parseFloat(document.getElementById('total_general').value) || 0;
    let totalPorcentajes = 0;
    
    document.querySelectorAll('.distribucion-row').forEach(row => {
        const porcentaje = parseFloat(row.querySelector('.dist-porcentaje').value) || 0;
        const cantidad = (totalGeneral * porcentaje) / 100;
        row.querySelector('.dist-cantidad').value = cantidad.toFixed(2);
        totalPorcentajes += porcentaje;
    });
    
    // Actualizar indicador de porcentajes
    const indicador = document.getElementById('indicadorPorcentaje');
    const mensaje = document.getElementById('mensajeValidacionPorcentajes');
    
    if (indicador) {
        indicador.textContent = totalPorcentajes.toFixed(2) + '%';
        
        if (Math.abs(totalPorcentajes - 100) < 0.01) {
            indicador.style.color = '#28a745';
            if (mensaje) mensaje.textContent = '✓ Los porcentajes suman correctamente';
            if (mensaje) mensaje.style.color = '#28a745';
        } else {
            indicador.style.color = '#dc3545';
            if (mensaje) mensaje.textContent = '⚠ Los porcentajes deben sumar 100%';
            if (mensaje) mensaje.style.color = '#dc3545';
        }
    }
    
    // Actualizar facturas automáticamente
    actualizarFacturas();
}

function actualizarFacturas() {
    // Inicializar totales por factura
    let factura1 = { porcentaje: 0, monto: 0 };
    let factura2 = { porcentaje: 0, monto: 0 };
    let factura3 = { porcentaje: 0, monto: 0 };
    
    // Recorrer todas las filas de distribución para sumar por factura
    document.querySelectorAll('.distribucion-row').forEach(row => {
        const porcentajeInput = row.querySelector('input[name*="[porcentaje]"]');
        const cantidadInput = row.querySelector('input[name*="[cantidad]"]');
        const facturaInput = row.querySelector('input[name*="[factura]"]');
        
        if (porcentajeInput && cantidadInput && facturaInput) {
            const porcentaje = parseFloat(porcentajeInput.value) || 0;
            const cantidad = parseFloat(cantidadInput.value) || 0;
            
            // Obtener el número de factura del atributo data o del valor del campo
            let numeroFactura = 1;
            if (facturaInput.getAttribute('data-factura-numero')) {
                numeroFactura = parseInt(facturaInput.getAttribute('data-factura-numero')) || 1;
            } else if (facturaInput.value) {
                // Extraer el número del texto "Factura X"
                const match = facturaInput.value.match(/Factura\s*(\d)/i);
                if (match) {
                    numeroFactura = parseInt(match[1]) || 1;
                }
            }
            
            switch(numeroFactura) {
                case 1:
                    factura1.porcentaje += porcentaje;
                    factura1.monto += cantidad;
                    break;
                case 2:
                    factura2.porcentaje += porcentaje;
                    factura2.monto += cantidad;
                    break;
                case 3:
                    factura3.porcentaje += porcentaje;
                    factura3.monto += cantidad;
                    break;
            }
        }
    });
    
    // Actualizar la tabla de facturas
    const porcentaje1El = document.getElementById('porcentaje-factura-1');
    const monto1El = document.getElementById('monto-factura-1');
    const porcentaje2El = document.getElementById('porcentaje-factura-2');
    const monto2El = document.getElementById('monto-factura-2');
    const porcentaje3El = document.getElementById('porcentaje-factura-3');
    const monto3El = document.getElementById('monto-factura-3');
    
    if (porcentaje1El) porcentaje1El.textContent = factura1.porcentaje.toFixed(2) + '%';
    if (monto1El) monto1El.textContent = 'Q ' + factura1.monto.toFixed(2);
    
    if (porcentaje2El) porcentaje2El.textContent = factura2.porcentaje.toFixed(2) + '%';
    if (monto2El) monto2El.textContent = 'Q ' + factura2.monto.toFixed(2);
    
    if (porcentaje3El) porcentaje3El.textContent = factura3.porcentaje.toFixed(2) + '%';
    if (monto3El) monto3El.textContent = 'Q ' + factura3.monto.toFixed(2);
    
    // Actualizar totales
    const totalPorcentaje = factura1.porcentaje + factura2.porcentaje + factura3.porcentaje;
    const totalMonto = factura1.monto + factura2.monto + factura3.monto;
    
    const totalPorcentajeEl = document.getElementById('totalPorcentajeFacturas');
    const totalMontoEl = document.getElementById('totalMontoFacturas');
    
    if (totalPorcentajeEl) totalPorcentajeEl.textContent = totalPorcentaje.toFixed(5);
    if (totalMontoEl) totalMontoEl.textContent = totalMonto.toFixed(5);
}

function mostrarArchivosSeleccionados(input) {
    const container = document.getElementById('archivosSeleccionados');
    container.innerHTML = '';
    
    if (input.files.length > 0) {
        const list = document.createElement('ul');
        list.className = 'list-group';
        
        Array.from(input.files).forEach((file, index) => {
            const item = document.createElement('li');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `<span><i class="fas fa-file me-2"></i>${file.name} (${(file.size / 1024).toFixed(2)} KB)</span>`;
            list.appendChild(item);
        });
        
        container.appendChild(list);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.item-row').forEach(row => attachItemEventListeners(row));
    document.querySelectorAll('.factura-porcentaje').forEach(input => {
        input.addEventListener('input', actualizarFacturas);
    });
    
    // Agregar event listeners para las distribuciones
    document.querySelectorAll('.dist-porcentaje').forEach(input => {
        input.addEventListener('input', calcularDistribucionPorcentajes);
    });
    
    // Agregar event listener para el cambio de centro de costo en la primera fila
    document.querySelectorAll('.distribucion-row').forEach(row => {
        const centroCostoSelect = row.querySelector('select[name*="[centro_costo_id]"]');
        if (centroCostoSelect) {
            centroCostoSelect.addEventListener('change', function() {
                if (window.calculadorAutomatico) {
                    window.calculadorAutomatico.actualizarFacturaPorCentroCosto(row);
                }
            });
        }
    });
    
    // Calcular distribuciones iniciales
    calcularDistribucionPorcentajes();
});

// ===== AUTOCOMPLETADO DE CUENTAS CONTABLES =====
// Datos de cuentas contables cargados desde PHP
const cuentasContables = <?= json_encode(array_map(function($cuenta) {
    return [
        'id' => $cuenta->id,
        'codigo' => $cuenta->codigo ?? '',
        'nombre' => $cuenta->descripcion ?? '',
        'label' => ($cuenta->codigo ?? '') . ' - ' . ($cuenta->descripcion ?? '')
    ];
}, $cuentas_contables ?? [])) ?>;

    // Cuentas contables cargadas desde PHP

let timeoutBusqueda;

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('cuenta-contable-input')) {
        buscarCuentaContable(e.target);
    }
});

// Mostrar todas las cuentas al hacer clic
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('cuenta-contable-input')) {
        const input = e.target;
        const suggestionsDiv = input.parentElement.querySelector('.cuenta-contable-suggestions');
        
        // Click en input de cuenta contable
        
        if (input.value.length < 2) {
            // Mostrar TODAS las cuentas
            mostrarSugerenciasCuentas(input, cuentasContables);
        }
    }
});

function buscarCuentaContable(input) {
    clearTimeout(timeoutBusqueda);
    const query = input.value.trim();
    const suggestionsDiv = input.parentElement.querySelector('.cuenta-contable-suggestions');
    
    // Buscando cuenta contable
    
    if (query.length < 2) {
        suggestionsDiv.innerHTML = '';
        suggestionsDiv.classList.remove('show');
        return;
    }
    
    // Buscar en los datos locales (case insensitive)
    const queryLower = query.toLowerCase();
    const resultados = cuentasContables.filter(cuenta => {
        const codigo = (cuenta.codigo || '').toLowerCase();
        const nombre = (cuenta.nombre || '').toLowerCase();
        return codigo.includes(queryLower) || nombre.includes(queryLower);
    });
    
    mostrarSugerenciasCuentas(input, resultados);
}


function seleccionarCuenta(item, id, label) {
    const wrapper = item.closest('.cuenta-contable-wrapper');
    const input = wrapper.querySelector('.cuenta-contable-input');
    const hidden = wrapper.querySelector('.cuenta-contable-id');
    const suggestionsDiv = wrapper.querySelector('.cuenta-contable-suggestions');
    
    input.value = label;
    hidden.value = id;
    suggestionsDiv.innerHTML = '';
    suggestionsDiv.classList.remove('show');
    
    // Restaurar TODOS los botones de agregar
    document.querySelectorAll('.btn-add-item').forEach(btn => {
        btn.style.zIndex = '';
        btn.style.opacity = '';
        btn.style.visibility = '';
        btn.style.pointerEvents = '';
    });
}

// Función para cerrar todas las sugerencias
function cerrarTodasLasSugerencias() {
    document.querySelectorAll('.cuenta-contable-suggestions').forEach(div => {
        div.innerHTML = '';
        div.classList.remove('show');
    });
    
    // Restaurar TODOS los botones de agregar
    document.querySelectorAll('.btn-add-item').forEach(btn => {
        btn.style.zIndex = '';
        btn.style.opacity = '';
        btn.style.visibility = '';
        btn.style.pointerEvents = '';
    });
}

// Cerrar sugerencias al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.cuenta-contable-wrapper')) {
        cerrarTodasLasSugerencias();
    }
});

// NO cerrar al hacer scroll DENTRO de las sugerencias
document.addEventListener('scroll', function(e) {
    // Solo cerrar si el scroll NO es dentro de las sugerencias
    if (!e.target.classList.contains('cuenta-contable-suggestions') && 
        !e.target.closest('.cuenta-contable-suggestions')) {
        cerrarTodasLasSugerencias();
    }
}, true);

// Cerrar cuando se redimensiona la ventana
window.addEventListener('resize', function(e) {
    cerrarTodasLasSugerencias();
});

// Prevenir el cierre cuando se interactúa con las sugerencias
document.addEventListener('wheel', function(e) {
    if (e.target.closest('.cuenta-contable-suggestions')) {
        e.stopPropagation();
    }
}, true);

// Asegurar que las sugerencias se muestren correctamente
function mostrarSugerenciasCuentas(input, cuentas) {
    const suggestionsDiv = input.parentElement.querySelector('.cuenta-contable-suggestions');
    
    if (cuentas.length === 0) {
        suggestionsDiv.innerHTML = '<div class="cuenta-no-results">No se encontraron resultados</div>';
        suggestionsDiv.classList.add('show');
        return;
    }
    
    suggestionsDiv.innerHTML = cuentas.map(cuenta => `
        <div class="cuenta-suggestion-item" onclick="seleccionarCuenta(this, ${cuenta.id}, '${cuenta.label.replace(/'/g, "\\'")}')">
            <div class="cuenta-suggestion-codigo">${cuenta.codigo}</div>
            <div class="cuenta-suggestion-nombre">${cuenta.nombre}</div>
        </div>
    `).join('');
    
    suggestionsDiv.classList.add('show');
    
    // Obtener la posición del input
    const inputRect = input.getBoundingClientRect();
    
    // Posicionar las sugerencias usando absolute para que se muevan con el scroll
    suggestionsDiv.style.position = 'absolute';
    suggestionsDiv.style.top = (inputRect.bottom - inputRect.top + 5) + 'px';
    suggestionsDiv.style.left = '0px';
    suggestionsDiv.style.right = '0px';
    suggestionsDiv.style.width = 'auto';
    suggestionsDiv.style.minWidth = '300px';
    
        // Posicionamiento absolute aplicado
        suggestionsDiv.style.zIndex = '2147483647'; // Z-index máximo de CSS
        suggestionsDiv.style.backgroundColor = 'white';
        suggestionsDiv.style.border = '2px solid #007bff';
        suggestionsDiv.style.borderRadius = '6px';
        suggestionsDiv.style.boxShadow = '0 8px 32px rgba(0,0,0,0.3)';
        suggestionsDiv.style.maxHeight = '250px';
        suggestionsDiv.style.overflowY = 'auto';
        suggestionsDiv.style.display = 'block'; // Asegurar que se muestre
        suggestionsDiv.style.position = 'absolute'; // Forzar posición absoluta
        
        // Ocultar completamente TODOS los botones de agregar
        document.querySelectorAll('.btn-add-item').forEach(btn => {
            btn.style.zIndex = '-1';
            btn.style.opacity = '0';
            btn.style.visibility = 'hidden';
            btn.style.pointerEvents = 'none';
        });
    
    // Sugerencias mostradas correctamente
    
    // Sugerencias posicionadas correctamente
}

</script>

<!-- Funcionalidades de cálculo automático integradas -->
<script>
// Sistema de cálculo automático integrado
class CalculadorAutomatico {
    constructor() {
        this.init();
    }

    init() {
        this.configurarEventListeners();
        setTimeout(() => {
            this.calcularTotalGeneral();
            this.recalcularTodasLasDistribuciones();
        }, 100);
    }

    configurarEventListeners() {
        // Event listeners para centros de costo
        document.addEventListener('change', (e) => {
            if (e.target.matches('select[name*="[centro_costo_id]"]')) {
                this.actualizarFacturaPorCentroCosto(e.target.closest('tr'));
            }
        });
    }

    actualizarFacturaPorCentroCosto(row) {
        const centroCostoSelect = row.querySelector('select[name*="[centro_costo_id]"]');
        const facturaInput = row.querySelector('input[name*="[factura]"]');
        const unidadNegocioSelect = row.querySelector('select[name*="[unidad_negocio_id]"]');
        
        if (!centroCostoSelect || !facturaInput) return;

        const centroCostoText = centroCostoSelect.options[centroCostoSelect.selectedIndex].textContent.toUpperCase();
        
        // Mapeo simple basado en el texto del centro de costo
        let tipoFactura = 'Factura 1';
        let numeroFactura = 1;
        let unidadNegocio = 'UNIDAD DE NEGOCIO GENERAL';
        
        if (centroCostoText.includes('BASICOS') || centroCostoText.includes('BACHILLERATO') || 
            centroCostoText.includes('PERITO') || centroCostoText.includes('SECRETARIADO') || 
            centroCostoText.includes('PRIMARIA')) {
            tipoFactura = 'Factura 2';
            numeroFactura = 2;
            unidadNegocio = 'COLEGIO';
        } else if (centroCostoText.includes('CURSOS') || centroCostoText.includes('DIRECCION') || 
                   centroCostoText.includes('FINANZAS') || centroCostoText.includes('SISTEMAS') || 
                   centroCostoText.includes('MERCADEO') || centroCostoText.includes('OPERACIONES') || 
                   centroCostoText.includes('RECURSOS HUMANOS') || centroCostoText.includes('SERVICIO') || 
                   centroCostoText.includes('UNIDAD ACADEMICA') || centroCostoText.includes('BIBLIOTECA')) {
            tipoFactura = 'Factura 3';
            numeroFactura = 3;
            if (centroCostoText.includes('CURSOS')) {
                unidadNegocio = 'CURSOS ADULTOS';
            } else {
                unidadNegocio = 'ADMINISTRACION';
            }
        } else if (centroCostoText.includes('BODEGA') || centroCostoText.includes('DISTRIBUCION') || 
                   centroCostoText.includes('DISTRIBUIDORA') || centroCostoText.includes('LIBRERIA')) {
            tipoFactura = 'Factura 1';
            numeroFactura = 1;
            unidadNegocio = 'COMERCIAL';
        } else if (centroCostoText.includes('ACTIVIDADES')) {
            tipoFactura = 'Factura 1';
            numeroFactura = 1;
            unidadNegocio = 'ACTIVIDADES CULTURALES';
        }
        
        // Actualizar factura
        facturaInput.value = tipoFactura;
        facturaInput.setAttribute('data-factura-numero', numeroFactura);
        
        // Actualizar unidad de negocio si existe el select
        if (unidadNegocioSelect) {
            const opciones = unidadNegocioSelect.querySelectorAll('option');
            for (let opcion of opciones) {
                if (opcion.textContent.trim().toUpperCase() === unidadNegocio.toUpperCase()) {
                    unidadNegocioSelect.value = opcion.value;
                    break;
                }
            }
        }
        
        // Recalcular cantidad y actualizar facturas
        this.calcularCantidadDistribucion(row);
        actualizarFacturas(); // Llamar a actualizar facturas
    }

    calcularCantidadDistribucion(row) {
        const porcentajeInput = row.querySelector('input[name*="[porcentaje]"]');
        const cantidadInput = row.querySelector('input[name*="[cantidad]"]');
        
        if (!porcentajeInput || !cantidadInput) return;

        const porcentaje = parseFloat(porcentajeInput.value) || 0;
        const totalGeneral = parseFloat(document.getElementById('total_general').value) || 0;
        const cantidad = (porcentaje / 100) * totalGeneral;
        
        cantidadInput.value = cantidad.toFixed(5);
        
        // Disparar actualización de resumen
        setTimeout(() => {
            this.actualizarResumenFacturas();
            this.validarPorcentajes();
        }, 10);
    }

    recalcularTodasLasDistribuciones() {
        const filasDistribucion = document.querySelectorAll('.distribucion-row');
        filasDistribucion.forEach(fila => {
            this.calcularCantidadDistribucion(fila);
        });
    }

    actualizarResumenFacturas() {
        const totalGeneral = parseFloat(document.getElementById('total_general').value) || 0;
        let totalPorcentaje = 0;
        
        document.querySelectorAll('.distribucion-row').forEach(row => {
            const porcentaje = parseFloat(row.querySelector('input[name*="[porcentaje]"]').value) || 0;
            totalPorcentaje += porcentaje;
        });
        
        // Actualizar indicadores de facturas si existen
        const indicadoresFactura = document.querySelectorAll('.factura-porcentaje');
        indicadoresFactura.forEach(indicador => {
            const porcentaje = parseFloat(indicador.value) || 0;
            const monto = (porcentaje / 100) * totalGeneral;
            const montoElement = indicador.closest('tr').querySelector('.factura-monto');
            if (montoElement) {
                montoElement.textContent = 'Q ' + monto.toFixed(2);
            }
        });
    }

    validarPorcentajes() {
        let totalPorcentaje = 0;
        
        document.querySelectorAll('.distribucion-row').forEach(row => {
            const porcentaje = parseFloat(row.querySelector('input[name*="[porcentaje]"]').value) || 0;
            totalPorcentaje += porcentaje;
        });
        
        const esValido = Math.abs(totalPorcentaje - 100) < 0.01;
        
        // Actualizar indicador de porcentajes
        const indicadorPorcentaje = document.getElementById('indicadorPorcentaje');
        const mensajeValidacion = document.getElementById('mensajeValidacionPorcentajes');
        
        if (indicadorPorcentaje) {
            indicadorPorcentaje.textContent = `${totalPorcentaje.toFixed(2)}%`;
            indicadorPorcentaje.className = esValido ? 'text-success fw-bold' : 'text-danger fw-bold';
        }
        
        if (mensajeValidacion) {
            if (esValido) {
                mensajeValidacion.textContent = '✓ Los porcentajes suman correctamente';
                mensajeValidacion.className = 'text-success small';
            } else {
                mensajeValidacion.textContent = `⚠ Los porcentajes deben sumar exactamente 100% (actual: ${totalPorcentaje.toFixed(2)}%)`;
                mensajeValidacion.className = 'text-danger small';
            }
        }
        
        return esValido;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('#tablaDistribucion') || document.querySelector('#itemsBody')) {
        window.calculadorAutomatico = new CalculadorAutomatico();
    }
});
</script>

<!-- Script para asegurar que nuestra función agregarDistribucion tenga prioridad -->
<script>
// Ejecutar después de que todos los scripts se hayan cargado
document.addEventListener('DOMContentLoaded', function() {
    // Sobrescribir cualquier implementación de agregarDistribucion que pueda haber sido cargada
    window.agregarDistribucion = function() {
        const tbody = document.getElementById('distribucionBody');
        if (!tbody) {
            return;
        }
        
        const newRow = document.createElement('tr');
        newRow.className = 'distribucion-row';
        newRow.innerHTML = `
            <td>
                <div class="cuenta-contable-wrapper">
                    <input type="text" 
                           class="form-control cuenta-contable-input" 
                           name="distribucion[${contadorDistribucion}][cuenta_contable_display]"
                           placeholder="Buscar cuenta..." 
                           autocomplete="off"
                           data-index="${contadorDistribucion}">
                    <input type="hidden" 
                           name="distribucion[${contadorDistribucion}][cuenta_contable_id]" 
                           class="cuenta-contable-id" 
                           required>
                    <div class="cuenta-contable-suggestions"></div>
                </div>
            </td>
            <td>
                <select class="form-select" name="distribucion[${contadorDistribucion}][centro_costo_id]" required>
                    <option value="">Seleccione...</option>
                    <?php if (!empty($centros_costo)): ?>
                        <?php foreach ($centros_costo as $centro): ?>
                            <option value="<?= $centro->id ?>">
                                <?= View::e($centro->nombre ?? $centro->descripcion ?? 'Sin nombre') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </td>
            <td>
                <select class="form-select" name="distribucion[${contadorDistribucion}][ubicacion_id]">
                    <option value="">Seleccione...</option>
                    <?php if (!empty($ubicaciones)): ?>
                        <?php foreach ($ubicaciones as $ubicacion): ?>
                            <option value="<?= $ubicacion->id ?>">
                                <?= View::e($ubicacion->nombre ?? $ubicacion->descripcion ?? 'Sin nombre') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </td>
            <td>
                <select class="form-select" name="distribucion[${contadorDistribucion}][unidad_negocio_id]">
                    <option value="">Seleccione...</option>
                    <?php if (!empty($unidades_negocio)): ?>
                        <?php foreach ($unidades_negocio as $unidad): ?>
                            <option value="<?= $unidad->id ?>">
                                <?= View::e($unidad->nombre ?? $unidad->descripcion ?? 'Sin nombre') ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </td>
            <td><input type="number" class="form-control dist-porcentaje" name="distribucion[${contadorDistribucion}][porcentaje]" min="0" max="100" step="0.01" required></td>
            <td><input type="number" class="form-control dist-cantidad" name="distribucion[${contadorDistribucion}][cantidad]" readonly></td>
            <td><input type="text" class="form-control dist-factura" name="distribucion[${contadorDistribucion}][factura]" value="Factura 1" data-factura-numero="1" readonly></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger" onclick="eliminarDistribucion(this)"><i class="fas fa-trash"></i></button></td>
        `;
        
        tbody.appendChild(newRow);
        
        // Agregar event listener para el porcentaje
        const porcentajeInput = newRow.querySelector('.dist-porcentaje');
        if (porcentajeInput) {
            porcentajeInput.addEventListener('input', calcularDistribucionPorcentajes);
        }
        
        // Agregar event listener para el cambio de centro de costo
        const centroCostoSelect = newRow.querySelector('select[name*="[centro_costo_id]"]');
        if (centroCostoSelect) {
            centroCostoSelect.addEventListener('change', function() {
                if (window.calculadorAutomatico) {
                    window.calculadorAutomatico.actualizarFacturaPorCentroCosto(newRow);
                }
            });
        }
        
        // El usuario debe seleccionar manualmente la cuenta contable
        
        contadorDistribucion++;
    };
    
    // Inicializar event listeners para todas las filas de distribución existentes
    document.querySelectorAll('.distribucion-row').forEach(row => {
        const facturaSelect = row.querySelector('select[name*="[factura]"]') || row.querySelector('.dist-factura');
        if (facturaSelect) {
            facturaSelect.addEventListener('change', actualizarFacturas);
        }
    });
    
    // El usuario debe seleccionar manualmente las cuentas contables
    
    // Actualizar facturas al cargar la página
    actualizarFacturas();
    
    // ========================================================================
    // ANIMACIONES Y VALIDACIONES
    // ========================================================================
    
    // Configurar validaciones en tiempo real
    function setupValidations() {
        const requiredFields = document.querySelectorAll('input[required], select[required], textarea[required]');
        
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });
            
            field.addEventListener('input', function() {
                // Remover clases de validación mientras escribe
                this.classList.remove('valid', 'invalid', 'validating');
            });
        });
    }
    
    function validateField(field) {
        field.classList.add('validating');
        
        setTimeout(() => {
            field.classList.remove('validating');
            
            if (field.checkValidity() && field.value.trim() !== '') {
                field.classList.add('valid');
                field.classList.remove('invalid');
            } else {
                field.classList.add('invalid');
                field.classList.remove('valid');
            }
        }, 300);
    }
    
    // Configurar animaciones de envío
    function setupSubmitAnimations() {
        const form = document.getElementById('requisicionForm');
        const submitBtn = document.getElementById('submitBtn');
        const saveDraftBtn = document.getElementById('saveDraftBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        
        if (form && submitBtn && loadingOverlay) {
            // Manejar envío de requisición (estado enviado)
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevenir envío normal
                
                // Agregar campo oculto para indicar envío
                addHiddenField(form, 'action_type', 'enviar');
                
                // Validar formulario antes de mostrar loading
                if (!form.checkValidity()) {
                    // Mostrar campos inválidos con animación
                    const invalidFields = form.querySelectorAll(':invalid');
                    invalidFields.forEach(field => {
                        field.classList.add('invalid');
                        field.focus();
                    });
                    return false;
                }
                
                // Validación personalizada
                const validationResult = validateFormData(form);
                if (!validationResult.valid) {
                    showValidationError(validationResult.message);
                    return false;
                }
                
                // IMPORTANTE: Enviar formulario ANTES de deshabilitar campos
                // Enviar formulario con manejo de CSRF
                submitFormWithCsrfHandling(form);
            });
            
            // Manejar guardar como borrador
            if (saveDraftBtn) {
                saveDraftBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Agregar campo oculto para indicar borrador
                    addHiddenField(form, 'action_type', 'borrador');
                    
                    // No validar formulario para borradores (permite guardar incompleto)
                    // Enviar directamente
                    submitFormWithCsrfHandling(form);
                });
            }
        }
    }
    
    // Función auxiliar para agregar campos ocultos al formulario
    function addHiddenField(form, name, value) {
        // Eliminar campo existente si ya existe
        const existingField = form.querySelector(`input[name="${name}"]`);
        if (existingField) {
            existingField.remove();
        }
        
        // Crear nuevo campo oculto
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = name;
        hiddenField.value = value;
        form.appendChild(hiddenField);
    }
    
    // Función auxiliar para restaurar el estado de los botones
    function restoreButtonsState() {
        const submitBtn = document.getElementById('submitBtn');
        const saveDraftBtn = document.getElementById('saveDraftBtn');
        
        if (submitBtn) {
            submitBtn.classList.remove('loading');
            submitBtn.querySelector('.btn-text').textContent = 'Enviar Requisición';
        }
        
        if (saveDraftBtn) {
            saveDraftBtn.classList.remove('loading');
            saveDraftBtn.querySelector('.btn-text').textContent = 'Guardar como Borrador';
        }
    }
    
    function showLoadingState() {
        const submitBtn = document.getElementById('submitBtn');
        const saveDraftBtn = document.getElementById('saveDraftBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        // Determinar cuál botón fue presionado y animar ese botón
        const form = document.getElementById('requisicionForm');
        const actionType = form.querySelector('input[name="action_type"]')?.value;
        
        if (actionType === 'borrador' && saveDraftBtn) {
            saveDraftBtn.classList.add('loading');
            saveDraftBtn.querySelector('.btn-text').textContent = 'Guardando borrador...';
        } else if (submitBtn) {
            submitBtn.classList.add('loading');
            submitBtn.querySelector('.btn-text').textContent = 'Enviando...';
        }
        
        // Mostrar overlay
        loadingOverlay.style.display = 'flex';
        
        // Deshabilitar formulario
        const formElements = document.querySelectorAll('input, select, textarea, button');
        formElements.forEach(el => el.disabled = true);
    }
    
    function simulateProgress() {
        const loadingText = document.getElementById('loadingText');
        const messages = [
            'Validando datos...',
            'Calculando totales...',
            'Generando distribución...',
            'Creando facturas...',
            'Iniciando flujo de autorización...',
            'Finalizando...'
        ];
        
        let currentMessage = 0;
        const interval = setInterval(() => {
            if (currentMessage < messages.length) {
                loadingText.textContent = messages[currentMessage];
                currentMessage++;
            } else {
                clearInterval(interval);
            }
        }, 800);
    }
    
    // Configurar validación de porcentajes en tiempo real
    function setupPercentageValidation() {
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('dist-porcentaje')) {
                validatePercentages();
            }
        });
    }
    
    function validatePercentages() {
        const porcentajeInputs = document.querySelectorAll('.dist-porcentaje');
        let total = 0;
        
        porcentajeInputs.forEach(input => {
            const value = parseFloat(input.value) || 0;
            total += value;
        });
        
        const isValid = Math.abs(total - 100) < 0.01;
        const totalDisplay = document.getElementById('totalPorcentaje');
        
        if (totalDisplay) {
            totalDisplay.textContent = total.toFixed(2) + '%';
            totalDisplay.className = isValid ? 'text-success' : 'text-danger';
        }
        
        // Animar inputs según validación
        porcentajeInputs.forEach(input => {
            input.classList.remove('valid', 'invalid');
            if (input.value) {
                input.classList.add(isValid ? 'valid' : 'invalid');
            }
        });
    }
    
    // Función para manejar timeout de envío
    function handleSubmissionTimeout() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        const submitBtn = document.getElementById('submitBtn');
        
        // Ocultar overlay de carga
        loadingOverlay.style.display = 'none';
        
        // Restaurar botones
        restoreButtonsState();
        
        // Rehabilitar formulario
        const formElements = document.querySelectorAll('input, select, textarea, button');
        formElements.forEach(el => el.disabled = false);
        
        // Mostrar mensaje de error
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Tiempo de espera agotado',
                html: `
                    <p>La requisición está tardando más de lo esperado.</p>
                    <p><strong>¿Qué hacer?</strong></p>
                    <ul style="text-align: left; margin: 10px 0;">
                        <li>Verifica tu conexión a internet</li>
                        <li>Revisa si la requisición se creó en la lista</li>
                        <li>Si no aparece, intenta enviar nuevamente</li>
                    </ul>
                `,
                showCancelButton: true,
                confirmButtonText: 'Ir a Lista de Requisiciones',
                cancelButtonText: 'Intentar Nuevamente',
                confirmButtonColor: '#007bff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/requisiciones';
                }
            });
        } else {
            const goToList = confirm(
                'La requisición está tardando más de lo esperado.\n\n' +
                '¿Deseas ir a la lista de requisiciones para verificar si se creó correctamente?'
            );
            if (goToList) {
                window.location.href = '/requisiciones';
            }
        }
    }
    
    // Función para enviar formulario con manejo de CSRF
    async function submitFormWithCsrfHandling(form, isRetry = false) {
        try {
            // IMPORTANTE: Crear FormData ANTES de deshabilitar el formulario
            const formData = new FormData(form);
            
            // DESPUÉS de crear FormData, mostrar animaciones de carga
            if (!isRetry) {
                showLoadingState();
                simulateProgress();
            }
            
            // Debug exhaustivo
            console.log('=== DATOS DEL FORMULARIO ===');
            console.log('Form element:', form);
            console.log('Form ID:', form.id);
            console.log('Form elements count:', form.elements.length);
            
            // Verificar que el formulario no esté deshabilitado
            const nombreInput = form.querySelector('#nombre_razon_social');
            console.log('Nombre input element:', nombreInput);
            console.log('Nombre input value:', nombreInput ? nombreInput.value : 'NO ENCONTRADO');
            console.log('Nombre input disabled:', nombreInput ? nombreInput.disabled : 'N/A');
            
            // Contar items y distribución manualmente
            const itemInputs = form.querySelectorAll('input[name*="items"]');
            const distInputs = form.querySelectorAll('input[name*="distribucion"], select[name*="distribucion"]');
            console.log('Items detectados:', itemInputs.length, 'Distribución detectada:', distInputs.length);
            
            // Mostrar todos los datos de FormData
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            // Verificar campos críticos
            const nombreRazonSocial = formData.get('nombre_razon_social');
            console.log('Nombre/Razón Social:', nombreRazonSocial);
            
            // Contar items y distribución
            let itemCount = 0;
            let distCount = 0;
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('items[')) itemCount++;
                if (key.startsWith('distribucion[')) distCount++;
            }
            console.log(`Items detectados: ${itemCount}, Distribución detectada: ${distCount}`);
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.status === 419) {
                // Token CSRF expirado
                if (!isRetry) {
                    await handleCsrfExpired(form);
                } else {
                    throw new Error('Token CSRF sigue siendo inválido después del reintento');
                }
            } else if (response.ok) {
                // Éxito - procesar respuesta JSON
                const result = await response.json();
                
                if (result.success) {
                    // Mostrar mensaje de éxito
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Requisición Creada!',
                            text: result.message || 'La requisición se ha creado exitosamente',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = result.redirect_url || '/requisiciones';
                        });
                    } else {
                        alert(result.message || 'Requisición creada exitosamente');
                        window.location.href = result.redirect_url || '/requisiciones';
                    }
                } else {
                    // Error en la respuesta
                    throw new Error(result.error || 'Error desconocido del servidor');
                }
            } else {
                // Otro error
                throw new Error(`Error del servidor: ${response.status}`);
            }
        } catch (error) {
            console.error('Error enviando formulario:', error);
            handleSubmissionError(error.message);
        }
    }
    
    // Función para manejar CSRF expirado
    async function handleCsrfExpired(form) {
        try {
            // Renovar token CSRF
            const tokenResponse = await fetch('/csrf-token', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (tokenResponse.ok) {
                const tokenData = await tokenResponse.json();
                
                // Actualizar token en el formulario
                const tokenInput = form.querySelector('input[name="_token"]');
                if (tokenInput && tokenData.token) {
                    tokenInput.value = tokenData.token;
                    
                    // Reintentar envío
                    await submitFormWithCsrfHandling(form, true);
                } else {
                    throw new Error('No se pudo obtener el nuevo token');
                }
            } else {
                throw new Error('No se pudo renovar el token CSRF');
            }
        } catch (error) {
            console.error('Error renovando token CSRF:', error);
            showCsrfExpiredDialog();
        }
    }
    
    // Función para mostrar diálogo de token expirado
    function showCsrfExpiredDialog() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.style.display = 'none';
        
        // Restaurar botón
        const submitBtn = document.getElementById('submitBtn');
        restoreButtonsState();
        
        // Rehabilitar formulario
        const formElements = document.querySelectorAll('input, select, textarea, button');
        formElements.forEach(el => el.disabled = false);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Sesión Expirada',
                html: `
                    <p>Su sesión ha expirado por seguridad.</p>
                    <p><strong>¿Qué desea hacer?</strong></p>
                `,
                showCancelButton: true,
                confirmButtonText: 'Recargar Página',
                cancelButtonText: 'Intentar Nuevamente',
                confirmButtonColor: '#007bff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
        } else {
            const reload = confirm(
                'Su sesión ha expirado por seguridad.\n\n' +
                '¿Desea recargar la página? (Se perderán los datos no guardados)'
            );
            if (reload) {
                window.location.reload();
            }
        }
    }
    
    // Función para manejar errores de envío
    function handleSubmissionError(errorMessage) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.style.display = 'none';
        
        // Restaurar botón
        const submitBtn = document.getElementById('submitBtn');
        restoreButtonsState();
        
        // Rehabilitar formulario
        const formElements = document.querySelectorAll('input, select, textarea, button');
        formElements.forEach(el => el.disabled = false);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error al Guardar',
                text: errorMessage || 'Ocurrió un error inesperado. Por favor intente nuevamente.',
                confirmButtonText: 'Entendido'
            });
        } else {
            alert('Error: ' + (errorMessage || 'Ocurrió un error inesperado'));
        }
    }
    
    // Función para validar datos del formulario
    function validateFormData(form) {
        const formData = new FormData(form);
        
        // 1. Validar nombre/razón social
        const nombreRazonSocial = formData.get('nombre_razon_social');
        if (!nombreRazonSocial || nombreRazonSocial.trim() === '') {
            return {
                valid: false,
                message: 'El nombre o razón social es obligatorio'
            };
        }
        
        // 2. Validar items
        let itemsCount = 0;
        let hasValidItems = false;
        
        for (let [key, value] of formData.entries()) {
            if (key.match(/^items\[\d+\]\[descripcion\]$/)) {
                itemsCount++;
                if (value && value.trim() !== '') {
                    hasValidItems = true;
                }
            }
        }
        
        if (itemsCount === 0 || !hasValidItems) {
            return {
                valid: false,
                message: 'Debe incluir al menos un item con descripción'
            };
        }
        
        // 3. Validar distribución
        let distCount = 0;
        let hasValidDist = false;
        let missingCuentaContable = false;
        
        for (let [key, value] of formData.entries()) {
            if (key.match(/^distribucion\[\d+\]\[centro_costo_id\]$/)) {
                distCount++;
                if (value && value !== '') {
                    hasValidDist = true;
                }
            }
            
            // Verificar cuentas contables
            if (key.match(/^distribucion\[\d+\]\[cuenta_contable_id\]$/)) {
                if (!value || value === '') {
                    missingCuentaContable = true;
                }
            }
        }
        
        if (distCount === 0 || !hasValidDist) {
            return {
                valid: false,
                message: 'Debe incluir la distribución de gastos con al menos un centro de costo'
            };
        }
        
        if (missingCuentaContable) {
            return {
                valid: false,
                message: 'Debe seleccionar una cuenta contable para cada distribución. Use el campo de búsqueda para seleccionar una cuenta.'
            };
        }
        
        // 4. Validar que los porcentajes sumen 100%
        let totalPorcentaje = 0;
        for (let [key, value] of formData.entries()) {
            if (key.match(/^distribucion\[\d+\]\[porcentaje\]$/)) {
                totalPorcentaje += parseFloat(value) || 0;
            }
        }
        
        if (Math.abs(totalPorcentaje - 100) > 0.01) {
            return {
                valid: false,
                message: `Los porcentajes deben sumar 100%. Actualmente suman ${totalPorcentaje.toFixed(2)}%`
            };
        }
        
        return { valid: true };
    }
    
    // Función removida: El usuario debe seleccionar manualmente las cuentas contables
    
    // Función para mostrar errores de validación
    function showValidationError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de Validación',
                text: message,
                confirmButtonText: 'Entendido'
            });
        } else {
            alert('Error: ' + message);
        }
    }
    
    // Función para detectar si el envío fue exitoso
    function detectSuccessfulSubmission() {
        // Esta función ya no es necesaria con el nuevo manejo AJAX
        // pero la mantenemos por compatibilidad
    }
    
    // Inicializar todas las funciones
    setupValidations();
    setupSubmitAnimations();
    setupPercentageValidation();
    detectSuccessfulSubmission();
});
</script>


<?php
View::endSection();
