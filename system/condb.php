<?php
declare(strict_types=1);

function pata_env(string $key, ?string $fallback = null): ?string
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $fallback;
    }

    return $value;
}

function pata_database_url_to_pdo(string $database_url, ?string $default_user, ?string $default_password): array
{
    $parts = parse_url($database_url);

    if ($parts === false || !isset($parts['scheme'])) {
        return [$database_url, $default_user, $default_password];
    }

    $scheme = strtolower($parts['scheme']);

    if (!in_array($scheme, ['postgres', 'postgresql', 'pgsql'], true)) {
        return [$database_url, $default_user, $default_password];
    }

    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 5432;
    $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
    $user = isset($parts['user']) ? rawurldecode($parts['user']) : $default_user;
    $password = isset($parts['pass']) ? rawurldecode($parts['pass']) : $default_password;

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    if (isset($parts['query'])) {
        parse_str($parts['query'], $query);

        if (!empty($query['sslmode'])) {
            $dsn .= ';sslmode=' . $query['sslmode'];
        }
    }

    return [$dsn, $user, $password];
}

function pata_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = pata_env('PATA_DB_DSN', pata_env('DATABASE_URL'));
    $user = pata_env('PATA_DB_USER', 'postgres');
    $password = pata_env('PATA_DB_PASSWORD', '');

    if ($dsn === null) {
        $dsn = 'pgsql:host=localhost;port=5432;dbname=pataconnect';
    }

    if (strpos($dsn, 'postgres://') === 0 || strpos($dsn, 'postgresql://') === 0 || strpos($dsn, 'pgsql://') === 0) {
        [$dsn, $user, $password] = pata_database_url_to_pdo($dsn, $user, $password);
    }

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
