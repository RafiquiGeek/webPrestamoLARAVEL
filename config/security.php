<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the security features of the database synchronization
    | system including firewall rules, rate limiting, and access controls.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Firewall Configuration
    |--------------------------------------------------------------------------
    */

    'firewall' => [
        'enabled' => env('SECURITY_FIREWALL_ENABLED', true),

        // Rate limiting per IP
        'rate_limit' => [
            'requests_per_minute' => env('SECURITY_RATE_LIMIT_PER_MINUTE', 60),
            'sync_operations_per_minute' => env('SECURITY_SYNC_RATE_LIMIT', 100),
        ],

        // Allowed countries (ISO country codes)
        'allowed_countries' => explode(',', env('SECURITY_ALLOWED_COUNTRIES', 'PE,US,ES')),

        // Emergency mode configuration
        'emergency_mode' => [
            'alert_threshold' => env('SECURITY_ALERT_THRESHOLD', 20),
            'duration_minutes' => env('SECURITY_EMERGENCY_DURATION', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist/Blacklist
    |--------------------------------------------------------------------------
    */

    'ip_access' => [
        // Always allowed IPs (internal networks)
        'whitelist' => explode(',', env('SECURITY_IP_WHITELIST', '127.0.0.1,192.168.1.0/24,10.0.0.0/8')),

        // Permanently blocked IPs
        'blacklist' => explode(',', env('SECURITY_IP_BLACKLIST', '')),

        // Auto-block after failed attempts
        'auto_block' => [
            'enabled' => env('SECURITY_AUTO_BLOCK_ENABLED', true),
            'max_attempts' => env('SECURITY_MAX_FAILED_ATTEMPTS', 15),
            'block_duration_hours' => env('SECURITY_BLOCK_DURATION_HOURS', 24),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    */

    'encryption' => [
        'algorithm' => env('SECURITY_ENCRYPTION_ALGORITHM', 'AES-256-CBC'),
        'backup_encryption' => env('SECURITY_BACKUP_ENCRYPTION', true),
        'sensitive_fields_encryption' => env('SECURITY_ENCRYPT_SENSITIVE_FIELDS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Alerting
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'security_alerts' => env('SECURITY_ALERTS_ENABLED', true),
        'slack_webhook' => env('SECURITY_SLACK_WEBHOOK'),
        'email_alerts' => env('SECURITY_EMAIL_ALERTS_ENABLED', true),
        'alert_recipients' => explode(',', env('SECURITY_ALERT_RECIPIENTS', 'admin@example.com')),

        // Metrics retention
        'metrics_retention_days' => env('SECURITY_METRICS_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Security
    |--------------------------------------------------------------------------
    */

    'database' => [
        'connection_timeout' => env('SECURITY_DB_CONNECTION_TIMEOUT', 30),
        'query_timeout' => env('SECURITY_DB_QUERY_TIMEOUT', 60),
        'max_connections_per_host' => env('SECURITY_DB_MAX_CONNECTIONS', 10),

        // SSL/TLS requirements
        'require_ssl' => env('SECURITY_DB_REQUIRE_SSL', false),
        'verify_certificates' => env('SECURITY_DB_VERIFY_CERTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Attack Detection Patterns
    |--------------------------------------------------------------------------
    */

    'attack_patterns' => [
        'sql_injection' => [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bDELETE\b|\bUPDATE\b|\bDROP\b)/i',
            '/(\bOR\s+1=1|\bAND\s+1=1)/i',
            '/(\'|\"|;|--|\#|\*|\bxp_cmdshell\b)/i',
        ],

        'xss' => [
            '/(<script|<iframe|<object|<embed|javascript:|data:)/i',
        ],

        'command_injection' => [
            '/(\||&|;|\$\(|\`)/i',
        ],

        'path_traversal' => [
            '/(\.\.\/|\.\.\\\|%2e%2e%2f)/i',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Malicious Bot Detection
    |--------------------------------------------------------------------------
    */

    'bot_detection' => [
        'enabled' => env('SECURITY_BOT_DETECTION_ENABLED', true),

        'malicious_bots' => [
            'sqlmap', 'nikto', 'nessus', 'openvas', 'nmap',
            'masscan', 'zap', 'burp', 'metasploit', 'w3af',
            'skipfish', 'arachni', 'wpscan', 'dirb', 'gobuster',
        ],

        'suspicious_user_agents' => [
            'bot', 'crawler', 'spider', 'scraper', 'parser',
            'extractor', 'scanner', 'monitor', 'checker',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Security
    |--------------------------------------------------------------------------
    */

    'backup' => [
        'encryption_required' => env('SECURITY_BACKUP_ENCRYPTION_REQUIRED', true),
        'compression_enabled' => env('SECURITY_BACKUP_COMPRESSION', true),
        'retention_days' => env('SECURITY_BACKUP_RETENTION_DAYS', 30),
        'verify_integrity' => env('SECURITY_BACKUP_VERIFY_INTEGRITY', true),

        // Storage security
        'storage_permissions' => 0640,
        'directory_permissions' => 0755,
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Settings
    |--------------------------------------------------------------------------
    */

    'compliance' => [
        'audit_log_retention_days' => env('SECURITY_AUDIT_RETENTION_DAYS', 365),
        'require_user_activity_log' => env('SECURITY_REQUIRE_USER_ACTIVITY_LOG', true),
        'data_masking_enabled' => env('SECURITY_DATA_MASKING_ENABLED', true),
        'gdpr_compliance' => env('SECURITY_GDPR_COMPLIANCE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Security
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'max_query_execution_time' => env('SECURITY_MAX_QUERY_TIME', 30),
        'max_records_per_sync' => env('SECURITY_MAX_RECORDS_PER_SYNC', 1000),
        'sync_batch_size' => env('SECURITY_SYNC_BATCH_SIZE', 100),
        'memory_limit_mb' => env('SECURITY_MEMORY_LIMIT_MB', 128),
    ],

];
