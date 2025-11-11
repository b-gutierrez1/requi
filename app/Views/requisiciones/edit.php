<?php
use App\Helpers\View;
use App\Middlewares\CsrfMiddleware;

View::startSection('title', 'Editar Requisición');
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
.cuenta-contable-suggestions.show + .btn-add-item {
    display: none !important;
}

/* Forzar que esté por encima de modales, dropdowns, tooltips, etc. */
.cuenta-contable-suggestions.show {
    z-index: 2147483647 !important;
    position: absolute !important;
    display: block !important;
}
</style>

<div class="container py-4" style="max-width: 1200px;">
    <form id="formRequisicion" method="POST" action="/requisiciones/<?php echo $requisicion['orden']->id ?? ''; ?>/actualizar" enctype="multipart/form-data">
        <?php echo CsrfMiddleware::field(); ?>
        <input type="hidden" name="_method" value="PUT">
        
        <div class="card-form">
            <!-- Logo y Título -->
            <div class="logo-header">
                <img src="/assets/images/logo-iga.png" alt="IGA" onerror="this.style.display='none'">
            </div>
            
            <h1 class="form-title">
                Editar Requisición #<?php echo View::e($requisicion['orden']->id ?? ''); ?>
            </h1>
            
            <?php 
            $estadoActual = $requisicion['flujo'] ? $requisicion['flujo']->estado : 'sin_flujo';
            if ($estadoActual !== 'borrador'): 
            ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Atención:</strong> Esta requisición ya está en proceso. Solo puede editar ciertos campos.
    </div>
    <?php endif; ?>

            <!-- INFORMACIÓN GENERAL -->
            <h2 class="h5 mb-4" style="border-bottom: 2px solid #000; padding-bottom: 10px;">
                Información General
            </h2>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="nombre_razon_social" class="form-label">Nombre o Razón Social</label>
                    <input type="text" class="form-control" id="nombre_razon_social" name="nombre_razon_social" 
                           value="<?php echo View::e($requisicion['orden']->nombre_razon_social ?? ''); ?>" required>
                    </div>

                    <div class="col-md-4">
                    <label for="unidad_requirente" class="form-label">Unidad Requirente</label>
                    <select class="form-select" id="unidad_requirente" name="unidad_requirente" required>
                        <option value="">Seleccione...</option>
                        <?php if (!empty($catalogos['unidades_requirentes'])): ?>
                            <?php foreach ($catalogos['unidades_requirentes'] as $unidad): ?>
                                <option value="<?php echo $unidad->id; ?>" <?php echo ($unidad->id == ($requisicion['orden']->unidad_requirente_id ?? '')) ? 'selected' : ''; ?>>
                                    <?php echo View::e($unidad->nombre ?? $unidad->descripcion ?? 'Sin nombre'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </select>
                    </div>

                <div class="col-md-4">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" 
                           value="<?php echo $requisicion['orden']->fecha ?? date('Y-m-d'); ?>" 
                           <?php echo $estadoActual !== 'borrador' ? 'readonly' : ''; ?> required>
                </div>
            </div>
            
            <div class="row mb-4">
                    <div class="col-md-4">
                    <label for="ubicacion" class="form-label">Ubicación</label>
                    <select class="form-select" id="ubicacion" name="ubicacion" required>
                        <option value="">Seleccione...</option>
                        <?php if (!empty($catalogos['ubicaciones'])): ?>
                            <?php foreach ($catalogos['ubicaciones'] as $ubicacion): ?>
                                <option value="<?php echo $ubicacion->id; ?>" <?php echo ($ubicacion->id == ($requisicion['orden']->ubicacion_id ?? '')) ? 'selected' : ''; ?>>
                                    <?php echo View::e($ubicacion->nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                    <label for="unidad_negocio" class="form-label">Unidad de Negocio</label>
                    <select class="form-select" id="unidad_negocio" name="unidad_negocio" required>
                        <option value="">Seleccione...</option>
                        <?php if (!empty($catalogos['unidades_negocio'])): ?>
                            <?php foreach ($catalogos['unidades_negocio'] as $unidad): ?>
                                <option value="<?php echo $unidad->id; ?>" <?php echo ($unidad->id == ($requisicion['orden']->unidad_negocio_id ?? '')) ? 'selected' : ''; ?>>
                                    <?php echo View::e($unidad->nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                    <label for="solicitado_por" class="form-label">Solicitado por</label>
                    <input type="text" class="form-control" id="solicitado_por" name="solicitado_por" 
                           value="<?php echo View::e($requisicion['orden']->solicitado_por ?? ''); ?>" readonly>
                </div>
                    </div>

            <div class="row mb-4">
                    <div class="col-md-4">
                    <label for="tipo_compra" class="form-label">Tipo de Compra</label>
                    <select class="form-select" id="tipo_compra" name="tipo_compra" required>
                        <option value="">Seleccione...</option>
                        <option value="normal" <?php echo ($requisicion['orden']->tipo_compra ?? '') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="proveedor_sugerido" <?php echo ($requisicion['orden']->tipo_compra ?? '') === 'proveedor_sugerido' ? 'selected' : ''; ?>>Proveedor Sugerido</option>
                        </select>
                    </div>

                <div class="col-md-4">
                    <label for="proveedor_sugerido" class="form-label">Proveedor Sugerido</label>
                    <input type="text" class="form-control" id="proveedor_sugerido" name="proveedor_sugerido" 
                           value="<?php echo View::e($requisicion['orden']->proveedor_sugerido ?? ''); ?>">
                </div>
                
                    <div class="col-md-4">
                    <label for="justificacion" class="form-label">Justificación <span class="text-danger">*</span></label>
                    <textarea name="justificacion" class="form-control" rows="3" required><?php echo View::e($requisicion['orden']->justificacion ?? ''); ?></textarea>
                </div>
                    </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="causal_compra" class="form-label">Causal de Compra</label>
                    <select class="form-select" id="causal_compra" name="causal_compra" required>
                        <option value="">Seleccione una opción</option>
                        <option value="tramite_normal" <?php echo ($requisicion['orden']->causal_compra ?? '') === 'tramite_normal' ? 'selected' : ''; ?>>Trámite Normal</option>
                        <option value="eventualidad" <?php echo ($requisicion['orden']->causal_compra ?? '') === 'eventualidad' ? 'selected' : ''; ?>>Eventualidad</option>
                        <option value="emergencia" <?php echo ($requisicion['orden']->causal_compra ?? '') === 'emergencia' ? 'selected' : ''; ?>>Emergencia</option>
                    </select>
                    </div>
                
                <div class="col-md-6">
                    <label for="moneda" class="form-label">Moneda</label>
                    <select class="form-select" id="moneda" name="moneda" required>
                        <option value="GTQ" <?php echo ($requisicion['orden']->moneda ?? 'GTQ') === 'GTQ' ? 'selected' : ''; ?>>Quetzales (GTQ)</option>
                        <option value="USD" <?php echo ($requisicion['orden']->moneda ?? '') === 'USD' ? 'selected' : ''; ?>>Dólares (USD)</option>
                        <option value="EUR" <?php echo ($requisicion['orden']->moneda ?? '') === 'EUR' ? 'selected' : ''; ?>>Euros (EUR)</option>
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
                        <?php if (!empty($requisicion['items'])): ?>
                            <?php foreach ($requisicion['items'] as $index => $item): ?>
                                <tr class="item-row">
                                    <td>
                                        <input type="number" class="form-control item-cantidad" name="items[<?php echo $index; ?>][cantidad]" min="1" step="0.01" value="<?php echo $item->cantidad ?? 1; ?>" required>
                                    </td>
                                    <td>
                                        <textarea class="form-control item-descripcion" name="items[<?php echo $index; ?>][descripcion]" rows="2" required><?php echo View::e($item->descripcion ?? ''); ?></textarea>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control item-precio" name="items[<?php echo $index; ?>][precio_unitario]" min="0" step="0.01" value="<?php echo $item->precio_unitario ?? 0; ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control item-total" readonly value="<?php echo number_format(($item->cantidad ?? 1) * ($item->precio_unitario ?? 0), 2); ?>">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="eliminarItem(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="item-row">
                                <td>
                                    <input type="number" class="form-control item-cantidad" name="items[0][cantidad]" min="1" step="0.01" value="1" required>
                                </td>
                                <td>
                                    <textarea class="form-control item-descripcion" name="items[0][descripcion]" rows="2" required></textarea>
                                </td>
                                <td>
                                    <input type="number" class="form-control item-precio" name="items[0][precio_unitario]" min="0" step="0.01" value="0" required>
                                </td>
                                <td>
                                    <input type="text" class="form-control item-total" readonly value="0.00">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarItem(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            
            <a href="javascript:void(0)" class="btn-add-item" onclick="agregarItem()">
                <i class="fas fa-plus"></i>
                Agregar Item
            </a>
            
            <div class="total-display">
                Total: $<span id="totalGeneral">0.00</span>
            </div>
        </div>

        <!-- DISTRIBUCIÓN DE GASTOS -->
        <div class="card-form">

                <div class="table-responsive">
                       <table class="table table-bordered form-table" id="tablaDistribucion">
                    <thead class="table-dark-custom">
                            <tr>
                                <th style="width: 40%">Centro de Costo</th>
                            <th style="width: 40%">Cuenta Contable</th>
                                <th style="width: 15%">Porcentaje</th>
                            <th style="width: 5%">Acciones</th>
                            </tr>
                        </thead>
                    <tbody id="distribucionBody">
                        <?php if (!empty($requisicion['distribucion'])): ?>
                            <?php foreach ($requisicion['distribucion'] as $index => $dist): ?>
                                <tr class="distribucion-row">
                                    <td>
                                        <select class="form-select centro-costo" name="distribucion[<?php echo $index; ?>][centro_costo_id]" required>
                                            <option value="">Seleccione...</option>
                                            <?php if (!empty($catalogos['centros_costo'])): ?>
                                                <?php foreach ($catalogos['centros_costo'] as $centro): ?>
                                                    <option value="<?php echo $centro->id; ?>" <?php echo ($centro->id == $dist->centro_costo_id) ? 'selected' : ''; ?>>
                                                        <?php echo View::e($centro->nombre); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select cuenta-contable" name="distribucion[<?php echo $index; ?>][cuenta_contable_id]" required>
                                            <option value="">Seleccione...</option>
                                            <?php if (!empty($catalogos['cuentas_contables'])): ?>
                                                <?php foreach ($catalogos['cuentas_contables'] as $cuenta): ?>
                                                    <option value="<?php echo $cuenta->id; ?>" <?php echo ($cuenta->id == $dist->cuenta_contable_id) ? 'selected' : ''; ?>>
                                                        <?php echo View::e($cuenta->codigo . ' - ' . $cuenta->nombre); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control porcentaje" name="distribucion[<?php echo $index; ?>][porcentaje]" min="0" max="100" step="0.01" value="<?php echo $dist->porcentaje ?? 0; ?>" required>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="eliminarDistribucion(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="distribucion-row">
                                <td>
                                    <select class="form-select centro-costo" name="distribucion[0][centro_costo_id]" required>
                                        <option value="">Seleccione...</option>
                                        <?php if (!empty($catalogos['centros_costo'])): ?>
                                            <?php foreach ($catalogos['centros_costo'] as $centro): ?>
                                                <option value="<?php echo $centro->id; ?>">
                                                    <?php echo View::e($centro->nombre); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select cuenta-contable" name="distribucion[0][cuenta_contable_id]" required>
                                        <option value="">Seleccione...</option>
                                        <?php if (!empty($catalogos['cuentas_contables'])): ?>
                                            <?php foreach ($catalogos['cuentas_contables'] as $cuenta): ?>
                                                <option value="<?php echo $cuenta->id; ?>">
                                                    <?php echo View::e($cuenta->codigo . ' - ' . $cuenta->nombre); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control porcentaje" name="distribucion[0][porcentaje]" min="0" max="100" step="0.01" value="0" required>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarDistribucion(this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
            </div>
            
            <a href="javascript:void(0)" class="btn-add-item" onclick="agregarDistribucion()">
                <i class="fas fa-plus"></i>
                Agregar Distribución
            </a>
        </div>

        <!-- ARCHIVOS ADJUNTOS -->
        <div class="card-form">
            <div class="section-header">
                <i class="fas fa-paperclip"></i>
                ARCHIVOS ADJUNTOS
            </div>
            
            <div class="mb-3">
                <label for="archivos" class="form-label">Seleccionar Archivos</label>
                <input type="file" class="form-control" id="archivos" name="archivos[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                <div class="form-text">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG</div>
            </div>
            
            <?php if (!empty($requisicion['archivos'])): ?>
                <div class="mt-3">
                    <h6>Archivos Actuales:</h6>
                    <ul class="list-group">
                        <?php foreach ($requisicion['archivos'] as $archivo): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo View::e($archivo->nombre_archivo ?? 'Archivo'); ?></span>
                                <a href="/archivos/<?php echo $archivo->id; ?>/descargar" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download"></i> Descargar
                                </a>
                            </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
        </div>

        <!-- Botones de Acción -->
        <div class="row mt-4">
            <div class="col-md-12 text-center">
                <button type="submit" class="btn btn-guardar me-3">
                    <i class="fas fa-save me-2"></i>Guardar Cambios
                </button>
                <a href="/requisiciones/<?php echo $requisicion['orden']->id ?? ''; ?>" class="btn btn-cancelar">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
            </div>
        </div>
    </form>
</div>

<script>
let itemIndex = <?php echo !empty($requisicion['items']) ? count($requisicion['items']) : 1; ?>;
let distribucionIndex = <?php echo !empty($requisicion['distribucion']) ? count($requisicion['distribucion']) : 1; ?>;

function agregarItem() {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.className = 'item-row';
    
    row.innerHTML = `
        <td>
            <input type="number" class="form-control item-cantidad" name="items[${itemIndex}][cantidad]" min="1" step="0.01" value="1" required>
        </td>
        <td>
            <textarea class="form-control item-descripcion" name="items[${itemIndex}][descripcion]" rows="2" required></textarea>
        </td>
        <td>
            <input type="number" class="form-control item-precio" name="items[${itemIndex}][precio_unitario]" min="0" step="0.01" value="0" required>
        </td>
        <td>
            <input type="text" class="form-control item-total" readonly value="0.00">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    itemIndex++;
    
    // Agregar event listeners para calcular totales
    const cantidadInput = row.querySelector('.item-cantidad');
    const precioInput = row.querySelector('.item-precio');
    const totalInput = row.querySelector('.item-total');
    
    cantidadInput.addEventListener('input', calcularTotalItem);
    precioInput.addEventListener('input', calcularTotalItem);
    
    function calcularTotalItem() {
        const cantidad = parseFloat(cantidadInput.value) || 0;
        const precio = parseFloat(precioInput.value) || 0;
        const total = cantidad * precio;
        totalInput.value = total.toFixed(2);
        calcularTotalGeneral();
    }
}

function eliminarItem(button) {
    const row = button.closest('tr');
    row.remove();
    calcularTotalGeneral();
}

function agregarDistribucion() {
    const tbody = document.getElementById('distribucionBody');
    const row = document.createElement('tr');
    row.className = 'distribucion-row';
    
    row.innerHTML = `
        <td>
            <select class="form-select centro-costo" name="distribucion[${distribucionIndex}][centro_costo_id]" required>
                <option value="">Seleccione...</option>
                <?php if (!empty($catalogos['centros_costo'])): ?>
                    <?php foreach ($catalogos['centros_costo'] as $centro): ?>
                        <option value="<?php echo $centro->id; ?>">
                            <?php echo View::e($centro->nombre); ?>
                        </option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </td>
        <td>
            <select class="form-select cuenta-contable" name="distribucion[${distribucionIndex}][cuenta_contable_id]" required>
                <option value="">Seleccione...</option>
                <?php if (!empty($catalogos['cuentas_contables'])): ?>
                    <?php foreach ($catalogos['cuentas_contables'] as $cuenta): ?>
                        <option value="<?php echo $cuenta->id; ?>">
                            <?php echo View::e($cuenta->codigo . ' - ' . $cuenta->nombre); ?>
                    </option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </td>
        <td>
            <input type="number" class="form-control porcentaje" name="distribucion[${distribucionIndex}][porcentaje]" min="0" max="100" step="0.01" value="0" required>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarDistribucion(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    distribucionIndex++;
}

function eliminarDistribucion(button) {
    const row = button.closest('tr');
    row.remove();
    }

    function calcularTotalGeneral() {
    const totalInputs = document.querySelectorAll('.item-total');
        let total = 0;
    
    totalInputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        
    document.getElementById('totalGeneral').textContent = total.toFixed(2);
}

// Inicializar event listeners para items existentes
document.addEventListener('DOMContentLoaded', function() {
    // Calcular totales para items existentes
    document.querySelectorAll('.item-cantidad, .item-precio').forEach(input => {
        input.addEventListener('input', function() {
            const row = this.closest('tr');
            const cantidad = parseFloat(row.querySelector('.item-cantidad').value) || 0;
            const precio = parseFloat(row.querySelector('.item-precio').value) || 0;
            const total = cantidad * precio;
            row.querySelector('.item-total').value = total.toFixed(2);
            calcularTotalGeneral();
        });
    });
    
    // Calcular total general inicial
    calcularTotalGeneral();
});
</script>

<?php View::endSection(); ?>