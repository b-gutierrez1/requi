<?php
/**
 * Script para consolidar duplicados específicos usando emails bgutierrez y bguti
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\CentroCosto;

try {
    $conn = CentroCosto::getConnection();
    $conn->beginTransaction();
    
    echo "========================================\n";
    echo "  CONSOLIDACIÓN DE DUPLICADOS\n";
    echo "========================================\n\n";
    
    // Consolidar Fidel Zelada: mantener bgutierrez@sp.iga.edu, eliminar fidel.zelada@iga.edu
    echo "1. Consolidando Fidel Zelada...\n";
    
    // Buscar IDs
    $sql = "SELECT id FROM autorizadores WHERE email = 'bgutierrez@sp.iga.edu' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $fidelGutierrez = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    $sql = "SELECT id FROM autorizadores WHERE email = 'fidel.zelada@iga.edu' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $fidelZelada = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($fidelGutierrez && $fidelZelada) {
        // Actualizar relaciones de fidel.zelada a bgutierrez
        $sql = "UPDATE autorizador_centro_costo SET autorizador_id = ? WHERE autorizador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fidelGutierrez['id'], $fidelZelada['id']]);
        $affected = $stmt->rowCount();
        
        // Actualizar nombre en bgutierrez
        $sql = "UPDATE autorizadores SET nombre = 'Fidel Zelada' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fidelGutierrez['id']]);
        
        // Eliminar duplicado
        $sql = "DELETE FROM autorizadores WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fidelZelada['id']]);
        
        echo "   ✓ Consolidado: {$affected} relaciones movidas, duplicado eliminado\n";
    }
    
    // Consolidar Milton Santizo: mantener bguti@sp.iga.edu, eliminar milton.santizo@iga.edu
    echo "\n2. Consolidando Milton Santizo...\n";
    
    $sql = "SELECT id FROM autorizadores WHERE email = 'bguti@sp.iga.edu' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $miltonGuti = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    $sql = "SELECT id FROM autorizadores WHERE email = 'milton.santizo@iga.edu' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $miltonSantizo = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($miltonGuti && $miltonSantizo) {
        // Actualizar relaciones de milton.santizo a bguti
        $sql = "UPDATE autorizador_centro_costo SET autorizador_id = ? WHERE autorizador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$miltonGuti['id'], $miltonSantizo['id']]);
        $affected = $stmt->rowCount();
        
        // Actualizar nombre en bguti
        $sql = "UPDATE autorizadores SET nombre = 'Milton Santizo' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$miltonGuti['id']]);
        
        // Eliminar duplicado
        $sql = "DELETE FROM autorizadores WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$miltonSantizo['id']]);
        
        echo "   ✓ Consolidado: {$affected} relaciones movidas, duplicado eliminado\n";
    }
    
    $conn->commit();
    
    echo "\n========================================\n";
    echo "  VERIFICACIÓN FINAL\n";
    echo "========================================\n\n";
    
    $sql = "SELECT nombre, email FROM autorizadores WHERE activo = 1 ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $autorizadores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "Total autorizadores únicos: " . count($autorizadores) . "\n\n";
    foreach ($autorizadores as $a) {
        echo "  - {$a['nombre']} | {$a['email']}\n";
    }
    
    echo "\n✓ Consolidación completada exitosamente!\n";
    
} catch (\Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}



