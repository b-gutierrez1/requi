<?php
/**
 * Debug para verificar el estado real de la sesión del usuario
 */

require_once 'vendor/autoload.php';

session_start();

echo "=== DEBUG SESIÓN REAL DEL USUARIO ===\n";

try {
    // Mostrar el estado actual de la sesión
    echo "=== ESTADO DE LA SESIÓN ===\n";
    if (empty($_SESSION)) {
        echo "❌ No hay sesión iniciada\n";
        echo "INSTRUCCIÓN: Inicia sesión primero en http://localhost:8000/login\n";
        exit;
    }
    
    echo "Sesión encontrada:\n";
    foreach ($_SESSION as $key => $value) {
        if ($key === 'csrf_token' || $key === 'azure_token') {
            echo "  $key: [OCULTO]\n";
        } else {
            echo "  $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    }
    
    // Verificar usuario actual
    $usuarioEmail = $_SESSION['user']['email'] ?? $_SESSION['user_email'] ?? 'N/A';
    $usuarioId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 'N/A';
    
    echo "\n=== USUARIO ACTUAL ===\n";
    echo "ID: $usuarioId\n";
    echo "Email: $usuarioEmail\n";
    
    // Verificar permisos de revisor
    echo "\n=== VERIFICACIÓN DE PERMISOS ===\n";
    $sessionIsRevisor = \App\Helpers\Session::isRevisor();
    echo "Session::isRevisor(): " . ($sessionIsRevisor ? 'SÍ' : 'NO') . "\n";
    
    // Probar con AutorizacionController
    $controller = new \App\Controllers\AutorizacionController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('isRevisorPorEmail');
    $method->setAccessible(true);
    $esRevisorPorEmail = $method->invokeArgs($controller, [$usuarioEmail]);
    echo "isRevisorPorEmail(): " . ($esRevisorPorEmail ? 'SÍ' : 'NO') . "\n";
    
    $esRevisorFinal = $sessionIsRevisor || $esRevisorPorEmail;
    echo "¿Es revisor (final)?: " . ($esRevisorFinal ? 'SÍ' : 'NO') . "\n";
    
    // Verificar middleware
    $middleware = new \App\Middlewares\RoleMiddleware('revisor');
    $reflection2 = new ReflectionClass($middleware);
    $method2 = $reflection2->getMethod('isRevisor');
    $method2->setAccessible(true);
    $middlewarePermite = $method2->invoke($middleware);
    echo "Middleware permite: " . ($middlewarePermite ? 'SÍ' : 'NO') . "\n";
    
    // Probar el flujo completo de autorización
    echo "\n=== FLUJO DE AUTORIZACIONES ===\n";
    $autorizacionService = new \App\Services\AutorizacionService();
    
    if ($esRevisorFinal) {
        $pendientesRevision = $autorizacionService->getRequisicionesPendientesRevision();
        echo "Requisiciones pendientes de revisión: " . count($pendientesRevision) . "\n";
        
        if (count($pendientesRevision) > 0) {
            echo "Lista de requisiciones:\n";
            foreach ($pendientesRevision as $i => $req) {
                $ordenId = $req['orden_id'] ?? $req['orden_compra_id'] ?? 'N/A';
                echo "  [$i] Requisición #$ordenId - {$req['nombre_razon_social']} - Q" . number_format($req['monto_total'], 2) . "\n";
            }
            
            // Verificar si está la 25
            $tiene25 = false;
            foreach ($pendientesRevision as $req) {
                $ordenId = $req['orden_id'] ?? $req['orden_compra_id'] ?? 0;
                if ($ordenId == 25) {
                    $tiene25 = true;
                    break;
                }
            }
            echo "¿Incluye requisición 25?: " . ($tiene25 ? 'SÍ' : 'NO') . "\n";
        } else {
            echo "❌ No se encontraron requisiciones pendientes de revisión\n";
        }
    } else {
        echo "❌ El usuario no tiene permisos de revisor\n";
    }
    
    // Verificar autorizaciones por centro
    $autorizacionesPendientes = $autorizacionService->getAutorizacionesPendientes($usuarioEmail);
    echo "Autorizaciones pendientes (centro): " . count($autorizacionesPendientes) . "\n";
    
    // Verificar todas las autorizaciones
    $todasAutorizaciones = $autorizacionService->getTodasAutorizacionesPendientes($usuarioEmail);
    echo "Total autorizaciones unificadas: " . count($todasAutorizaciones) . "\n";
    
    echo "\n=== DIAGNÓSTICO ===\n";
    if (!$esRevisorFinal) {
        echo "❌ PROBLEMA: El usuario no es reconocido como revisor\n";
        echo "SOLUCIÓN: Verificar configuración de permisos o email\n";
    } elseif (count($pendientesRevision) == 0) {
        echo "❌ PROBLEMA: No hay requisiciones pendientes de revisión\n";
        echo "SOLUCIÓN: Verificar estado de requisiciones en base de datos\n";
    } else {
        echo "✅ TODO PARECE ESTAR BIEN\n";
        echo "NOTA: Si la interfaz no funciona, podría ser problema de caché o navegador\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>