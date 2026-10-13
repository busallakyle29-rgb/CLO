<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| CLO APPLICATION CONFIGURATION
|--------------------------------------------------------------------------
| City Legal Office Document Tracking System
|
| Supports:
| - Localhost access
| - Local Network / LAN access
|
| Example:
|
| PC:
| http://localhost/CLO
|
| Phone / Other PC:
| http://192.168.1. /CLO
|
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Asia/Manila');

/*
|--------------------------------------------------------------------------
| PRODUCTION MODE
|--------------------------------------------------------------------------
*/

function is_production(): bool
{
    $env = getenv('APP_ENV');

    if ($env === false) {
        return false;
    }

    return strtolower(trim($env)) === 'production';
}

/*
|--------------------------------------------------------------------------
| ENVIRONMENT VARIABLE HELPER
|--------------------------------------------------------------------------
*/

function env_value(
    string $key,
    string $default = ''
): string {

    $value = getenv($key);

    if (
        $value === false ||
        trim($value) === ''
    ) {
        return $default;
    }

    return trim($value);
}

/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION
|--------------------------------------------------------------------------
*/

function get_database_config(): array
{
    $isProduction = is_production();
    $databaseUser = env_value(
        'DB_USER',
        $isProduction ? '' : 'root'
    );
    $databasePassword = env_value('DB_PASS');

    if ($isProduction && ($databaseUser === '' || $databasePassword === '')) {
        throw new RuntimeException(
            'DB_USER and DB_PASS must be configured when APP_ENV is production.'
        );
    }

    return [

        /*
        |--------------------------------------------------------------
        | Database Host
        |--------------------------------------------------------------
        */

        'host' => env_value(
            'DB_HOST',
            '127.0.0.1'
        ),

        /*
        |--------------------------------------------------------------
        | Database Port
        |--------------------------------------------------------------
        */

        'port' => (int) env_value(
            'DB_PORT',
            '3306'
        ),

        /*
        |--------------------------------------------------------------
        | Database Name
        |--------------------------------------------------------------
        */

        'name' => env_value(
            'DB_NAME',
            'clo_document_tracking'
        ),

        /*
        |--------------------------------------------------------------
        | Database Username
        |--------------------------------------------------------------
        */

        'user' => $databaseUser,

        /*
        |--------------------------------------------------------------
        | Database Password
        |--------------------------------------------------------------
        */

        'pass' => $databasePassword,

        /*
        |--------------------------------------------------------------
        | Character Set
        |--------------------------------------------------------------
        */

        'charset' => env_value(
            'DB_CHARSET',
            'utf8mb4'
        ),

        /*
        |--------------------------------------------------------------
        | Connection Timeout
        |--------------------------------------------------------------
        */

        'connect_timeout' => (int) env_value(
            'DB_CONNECT_TIMEOUT',
            '5'
        ),
    ];
}

/*
|--------------------------------------------------------------------------
| APPLICATION CONFIGURATION
|--------------------------------------------------------------------------
*/

function get_app_config(): array
{
    /*
    |--------------------------------------------------------------------------
    | DETERMINE BASE URL
    |--------------------------------------------------------------------------
    |
    | If APP_URL is manually configured, use it.
    |
    | Otherwise automatically detect the host used by the browser.
    |
    | Example:
    |
    | When accessed from PC:
    |
    | http://localhost/CLO
    |
    | When accessed from phone:
    |
    | http://192.168.1.129/CLO
    |
    | When accessed from another PC:
    |
    | http://192.168.1.129/CLO
    |
    |--------------------------------------------------------------------------
    */

    $configuredUrl = getenv('APP_URL');

    /*
    |--------------------------------------------------------------------------
    | USE MANUAL APP_URL IF AVAILABLE
    |--------------------------------------------------------------------------
    */

    if (
        $configuredUrl !== false &&
        trim($configuredUrl) !== ''
    ) {

        $baseUrl = rtrim(
            trim($configuredUrl),
            '/'
        );

    } else {

        /*
        |--------------------------------------------------------------------------
        | AUTOMATIC HOST DETECTION
        |--------------------------------------------------------------------------
        */

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        /*
        |--------------------------------------------------------------------------
        | Remove accidental spaces
        |--------------------------------------------------------------------------
        */

        $host = trim($host);

        /*
        |--------------------------------------------------------------------------
        | Remove trailing slash
        |--------------------------------------------------------------------------
        */

        $host = rtrim(
            $host,
            '/'
        );

        /*
        |--------------------------------------------------------------------------
        | Build Application URL
        |--------------------------------------------------------------------------
        */

        $baseUrl =
            'http://' .
            $host .
            '/CLO';
    }

    /*
    |--------------------------------------------------------------------------
    | RETURN APPLICATION CONFIGURATION
    |--------------------------------------------------------------------------
    */

    return [

        /*
        |--------------------------------------------------------------
        | Application Name
        |--------------------------------------------------------------
        */

        'name' => env_value(
            'APP_NAME',
            'City Legal Office Document Tracking System'
        ),

        /*
        |--------------------------------------------------------------
        | Base URL
        |--------------------------------------------------------------
        */

        'base_url' => $baseUrl,

        /*
        |--------------------------------------------------------------
        | Session Name
        |--------------------------------------------------------------
        */

        'session_name' => env_value(
            'APP_SESSION_NAME',
            'CLOSESSID'
        ),

        /*
        |--------------------------------------------------------------
        | Remember Me Cookie
        |--------------------------------------------------------------
        */

        'remember_cookie_name' => env_value(
            'APP_REMEMBER_COOKIE',
            'CLO_REMEMBER'
        ),

        /*
        |--------------------------------------------------------------
        | Remember Me Lifetime
        |--------------------------------------------------------------
        */

        'remember_lifetime_days' => (int) env_value(
            'APP_REMEMBER_DAYS',
            '30'
        ),

        /*
        |--------------------------------------------------------------
        | Password Reset Lifetime
        |--------------------------------------------------------------
        */

        'reset_lifetime_minutes' => (int) env_value(
            'APP_RESET_MINUTES',
            '30'
        ),
    ];
}
