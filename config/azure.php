<?php
/**
 * Configuración de Azure Active Directory
 * 
 * Este archivo contiene toda la configuración relacionada con la autenticación
 * mediante Microsoft Azure Active Directory:
 * - Credenciales de la aplicación
 * - Endpoints de OAuth2
 * - Scopes y permisos
 * - Configuración de sesión y tokens
 * - Mapeo de grupos a roles
 * 
 * @package RequisicionesMVC
 * @version 2.0
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Habilitar/Deshabilitar Azure AD
    |--------------------------------------------------------------------------
    */
    'enabled' => getenv('AZURE_AUTH_ENABLED') !== 'false',

    /*
    |--------------------------------------------------------------------------
    | Credenciales de la Aplicación Azure
    |--------------------------------------------------------------------------
    | Estos valores se obtienen del portal de Azure al registrar la aplicación
    */
    'credentials' => [
        'client_id' => getenv('AZURE_CLIENT_ID') ?: 'YOUR_AZURE_CLIENT_ID',
        'client_secret' => getenv('AZURE_CLIENT_SECRET') ?: 'YOUR_AZURE_CLIENT_SECRET',
        'tenant' => getenv('AZURE_TENANT') ?: 'common',
    ],

    /*
    |--------------------------------------------------------------------------
    | URLs de Redirección
    |--------------------------------------------------------------------------
    */
    'redirect' => [
        'uri' => getenv('AZURE_REDIRECT_URI') ?: null, // Se construye dinámicamente si es null
        'base_path' => '',
        'callback_route' => '/auth/azure/callback',
        'logout_route' => '/auth/logout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoints de Azure AD
    |--------------------------------------------------------------------------
    */
    'endpoints' => [
        'authorize' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize',
        'token' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token',
        'user_info' => 'https://graph.microsoft.com/v1.0/me',
        'user_photo' => 'https://graph.microsoft.com/v1.0/me/photo/$value',
        'logout' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/logout',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes / Permisos Solicitados
    |--------------------------------------------------------------------------
    | Define qué permisos se solicitan al usuario durante la autenticación
    */
    'scopes' => [
        'openid',           // Información de identidad básica
        'profile',          // Perfil del usuario
        'email',            // Correo electrónico
        'offline_access',   // Refresh token
        'User.Read',        // Leer información del usuario
        // 'User.ReadBasic.All', // Leer info básica de todos los usuarios
        // 'Group.Read.All',     // Leer grupos (si se necesita)
    ],

    /*
    |--------------------------------------------------------------------------
    | Versión del Endpoint
    |--------------------------------------------------------------------------
    */
    'endpoint_version' => '2.0', // '1.0' o '2.0'

    /*
    |--------------------------------------------------------------------------
    | Configuración de Tokens
    |--------------------------------------------------------------------------
    */
    'tokens' => [
        'access_token_lifetime' => 3600, // segundos (1 hora)
        'refresh_token_lifetime' => 86400 * 90, // segundos (90 días)
        'id_token_lifetime' => 3600, // segundos
        
        // Almacenamiento de tokens
        'storage' => 'session', // 'session', 'database', 'redis'
        
        // Renovación automática de tokens
        'auto_refresh' => true,
        'refresh_before_expiry' => 300, // segundos (5 minutos antes)
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de HTTP Client
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => 30, // segundos
        'connect_timeout' => 30,
        'verify_ssl' => true,
        'ip_version' => 4, // 4 para IPv4, 6 para IPv6
        
        // Opciones cURL adicionales
        'curl_options' => [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_VERBOSE => false,
            CURLOPT_DNS_USE_GLOBAL_CACHE => false,
            CURLOPT_DNS_CACHE_TIMEOUT => 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Sesión
    |--------------------------------------------------------------------------
    */
    'session' => [
        'key_prefix' => 'azure_',
        'user_key' => 'azure_user',
        'token_key' => 'azure_token',
        'state_key' => 'azure_state',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapeo de Datos del Usuario
    |--------------------------------------------------------------------------
    | Cómo se mapean los campos de Azure AD a la base de datos local
    */
    'user_mapping' => [
        'azure_id' => 'id',
        'azure_email' => 'mail',
        'azure_display_name' => 'displayName',
        'azure_first_name' => 'givenName',
        'azure_last_name' => 'surname',
        'azure_job_title' => 'jobTitle',
        'azure_department' => 'department',
        'azure_office_location' => 'officeLocation',
        'azure_mobile_phone' => 'mobilePhone',
        'azure_business_phones' => 'businessPhones',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sincronización de Usuario
    |--------------------------------------------------------------------------
    */
    'user_sync' => [
        // Crear usuario automáticamente si no existe en BD local
        'auto_create' => true,
        
        // Actualizar datos del usuario en cada login
        'update_on_login' => true,
        
        // Campos que se actualizan automáticamente
        'sync_fields' => [
            'azure_email',
            'azure_display_name',
            'azure_first_name',
            'azure_last_name',
            'azure_job_title',
            'azure_department',
        ],
        
        // Sincronizar foto del usuario
        'sync_photo' => false,
        'photo_path' => __DIR__ . '/../public/uploads/avatars',
    ],

    /*
    |--------------------------------------------------------------------------
    | Grupos de Azure AD
    |--------------------------------------------------------------------------
    | Mapeo de grupos de Azure AD a roles del sistema
    */
    'groups' => [
        'enabled' => false, // Habilitar si se usan grupos de Azure
        'fetch_on_login' => false,
        
        // Mapeo de Object ID de grupo a rol del sistema
        'role_mapping' => [
            // Ejemplo:
            // 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx' => 'admin',
            // 'yyyyyyyy-yyyy-yyyy-yyyy-yyyyyyyyyyyy' => 'revisor',
        ],
        
        // Rol por defecto si no coincide con ningún grupo
        'default_role' => 'usuario',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles del Sistema
    |--------------------------------------------------------------------------
    | Define los roles disponibles y sus configuraciones
    */
    'roles' => [
        'admin' => [
            'name' => 'Administrador',
            'description' => 'Acceso total al sistema',
            'permissions' => ['*'], // Todos los permisos
            'redirect_after_login' => '/admin/dashboard',
        ],
        'revisor' => [
            'name' => 'Revisor',
            'description' => 'Revisa requisiciones antes de autorización',
            'permissions' => [
                'requisiciones.revisar',
                'requisiciones.ver',
                'reportes.ver',
            ],
            'redirect_after_login' => '/pending-reviews',
        ],
        'autorizador' => [
            'name' => 'Autorizador',
            'description' => 'Autoriza requisiciones por centro de costo',
            'permissions' => [
                'requisiciones.autorizar',
                'requisiciones.ver',
                'reportes.ver',
            ],
            'redirect_after_login' => '/mis-autorizaciones',
        ],
        'usuario' => [
            'name' => 'Usuario',
            'description' => 'Crea y gestiona sus requisiciones',
            'permissions' => [
                'requisiciones.crear',
                'requisiciones.ver_propias',
                'requisiciones.editar_propias',
            ],
            'redirect_after_login' => '/dashboard',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dominios Permitidos
    |--------------------------------------------------------------------------
    | Lista de dominios de email permitidos para autenticación
    */
    'allowed_domains' => [
        'iga.edu',
        'sp.iga.edu',
        // Agregar más dominios según sea necesario
    ],

    /*
    |--------------------------------------------------------------------------
    | Restricciones de Acceso
    |--------------------------------------------------------------------------
    */
    'access_control' => [
        // Requiere verificación de email
        'require_verified_email' => false,
        
        // Requiere que el usuario esté activo en Azure
        'require_active_account' => true,
        
        // Lista negra de usuarios (emails)
        'blacklist' => [
            // 'usuario@ejemplo.com',
        ],
        
        // Lista blanca de usuarios (si está habilitada, solo estos pueden acceder)
        'whitelist_enabled' => false,
        'whitelist' => [
            // 'admin@iga.edu',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging y Depuración
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'log_path' => __DIR__ . '/../storage/logs/azure-auth.log',
        'log_level' => 'info', // 'debug', 'info', 'warning', 'error'
        
        // Log de intentos de autenticación
        'log_auth_attempts' => true,
        
        // Log de tokens (¡CUIDADO EN PRODUCCIÓN!)
        'log_tokens' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Caché
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'driver' => 'file', // 'file', 'redis', 'memcached'
        'ttl' => 3600, // segundos
        
        // Cachear información de usuario
        'cache_user_info' => true,
        
        // Cachear grupos
        'cache_groups' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Validar estado (state parameter) en OAuth2
        'validate_state' => true,
        
        // Validar nonce en ID token
        'validate_nonce' => true,
        
        // Validar issuer del token
        'validate_issuer' => true,
        'expected_issuer' => 'https://login.microsoftonline.com/{tenant}/v2.0',
        
        // Validar audience del token
        'validate_audience' => true,
        
        // Prevenir ataques CSRF
        'csrf_protection' => true,
        
        // Tiempo de gracia para validación de tokens (segundos)
        'token_leeway' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Conexión de Respaldo
    |--------------------------------------------------------------------------
    | En caso de que Azure AD no esté disponible
    */
    'fallback' => [
        'enabled' => false,
        'method' => 'local', // 'local', 'ldap'
        'message' => 'Autenticación Azure temporalmente no disponible. Por favor, intente más tarde.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing / Desarrollo
    |--------------------------------------------------------------------------
    */
    'testing' => [
        'enabled' => getenv('APP_ENV') === 'development',
        
        // Usuario mock para testing
        'mock_user' => [
            'id' => 'test-user-id',
            'mail' => 'test@iga.edu',
            'displayName' => 'Usuario de Prueba',
            'givenName' => 'Usuario',
            'surname' => 'Prueba',
        ],
        
        // Bypass Azure login en desarrollo
        'bypass_azure' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Eventos y Callbacks
    |--------------------------------------------------------------------------
    | Funciones que se ejecutan en ciertos eventos
    */
    'events' => [
        // Después de login exitoso
        'on_login_success' => null, // function($user) { ... }
        
        // Después de login fallido
        'on_login_failed' => null, // function($error) { ... }
        
        // Después de logout
        'on_logout' => null, // function($user) { ... }
        
        // Cuando se crea un nuevo usuario
        'on_user_created' => null, // function($user) { ... }
        
        // Cuando se actualiza un usuario
        'on_user_updated' => null, // function($user) { ... }
    ],
];
