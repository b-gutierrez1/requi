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

use App\Models\Requisicion;
use App\Models\DetalleItem;
use App\Models\DistribucionGasto;
use App\Models\Factura;
use App\Models\ArchivoAdjunto;
use App\Models\HistorialRequisicion;
use App\Models\Usuario;
use App\Models\CentroCosto;
use App\Models\CuentaContable;
use App\Models\AutorizacionFlujo;

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
    public function crearRequisicion($data, $usuarioId, $estado = 'pendiente_revision')
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
            $conn = Requisicion::getConnection();
            $conn->beginTransaction();

            try {
                // 1. Crear orden de compra con monto total calculado (optimizado)
                // Nota: El numero_requisicion se genera automáticamente basado en el ID (AUTO_INCREMENT)
                error_log("RequisicionService::crearRequisicion() - Iniciando creación de orden de compra");
                $orden = Requisicion::create([
                    'proveedor_nombre' => $datosProcesados['nombre_razon_social'],
                    'fecha_solicitud' => $datosProcesados['fecha'] ?? date('Y-m-d'),
                    'causal_compra' => $datosProcesados['causal_compra'],
                    'moneda' => $datosProcesados['moneda'],
                    'forma_pago' => $datosProcesados['forma_pago'],
                    'anticipo' => $datosProcesados['anticipo'] ?? 0,
                    'unidad_requirente' => $datosProcesados['unidad_requirente'] ?? 1, // Default to first unidad
                    'justificacion' => $datosProcesados['justificacion'] ?? '',
                    'observaciones' => $datosProcesados['observaciones'] ?? '',
                    'monto_total' => $datosProcesados['monto_total'],
                    'usuario_id' => $usuarioId,
                    'estado' => $estado  // borrador o pendiente_revision
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
                if ($estado === 'pendiente_revision') {
                    error_log("RequisicionService::crearRequisicion() - Iniciando flujo de validación v3.0 para requisición pendiente de revisión: " . $ordenId);
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
                    error_log("RequisicionService::crearRequisicion() - Requisición guardada como borrador ($estado), no se inicia flujo de validación");
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
                    'requisicion_id' => $ordenId,
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
            $montoTotal = $this->calcularTotalItems(DetalleItem::porRequisicion($ordenId));
            $distribucionConMontos = $this->calcularMontosDistribucion($distribucion, $montoTotal);

            // Guardar cada línea de distribución
            foreach ($distribucionConMontos as $dist) {
                DistribucionGasto::create([
                    'requisicion_id' => $ordenId,
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
                    'requisicion_id' => $ordenId,
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
        error_log("=== DEBUG RequisicionService::guardarArchivos ===");
        error_log("Orden ID: " . $ordenId);
        error_log("Archivos recibidos: " . json_encode($archivos));
        
        try {
            $resultados = [];
            $errores = [];

            // Reorganizar estructura de $_FILES si es necesario
            // $_FILES con multiple tiene estructura: name => [0 => 'file1', 1 => 'file2'], etc.
            $archivosNormalizados = [];
            
            if (isset($archivos['name']) && is_array($archivos['name'])) {
                // Estructura de múltiples archivos: reorganizar
                error_log("Detectada estructura de múltiples archivos, reorganizando...");
                $count = count($archivos['name']);
                error_log("Cantidad de archivos: " . $count);
                
                for ($i = 0; $i < $count; $i++) {
                    if (!empty($archivos['name'][$i])) {
                        $archivosNormalizados[] = [
                            'name' => $archivos['name'][$i],
                            'type' => $archivos['type'][$i] ?? '',
                            'tmp_name' => $archivos['tmp_name'][$i] ?? '',
                            'error' => $archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                            'size' => $archivos['size'][$i] ?? 0,
                        ];
                        error_log("Archivo $i normalizado: " . $archivos['name'][$i]);
                    }
                }
            } elseif (isset($archivos['name']) && !is_array($archivos['name'])) {
                // Archivo único
                error_log("Detectado archivo único");
                if (!empty($archivos['name'])) {
                    $archivosNormalizados[] = $archivos;
                }
            } else {
                // Ya está en formato normalizado (array de archivos)
                error_log("Estructura ya normalizada");
                $archivosNormalizados = $archivos;
            }

            error_log("Total archivos a procesar: " . count($archivosNormalizados));

            foreach ($archivosNormalizados as $index => $archivo) {
                error_log("Procesando archivo $index: " . ($archivo['name'] ?? 'SIN NOMBRE'));
                
                if (empty($archivo['name'])) {
                    error_log("Archivo $index: nombre vacío, saltando");
                    continue;
                }
                
                if ($archivo['error'] !== UPLOAD_ERR_OK) {
                    $errorMsg = $this->getUploadErrorMessage($archivo['error']);
                    error_log("Archivo $index: error de upload - " . $errorMsg);
                    $errores[] = "Archivo '{$archivo['name']}': $errorMsg";
                    
                    // Registrar en historial
                    HistorialRequisicion::registrar(
                        $ordenId,
                        'error_archivo',
                        "Error subiendo archivo '{$archivo['name']}': $errorMsg",
                        null
                    );
                    continue;
                }
                
                error_log("Archivo $index: llamando subirArchivo para " . $archivo['name']);
                $result = ArchivoAdjunto::subirArchivo($archivo, $ordenId);
                
                // $result puede ser un objeto ArchivoAdjunto o false
                $archivoId = null;
                if ($result instanceof ArchivoAdjunto) {
                    $archivoId = $result->id;
                } elseif (is_object($result) && isset($result->id)) {
                    $archivoId = $result->id;
                } elseif (is_numeric($result)) {
                    $archivoId = $result;
                }
                
                error_log("Archivo $index: resultado = " . ($archivoId ? "ID=$archivoId" : "FALSE"));
                
                if ($archivoId) {
                    $resultados[] = $archivoId;
                    
                    // Registrar éxito en historial
                    HistorialRequisicion::registrar(
                        $ordenId,
                        'archivo_subido',
                        "Archivo '{$archivo['name']}' subido correctamente (ID: $archivoId)",
                        null
                    );
                } else {
                    $errores[] = "No se pudo guardar el archivo '{$archivo['name']}'";
                    
                    // Registrar error en historial
                    HistorialRequisicion::registrar(
                        $ordenId,
                        'error_archivo',
                        "No se pudo guardar el archivo '{$archivo['name']}'",
                        null
                    );
                }
            }

            $success = count($errores) === 0 || count($resultados) > 0;
            
            return [
                'success' => $success,
                'message' => count($resultados) . ' archivo(s) guardado(s)',
                'count' => count($resultados),
                'errores' => $errores
            ];
        } catch (\Exception $e) {
            error_log("Error guardando archivos: " . $e->getMessage());
            
            // Registrar excepción en historial
            HistorialRequisicion::registrar(
                $ordenId,
                'error_archivo',
                "Excepción al guardar archivos: " . $e->getMessage(),
                null
            );
            
            return [
                'success' => false,
                'error' => 'Error al guardar archivos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Convierte código de error de upload a mensaje legible
     */
    private function getUploadErrorMessage($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida',
        ];
        
        return $errors[$errorCode] ?? "Error desconocido (código: $errorCode)";
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
            if (!$this->puedeEditar($archivo['requisicion_id'], $usuarioId)) {
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
                    'error' => 'No tienes permisos para editar esta requisición. Solo se puede editar cuando está en estado "rechazado" y eres el creador.',
                    'code' => 'UNAUTHORIZED'
                ];
            }

            // Procesar datos del formulario
            $datosProcesados = $this->procesarDatosFormulario($data);
            
            // Actualizar orden de compra
            $resultado = Requisicion::updateById($ordenId, [
                'proveedor_nombre' => $datosProcesados['nombre_razon_social'] ?? null,
                'justificacion' => $datosProcesados['justificacion'] ?? null,
                'observaciones' => $datosProcesados['observaciones'] ?? null,
                'causal_compra' => $datosProcesados['causal_compra'] ?? null,
                'moneda' => $datosProcesados['moneda'] ?? null,
                'forma_pago' => $datosProcesados['forma_pago'] ?? null,
                'anticipo' => $datosProcesados['anticipo'] ?? null,
                'unidad_requirente' => $datosProcesados['unidad_requirente'] ?? null,
                'fecha_solicitud' => $datosProcesados['fecha'] ?? null,
                'monto_total' => $datosProcesados['monto_total'] ?? null
            ]);

            if ($resultado) {
                // Actualizar items
                if (!empty($datosProcesados['items'])) {
                    DetalleItem::actualizarMultiples($ordenId, $datosProcesados['items']);
                }
                
                // Actualizar distribución
                if (!empty($datosProcesados['distribucion'])) {
                    DistribucionGasto::actualizarMultiples($ordenId, $datosProcesados['distribucion']);
                }
                
                // Registrar en historial
                HistorialRequisicion::registrarEdicion($ordenId, $usuarioId, array_keys($data));

                // Reiniciar flujo de autorización: enviar de nuevo a aprobación
                $this->reiniciarFlujoAutorizacion($ordenId, $datosProcesados);

                return [
                    'success' => true,
                    'message' => 'Requisición editada correctamente y enviada de nuevo a aprobación'
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
     * Solo permite editar cuando:
     * 1. El usuario es el creador de la requisición
     * 2. La requisición está en estado "rechazado"
     * 
     * @param int $ordenId ID de la orden
     * @param int $usuarioId ID del usuario
     * @return bool
     */
    public function puedeEditar($ordenId, $usuarioId)
    {
        $orden = Requisicion::find($ordenId);
        if (!$orden) {
            return false;
        }

        // Solo el creador puede editar
        if ($orden->usuario_id != $usuarioId) {
            return false;
        }

        // Solo puede editar si está en estado "rechazado"
        $estadoReal = $orden->getEstadoReal();
        return $estadoReal === 'rechazado';
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

            $conn = Requisicion::getConnection();
            $conn->beginTransaction();

            try {
                // Eliminar en cascada
                DetalleItem::deleteByOrden($ordenId);
                DistribucionGasto::deleteByOrden($ordenId);
                Factura::deleteByOrden($ordenId);
                // Los archivos se eliminan físicamente
                $archivos = ArchivoAdjunto::porRequisicion($ordenId);
                foreach ($archivos as $archivo) {
                    ArchivoAdjunto::eliminarArchivo($archivo['id']);
                }

                Requisicion::delete($ordenId);

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
        $orden = Requisicion::find($ordenId);
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
        return Requisicion::find($ordenId);
    }

    /**
     * Obtiene una requisición completa con relaciones
     * 
     * @param int $ordenId ID de la orden
     * @return array|null Requisición completa
     */
    public function getRequisicionCompleta($ordenId)
    {
        error_log("getRequisicionCompleta: Buscando requisición con ID: $ordenId");
        $orden = Requisicion::find($ordenId);
        if (!$orden) {
            error_log("getRequisicionCompleta: Requisición NO encontrada con ID: $ordenId");
            return null;
        }
        error_log("getRequisicionCompleta: Requisición encontrada - ID: " . ($orden->id ?? 'N/A'));

        // Obtener información del usuario creador
        $usuarioCreador = Usuario::find($orden->usuario_id);
        if ($usuarioCreador) {
            $orden->usuario_nombre = $usuarioCreador->azure_display_name ?? $usuarioCreador->nombre ?? 'Usuario desconocido';
            $orden->usuario_email = $usuarioCreador->azure_email ?? $usuarioCreador->email ?? '';
        } else {
            $orden->usuario_nombre = 'Usuario no encontrado';
            $orden->usuario_email = '';
        }

        // Obtener flujo de autorización
        $flujo = $orden->autorizacionFlujo();
        
        // Obtener distribuciones de gasto (v3.0)
        $distribuciones = DistribucionGasto::porRequisicion($ordenId);
        
        // Obtener autorizaciones del flujo (v3.0)
        $autorizaciones = [];
        if ($flujo) {
            // Buscar autorizaciones en la tabla unificada 'autorizaciones'
            $pdo = \App\Models\Model::getConnection();
            $stmt = $pdo->prepare("
                SELECT a.*, cc.nombre as centro_nombre, ct.descripcion as cuenta_nombre
                FROM autorizaciones a
                LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
                LEFT JOIN cuenta_contable ct ON a.cuenta_contable_id = ct.id
                WHERE a.requisicion_id = ?
                ORDER BY a.tipo, a.id
            ");
            $stmt->execute([$ordenId]);
            $autorizaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Obtener historial (con manejo de errores)
        $historial = [];
        try {
            $historial = HistorialRequisicion::porRequisicion($ordenId);
        } catch (\Exception $e) {
            error_log("Error obteniendo historial en getRequisicionCompleta: " . $e->getMessage());
            // Continuar sin historial en lugar de fallar toda la función
        }

        // Obtener items de la requisición desde detalle_items
        $items = [];
        try {
            $pdo = \App\Models\Model::getConnection();
            $stmt = $pdo->prepare("
                SELECT id, requisicion_id, cantidad, descripcion, precio_unitario, total
                FROM detalle_items 
                WHERE requisicion_id = ? 
                ORDER BY id
            ");
            $stmt->execute([$ordenId]);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            error_log("Items cargados para requisición $ordenId: " . count($items));
        } catch (\Exception $e) {
            error_log("Error obteniendo items en getRequisicionCompleta: " . $e->getMessage());
            // Continuar sin items en lugar de fallar toda la función
        }

        // Obtener archivos adjuntos
        $archivos = [];
        try {
            $pdo = \App\Models\Model::getConnection();
            $stmt = $pdo->prepare("
                SELECT id, requisicion_id, nombre_original, nombre_archivo, ruta_archivo, tipo_mime, tamano_bytes, created_at
                FROM archivos_adjuntos 
                WHERE requisicion_id = ? 
                ORDER BY id
            ");
            $stmt->execute([$ordenId]);
            $archivos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            error_log("Archivos cargados para requisición $ordenId: " . count($archivos));
        } catch (\Exception $e) {
            error_log("Error obteniendo archivos en getRequisicionCompleta: " . $e->getMessage());
        }

        return [
            'orden' => $orden,
            'distribuciones' => $distribuciones, // v3.0: distribuciones (plural)
            'autorizaciones' => $autorizaciones, // v3.0: autorizaciones del flujo
            'flujo' => $flujo,
            'historial' => $historial,
            'items' => $items, // Items cargados desde detalle_items
            'facturas' => [], // Tabla eliminada
            'archivos' => $archivos, // Archivos adjuntos de la requisición
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
        return Requisicion::all();
    }

    /**
     * Filtra requisiciones por estado
     * 
     * @param string $estado Estado del flujo
     * @return array Requisiciones filtradas
     */
    public function filtrarPorEstado($estado)
    {
        return Requisicion::porEstado($estado);
    }

    /**
     * Busca requisiciones por término
     * 
     * @param string $termino Término de búsqueda
     * @return array Resultados
     */
    public function buscar($termino)
    {
        return Requisicion::buscar($termino);
    }

    /**
     * Calcula el monto total de una requisición
     * 
     * @param int $ordenId ID de la orden
     * @return float Monto total
     */
    public function calcularMontoTotal($ordenId)
    {
        $items = DetalleItem::porRequisicion($ordenId);
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
        return Requisicion::update($ordenId, ['monto_total' => $montoTotal]);
    }

    /**
     * Obtiene el estado de una requisición
     * 
     * @param int $ordenId ID de la orden
     * @return string|null Estado
     */
    public function obtenerEstado($ordenId)
    {
        $orden = Requisicion::find($ordenId);
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
        return Requisicion::getEstadisticasUsuario($usuarioId);
    }

    /**
     * Obtiene estadísticas generales
     * 
     * @return array Estadísticas
     */
    public function getEstadisticasGenerales()
    {
        return Requisicion::getEstadisticasGenerales();
    }

    // ========================================================================
    // MÉTODOS PARA EL NUEVO FLUJO DE CÁLCULOS
    // ========================================================================

    /**
     * Aproxima un porcentaje a 100% si está cerca (entre 99.9% y 100.1%)
     * 
     * @param float $porcentaje Porcentaje a aproximar
     * @return float Porcentaje aproximado
     */
    private function aproximarPorcentaje($porcentaje)
    {
        // Si el porcentaje está entre 99.9% y 100.1%, aproximar a 100%
        if ($porcentaje >= 99.9 && $porcentaje <= 100.1) {
            return 100.0;
        }
        return $porcentaje;
    }

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
        
        // Mapear campos del formulario
        // razon_seleccion -> justificacion
        if (isset($data['razon_seleccion'])) {
            $datosProcesados['justificacion'] = $data['razon_seleccion'];
        }
        // datos_proveedor -> observaciones (para especificaciones técnicas)
        if (isset($data['datos_proveedor'])) {
            $datosProcesados['observaciones'] = $data['datos_proveedor'];
        }
        
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
            // Primero calcular el total de porcentajes para verificar si necesita ajuste
            $totalPorcentaje = 0;
            foreach ($data['distribucion'] as $dist) {
                $totalPorcentaje += floatval($dist['porcentaje'] ?? 0);
            }
            
            // Si el total está entre 99.9% y 100.1%, ajustar proporcionalmente a 100%
            $necesitaAjuste = ($totalPorcentaje >= 99.9 && $totalPorcentaje <= 100.1 && $totalPorcentaje != 100.0);
            $factorAjuste = $necesitaAjuste && $totalPorcentaje > 0 ? (100.0 / $totalPorcentaje) : 1.0;
            
            foreach ($data['distribucion'] as $dist) {
                $porcentaje = floatval($dist['porcentaje'] ?? 0);
                
                // Aplicar ajuste proporcional si el total está cerca de 100%
                if ($necesitaAjuste && $porcentaje > 0) {
                    $porcentaje = $porcentaje * $factorAjuste;
                }
                
                $cantidad = floatval($dist['cantidad'] ?? 0);
                
                // Si la cantidad es 0 o negativa, calcular basado en porcentaje
                if ($cantidad <= 0 && $porcentaje > 0) {
                    $cantidad = ($porcentaje / 100) * $montoTotal;
                }
                
                // Limpiar IDs vacíos
                $ubicacionId = (!empty($dist['ubicacion_id']) && $dist['ubicacion_id'] !== '') ? $dist['ubicacion_id'] : null;
                $unidadNegocioId = (!empty($dist['unidad_negocio_id']) && $dist['unidad_negocio_id'] !== '') ? $dist['unidad_negocio_id'] : null;
                
                // Limpiar y validar IDs de cuenta y centro
                $cuentaContableId = (!empty($dist['cuenta_contable_id']) && $dist['cuenta_contable_id'] !== '' && $dist['cuenta_contable_id'] !== '0') ? $dist['cuenta_contable_id'] : null;
                $centroCostoId = (!empty($dist['centro_costo_id']) && $dist['centro_costo_id'] !== '' && $dist['centro_costo_id'] !== '0') ? $dist['centro_costo_id'] : null;
                
                // Procesar número de factura - asegurar que sea un entero válido (1, 2, o 3)
                $facturaTipo = 1; // Default a factura 1
                if (!empty($dist['factura']) && is_numeric($dist['factura'])) {
                    $facturaTipo = intval($dist['factura']);
                    // Asegurar que esté en el rango válido (1-3)
                    if ($facturaTipo < 1 || $facturaTipo > 3) {
                        $facturaTipo = 1;
                    }
                }
                
                $distribucion[] = [
                    'cuenta_contable_id' => $cuentaContableId,
                    'centro_costo_id' => $centroCostoId,
                    'ubicacion_id' => $ubicacionId,
                    'unidad_negocio_id' => $unidadNegocioId,
                    'porcentaje' => $porcentaje,
                    'cantidad' => $cantidad,
                    'factura' => $facturaTipo
                ];
            }
        }
        
        $datosProcesados['distribucion'] = $distribucion;
        
        // Debug: mostrar las facturas asignadas a cada distribución
        foreach ($distribucion as $index => $dist) {
            error_log("Distribución $index - Centro: {$dist['centro_costo_id']}, Factura: {$dist['factura']}, Porcentaje: {$dist['porcentaje']}%");
        }
        
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
                    'requisicion_id' => $ordenId,
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
                // Validar que tenga centro de costo y cuenta contable
                if (empty($dist['centro_costo_id']) || empty($dist['cuenta_contable_id'])) {
                    throw new \Exception("Todas las distribuciones deben tener centro de costo y cuenta contable asignados");
                }
                
                // Limpiar valores vacíos y convertir a null
                $ubicacionId = (!empty($dist['ubicacion_id']) && $dist['ubicacion_id'] !== '') ? $dist['ubicacion_id'] : null;
                $unidadNegocioId = (!empty($dist['unidad_negocio_id']) && $dist['unidad_negocio_id'] !== '') ? $dist['unidad_negocio_id'] : null;
                
                $distribucionGasto = new DistribucionGasto([
                    'requisicion_id' => $ordenId,
                    'centro_costo_id' => $dist['centro_costo_id'],
                    'cuenta_contable_id' => $dist['cuenta_contable_id'],
                    'ubicacion_id' => $ubicacionId,
                    'unidad_negocio_id' => $unidadNegocioId,
                    'porcentaje' => $dist['porcentaje'],
                    'cantidad' => $dist['cantidad'],
                    'factura' => $dist['factura'] ?? 1
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
            error_log("=== generarFacturasAutomaticas ===");
            error_log("ordenId: $ordenId, montoTotal: $montoTotal, formaPago: $formaPago");
            error_log("Distribución recibida: " . json_encode($distribucion));
            
            // Agrupar por tipo de factura
            $facturas = [
                1 => ['porcentaje' => 0, 'monto' => 0],
                2 => ['porcentaje' => 0, 'monto' => 0],
                3 => ['porcentaje' => 0, 'monto' => 0]
            ];

            // Sumar las cantidades por factura
            foreach ($distribucion as $index => $dist) {
                $facturaTipo = intval($dist['factura'] ?? 1);
                $cantidad = floatval($dist['cantidad'] ?? 0);
                error_log("Dist $index: factura=$facturaTipo, cantidad=$cantidad");
                
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
            
            error_log("Facturas calculadas: " . json_encode($facturas));

            // Guardar facturas que tengan monto > 0
            // Nota: La tabla facturas en bd_prueba tiene estructura diferente
            // Columnas disponibles: requisicion_id, numero_factura, fecha_factura, monto_factura, proveedor_nombre, archivo_factura, estado
            foreach ($facturas as $numeroTipo => $datos) {
                if ($datos['monto'] > 0) {
                    $factura = new Factura([
                        'requisicion_id' => $ordenId,
                        'numero_factura' => 'Factura ' . $numeroTipo . ' (' . round($datos['porcentaje'], 2) . '%)',
                        'fecha_factura' => date('Y-m-d'),
                        'monto_factura' => round($datos['monto'], 2),
                        'proveedor_nombre' => 'Pendiente',
                        'estado' => 'pendiente'
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

    /**
     * Reinicia el flujo de autorización después de editar una requisición
     * Cambia el estado de "rechazado" a "pendiente_revision" para que vuelva a empezar el proceso
     * 
     * @param int $ordenId ID de la orden
     * @param array $datosProcesados Datos procesados de la requisición
     * @return void
     */
    private function reiniciarFlujoAutorizacion($ordenId, $datosProcesados)
    {
        try {
            // 1. Buscar el flujo de autorización existente
            $flujo = AutorizacionFlujo::porRequisicion($ordenId);
            
            if ($flujo) {
                // Obtener el ID del flujo (puede ser objeto o array)
                $flujoId = is_object($flujo) ? $flujo->id : ($flujo['id'] ?? null);
                
                if ($flujoId) {
                    // 2. Actualizar el flujo existente para reiniciarlo
                    AutorizacionFlujo::updateById($flujoId, [
                        'estado' => AutorizacionFlujo::ESTADO_PENDIENTE_REVISION,
                        'fecha_completado' => null,
                        'requiere_autorizacion_especial_pago' => $this->requiereAutorizacionPago($datosProcesados) ? 1 : 0,
                        'requiere_autorizacion_especial_cuenta' => $this->requiereAutorizacionCuenta($datosProcesados) ? 1 : 0,
                        'monto_total' => $datosProcesados['monto_total'] ?? 0
                    ]);
                    
                    error_log("Flujo de autorización reiniciado para requisición $ordenId");
                } else {
                    error_log("No se pudo obtener el ID del flujo para requisición $ordenId");
                }
            } else {
                // 3. Si no existe flujo, crear uno nuevo usando FlujoValidacionService
                error_log("Creando nuevo flujo de autorización para requisición editada $ordenId");
                $flujoService = new \App\Services\FlujoValidacionService();
                $resultFlujo = $flujoService->iniciarFlujo($ordenId);
                if (!$resultFlujo['success']) {
                    error_log("Error iniciando flujo de validación: " . ($resultFlujo['error'] ?? 'Error desconocido'));
                }
            }
            
            // 4. Registrar en el historial que se reinició el flujo
            HistorialRequisicion::create([
                'requisicion_id' => $ordenId,
                'accion' => 'flujo_reiniciado',
                'usuario_id' => $_SESSION['user_id'] ?? null,
                'comentarios' => 'Flujo de autorización reiniciado después de edición',
                'fecha_cambio' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            error_log("Error reiniciando flujo de autorización: " . $e->getMessage());
            // No lanzamos la excepción para que no falle la edición si hay problemas con el flujo
        }
    }

    /**
     * Verifica si la requisición requiere autorización especial por método de pago
     * 
     * @param array $datos Datos de la requisición
     * @return bool
     */
    private function requiereAutorizacionPago($datos)
    {
        $formaPago = $datos['forma_pago'] ?? '';
        // Verificar si el método de pago requiere autorización especial
        return in_array($formaPago, ['tarjeta_credito_lic_milton', 'tarjeta_credito']);
    }

    /**
     * Verifica si la requisición requiere autorización especial por cuenta contable
     * 
     * @param array $datos Datos de la requisición
     * @return bool
     */
    private function requiereAutorizacionCuenta($datos)
    {
        if (empty($datos['distribucion'])) {
            return false;
        }
        
        // Verificar si alguna distribución usa cuenta 336 u otra que requiera autorización especial
        foreach ($datos['distribucion'] as $dist) {
            $cuentaId = $dist['cuenta_contable_id'] ?? 0;
            if ($cuentaId == 336) { // Cuenta que requiere autorización especial
                return true;
            }
        }
        
        return false;
    }
}
