<?php
/**
 * Script de Limpieza de Duplicados
 * 
 * Este script limpia duplicados en:
 * - centro_de_costo (por nombre)
 * - persona_autorizada (por email + centro_costo_id)
 * 
 * Uso: php scripts/limpiar_duplicados.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar configuración
use App\Helpers\Config;
use App\Models\CentroCosto;
use App\Models\PersonaAutorizada;

try {
    echo "========================================\n";
    echo "  LIMPIEZA DE DUPLICADOS\n";
    echo "========================================\n\n";
    
    $conn = CentroCosto::getConnection();
    $conn->beginTransaction();
    
    $reporte = [
        'autorizadores' => [
            'duplicados_encontrados' => 0,
            'consolidados' => 0,
            'eliminados' => 0,
            'detalles' => []
        ],
        'centros_costo' => [
            'duplicados_encontrados' => 0,
            'consolidados' => 0,
            'eliminados' => 0,
            'detalles' => []
        ],
        'personas_autorizadas' => [
            'duplicados_encontrados' => 0,
            'consolidados' => 0,
            'eliminados' => 0,
            'detalles' => []
        ]
    ];
    
            // ================================================================
            // 0. LIMPIAR AUTORIZADORES DUPLICADOS (por email)
            // ================================================================
            
            echo "0. Buscando autorizadores duplicados por email...\n";
            
            $sql = "SELECT 
                        LOWER(TRIM(email)) as email_normalizado,
                        GROUP_CONCAT(id ORDER BY id) as ids,
                        COUNT(*) as total
                    FROM autorizadores
                    GROUP BY LOWER(TRIM(email))
                    HAVING COUNT(*) > 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $duplicadosAutorizadores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo "   Encontrados: " . count($duplicadosAutorizadores) . " grupos de duplicados\n\n";
            
            foreach ($duplicadosAutorizadores as $grupo) {
                $ids = explode(',', $grupo['ids']);
                $reporte['autorizadores']['duplicados_encontrados'] += count($ids);
                
                sort($ids);
                $idBase = $ids[0];
                $idsEliminar = array_slice($ids, 1);
                
                echo "   - Email: {$grupo['email_normalizado']}\n";
                echo "     Mantener ID: {$idBase}\n";
                echo "     Eliminar IDs: " . implode(', ', $idsEliminar) . "\n";
                
                // Consolidar relaciones en autorizador_centro_costo
                foreach ($idsEliminar as $idEliminar) {
                    // Obtener todas las relaciones del autorizador a eliminar
                    $sql = "SELECT centro_costo_id FROM autorizador_centro_costo WHERE autorizador_id = ? AND activo = 1";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idEliminar]);
                    $centrosEliminar = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                    
                    foreach ($centrosEliminar as $centroId) {
                        // Verificar si ya existe la relación en el autorizador base
                        $sql = "SELECT id FROM autorizador_centro_costo WHERE autorizador_id = ? AND centro_costo_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$idBase, $centroId]);
                        $existe = $stmt->fetch(\PDO::FETCH_ASSOC);
                        
                        if ($existe) {
                            // Si ya existe, solo desactivar el duplicado
                            $sql = "UPDATE autorizador_centro_costo SET activo = 0 WHERE autorizador_id = ? AND centro_costo_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([$idEliminar, $centroId]);
                        } else {
                            // Si no existe, mover la relación al autorizador base
                            $sql = "UPDATE autorizador_centro_costo SET autorizador_id = ? WHERE autorizador_id = ? AND centro_costo_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([$idBase, $idEliminar, $centroId]);
                        }
                    }
                    
                    // Desactivar todas las relaciones restantes
                    $sql = "UPDATE autorizador_centro_costo SET activo = 0 WHERE autorizador_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idEliminar]);
                    
                    // Eliminar el autorizador duplicado
                    $sql = "DELETE FROM autorizadores WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$idEliminar]);
                    
                    $reporte['autorizadores']['eliminados']++;
                }
                
                $reporte['autorizadores']['consolidados']++;
                echo "\n";
            }
            
            // ================================================================
            // 1. LIMPIAR CENTROS DE COSTO DUPLICADOS
            // ================================================================
    
    echo "1. Buscando centros de costo duplicados...\n";
    
    $sql = "SELECT 
                LOWER(TRIM(nombre)) as nombre_normalizado,
                GROUP_CONCAT(id ORDER BY id) as ids,
                COUNT(*) as total
            FROM centro_de_costo
            GROUP BY LOWER(TRIM(nombre))
            HAVING COUNT(*) > 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $duplicadosCentros = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "   Encontrados: " . count($duplicadosCentros) . " grupos de duplicados\n\n";
    
    foreach ($duplicadosCentros as $grupo) {
        $ids = explode(',', $grupo['ids']);
        $reporte['centros_costo']['duplicados_encontrados'] += count($ids);
        
        sort($ids);
        $idBase = $ids[0];
        $idsEliminar = array_slice($ids, 1);
        
        echo "   - Nombre: '{$grupo['nombre_normalizado']}'\n";
        echo "     Mantener ID: {$idBase}\n";
        echo "     Eliminar IDs: " . implode(', ', $idsEliminar) . "\n";
        
        $detalle = [
            'nombre' => $grupo['nombre_normalizado'],
            'mantener_id' => $idBase,
            'eliminar_ids' => $idsEliminar
        ];
        
        foreach ($idsEliminar as $idEliminar) {
            // Actualizar referencias
            $tablas = [
                'distribucion_gasto',
                'autorizacion_centro_costo',
                'persona_autorizada',
                'autorizador_respaldo'
            ];
            
            foreach ($tablas as $tabla) {
                $sql = "UPDATE {$tabla} SET centro_costo_id = ? WHERE centro_costo_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$idBase, $idEliminar]);
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    echo "       ✓ Actualizados {$affected} registros en {$tabla}\n";
                }
            }
            
            // Eliminar centro duplicado
            $sql = "DELETE FROM centro_de_costo WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$idEliminar]);
            
            $reporte['centros_costo']['eliminados']++;
        }
        
        $reporte['centros_costo']['consolidados']++;
        $reporte['centros_costo']['detalles'][] = $detalle;
        echo "\n";
    }
    
    // ================================================================
    // 2. LIMPIAR PERSONAS AUTORIZADAS DUPLICADAS
    // ================================================================
    
    echo "2. Buscando personas autorizadas duplicadas...\n";
    
    // La vista persona_autorizada es JOIN de autorizador_centro_costo y autorizadores
    // Buscar duplicados en autorizador_centro_costo con mismo autorizador_id y centro_costo_id
    $sql = "SELECT 
                acc.autorizador_id,
                acc.centro_costo_id,
                GROUP_CONCAT(acc.id ORDER BY acc.id) as ids,
                COUNT(*) as total
            FROM autorizador_centro_costo acc
            WHERE acc.activo = 1
            GROUP BY acc.autorizador_id, acc.centro_costo_id
            HAVING COUNT(*) > 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $duplicadosPersonas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "   Encontrados: " . count($duplicadosPersonas) . " grupos de duplicados\n\n";
    
    foreach ($duplicadosPersonas as $grupo) {
        $ids = explode(',', $grupo['ids']);
        $reporte['personas_autorizadas']['duplicados_encontrados'] += count($ids);
        
        sort($ids);
        $idBase = $ids[0];
        $idsEliminar = array_slice($ids, 1);
        
        // Obtener información del autorizador
        $sql = "SELECT email, nombre FROM autorizadores WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$grupo['autorizador_id']]);
        $autorizador = $stmt->fetch(\PDO::FETCH_ASSOC);
        $email = $autorizador['email'] ?? 'N/A';
        $nombre = $autorizador['nombre'] ?? 'N/A';
        
        echo "   - Autorizador: {$nombre} ({$email}), Centro: {$grupo['centro_costo_id']}\n";
        echo "     Mantener ID: {$idBase}\n";
        echo "     Eliminar IDs: " . implode(', ', $idsEliminar) . "\n";
        
        // Desactivar los duplicados en lugar de eliminarlos
        foreach ($idsEliminar as $idEliminar) {
            $sql = "UPDATE autorizador_centro_costo SET activo = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$idEliminar]);
            
            $reporte['personas_autorizadas']['eliminados']++;
        }
        
        $detalle = [
            'email' => $email,
            'centro_costo_id' => $grupo['centro_costo_id'],
            'autorizador_id' => $grupo['autorizador_id'],
            'mantener_id' => $idBase,
            'eliminar_ids' => $idsEliminar
        ];
        
        $reporte['personas_autorizadas']['consolidados']++;
        $reporte['personas_autorizadas']['detalles'][] = $detalle;
        echo "     ✓ Duplicados desactivados\n\n";
    }
    
    $conn->commit();
    
    echo "========================================\n";
    echo "  RESUMEN\n";
    echo "========================================\n\n";
    
    echo "AUTORIZADORES:\n";
    echo "  - Duplicados encontrados: {$reporte['autorizadores']['duplicados_encontrados']}\n";
    echo "  - Grupos consolidados: {$reporte['autorizadores']['consolidados']}\n";
    echo "  - Registros eliminados: {$reporte['autorizadores']['eliminados']}\n\n";
    
    echo "CENTROS DE COSTO:\n";
    echo "  - Duplicados encontrados: {$reporte['centros_costo']['duplicados_encontrados']}\n";
    echo "  - Grupos consolidados: {$reporte['centros_costo']['consolidados']}\n";
    echo "  - Registros eliminados: {$reporte['centros_costo']['eliminados']}\n\n";
    
    echo "PERSONAS AUTORIZADAS:\n";
    echo "  - Duplicados encontrados: {$reporte['personas_autorizadas']['duplicados_encontrados']}\n";
    echo "  - Grupos consolidados: {$reporte['personas_autorizadas']['consolidados']}\n";
    echo "  - Registros eliminados: {$reporte['personas_autorizadas']['eliminados']}\n\n";
    
    echo "✓ Limpieza completada exitosamente!\n";
    
} catch (\Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

