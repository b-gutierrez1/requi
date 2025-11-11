<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\CentroCosto;
use App\Models\PersonaAutorizada;

echo "=== VERIFICACIÓN CENTRO DE COSTO 32 ===\n";

try {
    // Buscar el centro
    $centro = CentroCosto::find(32);
    
    if (!$centro) {
        echo "❌ Centro 32 no encontrado\n";
        exit(1);
    }
    
    $data = is_object($centro) ? $centro->toArray() : $centro;
    echo "✓ Centro encontrado:\n";
    echo "  ID: {$data['id']}\n";
    echo "  Nombre: {$data['nombre']}\n";
    
    // Buscar autorizador
    echo "\nBuscando autorizador...\n";
    $persona = PersonaAutorizada::principalPorCentro(32);
    
    if ($persona) {
        echo "✓ Autorizador encontrado:\n";
        echo "  Nombre: {$persona['nombre']}\n";
        echo "  Email: {$persona['email']}\n";
    } else {
        echo "❌ No se encontró autorizador para este centro\n";
        
        // Listar algunos autorizadores disponibles
        echo "\nAutorizadores disponibles:\n";
        $conn = CentroCosto::getConnection();
        $stmt = $conn->query("SELECT DISTINCT email, nombre FROM persona_autorizada LIMIT 5");
        $autorizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($autorizadores as $auth) {
            echo "  - {$auth['nombre']} ({$auth['email']})\n";
        }
        
        echo "\n¿Quieres asignar uno de estos autorizadores al centro 32?\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}






