<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'admin/api/*', 'admin/consultar-dni', 'admin/prestamos/*/check-contrato-mutuo', 'admin/prestamos/*/generate-contrato-mutuo', 'admin/prestamos/*/preview-contrato-mutuo', 'admin/prestamos/*/download-contrato-mutuo', 'proxy-dni/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*', 'https://appgs.pe', 'https://grupo.test', 'http://localhost:56439', 'http://localhost:57365', 'http://localhost:50478', 'http://localhost:61721', 'http://localhost:61800'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
