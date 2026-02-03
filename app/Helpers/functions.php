<?php
/**
 * Funciones Helper Globales
 * 
 * Funciones de utilidad disponibles en toda la aplicación.
 * 
 * @package RequisicionesMVC
 * @version 2.0
 */

if (!function_exists('dd')) {
    /**
     * Dump and Die - Imprime variable y detiene ejecución
     * 
     * @param mixed ...$vars Variables a imprimir
     * @return void
     */
    function dd(...$vars)
    {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump - Imprime variable sin detener ejecución
     * 
     * @param mixed ...$vars Variables a imprimir
     * @return void
     */
    function dump(...$vars)
    {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
    }
}

if (!function_exists('env')) {
    /**
     * Obtiene una variable de entorno
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convertir strings a booleanos
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Obtiene un valor de configuración
     * 
     * @param string $key Clave de configuración (formato: archivo.clave)
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    function config($key, $default = null)
    {
        return App\Helpers\Config::get($key, $default);
    }
}

if (!function_exists('asset')) {
    /**
     * Genera URL para un asset
     * 
     * @param string $path Path del asset
     * @return string
     */
    function asset($path)
    {
        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
        $path = ltrim($path, '/');
        return $baseUrl . '/' . $path;
    }
}

if (!function_exists('url')) {
    /**
     * Genera una URL completa
     * 
     * @param string $path Path relativo
     * @return string
     */
    function url($path = '')
    {
        return \App\Helpers\Redirect::url($path);
    }
}

if (!function_exists('redirect')) {
    /**
     * Crea un objeto Redirect
     * 
     * @param string|null $to URL de destino
     * @return App\Helpers\Redirect
     */
    function redirect($to = null)
    {
        $redirect = new App\Helpers\Redirect();
        return $to ? $redirect->to($to) : $redirect;
    }
}

if (!function_exists('old')) {
    /**
     * Obtiene el valor antiguo de un campo de formulario
     * 
     * @param string $key Nombre del campo
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    function old($key, $default = '')
    {
        $oldInput = App\Helpers\Session::get('old_input', []);
        return $oldInput[$key] ?? $default;
    }
}

if (!function_exists('session')) {
    /**
     * Obtiene o establece un valor de sesión
     * 
     * @param string|null $key Clave de sesión
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    function session($key = null, $default = null)
    {
        if ($key === null) {
            return $_SESSION ?? [];
        }
        
        return App\Helpers\Session::get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Obtiene el token CSRF
     * 
     * @return string
     */
    function csrf_token()
    {
        return App\Middlewares\CsrfMiddleware::getToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Genera un campo oculto con el token CSRF
     * 
     * @return string
     */
    function csrf_field()
    {
        return App\Middlewares\CsrfMiddleware::field();
    }
}

if (!function_exists('auth')) {
    /**
     * Obtiene información del usuario autenticado
     * 
     * @return array|null
     */
    function auth()
    {
        return App\Helpers\Session::getUser();
    }
}

if (!function_exists('now')) {
    /**
     * Obtiene la fecha y hora actual
     * 
     * @param string $format Formato de fecha
     * @return string
     */
    function now($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }
}

if (!function_exists('str_limit')) {
    /**
     * Limita la longitud de un string
     * 
     * @param string $value String a limitar
     * @param int $limit Longitud máxima
     * @param string $end Sufijo
     * @return string
     */
    function str_limit($value, $limit = 100, $end = '...')
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        
        return mb_substr($value, 0, $limit) . $end;
    }
}

if (!function_exists('money')) {
    /**
     * Formatea un número como moneda
     * 
     * @param float $amount Cantidad
     * @param string $currency Símbolo de moneda
     * @return string
     */
    function money($amount, $currency = 'Q')
    {
        return $currency . ' ' . number_format($amount, 2, '.', ',');
    }
}

if (!function_exists('percentage')) {
    /**
     * Formatea un número como porcentaje
     * 
     * @param float $value Valor
     * @param int $decimals Decimales
     * @return string
     */
    function percentage($value, $decimals = 2)
    {
        return number_format($value, $decimals, '.', ',') . '%';
    }
}

if (!function_exists('array_get')) {
    /**
     * Obtiene un valor de un array usando notación punto
     * 
     * @param array $array Array
     * @param string $key Clave (puede usar notación punto)
     * @param mixed $default Valor por defecto
     * @return mixed
     */
    function array_get($array, $key, $default = null)
    {
        if (!is_array($array)) {
            return $default;
        }
        
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        
        return $array;
    }
}

if (!function_exists('logger')) {
    /**
     * Registra un mensaje en el log
     * 
     * @param string $message Mensaje
     * @param string $level Nivel (debug, info, warning, error)
     * @return void
     */
    function logger($message, $level = 'info')
    {
        $logLevel = config('app.log_level', 'info');
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        
        if ($levels[$level] >= $levels[$logLevel]) {
            error_log("[{$level}] {$message}");
        }
    }
}

if (!function_exists('getCausalCompraLabel')) {
    /**
     * Obtiene la etiqueta legible para una causal de compra
     * 
     * @param string $value Valor de la causal de compra
     * @return string Etiqueta legible
     */
    function getCausalCompraLabel($value)
    {
        $causales = [
            'tramite_normal' => 'Trámite Normal',
            'eventualidad' => 'Eventualidad',
            'emergencia' => 'Emergencia',
            // Valores antiguos para compatibilidad
            'urgencia' => 'Urgencia',
            'necesidad_operativa' => 'Necesidad Operativa',
            'proyecto' => 'Proyecto',
            'mantenimiento' => 'Mantenimiento',
            'inversion' => 'Inversión',
            'otro' => 'Otro',
        ];
        
        return $causales[$value] ?? $value;
    }
}

if (!function_exists('getFormaPagoLabel')) {
    /**
     * Obtiene la etiqueta legible para una forma de pago
     * 
     * @param string $value Valor de la forma de pago
     * @return string Etiqueta legible
     */
    function getFormaPagoLabel($value)
    {
        $formasPago = [
            'contado' => 'Contado',
            'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito (Lic. Milton)',
            'cheque' => 'Cheque',
            'transferencia' => 'Transferencia',
            'credito' => 'Crédito',
            // Valores antiguos para compatibilidad
            'credito_30' => 'Crédito 30 días',
            'credito_60' => 'Crédito 60 días',
            'credito_90' => 'Crédito 90 días',
            'efectivo' => 'Efectivo',
            'tarjeta_credito' => 'Tarjeta de Crédito',
            'tarjeta_debito' => 'Tarjeta de Débito',
        ];
        
        return $formasPago[$value] ?? $value;
    }
}

if (!function_exists('response_json')) {
    /**
     * Envía una respuesta JSON
     * 
     * @param mixed $data Datos
     * @param int $status Código de estado HTTP
     * @return void
     */
    function response_json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('abort')) {
    /**
     * Aborta la ejecución con un código de estado
     * 
     * @param int $code Código de estado HTTP
     * @param string $message Mensaje
     * @return void
     */
    function abort($code = 404, $message = '')
    {
        http_response_code($code);
        
        if (empty($message)) {
            $message = match($code) {
                404 => 'Página No Encontrada',
                403 => 'Acceso Prohibido',
                500 => 'Error Interno del Servidor',
                default => 'Error'
            };
        }
        
        // Si es una petición AJAX, devolver JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            response_json(['error' => $message], $code);
        }
        
        // Mostrar página de error
        if (file_exists(__DIR__ . '/../Views/errors/error.php')) {
            extract(['code' => $code, 'message' => $message]);
            require __DIR__ . '/../Views/errors/error.php';
        } else {
            echo "<h1>{$code}</h1><p>{$message}</p>";
        }
        
        exit;
    }
}
