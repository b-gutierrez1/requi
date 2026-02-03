<?php

return [
    'default' => 'smtp',

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'no-reply-moodle@iga.edu',
            'password' => 'xmlxdkbgtkyzehdi',
            'timeout' => null,
        ],
    ],

    'from' => [
        'address' => 'no-reply-moodle@iga.edu',
        'name' => 'Sistema de Requisiciones',
    ],

    'test_mode' => true,
    'test_recipient' => 'bgutierrez@sp.iga.edu',
    'skip_sending' => false,
];
