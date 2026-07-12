<?php
/**
 * View Helper
 * 
 * Sistema de renderizado de vistas con soporte para layouts,
 * secciones, componentes y datos compartidos.
 * 
 * @package RequisicionesMVC\Helpers
 * @version 2.0
 */

namespace App\Helpers;

class View
{
    /**
     * Layout actual
     * 
     * @var string|null
     */
    private static $layout = null;

    /**
     * Secciones capturadas
     * 
     * @var array
     */
    private static $sections = [];

    /**
     * Nombre de la sección actual siendo capturada
     * 
     * @var string|null
     */
    private static $currentSection = null;

    /**
     * Datos compartidos entre todas las vistas
     * 
     * @var array
     */
    private static $sharedData = [];

    /**
     * Directorio base de vistas
     * 
     * @var string
     */
    private static $viewsPath;

    /**
     * Inicializa el helper de vistas
     * 
     * @return void
     */
    public static function init()
    {
        if (self::$viewsPath === null) {
            self::$viewsPath = dirname(__DIR__) . '/Views';
        }
    }

    /**
     * Renderiza una vista e IMPRIME el resultado
     * 
     * @param string $view Nombre de la vista (ej: 'dashboard/index')
     * @param array $data Datos para la vista
     * @param string|null $layout Layout a usar (null para no usar layout)
     * @return void
     */
    public static function render($view, $data = [], $layout = 'layouts/main')
    {
        self::init();

        // Establecer layout
        self::$layout = $layout;
        self::$sections = [];

        // Agregar datos de sesión automáticamente a todas las vistas
        $sessionData = self::getSessionData();
        
        // Combinar datos en orden: compartidos -> sesión -> específicos
        // (los datos específicos tienen prioridad)
        $data = array_merge(self::$sharedData, $sessionData, $data);

        // Renderizar la vista
        $content = self::renderView($view, $data);

        // Si hay layout, renderizar con layout
        if (self::$layout !== null) {
            $layoutData = array_merge($data, ['content' => $content]);
            $content = self::renderView(self::$layout, $layoutData);
        }

        // IMPRIMIR el contenido
        echo $content;
    }

    /**
     * Renderiza una vista sin layout
     * 
     * @param string $view Nombre de la vista
     * @param array $data Datos para la vista
     * @return string Contenido renderizado
     */
    public static function renderPartial($view, $data = [])
    {
        self::init();
        
        // Agregar datos de sesión automáticamente
        $sessionData = self::getSessionData();
        $data = array_merge(self::$sharedData, $sessionData, $data);
        
        return self::renderView($view, $data);
    }

    /**
     * Renderiza una vista (método interno)
     * 
     * @param string $view Nombre de la vista
     * @param array $data Datos
     * @return string Contenido renderizado
     */
    private static function renderView($view, $data = [])
    {
        $viewPath = self::getViewPath($view);

        if (!file_exists($viewPath)) {
            throw new \Exception("Vista no encontrada: {$view} (buscando en {$viewPath})");
        }

        // Extraer variables para la vista
        extract($data);

        // Iniciar buffer de salida
        ob_start();

        // Incluir la vista
        require $viewPath;

        // Obtener contenido del buffer
        return ob_get_clean();
    }

    /**
     * Obtiene la ruta completa de una vista
     * 
     * @param string $view Nombre de la vista
     * @return string Ruta completa
     */
    private static function getViewPath($view)
    {
        // Convertir notación de punto a ruta
        $view = str_replace('.', '/', $view);
        
        // Agregar extensión si no la tiene
        if (!str_ends_with($view, '.php')) {
            $view .= '.php';
        }

        return self::$viewsPath . '/' . $view;
    }

    /**
     * Establece el layout a usar
     * 
     * @param string|null $layout Nombre del layout
     * @return void
     */
    public static function setLayout($layout)
    {
        self::$layout = $layout;
    }

    /**
     * Inicia una sección
     * 
     * @param string $name Nombre de la sección
     * @return void
     */
    public static function startSection($name)
    {
        self::$currentSection = $name;
        ob_start();
    }

    /**
     * Termina una sección
     * 
     * @return void
     */
    public static function endSection()
    {
        if (self::$currentSection === null) {
            throw new \Exception("No hay sección activa para terminar");
        }

        $content = ob_get_clean();
        self::$sections[self::$currentSection] = $content;
        self::$currentSection = null;
    }

    /**
     * Muestra el contenido de una sección
     * 
     * @param string $name Nombre de la sección
     * @param string $default Contenido por defecto si la sección no existe
     * @return void
     */
    public static function section($name, $default = '')
    {
        echo self::$sections[$name] ?? $default;
    }

    /**
     * Verifica si una sección existe
     * 
     * @param string $name Nombre de la sección
     * @return bool
     */
    public static function hasSection($name)
    {
        return isset(self::$sections[$name]);
    }

    /**
     * Incluye un componente/partial
     * 
     * @param string $component Nombre del componente
     * @param array $data Datos para el componente
     * @return void
     */
    public static function component($component, $data = [])
    {
        echo self::renderPartial('components/' . $component, $data);
    }

    /**
     * Comparte datos con todas las vistas
     * 
     * @param string|array $key Clave o array de datos
     * @param mixed $value Valor (si $key es string)
     * @return void
     */
    public static function share($key, $value = null)
    {
        if (is_array($key)) {
            self::$sharedData = array_merge(self::$sharedData, $key);
        } else {
            self::$sharedData[$key] = $value;
        }
    }

    /**
     * Escapa HTML para prevenir XSS
     * 
     * @param mixed $value Valor a escapar
     * @return string Valor escapado
     */
    public static function escape($value)
    {
        // Si es null o vacío, devolver cadena vacía
        if ($value === null || $value === '') {
            return '';
        }
        
        // Si es un array u objeto, convertir a JSON
        if (is_array($value) || is_object($value)) {
            return htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8');
        }
        
        // Si es un booleano, convertir a string
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        // Si es numérico, convertir a string
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        // Para strings, escapar normalmente
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Alias corto para escape
     * 
     * @param string $value Valor a escapar
     * @return string Valor escapado
     */
    public static function e($value)
    {
        return self::escape($value);
    }

    /**
     * Genera una URL
     * 
     * @param string $path Ruta
     * @return string URL completa
     */
    public static function url($path)
    {
        $baseUrl = self::getBaseUrl();
        $path = ltrim($path, '/');
        return $baseUrl . '/' . $path;
    }

    /**
     * Genera una URL a un asset (CSS, JS, imagen)
     * 
     * @param string $path Ruta del asset
     * @return string URL del asset
     */
    public static function asset($path)
    {
        $baseUrl = self::getBaseUrl();
        $path = ltrim($path, '/');
        
        // Detectar si estamos en desarrollo local o servidor
        $isLocalDev = (isset($_SERVER['SERVER_NAME']) && 
                      ($_SERVER['SERVER_NAME'] === 'localhost' || 
                       strpos($_SERVER['SERVER_NAME'], '127.0.0.1') === 0));
        
        // En desarrollo local, usar puerto específico si existe
        if ($isLocalDev && isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
            $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $host = $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];
            return $protocol . $host . ':' . $port . '/' . $path;
        }
        
        return $baseUrl . '/' . $path;
    }

    /**
     * Obtiene la URL base de la aplicación
     * 
     * @return string URL base
     */
    private static function getBaseUrl()
    {
        // Intentar obtener de configuración si existe
        if (class_exists('App\Helpers\Config')) {
            try {
                $configUrl = \App\Helpers\Config::get('app.url', '');
                if (!empty($configUrl)) {
                    return rtrim($configUrl, '/');
                }
            } catch (\Exception $e) {
                // Continuar con detección automática
            }
        }
        
        // Detección automática de URL base
        $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        // No incluir puerto para puertos estándar
        $port = $_SERVER['SERVER_PORT'] ?? '80';
        if (($protocol === 'https://' && $port == '443') || 
            ($protocol === 'http://' && $port == '80')) {
            return $protocol . $host;
        }
        
        return $protocol . $host . ':' . $port;
    }

    /**
     * Incluye un archivo CSS
     * 
     * @param string $path Ruta del CSS
     * @return string Tag link
     */
    public static function css($path)
    {
        $url = self::asset('css/' . $path);
        return '<link rel="stylesheet" href="' . $url . '">';
    }

    /**
     * Incluye un archivo JavaScript
     * 
     * @param string $path Ruta del JS
     * @param bool $defer Usar defer
     * @return string Tag script
     */
    public static function js($path, $defer = false)
    {
        $url = self::asset('js/' . $path);
        $deferAttr = $defer ? ' defer' : '';
        return '<script src="' . $url . '"' . $deferAttr . '></script>';
    }

    /**
     * Muestra el mensaje flash si existe
     * 
     * @return void
     */
    public static function flash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);

            $type = $flash['type'] ?? 'info';
            $message = $flash['message'] ?? '';

            echo self::renderPartial('components/flash', [
                'type' => $type,
                'message' => $message
            ]);
        }
    }

    /**
     * Renderiza errores de validación
     * 
     * @param string|null $field Campo específico (null para todos)
     * @return void
     */
    public static function errors($field = null)
    {
        if (!isset($_SESSION['errors'])) {
            return;
        }

        $errors = $_SESSION['errors'];

        if ($field !== null) {
            // Mostrar error de un campo específico
            if (isset($errors[$field])) {
                echo '<span class="error-message">' . self::escape($errors[$field]) . '</span>';
            }
        } else {
            // Mostrar todos los errores
            if (!empty($errors)) {
                echo '<div class="errors">';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li>' . self::escape($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
        }
    }

    /**
     * Obtiene el valor antiguo de un campo (útil después de validación)
     * 
     * @param string $field Nombre del campo
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    public static function old($field, $default = '')
    {
        if (isset($_SESSION['old'][$field])) {
            return $_SESSION['old'][$field];
        }

        return $default;
    }

    /**
     * Renderiza breadcrumbs
     * 
     * @param array $items Items del breadcrumb [['text' => '', 'url' => ''], ...]
     * @return void
     */
    public static function breadcrumbs($items)
    {
        if (empty($items)) {
            return;
        }

        echo '<nav class="breadcrumbs">';
        echo '<ol>';

        foreach ($items as $index => $item) {
            $isLast = ($index === count($items) - 1);
            
            echo '<li>';
            if (!$isLast && isset($item['url'])) {
                echo '<a href="' . self::url($item['url']) . '">' . self::escape($item['text']) . '</a>';
            } else {
                echo self::escape($item['text']);
            }
            echo '</li>';
        }

        echo '</ol>';
        echo '</nav>';
    }

    /**
     * Formatea una fecha
     * 
     * @param string $date Fecha a formatear
     * @param string $format Formato de salida
     * @return string Fecha formateada
     */
    public static function formatDate($date, $format = 'd/m/Y')
    {
        if (empty($date)) {
            return '';
        }

        try {
            $dateTime = new \DateTime($date);
            return $dateTime->format($format);
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Formatea un número como moneda
     * 
     * @param float $amount Cantidad
     * @param string $currency Código o símbolo de moneda (GTQ, USD, EUR, Q, $, etc.)
     * @return string Cantidad formateada
     */
    public static function money($amount, $currency = 'Q')
    {
        $amount = $amount ?? 0;
        
        // Mapeo de códigos de moneda a símbolos
        $currencySymbols = [
            'GTQ' => 'Q',
            'USD' => '$',
            'EUR' => '€',
            'Q' => 'Q',      // Mantener compatibilidad con símbolo directo
            '$' => '$',
            '€' => '€',
        ];
        
        // Obtener el símbolo correcto
        $symbol = $currencySymbols[$currency] ?? $currency;
        
        return $symbol . ' ' . number_format((float)$amount, 2, '.', ',');
    }
    
    /**
     * Obtiene el símbolo de una moneda basado en su código
     * 
     * @param string $currencyCode Código de moneda (GTQ, USD, EUR)
     * @return string Símbolo de la moneda
     */
    public static function getCurrencySymbol($currencyCode)
    {
        $symbols = [
            'GTQ' => 'Q',
            'USD' => '$',
            'EUR' => '€',
        ];
        
        return $symbols[$currencyCode] ?? $currencyCode;
    }

    /**
     * Trunca un texto
     * 
     * @param string $text Texto a truncar
     * @param int $length Longitud máxima
     * @param string $suffix Sufijo (ej: '...')
     * @return string Texto truncado
     */
    public static function truncate($text, $length = 100, $suffix = '...')
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . $suffix;
    }

    /**
     * Limpia el estado de las vistas (útil para testing)
     * 
     * @return void
     */
    public static function clear()
    {
        self::$layout = null;
        self::$sections = [];
        self::$currentSection = null;
        self::$sharedData = [];
    }

    /**
     * Obtiene datos de sesión para incluir automáticamente en todas las vistas
     * 
     * @return array Datos de sesión
     */
    private static function getSessionData()
    {
        // Solo intentar obtener datos de sesión si la sesión está iniciada
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }

        $data = [];

        // Agregar datos del usuario si está autenticado
        if (class_exists('App\Helpers\Session')) {
            $usuario = \App\Helpers\Session::getUser();
            if ($usuario) {
                $data['usuario'] = $usuario;
                
                // También agregar bajo nombres alternativos para compatibilidad
                $data['user'] = $usuario;
                $data['currentUser'] = $usuario;
            }

            // Agregar banderas de roles para fácil acceso en vistas
            $data['isAuthenticated'] = \App\Helpers\Session::isAuthenticated();
            $data['isAdmin'] = \App\Helpers\Session::isAdmin();
            $data['isRevisor'] = \App\Helpers\Session::isRevisor();
            $data['isAutorizador'] = \App\Helpers\Session::isAutorizador();
        }

        // Agregar mensajes flash si existen
        if (isset($_SESSION['flash'])) {
            $data['flash'] = $_SESSION['flash'];
        }

        // Agregar token CSRF si existe
        if (isset($_SESSION['csrf_token'])) {
            $data['csrfToken'] = $_SESSION['csrf_token'];
        }

        return $data;
    }
}
