<?php

declare(strict_types=1);

$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

$config = require __DIR__ . '/config/app.php';
date_default_timezone_set($config['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session_name']);
    session_start();
}

if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = __DIR__ . '/app/' . $relative . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

$dbFile = require __DIR__ . '/config/database.php';
if (!file_exists($dbFile['database'])) {
    require __DIR__ . '/database/seed.php';
} else {
    // Hostinger: se o banco ainda for o catálogo antigo, troca pela Coleção Bali sozinho
    $baliManifest = __DIR__ . '/public/uploads/products/bali/manifest.json';
    if (is_file($baliManifest)) {
        try {
            $pdo = App\Core\Database::connection();
            $version = (string) ($pdo->query("SELECT value FROM settings WHERE key = 'catalog_version'")->fetchColumn() ?: '');
            $baliCount = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE slug LIKE 'bali-look-%'")->fetchColumn();
            if ($version !== 'bali-v2' || $baliCount < 50) {
                require_once __DIR__ . '/database/seed_bali_catalog.php';
                mandalla_seed_bali_catalog($pdo, __DIR__);
            }
        } catch (Throwable $e) {
            if (!empty($config['debug'])) {
                throw $e;
            }
        }
    }
}
