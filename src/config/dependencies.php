<?php

return [
    'jwt-secret'=>'$bfg^&8#bvnn',
    'eloquent_ser'=>[
        'driver' => 'pgsql',
        'host' => 'localhost',
        'database' => 'genvideo',
        'port' => 5432,
        'username' => 'courseup',
        'password' => 'de78Y_lcydr',
        'charset' => 'utf-8'
    ],
    'eloquent_loc'=>[
        'driver' => 'pgsql',
        'host' => '45.92.176.207',
        'database' => 'genvideo',
        'port' => 5432,
        'username' => 'courseup',
        'password' => 'de78Y_lcydr',
        'charset' => 'utf-8'
    ],
    'smtp_config'=>[
        'smtp_server' => 'smtp-pulse.com',
        'smtp_port' => '465',
        'smtp_username' => 'info@analitika.school',
        'smtp_password' => '83ZYsZQ4Dd',
        'encryption' => 'ssl',
        'sender_email'   => 'service@scananalytics.ru',
        'sender_name'    => 'Школа Courseup',
    ],
    'keyIp' => ($_SERVER['SERVER_ADDR'] == '127.0.0.1') ? 'eloquent_loc': 'eloquent_ser',
];