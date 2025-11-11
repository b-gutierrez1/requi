<?php
/**
 * Configuración de Base de Datos
 * 
 * Este archivo contiene toda la configuración relacionada con la base de datos:
 * - Conexiones disponibles (MySQL, PostgreSQL, etc.)
 * - Configuración de pooling
 * - Configuración de caché de consultas
 * - Configuración de migraciones
 * 
 * @package RequisicionesMVC
 * @version 2.0
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Conexión por Defecto
    |--------------------------------------------------------------------------
    | Define qué conexión se usará por defecto en la aplicación
    */
    'default' => getenv('DB_CONNECTION') ?: 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de Conexión
    |--------------------------------------------------------------------------
    | Aquí se definen todas las conexiones disponibles
    */
    'connections' => [
        
        /*
        |----------------------------------------------------------------------
        | Conexión MySQL Principal
        |----------------------------------------------------------------------
        */
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: 3306,
            'database' => 'bd_prueba',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => 'InnoDB',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false, // Conexiones persistentes
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Conexión MySQL de Testing
        |----------------------------------------------------------------------
        */
        'mysql_test' => [
            'driver' => 'mysql',
            'host' => getenv('DB_TEST_HOST') ?: 'localhost',
            'port' => getenv('DB_TEST_PORT') ?: 3306,
            'database' => getenv('DB_TEST_DATABASE') ?: 'bd_prueba_test',
            'username' => getenv('DB_TEST_USERNAME') ?: 'root',
            'password' => getenv('DB_TEST_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => 'InnoDB',
        ],

        /*
        |----------------------------------------------------------------------
        | Conexión PostgreSQL (para futuro)
        |----------------------------------------------------------------------
        */
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: 5432,
            'database' => getenv('DB_DATABASE') ?: 'bd_prueba',
            'username' => getenv('DB_USERNAME') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
        ],

        /*
        |----------------------------------------------------------------------
        | Conexión SQLite (para desarrollo/testing rápido)
        |----------------------------------------------------------------------
        */
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../storage/database/database.sqlite',
            'prefix' => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Pool de Conexiones
    |--------------------------------------------------------------------------
    */
    'pool' => [
        'enabled' => false,
        'min_connections' => 2,
        'max_connections' => 10,
        'idle_timeout' => 60, // segundos
        'wait_timeout' => 30, // segundos
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Caché de Consultas
    |--------------------------------------------------------------------------
    */
    'query_cache' => [
        'enabled' => false,
        'ttl' => 3600, // segundos
        'driver' => 'file', // 'file', 'redis', 'memcached'
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Query Log
    |--------------------------------------------------------------------------
    | Log de todas las consultas SQL ejecutadas
    */
    'log_queries' => [
        'enabled' => getenv('DB_LOG_QUERIES') === 'true',
        'slow_query_threshold' => 1000, // milisegundos
        'log_path' => __DIR__ . '/../storage/logs/queries.log',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Migraciones
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table' => 'migrations',
        'path' => __DIR__ . '/../database/migrations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seeds
    |--------------------------------------------------------------------------
    */
    'seeds' => [
        'path' => __DIR__ . '/../database/seeds',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Backups
    |--------------------------------------------------------------------------
    */
    'backups' => [
        'enabled' => true,
        'path' => __DIR__ . '/../storage/backups',
        'schedule' => 'daily', // 'hourly', 'daily', 'weekly'
        'keep_days' => 30,
        'compress' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Transacciones
    |--------------------------------------------------------------------------
    */
    'transactions' => [
        'max_retries' => 3,
        'retry_delay' => 100, // milisegundos
        'deadlock_detection' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tablas del Sistema
    |--------------------------------------------------------------------------
    | Nombres de las tablas principales (para fácil referencia)
    */
    'tables' => [
        'usuarios' => 'usuarios',
        'orden_compra' => 'orden_compra',
        'detalle_items' => 'detalle_items',
        'distribucion_gasto' => 'distribucion_gasto',
        'centro_de_costo' => 'centro_de_costo',
        'cuenta_contable' => 'cuenta_contable',
        'autorizacion_flujo' => 'autorizacion_flujo',
        'autorizacion_centro_costo' => 'autorizacion_centro_costo',
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
    | Configuración de Optimización
    |--------------------------------------------------------------------------
    */
    'optimization' => [
        // Usar prepared statements cache
        'use_prepared_cache' => true,
        
        // Eager loading por defecto para relaciones comunes
        'eager_load_defaults' => [
            'OrdenCompra' => ['items', 'distribucionGasto', 'archivos'],
            'Usuario' => ['roles'],
        ],
        
        // Índices recomendados (para documentación)
        'recommended_indexes' => [
            'orden_compra' => ['usuario_id', 'fecha', 'estado'],
            'autorizacion_flujo' => ['orden_compra_id', 'estado'],
            'distribucion_gasto' => ['orden_compra_id', 'centro_costo_id'],
            'historial_requisicion' => ['orden_compra_id', 'fecha_cambio'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Replicación (para futuro escalamiento)
    |--------------------------------------------------------------------------
    */
    'replication' => [
        'enabled' => false,
        'read_write_split' => false,
        'master' => 'mysql',
        'slaves' => [
            // 'mysql_slave_1',
            // 'mysql_slave_2',
        ],
        'load_balancing' => 'random', // 'random', 'round_robin', 'weighted'
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad de BD
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Encriptar datos sensibles en BD
        'encrypt_sensitive_data' => false,
        'encryption_key' => getenv('DB_ENCRYPTION_KEY'),
        
        // Campos que deben ser encriptados
        'encrypted_fields' => [
            // 'usuarios' => ['password_reset_token'],
        ],
        
        // SQL Injection protection
        'use_prepared_statements' => true,
        'escape_identifiers' => true,
        
        // Auditoría
        'audit_enabled' => true,
        'audit_tables' => ['orden_compra', 'autorizacion_flujo'],
    ],
];
