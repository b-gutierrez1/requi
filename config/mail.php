<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Email
    |--------------------------------------------------------------------------
    |
    | Configuración para el envío de correos electrónicos del sistema.
    |
    */

    'default' => getenv('MAIL_MAILER') ?: 'smtp',

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => getenv('MAIL_HOST') ?: 'smtp.mailtrap.io',
            'port' => getenv('MAIL_PORT') ?: 587,
            'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
            'username' => getenv('MAIL_USERNAME') ?: '',
            'password' => getenv('MAIL_PASSWORD') ?: '',
            'timeout' => null,
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => getenv('MAIL_SENDMAIL_PATH') ?: '/usr/sbin/sendmail -bs',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => getenv('MAIL_LOG_CHANNEL') ?: 'mail',
        ],

        'array' => [
            'transport' => 'array',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dirección de remitente global
    |--------------------------------------------------------------------------
    */

    'from' => [
        'address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@requisiciones.local',
        'name' => getenv('MAIL_FROM_NAME') ?: 'Sistema de Requisiciones',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modo de prueba
    |--------------------------------------------------------------------------
    |
    | Cuando está activado, los emails no se envían realmente sino que se
    | registran en el log.
    |
    */

    'test_mode' => getenv('MAIL_TEST_MODE') !== false ? (bool)getenv('MAIL_TEST_MODE') : true,

    /*
    |--------------------------------------------------------------------------
    | Email de prueba
    |--------------------------------------------------------------------------
    |
    | En modo de prueba, todos los emails se redirigen a esta dirección.
    |
    */

    'test_email' => getenv('MAIL_TEST_EMAIL') ?: 'test@example.com',
];

