<?php
/**
 * Configuración General de la Aplicación
 * 
 * Este archivo contiene la configuración general del sistema:
 * - Información de la aplicación
 * - Configuración de entorno
 * - Rutas base
 * - Configuración de zona horaria
 * - Configuración de errores
 * - Configuración de sesión
 * 
 * @package RequisicionesMVC
 * @version 2.0
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Nombre de la Aplicación
    |--------------------------------------------------------------------------
    */
    'name' => 'Sistema de Requisiciones',
    'short_name' => 'ReqSys',
    'version' => '2.0.0',

    /*
    |--------------------------------------------------------------------------
    | Entorno de la Aplicación
    |--------------------------------------------------------------------------
    | Valores: 'development', 'staging', 'production'
    */
    'environment' => getenv('APP_ENV') ?: 'development',

    /*
    |--------------------------------------------------------------------------
    | Modo Debug
    |--------------------------------------------------------------------------
    | Cuando está en true, se muestran errores detallados
    */
    'debug' => getenv('APP_DEBUG') !== 'false',

    /*
    |--------------------------------------------------------------------------
    | URL Base de la Aplicación
    |--------------------------------------------------------------------------
    */
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'base_path' => getenv('APP_BASE_PATH') ?: '/requi-mvc',
    'public_path' => getenv('APP_PUBLIC_PATH') ?: '/requi-mvc/public',

    /*
    |--------------------------------------------------------------------------
    | Zona Horaria
    |--------------------------------------------------------------------------
    */
    'timezone' => 'America/Guatemala',

    /*
    |--------------------------------------------------------------------------
    | Locale / Idioma
    |--------------------------------------------------------------------------
    */
    'locale' => 'es_GT',
    'fallback_locale' => 'es',

    /*
    |--------------------------------------------------------------------------
    | Configuración de Sesión
    |--------------------------------------------------------------------------
    */
    'session' => [
        'name' => 'REQSYS_SESSION',
        'lifetime' => 120, // minutos
        'path' => '/',
        'domain' => null,
        'secure' => false, // true en producción con HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Cookies
    |--------------------------------------------------------------------------
    */
    'cookie' => [
        'prefix' => 'reqsys_',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Logging
    |--------------------------------------------------------------------------
    */
    'log' => [
        'enabled' => true,
        'path' => __DIR__ . '/../storage/logs',
        'filename' => 'app.log',
        'level' => 'debug', // debug, info, warning, error
        'max_files' => 30, // días de retención
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Uploads
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'path' => __DIR__ . '/../public/uploads',
        'max_size' => 10 * 1024 * 1024, // 10MB en bytes
        'allowed_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/jpg',
            'image/png',
        ],
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Paginación
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_per_page' => 10,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Roles y Permisos
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'admin' => 'Administrador',
        'revisor' => 'Revisor',
        'autorizador' => 'Autorizador',
        'usuario' => 'Usuario',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Email
    |--------------------------------------------------------------------------
    */
    'mail' => [
        'enabled' => true,
        'from_address' => 'noreply@iga.edu',
        'from_name' => 'Sistema de Requisiciones',
        'reply_to' => 'soporte@iga.edu',
        'test_mode' => true, // En true, todos los emails van a test_recipient
        'test_recipient' => 'bgutierrez@sp.iga.edu',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled' => true,
        'channels' => ['email'], // 'email', 'database', 'slack'
        'auto_reminders' => true,
        'reminder_interval' => 24, // horas
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    */
    'security' => [
        'csrf_protection' => true,
        'csrf_token_name' => '_token',
        'password_min_length' => 8,
        'session_timeout' => 120, // minutos
        'max_login_attempts' => 5,
        'lockout_duration' => 15, // minutos
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'driver' => 'file', // 'file', 'redis', 'memcached'
        'path' => __DIR__ . '/../storage/cache',
        'ttl' => 3600, // segundos
    ],

    /*
    |--------------------------------------------------------------------------
    | Proveedores de Servicios
    |--------------------------------------------------------------------------
    | Clases que se registrarán automáticamente al iniciar la aplicación
    */
    'providers' => [
        // App\Services\AuthService::class,
        // App\Services\EmailService::class,
        // App\Services\RequisicionService::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Aliases
    |--------------------------------------------------------------------------
    | Atajos para acceder a clases comunes
    */
    'aliases' => [
        'Config' => App\Helpers\Config::class,
        'Session' => App\Helpers\Session::class,
        'Request' => App\Helpers\Request::class,
        'Response' => App\Helpers\Response::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración Específica del Sistema
    |--------------------------------------------------------------------------
    */
    'system' => [
        'company_name' => 'Instituto Guatemalteco Americano',
        'company_short_name' => 'IGA',
        'logo_path' => '/requi-mvc/public/images/logo.png',
        'support_email' => 'soporte@iga.edu',
        'support_phone' => '+502 2422-5555',
    ],

    /*
    |--------------------------------------------------------------------------
    | Estados de Requisición
    |--------------------------------------------------------------------------
    */
    'estados_requisicion' => [
        'pendiente_revision' => [
            'label' => 'Pendiente de Revisión',
            'color' => 'warning',
            'icon' => 'fa-clock',
        ],
        'pendiente_autorizacion' => [
            'label' => 'Pendiente de Autorización',
            'color' => 'info',
            'icon' => 'fa-hourglass-half',
        ],
        'autorizada' => [
            'label' => 'Autorizada',
            'color' => 'success',
            'icon' => 'fa-check-circle',
        ],
        'rechazada' => [
            'label' => 'Rechazada',
            'color' => 'danger',
            'icon' => 'fa-times-circle',
        ],
        'completada' => [
            'label' => 'Completada',
            'color' => 'primary',
            'icon' => 'fa-check-double',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Monedas
    |--------------------------------------------------------------------------
    */
    'monedas' => [
        'GTQ' => [
            'nombre' => 'Quetzales',
            'simbolo' => 'Q',
            'codigo' => 'GTQ',
        ],
        'USD' => [
            'nombre' => 'Dólares',
            'simbolo' => '$',
            'codigo' => 'USD',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Formas de Pago
    |--------------------------------------------------------------------------
    */
    'formas_pago' => [
        'efectivo' => 'Efectivo',
        'cheque' => 'Cheque',
        'transferencia' => 'Transferencia Bancaria',
        'tarjeta_credito' => 'Tarjeta de Crédito',
        'tarjeta_debito' => 'Tarjeta de Débito',
    ],
];
