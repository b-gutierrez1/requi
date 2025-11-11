<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\Model;

$conn = Model::getConnection();

echo "=== VERIFICACIÓN DE ESTRUCTURA DE TABLAS ===\n\n";

$tablas = [
    'autorizador_respaldo',
    'autorizadores_metodos_pago',
    'autorizadores_cuentas_contables',
    'autorizadores',
    'centro_de_costo',
    'cuenta_contable'
];

foreach ($tablas as $tabla) {
    echo "--- Tabla: $tabla ---\n";
    try {
        $sql = "SHOW TABLES LIKE '$tabla'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            echo "✅ Tabla existe\n";
            
            // Obtener columnas
            $sql = "DESCRIBE $tabla";
            $stmt = $conn->query($sql);
            $columnas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo "Columnas:\n";
            foreach ($columnas as $col) {
                echo "  - {$col['Field']} ({$col['Type']})\n";
            }
        } else {
            echo "❌ Tabla NO existe\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}



