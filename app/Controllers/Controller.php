<?php
/**
 * Controller
 * 
 * Clase base para todos los controladores.
 * Proporciona métodos helper comunes para autenticación, vistas, respuestas, etc.
 * 
 * @package RequisicionesMVC\Controllers
 * @version 2.0
 */

namespace App\Controllers;

use App\Helpers\Config;
use App\Helpers\Redirect;

abstract class Controller
{
    /**
     * Sesión del usuario
     * 
     * @var array|null
     */
    protected $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->session = $_SESSION ?? [];
    }

    /**
     * Verifica si el usuario está autenticado
     * 
     * @return bool
     */
    protected function isAuthenticated()
    {
        // Usar $_SESSION directamente
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtiene el ID del usuario autenticado
     * 
     * @return int|null
     */
    protected function getUsuarioId()
    {
        // IMPORTANTE: Usar $_SESSION directamente para obtener datos actualizados
        
        // Priorizar el ID de la estructura nueva del usuario
        if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
            return $_SESSION['user']['id'];
        }
        
        // Fallback a la estructura legacy
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Obtiene el email del usuario autenticado
     * 
     * @return string|null
     */
    protected function getUsuarioEmail()
    {
        // IMPORTANTE: Usar $_SESSION directamente para obtener datos actualizados
        // (no usar $this->session que puede estar desactualizado)
        
        // Priorizar el email de la estructura nueva del usuario
        if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['email'])) {
            return $_SESSION['user']['email'];
        }
        
        // Fallback a la estructura legacy
        return $_SESSION['user_email'] ?? null;
    }

    /**
     * Obtiene el email del usuario autenticado o fuerza reautenticación.
     *
     * @return string
     */
    protected function requireUsuarioEmail()
    {
        $email = $this->getUsuarioEmail();

        if (!empty($email)) {
            return $email;
        }

        error_log(static::class . ': requireUsuarioEmail() - email no disponible, redirigiendo a login');

        Redirect::to('/login')
            ->withError('Tu sesión ha expirado. Inicia sesión nuevamente para continuar.')
            ->send();
        exit;
    }

    /**
     * Obtiene el nombre del usuario autenticado
     * 
     * @return string|null
     */
    protected function getUsuarioNombre()
    {
        return $this->session['user_name'] ?? null;
    }

    /**
     * Verifica si el usuario es revisor
     * 
     * @return bool
     */
    protected function isRevisor()
    {
        return \App\Helpers\Session::isRevisor();
    }

    /**
     * Verifica si el usuario es administrador
     * 
     * @return bool
     */
    protected function isAdmin()
    {
        return \App\Helpers\Session::isAdmin();
    }

    /**
     * Renderiza una vista
     * 
     * @param string $view Nombre de la vista (sin extensión)
     * @param array $data Datos para la vista
     * @return void
     */
    protected function view($view, $data = [])
    {
        // Extraer variables para la vista
        extract($data);
        
        // Incluir el archivo de vista
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            error_log("Vista no encontrada: {$viewPath}");
            $this->error('Vista no encontrada', 404);
        }
    }

    /**
     * Redirige a una URL
     * 
     * @param string $url URL de destino
     * @param int $statusCode Código de estado HTTP
     * @return void
     */
    protected function redirect($url, $statusCode = 302)
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }

    /**
     * Devuelve una respuesta JSON
     * 
     * @param array $data Datos a devolver
     * @param int $statusCode Código de estado HTTP
     * @return void
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        // Limpiar cualquier output previo que pueda contaminar la respuesta
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Envía respuesta AJAX limpia (método mejorado para operaciones críticas)
     * Limpia buffers, suprime errores y asegura headers limpios
     * 
     * @param array $response Datos a devolver
     * @return void
     */
    protected function sendAjaxResponse($response)
    {
        try {
            error_log("=== SENDAJAXRESPONSE DEBUG ===");
            error_log("Response data: " . json_encode($response, JSON_UNESCAPED_UNICODE));
            error_log("Headers sent: " . (headers_sent() ? 'YES' : 'NO'));
            error_log("OB level: " . ob_get_level());
            
            // Limpiar cualquier output buffer existente
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Configurar cabeceras solo si no se han enviado ya
            if (!headers_sent()) {
                http_response_code(200);
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-cache, must-revalidate');
                error_log("Headers set successfully");
            } else {
                error_log("WARNING: Headers already sent");
            }
            
            $json = json_encode($response, JSON_UNESCAPED_UNICODE);
            error_log("JSON output: " . $json);
            error_log("JSON error: " . json_last_error_msg());
            
            echo $json;
            error_log("Response sent successfully");
            exit;
        } catch (\Exception $e) {
            error_log("ERROR in sendAjaxResponse: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            
            // Fallback response
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['success' => false, 'error' => 'Internal server error']);
            exit;
        }
    }

    /**
     * Muestra una página de error
     * 
     * @param string $message Mensaje de error
     * @param int $statusCode Código de estado HTTP
     * @return void
     */
    protected function error($message, $statusCode = 500)
    {
        http_response_code($statusCode);
        
        // Si es AJAX, devolver JSON
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => false,
                'error' => $message
            ], $statusCode);
            return;
        }
        
        // Mostrar vista de error
        $this->view('errors/error', [
            'message' => $message,
            'code' => $statusCode
        ]);
    }

    /**
     * Verifica si es una petición AJAX
     * 
     * @return bool
     */
    protected function isAjaxRequest()
    {
        // Verificar cabecera X-Requested-With
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        
        // Verificar Content-Type application/json
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            return true;
        }
        
        // Verificar Accept header que incluya application/json
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }
        
        return false;
    }

    /**
     * Valida el token CSRF
     * 
     * @return bool
     */
    protected function validateCSRF()
    {
        // Buscar token en múltiples ubicaciones
        $token = $_POST['_token'] ?? 
                 $_GET['_token'] ?? 
                 $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
                 '';
        
        // Usar $_SESSION directamente
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }

    /**
     * Genera un token CSRF
     * 
     * @return string
     */
    protected function generateCSRFToken()
    {
        if (!isset($this->session['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Obtiene un valor de la sesión
     * 
     * @param string $key Clave
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    protected function getSession($key, $default = null)
    {
        return $this->session[$key] ?? $default;
    }

    /**
     * Establece un valor en la sesión
     * 
     * @param string $key Clave
     * @param mixed $value Valor
     * @return void
     */
    protected function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
        $this->session[$key] = $value;
    }

    /**
     * Elimina un valor de la sesión
     * 
     * @param string $key Clave
     * @return void
     */
    protected function unsetSession($key)
    {
        unset($_SESSION[$key]);
        unset($this->session[$key]);
    }

    /**
     * Establece un mensaje flash
     * 
     * @param string $type Tipo de mensaje (success, error, warning, info)
     * @param string $message Mensaje
     * @return void
     */
    protected function setFlash($type, $message)
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Obtiene y elimina el mensaje flash
     * 
     * @return array|null
     */
    protected function getFlash()
    {
        $flash = $this->session['flash'] ?? null;
        
        if ($flash) {
            unset($_SESSION['flash']);
        }
        
        return $flash;
    }

    /**
     * Obtiene datos del request
     * 
     * @param string $key Clave (opcional)
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    protected function input($key = null, $default = null)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        $data = [];
        if ($method === 'POST') {
            $data = $_POST;
        } elseif ($method === 'GET') {
            $data = $_GET;
        }
        
        if ($key === null) {
            return $data;
        }
        
        return $data[$key] ?? $default;
    }

    /**
     * Valida que campos requeridos existan
     * 
     * @param array $required Campos requeridos
     * @param array $data Datos a validar
     * @return array Errores de validación
     */
    protected function validateRequired($required, $data)
    {
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "El campo {$field} es requerido";
            }
        }
        
        return $errors;
    }

    /**
     * Sanitiza una cadena de texto
     * 
     * @param string $string Cadena a sanitizar
     * @return string
     */
    protected function sanitize($string)
    {
        return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Destruye la sesión (logout)
     * 
     * @return void
     */
    protected function destroySession()
    {
        session_destroy();
        $this->session = [];
    }

    /**
     * Verifica si un usuario es revisor por su email
     * 
     * @param string $usuarioEmail
     * @return bool
     */
    protected function isRevisorPorEmail($usuarioEmail)
    {
        // Solo verificar el flag is_revisor de la base de datos (vía sesión)
        // Ya no se usa verificación por dominio de email
        return $this->isRevisor();
    }
}
