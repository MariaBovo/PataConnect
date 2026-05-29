<?php
declare(strict_types=1);

require_once __DIR__ . '/condb.php';

function pata_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('PATASESSID');

    if (!headers_sent()) {
        $secure_cookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secure_cookie,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params(0, '/', '', $secure_cookie, true);
        }
    }

    session_start();
}

function pata_current_user(): ?array
{
    pata_start_session();

    return $_SESSION['auth_user'] ?? null;
}

function pata_is_authenticated(): bool
{
    return pata_current_user() !== null;
}

function pata_bool_value($value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return (int) $value === 1;
    }

    return in_array(strtolower((string) $value), ['true', 't', 'yes', 'y', '1'], true);
}

function pata_find_user_by_username(string $username): ?array
{
    $query = 'SELECT "id", "full_name", "username", "pass_hash", "privileged" FROM "users" WHERE "username" = :username LIMIT 1';
    $statement = pata_db()->prepare($query);
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();

    return $user ?: null;
}

function pata_local_login_user(string $username, string $password): ?array
{
    // Temporary local login while the database is not available.
    if (!hash_equals('admin', $username) || !hash_equals('pata123', $password)) {
        return null;
    }

    return [
        'id' => 0,
        'full_name' => 'Administrador Pata',
        'username' => 'admin',
        'privileged' => true,
    ];
}

function pata_set_authenticated_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'username' => (string) $user['username'],
        'privileged' => pata_bool_value($user['privileged']),
    ];

    unset($_SESSION['csrf_token']);
}

function pata_login(string $username, string $password): array
{
    pata_start_session();

    $username = trim($username);

    if ($username === '' || $password === '') {
        return ['ok' => false, 'message' => 'Preencha usuário e senha para entrar.'];
    }

    $local_user = pata_local_login_user($username, $password);

    if ($local_user !== null) {
        pata_set_authenticated_user($local_user);

        return ['ok' => true, 'message' => 'Login realizado com sucesso.'];
    }

    try {
        $user = pata_find_user_by_username($username);
    } catch (Throwable $error) {
        return ['ok' => false, 'message' => 'Nao foi possivel conectar ao banco de dados do Pata.'];
    }

    if ($user === null || !password_verify($password, (string) $user['pass_hash'])) {
        return ['ok' => false, 'message' => 'Usuário ou senha invalidos.'];
    }

    pata_set_authenticated_user($user);

    return ['ok' => true, 'message' => 'Login realizado com sucesso.'];
}

function pata_logout(): void
{
    pata_start_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function pata_sanitize_redirect(?string $redirect): string
{
    if ($redirect === null || $redirect === '' || $redirect[0] !== '/' || strpos($redirect, '//') === 0) {
        return '/';
    }

    if (strpos($redirect, '/login.php') === 0 || strpos($redirect, '/logout.php') === 0) {
        return '/';
    }

    return $redirect;
}

function pata_require_login(): void
{
    if (pata_is_authenticated()) {
        return;
    }

    $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header("Location: /login.php?redirect={$redirect}");
    exit;
}

function pata_csrf_token(): string
{
    pata_start_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function pata_verify_csrf(?string $token): bool
{
    pata_start_session();

    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function pata_user_initials(array $user): string
{
    $name = trim((string) ($user['full_name'] ?? $user['username'] ?? 'Pata'));
    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initial = function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
        $initials .= function_exists('mb_strtoupper') ? mb_strtoupper($initial, 'UTF-8') : strtoupper($initial);
    }

    return $initials !== '' ? $initials : 'PT';
}
