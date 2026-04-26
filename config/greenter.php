<?php

return [
    'credentials' => [
        'ruc' => env('SUNAT_RUC'),
        'user_sol' => env('SUNAT_USER_SOL'),
        'password_sol' => env('SUNAT_PASS_SOL'),
    ],
    'certificate' => env('SUNAT_CERTIFICATE_PATH'), // Ruta del archivo .pem

    'company' => [
        'name' => env('COMPANY_NAME', 'Mi Empresa SAC'),
        'trade_name' => env('COMPANY_TRADE_NAME', 'Mi Empresa'),
        'address' => env('COMPANY_ADDRESS', 'Av. Principal 123'),
        'ubigeo' => env('COMPANY_UBIGEO', '150101'),
        'distrito' => env('COMPANY_DISTRITO', 'Lima'),
        'provincia' => env('COMPANY_PROVINCIA', 'Lima'),
        'departamento' => env('COMPANY_DEPARTAMENTO', 'Lima'),
    ],
];
