<?php
/**
 * Application configuration.
 * Values come from the .env file in the project root (see .env.example).
 */
declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'name'     => Env::get('APP_NAME', 'E-FoodHub'),
        'env'      => Env::get('APP_ENV', 'development'),
        'debug'    => Env::get('APP_DEBUG', '0') === '1',
        'timezone' => Env::get('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),
    ],
    'db' => [
        'driver'   => Env::get('DB_DRIVER', 'mysql'),
        'host'     => Env::get('DB_HOST', '127.0.0.1'),
        'port'     => Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', 'kvpm_efoodhub'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
    ],
    'upload' => [
        'max_bytes'  => (int) Env::get('UPLOAD_MAX_BYTES', '2097152'),
        'mime_types' => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ],
        'max_dimension' => 5000,
    ],
    'session' => [
        'name'          => 'kvpm_session',
        'secure_cookie' => Env::get('SESSION_SECURE_COOKIE', '0') === '1',
        'idle_timeout'  => 60 * 60 * 2, // 2 hours of inactivity
    ],
    'security' => [
        'login_max_attempts'   => 5,
        'login_window_minutes' => 15,
        'password_min_length'  => 8,
    ],
    'pagination' => [
        'catalogue' => 12,
        'dashboard' => 15,
    ],
];
