<?php
/**
 * AuthMiddleware
 * 
 * Middleware para verificar que el usuario está autenticado.
 * Verifica la sesión de Azure AD y redirige al login si no está autenticado.
 * 
 * @package RequisicionesMVC\Middlewares
 * @version 2.0
 */

namespace App\Middlewares;

use App\Helpers\Config;
use App\Helpers\Session;

class AuthMiddleware
{
    /**
     * Rutas que no requieren autenticación
     * 
     * @var array
     */
    private $publicRoutes = [
        '/',
        '/login',
        '/auth/azure',
        '/auth/callback',
        '/auth/refresh-session',
        '/logout',
        '/test',
        '/test/',
        '/debug_session_browser.php'
    ];

    /**
     * Maneja la verificación de autenticación
     * 
     * @return bool True si está autenticado, redirecciona si no
     */
    public function handle()
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar si la ruta actual es pública
        $currentRoute = $this->getCurrentRoute();
        
        if ($this->isPublicRoute($currentRoute)) {
            return true;
        }

        // Verificar si hay sesión de usuario
        if (!$this->isAuthenticated()) {
            $this->redirectToLogin();
            return false;
        }

        // Agregar headers para prevenir cache en páginas autenticadas
        $this->setNoCacheHeaders();

        // Verificar que la sesión no haya expirado
        if ($this->isSessionExpired()) {
            $this->logout();
            $this->redirectToLogin('Sesión expirada. Por favor inicie sesión nuevamente.');
            return false;
        }

        // Actualizar actividad del usuario (después de validar expiración)
        Session::updateActivity();

        return true;
    }

    /**
     * Verifica si el usuario está autenticado
     * 
     * @return bool
     */
    private function isAuthenticated()
    {
        return isset($_SESSION['user_id']) && 
               !empty($_SESSION['user_id']) &&
               isset($_SESSION['azure_token']) &&
               !empty($_SESSION['azure_token']);
    }

    /**
     * Verifica si la sesión ha expirado por inactividad
     * 
     * @return bool
     */
    private function isSessionExpired()
    {
        // Tiempo máximo de inactividad en segundos (minutos en config)
        $lifetimeMinutes = Config::get('app.session.lifetime', null);
        if ($lifetimeMinutes === null) {
            $lifetimeMinutes = Config::get('app.security.session_timeout', 30);
        }
        $maxInactivity = (int) $lifetimeMinutes * 60;

        if (!isset($_SESSION['last_activity'])) {
            return false;
        }

        $inactiveTime = time() - $_SESSION['last_activity'];
        
        return $inactiveTime > $maxInactivity;
    }



    /**
     * Obtiene la ruta actual
     * 
     * @return string
     */
    private function getCurrentRoute()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remover query string
        $uri = strtok($uri, '?');
        
        // Remover subdirectorio si existe
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && str_starts_with($uri, $scriptName)) {
            $uri = substr($uri, strlen($scriptName));
        }

        // Asegurar que empiece con /
        if (empty($uri) || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    /**
     * Verifica si una ruta es pública (no requiere autenticación)
     * 
     * @param string $route Ruta a verificar
     * @return bool
     */
    private function isPublicRoute($route)
    {
        // Verificar rutas exactas
        if (in_array($route, $this->publicRoutes)) {
            return true;
        }

        // Verificar rutas con parámetros (test/{param})
        foreach ($this->publicRoutes as $publicRoute) {
            if (str_starts_with($route, rtrim($publicRoute, '/'))) {
                return true;
            }
        }

        // Verificar rutas de archivos estáticos
        if ($this->isStaticFile($route)) {
            return true;
        }

        return false;
    }

    /**
     * Verifica si la ruta es un archivo estático
     * 
     * @param string $route Ruta a verificar
     * @return bool
     */
    private function isStaticFile($route)
    {
        $staticExtensions = [
            '.css', '.js', '.jpg', '.jpeg', '.png', '.gif', 
            '.svg', '.ico', '.woff', '.woff2', '.ttf', '.eot'
        ];

        foreach ($staticExtensions as $extension) {
            if (str_ends_with($route, $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirige al login
     * 
     * @param string|null $message Mensaje flash opcional
     * @return void
     */
    private function redirectToLogin($message = null)
    {
        if ($message) {
            $_SESSION['flash'] = [
                'type' => 'warning',
                'message' => $message
            ];
        }

        // Guardar la URL original para redirigir después del login
        if (!isset($_SESSION['redirect_after_login'])) {
            $_SESSION['redirect_after_login'] = $this->getCurrentRoute();
        }

        // Verificar si es una petición AJAX
        if ($this->isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'No autenticado',
                'redirect' => \App\Helpers\Redirect::url('/login')
            ]);
            exit;
        }

        // Redirección normal usando helper url() que considera el subdirectorio
        header('Location: ' . \App\Helpers\Redirect::url('/login'));
        exit;
    }

    /**
     * Cierra la sesión del usuario
     * 
     * @return void
     */
    private function logout()
    {
        // Limpiar todas las variables de sesión
        $_SESSION = [];

        // Destruir la cookie de sesión
        if (isset($_COOKIE[session_name()])) {
            setcookie(
                session_name(), 
                '', 
                time() - 42000, 
                '/'
            );
        }

        // Destruir la sesión
        session_destroy();
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
     * Obtiene información del usuario autenticado
     * 
     * @return array|null
     */
    public static function getUser()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'azure_id' => $_SESSION['azure_id'] ?? null,
            'is_revisor' => $_SESSION['is_revisor'] ?? 0,
            'is_admin' => $_SESSION['is_admin'] ?? 0
        ];
    }

    /**
     * Verifica si el usuario tiene un rol específico
     * 
     * @param string $role Rol a verificar (revisor, admin)
     * @return bool
     */
    public static function hasRole($role)
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        switch ($role) {
            case 'revisor':
                return \App\Helpers\Session::isRevisor();
            
            case 'admin':
                return \App\Helpers\Session::isAdmin();
            
            default:
                return false;
        }
    }

    /**
     * Establece headers para prevenir cache en páginas autenticadas
     * 
     * @return void
     */
    private function setNoCacheHeaders()
    {
        // Headers para prevenir cache
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        // Header adicional para IE
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    }
}

