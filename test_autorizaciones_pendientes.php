<?php
/**
 * Script para probar que las autorizaciones pendientes aparezcan correctamente
 */

require_once 'vendor/autoload.php';

use App\Models\AutorizacionCentroCosto;

// Test con los emails de la requisición 3
$emails = ['bgutierrez@sp.iga.edu', 'bguti@sp.iga.edu'];

echo "<h1>Test de Autorizaciones Pendientes</h1>\n";

foreach ($emails as $email) {
    echo "<h2>Email: $email</h2>\n";
    
    try {
        $autorizaciones = AutorizacionCentroCosto::pendientesPorAutorizador($email);
        
        echo "<p><strong>Total encontradas:</strong> " . count($autorizaciones) . "</p>\n";
        
        if (!empty($autorizaciones)) {
            echo "<table border='1' cellpadding='5'>\n";
            echo "<tr><th>Orden ID</th><th>Proveedor</th><th>Centro</th><th>Estado</th><th>Monto</th></tr>\n";
            foreach ($autorizaciones as $auth) {
                echo "<tr>";
                echo "<td>" . $auth['orden_id'] . "</td>";
                echo "<td>" . htmlspecialchars($auth['nombre_razon_social']) . "</td>";
                echo "<td>" . htmlspecialchars($auth['centro_nombre']) . "</td>";
                echo "<td>" . $auth['estado'] . "</td>";
                echo "<td>$" . number_format($auth['monto_total'], 2) . "</td>";
                echo "</tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<p>No se encontraron autorizaciones pendientes</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>\n";
    }
    
    echo "<hr>\n";
}

echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";
?>