<?php
/**
 * RequisicionService
 * 
 * Servicio para gestionar el ciclo completo de vida de una requisición.
 * Maneja creación, edición, eliminación, validaciones y consultas.
 * 
 * @package RequisicionesMVC\Services
 * @version 2.0
 */

namespace App\Services;

use App\Models\OrdenCompra;
use App\Models\DetalleItem;
use App\Models\DistribucionGasto;
use App\Models\Factura;
use App\Models\ArchivoAdjunto;
use App\Models\HistorialRequisicion;
use App\Models\Usuario;
use App\Models\CentroCosto;
use App\Models\CuentaContable;

class RequisicionService
{
    /**
     * Servicio de autorización
     * 
     * @var AutorizacionService
     */
    private $autorizacionService;

    /**
     * Servicio de notificaciones
     * 
     * @var NotificacionService
     */
    private $notificacionService;

    /**
     * Constructor
     * 
     * @param AutorizacionService $autorizacionService
     * @param NotificacionService $notificacionService
     */
    public function __construct(
        AutorizacionService $autorizacionService = null,
        NotificacionService $notificacionService = null
    ) {
        $this->autorizacionService = $autorizacionService ?? new AutorizacionService();
        $this->notificacionService = $notificacionService ?? new NotificacionService();
    }

    // ========================================================================
    // CREACIÓN DE REQUISICIONES
    // ========================================================================

    /**
     * Crea una requisición completa con todos sus componentes
     * 
     * @param array $data Datos de la requisición
     * @param int $usuarioId ID del usuario creador
     * @return array Resultado
     */
    public function crearRequisicion($data, $usuarioId, $estado = 'enviado')
    {
        try {
            // Procesar datos del formulario con el nuevo flujo de cálculos
            $datosProcesados = $this->procesarDatosFormulario($data);
            
            // Validar datos básicos
            $validacion = $this->validarDatosRequisicion($datosProcesados);
            if (!$validacion['success']) {
                return $validacion;
            }

            // Iniciar transacción
            $conn = OrdenCompra::getConnection();
            $conn->beginTransaction();

            try {
                // 1. Crear orden de compra con monto total calculado (optimizado)
                error_log("RequisicionService::crearRequisicion() - Iniciando creación de orden de compra");
                $orden = OrdenCompra::create([
                    'nombre_razon_social' => $datosProcesados['nombre_razon_social'],
                    'fecha' => $datosProcesados['fecha'] ?? date('Y-m-d'),
                    'causal_compra' => $datosProcesados['causal_compra'],
                    'moneda' => $datosProcesados['moneda'],
                    'forma_pago' => $datosProcesados['forma_pago'],
                    'anticipo' => $datosProcesados['anticipo'] ?? 0,
                    'unidad_requirente' => $datosProcesados['unidad_requirente'] ?? 1, // Default to first unidad
                    'justificacion' => $datosProcesados['justificacion'] ?? '',
                    'datos_proveedor' => $datosProcesados['datos_proveedor'] ?? '',
                    'razon_seleccion' => $datosProcesados['razon_seleccion'] ?? '',
                    'monto_total' => $datosProcesados['monto_total'],
                    'usuario_id' => $usuarioId,
                    'estado' => $estado  // borrador o enviado
                ]);

                if (!$orden || !$orden->id) {
                    throw new \Exception('Error al crear la orden de compra');
                }

                $ordenId = $orden->id;
                error_log("RequisicionService::crearRequisicion() - Orden creada con ID: " . $ordenId);

                // 2. Guardar items con totales calculados
                error_log("RequisicionService::crearRequisicion() - Iniciando guardado de items");
                $resultItems = $this->guardarItemsConTotales($ordenId, $datosProcesados['items']);
                if (!$resultItems['success']) {
                    throw new \Exception($resultItems['error']);
                }
                error_log("RequisicionService::crearRequisicion() - Items guardados exitosamente");

                // 3. Guardar distribución de gastos con cantidades calculadas
                error_log("RequisicionService::crearRequisicion() - Iniciando guardado de distribución");
                $resultDist = $this->guardarDistribucionConCantidades($ordenId, $datosProcesados['distribucion'], $datosProcesados['monto_total']);
                if (!$resultDist['success']) {
                    throw new \Exception($resultDist['error']);
                }
                error_log("RequisicionService::crearRequisicion() - Distribución guardada exitosamente");

                // 4. Generar facturas automáticas
                error_log("RequisicionService::crearRequisicion() - Iniciando generación de facturas");
                $resultFacturas = $this->generarFacturasAutomaticas($ordenId, $datosProcesados['distribucion'], $datosProcesados['monto_total'], $datosProcesados['forma_pago']);
                if (!$resultFacturas['success']) {
                    throw new \Exception($resultFacturas['error']);
                }
                error_log("RequisicionService::crearRequisicion() - Facturas generadas exitosamente");

                // 5. Registrar en historial
                error_log("RequisicionService::crearRequisicion() - Registrando en historial");
                HistorialRequisicion::registrarCreacion($ordenId, $usuarioId);
                error_log("RequisicionService::crearRequisicion() - Historial registrado exitosamente");

                // 6. Iniciar flujo de validación con nuevo sistema v3.0 (solo para requisiciones enviadas)
                if ($estado === 'enviado') {
                    error_log("RequisicionService::crearRequisicion() - Iniciando flujo de validación v3.0 para requisición enviada: " . $ordenId);
                    $flujoService = new \App\Services\FlujoValidacionService();
                    $resultFlujo = $flujoService->iniciarFlujo($ordenId);
                    error_log("RequisicionService::crearRequisicion() - Resultado del flujo v3.0: " . json_encode($resultFlujo));
                    
                    if (!$resultFlujo['success']) {
                        error_log("Error iniciando flujo de validación v3.0: " . $resultFlujo['error']);
                        // Registrar el error pero no fallar la creación de la requisición
                        HistorialRequisicion::registrar(
                            $ordenId,
                            'error_flujo',
                            'Error al iniciar flujo de validación v3.0: ' . $resultFlujo['error'],
                            $usuarioId
                        );
                    } else {
                        error_log("✅ Flujo de validación v3.0 iniciado exitosamente para requisición: " . $ordenId);
                        error_log("Tipos de autorización requeridos: " . implode(', ', $resultFlujo['tipos_requeridos'] ?? []));
                        error_log("Total de autorizaciones pendientes: " . ($resultFlujo['total_autorizaciones'] ?? 0));
                    }
                } else {
                    error_log("RequisicionService::crearRequisicion() - Requisición guardada como borrador, no se inicia flujo de validación");
                }

                $conn->commit();

                return [
                    'success' => true,
                    'message' => 'Requisición creada exitosamente',
                    'orden_id' => $ordenId,
                    'monto_total' => $datosProcesados['monto_total']
                ];
            } catch (\Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            // Log detallado del error
            \App\Helpers\ErrorLogger::logRequisicionError(
                'crear_requisicion_service',
                $data ?? [],
                $e->getMessage(),
                [
                    'usuario_id' => $usuarioId,
                    'estado_solicitado' => $estado,
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine(),
                    'stack_trace' => $e->getTraceAsString(),
                    'datos_procesados' => $datosProcesados ?? 'No procesados'
                ]
            );
            
            error_log("Error creando requisición: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'CREATE_ERROR'
            ];
        }
    }

    /**
     * Valida los datos de una requisición
     * 
     * @param array $data Datos a validar
     * @return array Resultado de validación
     */
    public function validarDatosRequisicion($data)
    {
        $errores = [];

        // Validar nombre o razón social
        if (empty($data['nombre_razon_social'])) {
            $errores[] = 'El nombre o razón social es obligatorio';
        }

        // Validar causal de compra
        if (empty($data['causal_compra'])) {
            $errores[] = 'La causal de compra es obligatoria';
        }

        // Validar moneda
        if (empty($data['moneda'])) {
            $errores[] = 'La moneda es obligatoria';
        }

        // Validar items
        if (empty($data['items']) || !is_array($data['items'])) {
            $errores[] = 'Debe incluir al menos un item';
        } else {
            $validacionItems = $this->validarItems($data['items']);
            if (!$validacionItems['success']) {
                $errores[] = $validacionItems['error'];
            }
        }

        // Validar distribución
        if (empty($data['distribucion']) || !is_array($data['distribucion'])) {
            $errores[] = 'Debe incluir la distribución de gastos';
        } else {
            $validacionDist = $this->validarDistribucion($data['distribucion']);
            if (!$validacionDist['success']) {
                $errores[] = $validacionDist['error'];
            }
        }

        // Validar facturas si existen
        if (!empty($data['facturas'])) {
            $validacionFacturas = $this->validarFacturas($data['facturas']);
            if (!$validacionFacturas['success']) {
                $errores[] = $validacionFacturas['error'];
            }
        }

        if (!empty($errores)) {
            return [
                'success' => false,
                'error' => implode(', ', $errores),
                'code' => 'VALIDATION_ERROR'
            ];
        }

        return ['success' => true];
    }

    /**
     * Guarda los items de una requisición
     * 
     * @param int $ordenId ID de la orden
     * @param array $items Array de items
     * @return array Resultado
     */
    public function guardarItems($ordenId, $items)
    {
        try {
            foreach ($items as $item) {
                DetalleItem::create([
                    'orden_compra_id' => $ordenId,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'unidad_medida' => $item['unidad_medida'] ?? 'unidad'
                ]);
            }

            return [
                'success' => true,
                'message' => 'Items guardados correctamente'
            ];
        } catch (\Exception $e) {
            error_log("Error guardando items: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al guardar items: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida los items
     * 
     * @param array $items
     * @return array Resultado
     */
    public function validarItems($items)
    {
        foreach ($items as $index => $item) {
            if (empty($item['descripcion'])) {
                return [
                    'success' => false,
                    'error' => "Item " . ($index + 1) . ": La descripción es obligatoria"
                ];
            }

            if (!isset($item['cantidad']) || $item['cantidad'] <= 0) {
                return [
                    'success' => false,
                    'error' => "Item " . ($index + 1) . ": La cantidad debe ser mayor a 0"
                ];
            }

            if (!isset($item['precio_unitario']) || $item['precio_unitario'] < 0) {
                return [
                    'success' => false,
                    'error' => "Item " . ($index + 1) . ": El precio unitario es inválido"
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Calcula el total de los items
     * 
     * @param array $items
     * @return float Total
     */
    public function calcularTotalItems($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += ($item['cantidad'] ?? 0) * ($item['precio_unitario'] ?? 0);
        }
        return $total;
    }

    // ========================================================================
    // DISTRIBUCIÓN DE GASTOS
    // ========================================================================

    /**
     * Guarda la distribución de gastos
     * 
     * @param int $ordenId ID de la orden
     * @param array $distribucion Array de distribución
     * @return array Resultado
     */
    public function guardarDistribucion($ordenId, $distribucion)
    {
        try {
            // Validar que sume 100%
            $validacion = $this->validarDistribucion($distribucion);
            if (!$validacion['success']) {
                return $validacion;
            }

            // Calcular montos según porcentajes
            $montoTotal = $this->calcularTotalItems(DetalleItem::porOrdenCompra($ordenId));
            $distribucionConMontos = $this->calcularMontosDistribucion($distribucion, $montoTotal);

            // Guardar cada línea de distribución
            foreach ($distribucionConMontos as $dist) {
                DistribucionGasto::create([
                    'orden_compra_id' => $ordenId,
                    'centro_costo_id' => $dist['centro_costo_id'],
                    'cuenta_contable_id' => $dist['cuenta_contable_id'],
                    'ubicacion_id' => $dist['ubicacion_id'] ?? null,
                    'unidad_negocio_id' => $dist['unidad_negocio_id'] ?? null,
                    'porcentaje' => $dist['porcentaje'],
                    'cantidad' => $dist['cantidad']
                ]);
            }

            return [
                'success' => true,
                'message' => 'Distribución guardada correctamente'
            ];
        } catch (\Exception $e) {
            error_log("Error guardando distribución: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al guardar distribución: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida la distribución de gastos
     * 
     * @param array $distribucion
     * @return array Resultado
     */
    public function validarDistribucion($distribucion)
    {
        if (empty($distribucion)) {
            return [
                'success' => false,
                'error' => 'La distribución no puede estar vacía'
            ];
        }

        $totalPorcentaje = 0;

        foreach ($distribucion as $index => $dist) {
            // Validar campos obligatorios
            if (empty($dist['centro_costo_id'])) {
                return [
                    'success' => false,
                    'error' => "Distribución " . ($index + 1) . ": El centro de costo es obligatorio"
                ];
            }

            if (empty($dist['cuenta_contable_id'])) {
                return [
                    'success' => false,
                    'error' => "Distribución " . ($index + 1) . ": La cuenta contable es obligatoria"
                ];
            }

            if (!isset($dist['porcentaje']) || $dist['porcentaje'] <= 0) {
                return [
                    'success' => false,
                    'error' => "Distribución " . ($index + 1) . ": El porcentaje debe ser mayor a 0"
                ];
            }

            $totalPorcentaje += $dist['porcentaje'];
        }

        // Validar que sume 100% (con margen de error de 0.01)
        if (abs($totalPorcentaje - 100) > 0.01) {
            return [
                'success' => false,
                'error' => "La distribución debe sumar 100%. Actualmente suma: {$totalPorcentaje}%"
            ];
        }

        return ['success' => true];
    }

    /**
     * Calcula los montos de la distribución según porcentajes
     * 
     * @param array $distribucion
     * @param float $montoTotal
     * @return array Distribución con montos
     */
    public function calcularMontosDistribucion($distribucion, $montoTotal)
    {
        $resultado = [];

        foreach ($distribucion as $dist) {
            $monto = ($dist['porcentaje'] / 100) * $montoTotal;
            
            $resultado[] = array_merge($dist, [
                'cantidad' => round($monto, 2)
            ]);
        }

        return $resultado;
    }

    // ========================================================================
    // FACTURAS
    // ========================================================================

    /**
     * Guarda las facturas de una requisición
     * 
     * @param int $ordenId ID de la orden
     * @param array $facturas Array de facturas
     * @return array Resultado
     */
    public function guardarFacturas($ordenId, $facturas)
    {
        try {
            // Validar que sume 100%
            $validacion = $this->validarFacturas($facturas);
            if (!$validacion['success']) {
                return $validacion;
            }

            foreach ($facturas as $factura) {
                Factura::create([
                    'orden_compra_id' => $ordenId,
                    'numero_factura' => $factura['numero_factura'] ?? '',
                    'fecha_factura' => $factura['fecha_factura'] ?? date('Y-m-d'),
                    'monto' => $factura['monto'],
                    'forma_pago' => $factura['forma_pago'],
                    'estado' => 'pendiente'
                ]);
            }

            return [
                'success' => true,
                'message' => 'Facturas guardadas correctamente'
            ];
        } catch (\Exception $e) {
            error_log("Error guardando facturas: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al guardar facturas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida las facturas
     * 
     * @param array $facturas
     * @return array Resultado
     */
    public function validarFacturas($facturas)
    {
        if (empty($facturas)) {
            return ['success' => true]; // Las facturas son opcionales
        }

        $totalPorcentaje = 0;

        foreach ($facturas as $index => $factura) {
            if (!isset($factura['monto']) || $factura['monto'] <= 0) {
                return [
                    'success' => false,
                    'error' => "Factura " . ($index + 1) . ": El monto debe ser mayor a 0"
                ];
            }

            if (empty($factura['forma_pago'])) {
                return [
                    'success' => false,
                    'error' => "Factura " . ($index + 1) . ": La forma de pago es obligatoria"
                ];
            }

            if (isset($factura['porcentaje'])) {
                $totalPorcentaje += $factura['porcentaje'];
            }
        }

        // Si se especificaron porcentajes, validar que sumen 100%
        if ($totalPorcentaje > 0 && abs($totalPorcentaje - 100) > 0.01) {
            return [
                'success' => false,
                'error' => "Los porcentajes de las facturas deben sumar 100%. Actualmente suman: {$totalPorcentaje}%"
            ];
        }

        return ['success' => true];
    }

    // ========================================================================
    // ARCHIVOS ADJUNTOS
    // ========================================================================

    /**
     * Guarda archivos adjuntos de una requisición
     * 
     * @param int $ordenId ID de la orden
     * @param array $archivos Array de archivos ($_FILES)
     * @return array Resultado
     */
    public function guardarArchivos($ordenId, $archivos)
    {
        try {
            $resultados = [];

            foreach ($archivos as $archivo) {
                $result = ArchivoAdjunto::subirArchivo($archivo, $ordenId);
                if ($result) {
                    $resultados[] = $result;
                }
            }

            return [
                'success' => true,
                'message' => 'Archivos guardados',
                'count' => count($resultados)
            ];
        } catch (\Exception $e) {
            error_log("Error guardando archivos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al guardar archivos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un archivo adjunto
     * 
     * @param int $archivoId ID del archivo
     * @param int $usuarioId ID del usuario
     * @return array Resultado
     */
    public function eliminarArchivo($archivoId, $usuarioId)
    {
        try {
            $archivo = ArchivoAdjunto::find($archivoId);
            if (!$archivo) {
                return [
                    'success' => false,
                    'error' => 'Archivo no encontrado'
                ];
            }

            // Verificar permisos
            if (!$this->puedeEditar($archivo['orden_compra_id'], $usuarioId)) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para eliminar este archivo'
                ];
            }

            $resultado = ArchivoAdjunto::eliminarArchivo($archivoId);

            if ($resultado) {
                return [
                    'success' => true,
                    'message' => 'Archivo eliminado correctamente'
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al eliminar el archivo'
            ];
        } catch (\Exception $e) {
            error_log("Error eliminando archivo: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========================================================================
    // EDICIÓN Y ELIMINACIÓN
    // ========================================================================

    /**
     * Edita una requisición existente
     * 
     * @param int $ordenId ID de la orden
     * @param array $data Datos actualizados
     * @param int $usuarioId ID del usuario
     * @return array Resultado
     */
    public function editarRequisicion($ordenId, $data, $usuarioId)
    {
        try {
            // Verificar permisos
            if (!$this->puedeEditar($ordenId, $usuarioId)) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para editar esta requisición',
                    'code' => 'UNAUTHORIZED'
                ];
            }

            // Actualizar orden de compra
            $resultado = OrdenCompra::update($ordenId, [
                'nombre_razon_social' => $data['nombre_razon_social'] ?? null,
                'lugar_entrega' => $data['lugar_entrega'] ?? null,
                'justificacion' => $data['justificacion'] ?? null
            ]);

            if ($resultado) {
                // Registrar en historial
                HistorialRequisicion::registrarEdicion($ordenId, $usuarioId, array_keys($data));

                return [
                    'success' => true,
                    'message' => 'Requisición editada correctamente'
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al editar la requisición'
            ];
        } catch (\Exception $e) {
            error_log("Error editando requisición: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si un usuario puede editar una requisición
     * 
     * @param int $ordenId ID de la orden
     * @param int $usuarioId ID del usuario
     * @return bool
     */
    public function puedeEditar($ordenId, $usuarioId)
    {
        $orden = OrdenCompra::find($ordenId);
        if (!$orden) {
            return false;
        }

        // Solo el creador puede editar
        if ($orden->usuario_id != $usuarioId) {
            return false;
        }

        // Solo si está en revisión o pendiente
        $flujo = $orden->autorizacionFlujo();
        if (!$flujo) {
            return true; // Sin flujo, puede editar
        }

        return in_array($flujo->estado, ['pendiente_revision', 'rechazado_revision']);
    }

    /**
     * Elimina una requisición
     * 
     * @param int $ordenId ID de la orden
     * @param int $usuarioId ID del usuario
     * @return array Resultado
     */
    public function eliminarRequisicion($ordenId, $usuarioId)
    {
        try {
            if (!$this->puedeEliminar($ordenId, $usuarioId)) {
                return [
                    'success' => false,
                    'error' => 'No tienes permisos para eliminar esta requisición'
                ];
            }

            $conn = OrdenCompra::getConnection();
            $conn->beginTransaction();

            try {
                // Eliminar en cascada
                DetalleItem::deleteByOrden($ordenId);
                DistribucionGasto::deleteByOrden($ordenId);
                Factura::deleteByOrden($ordenId);
                // Los archivos se eliminan físicamente
                $archivos = ArchivoAdjunto::porOrdenCompra($ordenId);
                foreach ($archivos as $archivo) {
                    ArchivoAdjunto::eliminarArchivo($archivo['id']);
                }

                OrdenCompra::delete($ordenId);

                $conn->commit();

                return [
                    'success' => true,
                    'message' => 'Requisición eliminada correctamente'
                ];
            } catch (\Exception $e) {
                $conn->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log("Error eliminando requisición: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si un usuario puede eliminar una requisición
     * 
     * @param int $ordenId ID de la orden
     * @param int $usuarioId ID del usuario
     * @return bool
     */
    public function puedeEliminar($ordenId, $usuarioId)
    {
        $orden = OrdenCompra::find($ordenId);
        if (!$orden) {
            return false;
        }

        // Solo el creador puede eliminar
        if ($orden->usuario_id != $usuarioId) {
            return false;
        }

        // Solo si no ha sido autorizada
        $flujo = $orden->autorizacionFlujo();
        if (!$flujo) {
            return true;
        }

        return $flujo->estado !== 'autorizado';
    }

    // ========================================================================
    // CONSULTAS
    // ========================================================================

    /**
     * Obtiene una requisición por ID
     * 
     * @param int $ordenId ID de la orden
     * @return array|null Requisición
     */
    public function getRequisicion($ordenId)
    {
        return OrdenCompra::find($ordenId);
    }

    /**
     * Obtiene una requisición completa con relaciones
     * 
     * @param int $ordenId ID de la orden
     * @return array|null Requisición completa
     */
    public function getRequisicionCompleta($ordenId)
    {
        $orden = OrdenCompra::find($ordenId);
        if (!$orden) {
            return null;
        }

        // Obtener información del usuario creador
        $usuarioCreador = Usuario::find($orden->usuario_id);
        if ($usuarioCreador) {
            $orden->usuario_nombre = $usuarioCreador->azure_display_name ?? $usuarioCreador->nombre ?? 'Usuario desconocido';
            $orden->usuario_email = $usuarioCreador->azure_email ?? $usuarioCreador->email ?? '';
        } else {
            $orden->usuario_nombre = 'Usuario no encontrado';
            $orden->usuario_email = '';
        }

        return [
            'orden' => $orden,
            'items' => DetalleItem::porOrdenCompra($ordenId),
            'distribucion' => DistribucionGasto::porOrdenCompra($ordenId),
            'facturas' => Factura::porOrdenCompra($ordenId),
            'archivos' => ArchivoAdjunto::porOrdenCompra($ordenId),
            'flujo' => $orden->autorizacionFlujo(),
            'historial' => HistorialRequisicion::porOrdenCompra($ordenId)
        ];
    }

    /**
     * Lista requisiciones con filtros
     * 
     * @param array $filtros Filtros a aplicar
     * @param array $paginacion Parámetros de paginación
     * @return array Listado de requisiciones
     */
    public function listarRequisiciones($filtros = [], $paginacion = [])
    {
        // TODO: Implementar filtros y paginación
        return OrdenCompra::all();
    }

    /**
     * Filtra requisiciones por estado
     * 
     * @param string $estado Estado del flujo
     * @return array Requisiciones filtradas
     */
    public function filtrarPorEstado($estado)
    {
        return OrdenCompra::porEstado($estado);
    }

    /**
     * Busca requisiciones por término
     * 
     * @param string $termino Término de búsqueda
     * @return array Resultados
     */
    public function buscar($termino)
    {
        return OrdenCompra::buscar($termino);
    }

    /**
     * Calcula el monto total de una requisición
     * 
     * @param int $ordenId ID de la orden
     * @return float Monto total
     */
    public function calcularMontoTotal($ordenId)
    {
        $items = DetalleItem::porOrdenCompra($ordenId);
        return $this->calcularTotalItems($items);
    }

    /**
     * Actualiza el monto total de una requisición
     * 
     * @param int $ordenId ID de la orden
     * @return bool
     */
    public function actualizarMontoTotal($ordenId)
    {
        $montoTotal = $this->calcularMontoTotal($ordenId);
        return OrdenCompra::update($ordenId, ['monto_total' => $montoTotal]);
    }

    /**
     * Obtiene el estado de una requisición
     * 
     * @param int $ordenId ID de la orden
     * @return string|null Estado
     */
    public function obtenerEstado($ordenId)
    {
        $orden = OrdenCompra::find($ordenId);
        if (!$orden) {
            return null;
        }

        $flujo = $orden->autorizacionFlujo();
        return $flujo ? $flujo->estado : 'sin_flujo';
    }

    /**
     * Obtiene estadísticas de un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return array Estadísticas
     */
    public function getEstadisticasUsuario($usuarioId)
    {
        return OrdenCompra::getEstadisticasUsuario($usuarioId);
    }

    /**
     * Obtiene estadísticas generales
     * 
     * @return array Estadísticas
     */
    public function getEstadisticasGenerales()
    {
        return OrdenCompra::getEstadisticasGenerales();
    }

    // ========================================================================
    // MÉTODOS PARA EL NUEVO FLUJO DE CÁLCULOS
    // ========================================================================

    /**
     * Procesa los datos del formulario aplicando el flujo de cálculos
     * 
     * @param array $data Datos del formulario
     * @return array Datos procesados
     */
    private function procesarDatosFormulario($data)
    {
        $datosProcesados = $data;
        
        // 0. Asegurar campos obligatorios con valores por defecto
        $datosProcesados['causal_compra'] = $data['causal_compra'] ?? 'otro';
        $datosProcesados['moneda'] = $data['moneda'] ?? 'GTQ';
        $datosProcesados['forma_pago'] = $data['forma_pago'] ?? 'contado';
        $datosProcesados['anticipo'] = $data['anticipo'] ?? 0;
        $datosProcesados['fecha'] = $data['fecha'] ?? date('Y-m-d');
        
        // 1. Procesar items y calcular totales
        $items = [];
        $montoTotal = 0;
        
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $cantidad = floatval($item['cantidad'] ?? 0);
                $precioUnitario = floatval($item['precio_unitario'] ?? 0);
                $total = $cantidad * $precioUnitario;
                
                $items[] = [
                    'cantidad' => $cantidad,
                    'descripcion' => $item['descripcion'] ?? '',
                    'precio_unitario' => $precioUnitario,
                    'total' => $total
                ];
                
                $montoTotal += $total;
            }
        }
        
        $datosProcesados['items'] = $items;
        $datosProcesados['monto_total'] = $montoTotal;
        
        // 2. Procesar distribución y calcular cantidades
        $distribucion = [];
        if (!empty($data['distribucion'])) {
            foreach ($data['distribucion'] as $dist) {
                $porcentaje = floatval($dist['porcentaje'] ?? 0);
                $cantidad = floatval($dist['cantidad'] ?? 0);
                
                // Si la cantidad es 0 o negativa, calcular basado en porcentaje
                if ($cantidad <= 0 && $porcentaje > 0) {
                    $cantidad = ($porcentaje / 100) * $montoTotal;
                }
                
                // Limpiar IDs vacíos
                $ubicacionId = (!empty($dist['ubicacion_id']) && $dist['ubicacion_id'] !== '') ? $dist['ubicacion_id'] : null;
                $unidadNegocioId = (!empty($dist['unidad_negocio_id']) && $dist['unidad_negocio_id'] !== '') ? $dist['unidad_negocio_id'] : null;
                
                $distribucion[] = [
                    'cuenta_contable_id' => $dist['cuenta_contable_id'] ?? null,
                    'centro_costo_id' => $dist['centro_costo_id'] ?? null,
                    'ubicacion_id' => $ubicacionId,
                    'unidad_negocio_id' => $unidadNegocioId,
                    'porcentaje' => $porcentaje,
                    'cantidad' => $cantidad,
                    'factura' => $dist['factura'] ?? ''
                ];
            }
        }
        
        $datosProcesados['distribucion'] = $distribucion;
        
        error_log("Datos procesados - Total: " . $montoTotal . ", Items: " . count($items) . ", Distribuciones: " . count($distribucion));
        
        return $datosProcesados;
    }

    /**
     * Guarda items con totales ya calculados
     * 
     * @param int $ordenId ID de la orden
     * @param array $items Array de items con totales
     * @return array Resultado
     */
    private function guardarItemsConTotales($ordenId, $items)
    {
        try {
            foreach ($items as $item) {
                $detalleItem = new DetalleItem([
                    'orden_compra_id' => $ordenId,
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'total' => $item['total'],
                    'unidad_medida' => 'unidad'
                ]);
                
                if (!$detalleItem->save()) {
                    throw new \Exception('Error al guardar item: ' . $item['descripcion']);
                }
            }

            return [
                'success' => true,
                'message' => 'Items guardados correctamente'
            ];
        } catch (\Exception $e) {
            error_log("Error guardando items: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al guardar items: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Guarda distribución con cantidades ya calculadas
     * 
     * @param int $ordenId ID de la orden
     * @param array $distribucion Array de distribución
     * @param float $montoTotal Monto total para validación
     * @return array Resultado
     */
    private function guardarDistribucionConCantidades($ordenId, $distribucion, $montoTotal)
    {
        try {
            // Validar que sume 100%
            $totalPorcentaje = 0;
            foreach ($distribucion as $dist) {
                $totalPorcentaje += $dist['porcentaje'];
            }
            
            if (abs($totalPorcentaje - 100) > 0.01) {
                return [
                    'success' => false,
                    'error' => "Los porcentajes deben sumar exactamente 100% (actual: {$totalPorcentaje}%)"
                ];
            }

            // Guardar cada línea de distribución
            foreach ($distribucion as $dist) {
                // Limpiar valores vacíos y convertir a null
                $ubicacionId = (!empty($dist['ubicacion_id']) && $dist['ubicacion_id'] !== '') ? $dist['ubicacion_id'] : null;
                $unidadNegocioId = (!empty($dist['unidad_negocio_id']) && $dist['unidad_negocio_id'] !== '') ? $dist['unidad_negocio_id'] : null;
                
                $distribucionGasto = new DistribucionGasto([
                    'orden_compra_id' => $ordenId,
                    'centro_costo_id' => $dist['centro_costo_id'],
                    'cuenta_contable_id' => $dist['cuenta_contable_id'],
                    'ubicacion_id' => $ubicacionId,
                    'unidad_negocio_id' => $unidadNegocioId,
                    'porcentaje' => $dist['porcentaje'],
                    'cantidad' => $dist['cantidad']
                ]);
                
                if (!$distribucionGasto->save()) {
                    throw new \Exception('Error al guardar distribución de gasto');
                }
            }

            return [
                'success' => true,
                'message' => 'Distribución guardada correctamente'
            ];
        } catch (\Exception $e) {
            error_log("Error guardando distribución: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al guardar distribución: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Genera facturas automáticas basadas en la distribución
     * 
     * @param int $ordenId ID de la orden
     * @param array $distribucion Array de distribución
     * @param float $montoTotal Monto total
     * @param string $formaPago Forma de pago
     * @return array Resultado
     */
    private function generarFacturasAutomaticas($ordenId, $distribucion, $montoTotal, $formaPago)
    {
        try {
            // Agrupar por tipo de factura
            $facturas = [
                1 => ['porcentaje' => 0, 'monto' => 0],
                2 => ['porcentaje' => 0, 'monto' => 0],
                3 => ['porcentaje' => 0, 'monto' => 0]
            ];

            // Sumar las cantidades por factura
            foreach ($distribucion as $dist) {
                $facturaTipo = intval($dist['factura'] ?? 1);
                $cantidad = floatval($dist['cantidad'] ?? 0);
                
                if (isset($facturas[$facturaTipo]) && $cantidad > 0) {
                    $facturas[$facturaTipo]['monto'] += $cantidad;
                }
            }

            // Calcular porcentajes basados en el monto total
            foreach ($facturas as $tipo => $datos) {
                if ($montoTotal > 0 && $datos['monto'] > 0) {
                    $facturas[$tipo]['porcentaje'] = ($datos['monto'] / $montoTotal) * 100;
                }
            }

            // Guardar facturas que tengan monto > 0
            foreach ($facturas as $numeroTipo => $datos) {
                if ($datos['monto'] > 0) {
                    $factura = new Factura([
                        'orden_compra_id' => $ordenId,
                        'forma_pago' => $formaPago,
                        'factura_numero' => 'Factura ' . $numeroTipo,
                        'porcentaje' => round($datos['porcentaje'], 2),
                        'monto' => round($datos['monto'], 2)
                    ]);
                    
                    if (!$factura->save()) {
                        throw new \Exception('Error al guardar factura: Factura ' . $numeroTipo);
                    }
                }
            }

            return [
                'success' => true,
                'message' => 'Facturas generadas correctamente'
            ];
        } catch (\Exception $e) {
            error_log("Error generando facturas: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error al generar facturas: ' . $e->getMessage()
            ];
        }
    }
}
