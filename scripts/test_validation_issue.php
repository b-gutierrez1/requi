<?php
/**
 * Script para probar el problema de validación específico
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== PRUEBA DEL PROBLEMA DE VALIDACIÓN ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Simular datos problemáticos (campos vacíos o mal formateados)
$testCases = [
    'Caso 1: Datos completos' => [
        'nombre_razon_social' => 'sdfgh',
        'fecha' => date('Y-m-d'),
        'causal_compra' => 'otro',
        'moneda' => 'GTQ',
        'forma_pago' => 'contado',
        'items' => [
            ['descripcion' => 'sdfgh', 'cantidad' => '41', 'precio_unitario' => '57']
        ],
        'distribucion' => [
            ['centro_costo_id' => '2', 'porcentaje' => '50', 'cantidad' => '1168.50'],
            ['centro_costo_id' => '1', 'porcentaje' => '50', 'cantidad' => '1168.50']
        ]
    ],
    
    'Caso 2: Nombre vacío' => [
        'nombre_razon_social' => '',
        'fecha' => date('Y-m-d'),
        'causal_compra' => 'otro',
        'moneda' => 'GTQ',
        'forma_pago' => 'contado',
        'items' => [
            ['descripcion' => 'sdfgh', 'cantidad' => '41', 'precio_unitario' => '57']
        ],
        'distribucion' => [
            ['centro_costo_id' => '2', 'porcentaje' => '100']
        ]
    ],
    
    'Caso 3: Items vacío' => [
        'nombre_razon_social' => 'Proveedor Test',
        'fecha' => date('Y-m-d'),
        'causal_compra' => 'otro',
        'moneda' => 'GTQ',
        'forma_pago' => 'contado',
        'items' => [],
        'distribucion' => [
            ['centro_costo_id' => '2', 'porcentaje' => '100']
        ]
    ],
    
    'Caso 4: Distribución vacía' => [
        'nombre_razon_social' => 'Proveedor Test',
        'fecha' => date('Y-m-d'),
        'causal_compra' => 'otro',
        'moneda' => 'GTQ',
        'forma_pago' => 'contado',
        'items' => [
            ['descripcion' => 'Item test', 'cantidad' => '1', 'precio_unitario' => '100']
        ],
        'distribucion' => []
    ],
    
    'Caso 5: Items sin descripción' => [
        'nombre_razon_social' => 'Proveedor Test',
        'fecha' => date('Y-m-d'),
        'causal_compra' => 'otro',
        'moneda' => 'GTQ',
        'forma_pago' => 'contado',
        'items' => [
            ['descripcion' => '', 'cantidad' => '1', 'precio_unitario' => '100']
        ],
        'distribucion' => [
            ['centro_costo_id' => '2', 'porcentaje' => '100']
        ]
    ]
];

try {
    $service = new \App\Services\RequisicionService(new \App\Services\AutorizacionService());
    
    // Usar reflexión para acceder a métodos privados
    $reflection = new ReflectionClass($service);
    $procesarMethod = $reflection->getMethod('procesarDatosFormulario');
    $procesarMethod->setAccessible(true);
    $validarMethod = $reflection->getMethod('validarDatosRequisicion');
    $validarMethod->setAccessible(true);
    
    foreach ($testCases as $caseName => $data) {
        echo "--- $caseName ---\n";
        
        try {
            // 1. Procesar datos
            $datosProcesados = $procesarMethod->invoke($service, $data);
            echo "  ✅ Procesamiento exitoso\n";
            
            // 2. Validar datos procesados
            $validacion = $validarMethod->invoke($service, $datosProcesados);
            
            if ($validacion['success']) {
                echo "  ✅ Validación exitosa\n";
            } else {
                echo "  ❌ Error de validación: {$validacion['error']}\n";
                echo "  Código: {$validacion['code']}\n";
            }
            
            // 3. Mostrar detalles relevantes
            echo "  Nombre: '" . ($datosProcesados['nombre_razon_social'] ?? 'VACÍO') . "'\n";
            echo "  Items: " . count($datosProcesados['items'] ?? []) . "\n";
            echo "  Distribución: " . count($datosProcesados['distribucion'] ?? []) . "\n";
            
        } catch (Exception $e) {
            echo "  ❌ Error en procesamiento: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "=== SIMULACIÓN DE DATOS COMO LLEGAN DEL FORMULARIO ===\n";
    
    // Simular cómo llegan los datos del formulario HTML
    $formDataSimulation = [
        'nombre_razon_social' => 'sdfgh',
        'fecha' => date('Y-m-d'),
        'causal_compra' => 'otro',
        'moneda' => 'GTQ',
        'forma_pago' => 'contado',
        'anticipo' => '0',
        'unidad_requirente' => '1',
        'justificacion' => '',
        'datos_proveedor' => '',
        'razon_seleccion' => '',
        
        // Como llegan del HTML (con índices numéricos)
        'items' => [
            0 => [
                'descripcion' => 'sdfgh',
                'cantidad' => '41',
                'precio_unitario' => '57',
                'total' => '2337.00'
            ]
        ],
        
        'distribucion' => [
            0 => [
                'centro_costo_id' => '2',
                'cuenta_contable_id' => '1',
                'porcentaje' => '50',
                'cantidad' => '1168.50',
                'factura' => 'Factura 1'
            ],
            1 => [
                'centro_costo_id' => '1',
                'cuenta_contable_id' => '1',
                'porcentaje' => '50',
                'cantidad' => '1168.50',
                'factura' => 'Factura 1'
            ]
        ],
        
        '_token' => 'dummy_token'
    ];
    
    echo "Simulando datos exactos del formulario...\n";
    
    $datosProcesados = $procesarMethod->invoke($service, $formDataSimulation);
    $validacion = $validarMethod->invoke($service, $datosProcesados);
    
    if ($validacion['success']) {
        echo "✅ Los datos del formulario son válidos\n";
    } else {
        echo "❌ Error con datos del formulario: {$validacion['error']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== PRUEBA COMPLETADA ===\n";






