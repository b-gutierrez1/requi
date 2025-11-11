<?php
/**
 * Script para debuggear los datos del formulario de requisiciones
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== DEBUG DE DATOS DEL FORMULARIO ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Simular datos como los que se ven en la imagen
$formData = [
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
    
    // Items como aparecen en el formulario HTML
    'items' => [
        0 => [
            'descripcion' => 'sdfgh',
            'cantidad' => '41',
            'precio_unitario' => '57',
            'total' => '2337.00'
        ]
    ],
    
    // Distribución como aparece en el formulario HTML
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

echo "1. Datos originales del formulario:\n";
echo "   Nombre/Razón Social: '{$formData['nombre_razon_social']}'\n";
echo "   Items count: " . count($formData['items']) . "\n";
echo "   Distribución count: " . count($formData['distribucion']) . "\n\n";

echo "2. Detalle de items:\n";
foreach ($formData['items'] as $i => $item) {
    echo "   Item $i:\n";
    echo "     - Descripción: '{$item['descripcion']}'\n";
    echo "     - Cantidad: {$item['cantidad']}\n";
    echo "     - Precio: {$item['precio_unitario']}\n";
    echo "     - Total: {$item['total']}\n";
}

echo "\n3. Detalle de distribución:\n";
foreach ($formData['distribucion'] as $i => $dist) {
    echo "   Distribución $i:\n";
    echo "     - Centro Costo: {$dist['centro_costo_id']}\n";
    echo "     - Porcentaje: {$dist['porcentaje']}\n";
    echo "     - Cantidad: {$dist['cantidad']}\n";
    echo "     - Factura: {$dist['factura']}\n";
}

echo "\n4. Procesando datos con RequisicionService...\n";

try {
    $service = new \App\Services\RequisicionService(new \App\Services\AutorizacionService());
    
    // Usar reflexión para acceder al método privado
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('procesarDatosFormulario');
    $method->setAccessible(true);
    
    $datosProcesados = $method->invoke($service, $formData);
    
    echo "   ✅ Procesamiento exitoso\n";
    echo "   Nombre procesado: '{$datosProcesados['nombre_razon_social']}'\n";
    echo "   Items procesados: " . count($datosProcesados['items']) . "\n";
    echo "   Distribución procesada: " . count($datosProcesados['distribucion']) . "\n";
    echo "   Monto total: Q " . number_format($datosProcesados['monto_total'], 2) . "\n";
    
    echo "\n5. Validando datos procesados...\n";
    
    $validationMethod = $reflection->getMethod('validarDatosRequisicion');
    $validationMethod->setAccessible(true);
    
    $validacion = $validationMethod->invoke($service, $datosProcesados);
    
    if ($validacion['success']) {
        echo "   ✅ Validación exitosa\n";
    } else {
        echo "   ❌ Error de validación: {$validacion['error']}\n";
        echo "   Código: {$validacion['code']}\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n6. Verificando estructura de datos esperada...\n";

// Verificar qué está esperando la validación
$checks = [
    'nombre_razon_social' => !empty($formData['nombre_razon_social']),
    'items_array' => !empty($formData['items']) && is_array($formData['items']),
    'distribucion_array' => !empty($formData['distribucion']) && is_array($formData['distribucion']),
    'items_not_empty' => !empty($formData['items']),
    'distribucion_not_empty' => !empty($formData['distribucion'])
];

foreach ($checks as $check => $result) {
    echo "   $check: " . ($result ? '✅ PASS' : '❌ FAIL') . "\n";
}

echo "\n=== DEBUG COMPLETADO ===\n";






