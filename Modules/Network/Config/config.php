<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credenciales por Defecto
    |--------------------------------------------------------------------------
    |
    | Estas credenciales se usan cuando no se especifican en el router
    |
    */
    'default_username' => env('ROUTER_DEFAULT_USERNAME', 'admin'),
    'default_password' => env('ROUTER_DEFAULT_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Configuración de MikroTik
    |--------------------------------------------------------------------------
    */
    'mikrotik' => [
        'rest_port' => env('MIKROTIK_REST_PORT', null), // null = puerto por defecto (80/443)
        'use_https' => env('MIKROTIK_USE_HTTPS', true),
        'timeout' => env('MIKROTIK_TIMEOUT', 30),

        // Perfiles PPPoE por defecto
        'default_profile' => env('MIKROTIK_DEFAULT_PROFILE', 'default'),

        // Pool de IPs para clientes
        'ip_pool' => env('MIKROTIK_IP_POOL', '10.0.0.0/8'),

        // DNS Servers
        'dns_servers' => env('MIKROTIK_DNS_SERVERS', '8.8.8.8,8.8.4.4'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Huawei
    |--------------------------------------------------------------------------
    */
    'huawei' => [
        'api_url' => env('HUAWEI_API_URL', null),
        'api_token' => env('HUAWEI_API_TOKEN', null),
        'timeout' => env('HUAWEI_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check
    |--------------------------------------------------------------------------
    |
    | Configuración para el monitoreo de routers
    |
    */
    'health_check' => [
        'enabled' => env('NETWORK_HEALTH_CHECK_ENABLED', true),
        'interval' => env('NETWORK_HEALTH_CHECK_INTERVAL', 5), // minutos

        // Umbrales de alerta
        'thresholds' => [
            'cpu_warning' => 70,
            'cpu_critical' => 90,
            'memory_warning' => 80,
            'memory_critical' => 95,
            'clients_warning' => 85, // % de capacidad
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatización
    |--------------------------------------------------------------------------
    */
    'automation' => [
        'enabled' => env('NETWORK_AUTOMATION_ENABLED', true),

        // Reinicio automático cuando falla
        'auto_retry' => true,
        'max_retries' => 3,
        'retry_delay' => 60, // segundos
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'retention_days' => 90, // días que se guardan los logs
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Worker
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('QUEUE_CONNECTION', 'database'),
        'queue_name' => 'network',
        'timeout' => 120, // segundos
        'tries' => 3,
    ],
];
