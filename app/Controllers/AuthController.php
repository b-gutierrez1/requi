<?php
/**
 * AuthController
 * 
 * Controlador para autenticación con Azure AD.
 * Maneja login, callback, logout y verificación de usuarios.
 * 
 * @package RequisicionesMVC\Controllers
 * @version 2.0
 */

namespace App\Controllers;

use App\Helpers\Config;
use App\Helpers\Session;
use App\Helpers\Redirect;
use App\Helpers\View;
use App\Models\Usuario;

class AuthController extends Controller
{
    /**
     * Muestra la página de login
     * 
     * @return void
     */
    public function showLogin()
    {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->isAuthenticated()) {
            Redirect::dashboard()->send();
        }

        View::render('auth/login', [
            'title' => 'Iniciar Sesión'
        ], null); // Sin layout
    }

    /**
     * Inicia el proceso de autenticación con Azure AD
     * 
     * @return void
     */
    public function login()
    {
        // Guardar URL de redirección si existe
        if (isset($_GET['redirect'])) {
            Session::setIntendedUrl($_GET['redirect']);
        }

        // Generar state para CSRF
        $state = bin2hex(random_bytes(16));
        Session::set('oauth_state', $state);

        // Construir URL de autorización de Azure
        $authUrl = $this->buildAzureAuthUrl($state);

        // Redirigir a Azure
        Redirect::now($authUrl);
    }

    /**
     * Callback de Azure AD después de autenticación
     * 
     * @return void
     */
    public function azureCallback()
    {
        try {
            // Verificar que no haya error
            if (isset($_GET['error'])) {
                $errorDescription = $_GET['error_description'] ?? 'Error desconocido';
                Redirect::toLogin('Error de autenticación: ' . $errorDescription);
            }

            // Verificar code
            if (!isset($_GET['code'])) {
                Redirect::toLogin('No se recibió código de autorización');
            }

            // Verificar state (CSRF)
            $receivedState = $_GET['state'] ?? '';
            $savedState = Session::get('oauth_state', '');
            
            if (empty($receivedState) || $receivedState !== $savedState) {
                Redirect::toLogin('State inválido. Por favor intente nuevamente.');
            }

            // Limpiar state
            Session::remove('oauth_state');

            // Intercambiar código por token
            $code = $_GET['code'];
            $tokenData = $this->exchangeCodeForToken($code);

            if (!$tokenData) {
                Redirect::toLogin('Error al obtener token de acceso');
            }

            // Obtener información del usuario de Azure
            $azureUser = $this->getAzureUserInfo($tokenData['access_token']);

            if (!$azureUser) {
                Redirect::toLogin('Error al obtener información del usuario');
            }

            // Buscar o crear usuario en la base de datos
            $usuario = $this->findOrCreateUser($azureUser, $tokenData);

            if (!$usuario) {
                Redirect::toLogin('Error al procesar usuario');
            }

            // Guardar usuario en sesión
            Session::setUser([
                'id' => $usuario->id,
                'email' => $usuario->azure_email,
                'name' => $usuario->azure_display_name,
                'azure_id' => $usuario->azure_id,
                'azure_token' => $tokenData['access_token'],
                'is_revisor' => $usuario->is_revisor ?? 0,
                'is_admin' => $usuario->is_admin ?? 0,
                'is_autorizador' => $usuario->is_autorizador ?? 0
            ]);

            // Registrar login
            $this->logLogin($usuario->id);

            // Redirigir a la URL deseada o al dashboard
            Redirect::afterLogin('/dashboard');

        } catch (\Exception $e) {
            error_log("Error en callback de Azure: " . $e->getMessage());
            Redirect::toLogin('Error en el proceso de autenticación');
        }
    }

    /**
     * Cierra la sesión del usuario
     * 
     * @return void
     */
    public function logout()
    {
        // Registrar logout si hay usuario
        if ($this->isAuthenticated()) {
            $this->logLogout($this->getUsuarioId());
        }

        // Limpiar todas las cookies relacionadas con autenticación
        $this->clearAllAuthCookies();

        // Destruir sesión completamente
        Session::logout();

        // Agregar headers para prevenir cache
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        // Redirigir al login con mensaje y forzar recarga completa
        Redirect::toLogin('Sesión cerrada exitosamente');
    }

    // ========================================================================
    // MÉTODOS PRIVADOS - Azure AD
    // ========================================================================

    /**
     * Construye la URL de autorización de Azure
     * 
     * @param string $state State para CSRF
     * @return string URL de autorización
     */
    private function buildAzureAuthUrl($state)
    {
        // Obtener configuración de Azure
        $clientId = Config::get('azure.credentials.client_id');
        $tenant = Config::get('azure.credentials.tenant', 'common');
        $redirectUri = $this->getRedirectUri();
        $scopes = implode(' ', Config::get('azure.scopes', ['openid', 'profile', 'email', 'User.Read']));

        $params = [
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'response_mode' => 'query',
            'scope' => $scopes,
            'state' => $state
        ];

        $baseUrl = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/authorize";

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Intercambia el código de autorización por un token de acceso
     * 
     * @param string $code Código de autorización
     * @return array|null Token data
     */
    private function exchangeCodeForToken($code)
    {
        $tenant = Config::get('azure.credentials.tenant', 'common');
        $tokenUrl = "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token";

        $params = [
            'client_id' => Config::get('azure.credentials.client_id'),
            'scope' => 'openid profile email User.Read',
            'code' => $code,
            'redirect_uri' => $this->getRedirectUri(),
            'grant_type' => 'authorization_code',
            'client_secret' => Config::get('azure.credentials.client_secret')
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Error obteniendo token: HTTP {$httpCode}");
            error_log("Response: " . $response);
            return null;
        }

        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            error_log("Token no encontrado en respuesta");
            return null;
        }

        return $data;
    }

    /**
     * Obtiene información del usuario desde Microsoft Graph
     * 
     * @param string $accessToken Token de acceso
     * @return array|null Información del usuario
     */
    private function getAzureUserInfo($accessToken)
    {
        $graphUrl = 'https://graph.microsoft.com/v1.0/me';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $graphUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Error obteniendo info de usuario: HTTP {$httpCode}");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Busca o crea un usuario en la base de datos
     * 
     * @param array $azureUser Datos del usuario de Azure
     * @param array $tokenData Datos del token
     * @return Usuario|null Usuario
     */
    private function findOrCreateUser($azureUser, $tokenData)
    {
        try {
            $azureId = $azureUser['id'];
            $email = $azureUser['mail'] ?? $azureUser['userPrincipalName'];
            $displayName = $azureUser['displayName'] ?? '';
            $firstName = $azureUser['givenName'] ?? '';
            $lastName = $azureUser['surname'] ?? '';
            $jobTitle = $azureUser['jobTitle'] ?? '';
            $department = $azureUser['department'] ?? '';

            // Buscar por Azure ID
            $usuario = Usuario::findByAzureId($azureId);
            error_log("Buscando por Azure ID: {$azureId} - Encontrado: " . ($usuario ? 'SI' : 'NO'));

            if ($usuario) {
                error_log("Actualizando usuario existente ID: {$usuario->id}");
                // Actualizar información del usuario usando query SQL directa
                $db = Usuario::getConnection();
                $stmt = $db->prepare("
                    UPDATE usuarios SET 
                        azure_email = ?,
                        azure_display_name = ?,
                        azure_first_name = ?,
                        azure_last_name = ?,
                        azure_job_title = ?,
                        azure_department = ?,
                        last_login = ?
                    WHERE id = ?
                ");
                $updateResult = $stmt->execute([
                    $email,
                    $displayName,
                    $firstName,
                    $lastName,
                    $jobTitle,
                    $department,
                    date('Y-m-d H:i:s'),
                    $usuario->id
                ]);
                
                error_log("UPDATE ejecutado: " . ($updateResult ? 'OK' : 'FALLO'));
                
                $usuarioActualizado = Usuario::find($usuario->id);
                error_log("Usuario recuperado después de UPDATE: " . ($usuarioActualizado ? 'SI - ID: ' . $usuarioActualizado->id : 'NO'));
                
                return $usuarioActualizado;
            }

            // Si no existe, buscar por email
            $usuario = Usuario::findByEmail($email);
            error_log("Buscando por Email: {$email} - Encontrado: " . ($usuario ? 'SI - ID: ' . $usuario->id : 'NO'));

            if ($usuario) {
                error_log("Actualizando Azure ID para usuario existente ID: {$usuario->id}");
                // Actualizar Azure ID usando query SQL directa
                $db = Usuario::getConnection();
                $stmt = $db->prepare("
                    UPDATE usuarios SET 
                        azure_id = ?,
                        azure_display_name = ?,
                        azure_first_name = ?,
                        azure_last_name = ?,
                        azure_job_title = ?,
                        azure_department = ?,
                        last_login = ?
                    WHERE id = ?
                ");
                $updateResult = $stmt->execute([
                    $azureId,
                    $displayName,
                    $firstName,
                    $lastName,
                    $jobTitle,
                    $department,
                    date('Y-m-d H:i:s'),
                    $usuario->id
                ]);
                
                error_log("UPDATE Azure ID ejecutado: " . ($updateResult ? 'OK' : 'FALLO'));
                
                $usuarioActualizado = Usuario::find($usuario->id);
                error_log("Usuario recuperado después de UPDATE: " . ($usuarioActualizado ? 'SI - ID: ' . $usuarioActualizado->id : 'NO'));
                
                return $usuarioActualizado;
            }

            // Crear nuevo usuario
            error_log("Creando nuevo usuario con email: {$email}");
            $nuevoUsuario = Usuario::create([
                'nombre' => $displayName, // Campo obligatorio de la tabla
                'email' => $email,
                'password' => password_hash('azure_user_temp', PASSWORD_DEFAULT), // Campo obligatorio
                'rol' => 'usuario', // Rol por defecto
                'azure_id' => $azureId,
                'azure_email' => $email,
                'azure_display_name' => $displayName,
                'azure_first_name' => $firstName,
                'azure_last_name' => $lastName,
                'azure_job_title' => $jobTitle,
                'azure_department' => $department,
                'is_revisor' => 0,
                'is_autorizador' => 0,
                'is_admin' => 0,
                'activo' => 1,
                'last_login' => date('Y-m-d H:i:s')
            ]);
            
            error_log("Usuario creado con ID: " . $nuevoUsuario->id);
            error_log("Usuario recuperado después de CREATE: SI - ID: " . $nuevoUsuario->id);

            return $nuevoUsuario;

        } catch (\Exception $e) {
            error_log("Error en findOrCreateUser: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Registra el login del usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return void
     */
    private function logLogin($usuarioId)
    {
        try {
            // Actualizar último login
            $usuario = Usuario::find($usuarioId);
            if ($usuario) {
                $usuario->updateLastLogin();
            }

            // Log del evento
            error_log("Login exitoso - Usuario ID: {$usuarioId}");
        } catch (\Exception $e) {
            error_log("Error registrando login: " . $e->getMessage());
        }
    }

    /**
     * Registra el logout del usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return void
     */
    private function logLogout($usuarioId)
    {
        try {
            error_log("Logout - Usuario ID: {$usuarioId}");
        } catch (\Exception $e) {
            error_log("Error registrando logout: " . $e->getMessage());
        }
    }

    // ========================================================================
    // MÉTODOS DE UTILIDAD
    // ========================================================================

    /**
     * Obtiene la URI de redirección configurada
     * 
     * @return string URI de redirección
     */
    private function getRedirectUri()
    {
        // Intentar obtener del archivo .env primero
        $envRedirectUri = getenv('AZURE_REDIRECT_URI');
        if (!empty($envRedirectUri)) {
            return $envRedirectUri;
        }

        // Construir dinámicamente basado en la configuración
        $config = Config::get('azure.redirect', []);
        
        // Obtener el protocolo
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        
        // Obtener el host
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Obtener la ruta base
        $basePath = $config['base_path'] ?? '';
        
        // Obtener la ruta del callback
        $callbackRoute = $config['callback_route'] ?? '/auth/callback';
        
        // Construir la URL completa
        return "{$protocol}://{$host}{$basePath}{$callbackRoute}";
    }

    /**
     * Verifica el estado de la autenticación (endpoint de prueba)
     * 
     * @return void
     */
    public function status()
    {
        $this->jsonResponse([
            'authenticated' => $this->isAuthenticated(),
            'user' => $this->isAuthenticated() ? Session::getUser() : null
        ]);
    }

    /**
     * Actualizar sesión con datos frescos de la base de datos
     * 
     * @return void
     */
    public function refreshSession()
    {
        if (!Session::isAuthenticated()) {
            Redirect::to('/login')->send();
        }

        $usuario = Session::getUser();
        $usuario = Usuario::find($usuario['id']);
        
        if (!$usuario) {
            Redirect::to('/login')->send();
        }

        // Actualizar la sesión con datos frescos
        Session::setUser([
            'id' => $usuario->id,
            'email' => $usuario->email,
            'name' => $usuario->nombre,
            'azure_id' => $usuario->azure_id,
            'is_revisor' => $usuario->is_revisor ?? 0,
            'is_admin' => $usuario->is_admin ?? 0,
            'is_autorizador' => $usuario->is_autorizador ?? 0
        ]);

        // Redirigir al dashboard
        Redirect::to('/dashboard')
            ->withSuccess('Sesión actualizada correctamente')
            ->send();
    }

    /**
     * Endpoint para refrescar el token (futuro)
     * 
     * @return void
     */
    public function refresh()
    {
        // TODO: Implementar refresh token si es necesario
        $this->jsonResponse([
            'success' => false,
            'message' => 'Refresh token no implementado'
        ], 501);
    }

    /**
     * Limpia todas las cookies relacionadas con autenticación
     * 
     * @return void
     */
    private function clearAllAuthCookies()
    {
        $cookiesToClear = [
            'user_id',
            'auth_token',
            'remember_token',
            'azure_token',
            'access_token',
            session_name() // PHPSESSID
        ];

        foreach ($cookiesToClear as $cookieName) {
            if (isset($_COOKIE[$cookieName])) {
                setcookie($cookieName, '', time() - 3600, '/', '', false, true);
                setcookie($cookieName, '', time() - 3600, '/requi/', '', false, true);
                setcookie($cookieName, '', time() - 3600, '/requi', '', false, true);
                unset($_COOKIE[$cookieName]);
            }
        }

        // También limpiar cualquier cookie que empiece con 'auth_' o 'session_'
        foreach ($_COOKIE as $cookieName => $value) {
            if (strpos($cookieName, 'auth_') === 0 || strpos($cookieName, 'session_') === 0) {
                setcookie($cookieName, '', time() - 3600, '/', '', false, true);
                setcookie($cookieName, '', time() - 3600, '/requi/', '', false, true);
                unset($_COOKIE[$cookieName]);
            }
        }
    }
}
