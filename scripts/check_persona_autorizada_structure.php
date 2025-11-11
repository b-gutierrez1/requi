<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Model;

try {
    $conn = Model::getConnection();
    
    echo "=== ESTRUCTURA DE PERSONA_AUTORIZADA ===\n";
    
    // Verificar si es tabla o vista
    $stmt = $conn->query("
        SELECT TABLE_TYPE 
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name = 'persona_autorizada'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Tipo: {$result['TABLE_TYPE']}\n\n";
        
        if ($result['TABLE_TYPE'] === 'VIEW') {
            echo "Es una vista. Verificando definición...\n";
            $stmt = $conn->query("SHOW CREATE VIEW persona_autorizada");
            $viewDef = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Definición:\n" . $viewDef['Create View'] . "\n\n";
        }
    }
    
    // Buscar tablas relacionadas con autorizadores
    echo "=== TABLAS RELACIONADAS ===\n";
    $stmt = $conn->query("SHOW TABLES LIKE '%autoriza%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Verificar estructura de autorizadores
    echo "\n=== ESTRUCTURA DE AUTORIZADORES ===\n";
    $stmt = $conn->query("DESCRIBE autorizadores");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']}\n";
    }
    
    // Verificar estructura de autorizador_centro_costo
    echo "\n=== ESTRUCTURA DE AUTORIZADOR_CENTRO_COSTO ===\n";
    $stmt = $conn->query("DESCRIBE autorizador_centro_costo");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}






