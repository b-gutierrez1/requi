<?php
/**
 * Redirect Helper
 * 
 * Sistema de redirecciones con soporte para:
 * - Redirecciones simples
 * - Redirecciones con mensajes flash
 * - Redirecciones con datos antiguos
 * - Redirecciones con errores
 * - Rutas nombradas
 * 
 * @package RequisicionesMVC\Helpers
 * @version 2.0
 */

namespace App\Helpers;

class Redirect
{
    /**
     * URL de redirección
     * 
     * @var string
     */
    private $url;

    /**
     * Código de estado HTTP
     * 
     * @var int
     */
    private $statusCode = 302;

    /**
     * Datos flash a guardar
     * 
     * @var array
     */
    private $flashData = [];

    /**
     * Datos antiguos a guardar
     * 
     * @var array
     */
    private $oldData = [];

    /**
     * Errores a guardar
     * 
     * @var array
     */
    private $errors = [];

    /**
     * Constructor privado (usar métodos estáticos)
     * 
     * @param string $url URL de redirección
     */
    private function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Crea una redirección a una URL
     * 
     * @param string $url URL de destino
     * @return self
     */
    public static function to($url)
    {
        return new self($url);
    }

    /**
     * Redirige a una ruta con nombre
     * 
     * @param string $name Nombre de la ruta
     * @param array $params Parámetros para la ruta
     * @return self
     */
    public static function route($name, $params = [])
    {
        // TODO: Implementar sistema de rutas nombradas
        // Por ahora, simplemente usamos la URL directamente
        $url = $name;
        
        // Reemplazar parámetros en la URL
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        
        return new self($url);
    }

    /**
     * Redirige al home
     * 
     * @return self
     */
    public static function home()
    {
        return new self('/');
    }

    /**
     * Redirige al dashboard
     * 
     * @return self
     */
    public static function dashboard()
    {
        return new self('/dashboard');
    }

    /**
     * Redirige a la página anterior
     * 
     * @return self
     */
    public static function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return new self($referer);
    }

    /**
     * Redirige a la URL de redirección guardada
     * 
     * @param string $default URL por defecto si no hay guardada
     * @return self
     */
    public static function intended($default = '/')
    {
        $url = Session::getIntendedUrl($default);
        return new self($url);
    }

    /**
     * Agrega un mensaje flash de éxito
     * 
     * @param string $message Mensaje
     * @return self
     */
    public function withSuccess($message)
    {
        $this->flashData = [
            'type' => 'success',
            'message' => $message
        ];
        
        return $this;
    }

    /**
     * Agrega un mensaje flash de error
     * 
     * @param string $message Mensaje
     * @return self
     */
    public function withError($message)
    {
        $this->flashData = [
            'type' => 'error',
            'message' => $message
        ];
        
        return $this;
    }

    /**
     * Agrega un mensaje flash de advertencia
     * 
     * @param string $message Mensaje
     * @return self
     */
    public function withWarning($message)
    {
        $this->flashData = [
            'type' => 'warning',
            'message' => $message
        ];
        
        return $this;
    }

    /**
     * Agrega un mensaje flash de información
     * 
     * @param string $message Mensaje
     * @return self
     */
    public function withInfo($message)
    {
        $this->flashData = [
            'type' => 'info',
            'message' => $message
        ];
        
        return $this;
    }

    /**
     * Agrega un mensaje flash personalizado
     * 
     * @param string $type Tipo de mensaje
     * @param string $message Mensaje
     * @return self
     */
    public function withFlash($type, $message)
    {
        $this->flashData = [
            'type' => $type,
            'message' => $message
        ];
        
        return $this;
    }

    /**
     * Guarda errores de validación
     * 
     * @param array $errors Errores ['campo' => 'mensaje']
     * @return self
     */
    public function withErrors($errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Guarda los datos del input anterior
     * 
     * @param array $data Datos del formulario
     * @return self
     */
    public function withInput($data = null)
    {
        if ($data === null) {
            // Usar datos del POST
            $data = $_POST;
        }
        
        $this->oldData = $data;
        return $this;
    }

    /**
     * Guarda datos en la sesión
     * 
     * @param string|array $key Clave o array de datos
     * @param mixed $value Valor (si $key es string)
     * @return self
     */
    public function with($key, $value = null)
    {
        Session::start();
        
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Session::set($k, $v);
            }
        } else {
            Session::set($key, $value);
        }
        
        return $this;
    }

    /**
     * Establece el código de estado HTTP
     * 
     * @param int $code Código de estado
     * @return self
     */
    public function withStatus($code)
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Establece redirección permanente (301)
     * 
     * @return self
     */
    public function permanent()
    {
        $this->statusCode = 301;
        return $this;
    }

    /**
     * Ejecuta la redirección
     * 
     * @return void
     */
    public function send()
    {
        Session::start();

        // Guardar mensaje flash
        if (!empty($this->flashData)) {
            Session::flash($this->flashData['type'], $this->flashData['message']);
        }

        // Guardar errores
        if (!empty($this->errors)) {
            Session::setErrors($this->errors);
        }

        // Guardar datos antiguos
        if (!empty($this->oldData)) {
            Session::setOldInput($this->oldData);
        }

        // Realizar la redirección
        http_response_code($this->statusCode);
        header('Location: ' . $this->url);
        exit;
    }

    /**
     * Alias de send() - Ejecuta la redirección
     * 
     * @return void
     */
    public function go()
    {
        $this->send();
    }

    /**
     * Redirección rápida sin configuración adicional
     * 
     * @param string $url URL de destino
     * @param int $statusCode Código de estado
     * @return void
     */
    public static function now($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Redirección con mensaje de éxito (atajo)
     * 
     * @param string $url URL de destino
     * @param string $message Mensaje de éxito
     * @return void
     */
    public static function success($url, $message)
    {
        self::to($url)->withSuccess($message)->send();
    }

    /**
     * Redirección con mensaje de error (atajo)
     * 
     * @param string $url URL de destino
     * @param string $message Mensaje de error
     * @return void
     */
    public static function error($url, $message)
    {
        self::to($url)->withError($message)->send();
    }

    /**
     * Redirección atrás con errores e input (atajo)
     * 
     * @param array $errors Errores de validación
     * @param array|null $input Datos del formulario
     * @return void
     */
    public static function backWithErrors($errors, $input = null)
    {
        $redirect = self::back()->withErrors($errors);
        
        if ($input !== null) {
            $redirect->withInput($input);
        } else {
            $redirect->withInput();
        }
        
        $redirect->send();
    }

    /**
     * Redirección al login
     * 
     * @param string|null $message Mensaje opcional
     * @return void
     */
    public static function toLogin($message = null)
    {
        $redirect = self::to('/login');
        
        if ($message) {
            $redirect->withWarning($message);
        }
        
        $redirect->send();
    }

    /**
     * Redirección después del login
     * 
     * @param string $default URL por defecto
     * @return void
     */
    public static function afterLogin($default = '/dashboard')
    {
        self::intended($default)->send();
    }

    /**
     * Recarga la página actual
     * 
     * @return void
     */
    public static function refresh()
    {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
        self::now($currentUrl);
    }

    /**
     * Construye una URL con query string
     * 
     * @param string $url URL base
     * @param array $params Parámetros
     * @return string URL completa
     */
    public static function buildUrl($url, $params = [])
    {
        if (empty($params)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . http_build_query($params);
    }

    /**
     * Verifica si una URL es externa
     * 
     * @param string $url URL a verificar
     * @return bool
     */
    public static function isExternal($url)
    {
        // Si empieza con protocolo, es externa
        if (preg_match('/^https?:\/\//', $url)) {
            return true;
        }

        // Si empieza con //, es externa
        if (str_starts_with($url, '//')) {
            return true;
        }

        return false;
    }

    /**
     * Normaliza una URL
     * 
     * @param string $url URL a normalizar
     * @return string URL normalizada
     */
    public static function normalize($url)
    {
        // Si es URL externa, retornar tal cual
        if (self::isExternal($url)) {
            return $url;
        }

        // Asegurar que empiece con /
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        // Remover múltiples /
        $url = preg_replace('#/+#', '/', $url);

        return $url;
    }

    /**
     * Obtiene la URL actual
     * 
     * @param bool $withQuery Incluir query string
     * @return string
     */
    public static function current($withQuery = true)
    {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        
        if (!$withQuery) {
            $url = strtok($url, '?');
        }
        
        return $url;
    }

    /**
     * Obtiene la URL anterior (referer)
     * 
     * @return string|null
     */
    public static function previous()
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }
}
