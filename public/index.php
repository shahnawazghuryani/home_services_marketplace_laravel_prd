<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$defaultAppRoot = realpath(__DIR__.'/..') ?: __DIR__.'/..';
$cpanelSiblingAppRoot = realpath(__DIR__.'/../gharkaam_app');
$appRoot = is_file($defaultAppRoot.'/vendor/autoload.php')
    ? $defaultAppRoot
    : ($cpanelSiblingAppRoot ?: $defaultAppRoot);

if (! function_exists('comingSoonEnabled')) {
    function comingSoonEnabled(string $envPath): bool
    {
        if (! is_file($envPath)) {
            return false;
        }

        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));

            if ($key !== 'COMING_SOON') {
                continue;
            }

            return filter_var(trim($value, "\"'"), FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }
}

if (comingSoonEnabled($appRoot.'/.env') && is_file(__DIR__.'/coming-soon.html')) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Retry-After: 3600');
    readfile(__DIR__.'/coming-soon.html');
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appRoot.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
