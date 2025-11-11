/**
 * Sistema de Cálculo Automático de Cantidades
 * 
 * Implementa el flujo completo de cálculos desde items individuales
 * hasta facturas automáticas con validaciones en tiempo real.
 * 
 * @package RequisicionesMVC
 * @version 2.0
 */

class CalculadorCantidades {
    constructor() {
        this.totalGeneral = 0;
        this.items = [];
        this.distribuciones = [];
        this.facturas = {
            'Factura 1': { porcentaje: 0, monto: 0 },
            'Factura 2': { porcentaje: 0, monto: 0 },
            'Factura 3': { porcentaje: 0, monto: 0 }
        };
        
        this.init();
    }

    /**
     * Inicializa el sistema de cálculos
     */
    init() {
        console.log('Inicializando sistema de cálculos automáticos...');
        
        // Configurar event listeners
        this.configurarEventListeners();
        
        // Calcular valores iniciales
        this.calcularTotalGeneral();
        this.actualizarDistribucionMontos();
        this.actualizarResumenFacturas();
        
        console.log('Sistema de cálculos inicializado correctamente');
    }

    /**
     * Configura todos los event listeners necesarios
     */
    configurarEventListeners() {
        // Event listeners para items
        this.configurarEventListenersItems();
        
        // Event listeners para distribución
        this.configurarEventListenersDistribucion();
        
        // Event listeners para facturas
        this.configurarEventListenersFacturas();
        
        // Event listeners globales
        this.configurarEventListenersGlobales();
    }

    /**
     * Configura event listeners para la tabla de items
     */
    configurarEventListenersItems() {
        // Event listeners para cantidad y precio en items existentes
        document.querySelectorAll('#itemsBody tr').forEach(row => {
            this.configurarEventListenersFilaItem(row);
        });

        // Event listener para el botón de agregar item
        const btnAgregarItem = document.querySelector('a[onclick*="agregarItem"]');
        if (btnAgregarItem) {
            btnAgregarItem.addEventListener('click', () => {
                setTimeout(() => {
                    // Configurar event listeners para la nueva fila
                    const nuevasFilas = document.querySelectorAll('#itemsBody tr:not([data-configured])');
                    nuevasFilas.forEach(fila => {
                        this.configurarEventListenersFilaItem(fila);
                        fila.setAttribute('data-configured', 'true');
                    });
                }, 100);
            });
        }
    }

    /**
     * Configura event listeners para una fila de item específica
     */
    configurarEventListenersFilaItem(row) {
        const cantidadInput = row.querySelector('input[name*="[cantidad]"]');
        const precioInput = row.querySelector('input[name*="[precio_unitario]"]');
        
        if (cantidadInput && precioInput) {
            cantidadInput.addEventListener('input', () => {
                this.calcularTotalFila(row);
                this.actualizarTodo();
            });
            
            precioInput.addEventListener('input', () => {
                this.calcularTotalFila(row);
                this.actualizarTodo();
            });
        }
    }

    /**
     * Configura event listeners para la tabla de distribución
     */
    configurarEventListenersDistribucion() {
        // Event listeners para porcentajes en distribución
        document.querySelectorAll('#tablaDistribucion input[name*="[porcentaje]"]').forEach(input => {
            input.addEventListener('input', () => {
                this.actualizarDistribucionMontos();
                this.actualizarResumenFacturas();
                this.validarPorcentajeTotal();
            });
        });

        // Event listeners para centros de costo
        document.querySelectorAll('#tablaDistribucion select[name*="[centro_costo_id]"]').forEach(select => {
            select.addEventListener('change', () => {
                this.actualizarResumenFacturas();
            });
        });
    }

    /**
     * Configura event listeners para la tabla de facturas
     */
    configurarEventListenersFacturas() {
        // Event listeners para porcentajes de facturas
        document.querySelectorAll('input[name="porcentaje_factura[]"]').forEach(input => {
            input.addEventListener('input', () => {
                this.actualizarMontosFacturas();
            });
        });
    }

    /**
     * Configura event listeners globales
     */
    configurarEventListenersGlobales() {
        // Event listener para cambios en el formulario
        document.addEventListener('input', (e) => {
            if (e.target.matches('input[name*="[total]"]')) {
                this.calcularTotalGeneral();
            }
        });

        // Event listener para el botón de agregar distribución
        const btnAgregarDistribucion = document.querySelector('a[onclick*="agregarDistribucion"]');
        if (btnAgregarDistribucion) {
            btnAgregarDistribucion.addEventListener('click', () => {
                setTimeout(() => {
                    this.configurarEventListenersDistribucion();
                }, 100);
            });
        }
    }

    /**
     * Calcula el total de una fila individual de item
     */
    calcularTotalFila(row) {
        const cantidadInput = row.querySelector('input[name*="[cantidad]"]');
        const precioInput = row.querySelector('input[name*="[precio_unitario]"]');
        const totalInput = row.querySelector('input[name*="[total]"]');
        
        if (!cantidadInput || !precioInput || !totalInput) return;

        const cantidad = parseFloat(cantidadInput.value) || 0;
        const precio = parseFloat(precioInput.value) || 0;
        const total = cantidad * precio;
        
        totalInput.value = total.toFixed(5);
        
        console.log(`Item calculado: ${cantidad} × ${precio} = ${total.toFixed(5)}`);
    }

    /**
     * Calcula el total general sumando todos los items
     */
    calcularTotalGeneral() {
        let total = 0;
        const totalInputs = document.querySelectorAll('input[name*="[total]"]');
        
        totalInputs.forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        
        this.totalGeneral = total;
        
        // Actualizar el campo de total general
        const totalGeneralElement = document.getElementById('totalGeneral');
        if (totalGeneralElement) {
            totalGeneralElement.textContent = `Q ${total.toFixed(2)}`;
        }
        
        // Actualizar el campo hidden si existe
        const totalGeneralInput = document.getElementById('total_general');
        if (totalGeneralInput) {
            totalGeneralInput.value = total.toFixed(5);
        }
        
        console.log(`Total general calculado: ${total.toFixed(5)}`);
        
        // Disparar actualizaciones en cascada
        this.actualizarDistribucionMontos();
        this.actualizarResumenFacturas();
    }

    /**
     * Actualiza los montos de distribución basados en porcentajes
     */
    actualizarDistribucionMontos() {
        const filasDistribucion = document.querySelectorAll('#tablaDistribucion tr.distribucion-row');
        let totalPorcentaje = 0;
        
        filasDistribucion.forEach(row => {
            const porcentajeInput = row.querySelector('input[name*="[porcentaje]"]');
            const cantidadInput = row.querySelector('input[name*="[cantidad]"]');
            
            if (porcentajeInput && cantidadInput) {
                const porcentaje = parseFloat(porcentajeInput.value) || 0;
                totalPorcentaje += porcentaje;
                
                // Calcular la cantidad basada en el porcentaje del total general
                const cantidad = (porcentaje / 100) * this.totalGeneral;
                cantidadInput.value = cantidad.toFixed(5);
                
                console.log(`Distribución: ${porcentaje}% de ${this.totalGeneral} = ${cantidad.toFixed(5)}`);
            }
        });
        
        // Validar porcentajes
        this.validarPorcentajeTotal(totalPorcentaje);
    }

    /**
     * Actualiza el resumen de facturas agrupando por tipo
     */
    actualizarResumenFacturas() {
        // Resetear facturas
        this.facturas = {
            'Factura 1': { porcentaje: 0, monto: 0 },
            'Factura 2': { porcentaje: 0, monto: 0 },
            'Factura 3': { porcentaje: 0, monto: 0 }
        };
        
        // Recorrer todas las distribuciones y agrupar por tipo de factura
        const filasDistribucion = document.querySelectorAll('#tablaDistribucion tr.distribucion-row');
        
        filasDistribucion.forEach(row => {
            const facturaInput = row.querySelector('input[name*="[factura]"]');
            const porcentajeInput = row.querySelector('input[name*="[porcentaje]"]');
            const cantidadInput = row.querySelector('input[name*="[cantidad]"]');
            
            if (facturaInput && porcentajeInput && cantidadInput) {
                const facturaTipo = facturaInput.value;
                const porcentaje = parseFloat(porcentajeInput.value) || 0;
                const monto = parseFloat(cantidadInput.value) || 0;
                
                if (this.facturas.hasOwnProperty(facturaTipo)) {
                    this.facturas[facturaTipo].porcentaje += porcentaje;
                    this.facturas[facturaTipo].monto += monto;
                }
            }
        });

        // Actualizar la tabla de resumen de facturas
        this.actualizarTablaResumenFacturas();
        
        console.log('Facturas actualizadas:', this.facturas);
    }

    /**
     * Actualiza la tabla de resumen de facturas
     */
    actualizarTablaResumenFacturas() {
        const tablaResumen = document.getElementById('tablaResumenFacturas');
        if (!tablaResumen) return;

        const filasResumen = tablaResumen.querySelectorAll('tbody tr');
        
        filasResumen.forEach((fila, index) => {
            const tipoFactura = `Factura ${index + 1}`;
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
                    montoCell.textContent = `Q ${datosFactura.monto.toFixed(2)}`;
                }
            }
        });

        // Actualizar totales generales
        const totalPorcentaje = Object.values(this.facturas).reduce((sum, factura) => sum + factura.porcentaje, 0);
        const totalMonto = Object.values(this.facturas).reduce((sum, factura) => sum + factura.monto, 0);
        
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
     * Valida que los porcentajes sumen exactamente 100%
     */
    validarPorcentajeTotal(totalPorcentaje = null) {
        if (totalPorcentaje === null) {
            const porcentajes = document.querySelectorAll('#tablaDistribucion input[name*="[porcentaje]"]');
            totalPorcentaje = 0;
            
            porcentajes.forEach(input => {
                totalPorcentaje += parseFloat(input.value) || 0;
            });
        }
        
        const diferencia = Math.abs(totalPorcentaje - 100);
        const esValido = diferencia < 0.01; // Permitir margen de error por redondeo
        
        // Mostrar indicador visual
        const indicadorPorcentaje = document.getElementById('indicadorPorcentaje');
        if (indicadorPorcentaje) {
            indicadorPorcentaje.textContent = `${totalPorcentaje.toFixed(2)}%`;
            indicadorPorcentaje.className = esValido ? 'text-success fw-bold' : 'text-danger fw-bold';
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
        
        // Habilitar/deshabilitar botón de envío
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = !esValido;
        }
        
        console.log(`Validación de porcentajes: ${totalPorcentaje.toFixed(2)}% - ${esValido ? 'VÁLIDO' : 'INVÁLIDO'}`);
        
        return esValido;
    }

    /**
     * Actualiza todo el sistema de cálculos
     */
    actualizarTodo() {
        this.calcularTotalGeneral();
        this.actualizarDistribucionMontos();
        this.actualizarResumenFacturas();
    }

    /**
     * Obtiene datos para enviar al servidor
     */
    getDatosParaEnvio() {
        const datos = {
            totalGeneral: this.totalGeneral,
            items: [],
            distribuciones: [],
            facturas: this.facturas
        };

        // Recopilar datos de items
        document.querySelectorAll('#detalle_items tr').forEach(row => {
            const cantidad = row.querySelector('input[name*="[cantidad]"]')?.value;
            const descripcion = row.querySelector('textarea[name*="[descripcion]"]')?.value;
            const precio = row.querySelector('input[name*="[precio_unitario]"]')?.value;
            const total = row.querySelector('input[name*="[total]"]')?.value;

            if (cantidad && descripcion && precio) {
                datos.items.push({
                    cantidad: parseFloat(cantidad),
                    descripcion: descripcion,
                    precio_unitario: parseFloat(precio),
                    total: parseFloat(total)
                });
            }
        });

        // Recopilar datos de distribuciones
        document.querySelectorAll('#tablaDistribucion tr.distribucion-row').forEach(row => {
            const centroCosto = row.querySelector('[name*="[centro_costo_id]"]')?.value;
            const unidadNegocio = row.querySelector('[name*="[unidad_negocio_id]"]')?.value;
            const porcentaje = row.querySelector('[name*="[porcentaje]"]')?.value;
            const cantidad = row.querySelector('[name*="[cantidad]"]')?.value;
            const factura = row.querySelector('[name*="[factura]"]')?.value;

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

    /**
     * Recalcula todo el sistema (útil para edición)
     */
    recalcularTodo() {
        console.log('Recalculando todo el sistema...');
        this.actualizarTodo();
    }
}

// Funciones globales para compatibilidad
window.agregarItem = function() {
    const tbody = document.getElementById('itemsBody');
    if (!tbody) return;
    
    const contadorItems = document.querySelectorAll('#detalle_items tr').length;
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
    
    // Configurar event listeners para la nueva fila
    if (window.calculadorCantidades) {
        window.calculadorCantidades.configurarEventListenersFilaItem(newRow);
        newRow.setAttribute('data-configured', 'true');
    }
};

window.eliminarItem = function(button) {
    const fila = button.closest('tr');
    if (fila) {
        fila.remove();
        if (window.calculadorCantidades) {
            window.calculadorCantidades.actualizarTodo();
        }
    }
};

window.eliminarDistribucion = function(button) {
    const fila = button.closest('tr');
    if (fila) {
        fila.remove();
        if (window.calculadorCantidades) {
            window.calculadorCantidades.actualizarTodo();
        }
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar si estamos en la página de requisiciones
    if (document.querySelector('#detalle_items') || document.querySelector('#tablaDistribucion')) {
        window.calculadorCantidades = new CalculadorCantidades();
        console.log('Sistema de cálculos automáticos inicializado');
    }
});
