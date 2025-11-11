<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Model;

try {
    $conn = Model::getConnection();
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "=== TABLAS EN LA BASE DE DATOS ===\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\n=== TABLAS RELACIONADAS CON DISTRIBUCIÓN ===\n";
    foreach ($tables as $table) {
        if (stripos($table, 'distri') !== false || stripos($table, 'gastos') !== false) {
            echo "- $table\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}






