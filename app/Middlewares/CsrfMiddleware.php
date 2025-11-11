<?php
/**
 * CsrfMiddleware
 * 
 * Middleware para proteger contra ataques CSRF (Cross-Site Request Forgery).
 * Genera y valida tokens CSRF para todas las peticiones POST, PUT, DELETE.
 * 
 * @package RequisicionesMVC\Middlewares
 * @version 2.0
 */

namespace App\Middlewares;

class CsrfMiddleware
{
    /**
     * Nombre de la sesión del token CSRF
     * 
     * @var string
     */
    private $tokenName = 'csrf_token';

    /**
     * Métodos HTTP que requieren validación CSRF
     * 
     * @var array
     */
    private $protectedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * Rutas excluidas de la validación CSRF
     * 
     * @var array
     */
    private $excludedRoutes = [
        '/auth/callback',  // Callback de Azure
        '/api/webhook',    // Webhooks externos
    ];

    /**
     * Maneja la validación CSRF
     * 
     * @return bool True si pasa la validación, false si falla
     */
    public function handle()
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generar token si no existe
        $this->ensureTokenExists();

        // Obtener método HTTP
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Si el método no requiere protección, permitir
        if (!in_array($method, $this->protectedMethods)) {
            return true;
        }

        // Verificar si la ruta está excluida
        if ($this->isExcludedRoute()) {
            return true;
        }

        // Validar el token CSRF
        if (!$this->validateToken()) {
            $this->handleInvalidToken();
            return false;
        }

        return true;
    }

    /**
     * Asegura que existe un token CSRF en la sesión
     * 
     * @return void
     */
    private function ensureTokenExists()
    {
        if (!isset($_SESSION[$this->tokenName])) {
            $_SESSION[$this->tokenName] = $this->generateToken();
        }
    }

    /**
     * Genera un nuevo token CSRF
     * 
     * @return string Token generado
     */
    private function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Valida el token CSRF de la petición
     * 
     * @return bool True si el token es válido
     */
    private function validateToken()
    {
        // Obtener token de la sesión
        $sessionToken = $_SESSION[$this->tokenName] ?? null;

        if (!$sessionToken) {
            return false;
        }

        // Obtener token de la petición
        $requestToken = $this->getRequestToken();

        if (!$requestToken) {
            return false;
        }

        // Comparación segura contra timing attacks
        return hash_equals($sessionToken, $requestToken);
    }

    /**
     * Obtiene el token CSRF de la petición
     * 
     * @return string|null Token de la petición
     */
    private function getRequestToken()
    {
        // Prioridad 1: Header HTTP (para AJAX)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // Prioridad 2: POST data
        if (isset($_POST['_token'])) {
            return $_POST['_token'];
        }

        // Prioridad 3: JSON body (para APIs)
        if ($this->isJsonRequest()) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (isset($json['_token'])) {
                return $json['_token'];
            }
        }

        // Prioridad 4: Query string (menos común, menos seguro)
        if (isset($_GET['_token'])) {
            return $_GET['_token'];
        }

        return null;
    }

    /**
     * Verifica si la petición es JSON
     * 
     * @return bool
     */
    private function isJsonRequest()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($contentType, 'application/json');
    }

    /**
     * Verifica si la ruta actual está excluida de la validación
     * 
     * @return bool
     */
    private function isExcludedRoute()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = strtok($uri, '?'); // Remover query string

        foreach ($this->excludedRoutes as $excludedRoute) {
            if (str_starts_with($uri, $excludedRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Maneja un token CSRF inválido
     * 
     * @return void
     */
    private function handleInvalidToken()
    {
        http_response_code(419); // Page Expired (similar a Laravel)

        // Registrar el intento para auditoría
        $this->logCsrfAttempt();

        // Responder según el tipo de petición
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Token CSRF inválido o expirado',
                'code' => 'CSRF_TOKEN_MISMATCH'
            ]);
        } else {
            echo '<h1>419 - Página Expirada</h1>';
            echo '<p>Su sesión ha expirado. Por favor recargue la página e intente nuevamente.</p>';
            echo '<a href="javascript:history.back()">Volver atrás</a>';
        }

        exit;
    }

    /**
     * Registra un intento de CSRF para auditoría
     * 
     * @return void
     */
    private function logCsrfAttempt()
    {
        $logMessage = sprintf(
            "CSRF Token Mismatch - IP: %s, URI: %s, Method: %s, User: %s",
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['REQUEST_URI'] ?? 'unknown',
            $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            $_SESSION['user_email'] ?? 'guest'
        );

        error_log($logMessage);
    }

    /**
     * Verifica si es una petición AJAX
     * 
     * @return bool
     */
    private function isAjaxRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Obtiene el token CSRF actual (para usar en vistas)
     * 
     * @return string
     */
    public static function getToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Genera un campo hidden HTML con el token CSRF
     * 
     * @return string HTML del campo hidden
     */
    public static function field()
    {
        $token = self::getToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Genera un meta tag HTML con el token CSRF (para AJAX)
     * 
     * @return string HTML del meta tag
     */
    public static function metaTag()
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }

    /**
     * Regenera el token CSRF (útil después de login/logout)
     * 
     * @return string Nuevo token
     */
    public static function regenerate()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Excluye una ruta de la validación CSRF
     * 
     * @param string $route Ruta a excluir
     * @return void
     */
    public function excludeRoute($route)
    {
        if (!in_array($route, $this->excludedRoutes)) {
            $this->excludedRoutes[] = $route;
        }
    }

    /**
     * Excluye múltiples rutas de la validación CSRF
     * 
     * @param array $routes Rutas a excluir
     * @return void
     */
    public function excludeRoutes($routes)
    {
        foreach ($routes as $route) {
            $this->excludeRoute($route);
        }
    }
}
