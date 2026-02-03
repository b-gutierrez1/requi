<?php

/**
 * Bootstrap del Sistema de Requisiciones MVC
 * 
 * Este archivo es el punto de entrada único de la aplicación.
 * Todas las peticiones HTTP son redirigidas aquí mediante .htaccess
 * 
 * Responsabilidades:
 * - Configurar el autoloader PSR-4
 * - Cargar variables de entorno
 * - Cargar archivos de configuración
 * - Iniciar la sesión
 * - Crear el router y cargar rutas
 * - Despachar la petición
 * 
 * @package RequisicionesMVC
 * @version 1.0.0
 */

// ============================================================================
// 1. CONFIGURACIÓN DE ERRORES Y TIMEZONE
// ============================================================================

// Mostrar errores en desarrollo (se sobrescribe con APP_DEBUG)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar zona horaria
date_default_timezone_set('America/Guatemala');

// ============================================================================
// 2. DEFINIR CONSTANTES DE RUTAS
// ============================================================================

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('PUBLIC_PATH', __DIR__);
define('ROUTES_PATH', BASE_PATH . '/routes');

// ============================================================================
// 3. AUTOLOADER PSR-4
// ============================================================================

spl_autoload_register(function ($class) {
    // Prefijo del namespace
    $prefix = 'App\\';
    
    // Directorio base para el namespace
    $baseDir = APP_PATH . '/';
    
    // Verificar si la clase usa el namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, mover a la siguiente función autoloader registrada
        return;
    }
    
    // Obtener el nombre relativo de la clase
    $relativeClass = substr($class, $len);
    
    // Reemplazar namespace prefix con el directorio base
    // Reemplazar namespace separators con directory separators
    // Agregar .php al final
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Si el archivo existe, requerirlo
    if (file_exists($file)) {
        require $file;
    }
});

// ============================================================================
// 4. CARGAR COMPOSER AUTOLOADER (para dependencias externas)
// ============================================================================

$composerAutoloader = BASE_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoloader)) {
    require $composerAutoloader;
}
// ============================================================================
// 4.5. CARGAR HELPERS GLOBALES
// ============================================================================

$helpersFile = APP_PATH . '/Helpers/helpers.php';
if (file_exists($helpersFile)) {
    require $helpersFile;
}
// ============================================================================
// 5. CARGAR VARIABLES DE ENTORNO
// ============================================================================

// Cargar .env si existe (para desarrollo local)
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parsear línea KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remover comillas si existen
            $value = trim($value, '"\'');
            
            // Establecer variable de entorno
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// ============================================================================
// 6. CONFIGURAR MODO DEBUG
// ============================================================================

$appDebug = getenv('APP_DEBUG') === 'true';

if (!$appDebug) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// ============================================================================
// 7. CARGAR ARCHIVOS DE CONFIGURACIÓN
// ============================================================================

// Config está disponible globalmente mediante el helper App\Helpers\Config
// Los archivos de configuración se cargan bajo demanda

// ============================================================================
// 8. INICIAR SESIÓN
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    // Configurar parámetros de sesión
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    
    // Iniciar sesión
    session_start();
}

// Regenerar ID de sesión periódicamente (cada 30 minutos)
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// ============================================================================
// 9. CREAR ROUTER Y CARGAR RUTAS
// ============================================================================

use App\Core\Router;

$router = new Router();

// Cargar archivo de rutas
$webRoutes = ROUTES_PATH . '/web.php';
if (file_exists($webRoutes)) {
    require $webRoutes;
}

// ============================================================================
// 10. DESPACHAR PETICIÓN
// ============================================================================

try {
    // Obtener URI y método HTTP con validaciones
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = parse_url($requestUri, PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // Si el método es POST, verificar si hay _method para simular PUT/DELETE
    if ($method === 'POST' && isset($_POST['_method'])) {
        $method = strtoupper($_POST['_method']);
    }
    
    // Remover el subdirectorio si la app está en un subdirectorio
    $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptName !== '/' && str_starts_with($uri, $scriptName)) {
        $uri = substr($uri, strlen($scriptName));
    }
    
    // Asegurar que URI empiece con /
    if (empty($uri) || $uri[0] !== '/') {
        $uri = '/' . $uri;
    }
    
    // Despachar la ruta
    $router->dispatch($uri, $method);

} catch (Exception $e) {
    // Manejo global de excepciones
    http_response_code(500);
    
    if ($appDebug) {
        echo '<h1>Error Fatal</h1>';
        echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Archivo:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>Error 500</h1>';
        echo '<p>Ha ocurrido un error. Por favor contacte al administrador.</p>';
    }
    
    // Log del error
    error_log(sprintf(
        "Fatal Error: %s in %s:%d\nStack trace:\n%s",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));
}
