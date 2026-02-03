<?php
/**
 * Visualizador de Logs del Sistema
 * 
 * Permite ver los logs del sistema directamente en el navegador
 * IMPORTANTE: Eliminar este archivo en producción por seguridad
 */

// Verificar si está en desarrollo (por seguridad)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Solo permitir acceso en desarrollo local
$allowedIps = ['127.0.0.1', '::1', 'localhost'];
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($clientIp, $allowedIps) && strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === false) {
    die('Acceso denegado. Solo disponible en desarrollo local.');
}

// Obtener la fecha solicitada (por defecto hoy)
$date = $_GET['date'] ?? date('Y-m-d');
$logFile = BASE_PATH . '/storage/logs/errors_' . $date . '.txt';

// Listar archivos de log disponibles
$logDir = BASE_PATH . '/storage/logs';
$logFiles = [];
if (is_dir($logDir)) {
    $files = glob($logDir . '/errors_*.txt');
    foreach ($files as $file) {
        $logFiles[] = basename($file, '.txt');
    }
    rsort($logFiles); // Más recientes primero
}

// Leer el log solicitado
$logContent = '';
if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
} else {
    $logContent = "No hay logs para la fecha: $date\n\nArchivos disponibles:\n" . implode("\n", array_slice($logFiles, 0, 10));
}

// Filtrar por término de búsqueda
$search = $_GET['search'] ?? '';
if ($search && $logContent) {
    $lines = explode("\n", $logContent);
    $filteredLines = [];
    foreach ($lines as $line) {
        if (stripos($line, $search) !== false) {
            $filteredLines[] = $line;
        }
    }
    $logContent = implode("\n", $filteredLines);
}

// Limitar número de líneas (últimas N líneas)
$lines = (int)($_GET['lines'] ?? 500);
if ($logContent && $lines > 0) {
    $allLines = explode("\n", $logContent);
    if (count($allLines) > $lines) {
        $logContent = "... (mostrando últimas $lines líneas de " . count($allLines) . " totales)\n\n" . 
                     implode("\n", array_slice($allLines, -$lines));
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs del Sistema - Sistema de Requisiciones</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: #252526;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #3e3e42;
        }
        .header h1 {
            color: #4ec9b0;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .controls label {
            color: #cccccc;
            font-size: 14px;
        }
        .controls input, .controls select {
            background: #3c3c3c;
            border: 1px solid #555;
            color: #cccccc;
            padding: 8px 12px;
            border-radius: 3px;
            font-family: inherit;
            font-size: 14px;
        }
        .controls input:focus, .controls select:focus {
            outline: none;
            border-color: #007acc;
        }
        .controls button {
            background: #007acc;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
        }
        .controls button:hover {
            background: #005a9e;
        }
        .log-container {
            background: #1e1e1e;
            border: 1px solid #3e3e42;
            border-radius: 5px;
            padding: 20px;
            overflow-x: auto;
        }
        .log-content {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
            line-height: 1.5;
        }
        .log-line {
            padding: 2px 0;
        }
        .log-line.error {
            color: #f48771;
        }
        .log-line.warning {
            color: #cca700;
        }
        .log-line.info {
            color: #4ec9b0;
        }
        .log-line.debug {
            color: #9cdcfe;
        }
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .stat {
            background: #2d2d30;
            padding: 10px 15px;
            border-radius: 3px;
            font-size: 13px;
        }
        .stat-label {
            color: #858585;
            font-size: 11px;
            margin-bottom: 5px;
        }
        .stat-value {
            color: #4ec9b0;
            font-size: 16px;
            font-weight: bold;
        }
        .empty {
            color: #858585;
            text-align: center;
            padding: 40px;
            font-style: italic;
        }
        a {
            color: #4ec9b0;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Logs del Sistema de Requisiciones</h1>
            
            <form method="GET" action="" class="controls">
                <label>
                    Fecha:
                    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
                </label>
                
                <label>
                    Líneas (0 = todas):
                    <input type="number" name="lines" value="<?= htmlspecialchars($lines) ?>" min="0" max="10000" style="width: 100px;">
                </label>
                
                <label>
                    Buscar:
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar en logs...">
                </label>
                
                <button type="submit">🔍 Actualizar</button>
                <a href="?date=<?= date('Y-m-d') ?>" style="color: #4ec9b0; padding: 8px 16px; display: inline-block;">Hoy</a>
            </form>
            
            <?php if ($logContent && !strpos($logContent, 'No hay logs')): ?>
                <?php
                $errorCount = substr_count($logContent, '[ERROR]') + substr_count($logContent, 'ERROR:');
                $warningCount = substr_count($logContent, '[WARNING]') + substr_count($logContent, 'WARNING:');
                $infoCount = substr_count($logContent, '[INFO]') + substr_count($logContent, 'INFO:');
                $lineCount = substr_count($logContent, "\n");
                ?>
                <div class="stats">
                    <div class="stat">
                        <div class="stat-label">Total Líneas</div>
                        <div class="stat-value"><?= number_format($lineCount) ?></div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Errores</div>
                        <div class="stat-value" style="color: #f48771;"><?= $errorCount ?></div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Advertencias</div>
                        <div class="stat-value" style="color: #cca700;"><?= $warningCount ?></div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Info</div>
                        <div class="stat-value" style="color: #4ec9b0;"><?= $infoCount ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($logFiles)): ?>
                <div style="margin-top: 15px;">
                    <strong style="color: #cccccc;">Archivos disponibles:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                        <?php foreach (array_slice($logFiles, 0, 10) as $logFileDate): ?>
                            <?php
                            $fileName = str_replace('errors_', '', $logFileDate);
                            $displayDate = str_replace('errors_', '', $logFileDate);
                            ?>
                            <a href="?date=<?= $displayDate ?>" style="padding: 5px 10px; background: #3c3c3c; border-radius: 3px; display: inline-block; font-size: 12px;">
                                <?= $displayDate ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="log-container">
            <?php if ($logContent): ?>
                <div class="log-content">
                    <?php
                    $lines = explode("\n", htmlspecialchars($logContent));
                    foreach ($lines as $line) {
                        $class = '';
                        if (stripos($line, 'error') !== false || stripos($line, '❌') !== false) {
                            $class = 'error';
                        } elseif (stripos($line, 'warning') !== false || stripos($line, '⚠️') !== false) {
                            $class = 'warning';
                        } elseif (stripos($line, 'info') !== false || stripos($line, '✅') !== false) {
                            $class = 'info';
                        } elseif (stripos($line, 'debug') !== false) {
                            $class = 'debug';
                        }
                        echo '<div class="log-line ' . $class . '">' . $line . '</div>';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="empty">
                    No hay contenido en el log para la fecha seleccionada.
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #252526; border-radius: 5px; font-size: 12px; color: #858585;">
            <strong>⚠️ Nota de Seguridad:</strong> Este visualizador solo debe estar disponible en desarrollo local. 
            Eliminar o proteger este archivo en producción.
        </div>
    </div>
</body>
</html>






