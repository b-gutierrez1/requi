<?php
/**
 * Configuración de Base de Datos PARA PRODUCCIÓN
 * 
 * Este archivo contiene la configuración de BD lista para producción.
 * REEMPLAZAR config/database.php con este contenido.
 * 
 * @package RequisicionesMVC
 * @version 2.0 - Production Ready
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Conexión por Defecto
    |--------------------------------------------------------------------------
    */
    'default' => getenv('DB_CONNECTION') ?: 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de Conexión
    |--------------------------------------------------------------------------
    */
    'connections' => [
        
        /*
        |----------------------------------------------------------------------
        | Conexión MySQL Principal - PRODUCCIÓN
        |----------------------------------------------------------------------
        */
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: 3306,
            'database' => getenv('DB_DATABASE') ?: 'requisiciones_prod',  // ← CAMBIADO
            'username' => getenv('DB_USERNAME') ?: 'requi_user',          // ← CAMBIADO
            'password' => getenv('DB_PASSWORD') ?: '',                    // ← USAR ENV
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,                                             // ← ACTIVADO
            'engine' => 'InnoDB',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,         // ← SSL
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Conexión MySQL de Testing - OPCIONAL
        |----------------------------------------------------------------------
        */
        'mysql_test' => [
            'driver' => 'mysql',
            'host' => getenv('DB_TEST_HOST') ?: 'localhost',
            'port' => getenv('DB_TEST_PORT') ?: 3306,
            'database' => getenv('DB_TEST_DATABASE') ?: 'requisiciones_test',
            'username' => getenv('DB_TEST_USERNAME') ?: 'requi_test',
            'password' => getenv('DB_TEST_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Pool de Conexiones - DESHABILITADO EN PROD
    |--------------------------------------------------------------------------
    */
    'pool' => [
        'enabled' => false,
        'min_connections' => 2,
        'max_connections' => 10,
        'idle_timeout' => 60,
        'wait_timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Caché de Consultas - DESHABILITADO EN PROD INICIAL
    |--------------------------------------------------------------------------
    */
    'query_cache' => [
        'enabled' => false,
        'ttl' => 3600,
        'driver' => 'file',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Query Log - SOLO ERRORES EN PROD
    |--------------------------------------------------------------------------
    */
    'log_queries' => [
        'enabled' => getenv('DB_LOG_QUERIES') === 'true',              // ← CONTROLADO POR ENV
        'slow_query_threshold' => 2000,                               // ← MÁS ESTRICTO
        'log_path' => __DIR__ . '/../storage/logs/queries.log',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Migraciones
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table' => 'migrations',
        'path' => __DIR__ . '/../storage/migrations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seeds
    |--------------------------------------------------------------------------
    */
    'seeds' => [
        'path' => __DIR__ . '/../storage/seeds',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Backups - HABILITADO EN PROD
    |--------------------------------------------------------------------------
    */
    'backups' => [
        'enabled' => getenv('BACKUP_ENABLED') !== 'false',             // ← HABILITADO
        'path' => __DIR__ . '/../storage/backups',
        'schedule' => getenv('BACKUP_SCHEDULE') ?: 'daily',
        'keep_days' => (int)(getenv('BACKUP_RETENTION_DAYS') ?: 30),
        'compress' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Transacciones
    |--------------------------------------------------------------------------
    */
    'transactions' => [
        'max_retries' => 3,
        'retry_delay' => 100,
        'deadlock_detection' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tablas del Sistema
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'usuarios' => 'usuarios',
        'requisiciones' => 'requisiciones',                            // ← ACTUALIZADO
        'detalle_items' => 'detalle_items',
        'distribucion_gasto' => 'distribucion_gasto',
        'centro_de_costo' => 'centro_de_costo',
        'cuenta_contable' => 'cuenta_contable',
        'autorizacion_flujo' => 'autorizacion_flujo',
        'autorizaciones' => 'autorizaciones',
        'persona_autorizada' => 'persona_autorizada',
        'autorizador_respaldo' => 'autorizador_respaldo',
        'autorizadores_metodos_pago' => 'autorizadores_metodos_pago',
        'autorizadores_cuentas_contables' => 'autorizadores_cuentas_contables',
        'archivos_adjuntos' => 'archivos_adjuntos',
        'historial_requisicion' => 'historial_requisicion',
        'recordatorios' => 'recordatorios',
        'facturas' => 'facturas',
        'ubicacion' => 'ubicacion',
        'unidad_de_negocio' => 'unidad_de_negocio',
        'unidad_requirente' => 'unidad_requirente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Optimización - HABILITADO EN PROD
    |--------------------------------------------------------------------------
    */
    'optimization' => [
        'use_prepared_cache' => true,                                   // ← HABILITADO
        
        'eager_load_defaults' => [
            'Requisicion' => ['items', 'distribucionGasto', 'archivos'], // ← ACTUALIZADO
            'Usuario' => ['roles'],
        ],
        
        'recommended_indexes' => [
            'requisiciones' => ['usuario_id', 'fecha_solicitud'],       // ← ACTUALIZADO
            'autorizacion_flujo' => ['requisicion_id', 'estado'],       // ← ACTUALIZADO
            'distribucion_gasto' => ['orden_compra_id', 'centro_costo_id'],
            'historial_requisicion' => ['orden_compra_id', 'fecha_cambio'],
            'autorizaciones' => ['requisicion_id', 'estado', 'autorizador_email'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Replicación - DESHABILITADO INICIALMENTE
    |--------------------------------------------------------------------------
    */
    'replication' => [
        'enabled' => false,
        'read_write_split' => false,
        'master' => 'mysql',
        'slaves' => [],
        'load_balancing' => 'random',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad de BD - HABILITADO EN PROD
    |--------------------------------------------------------------------------
    */
    'security' => [
        'encrypt_sensitive_data' => getenv('DB_ENCRYPT_DATA') === 'true',
        'encryption_key' => getenv('DB_ENCRYPTION_KEY'),
        
        'encrypted_fields' => [
            // 'usuarios' => ['password_reset_token'],
        ],
        
        'use_prepared_statements' => true,                              // ← OBLIGATORIO
        'escape_identifiers' => true,                                  // ← OBLIGATORIO
        
        'audit_enabled' => true,                                       // ← HABILITADO
        'audit_tables' => ['requisiciones', 'autorizacion_flujo', 'autorizaciones'],
    ],
];