<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    */

    // Contraseña mínima requerida (caracteres)
    'password_min_length' => 12,

    // Requerir mayúsculas, minúsculas, números y símbolos
    'password_require_mixed_case' => true,
    'password_require_numbers' => true,
    'password_require_symbols' => true,

    // Rate limiting
    'rate_limits' => [
        'login_attempts' => 5,
        'login_decay_minutes' => 15,
        'register_attempts' => 3,
        'register_decay_hours' => 1,
        'create_ticket_limit' => 10,
        'create_ticket_decay_hours' => 1,
        'add_comment_limit' => 20,
        'add_comment_decay_hours' => 1,
    ],

    // Validación de archivos
    'file_upload' => [
        'max_size_mb' => 5,
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'txt'],
        'allowed_mime_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/zip',
            'text/plain',
        ],
    ],

    // Session
    'session_lifetime_minutes' => 480, // 8 horas
    'session_secure_cookies' => true, // Solo HTTPS
    'session_http_only' => true, // No accesible via JavaScript
    'session_same_site' => 'strict', // Protección contra CSRF

    // Headers HTTP de seguridad
    'http_headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    // CORS
    'cors' => [
        'allowed_origins' => ['https://conecta.dimak.local'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization'],
        'expose_headers' => [],
        'max_age' => 3600,
        'supports_credentials' => true,
    ],

    // Logging
    'log_security_events' => true,
    'log_failed_logins' => true,
    'log_file_uploads' => true,
    'log_admin_actions' => true,

    // IP Whitelist (opcional, deja vacío para deshabilitar)
    'ip_whitelist' => [],

    // Detectar cambios de IP durante sesión
    'detect_ip_changes' => true,

    // Invalidar sesión después de cambio de IP
    'invalidate_session_on_ip_change' => true,
];
