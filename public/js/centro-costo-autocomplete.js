/**
 * Sistema de Autocompletado de Centros de Costo
 * 
 * Implementa el mapeo automático de centros de costo a unidades de negocio
 * y tipos de factura, con cálculos automáticos de montos y porcentajes.
 * 
 * @package RequisicionesMVC
 * @version 2.0
 */

class CentroCostoAutocompletado {
    constructor() {
        this.mapeo = {};
        this.unidadesNegocio = {};
        this.totalGeneral = 0;
        this.facturas = {
            1: { total: 0, porcentaje: 0, items: [] },
            2: { total: 0, porcentaje: 0, items: [] },
            3: { total: 0, porcentaje: 0, items: [] }
        };
        
        this.init();
    }

    /**
     * Inicializa el sistema
     */
    async init() {
        try {
            // Cargar datos de centros de costo
            await this.cargarDatosCentrosCosto();
            
            // Configurar event listeners
            this.configurarEventListeners();
            
            // Calcular totales iniciales
        this.actualizarCalculos();
            
            console.log('Sistema de autocompletado de centros de costo inicializado');
        } catch (error) {
            console.error('Error al inicializar el sistema:', error);
        }
    }

    /**
     * Carga los datos de centros de costo desde el servidor
     */
    async cargarDatosCentrosCosto() {
        try {
            const response = await fetch('/requisiciones/api/centros-costo');
            const data = await response.json();
            
            if (data.success) {
                this.mapeo = data.centros_costo;
                this.unidadesNegocio = data.unidades_negocio;
                console.log('Datos de centros de costo cargados:', this.mapeo);
            } else {
                console.error('Error al cargar centros de costo:', data.error);
            }
        } catch (error) {
            console.error('Error de conexión:', error);
        }
    }

    /**
     * Configura los event listeners
     */
    configurarEventListeners() {
        // Event listener para cambios en centros de costo
        document.addEventListener('change', (e) => {
            if (e.target.matches('select[name*="[centro_costo_id]"]')) {
                this.onCentroCostoChange(e.target);
            }
        });

        // Event listeners para cambios en porcentajes y montos
        document.addEventListener('input', (e) => {
            if (e.target.matches('input[name*="[porcentaje]"]') || 
                e.target.matches('input[name*="[cantidad]"]')) {
                this.actualizarCalculos();
            }
        });

        // Event listener para el total general
        const totalGeneralElement = document.getElementById('totalGeneral');
        if (totalGeneralElement) {
            totalGeneralElement.addEventListener('DOMSubtreeModified', () => {
                this.actualizarCalculos();
            });
        }
    }

    /**
     * Maneja el cambio de centro de costo
     */
    onCentroCostoChange(selectElement) {
        const centroCostoId = selectElement.value;
        const fila = selectElement.closest('tr');
        
        if (!centroCostoId || !fila) return;

        // Obtener datos del centro de costo
        const datosCentro = this.mapeo[centroCostoId];
        if (!datosCentro) return;

        // Actualizar unidad de negocio
        this.actualizarUnidadNegocio(fila, datosCentro.unidad_negocio);
        
        // Actualizar tipo de factura
        this.actualizarTipoFactura(fila, datosCentro.tipo_factura, datosCentro.factura_numero);
        
        // Recalcular totales
        this.actualizarCalculos();
        
        console.log(`Centro de costo actualizado: ${datosCentro.nombre} -> ${datosCentro.unidad_negocio} (${datosCentro.factura_numero})`);
    }

    /**
     * Actualiza la unidad de negocio en la fila
     */
    actualizarUnidadNegocio(fila, unidadNegocioNombre) {
        const selectUnidad = fila.querySelector('select[name*="[unidad_negocio_id]"]');
        if (!selectUnidad) return;
        
        // Buscar la opción que coincida con el nombre
        const opciones = selectUnidad.querySelectorAll('option');
        for (let opcion of opciones) {
            if (opcion.textContent.trim().toUpperCase() === unidadNegocioNombre.toUpperCase()) {
                selectUnidad.value = opcion.value;
                break;
            }
        }
    }

    /**
     * Actualiza el tipo de factura en la fila
     */
    actualizarTipoFactura(fila, tipoFactura, facturaNumero) {
        const inputFactura = fila.querySelector('input[name*="[factura]"]');
        if (inputFactura) {
            inputFactura.value = facturaNumero;
            inputFactura.setAttribute('data-tipo-factura', tipoFactura);
        }
    }

    /**
     * Actualiza todos los cálculos
     */
    actualizarCalculos() {
        this.calcularTotalGeneral();
        this.calcularTotalesPorFactura();
        this.calcularPorcentajes();
        this.actualizarTablaResumen();
        this.validarPorcentajes();
    }

    /**
     * Calcula el total general desde los items
     */
    calcularTotalGeneral() {
        const totalInputs = document.querySelectorAll('.item-total');
        this.totalGeneral = 0;
        
        totalInputs.forEach(input => {
            this.totalGeneral += parseFloat(input.value) || 0;
        });
        
        const totalGeneralElement = document.getElementById('totalGeneral');
        if (totalGeneralElement) {
            totalGeneralElement.textContent = `Q ${this.totalGeneral.toFixed(2)}`;
        }
    }

    /**
     * Calcula totales por tipo de factura
     */
    calcularTotalesPorFactura() {
        // Resetear totales
        this.facturas = {
            1: { total: 0, porcentaje: 0, items: [] },
            2: { total: 0, porcentaje: 0, items: [] },
            3: { total: 0, porcentaje: 0, items: [] }
        };

        // Recorrer todas las filas de distribución
        const filas = document.querySelectorAll('.distribucion-row');
        
        filas.forEach((fila, index) => {
            const porcentajeInput = fila.querySelector('input[name*="[porcentaje]"]');
            const cantidadInput = fila.querySelector('input[name*="[cantidad]"]');
            const facturaInput = fila.querySelector('input[name*="[factura]"]');
            
            if (!porcentajeInput || !cantidadInput || !facturaInput) return;

            const porcentaje = parseFloat(porcentajeInput.value) || 0;
            const cantidad = parseFloat(cantidadInput.value) || 0;
            const tipoFactura = parseInt(facturaInput.getAttribute('data-tipo-factura')) || 1;

            if (porcentaje > 0 && this.facturas[tipoFactura]) {
                this.facturas[tipoFactura].total += cantidad;
                this.facturas[tipoFactura].items.push({
                    fila: index,
                    porcentaje: porcentaje,
                    cantidad: cantidad
                });
            }
        });
    }

    /**
     * Calcula porcentajes de cada factura
     */
    calcularPorcentajes() {
        Object.keys(this.facturas).forEach(tipo => {
            if (this.totalGeneral > 0) {
                this.facturas[tipo].porcentaje = (this.facturas[tipo].total / this.totalGeneral) * 100;
            } else {
                this.facturas[tipo].porcentaje = 0;
            }
        });
    }

    /**
     * Actualiza la tabla de resumen de facturas
     */
    actualizarTablaResumen() {
        // Buscar tabla de resumen de facturas
        const tablaResumen = document.getElementById('tablaResumenFacturas');
        if (!tablaResumen) return;

        const filasResumen = tablaResumen.querySelectorAll('tbody tr');
        
        filasResumen.forEach((fila, index) => {
            const tipoFactura = index + 1;
            const datosFactura = this.facturas[tipoFactura];
            
            if (datosFactura) {
                // Actualizar porcentaje
                const porcentajeCell = fila.querySelector('.porcentaje-factura');
                if (porcentajeCell) {
                    porcentajeCell.textContent = `${datosFactura.porcentaje.toFixed(2)}%`;
                }
                
                // Actualizar monto
                const montoCell = fila.querySelector('.monto-factura');
                if (montoCell) {
                    montoCell.textContent = `Q ${datosFactura.total.toFixed(2)}`;
                }
            }
        });

        // Actualizar totales generales
        const totalPorcentaje = Object.values(this.facturas).reduce((sum, factura) => sum + factura.porcentaje, 0);
        const totalMonto = Object.values(this.facturas).reduce((sum, factura) => sum + factura.total, 0);
        
        const totalPorcentajeElement = document.getElementById('totalPorcentajeFacturas');
        const totalMontoElement = document.getElementById('totalMontoFacturas');
        
        if (totalPorcentajeElement) {
            totalPorcentajeElement.textContent = `${totalPorcentaje.toFixed(2)}%`;
        }
        
        if (totalMontoElement) {
            totalMontoElement.textContent = `Q ${totalMonto.toFixed(2)}`;
        }
    }

    /**
     * Valida que los porcentajes sumen 100%
     */
    validarPorcentajes() {
        const porcentajes = document.querySelectorAll('input[name*="[porcentaje]"]');
        let totalPorcentaje = 0;
        
        porcentajes.forEach(input => {
            totalPorcentaje += parseFloat(input.value) || 0;
        });
        
        const diferencia = Math.abs(totalPorcentaje - 100);
        const esValido = diferencia < 0.01; // Permitir margen de error por redondeo
        
        // Mostrar indicador visual
        const indicadorPorcentaje = document.getElementById('indicadorPorcentaje');
        if (indicadorPorcentaje) {
            indicadorPorcentaje.textContent = `${totalPorcentaje.toFixed(2)}%`;
            indicadorPorcentaje.className = esValido ? 'text-success' : 'text-danger';
        }
        
        // Mostrar mensaje de validación
        const mensajeValidacion = document.getElementById('mensajeValidacionPorcentajes');
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

    /**
     * Agrega una nueva fila de distribución
     */
    agregarNuevaFila() {
        const tabla = document.getElementById('tablaDistribucion');
        const tbody = tabla.querySelector('tbody');
        const filasExistentes = tbody.querySelectorAll('.distribucion-row');
        const nuevoIndex = filasExistentes.length;
        
        // Crear nueva fila
        const nuevaFila = this.crearFilaDistribucion(nuevoIndex);
        tbody.appendChild(nuevaFila);
        
        // Configurar event listeners para la nueva fila
        this.configurarEventListenersFila(nuevaFila);
        
        console.log(`Nueva fila de distribución agregada (índice: ${nuevoIndex})`);
    }

    /**
     * Crea una nueva fila de distribución
     */
    crearFilaDistribucion(index) {
        const fila = document.createElement('tr');
        fila.className = 'distribucion-row';
        
        fila.innerHTML = `
            <td>
                <div class="cuenta-contable-wrapper">
                    <input type="text" 
                           class="form-control cuenta-contable-input" 
                           name="distribucion[${index}][cuenta_contable_display]"
                           placeholder="Buscar cuenta..." 
                           autocomplete="off"
                           data-index="${index}">
                    <input type="hidden" 
                           name="distribucion[${index}][cuenta_contable_id]" 
                           class="cuenta-contable-id" 
                           required>
                    <div class="cuenta-contable-suggestions"></div>
                </div>
            </td>
            <td>
                <select class="form-select" name="distribucion[${index}][centro_costo_id]" required>
                    <option value="">Seleccione...</option>
                    ${this.generarOpcionesCentrosCosto()}
                </select>
            </td>
            <td>
                <select class="form-select" name="distribucion[${index}][ubicacion_id]">
                    <option value="">Seleccione...</option>
                    ${this.generarOpcionesUbicaciones()}
                </select>
            </td>
            <td>
                <select class="form-select" name="distribucion[${index}][unidad_negocio_id]">
                    <option value="">Seleccione...</option>
                    ${this.generarOpcionesUnidadesNegocio()}
                </select>
            </td>
            <td>
                <input type="number" 
                       class="form-control dist-porcentaje" 
                       name="distribucion[${index}][porcentaje]" 
                       min="0" max="100" step="0.01" 
                       value="0" required>
            </td>
            <td>
                <input type="number" 
                       class="form-control dist-cantidad" 
                       name="distribucion[${index}][cantidad]" 
                       readonly>
            </td>
            <td>
                <input type="text" 
                       class="form-control" 
                       name="distribucion[${index}][factura]" 
                       placeholder="Núm. factura" 
                       readonly>
            </td>
            <td class="text-center">
                <button type="button" 
                        class="btn btn-sm btn-danger" 
                        onclick="eliminarDistribucion(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        return fila;
    }

    /**
     * Genera las opciones de centros de costo
     */
    generarOpcionesCentrosCosto() {
        let opciones = '';
        Object.values(this.mapeo).forEach(centro => {
            opciones += `<option value="${centro.id}">${centro.nombre}</option>`;
        });
        return opciones;
    }

    /**
     * Genera las opciones de ubicaciones
     */
    generarOpcionesUbicaciones() {
        // TODO: Implementar carga de ubicaciones desde el servidor
        return `
            <option value="1">GUATEMALA</option>
            <option value="2">COBAN</option>
            <option value="3">QUETZALTENANGO</option>
            <option value="4">HUEHUETENANGO</option>
        `;
    }

    /**
     * Genera las opciones de unidades de negocio
     */
    generarOpcionesUnidadesNegocio() {
        let opciones = '';
        Object.values(this.unidadesNegocio).forEach(unidad => {
            opciones += `<option value="${unidad.id}">${unidad.nombre}</option>`;
        });
        return opciones;
    }

    /**
     * Configura event listeners para una fila específica
     */
    configurarEventListenersFila(fila) {
        const selectCentroCosto = fila.querySelector('select[name*="[centro_costo_id]"]');
        const inputPorcentaje = fila.querySelector('input[name*="[porcentaje]"]');
        
        if (selectCentroCosto) {
            selectCentroCosto.addEventListener('change', (e) => {
                this.onCentroCostoChange(e.target);
            });
        }
        
        if (inputPorcentaje) {
            inputPorcentaje.addEventListener('input', () => {
                this.calcularCantidadPorPorcentaje(fila);
                this.actualizarCalculos();
            });
        }
    }

    /**
     * Calcula la cantidad basada en el porcentaje
     */
    calcularCantidadPorPorcentaje(fila) {
        const porcentajeInput = fila.querySelector('input[name*="[porcentaje]"]');
        const cantidadInput = fila.querySelector('input[name*="[cantidad]"]');
        
        if (!porcentajeInput || !cantidadInput) return;
        
        const porcentaje = parseFloat(porcentajeInput.value) || 0;
        const cantidad = (porcentaje / 100) * this.totalGeneral;
        
        cantidadInput.value = cantidad.toFixed(2);
    }

    /**
     * Maneja la eliminación de una fila
     */
    onEliminarFila(button) {
        const fila = button.closest('tr');
        if (fila) {
            fila.remove();
            this.actualizarCalculos();
            console.log('Fila de distribución eliminada');
        }
    }

    /**
     * Obtiene datos para enviar al servidor
     */
    getDatosParaEnvio() {
        const datos = {
            facturas: this.facturas,
            totalGeneral: this.totalGeneral,
            distribuciones: []
        };

        const filas = document.querySelectorAll('.distribucion-row');
        filas.forEach((fila, index) => {
            const centroCosto = fila.querySelector('[name*="[centro_costo_id]"]')?.value;
            const unidadNegocio = fila.querySelector('[name*="[unidad_negocio_id]"]')?.value;
            const porcentaje = fila.querySelector('[name*="[porcentaje]"]')?.value;
            const cantidad = fila.querySelector('[name*="[cantidad]"]')?.value;
            const factura = fila.querySelector('[name*="[factura]"]')?.value;

            if (centroCosto && porcentaje && cantidad) {
                datos.distribuciones.push({
                    centro_costo_id: centroCosto,
                    unidad_negocio_id: unidadNegocio,
                    porcentaje: parseFloat(porcentaje),
                    cantidad: parseFloat(cantidad),
                    factura: factura
                });
            }
        });

        return datos;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si estamos en la página de requisiciones
    if (document.querySelector('#tablaDistribucion') || document.querySelector('.distribucion-row')) {
        window.centroCostoAutocompletado = new CentroCostoAutocompletado();
    }
});

// Funciones globales para compatibilidad
window.agregarDistribucion = function() {
    if (window.centroCostoAutocompletado) {
        window.centroCostoAutocompletado.agregarNuevaFila();
    }
};

window.eliminarDistribucion = function(button) {
    if (window.centroCostoAutocompletado) {
        window.centroCostoAutocompletado.onEliminarFila(button);
    }
};

// Función para agregar item (compatibilidad con código existente)
window.agregarItem = function() {
    // TODO: Implementar agregar item si es necesario
    console.log('Función agregarItem llamada');
};