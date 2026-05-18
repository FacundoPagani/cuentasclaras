<?php

declare(strict_types=1);

return [
    'app_name' => getenv('APP_NAME') ?: 'CuentasClaras',
    'env' => getenv('APP_ENV') ?: 'production',
    'base_url' => getenv('APP_URL') ?: '',
    'database_path' => getenv('DB_PATH') ?: dirname(__DIR__) . '/storage/cuentasclaras.sqlite',
    'session_name' => getenv('SESSION_NAME') ?: 'cuentasclaras_session',
    'session_secure' => filter_var(getenv('SESSION_SECURE') ?: '1', FILTER_VALIDATE_BOOLEAN),
    'session_lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 7200),
];
