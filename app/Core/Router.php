<?php

namespace App\Core;

use Exception;

/**
 * Router - Sistema de enrutamiento MVC
 * 
 * Maneja el registro y despacho de rutas HTTP con soporte para:
 * - Métodos HTTP (GET, POST, PUT, DELETE)
 * - Parámetros dinámicos en rutas (/requisiciones/{id})
 * - Grupos de rutas con prefijos
 * - Middlewares globales y por ruta
 * 
 * @package App\Core
 * @version 1.0.0
 */
class Router
{
    /**
     * Colección de rutas registradas
     * 
     * @var array
     */
    private array $routes = [];

    /**
     * Middlewares globales que se ejecutan en todas las rutas
     * 
     * @var array
     */
    private array $globalMiddlewares = [];

    /**
     * Prefijo actual para grupos de rutas
     * 
     * @var string
     */
    private string $currentPrefix = '';

    /**
     * Middlewares actuales para grupos de rutas
     * 
     * @var array
     */
    private array $currentMiddlewares = [];

    /**
     * Constructor del Router
     */
    public function __construct()
    {
        $this->routes = [
            'GET' => [],
            'POST' => [],
            'PUT' => [],
            'DELETE' => []
        ];
    }

    /**
     * Registra una ruta GET
     * 
     * @param string $uri URI de la ruta (ej: '/requisiciones/{id}')
     * @param callable|array $action Callback o [Controller::class, 'method']
     * @param array $middlewares Middlewares específicos para esta ruta
     * @return self
     */
    public function get(string $uri, $action, array $middlewares = []): self
    {
        return $this->addRoute('GET', $uri, $action, $middlewares);
    }

    /**
     * Registra una ruta POST
     * 
     * @param string $uri URI de la ruta
     * @param callable|array $action Callback o [Controller::class, 'method']
     * @param array $middlewares Middlewares específicos para esta ruta
     * @return self
     */
    public function post(string $uri, $action, array $middlewares = []): self
    {
        return $this->addRoute('POST', $uri, $action, $middlewares);
    }

    /**
     * Registra una ruta PUT
     * 
     * @param string $uri URI de la ruta
     * @param callable|array $action Callback o [Controller::class, 'method']
     * @param array $middlewares Middlewares específicos para esta ruta
     * @return self
     */
    public function put(string $uri, $action, array $middlewares = []): self
    {
        return $this->addRoute('PUT', $uri, $action, $middlewares);
    }

    /**
     * Registra una ruta DELETE
     * 
     * @param string $uri URI de la ruta
     * @param callable|array $action Callback o [Controller::class, 'method']
     * @param array $middlewares Middlewares específicos para esta ruta
     * @return self
     */
    public function delete(string $uri, $action, array $middlewares = []): self
    {
        return $this->addRoute('DELETE', $uri, $action, $middlewares);
    }

    /**
     * Crea un grupo de rutas con prefijo y/o middlewares compartidos
     * 
     * @param array $attributes Atributos del grupo ['prefix' => '/admin', 'middlewares' => [...]]
     * @param callable $callback Función que define las rutas del grupo
     * @return void
     * 
     * @example
     * $router->group(['prefix' => '/admin', 'middlewares' => ['auth']], function($router) {
     *     $router->get('/users', [UserController::class, 'index']);
     * });
     */
    public function group(array $attributes, callable $callback): void
    {
        // Guardar estado actual
        $previousPrefix = $this->currentPrefix;
        $previousMiddlewares = $this->currentMiddlewares;

        // Aplicar nuevo prefijo
        if (isset($attributes['prefix'])) {
            $this->currentPrefix = $previousPrefix . rtrim($attributes['prefix'], '/');
        }

        // Aplicar nuevos middlewares
        if (isset($attributes['middlewares'])) {
            $this->currentMiddlewares = array_merge(
                $previousMiddlewares,
                $attributes['middlewares']
            );
        }

        // Ejecutar el callback con el router
        $callback($this);

        // Restaurar estado anterior
        $this->currentPrefix = $previousPrefix;
        $this->currentMiddlewares = $previousMiddlewares;
    }

    /**
     * Agrega un middleware global que se ejecuta en todas las rutas
     * 
     * @param string $middleware Nombre de la clase middleware
     * @return self
     */
    public function addGlobalMiddleware(string $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Despacha la ruta actual basándose en URI y método HTTP
     * 
     * @param string $uri URI solicitada
     * @param string $method Método HTTP (GET, POST, PUT, DELETE)
     * @return mixed Resultado de la ejecución del controlador
     * @throws Exception Si la ruta no existe
     */
    public function dispatch(string $uri, string $method)
    {
        try {
            // Log para debug
            if (strpos($uri, 'metodos-pago') !== false) {
                error_log("=== Router dispatch ===");
                error_log("URI original: " . $uri);
            }
            
            // Decodificar URI primero para manejar caracteres especiales como @ en emails
            $uri = urldecode($uri);
            
            if (strpos($uri, 'metodos-pago') !== false) {
                error_log("URI después de urldecode: " . $uri);
            }
            
            // Normalizar URI (remover query string y barras finales)
            $uri = $this->normalizeUri($uri);
            
            if (strpos($uri, 'metodos-pago') !== false) {
                error_log("URI después de normalizeUri: " . $uri);
            }
            
            // Normalizar método HTTP
            $method = strtoupper($method);

            // Buscar la ruta coincidente
            $routeData = $this->findRoute($uri, $method);

            if (!$routeData) {
                $this->handleNotFound();
                return null;
            }

            // Extraer datos de la ruta
            ['action' => $action, 'params' => $params, 'middlewares' => $middlewares] = $routeData;

            // Combinar middlewares: globales + grupo + específicos de ruta
            $allMiddlewares = array_merge(
                $this->globalMiddlewares,
                $middlewares
            );

            // Ejecutar middlewares
            foreach ($allMiddlewares as $middlewareClass) {
                // Manejar middlewares con parámetros (ej: RoleMiddleware:admin)
                $middlewareParams = null;
                if (strpos($middlewareClass, ':') !== false) {
                    [$middlewareClass, $middlewareParams] = explode(':', $middlewareClass, 2);
                }
                
                // Resolver el nombre de la clase del middleware
                if (!class_exists($middlewareClass)) {
                    $middlewareClass = "App\\Middlewares\\{$middlewareClass}";
                }
                
                // Crear instancia del middleware con parámetros si los tiene
                $middlewareInstance = new $middlewareClass($middlewareParams);
                
                if (method_exists($middlewareInstance, 'handle')) {
                    $result = $middlewareInstance->handle();
                    
                    // Si el middleware retorna false, detener ejecución
                    if ($result === false) {
                        return null;
                    }
                }
            }

            // Ejecutar el action (controlador)
            return $this->executeAction($action, $params);

        } catch (Exception $e) {
            $this->handleException($e);
            return null;
        }
    }

    /**
     * Agrega una ruta al registro interno
     * 
     * @param string $method Método HTTP
     * @param string $uri URI de la ruta
     * @param callable|array $action Acción a ejecutar
     * @param array $middlewares Middlewares específicos
     * @return self
     */
    private function addRoute(string $method, string $uri, $action, array $middlewares = []): self
    {
        // Aplicar prefijo del grupo si existe
        $fullUri = $this->currentPrefix . $uri;
        
        // Normalizar URI
        $fullUri = $this->normalizeUri($fullUri);

        // Combinar middlewares del grupo con los específicos de la ruta
        $allMiddlewares = array_merge($this->currentMiddlewares, $middlewares);

        // Convertir URI a patrón regex para soportar parámetros
        $pattern = $this->convertToPattern($fullUri);

        // Guardar la ruta
        $this->routes[$method][$pattern] = [
            'uri' => $fullUri,
            'action' => $action,
            'middlewares' => $allMiddlewares
        ];

        return $this;
    }

    /**
     * Convierte una URI con parámetros a un patrón regex
     * 
     * Convierte /requisiciones/{id} a /requisiciones/([^/]+)
     * 
     * @param string $uri URI original
     * @return string Patrón regex
     */
    private function convertToPattern(string $uri): string
    {
        // Reemplazar {param} con marcador temporal antes de escapar
        $pattern = preg_replace('/\{([^}]+)\}/', '___PARAM___', $uri);
        
        // Escapar caracteres especiales
        $pattern = preg_quote($pattern, '/');
        
        // Restaurar parámetros como grupos de captura regex
        $pattern = str_replace('___PARAM___', '([^\/]+)', $pattern);
        
        // Agregar delimitadores y anclas
        return '/^' . $pattern . '$/';
    }

    /**
     * Busca una ruta que coincida con la URI y método proporcionados
     * 
     * @param string $uri URI a buscar
     * @param string $method Método HTTP
     * @return array|null Datos de la ruta o null si no se encuentra
     */
    private function findRoute(string $uri, string $method): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        // Ordenar rutas: las más específicas (más largas) primero
        // Usar la URI original almacenada en routeData, no el patrón regex
        $sortedRoutes = [];
        foreach ($this->routes[$method] as $pattern => $routeData) {
            $sortedRoutes[] = [
                'pattern' => $pattern,
                'uri' => $routeData['uri'],
                'data' => $routeData
            ];
        }
        
        usort($sortedRoutes, function($a, $b) {
            // Contar barras para determinar especificidad (más barras = más específico)
            $aCount = substr_count($a['uri'], '/');
            $bCount = substr_count($b['uri'], '/');
            if ($aCount !== $bCount) {
                return $bCount - $aCount; // Más específicas primero
            }
            // Si tienen la misma cantidad de barras, comparar longitud
            return strlen($b['uri']) - strlen($a['uri']);
        });
        
        foreach ($sortedRoutes as $route) {
            $pattern = $route['pattern'];
            $routeData = $route['data'];
            // Debug: Log para rutas que contienen email
            if (strpos($pattern, 'metodos-pago') !== false && strpos($uri, 'metodos-pago') !== false) {
                error_log("Router: Intentando match URI: $uri con patrón: $pattern");
            }
            
            if (preg_match($pattern, $uri, $matches)) {
                // Remover el match completo (índice 0)
                array_shift($matches);
                
                // Debug
                if (strpos($pattern, 'metodos-pago') !== false) {
                    error_log("Router: Match encontrado! Parámetros: " . json_encode($matches));
                }
                
                return [
                    'action' => $routeData['action'],
                    'params' => $matches,
                    'middlewares' => $routeData['middlewares']
                ];
            }
        }

        return null;
    }

    /**
     * Ejecuta la acción del controlador con los parámetros extraídos
     * 
     * @param callable|array $action Acción a ejecutar
     * @param array $params Parámetros extraídos de la URI
     * @return mixed Resultado de la ejecución
     * @throws Exception Si el controlador no existe o el método no está definido
     */
    private function executeAction($action, array $params = [])
    {
        // Si es un callable directo
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }

        // Si es un array [Controller::class, 'method']
        if (is_array($action) && count($action) === 2) {
            [$controllerClass, $method] = $action;

            // Verificar que la clase existe
            if (!class_exists($controllerClass)) {
                throw new Exception("Controlador no encontrado: {$controllerClass}");
            }

            // Crear instancia del controlador
            $controller = new $controllerClass();

            // Verificar que el método existe
            if (!method_exists($controller, $method)) {
                throw new Exception("Método {$method} no encontrado en {$controllerClass}");
            }

            // Ejecutar el método con los parámetros
            return call_user_func_array([$controller, $method], $params);
        }

        throw new Exception("Formato de acción inválido");
    }

    /**
     * Normaliza una URI removiendo query strings y barras extras
     * 
     * @param string $uri URI a normalizar
     * @return string URI normalizada
     */
    private function normalizeUri(string $uri): string
    {
        // Remover query string
        $uri = strtok($uri, '?');
        
        // Remover barra final excepto para la raíz
        if ($uri !== '/' && strlen($uri) > 1) {
            $uri = rtrim($uri, '/');
        }

        // Asegurar que empiece con /
        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    /**
     * Maneja el error 404 - Ruta no encontrada
     * 
     * @return void
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Ruta no encontrada'
            ]);
        } else {
            echo '<h1>404 - Página no encontrada</h1>';
        }
    }

    /**
     * Maneja excepciones durante el dispatch
     * 
     * @param Exception $e Excepción capturada
     * @return void
     */
    private function handleException(Exception $e): void
    {
        http_response_code(500);
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor',
                'message' => getenv('APP_DEBUG') === 'true' ? $e->getMessage() : null
            ]);
        } else {
            if (getenv('APP_DEBUG') === 'true') {
                echo '<h1>Error 500</h1>';
                echo '<pre>' . $e->getMessage() . '</pre>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            } else {
                echo '<h1>Error 500 - Error interno del servidor</h1>';
            }
        }

        // Log del error
        error_log("Router Error: " . $e->getMessage());
    }

    /**
     * Detecta si la petición es para API (espera respuesta JSON)
     * 
     * @return bool
     */
    private function isApiRequest(): bool
    {
        // Verificar si la URI comienza con /api/
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_starts_with($uri, '/api/')) {
            return true;
        }

        // Verificar el header Accept
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }

    /**
     * Obtiene todas las rutas registradas (útil para debugging)
     * 
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
