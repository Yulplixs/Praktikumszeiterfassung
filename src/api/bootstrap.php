<?php

declare(strict_types=1);

// Gemeinsamer Einstieg aller API-Endpunkte: Autoloader, DB-Verbindung, JSON-Antworten.

spl_autoload_register(static function (string $class): void {
    // App\Repository\Foo -> src/repository/Foo.php
    $parts = explode('\\', $class);
    if (count($parts) !== 3 || $parts[0] !== 'App') {
        return;
    }
    $file = __DIR__ . '/../' . strtolower($parts[1]) . '/' . $parts[2] . '.php';
    if (is_file($file)) {
        require $file;
    }
});

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        // Lokale Zugangsdaten optional in src/config.php (nicht im Git), sonst XAMPP-Standard.
        $configFile = __DIR__ . '/../config.php';
        $config = (is_file($configFile) ? require $configFile : []) + [
            'dsn' => 'mysql:host=127.0.0.1;dbname=praktikumsbetreuung;charset=utf8mb4',
            'user' => 'root',
            'password' => '',
        ];
        $pdo = new PDO($config['dsn'], $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function antworte(mixed $daten, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($daten, JSON_UNESCAPED_UNICODE);
    exit;
}

set_exception_handler(static function (Throwable $e): void {
    if ($e instanceof InvalidArgumentException || $e instanceof DomainException) {
        antworte(['fehler' => $e->getMessage()], 422);
    }
    error_log((string) $e);
    antworte(['fehler' => 'Interner Fehler. Details im PHP-Fehlerlog.'], 500);
});
