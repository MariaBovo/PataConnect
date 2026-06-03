<?php
declare(strict_types=1);

require_once __DIR__ . '/../system/auth.php';

function pata_init_project_root(): string
{
    return dirname(__DIR__);
}

function pata_init_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function pata_init_direct_request(): bool
{
    $script = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $current = realpath(__FILE__);

    return $script !== false && $current !== false && $script === $current;
}

function pata_init_is_cli(): bool
{
    return PHP_SAPI === 'cli';
}

function pata_init_is_local_request(): bool
{
    if (pata_init_is_cli()) {
        return true;
    }

    $remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';

    return in_array($remote_addr, ['127.0.0.1', '::1', ''], true);
}

function pata_init_token_matches(): bool
{
    $expected_token = pata_env('PATA_INIT_TOKEN');

    if ($expected_token === null) {
        return false;
    }

    $provided_token = $_POST['init_token'] ?? $_GET['init_token'] ?? '';

    return is_string($provided_token) && hash_equals($expected_token, $provided_token);
}

function pata_init_can_execute(): bool
{
    if (pata_init_is_local_request() || pata_init_token_matches()) {
        return true;
    }

    $current_user = pata_current_user();

    return is_array($current_user) && !empty($current_user['privileged']);
}

function pata_init_storage_directories(): array
{
    $root = pata_init_project_root();
    $directories = [
        $root . '/storage',
        $root . '/storage/sessions',
        $root . '/storage/cache',
        $root . '/analytics/artifacts',
    ];

    $results = [];

    foreach ($directories as $directory) {
        if (!is_dir($directory)) {
            @mkdir($directory, 0770, true);
        }

        $results[] = [
            'path' => $directory,
            'exists' => is_dir($directory),
            'writable' => is_dir($directory) && is_writable($directory),
        ];
    }

    return $results;
}

function pata_init_schema_path(): string
{
    return pata_init_project_root() . '/system/struct.sql';
}

function pata_init_schema_statements(): array
{
    $schema_path = pata_init_schema_path();

    if (!is_file($schema_path)) {
        throw new RuntimeException('Arquivo system/struct.sql nao encontrado.');
    }

    $sql = (string) file_get_contents($schema_path);
    $statements = [];

    foreach (explode(';', $sql) as $statement) {
        $statement = trim($statement);

        if ($statement !== '') {
            $statements[] = $statement;
        }
    }

    return $statements;
}

function pata_init_statement_table_name(string $statement): ?string
{
    if (preg_match('/CREATE\s+TABLE\s+"?([a-zA-Z_][a-zA-Z0-9_]*)"?/i', $statement, $matches) !== 1) {
        return null;
    }

    return $matches[1];
}

function pata_init_statement_index_name(string $statement): ?string
{
    if (preg_match('/CREATE\s+(?:UNIQUE\s+)?INDEX\s+"?([a-zA-Z_][a-zA-Z0-9_]*)"?/i', $statement, $matches) !== 1) {
        return null;
    }

    return $matches[1];
}

function pata_init_table_exists(PDO $pdo, string $table_name): bool
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        $statement = $pdo->prepare(
            'SELECT EXISTS (
                SELECT 1
                FROM information_schema.tables
                WHERE table_schema = current_schema()
                  AND table_name = :table_name
            )'
        );
        $statement->execute(['table_name' => $table_name]);

        return pata_bool_value($statement->fetchColumn());
    }

    if ($driver === 'sqlite') {
        $statement = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :table_name");
        $statement->execute(['table_name' => $table_name]);

        return (bool) $statement->fetchColumn();
    }

    $statement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.tables
         WHERE table_name = :table_name
         LIMIT 1'
    );
    $statement->execute(['table_name' => $table_name]);

    return (bool) $statement->fetchColumn();
}

function pata_init_index_exists(PDO $pdo, string $index_name): bool
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        $statement = $pdo->prepare(
            'SELECT EXISTS (
                SELECT 1
                FROM pg_class
                WHERE relkind = :relkind
                  AND relname = :index_name
            )'
        );
        $statement->execute(['relkind' => 'i', 'index_name' => $index_name]);

        return pata_bool_value($statement->fetchColumn());
    }

    if ($driver === 'sqlite') {
        $statement = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'index' AND name = :index_name");
        $statement->execute(['index_name' => $index_name]);

        return (bool) $statement->fetchColumn();
    }

    return false;
}

function pata_init_apply_schema(PDO $pdo): array
{
    $results = [];

    foreach (pata_init_schema_statements() as $statement) {
        $table_name = pata_init_statement_table_name($statement);
        $index_name = pata_init_statement_index_name($statement);

        if ($table_name !== null && pata_init_table_exists($pdo, $table_name)) {
            $results[] = ['name' => $table_name, 'type' => 'table', 'status' => 'skipped'];
            continue;
        }

        if ($index_name !== null && pata_init_index_exists($pdo, $index_name)) {
            $results[] = ['name' => $index_name, 'type' => 'index', 'status' => 'skipped'];
            continue;
        }

        $pdo->exec($statement);
        $results[] = [
            'name' => $table_name ?? $index_name ?? 'statement',
            'type' => $table_name !== null ? 'table' : ($index_name !== null ? 'index' : 'statement'),
            'status' => 'created',
        ];
    }

    return $results;
}

function pata_init_seed_admin(PDO $pdo): array
{
    if (!pata_init_table_exists($pdo, 'users')) {
        return ['status' => 'skipped', 'message' => 'Tabela users ainda nao existe.'];
    }

    $username = pata_env('PATA_DEFAULT_ADMIN_USER', 'pata');
    $password = pata_env('PATA_DEFAULT_ADMIN_PASSWORD', 'admin');
    $full_name = pata_env('PATA_DEFAULT_ADMIN_NAME', 'Administrador Pata');

    $statement = $pdo->prepare('SELECT "id" FROM "users" WHERE "username" = :username LIMIT 1');
    $statement->execute(['username' => $username]);

    if ($statement->fetchColumn()) {
        return ['status' => 'skipped', 'message' => "Usuario {$username} ja existe."];
    }

    $insert = $pdo->prepare(
        'INSERT INTO "users" ("full_name", "username", "pass_hash", "privileged")
         VALUES (:full_name, :username, :pass_hash, TRUE)'
    );
    $insert->execute([
        'full_name' => $full_name,
        'username' => $username,
        'pass_hash' => password_hash((string) $password, PASSWORD_DEFAULT),
    ]);

    return ['status' => 'created', 'message' => "Usuario {$username} criado."];
}

function pata_init_seed_deposit(PDO $pdo): array
{
    if (!pata_init_table_exists($pdo, 'deposit_items')) {
        return ['status' => 'skipped', 'message' => 'Tabela deposit_items ainda nao existe.'];
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM "deposit_items"')->fetchColumn();
    if ($count > 0) {
        return ['status' => 'skipped', 'message' => 'Tabela deposit_items ja contem dados.'];
    }

    $seeds = [
        ['name' => 'Vacina Polivalente', 'category' => 'Vacina', 'quantity' => 25.0, 'unit' => 'Doses', 'min_quantity' => 10.0],
        ['name' => 'Vacina Antirrábica', 'category' => 'Vacina', 'quantity' => 15.0, 'unit' => 'Doses', 'min_quantity' => 10.0],
        ['name' => 'Ração Adulto', 'category' => 'Ração', 'quantity' => 80.0, 'unit' => 'kg', 'min_quantity' => 50.0],
        ['name' => 'Ração Filhote', 'category' => 'Ração', 'quantity' => 120.0, 'unit' => 'kg', 'min_quantity' => 50.0],
        ['name' => 'Desinfetante Canil', 'category' => 'Limpeza', 'quantity' => 5.0, 'unit' => 'Litros', 'min_quantity' => 10.0]
    ];

    $insert = $pdo->prepare('
        INSERT INTO "deposit_items" ("name", "category", "quantity", "unit", "min_quantity")
        VALUES (:name, :category, :quantity, :unit, :min_quantity)
    ');

    foreach ($seeds as $item) {
        $insert->execute($item);
    }

    return ['status' => 'created', 'message' => 'Itens iniciais do almoxarifado inseridos.'];
}

function pata_init_database_status(): array
{
    try {
        $pdo = pata_db();
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $tables = [];

        foreach (pata_init_schema_statements() as $statement) {
            $table_name = pata_init_statement_table_name($statement);

            if ($table_name !== null) {
                $tables[$table_name] = pata_init_table_exists($pdo, $table_name);
            }
        }

        return [
            'ok' => true,
            'driver' => $driver,
            'tables' => $tables,
            'error' => null,
        ];
    } catch (Throwable $error) {
        return [
            'ok' => false,
            'driver' => null,
            'tables' => [],
            'error' => $error->getMessage(),
        ];
    }
}

function pata_init_run(): array
{
    $result = [
        'storage' => pata_init_storage_directories(),
        'database' => null,
        'schema' => [],
        'admin' => null,
        'deposit' => null,
        'error' => null,
    ];

    try {
        $pdo = pata_db();
        $result['database'] = [
            'ok' => true,
            'driver' => (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
        ];
        $result['schema'] = pata_init_apply_schema($pdo);
        $result['admin'] = pata_init_seed_admin($pdo);
        $result['deposit'] = pata_init_seed_deposit($pdo);
    } catch (Throwable $error) {
        $result['database'] = ['ok' => false, 'driver' => null];
        $result['error'] = $error->getMessage();
    }

    return $result;
}

function pata_init_page_state(): array
{
    $method = pata_request_method();
    $state = [
        'method' => $method,
        'can_execute' => pata_init_can_execute(),
        'csrf_token' => pata_csrf_token(),
        'status' => [
            'storage' => pata_init_storage_directories(),
            'database' => pata_init_database_status(),
        ],
        'result' => null,
        'error' => null,
    ];

    if (!in_array($method, ['GET', 'POST'], true)) {
        http_response_code(405);
        $state['error'] = 'Metodo HTTP nao permitido.';

        return $state;
    }

    if ($method === 'POST') {
        if (!$state['can_execute']) {
            http_response_code(403);
            $state['error'] = 'Inicializacao bloqueada. Execute localmente, use um usuario privilegiado ou configure PATA_INIT_TOKEN.';
        } elseif (!pata_verify_csrf($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            $state['error'] = 'Sessao expirada. Recarregue a pagina e tente novamente.';
        } else {
            $state['result'] = pata_init_run();
            $state['status']['storage'] = pata_init_storage_directories();
            $state['status']['database'] = pata_init_database_status();
        }
    }

    return $state;
}

function pata_init_render_storage(array $storage): void
{
    foreach ($storage as $directory) {
        $status = $directory['exists'] && $directory['writable'] ? 'ok' : 'erro';
        echo '<li><strong>' . pata_init_escape($status) . '</strong> ' . pata_init_escape($directory['path']) . '</li>';
    }
}

function pata_init_render_tables(array $tables): void
{
    if ($tables === []) {
        echo '<li>Nenhuma tabela verificada.</li>';
        return;
    }

    foreach ($tables as $table => $exists) {
        echo '<li><strong>' . pata_init_escape($exists ? 'ok' : 'pendente') . '</strong> ' . pata_init_escape($table) . '</li>';
    }
}

function pata_init_render_result(?array $result): void
{
    if ($result === null) {
        return;
    }

    echo '<section class="panel result-panel">';
    echo '<h2>Resultado</h2>';

    if ($result['error'] !== null) {
        echo '<p class="error">' . pata_init_escape($result['error']) . '</p>';
    }

    echo '<h3>Schema</h3><ul>';
    foreach ($result['schema'] as $item) {
        echo '<li><strong>' . pata_init_escape($item['status']) . '</strong> ' . pata_init_escape($item['type']) . ' ' . pata_init_escape($item['name']) . '</li>';
    }
    echo '</ul>';

    if ($result['admin'] !== null) {
        echo '<h3>Usuario inicial</h3>';
        echo '<p><strong>' . pata_init_escape($result['admin']['status']) . '</strong> ' . pata_init_escape($result['admin']['message']) . '</p>';
    }

    if ($result['deposit'] !== null) {
        echo '<h3>Almoxarifado inicial</h3>';
        echo '<p><strong>' . pata_init_escape($result['deposit']['status']) . '</strong> ' . pata_init_escape($result['deposit']['message']) . '</p>';
    }

    echo '</section>';
}

function pata_init_render_page(array $state): void
{
    $database = $state['status']['database'];
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pata - Inicializacao</title>
    <style>
        body {
            margin: 0;
            padding: 2rem;
            background: #12141a;
            color: #f2f5f7;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        main {
            max-width: 980px;
            margin: 0 auto;
            display: grid;
            gap: 1rem;
        }

        .panel {
            background: #1a1c23;
            border: 1px solid #333333;
            border-radius: 8px;
            padding: 1.25rem;
        }

        h1, h2, h3 {
            margin-top: 0;
        }

        ul {
            margin-bottom: 0;
        }

        .status-ok {
            color: #7cc7aa;
        }

        .error {
            color: #ffd1d1;
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        button, a {
            border: 1px solid #6c757d;
            border-radius: 6px;
            padding: 0.7rem 0.95rem;
            background: transparent;
            color: #f2f5f7;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        button:hover, a:hover {
            border-color: #7cc7aa;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <main>
        <section class="panel">
            <h1>Inicializacao do Pata</h1>
            <p>Esta pagina cria a estrutura local necessaria, aplica o schema em <code>system/struct.sql</code> e garante o usuario inicial.</p>
            <?php if ($state['error'] !== null): ?>
                <p class="error"><?php echo pata_init_escape($state['error']); ?></p>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>Status</h2>
            <h3>Diretorios</h3>
            <ul><?php pata_init_render_storage($state['status']['storage']); ?></ul>

            <h3>Banco</h3>
            <?php if ($database['ok']): ?>
                <p class="status-ok">Conectado via <?php echo pata_init_escape($database['driver']); ?>.</p>
                <ul><?php pata_init_render_tables($database['tables']); ?></ul>
            <?php else: ?>
                <p class="error"><?php echo pata_init_escape($database['error']); ?></p>
            <?php endif; ?>
        </section>

        <?php pata_init_render_result($state['result']); ?>

        <section class="panel">
            <h2>Executar</h2>
            <form class="actions" method="POST" action="<?php echo pata_form_action(); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo pata_init_escape($state['csrf_token']); ?>">
                <?php if (isset($_GET['init_token'])): ?>
                    <input type="hidden" name="init_token" value="<?php echo pata_init_escape((string) $_GET['init_token']); ?>">
                <?php endif; ?>
                <button type="submit" <?php echo $state['can_execute'] ? '' : 'disabled'; ?>>Inicializar sistema</button>
                <a href="/login.php">Voltar para login</a>
            </form>
        </section>
    </main>
</body>
</html>
    <?php
}

if (pata_init_direct_request()) {
    if (pata_init_is_cli()) {
        echo "Iniciando inicializacao do Pata via CLI...\n";
        $result = pata_init_run();
        if ($result['error'] !== null) {
            echo "Erro: " . $result['error'] . "\n";
            exit(1);
        }
        echo "Storage:\n";
        foreach ($result['storage'] as $dir) {
            echo "  [" . ($dir['exists'] && $dir['writable'] ? "OK" : "ERRO") . "] " . $dir['path'] . "\n";
        }
        echo "Banco de dados: [" . ($result['database']['ok'] ? "OK" : "ERRO") . "] via " . $result['database']['driver'] . "\n";
        echo "Schema:\n";
        foreach ($result['schema'] as $item) {
            echo "  [" . $item['status'] . "] " . $item['type'] . " " . $item['name'] . "\n";
        }
        if ($result['admin'] !== null) {
            echo "Administrador: [" . $result['admin']['status'] . "] " . $result['admin']['message'] . "\n";
        }
        if ($result['deposit'] !== null) {
            echo "Almoxarifado: [" . $result['deposit']['status'] . "] " . $result['deposit']['message'] . "\n";
        }
        echo "Inicializacao concluida com sucesso!\n";
    } else {
        pata_init_render_page(pata_init_page_state());
    }
}
