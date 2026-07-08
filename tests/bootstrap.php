<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$envPath = dirname(__DIR__) . '/.env';
if (!is_file($envPath)) {
    return;
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    return;
}

foreach ($lines as $line) {
    $trimmed = trim($line);

    if ($trimmed === '' || str_starts_with($trimmed, '#')) {
        continue;
    }

    $parts = explode('=', $trimmed, 2);
    if (count($parts) !== 2) {
        continue;
    }

    $key = trim($parts[0]);
    $value = trim($parts[1]);

    if ($key === '' || getenv($key) !== false) {
        continue;
    }

    $value = trim($value, "\"'");

    putenv($key . '=' . $value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
