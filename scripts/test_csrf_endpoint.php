<?php
/**
 * Script para probar el endpoint de renovación de token CSRF
 */

echo "=== PRUEBA DE ENDPOINT CSRF TOKEN ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $url = 'http://localhost:8000/csrf-token';
    
    echo "1. Probando endpoint: $url\n";
    
    // Configurar contexto para la petición
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'X-Requested-With: XMLHttpRequest',
                'User-Agent: PHP Test Script'
            ],
            'timeout' => 10
        ]
    ]);
    
    echo "2. Enviando petición...\n";
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "   ❌ Error: No se pudo conectar al servidor\n";
        echo "   Verifica que el servidor esté ejecutándose en localhost:8000\n";
        
        // Intentar con diferentes puertos comunes
        $ports = [80, 8080, 3000];
        foreach ($ports as $port) {
            $testUrl = "http://localhost:$port/csrf-token";
            echo "   Probando puerto $port...\n";
            
            $testContext = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => ['X-Requested-With: XMLHttpRequest'],
                    'timeout' => 3
                ]
            ]);
            
            $testResponse = @file_get_contents($testUrl, false, $testContext);
            if ($testResponse !== false) {
                echo "   ✅ Servidor encontrado en puerto $port\n";
                $url = $testUrl;
                $response = $testResponse;
                break;
            }
        }
    }
    
    if ($response !== false) {
        echo "3. Analizando respuesta...\n";
        echo "   Tamaño: " . strlen($response) . " bytes\n";
        
        // Intentar decodificar JSON
        $data = json_decode($response, true);
        
        if ($data) {
            echo "   ✅ Respuesta JSON válida\n";
            
            if (isset($data['success']) && $data['success']) {
                echo "   ✅ Success: true\n";
                
                if (isset($data['token'])) {
                    echo "   ✅ Token presente: " . substr($data['token'], 0, 16) . "...\n";
                    echo "   Longitud del token: " . strlen($data['token']) . " caracteres\n";
                    
                    // Verificar que es un token válido (hexadecimal)
                    if (ctype_xdigit($data['token'])) {
                        echo "   ✅ Token tiene formato hexadecimal válido\n";
                    } else {
                        echo "   ⚠️  Token no tiene formato hexadecimal\n";
                    }
                } else {
                    echo "   ❌ Token no presente en la respuesta\n";
                }
            } else {
                echo "   ❌ Success: false o no presente\n";
            }
            
            echo "\n   Respuesta completa:\n";
            echo "   " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "   ❌ Respuesta no es JSON válido\n";
            echo "   Contenido: $response\n";
        }
    } else {
        echo "3. ❌ No se pudo obtener respuesta del servidor\n";
    }
    
    echo "\n=== PRUEBA COMPLETADA ===\n";
    
    if ($response !== false && isset($data['token'])) {
        echo "✅ El endpoint de CSRF funciona correctamente\n";
    } else {
        echo "❌ El endpoint de CSRF no está funcionando\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}






