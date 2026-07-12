<?php
/**
 * Session Helper
 * 
 * Sistema de manejo de sesión con soporte para:
 * - Datos de sesión
 * - Mensajes flash
 * - Tokens CSRF
 * - Datos antiguos (old input)
 * - Errores de validación
 * 
 * @package RequisicionesMVC\Helpers
 * @version 2.0
 */

namespace App\Helpers;

class Session
{
    /**
     * Prefijo para las claves de sesión
     * 
     * @var string
     */
    private static $prefix = 'requisiciones_';

    /**
     * Indica si la sesión ya fue iniciada
     * 
     * @var bool
     */
    private static $started = false;

    /**
     * Inicia la sesión si no está iniciada
     * 
     * @return bool True si se inició correctamente
     */
    public static function start()
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return true;
        }

        // Configurar parámetros de sesión seguros
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Lax');

        self::$started = session_start();
        return self::$started;
    }

    /**
     * Guarda un valor en la sesión
     * 
     * @param string $key Clave
     * @param mixed $value Valor
     * @return void
     */
    public static function set($key, $value)
    {
        self::start();
        $_SESSION[self::$prefix . $key] = $value;
    }

    /**
     * Obtiene un valor de la sesión
     * 
     * @param string $key Clave
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        self::start();
        return $_SESSION[self::$prefix . $key] ?? $default;
    }

    /**
     * Verifica si existe una clave en la sesión
     * 
     * @param string $key Clave
     * @return bool
     */
    public static function has($key)
    {
        self::start();
        return isset($_SESSION[self::$prefix . $key]);
    }

    /**
     * Elimina un valor de la sesión
     * 
     * @param string $key Clave
     * @return void
     */
    public static function remove($key)
    {
        self::start();
        unset($_SESSION[self::$prefix . $key]);
    }

    /**
     * Elimina todos los valores de la sesión
     * 
     * @return void
     */
    public static function clear()
    {
        self::start();
        
        // Solo limpiar las claves con nuestro prefijo
        foreach ($_SESSION as $key => $value) {
            if (str_starts_with($key, self::$prefix)) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * Destruye completamente la sesión
     * 
     * @return void
     */
    public static function destroy()
    {
        self::start();

        // Limpiar todas las variables de sesión
        $_SESSION = [];

        // Destruir la cookie de sesión en múltiples rutas
        if (isset($_COOKIE[session_name()])) {
            $sessionName = session_name();
            $paths = ['/', '/requi/', '/requi'];
            
            foreach ($paths as $path) {
                setcookie(
                    $sessionName,
                    '',
                    time() - 42000,
                    $path,
                    '',
                    isset($_SERVER['HTTPS']),
                    true
                );
            }
        }

        // Regenerar el ID de sesión antes de destruir (seguridad adicional)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Destruir la sesión
        session_destroy();
        self::$started = false;

        // Limpiar superglobales relacionadas
        if (isset($_SESSION)) {
            unset($_SESSION);
        }
    }

    /**
     * Regenera el ID de sesión (seguridad)
     * 
     * @param bool $deleteOld Eliminar sesión antigua
     * @return bool
     */
    public static function regenerate($deleteOld = true)
    {
        self::start();
        return session_regenerate_id($deleteOld);
    }

    /**
     * Obtiene el ID de la sesión actual
     * 
     * @return string
     */
    public static function id()
    {
        self::start();
        return session_id();
    }

    // ========================================================================
    // MENSAJES FLASH
    // ========================================================================

    /**
     * Establece un mensaje flash
     * 
     * @param string $type Tipo de mensaje (success, error, warning, info)
     * @param string $message Mensaje
     * @return void
     */
    public static function flash($type, $message)
    {
        self::start();
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Obtiene y elimina el mensaje flash
     * 
     * @param string|null $type Tipo específico de mensaje (opcional)
     * @return array|string|null ['type' => '', 'message' => ''] o solo el mensaje si se especifica tipo
     */
    public static function getFlash($type = null)
    {
        self::start();
        
        if (!isset($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        
        // Si se solicita un tipo específico, devolver solo el mensaje si coincide
        if ($type !== null) {
            return ($flash['type'] === $type) ? $flash['message'] : null;
        }
        
        return $flash;
    }

    /**
     * Verifica si hay un mensaje flash
     * 
     * @param string|null $type Tipo específico a verificar (opcional)
     * @return bool
     */
    public static function hasFlash($type = null)
    {
        self::start();
        
        if (!isset($_SESSION['flash'])) {
            return false;
        }
        
        // Si se solicita un tipo específico, verificar si coincide
        if ($type !== null) {
            return $_SESSION['flash']['type'] === $type;
        }
        
        return true;
    }

    /**
     * Mensajes flash de tipo específico
     */
    public static function success($message)
    {
        self::flash('success', $message);
    }

    public static function error($message)
    {
        self::flash('error', $message);
    }

    public static function warning($message)
    {
        self::flash('warning', $message);
    }

    public static function info($message)
    {
        self::flash('info', $message);
    }

    // ========================================================================
    // ERRORES DE VALIDACIÓN
    // ========================================================================

    /**
     * Guarda errores de validación
     * 
     * @param array $errors Errores ['campo' => 'mensaje']
     * @return void
     */
    public static function setErrors($errors)
    {
        self::start();
        $_SESSION['errors'] = $errors;
    }

    /**
     * Obtiene errores de validación
     * 
     * @param string|null $field Campo específico (null para todos)
     * @return array|string|null
     */
    public static function getErrors($field = null)
    {
        self::start();

        if (!isset($_SESSION['errors'])) {
            return $field ? null : [];
        }

        $errors = $_SESSION['errors'];

        if ($field !== null) {
            return $errors[$field] ?? null;
        }

        return $errors;
    }

    /**
     * Verifica si hay errores de validación
     * 
     * @param string|null $field Campo específico
     * @return bool
     */
    public static function hasErrors($field = null)
    {
        self::start();

        if (!isset($_SESSION['errors'])) {
            return false;
        }

        if ($field !== null) {
            return isset($_SESSION['errors'][$field]);
        }

        return !empty($_SESSION['errors']);
    }

    /**
     * Limpia los errores de validación
     * 
     * @return void
     */
    public static function clearErrors()
    {
        self::start();
        unset($_SESSION['errors']);
    }

    // ========================================================================
    // OLD INPUT (datos anteriores)
    // ========================================================================

    /**
     * Guarda los datos del input anterior
     * 
     * @param array $data Datos del formulario
     * @return void
     */
    public static function setOldInput($data)
    {
        self::start();
        $_SESSION['old'] = $data;
    }

    /**
     * Obtiene un valor del input anterior
     * 
     * @param string $key Clave
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public static function old($key, $default = '')
    {
        self::start();

        if (!isset($_SESSION['old'])) {
            return $default;
        }

        $value = $_SESSION['old'][$key] ?? $default;
        
        return $value;
    }

    /**
     * Limpia los datos antiguos
     * 
     * @return void
     */
    public static function clearOldInput()
    {
        self::start();
        unset($_SESSION['old']);
    }

    // ========================================================================
    // AUTENTICACIÓN
    // ========================================================================

    /**
     * Guarda los datos del usuario autenticado
     * 
     * @param array $user Datos del usuario
     * @return void
     */
    public static function setUser($user)
    {
        self::start();
        
        // Guardar como array estructurado para consistencia
        $_SESSION['user'] = [
            'id' => $user['id'] ?? null,
            'email' => $user['email'] ?? null,
            'name' => $user['name'] ?? null,
            'azure_id' => $user['azure_id'] ?? null,
            'azure_token' => $user['azure_token'] ?? null,
            'is_revisor' => $user['is_revisor'] ?? $user['es_revisor'] ?? 0,
            'is_admin' => $user['is_admin'] ?? $user['es_admin'] ?? 0,
            'is_autorizador' => $user['is_autorizador'] ?? $user['es_autorizador'] ?? 0,
            'last_activity' => time()
        ];

        // Mantener compatibilidad con métodos legacy
        $_SESSION['user_id'] = $user['id'] ?? null;
        $_SESSION['user_email'] = $user['email'] ?? null;
        $_SESSION['user_name'] = $user['name'] ?? null;
        $_SESSION['azure_id'] = $user['azure_id'] ?? null;
        $_SESSION['azure_token'] = $user['azure_token'] ?? null;
        $_SESSION['is_revisor'] = $user['is_revisor'] ?? $user['es_revisor'] ?? 0;
        $_SESSION['is_admin'] = $user['is_admin'] ?? $user['es_admin'] ?? 0;
        $_SESSION['is_autorizador'] = $user['is_autorizador'] ?? $user['es_autorizador'] ?? 0;
        $_SESSION['last_activity'] = time();

        // Regenerar ID por seguridad
        self::regenerate();
    }

    /**
     * Obtiene el usuario autenticado
     * 
     * @return array|null
     */
    public static function getUser()
    {
        self::start();

        // Priorizar la estructura nueva
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        // Fallback a estructura legacy
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'azure_id' => $_SESSION['azure_id'] ?? null,
            'is_revisor' => $_SESSION['is_revisor'] ?? 0,
            'is_admin' => $_SESSION['is_admin'] ?? 0,
            'is_autorizador' => $_SESSION['is_autorizador'] ?? 0,
        ];
    }

    /**
     * Verifica si hay un usuario autenticado
     * 
     * @return bool
     */
    public static function isAuthenticated()
    {
        self::start();
        
        // Verificar estructura nueva primero
        if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
            // Verificar que la sesión no haya expirado (24 horas)
            $lastActivity = $_SESSION['user']['last_activity'] ?? 0;
            if (time() - $lastActivity > 86400) { // 24 horas
                self::logout();
                return false;
            }
            return true;
        }
        
        // Fallback a estructura legacy
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Cierra la sesión del usuario
     * 
     * @return void
     */
    public static function logout()
    {
        self::destroy();
    }

    /**
     * Actualiza la actividad del usuario
     * 
     * @return void
     */
    public static function updateActivity()
    {
        self::start();
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['last_activity'] = time();
        }
        $_SESSION['last_activity'] = time();
    }

    /**
     * Verifica si el usuario tiene un rol específico
     * 
     * @param string $role Rol a verificar (admin, revisor, autorizador)
     * @return bool
     */
    public static function hasRole($role)
    {
        $user = self::getUser();
        if (!$user) {
            return false;
        }

        switch (strtolower($role)) {
            case 'admin':
                return (bool)($user['is_admin'] ?? false);
            case 'revisor':
                return (bool)($user['is_revisor'] ?? false);
            case 'autorizador':
                return (bool)($user['is_autorizador'] ?? false);
            default:
                return false;
        }
    }

    /**
     * Obtiene los roles del usuario actual
     * 
     * @return array
     */
    public static function getUserRoles()
    {
        $user = self::getUser();
        if (!$user) {
            return [];
        }

        $roles = [];
        if ($user['is_admin'] ?? false) {
            $roles[] = 'admin';
        }
        if ($user['is_revisor'] ?? false) {
            $roles[] = 'revisor';
        }
        if ($user['is_autorizador'] ?? false) {
            $roles[] = 'autorizador';
        }

        return $roles;
    }

    /**
     * Verifica si el usuario es administrador
     * 
     * @return bool
     */
    public static function isAdmin()
    {
        return self::hasRole('admin');
    }

    /**
     * Verifica si el usuario es revisor
     * 
     * @return bool
     */
    public static function isRevisor()
    {
        return self::hasRole('revisor');
    }

    /**
     * Verifica si el usuario es autorizador
     * 
     * @return bool
     */
    public static function isAutorizador()
    {
        return self::hasRole('autorizador');
    }

    // ========================================================================
    // UTILIDADES
    // ========================================================================

    /**
     * Guarda la URL de redirección después del login
     * 
     * @param string $url URL
     * @return void
     */
    public static function setIntendedUrl($url)
    {
        self::start();
        $_SESSION['intended_url'] = $url;
    }

    /**
     * Obtiene y elimina la URL de redirección
     * 
     * @param string $default URL por defecto
     * @return string
     */
    public static function getIntendedUrl($default = '/')
    {
        self::start();

        if (!isset($_SESSION['intended_url'])) {
            return $default;
        }

        $url = $_SESSION['intended_url'];
        unset($_SESSION['intended_url']);

        return $url;
    }

    /**
     * Obtiene toda la información de la sesión (para debugging)
     * 
     * @return array
     */
    public static function all()
    {
        self::start();
        return $_SESSION;
    }

    /**
     * Guarda múltiples valores
     * 
     * @param array $data Array de datos ['key' => 'value']
     * @return void
     */
    public static function setMultiple($data)
    {
        self::start();
        
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * Obtiene múltiples valores
     * 
     * @param array $keys Array de claves
     * @return array
     */
    public static function getMultiple($keys)
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = self::get($key);
        }
        
        return $result;
    }

    /**
     * Incrementa un valor numérico en la sesión
     * 
     * @param string $key Clave
     * @param int $amount Cantidad a incrementar
     * @return int Nuevo valor
     */
    public static function increment($key, $amount = 1)
    {
        $current = (int) self::get($key, 0);
        $new = $current + $amount;
        self::set($key, $new);
        
        return $new;
    }

    /**
     * Decrementa un valor numérico en la sesión
     * 
     * @param string $key Clave
     * @param int $amount Cantidad a decrementar
     * @return int Nuevo valor
     */
    public static function decrement($key, $amount = 1)
    {
        return self::increment($key, -$amount);
    }

    /**
     * Agrega un elemento a un array en la sesión
     * 
     * @param string $key Clave del array
     * @param mixed $value Valor a agregar
     * @return void
     */
    public static function push($key, $value)
    {
        $array = self::get($key, []);
        
        if (!is_array($array)) {
            $array = [];
        }
        
        $array[] = $value;
        self::set($key, $array);
    }

    /**
     * Elimina un elemento de un array en la sesión
     * 
     * @param string $key Clave del array
     * @param mixed $value Valor a eliminar
     * @return void
     */
    public static function pull($key, $value)
    {
        $array = self::get($key, []);
        
        if (!is_array($array)) {
            return;
        }
        
        $array = array_diff($array, [$value]);
        self::set($key, array_values($array));
    }
}
