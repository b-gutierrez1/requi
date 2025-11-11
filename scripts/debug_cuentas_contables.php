<?php
/**
 * Script para debuggear las cuentas contables
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== DEBUG DE CUENTAS CONTABLES ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    echo "1. Verificando estructura de la tabla cuenta_contable...\n";
    
    $conn = \App\Models\Model::getConnection();
    $stmt = $conn->prepare("DESCRIBE cuenta_contable");
    $stmt->execute();
    $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "   Columnas encontradas:\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n2. Obteniendo cuentas contables con CuentaContable::all()...\n";
    
    $cuentas = \App\Models\CuentaContable::all();
    echo "   Total de cuentas: " . count($cuentas) . "\n";
    
    if (count($cuentas) > 0) {
        echo "\n3. Primeras 3 cuentas contables:\n";
        for ($i = 0; $i < min(3, count($cuentas)); $i++) {
            $cuenta = $cuentas[$i];
            echo "   Cuenta " . ($i + 1) . ":\n";
            
            if (is_object($cuenta)) {
                echo "     - Tipo: Objeto\n";
                echo "     - ID: " . ($cuenta->id ?? 'NO DEFINIDO') . "\n";
                echo "     - Código: " . ($cuenta->codigo ?? 'NO DEFINIDO') . "\n";
                echo "     - Descripción: " . ($cuenta->descripcion ?? 'NO DEFINIDO') . "\n";
                echo "     - Activo: " . ($cuenta->activo ?? 'NO DEFINIDO') . "\n";
            } elseif (is_array($cuenta)) {
                echo "     - Tipo: Array\n";
                echo "     - ID: " . ($cuenta['id'] ?? 'NO DEFINIDO') . "\n";
                echo "     - Código: " . ($cuenta['codigo'] ?? 'NO DEFINIDO') . "\n";
                echo "     - Descripción: " . ($cuenta['descripcion'] ?? 'NO DEFINIDO') . "\n";
                echo "     - Activo: " . ($cuenta['activo'] ?? 'NO DEFINIDO') . "\n";
            } else {
                echo "     - Tipo: " . gettype($cuenta) . "\n";
                echo "     - Valor: " . print_r($cuenta, true) . "\n";
            }
            echo "\n";
        }
        
        echo "4. Probando JSON encode de las cuentas...\n";
        $json = json_encode($cuentas, JSON_UNESCAPED_UNICODE);
        if ($json) {
            echo "   ✅ JSON encode exitoso\n";
            echo "   Primeros 200 caracteres: " . substr($json, 0, 200) . "...\n";
        } else {
            echo "   ❌ Error en JSON encode: " . json_last_error_msg() . "\n";
        }
        
        echo "\n5. Simulando lo que recibe JavaScript...\n";
        $cuentasParaJS = $cuentas;
        echo "   Tipo de datos: " . gettype($cuentasParaJS) . "\n";
        echo "   Count: " . count($cuentasParaJS) . "\n";
        
        if (count($cuentasParaJS) > 0) {
            $primera = $cuentasParaJS[0];
            echo "   Primera cuenta para JS:\n";
            if (is_object($primera)) {
                echo "     - ID: " . ($primera->id ?? 'undefined') . "\n";
                echo "     - Código: " . ($primera->codigo ?? 'undefined') . "\n";
                echo "     - Descripción: " . ($primera->descripcion ?? 'undefined') . "\n";
                echo "     - Texto esperado: " . ($primera->codigo ?? 'undefined') . " - " . ($primera->descripcion ?? 'undefined') . "\n";
            } else {
                echo "     - ID: " . ($primera['id'] ?? 'undefined') . "\n";
                echo "     - Código: " . ($primera['codigo'] ?? 'undefined') . "\n";
                echo "     - Descripción: " . ($primera['descripcion'] ?? 'undefined') . "\n";
                echo "     - Texto esperado: " . ($primera['codigo'] ?? 'undefined') . " - " . ($primera['descripcion'] ?? 'undefined') . "\n";
            }
        }
        
    } else {
        echo "   ❌ No se encontraron cuentas contables\n";
        
        echo "\n3. Verificando si hay datos en la tabla...\n";
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cuenta_contable");
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        echo "   Total de registros en tabla: " . ($result['total'] ?? 0) . "\n";
        
        if (($result['total'] ?? 0) > 0) {
            echo "\n4. Obteniendo datos directamente de la tabla...\n";
            $stmt = $conn->prepare("SELECT * FROM cuenta_contable LIMIT 3");
            $stmt->execute();
            $directData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($directData as $i => $row) {
                echo "   Fila " . ($i + 1) . ": " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
            }
        } else {
            echo "   ❌ La tabla está vacía\n";
            
            echo "\n5. Creando cuenta contable de prueba...\n";
            try {
                $nuevaCuenta = \App\Models\CuentaContable::create([
                    'codigo' => '999999999-99-99',
                    'descripcion' => 'Cuenta de Prueba Auto-generada',
                    'activo' => 1,
                    'tipo' => 'gasto'
                ]);
                
                if ($nuevaCuenta) {
                    echo "   ✅ Cuenta de prueba creada exitosamente\n";
                    echo "   ID: " . ($nuevaCuenta->id ?? $nuevaCuenta['id'] ?? 'N/A') . "\n";
                } else {
                    echo "   ❌ No se pudo crear cuenta de prueba\n";
                }
            } catch (Exception $e) {
                echo "   ❌ Error creando cuenta de prueba: " . $e->getMessage() . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETADO ===\n";






