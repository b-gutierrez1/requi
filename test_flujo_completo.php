<?php
/**
 * Test del flujo completo para entender qué está viendo el usuario
 */

require_once 'vendor/autoload.php';

session_start();

// Simular usuario real con email específico
$_SESSION['user'] = [
    'id' => 107,
    'email' => 'bgutierrez@sp.iga.edu',
    'nombre' => 'Usuario Real'
];

echo "=== TEST FLUJO COMPLETO ===\n";

try {
    $usuarioEmail = 'bgutierrez@sp.iga.edu';
    $controller = new \App\Controllers\AutorizacionController();
    $autorizacionService = new \App\Services\AutorizacionService();
    
    echo "=== PASO 1: VERIFICAR PERMISOS ===\n";
    
    // Verificar permisos de revisor
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('isRevisorPorEmail');
    $method->setAccessible(true);
    $esRevisor = $method->invokeArgs($controller, [$usuarioEmail]);
    
    echo "¿Es revisor?: " . ($esRevisor ? 'SÍ' : 'NO') . "\n";
    
    if (!$esRevisor) {
        echo "❌ PROBLEMA ENCONTRADO: Usuario no es reconocido como revisor\n";
        echo "Email verificado: $usuarioEmail\n";
        echo "Emails válidos de revisor: bgutierrez@sp.iga.edu, bgutierrez@iga.edu, admin@iga.edu\n";
        exit;
    }
    
    echo "=== PASO 2: OBTENER DATOS PARA LA VISTA ===\n";
    
    // Obtener todos los datos que se pasan a la vista
    $requisicionesPendientesRevision = $autorizacionService->getRequisicionesPendientesRevision();
    $autorizacionesPendientes = $autorizacionService->getAutorizacionesPendientes($usuarioEmail);
    $todasAutorizaciones = $autorizacionService->getTodasAutorizacionesPendientes($usuarioEmail);
    
    echo "Requisiciones pendientes de REVISIÓN: " . count($requisicionesPendientesRevision) . "\n";
    echo "Autorizaciones por CENTRO: " . count($autorizacionesPendientes) . "\n";  
    echo "TODAS las autorizaciones: " . count($todasAutorizaciones) . "\n";
    
    echo "\n=== PASO 3: ANALIZAR FLUJO ESPERADO ===\n";
    
    if (count($requisicionesPendientesRevision) > 0) {
        echo "✅ HAY REQUISICIONES PARA REVISAR (paso 1 del flujo)\n";
        echo "Lista de requisiciones en revisión:\n";
        foreach ($requisicionesPendientesRevision as $i => $req) {
            $ordenId = $req['orden_id'] ?? $req['orden_compra_id'] ?? 'N/A';
            echo "  - Requisición #$ordenId: {$req['nombre_razon_social']} (Q" . number_format($req['monto_total'], 2) . ")\n";
        }
        
        echo "\n❗ FLUJO CORRECTO:\n";
        echo "1. El usuario DEBE VER la sección 'Requisiciones Pendientes de Revisión'\n";
        echo "2. El usuario DEBE HACER CLIC en 'Revisar' para cada requisición\n";
        echo "3. DESPUÉS de aprobar/rechazar la revisión, la requisición pasa al siguiente paso\n";
        echo "4. SOLO ENTONCES aparecen en las autorizaciones por centro de costo\n";
        
    } else {
        echo "ℹ️ No hay requisiciones pendientes de revisión inicial\n";
    }
    
    if (count($todasAutorizaciones) > 0) {
        echo "\n=== AUTORIZACIONES POSTERIORES A LA REVISIÓN ===\n";
        foreach ($todasAutorizaciones as $i => $auth) {
            echo "  - Tipo: {$auth['tipo_flujo']}, Orden: " . ($auth['orden_id'] ?? 'N/A') . "\n";
        }
        
        echo "\nESTAS autorizaciones aparecen DESPUÉS de la revisión inicial\n";
    }
    
    echo "\n=== PASO 4: DIAGNÓSTICO DEL PROBLEMA ===\n";
    
    if (count($requisicionesPendientesRevision) > 0 && count($todasAutorizaciones) > 0) {
        echo "❓ POSIBLE PROBLEMA: El usuario ve AMBAS secciones\n";
        echo "HIPÓTESIS: El usuario está viendo la sección de autorizaciones (segunda)\n";
        echo "           en lugar de la sección de revisiones (primera)\n";
        echo "\nSOLUCIÓN SUGERIDA:\n";
        echo "- Hacer más prominente la sección de revisiones\n";
        echo "- O ocultar las autorizaciones hasta que se complete la revisión\n";
    } elseif (count($requisicionesPendientesRevision) == 0) {
        echo "❓ POSIBLE PROBLEMA: No hay requisiciones para revisar\n";
        echo "CAUSA: Tal vez ya fueron revisadas o están en estado incorrecto\n";
    } else {
        echo "✅ El flujo debería estar funcionando correctamente\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== INSTRUCCIONES PARA EL USUARIO ===\n";
echo "1. Ve a http://localhost:8000/autorizaciones\n";
echo "2. Busca la sección 'Requisiciones Pendientes de Revisión' (debería estar ARRIBA)\n";
echo "3. Si no la ves, scroll hacia arriba - podría estar por encima de otras secciones\n";
echo "4. Haz clic en 'Revisar' en cada requisición de esa sección PRIMERO\n";
echo "5. SOLO después de revisar, las autorizaciones por centro aparecerán\n";

echo "\n=== FIN TEST ===\n";
?>