<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the mobile API system
    |
    */

    'version' => env('API_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limiting' => [
        'auth' => [
            'attempts' => env('API_AUTH_RATE_LIMIT', 5),
            'decay_minutes' => env('API_AUTH_RATE_DECAY', 1),
        ],
        'general' => [
            'attempts' => env('API_GENERAL_RATE_LIMIT', 60),
            'decay_minutes' => env('API_GENERAL_RATE_DECAY', 1),
        ],
        'sensitive' => [
            'attempts' => env('API_SENSITIVE_RATE_LIMIT', 10),
            'decay_minutes' => env('API_SENSITIVE_RATE_DECAY', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Settings
    |--------------------------------------------------------------------------
    */

    'responses' => [
        'pagination' => [
            'default_per_page' => env('API_DEFAULT_PER_PAGE', 15),
            'max_per_page' => env('API_MAX_PER_PAGE', 100),
        ],
        'cache_ttl' => env('API_CACHE_TTL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        'max_file_size' => env('API_MAX_FILE_SIZE', 2048), // 2MB en KB
        'allowed_image_types' => ['jpg', 'jpeg', 'png'],
        'allowed_document_types' => ['pdf', 'doc', 'docx'],
        'storage_disk' => env('API_STORAGE_DISK', 'public'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rules
    |--------------------------------------------------------------------------
    */

    'business_rules' => [
        'loan' => [
            'min_amount' => env('API_LOAN_MIN_AMOUNT', 100),
            'max_amount' => env('API_LOAN_MAX_AMOUNT', 999999999.99),
            'tolerance_minutes' => env('API_PAYMENT_TOLERANCE_MINUTES', 15),
        ],
        'attendance' => [
            'tolerance_minutes' => env('API_ATTENDANCE_TOLERANCE_MINUTES', 15),
            'max_photo_size' => env('API_ATTENDANCE_PHOTO_SIZE', 2048),
        ],
        'provisional_funds' => [
            'max_pending_requests' => env('API_MAX_PENDING_FUNDS', 1),
            'max_amount' => env('API_MAX_FUND_AMOUNT', 10000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */

    'security' => [
        'token_expiry_hours' => env('API_TOKEN_EXPIRY_HOURS', 168), // 7 days
        'max_login_attempts' => env('API_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('API_LOCKOUT_DURATION', 900), // 15 minutes
        'require_device_name' => env('API_REQUIRE_DEVICE_NAME', true),
        'log_api_calls' => env('API_LOG_CALLS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'enabled' => env('API_NOTIFICATIONS_ENABLED', true),
        'channels' => [
            'push' => env('API_PUSH_NOTIFICATIONS', true),
            'email' => env('API_EMAIL_NOTIFICATIONS', true),
            'sms' => env('API_SMS_NOTIFICATIONS', false),
        ],
        'events' => [
            'loan_approved' => true,
            'payment_received' => true,
            'commitment_due' => true,
            'fund_approved' => true,
            'attendance_reminder' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */

    'integrations' => [
        'maps' => [
            'provider' => env('API_MAPS_PROVIDER', 'google'),
            'api_key' => env('API_MAPS_KEY'),
        ],
        'sms' => [
            'provider' => env('API_SMS_PROVIDER'),
            'api_key' => env('API_SMS_KEY'),
        ],
        'push' => [
            'firebase_key' => env('FIREBASE_SERVER_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    */

    'errors' => [
        'log_level' => env('API_LOG_LEVEL', 'error'),
        'include_trace' => env('API_INCLUDE_TRACE', false),
        'report_errors' => env('API_REPORT_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Documentation
    |--------------------------------------------------------------------------
    */

    'documentation' => [
        'enabled' => env('API_DOCS_ENABLED', true),
        'title' => 'Sistema Financiero - API Móvil',
        'description' => 'API RESTful para aplicaciones móviles del sistema de gestión financiera',
        'version' => '1.0',
        'contact' => [
            'name' => 'Soporte Técnico',
            'email' => env('API_CONTACT_EMAIL', 'soporte@empresa.com'),
        ],
    ],

];
