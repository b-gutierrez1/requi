<?php
/**
 * Test para verificar que el middleware de revisor funciona correctamente
 */

require_once 'vendor/autoload.php';

session_start();

// Simular usuario revisor por email
$_SESSION['user'] = [
    'id' => 107,
    'email' => 'bgutierrez@sp.iga.edu',
    'nombre' => 'Usuario Actual',
    'is_revisor' => 0  // NO es revisor por sesión, pero SÍ por email
];

echo "=== TEST MIDDLEWARE REVISOR ===\n";

try {
    // Probar directamente el middleware
    $middleware = new \App\Middlewares\RoleMiddleware('revisor');
    
    echo "Usuario email: " . ($_SESSION['user']['email'] ?? 'N/A') . "\n";
    echo "is_revisor en sesión: " . ($_SESSION['user']['is_revisor'] ?? 0) . "\n";
    
    // Usar reflexión para acceder al método privado isRevisor
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('isRevisor');
    $method->setAccessible(true);
    $esRevisor = $method->invoke($middleware);
    
    echo "Resultado isRevisor(): " . ($esRevisor ? 'SÍ' : 'NO') . "\n";
    
    // Probar el método handle completo
    $resultado = $middleware->handle();
    echo "Resultado handle(): " . ($resultado ? 'PERMITIDO' : 'BLOQUEADO') . "\n";
    
    if ($esRevisor && $resultado) {
        echo "✅ ÉXITO: El middleware ahora permite el acceso para revisores por email\n";
    } else {
        echo "❌ PROBLEMA: El middleware sigue bloqueando el acceso\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
}

echo "\n=== FIN TEST ===\n";
?>