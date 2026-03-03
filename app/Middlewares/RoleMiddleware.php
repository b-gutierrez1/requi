<?php
/**
 * RoleMiddleware
 * 
 * Middleware para verificar que el usuario tiene el rol requerido.
 * Verifica roles como revisor, admin, autorizador, etc.
 * 
 * @package RequisicionesMVC\Middlewares
 * @version 2.0
 */

namespace App\Middlewares;

class RoleMiddleware
{
    /**
     * Rol requerido para acceder
     * 
     * @var string
     */
    private $requiredRole;

    /**
     * Constructor
     * 
     * @param string $role Rol requerido (ej: 'admin', 'revisor')
     */
    public function __construct($role = null)
    {
        $this->requiredRole = $role;
    }

    /**
     * Maneja la verificación de rol
     * 
     * @return bool True si tiene el rol, false si no
     */
    public function handle()
    {
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar que el usuario está autenticado
        if (!$this->isAuthenticated()) {
            $this->unauthorized('Usuario no autenticado');
            return false;
        }

        // Si no se especificó rol, solo verifica autenticación
        if (empty($this->requiredRole)) {
            return true;
        }

        // Verificar el rol específico
        if (!$this->hasRole($this->requiredRole)) {
            $this->forbidden('No tiene permisos suficientes para acceder a este recurso');
            return false;
        }

        return true;
    }

    /**
     * Verifica si el usuario está autenticado
     * 
     * @return bool
     */
    private function isAuthenticated()
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    /**
     * Verifica si el usuario tiene un rol específico
     * 
     * @param string $role Rol a verificar
     * @return bool
     */
    private function hasRole($role)
    {
        switch (strtolower($role)) {
            case 'admin':
                return $this->isAdmin();
            
            case 'revisor':
                return $this->isRevisor();
            
            case 'autorizador':
                return $this->isAutorizador();
            
            default:
                return false;
        }
    }

    /**
     * Verifica si el usuario es administrador
     * 
     * @return bool
     */
    private function isAdmin()
    {
        return isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] == 1;
    }

    /**
     * Verifica si el usuario es revisor
     * 
     * @return bool
     */
    private function isRevisor()
    {
        // Verificar por sesión primero
        if (isset($_SESSION['user']['is_revisor']) && $_SESSION['user']['is_revisor'] == 1) {
            return true;
        }
        
        // Verificar por email usando la misma lógica que DashboardController
        $usuarioEmail = $_SESSION['user']['email'] ?? '';
        return $this->isRevisorPorEmail($usuarioEmail);
    }

    /**
     * Verifica si un usuario es revisor basándose en su email
     * Solo usa el flag is_revisor de la sesión (de la base de datos)
     * 
     * @param string $usuarioEmail Email del usuario (no usado, mantenido por compatibilidad)
     * @return bool
     */
    private function isRevisorPorEmail($usuarioEmail)
    {
        // Solo verificar el flag is_revisor de la sesión
        return $this->isRevisor();
    }

    /**
     * Verifica si el usuario es autorizador
     * 
     * @return bool
     */
    private function isAutorizador()
    {
        // Un usuario es autorizador si tiene autorizaciones pendientes
        // o está configurado como autorizador de algún centro de costo
        return isset($_SESSION['user']['is_autorizador']) && $_SESSION['user']['is_autorizador'] == 1;
    }

    /**
     * Maneja error 401 - No autenticado
     * 
     * @param string $message Mensaje de error
     * @return void
     */
    private function unauthorized($message)
    {
        http_response_code(401);

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $message,
                'code' => 401
            ]);
        } else {
            echo '<h1>401 - No Autorizado</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            echo '<a href="' . \App\Helpers\Redirect::url('/login') . '">Iniciar sesión</a>';
        }

        exit;
    }

    /**
     * Maneja error 403 - Sin permisos
     * 
     * @param string $message Mensaje de error
     * @return void
     */
    private function forbidden($message)
    {
        http_response_code(403);

        // Registrar flash message para la vista
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => $message
        ];

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $message,
                'code' => 403
            ]);
        } else {
            echo '<h1>403 - Acceso Prohibido</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            echo '<p>Contacte al administrador si cree que esto es un error.</p>';
            echo '<a href="/dashboard">Volver al inicio</a>';
        }

        exit;
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
     * Verifica múltiples roles (el usuario debe tener al menos uno)
     * 
     * @param array $roles Lista de roles permitidos
     * @return bool
     */
    public function hasAnyRole($roles)
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica múltiples roles (el usuario debe tener todos)
     * 
     * @param array $roles Lista de roles requeridos
     * @return bool
     */
    public function hasAllRoles($roles)
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene los roles del usuario actual
     * 
     * @return array
     */
    public static function getUserRoles()
    {
        $roles = [];

        if (isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin'] == 1) {
            $roles[] = 'admin';
        }

        if (isset($_SESSION['user']['is_revisor']) && $_SESSION['user']['is_revisor'] == 1) {
            $roles[] = 'revisor';
        }

        if (isset($_SESSION['user']['is_autorizador']) && $_SESSION['user']['is_autorizador'] == 1) {
            $roles[] = 'autorizador';
        }

        return $roles;
    }

    /**
     * Método estático para verificar rol desde cualquier lugar
     * 
     * @param string $role Rol a verificar
     * @return bool
     */
    public static function check($role)
    {
        $middleware = new self($role);
        return $middleware->hasRole($role);
    }
}
