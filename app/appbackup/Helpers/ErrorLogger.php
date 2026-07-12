<?php
/**
 * Sistema de logging de errores personalizado
 * 
 * Captura y guarda errores del sistema en archivos de texto
 * para facilitar el debugging y seguimiento de problemas.
 * 
 * @package RequisicionesMVC\Helpers
 * @version 1.0
 */

namespace App\Helpers;

class ErrorLogger
{
    /**
     * Directorio donde se guardan los logs
     */
    private static $logDir = null;
    
    /**
     * Inicializa el directorio de logs
     */
    private static function initLogDir()
    {
        if (self::$logDir === null) {
            self::$logDir = dirname(__DIR__, 2) . '/storage/logs';
            
            // Crear directorio si no existe
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
    }
    
    /**
     * Registra un error en el sistema
     * 
     * @param string $message Mensaje del error
     * @param array $context Contexto adicional (datos de debug)
     * @param string $level Nivel del error (ERROR, WARNING, INFO, DEBUG)
     * @return void
     */
    public static function log($message, $context = [], $level = 'ERROR')
    {
        try {
            self::initLogDir();
            
            $timestamp = date('Y-m-d H:i:s');
            $date = date('Y-m-d');
            $filename = self::$logDir . "/errors_{$date}.txt";
            
            // Preparar información del contexto
            $contextInfo = '';
            if (!empty($context)) {
                $contextInfo = "\nContexto:\n";
                foreach ($context as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $contextInfo .= "  {$key}: " . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
                    } else {
                        $contextInfo .= "  {$key}: {$value}\n";
                    }
                }
            }
            
            // Información de la petición actual
            $requestInfo = self::getRequestInfo();
            
            // Preparar el mensaje completo
            $logEntry = str_repeat('=', 80) . "\n";
            $logEntry .= "[{$timestamp}] {$level}: {$message}\n";
            $logEntry .= $requestInfo;
            $logEntry .= $contextInfo;
            $logEntry .= str_repeat('-', 80) . "\n\n";
            
            // Escribir al archivo
            file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
            
            // También escribir al log de PHP para backup
            error_log("[ErrorLogger] {$level}: {$message}");
            
        } catch (\Exception $e) {
            // Si el logging falla, al menos intentar escribir al log de PHP
            error_log("ErrorLogger falló: " . $e->getMessage() . " - Error original: " . $message);
        }
    }
    
    /**
     * Registra un error específico de requisición
     * 
     * @param string $action Acción que se intentaba realizar
     * @param array $data Datos de la requisición
     * @param string $error Mensaje de error
     * @param array $additionalContext Contexto adicional
     * @return void
     */
    public static function logRequisicionError($action, $data, $error, $additionalContext = [])
    {
        $context = [
            'action' => $action,
            'requisicion_data' => self::sanitizeData($data),
            'error' => $error,
            'user_id' => $_SESSION['user']['id'] ?? 'No definido',
            'user_email' => $_SESSION['user']['email'] ?? 'No definido',
            'session_data' => self::sanitizeSessionData()
        ];
        
        // Agregar contexto adicional
        $context = array_merge($context, $additionalContext);
        
        self::log("Error en requisición - Acción: {$action}", $context, 'ERROR');
    }
    
    /**
     * Registra información detallada de una excepción
     * 
     * @param \Exception|\Error $exception La excepción capturada
     * @param string $context Contexto donde ocurrió
     * @param array $additionalData Datos adicionales relevantes
     * @return void
     */
    public static function logException($exception, $context = '', $additionalData = [])
    {
        $data = [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'additional_data' => $additionalData
        ];
        
        self::log("Excepción capturada: " . $exception->getMessage(), $data, 'ERROR');
    }
    
    /**
     * Obtiene información de la petición actual
     * 
     * @return string
     */
    private static function getRequestInfo()
    {
        $info = "Información de la petición:\n";
        $info .= "  URL: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
        $info .= "  Método: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n";
        $info .= "  IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
        $info .= "  User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n";
        
        // Headers importantes
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $info .= "  Ajax Request: " . $_SERVER['HTTP_X_REQUESTED_WITH'] . "\n";
        }
        
        // Datos POST (sanitizados)
        if (!empty($_POST)) {
            $info .= "  POST Data: " . json_encode(self::sanitizeData($_POST), JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        return $info;
    }
    
    /**
     * Sanitiza datos sensibles antes de guardarlos en logs
     * 
     * @param array $data Datos a sanitizar
     * @return array Datos sanitizados
     */
    private static function sanitizeData($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        
        $sensitiveFields = ['password', 'token', 'csrf_token', 'api_key', 'secret'];
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveFields as $sensitiveField) {
                if (strpos($lowerKey, $sensitiveField) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $sanitized[$key] = '[OCULTO]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiza datos de sesión para el log
     * 
     * @return array Datos de sesión sanitizados
     */
    private static function sanitizeSessionData()
    {
        $sessionData = $_SESSION ?? [];
        
        // Remover datos sensibles específicos
        unset($sessionData['csrf_token']);
        
        return self::sanitizeData($sessionData);
    }
    
    /**
     * Obtiene los últimos errores registrados
     * 
     * @param int $lines Número de líneas a obtener
     * @param string $date Fecha específica (Y-m-d), null para hoy
     * @return string Contenido del log
     */
    public static function getRecentErrors($lines = 100, $date = null)
    {
        self::initLogDir();
        
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $filename = self::$logDir . "/errors_{$date}.txt";
        
        if (!file_exists($filename)) {
            return "No hay errores registrados para la fecha: {$date}";
        }
        
        $content = file_get_contents($filename);
        $allLines = explode("\n", $content);
        $recentLines = array_slice($allLines, -$lines);
        
        return implode("\n", $recentLines);
    }
    
    /**
     * Limpia logs antiguos (más de N días)
     * 
     * @param int $days Días a conservar
     * @return int Número de archivos eliminados
     */
    public static function cleanOldLogs($days = 30)
    {
        self::initLogDir();
        
        $deleted = 0;
        $cutoffDate = time() - ($days * 24 * 60 * 60);
        
        $files = glob(self::$logDir . '/errors_*.txt');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffDate) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}
?>