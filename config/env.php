<?php
declare(strict_types=1);

/**
 * Load .env file and make variables available via env()
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        throw new RuntimeException(".env file not found at: $path");
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, '"\'');
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

function env(string $key, mixed $default = null): mixed
{
    $val = getenv($key);
    return $val === false ? $default : $val;
}

// Load .env from project root (two levels up from config/)
loadEnv(dirname(__DIR__) . '/.env');
