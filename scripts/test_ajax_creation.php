<?php
/**
 * Script para probar la creación de requisiciones vía AJAX
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== PRUEBA DE CREACIÓN VÍA AJAX ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Simular datos de requisición
    $postData = [
        'nombre_razon_social' => 'Proveedor Test AJAX',
        'fecha' => date('Y-m-d'),
        'causal_compra' => 'Prueba AJAX',
        'moneda' => 'GTQ',
        'forma_pago' => 'contado',
        'anticipo' => '0',
        'unidad_requirente' => '1',
        'justificacion' => 'Prueba de creación vía AJAX',
        'datos_proveedor' => 'Datos del proveedor de prueba',
        'razon_seleccion' => 'Seleccionado para prueba AJAX',
        
        // Items
        'items' => [
            [
                'descripcion' => 'Item AJAX Test',
                'cantidad' => '1',
                'precio_unitario' => '150.00',
                'total' => '150.00'
            ]
        ],
        
        // Distribución
        'distribucion' => [
            [
                'centro_costo_id' => '1',
                'cuenta_contable_id' => '1',
                'porcentaje' => '100.00',
                'cantidad' => '150.00',
                'factura' => 'Factura 1'
            ]
        ],
        
        'monto_total' => '150.00',
        '_token' => \App\Middlewares\CsrfMiddleware::getToken()
    ];
    
    echo "1. Preparando datos de prueba...\n";
    echo "   Proveedor: {$postData['nombre_razon_social']}\n";
    echo "   Monto: Q {$postData['monto_total']}\n";
    echo "   Token CSRF: " . substr($postData['_token'], 0, 16) . "...\n\n";
    
    echo "2. Simulando petición AJAX...\n";
    
    // Simular entorno de petición AJAX
    $_POST = $postData;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    $_SERVER['REQUEST_URI'] = '/requisiciones';
    
    // Simular sesión de usuario
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['user_id'] = 107;
    $_SESSION['user_email'] = 'test@example.com';
    $_SESSION['user_name'] = 'Usuario Test';
    $_SESSION['is_admin'] = 1;
    $_SESSION['is_revisor'] = 1;
    
    echo "   Usuario simulado: ID {$_SESSION['user_id']}\n";
    echo "   Método: {$_SERVER['REQUEST_METHOD']}\n";
    echo "   AJAX: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'NO') . "\n\n";
    
    echo "3. Ejecutando controlador...\n";
    
    // Capturar salida
    ob_start();
    
    try {
        $controller = new \App\Controllers\RequisicionController();
        $controller->store();
    } catch (Exception $e) {
        $output = ob_get_clean();
        echo "   ❌ Error en controlador: " . $e->getMessage() . "\n";
        if ($output) {
            echo "   Output capturado: $output\n";
        }
        throw $e;
    }
    
    $output = ob_get_clean();
    
    echo "4. Analizando respuesta...\n";
    
    if ($output) {
        echo "   Respuesta recibida: " . strlen($output) . " caracteres\n";
        
        // Intentar decodificar JSON
        $response = json_decode($output, true);
        
        if ($response) {
            echo "   ✅ Respuesta JSON válida\n";
            echo "   Success: " . ($response['success'] ? 'SÍ' : 'NO') . "\n";
            
            if ($response['success']) {
                echo "   ✅ Requisición creada exitosamente\n";
                echo "   Orden ID: {$response['orden_id']}\n";
                echo "   Mensaje: {$response['message']}\n";
                echo "   URL Redirección: {$response['redirect_url']}\n";
            } else {
                echo "   ❌ Error en creación: {$response['error']}\n";
            }
        } else {
            echo "   ❌ Respuesta no es JSON válido\n";
            echo "   Contenido: " . substr($output, 0, 200) . "...\n";
        }
    } else {
        echo "   ❌ No se recibió respuesta\n";
    }
    
    echo "\n=== PRUEBA COMPLETADA ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}






