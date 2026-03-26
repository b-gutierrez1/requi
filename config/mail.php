<?php

return [
    'default' => 'smtp',

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => 'smtp.office365.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'bgutierrez@sp.iga.edu',
            'password' => 'Bryangutierrez1',
            'timeout' => 10,
        ],
    ],

    'from' => [
        'address' => 'bgutierrez@sp.iga.edu',
        'name' => 'Sistema de Requisiciones',
    ],

    'test_mode' => true,
    'test_recipient' => 'bgutierrez@sp.iga.edu',
    'skip_sending' => false,
];
