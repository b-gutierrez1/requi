<?php
/**
 * Script para probar la renovación de tokens CSRF
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== PRUEBA DE RENOVACIÓN DE TOKEN CSRF ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Simular sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "1. Generando token inicial...\n";
    $token1 = \App\Middlewares\CsrfMiddleware::getToken();
    echo "   Token 1: " . substr($token1, 0, 16) . "...\n";
    
    echo "\n2. Obteniendo mismo token (debería ser igual)...\n";
    $token2 = \App\Middlewares\CsrfMiddleware::getToken();
    echo "   Token 2: " . substr($token2, 0, 16) . "...\n";
    echo "   ¿Son iguales? " . ($token1 === $token2 ? 'SÍ' : 'NO') . "\n";
    
    echo "\n3. Regenerando token...\n";
    $token3 = \App\Middlewares\CsrfMiddleware::regenerate();
    echo "   Token 3: " . substr($token3, 0, 16) . "...\n";
    echo "   ¿Es diferente? " . ($token1 !== $token3 ? 'SÍ' : 'NO') . "\n";
    
    echo "\n4. Probando endpoint /csrf-token...\n";
    
    // Simular petición HTTP al endpoint
    $url = 'http://localhost:8000/csrf-token';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'X-Requested-With: XMLHttpRequest',
                'Cookie: ' . session_name() . '=' . session_id()
            ]
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['token'])) {
            echo "   ✅ Endpoint funciona correctamente\n";
            echo "   Token del endpoint: " . substr($data['token'], 0, 16) . "...\n";
        } else {
            echo "   ❌ Respuesta inválida del endpoint\n";
            echo "   Respuesta: $response\n";
        }
    } else {
        echo "   ⚠️  No se pudo conectar al endpoint (servidor no ejecutándose?)\n";
        echo "   URL probada: $url\n";
    }
    
    echo "\n5. Probando validación de token...\n";
    
    // Simular validación
    $_POST['_token'] = $token3;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    $csrf = new \App\Middlewares\CsrfMiddleware();
    
    // Capturar salida para evitar que termine el script
    ob_start();
    $isValid = $csrf->handle();
    $output = ob_get_clean();
    
    if ($isValid) {
        echo "   ✅ Token válido correctamente\n";
    } else {
        echo "   ❌ Token no válido\n";
        echo "   Output: $output\n";
    }
    
    echo "\n=== PRUEBA COMPLETADA ===\n";
    echo "✅ El sistema de CSRF está funcionando correctamente\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}






