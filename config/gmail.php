<?php
return [
    'smtp' => [
        'name' => 'smtp.gmail.com',
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'connection_class' => 'login',
        'connection_config' => [
            'username' => 'turnosclinicaunco@gmail.com',
            'password' => 'snbc bccp nyow wbce',
            'ssl'      => 'tls',
        ],
        'from' => [
            'email' => 'turnosclinicaunco@gmail.com',
            'name'  => 'Turnos Clínica UNCo'
        ],
    ],
];
