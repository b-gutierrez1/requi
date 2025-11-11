<?php
/**
 * Helper de Configuración
 * 
 * Proporciona una forma sencilla de acceder a los valores de configuración
 * desde cualquier parte de la aplicación.
 * 
 * @package RequisicionesMVC\Helpers
 * @version 2.0
 */

namespace App\Helpers;

class Config
{
    /**
     * Array que almacena todas las configuraciones cargadas
     * @var array
     */
    private static $config = [];

    /**
     * Carga un archivo de configuración
     * 
     * @param string $file Nombre del archivo sin extensión
     * @return array
     */
    public static function load($file)
    {
        if (!isset(self::$config[$file])) {
            $path = __DIR__ . '/../../config/' . $file . '.php';
            
            if (file_exists($path)) {
                self::$config[$file] = require $path;
            } else {
                throw new \Exception("Archivo de configuración no encontrado: {$file}");
            }
        }
        
        return self::$config[$file];
    }

    /**
     * Carga todos los archivos de configuración disponibles
     * 
     * @return void
     */
    public static function loadAll()
    {
        $configPath = __DIR__ . '/../../config/';
        $files = ['app', 'database', 'azure'];
        
        foreach ($files as $file) {
            $path = $configPath . $file . '.php';
            if (file_exists($path)) {
                self::load($file);
            }
        }
    }

    /**
     * Obtiene un valor de configuración usando notación de punto
     * 
     * Ejemplos:
     * Config::get('app.name')
     * Config::get('database.connections.mysql.host')
     * Config::get('azure.credentials.client_id')
     * 
     * @param string $key Clave en notación de punto
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        // Separar el archivo del resto de la clave
        $parts = explode('.', $key, 2);
        $file = $parts[0];
        
        // Cargar el archivo si no está cargado
        if (!isset(self::$config[$file])) {
            self::load($file);
        }
        
        // Si solo se pidió el archivo completo
        if (count($parts) === 1) {
            return self::$config[$file] ?? $default;
        }
        
        // Navegar por el array usando notación de punto
        $value = self::$config[$file];
        $keys = explode('.', $parts[1]);
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    /**
     * Establece un valor de configuración en tiempo de ejecución
     * 
     * @param string $key Clave en notación de punto
     * @param mixed $value Valor a establecer
     * @return void
     */
    public static function set($key, $value)
    {
        $parts = explode('.', $key, 2);
        $file = $parts[0];
        
        // Cargar el archivo si no está cargado
        if (!isset(self::$config[$file])) {
            self::load($file);
        }
        
        // Si es solo el archivo
        if (count($parts) === 1) {
            self::$config[$file] = $value;
            return;
        }
        
        // Navegar y establecer el valor
        $keys = explode('.', $parts[1]);
        $current = &self::$config[$file];
        
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        
        $current = $value;
    }

    /**
     * Verifica si existe una clave de configuración
     * 
     * @param string $key Clave en notación de punto
     * @return bool
     */
    public static function has($key)
    {
        try {
            $value = self::get($key);
            return $value !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene toda la configuración cargada
     * 
     * @return array
     */
    public static function all()
    {
        return self::$config;
    }

    /**
     * Limpia la configuración cargada (útil para testing)
     * 
     * @return void
     */
    public static function clear()
    {
        self::$config = [];
    }


    /**
     * Reemplaza placeholders en endpoints de Azure
     * 
     * @param string $endpoint
     * @param array $replacements
     * @return string
     */
    public static function replaceInEndpoint($endpoint, array $replacements = [])
    {
        // Reemplazos por defecto
        $defaults = [
            '{tenant}' => self::get('azure.credentials.tenant', 'common'),
        ];
        
        $replacements = array_merge($defaults, $replacements);
        
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $endpoint
        );
    }

    /**
     * Construye la URL de redirección de Azure dinámicamente
     * 
     * @return string
     */
    public static function getAzureRedirectUri()
    {
        // Si ya está configurada, usarla
        $configured = self::get('azure.redirect.uri');
        if ($configured) {
            return $configured;
        }
        
        // Construir dinámicamente
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = self::get('azure.redirect.base_path', '');
        $callbackRoute = self::get('azure.redirect.callback_route', '/auth/callback');
        
        return $protocol . '://' . $host . $basePath . $callbackRoute;
    }
}
